<?php
/**
 * Класс для работы с файлами изображений (сохранение, изменение размеров, пропорций...)
 * @version  1.75
 * @modified 27.января.2013
 */

/**
* 1) Реализована работа с IMagick расширением:
*    - нереализовано скругление углов
*    - нереализовано добавление watermark'a на оригинальное изображение
*    - нереализовано позиционирование(crop_h, crop_v) при жестком кропе, только center-center
*/
namespace bff\img;
 class Thumbnail
 {
    /** @var array текущие актуальные параметры обрабатываемого изображения */
    protected $img;
    /** @var CInput компонент */
    public $errors = null; 
    /** @var array текстовки ошибок */
    private $lang = array();
    /** @var string путь к шрифтам */
    private $sFontDir = '';
    /** @var array resource загруженного watermark изображения */
    private $aWatermarkSources = array();
    /** @var boolean сохранять ли оригинал */
    private $saveOriginal = false;
    /** @var string библиотека обработки: gd, im */
    private $saveMethod = 'gd';
    
    /** @var IMagick object */
    private $im = false; 
    private $imShell = false;
    private $imPath = false;
    private $imTmpPath = '';

    /** @var array найтройки по-умолчанию */
    private $aDef = array(
        'filename' => '',    // имя файла сохраняемого изображения
       /* сохранять ли пропорции изображения
        *  1) не указана размерность одной из сторон thumbnail'а - сторона для которой размер указан сожмется до указанных размеров, а вторая сторона сожмется пропорционально
        *  2) указаны оба размера тогда изображение пропорционально уменьшается (исходя из стороны которая ужимается меньше), а вторая обрезается до нужного размера(для указания какую часть изображения необходимо оставить укажите crop_v и crop_h)
        * если false :
        *  1) не указана размерность одной из сторон thumbnail'а - по этой стороне изображение сожмется не сохраняя пропорций
        *  2) указаны оба размера - изображение сжимается до нужных размеров не сохраняя пропорции
        */
       'autofit'  => true,  //
         'autofit_nocrop' => false, // уменьшить, до 'width' или 'height' оставив оригинальные пропорции
         'autofit_nocrop_bg' => 0xFFFFFF, // если в заданных рамках показывать изображение меньше, тогда нужно заполнять пространство каким либо фоном
         'crop_h'   => 'center', // позиционирование по горизонтали(X) (left, center,right) (при autofit)
         'crop_v'   => 'center', // позиционирование по вертикали(Y) (top, center, bottom) (при autofit)
       'width'    => 100,   // ширина конечного изображения: integer; false - авто, пропорциональная высоте
       'height'   => false, // высота конечного изображения: integer; false - авто, пропорциональная ширине
       'no_resize_if_less' => true, // не ресайзить, если размеры исходного изображения меньше требуемых
       'src_x'    => 0, // расположение картинки на холсте по оси X
       'src_y'    => 0, // расположение картинки на холсте по оси Y
       'crop_width'     => false, // ?
       'crop_height'    => false, // ?
       'round_corners'         => false, // скруглять углы
       'round_corners_color'   => false, // цвет фона скругленных углов, формат: 0xFFFFFF или false - прозрачные
       'round_corners_radius'  => 5,     // радиус скругления углов (проценты 0-100)
       'round_corners_rate'    => 5,     // уровень сглаживания краев закругленных углов, (0 - 20), чем больше - тем больше памяти требуется
       'original_sizes'        => false, // сделать копию, без изменения размеров
       'watermark'             => false, // накладывать ли водяной знак (wm)
       'watermark_src'         => '',    // путь к wm-изображению
       'watermark_pos_x'       => 'right',  // позиция wm по горизонтали(X): left, center,right
       'watermark_pos_y'       => 'bottom', // позиция wm по вертикали(Y): top, center, bottom
       'watermark_padding_v'   => 10,    // отступ wm по вертикали (снизу и сверху)
       'watermark_padding_h'   => 10,    // отступ wm по горизонтали (слева и справа)
       'watermark_font_size'   => 12,    // размер шрифта для wm
       'watermark_font_color'  => '#ffffff',   // цвет шрифта для wm, формат: #FFAAAA
       'watermark_font'        => 'arial.ttf', // шрифт для wm
       'watermark_on_original' => false, // наносить ли wm на оригинал изображения   ! НЕ РЕАЛИЗОВАНО
       'watermark_resizable'   => false,  // сохранять ли пропорции wm
       'quality'               => 85);   // качество конечного JPEG изображения

    /**
    * Создание объекта
    * @param string $sImagePath - путь к исходному изображению, если изображение нужно загрузить, то указывается путь, как его сохранять
    * @param boolean $bSaveOriginal - сохранять ли оригинал
    * @param mixed $sInputName  - имя file input'a на html форме, если изображение загружается
    */
    public function __construct($sImagePath, $bSaveOriginal = true, $sInputName = false)
    {
        $this->errors = \Errors::i();
        
        $this->saveOriginal = $bSaveOriginal;
        if( SITEHOST == 'max.rio-dev.shalb.com') $this->setSaveMethod('im');
        
        if($sInputName!==false)
        {
            if( ! isset($_FILES[$sInputName]) || $_FILES[$sInputName]['error']) {
                $this->error('no_file');
                return;
            }
            
            if($this->saveOriginal)
            {
                $dir = pathinfo($sImagePath, PATHINFO_DIRNAME);
                if( ! is_dir( $dir )) {
                   $this->error('wrong_path');
                   return;
                }
                
                $sImagePath = rtrim($sImagePath, '\\/');

                if( ! move_uploaded_file($_FILES[$sInputName]['tmp_name'], $sImagePath )) {
                    $this->error('wrong_path');   
                }
                else {
                    @chmod($sImagePath, 0777);
                }
            }
            else
            {
                 $sImagePath = $_FILES[$sInputName]['tmp_name'];
            } 
        }
        
        if( ! $sImagePath) {
           $this->error('wrong_path');
           return; 
        }
        
        if( ! file_exists($sImagePath) ) {
           $this->error('no_file');
           return;
        }
        
        if( ! $this->_isImageFile($sImagePath, false)) {
            $this->error('file_isnt_image');
            return;
        }

        $this->img['orig_filename'] = $sImagePath;
        
        $size = getimagesize($sImagePath);
        $this->img['src'] = $sImagePath;
        $this->img['format'] = $size[2];
        $this->img['orig_width']  = $this->img['src_width'] = $size[0];
        $this->img['orig_height'] = $this->img['src_height'] = $size[1];
        $this->aDef['type'] = $size[2];
        
        $this->setFontDir( PATH_CORE.'fonts'.DS );
    }
    
    private function error($key) 
    {
        static $lang = array(
            'no_init_watermark'   => 'Инициализируйте водяной знак',
            'no_watermark_src'    => 'Не указан источник водяного знака',
            'gd_not_loaded'       => 'Библиотека GD не загружена',
            'no_file'             => 'Файла не существует',
            'wrong_path'          => 'Неверный путь',
            'no_language_support' => 'Шрифт не поддерживает данный язык',
            'file_isnt_image'     => 'Файл не является изображением',
            'wrong_file_format'   => 'Неверный формат файла',
            'no_th_size'          => 'Не указаны размеры thumbnaila',
            'watermark_src_isnt_image' => 'Источник водяного знака не является изображением',
            'no_imagick_path'     => 'Не указан путь к ImageMagick',
            'no_rouncorners'      => 'Невозможно выполнить закругление углов',
            //ошибки imagemagick
            'im_prop_w_err'    => 'ImageMagick:Невозможно пропорционально уменьшить изображение по ширине', 
            'im_prop_h_err'    => 'ImageMagick:Невозможно пропорционально уменьшить изображение по высоте', 
            'im_croping_err'   => 'ImageMagick:Невозможно обрезать изображение до заданных размеров',
            'im_unprop_h_err'  => 'ImageMagick:Невозможно непропорционально уменьшить изображение по высоте',
            'im_unprop_w_err'  => 'ImageMagick:Невозможно непропорционально уменьшить изображение по ширине',
            'im_unprop_err'    => 'ImageMagick:Невозможно непропорционально уменьшить изображение',
            'im_wmorig_err'    => 'ImageMagick:Невозможно нанести wmark на оригинал изображения',
            'im_wmcreate_err'  => 'ImageMagick:Невозможно создать изображение для wmark',
            'im_wmadd_err'     => 'ImageMagick:Невозможно добавить wmark на изображение',
            'im_wmresize_err'  => 'ImageMagick:Невозможно изменить размер wmark',
            'im_wmanimate_err' => 'ImageMagick:Невозможно взять первый кадр анимированного gif изображения',
        );
        
        $this->errors->set( $lang[$key] ); 
    }
    
    /**
    * Устанавливаем метод сохранения изображений: GD, ImageMagick
    * @param string $sMethod библиотека обрабоки: gd, im
    * @param string $sPath путь к ImageMagick
    */
    public function setSaveMethod($sMethod, $sPath = '')
    {
        if( ! in_array($sMethod, array('gd','im')))
            return false;
        
        $this->saveMethod = $sMethod;
        
        if($this->isIMagick()) {
            $this->imShell = !extension_loaded('imagick');
            $this->imPath = $sPath;
        }
    }
    
    private function isIMagick()
    {
         return ($this->saveMethod == 'im');
    }

    private function isGD()
    {
         return ($this->saveMethod == 'gd');
    }
    
    /**
     * Обработка и сохранение изображений по указанным параметрам
     * - параметры формируются на основе указанных + параметров по-умолчаню ($this->aDef)
     * - далее происходит сохранение thumbnail-ов с помощью GD или Image Magik (в зависимости от $this->saveMethod default(gd))
     * @param array $aParams параметры (0=>параметры, 1=>параметры, ...) @see $this->aDef
     * @return boolean
     */
    public function save($aParams)
    {
        foreach($aParams as $k=>$v)
        {
            foreach($this->aDef as $key=>$val)
            {
                // дополняем параметрами по-умолчанию
                if( ! isset($aParams[$k][$key])) {
                    $aParams[$k][$key] = $val;
                }
            }
            if($aParams[$k]['watermark'] && $aParams[$k]['watermark_src']=='')
                $this->error('no_watermark_src');
        }

        if( ! $this->errors->no())
            return false;

        if($this->isGD()) // Сохраняем при помощи билиотеки GD
        {
            // Проверяем подключена ли библиотека GD
            if( !(extension_loaded('gd') || extension_loaded('gd2')) ) {
                $this->error('gd_not_loaded');
                return false;
            }

            //@set_time_limit(6000);
            
            switch($this->img['format'])
            {
                case IMAGETYPE_GIF:  { $this->img['src'] = ImageCreateFromGIF($this->img['src']);  } break;
                case IMAGETYPE_JPEG: { $this->img['src'] = ImageCreateFromJPEG($this->img['src']); } break;
                case IMAGETYPE_PNG:  { $this->img['src'] = ImageCreateFromPNG($this->img['src']);  } break;
                case IMAGETYPE_WBMP: { $this->img['src'] = ImageCreateFromWBMP($this->img['src']); } break;
                default: {  $this->error('wrong_file_format'); }
            }
            
            if( ! $this->errors->no()) {
                return false;
            }
            
            foreach($aParams as $val)
            {
                foreach($val as $k => $v) {
                   $this->img[$k] = $v; // переносим настройки в текущие актуальные
                }
                
                // ! отсутствует логика, поскольку при пакетной обработке проиходит множественное накладывание
                $this->img['watermark_on_original'] = false;

                $this->img['src_x'] = (isset($v['src_x'])?$v['src_x']:0);
                $this->img['src_y'] = (isset($v['src_y'])?$v['src_y']:0);
                $this->img['orig_width']  = ( ! empty($v['crop_width'])? $v['crop_width'] : $this->img['src_width']);
                $this->img['orig_height'] = ( ! empty($v['crop_height'])? $v['crop_height'] : $this->img['src_height']);
                $this->img['coef_height'] = $this->img['height']/$this->img['orig_height'];
                $this->img['coef_width'] = $this->img['width']/$this->img['orig_width'];
                if($this->img['coef_height'] == 0) {
                   $this->img['coef_height'] = $this->img['coef_width'];
                }
                else if($this->img['coef_width'] == 0) {
                        $this->img['coef_width'] = $this->img['coef_height'];
                }

                if(  $this->img['original_sizes'] &&
                   ! $this->img['watermark'] &&
                   ! $this->img['round_corners']) {
                       @copy($this->img['orig_filename'], $this->img['filename']);
                       continue;
                }
                
                $this->calcSizesGD();
                $this->saveFileGD();
            }
            
            imagedestroy($this->img['src']);
            
            // Удаление кэшированных watermark'ов
            if( ! empty($this->aWatermarkSources)) {
                foreach($this->aWatermarkSources as $v) {
                    imagedestroy($v['src']);
                }
            }

            if( ! $this->saveOriginal) {
                unlink($this->img['orig_filename']);
            }
        } 
        else // Сохраняем при помощи билиотеки ImageMagick
        {
            if($this->imShell && empty($this->imPath)) {
                $this->error('no_imagick_path');
                return false;
            }

            foreach($aParams as $val)
            {
                foreach($val as $k=>$v) {
                   $this->img[$k] = $v; // переносим настройки в текущие актуальные
                }
                
                // ! отсутствует логика, поскольку при пакетной обработке проиходит множественное накладывание
                $this->img['watermark_on_original'] = false;

                $this->img['src_x'] = (isset($v['src_x'])?$v['src_x']:0);
                $this->img['src_y'] = (isset($v['src_y'])?$v['src_y']:0);
                $this->img['orig_width']  = ( ! empty($v['crop_width']) ? $v['crop_width'] : $this->img['src_width']);
                $this->img['orig_height'] = ( ! empty($v['crop_height']) ? $v['crop_height'] : $this->img['src_height']);
                $this->img['coef_height'] = $this->img['height'] / $this->img['orig_height'];
                $this->img['coef_width']  = $this->img['width'] / $this->img['orig_width'];
                if($this->img['coef_height'] == 0) {
                   $this->img['coef_height'] = $this->img['coef_width'];
                }
                else if($this->img['coef_width'] == 0) {
                        $this->img['coef_width'] = $this->img['coef_height'];
                }

                if(  $this->img['original_sizes'] &&
                   ! $this->img['watermark'] &&
                   ! $this->img['round_corners']) {
                       @copy($this->img['orig_filename'], $this->img['filename']);
                       continue;
                }                
                
                $this->imTmpPath = dirname($this->img['filename']).'/';
                 
                // если gif анимированный и используется наложение watermark или закругление углов тогда работаем с первым кадром gif'a
                $isAnimatedGIF = false;
                if( $this->img['format'] === IMAGETYPE_GIF && ($this->img['watermark'] || $this->img['round_corners']) )
                {
                   // Проверяет является ли обрабатываемое изображение анимированным gif'ом
                   // с помощью identify получает строку с параметрами файла, если gif массив - он анимированный
                   $sParam = exec($this->imPath.'identify '.$this->img['orig_filename']);
                   preg_match('/.gif\[(\d)\]/Us', $sParam , $aFind); 
                   if(isset($aFind[1])) {
                       $sNewFile = $isAnimatedGIF = $this->imTmpPath.'__firstFrame.gif';
                       exec($this->imPath.'convert '.$this->img['orig_filename'].'[0] '.$sNewFile);
                       $this->img['orig_filename'] = $sNewFile;
                   }
                }

                if( ! $this->imShell) {
                    $this->im = new Imagick( $this->img['orig_filename'] );
                    if($this->img['format'] === IMAGETYPE_JPEG) {
                       $this->im->setCompressionQuality( $this->img['quality'] );
                    }
                }
                
                try {
                    $this->saveFileIM();
                } catch(ImagickException $e) {
                    $this->errors->set($e->getMessage());
                }

                // удаляем первый кадр анимированного гифа, если наносился wm или round_corners
                if( $isAnimatedGIF!==false ) {
                    unlink( $isAnimatedGIF );
                }
                
                if( ! $this->imShell) {
                    $this->im->destroy();
                }
                
                //next^
            }
            
            if( ! $this->saveOriginal)
                unlink($this->img['src']);
            
        }
        
        return $this->errors->no();
    }
    
    /**
    * Сохраняет thumbnail с помощью GD
    */
    private function saveFileGD()
    {
        $this->img['dest'] = ImageCreateTrueColor($this->img['width'], $this->img['height']);

        $bFillBg = ( ! $this->img['original_sizes'] && $this->img['autofit_nocrop'] && !empty($this->img['autofit_nocrop_bg']) );

        //сохранение прозрачности у png
        if($this->img['type'] == IMAGETYPE_PNG )
        {
           ImageAlphaBlending($this->img['dest'], false);
           ImageSaveAlpha($this->img['dest'], true);
           $transparent = imagecolorallocatealpha($this->img['dest'], 255, 255, 255, 127);
           imagefilledrectangle($this->img['dest'], 0, 0, $this->img['width'], $this->img['height'], $transparent);
        }
        // сохранение прозрачности у gif
        else if($this->img['type'] == IMAGETYPE_GIF)
        {
            $colorcount = imagecolorstotal($this->img['src']);
            imagetruecolortopalette($this->img['dest'],true,$colorcount);
            imagepalettecopy($this->img['dest'],$this->img['src']);
            $transparentcolor = imagecolortransparent($this->img['src']);
            if( ! $bFillBg) imagefill($this->img['dest'], 0, 0, $transparentcolor);
            imagecolortransparent($this->img['dest'],$transparentcolor);
        }

        if( $this->img['original_sizes'] ) {
            //копируем в оригинальных размерах
            imagecopy($this->img['dest'], $this->img['src'], 0, 0, 0, 0, $this->img['src_width'], $this->img['src_height']);
        } else {
            // размещаем на холсте заданных размеров(width X height) уменьшенное пропроционально(dest_width X dest_height)
            // исходное изображение без обрезания какой либо части изображения
            // с позиционированием по нужной стороне (dest_x, dest_y)
            if($this->img['autofit_nocrop'] && isset($this->img['dest_x']))
            {
                // заполняем холст фоновым цветом
                if($bFillBg) {
                    $bg_clr = $this->img['autofit_nocrop_bg']; // формат: 0xFFFFFF
                    $bg_clr = @imagecolorallocate($this->img['dest'], (integer)($bg_clr%0x1000000/0x10000), (integer)($bg_clr%0x10000/0x100), $bg_clr%0x100);
                    imagefill($this->img['dest'], 0, 0, $bg_clr);
                }
                imagecopyresampled($this->img['dest'], $this->img['src'],
                                   $this->img['dest_x'], $this->img['dest_y'], $this->img['src_x'], $this->img['src_y'],
                                   $this->img['dest_width'], $this->img['dest_height'],
                                   $this->img['orig_width'], $this->img['orig_height']);
            }
            //копируем в необходимых размерах
            else
            {
                imagecopyresampled($this->img['dest'], $this->img['src'],
                                   0, 0, $this->img['src_x'], $this->img['src_y'],
                                   $this->img['width'], $this->img['height'],
                                   $this->img['orig_width'], $this->img['orig_height']);
            }
        }
        
        //ставим watermark
        if($this->img['watermark'])
        {
            $this->watermarkInitGD($this->img['watermark_src'], $this->img['watermark_pos_x'], $this->img['watermark_pos_y'], $this->img['watermark_padding_h'], $this->img['watermark_padding_v'], $this->img['watermark_on_original']);
            $this->watermarkPrintGD($this->img['dest'], intval($this->img['width']), intval($this->img['height']));
        }
        //закругляем углы
        if($this->img['round_corners'])
        {
            $this->roundCornersGD($this->img['dest'], $this->img['round_corners_color'], $this->img['round_corners_radius'], $this->img['round_corners_rate']);
        }       
        //сохраняем картинку
        switch($this->img['type'])
        {
            case IMAGETYPE_GIF:  { imageGIF( $this->img['dest'], $this->img['filename']);  } break;
            case IMAGETYPE_JPEG: { imageJPEG($this->img['dest'], $this->img['filename'], $this->img['quality']); } break;
            case IMAGETYPE_PNG:  { imagePNG( $this->img['dest'], $this->img['filename']);  } break;
            case IMAGETYPE_WBMP: { imageWBMP($this->img['dest'], $this->img['filename']);  } break;
        }

        $this->clearOriginalImage();

        ImageDestroy ($this->img['dest']);

    }
    
    /**
    * Расчет величины сторон, коэфициентов сжатия той или другой стороны, в зависимости от переданных параметров
    * если выставлен флаг autofit - сжатие происходит с учетом пропорций сторон,
    *   если при этом не указана ширина или высота thumbnail-а то исходное изображение ужимается так, что величина стороны с неуказанным размером уменьшается пропорционально
    *   если указана и ширина и высота thumbnail то изображение пропорционально уменьшается, а та сторона коэф сжатия которой больше - обрезается точно по заданным размерам
    * если  autofit = false то изображение уменьшается не пропорционально
    *   если при этом не указана величина одной из сторон изображения, то она не сжимается(т.е сжатие по указанной стороне)
    */
    private function calcSizesGD()
    {
        // сохраняем в оригинальных размерах
        if($this->img['original_sizes'])
        {
            $this->img['width'] = $this->img['src_width'];
            $this->img['height'] = $this->img['src_height'];
            return;
        }

        // если пропорции будут сохранятся
        if($this->img['autofit'])
        {
            if( ! $this->img['height']) // не указана высота thumbnail'a
            {
                // если изображение меньше указанной ширины, оставляем оригинальные размеры
                if( $this->img['no_resize_if_less'] ) {
                    if( $this->img['orig_width'] <= $this->img['width'] ) {
                        $this->img['width'] = $this->img['orig_width'];
                        $this->img['height'] = $this->img['orig_height'];
                        $this->img['original_sizes'] = true;
                        return;
                    }
                }
                // пропорционально уменьшаем, для того чтобы вписаться по-ширине
                $this->img['height'] = ($this->img['width'] / $this->img['orig_width']) * $this->img['orig_height'];
            }
            else if( ! $this->img['width']) // не указана ширина thumbnail'a
            {
                // если изображение меньше указанной высоты, оставляем оригинальные размеры
                if( $this->img['no_resize_if_less'] ) {
                    if( $this->img['orig_height'] <= $this->img['height'] ) {
                        $this->img['width'] = $this->img['orig_width'];
                        $this->img['height'] = $this->img['orig_height'];
                        $this->img['original_sizes'] = true;
                        return;
                    }
                }
                // пропорционально уменьшаем, для того чтобы вписаться по-высоте
                $this->img['width'] = ($this->img['height'] / $this->img['orig_height']) * $this->img['orig_width'];
            }
            else
            {
                // указаны значения и высоты и ширины:
                if($this->img['height']>0 && $this->img['width']>0)
                {
                    if($this->img['no_resize_if_less']) {
                        // размеры исходного изображения меньше требуемых
                        // не растягиваем => вписываем в заданные размеры + заливаем фоном
                        if( $this->img['orig_width'] <= $this->img['width']
                         && $this->img['orig_height'] <= $this->img['height'])
                        {
                            $this->img['autofit_nocrop'] = true;
                        }
                    }

                    if($this->img['autofit_nocrop'])
                    {
                        // уменьшаем пропрорционально, без обрезания
                        $this->calcAutofitNocrop();
                    } else {
                        // обрезаем изображение точно по размерам

                        // проверяем какая сторона ужимается меньше всего
                        $nWidthDif  = ($this->img['width'] / $this->img['orig_width']);
                        $nHeightDif = ($this->img['height'] / $this->img['orig_height']);
                        // сжимаем пропорционально по ширине или по высоте (по меньшему сжатию) сторону,
                        // коэф. сжатия которой больше, обрезаем
                        if($nWidthDif > $nHeightDif) {
                            $this->cropHeight($nWidthDif, $this->img['crop_v']);
                        } else {
                            $this->cropWidth($nHeightDif, $this->img['crop_h']);
                        }
                    }
                }
                else{
                    $this->error('no_th_size');
                }
            }
        }
        // ужимать не сохраняя пропорции
        else
        {
            if( ! $this->img['height']) {
                // не указана высота  thumbnail'a (выставляем высоту оригинального изображения)
                $this->img['height'] = $this->img['orig_height'];
            }
            else if( ! $this->img['width']) {
                // не указана ширина thumbnail'a (выставляем ширину оригинального изображения)
                $this->img['width'] = $this->img['orig_width'];
            }
        }
    }

    private function calcAutofitNocrop()
    {
        $this->img['dest_x'] = 0;
        $this->img['dest_y'] = 0;

        $W = $this->img['width'];
        $H = $this->img['height'];

        $bLog = false;

        // размеры исходного и конечного изображений совпадают
        if( $W >= $this->img['orig_width'] &&
            $H >= $this->img['orig_height'])
        {
            $this->img['dest_width']  = $this->img['orig_width'];
            $this->img['dest_height'] = $this->img['orig_height'];

            if( $H > $this->img['dest_height'] )
            {
                $nEmptyPart = ( $H - $this->img['dest_height'] );
                switch($this->img['crop_v']) {
                    case 'top':    $this->img['dest_y'] = 0; break;
                    case 'bottom': $this->img['dest_y'] = $nEmptyPart; break;
                    case 'center': default: $this->img['dest_y'] = round($nEmptyPart / 2);
                }
            }
            if( $W > $this->img['dest_width'] )
            {
                $nEmptyPart = ( $W - $this->img['dest_width'] );
                switch($this->img['crop_h']) {
                    case 'left':  $this->img['dest_x'] = 0; break;
                    case 'right': $this->img['dest_x'] = $nEmptyPart; break;
                    case 'center': default: $this->img['dest_x'] = round($nEmptyPart / 2);
                }
            }
        }
        else
        {
            if($bLog) {
                bff::log('-------------------');
                bff::log('src  = [W='.$this->img['orig_width'].',H='.$this->img['orig_height'].']');
                bff::log('need = [W='.$W.',H='.$H.']');
            }

            $nDiffWidth = ($this->img['orig_width'] - $W);
            $nDiffHeight = ($this->img['orig_height'] - $H);
            $nKoef = ( $nDiffWidth > $nDiffHeight ?
                       ( $W / $this->img['orig_width'] ) :
                       ( $H / $this->img['orig_height'] ) );

            $this->img['dest_width']  = round($this->img['orig_width'] * $nKoef);
            $this->img['dest_height'] = round($this->img['orig_height'] * $nKoef);

            $bPosVertical = ( $nDiffWidth > $nDiffHeight );

            if($bLog) {
                bff::log('cжали по '.( $nDiffWidth > $nDiffHeight ? 'ширине' : 'высоте' ));
                bff::log('tmp = [W='.$this->img['dest_width'].',H='.$this->img['dest_height'].']');
            }

            if($this->img['dest_width'] > $W) { // дожимаем по ширине
                $nKoef = ($W / $this->img['dest_width']);
                $this->img['dest_width']  = $this->img['dest_width'] * $nKoef;
                $this->img['dest_height'] = $this->img['dest_height'] * $nKoef;
                $bPosVertical = true;
            } else if($this->img['dest_height'] > $H) { // дожимаем по высоте
                $nKoef = ($H / $this->img['dest_height']);
                $this->img['dest_width']  = $this->img['dest_width'] * $nKoef;
                $this->img['dest_height'] = $this->img['dest_height'] * $nKoef;
                $bPosVertical = false;
            }

            if( $bPosVertical ) {
                $nEmptyPart = ( $H - $this->img['dest_height'] );
                switch($this->img['crop_v']) {
                    case 'top':    $this->img['dest_y'] = 0; break;
                    case 'bottom': $this->img['dest_y'] = $nEmptyPart; break;
                    case 'center': default: $this->img['dest_y'] = round($nEmptyPart / 2);
                }
            } else {
                $nEmptyPart = ( $W - $this->img['dest_width'] );
                switch($this->img['crop_h']) {
                    case 'left':  $this->img['dest_x'] = 0; break;
                    case 'right': $this->img['dest_x'] = $nEmptyPart; break;
                    case 'center': default: $this->img['dest_x'] = round($nEmptyPart / 2);
                }
            }
        }

        if($bLog) {
            bff::log('destSize = [W='.$this->img['dest_width'].',H='.$this->img['dest_height'].'],
                       destPos = [x='.$this->img['dest_x'].',y='.$this->img['dest_y'].'],
                       позиционируем = по '.($bPosVertical ? 'вертикали': 'горизонтали'));
        }
    }
    
    /**
     * Обрезаем нужную часть изображения по ширине
     */
    private function cropWidth($nKoef, $align = 'center')
    {
        if($this->isGD()) 
        {
            $nCropingPart = (($this->img['orig_width'] * $nKoef) - $this->img['width']) / $nKoef;
            switch ($align)
            {
                case 'left':
                    $this->img['src_x'] = 0;
                    $this->img['orig_width'] = $this->img['orig_width'] - $nCropingPart;
                    break;
                case 'right':
                    $this->img['src_x'] = $nCropingPart;
                    $this->img['orig_width'] = $this->img['orig_width'] - $nCropingPart;
                    break;
                case 'center':
                default:
                    $this->img['src_x'] = (int)$nCropingPart/2;
                    $this->img['orig_width'] = $this->img['orig_width'] - (int)$nCropingPart;
            }
        } else {
            switch ($align)
            {
                case 'left':
                    $this->img['gravity'] = 'West';
                    break;
                case 'right':
                    $this->img['gravity'] = 'East';
                    break;
                case 'center':
                default:
                    $this->img['gravity'] = 'Center';
            }
        } 
    }
    
    /**
     * Обрезаем нужную часть изображения по высоте
     */
    private function cropHeight($nKoef, $valign = 'center')
    {
        if($this->isGD()) 
        {
            $nCropingPart = (($this->img['orig_height'] * $nKoef) - $this->img['height']) / $nKoef;
            switch ($valign)
            {
                case 'top':
                    $this->img['src_y'] = 0;
                    $this->img['orig_height'] = $this->img['orig_height'] - $nCropingPart;
                    break;
                case 'bottom':
                    $this->img['src_y'] = $nCropingPart;
                    $this->img['orig_height'] = $this->img['orig_height'] - $nCropingPart;
                    break;
                case 'center':
                default:
                    $this->img['src_y'] = (int)$nCropingPart / 2;
                    $this->img['orig_height'] = $this->img['orig_height'] - (int)$nCropingPart;
            }
        } else {
            switch ($valign)
            {
                case 'top':
                    $this->img['gravity'] = 'North';
                    break;
                case 'bottom':
                    $this->img['gravity'] = 'South';
                    break;
                case 'center':
                default:
                    $this->img['gravity'] = 'Center';
            }            
        }
    }
    
    /**
     * Очищает $this->img для повторного перезаполнения(используется и GD и IMagick)
     */
    private function clearOriginalImage()
    {
        $this->img['width']         = false;
        $this->img['height']        = false;
        $this->img['filename']      = '';
        $this->img['autofit']       = true;
        $this->img['crop_h']        = 'center';
        $this->img['crop_v']        = 'center';
        $this->img['orig_width']    = 0;
        $this->img['orig_height']   = 0;
        $this->img['round_corners'] = false;
        $this->img['watermark']     = false;
    }
	
	/**
     * Получение расширения файла (без точки)
     * @param string $sPath путь к файлу
     * @return string расширение без точки
     */
    public function _getExtension($sPath)
    {
        $res = mb_strtolower(pathinfo($sPath, PATHINFO_EXTENSION));

        return ($res == 'jpeg' ? 'jpg' : $res);
    }
	
	/**
     * Проверка, является ли файл изображением
     * @param mixed $sPath путь к файлу
     * @param boolean $bCheckExtension проверять расширение файла
     * @return boolean
     */
    public function _isImageFile($sPath, $bCheckExtension = false)
    {
        if ($bCheckExtension && !in_array($this->_getExtension($sPath), array('gif', 'jpg', 'png'))) {
            return false;
        }

        $imSize = getimagesize($sPath);
        if (empty($imSize)) {
            return false;
        }
        if (in_array($imSize[2], array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
            return true;
        }

        return false;
    }
    
    /**
     * Инициализация водяного знака.
     * @param mixed $sWatermark путь к файлу изображения(используемого в качестве wm) или текст
     * @param mixed $hPosition позиция по горизонтали
     * @param mixed $vPosition позиция по вертикали
     * @param mixed $xPadding отступ от края по oси X
     * @param mixed $yPadding отступ от края по oси Y
     * @param mixed $bMakeSourceWatermark флаг, ставить ли знак на оригинале изображения
     */
    private function watermarkInitGD($sWatermark, $hPosition='right', $vPosition='bottom', $xPadding = 15, $yPadding=15, $bMakeSourceWatermark = true)
    {
        if( ! $this->img['watermark_resizable'])
        {
              $this->img['coef_width']  = 1;
              $this->img['coef_height'] = 1;
        }

        if(file_exists($sWatermark))   
        {
            if($this->_isImageFile($sWatermark, true))
            {
                // создаем resource для watermark с таким путем (если еще не создан)
                if( !isset($this->aWatermarkSources[$sWatermark]) )
                {
                    $size = getimagesize($sWatermark);
                    
                    $this->aWatermarkSources[$sWatermark]['width']  = $size[0];
                    $this->aWatermarkSources[$sWatermark]['height'] = $size[1];
                                   
                    switch($size[2])
                    {
                        case IMAGETYPE_GIF:  $this->aWatermarkSources[$sWatermark]['src'] = ImageCreateFromGIF($sWatermark);  break;
                        case IMAGETYPE_JPEG: $this->aWatermarkSources[$sWatermark]['src'] = ImageCreateFromJPEG($sWatermark); break;
                        case IMAGETYPE_PNG:  $this->aWatermarkSources[$sWatermark]['src'] = ImageCreateFromPNG($sWatermark);  break;
                        case IMAGETYPE_WBMP: $this->aWatermarkSources[$sWatermark]['src'] = ImageCreateFromWBMP($sWatermark); break;
                        default: return;
                    }
                }

                // определяем нужные размеры wm исходя из коэффициентов
                if($this->img['watermark_resizable']) {
                    $nCoef = ( $this->img['coef_width'] > $this->img['coef_height'] ? $this->img['coef_height'] : $this->img['coef_width'] );
                    $dst_width = intval($this->aWatermarkSources[$sWatermark]['width'] * $nCoef);
                    $dst_height = intval($this->aWatermarkSources[$sWatermark]['height'] * $nCoef);
                } else {
                    $dst_width = $this->aWatermarkSources[$sWatermark]['width'];
                    $dst_height = $this->aWatermarkSources[$sWatermark]['height'];
                }

                $this->img['wm'] = imagecreatetruecolor($dst_width, $dst_height);
                
                ImageAlphaBlending($this->img['wm'], false);
                ImageSaveAlpha($this->img['wm'], true);
                ImageCopyResized($this->img['wm'], $this->aWatermarkSources[$sWatermark]['src'], 0, 0, 0, 0,
                                 $dst_width, $dst_height,
                                 $this->aWatermarkSources[$sWatermark]['width'], $this->aWatermarkSources[$sWatermark]['height']);
                
                $this->img['wm_width'] = $dst_width;
                $this->img['wm_height'] = $dst_height;
                $this->img['watermark_pos_x'] = $hPosition;
                $this->img['watermark_pos_y'] = $vPosition;
                $this->img['watermark_padding_h'] = intval($xPadding * $this->img['coef_width']);
                $this->img['watermark_padding_v'] = intval($yPadding * $this->img['coef_height']);
            }
            else
            {
                unset($this->img['wm']);
                $this->error('watermark_src_isnt_image');
            } 
        }
        else
        { 
            $nCoef = ( $this->img['coef_width'] > $this->img['coef_height'] ? $this->img['coef_height'] : $this->img['coef_width'] );
            $nFontSize = round($this->img['watermark_font_size']*$nCoef);
            $nFontSize = round($nFontSize / 1.333);
            //определение координат нужного прямоугольника под WM
            $aImageDimmention = imagettfbbox($nFontSize, 0, $this->sFontDir.$this->img['watermark_font'], $sWatermark);

            //ширина (разница по oX между нижними левым и правым углом прямоугольника)
            $nImgWidth = $aImageDimmention[4] - $aImageDimmention[6];
            //высота (разница по oY между левыми верхним и нижним углом прямоугольника)
            $nImgHeight = $aImageDimmention[1] - $aImageDimmention[7];
            if( ! $nImgHeight)
            {
                $this->error('no_language_support');
                $this->img['wm'] = false;
            }
            
            if($this->errors->no())
            {
                $nImgHeight*=1.6;//небольшое увеличение высоты чтобы поместились выступающие края букв

                
                $this->img['wm'] = ImageCreateTrueColor($nImgWidth,$nImgHeight);
                
                if($this->img['type'] == IMAGETYPE_GIF)
                {
                    //Определяем индекс прозрачного цвета у gif
                    $colorcount = imagecolorstotal($this->img['src']);
                    imagetruecolortopalette($this->img['wm'],true,$colorcount);
                    imagepalettecopy($this->img['wm'],$this->img['wm']);
                    $trans = imagecolortransparent($this->img['wm']);
                    imagefill($this->img['wm'],0,0,$trans);
                    imagecolortransparent($this->img['wm'],$trans);
                    $textcolor=imagecolorallocate($this->img['wm'],(integer)($this->img['watermark_font_color']%0x1000000/0x10000), (integer)($this->img['watermark_font_color']%0x10000/0x100), $this->img['watermark_font_color']%0x100);
                }
                else
                {
                    //Определяем индекс прозрачного цвета у png
                    $opacity = imagecolorallocatealpha($this->img['wm'],255,255,255,127);
                    imagefill($this->img['wm'],0,0,$opacity);
                    ImageAlphaBlending($this->img['wm'], false);
                    ImageSaveAlpha($this->img['wm'], true);
                    $textcolor = imagecolorallocatealpha($this->img['wm'], (integer)($this->img['watermark_font_color']%0x1000000/0x10000), (integer)($this->img['watermark_font_color']%0x10000/0x100), $this->img['watermark_font_color']%0x100,0);
                }
                // Наносим текст

                imagettftext($this->img['wm'],$nFontSize,0,0,intval($nImgHeight*0.8),$textcolor,$this->sFontDir.$this->img['watermark_font'],$sWatermark);
                
                $this->img['wm_width'] = $nImgWidth;
                $this->img['wm_height'] = $nImgHeight;
                $this->img['watermark_pos_x'] = $hPosition;
                $this->img['watermark_pos_y'] = $vPosition;
                $this->img['watermark_padding_h'] = intval($xPadding*$this->img['coef_width']);
                $this->img['watermark_padding_v'] = intval($yPadding*$this->img['coef_height']);
            }
        }

        // нанесение wm на оригинал        
        if($bMakeSourceWatermark)
        {
            $this->img['only_src'] = imageCreateTrueColor($this->img['orig_width'], $this->img['orig_height']);
            ImageCopy($this->img['only_src'], $this->img['src'], 0, 0, 0, 0, $this->img['orig_width'], $this->img['orig_height']);
        
            $this->watermarkPrintGD($this->img['only_src'], intval($this->img['orig_width']), intval($this->img['orig_height']));

            switch($this->img['format'])
            {
                case IMAGETYPE_GIF:  { imageGIF($this->img['only_src'],  $this->img['orig_filename']);  } break;
                case IMAGETYPE_JPEG: { imageJPEG($this->img['only_src'], $this->img['orig_filename'], $this->img['quality']); } break;
                case IMAGETYPE_PNG:  { imagePNG($this->img['only_src'],  $this->img['orig_filename']); } break;
                case IMAGETYPE_WBMP: { imageWBMP($this->img['only_src'], $this->img['orig_filename']); } break;
            }

            imageDestroy($this->img['only_src']);
        }
    }
    
    /**
    * Нанесение водяного знака
    * @param mixed $resImg дескриптор изображения
    * @param mixed $sDestWidth координаты правого нижнего угла водяного знака по X
    * @param mixed $sDestHeight координаты правого нижнего угла водяного знака по Y
    */
    private function watermarkPrintGD(&$resImg, $sDestWidth, $sDestHeight)
    {
        if(isset($this->img['wm']) && $this->img['wm'])
        {
            //определяем позиционирование
            switch($this->img['watermark_pos_x']){
                case 'left':   $placementX = $this->img['watermark_padding_h']; break;
                case 'right':  $placementX = $sDestWidth - $this->img['wm_width'] - $this->img['watermark_padding_h']; break;
                default:$placementX = round( ($sDestWidth - $this->img['wm_width']) / 2); break;
            }

            switch($this->img['watermark_pos_y']){
                case 'top':    $placementY = $this->img['watermark_padding_v']; break;
                case 'bottom': $placementY = $sDestHeight - $this->img['wm_height'] - $this->img['watermark_padding_v']; break;
                default:$placementY = round( ($sDestHeight - $this->img['wm_height']) / 2); break;
            }
            //накладывем watermark
            imagecopy($resImg,
                $this->img['wm'],
                $placementX, $placementY, 
                0, 0, $this->img['wm_width'], $this->img['wm_height']);
        }
        else if( ! isset($this->img['wm']))
        {
            $this->error('no_init_watermark');
        }
        if($this->img['wm'])
            imagedestroy($this->img['wm']);
    }
    
    /**
    * Закругление углов
    * @param mixed $resImg дескриптор изображения
    * @param mixed $cornercolor цвет углов в 16-ной кодировке. Если false - прозрачный
    * @param mixed $radius радиус закругления
    * @param mixed $rate сглаживание закругления, максимум - 20
    */
    private function roundCornersGD(&$resImg, $cornercolor, $radius = 5, $rate = 5)
    {
        if($radius<=0) return false;
        if($radius > 100) $radius = 100;
        if($rate <=0) $rate = 5;
        if($rate > 20) $rate = 20;
                
        $width = ImagesX($resImg);
        $height = ImagesY($resImg);
        
        $radius = ($width<=$height)?round((($width/100)*$radius)/2):round((($height/100)*$radius)/2); 

        $rs_radius = $radius * $rate;
        $rs_size = $rs_radius * 2;

        ImageAlphablending($resImg, false);
        ImageSaveAlpha($resImg, true);

        $corner = ImageCreateTrueColor($rs_size, $rs_size);
        ImageAlphablending($corner, false);
        
        if($cornercolor===false) { // указан ли прозрачный цвет.
            $this->img['type'] = IMAGETYPE_PNG;
        }
        if($this->img['type'] == IMAGETYPE_PNG) {
            $trans = ImageColorAllocateAlpha($corner, 255, 255, 255, 127);
        } else {
            $trans = ImageColorAllocateAlpha($corner,(integer)($cornercolor%0x1000000/0x10000), (integer)($cornercolor%0x10000/0x100), $cornercolor%0x100,0);
        } 
        
        ImageFilledRectangle($corner,0,0,$rs_size,$rs_size,$trans);
         
        $positions = array(
            array(0, 0, 0, 0),
            array($rs_radius, 0, $width - $radius, 0),
            array($rs_radius, $rs_radius, $width - $radius, $height - $radius),
            array(0, $rs_radius, 0, $height - $radius),
        );
     
        foreach ($positions as $pos) {
            ImageCopyResampled($corner, $resImg, $pos[0], $pos[1], $pos[2], $pos[3], $rs_radius, $rs_radius, $radius, $radius);
        }
        
        $lx = $ly = 0;
        $i = -$rs_radius;
        $y2 = -$i;
        $r_2 = $rs_radius * $rs_radius;
     
        for (; $i <= $y2; $i++) {
     
            $y = $i;
            $x = sqrt($r_2 - $y * $y);
     
            $y += $rs_radius;
            $x += $rs_radius;
     
            ImageLine($corner, $x, $y, $rs_size, $y, $trans);
            ImageLine($corner, 0, $y, $rs_size - $x, $y, $trans);
     
            $lx = $x;
            $ly = $y;
        }
        foreach ($positions as $i => $pos) {
            ImageCopyResampled($resImg, $corner, $pos[2], $pos[3], $pos[0], $pos[1], $radius, $radius, $rs_radius, $rs_radius);
        }
             
        ImageDestroy($corner);    
    }
   
    # ------------------------------------------------------------------------------------------------------------------------
    # ImageMagick
   
    private function saveFileIM()
    {
        if($this->imShell) {
            $filename_orig = escapeshellarg($this->img['orig_filename']);
            $filename_dest = escapeshellarg($this->img['filename']);
        }
        
        do{
        
        // crop по указанным координатам и размерам
        if( ! empty($this->img['crop_width']) || ! empty($this->img['crop_height'])) {
            if($this->imShell) {
                // not implemented   
            } else {
                $this->im->cropImage($this->img['orig_width'], $this->img['orig_height'], $this->img['src_x'], $this->img['src_y']);
            }
        }
            
        // если пропорции будут сохранятся
        if($this->img['autofit'])
        {
            // указаны значения и высоты и ширины, обрезать изображение точно по размерам
            if( $this->img['height']<=0 && $this->img['width']<=0 ) {
                $this->error('no_th_size');
                break;
            }
            
            if($this->imShell)
            {
                if( ! $this->img['height'])
                {
                    // не указана высота thumbnail'a пропорциональное изображение по ширине
                    if(exec($this->imPath.'convert '.$filename_orig.' 
                            -resize '.$this->img['width'].'x
                            -quality '.$this->img['quality'].' '.$filename_dest))
                        $this->error('im_prop_w_err');
                }
                else if( ! $this->img['width'])
                {
                    // не указана ширина thumbnail'a пропорциональное изображение по высоте
                    if(exec($this->imPath.'convert '.$filename_orig.' 
                            -resize x'.$this->img['height'].'
                            -quality '.$this->img['quality'].' '.$filename_dest))
                        $this->error('im_prop_h_err');
                }         
                else
                {
                    if( $this->img['autofit_nocrop'] ) {
                        // not implemented
                    }

                    // проверяем какая сторона ужимается меньше всего
                    $nWidthDif = $this->img['width'] / $this->img['orig_width'];
                    $nHeightDif = $this->img['height'] / $this->img['orig_height'];
                    // сжимаем пропорционально по ширине или по высоте (по меньшему сжатию) сторону, коэф сжатия которой больше обрезаем
                    if($nWidthDif > $nHeightDif)
                    {
                        $this->cropHeight($nWidthDif, $this->img['crop_v']);
                        if(exec($this->imPath.'convert '.$filename_orig.' 
                                -resize '.$this->img['width'].'x
                                -quality '.$this->img['quality'].' '.$filename_dest))
                           $this->error('im_prop_h_err'); 
                    }
                    else                        
                    {
                        $this->cropWidth($nHeightDif, $this->img['crop_h']);
                        if(exec($this->imPath.'convert '.$filename_orig.' 
                                -resize x'.$this->img['height'].'
                                -quality '.$this->img['quality'].' '.$filename_dest))
                            $this->error('im_prop_w_err');
                    }
                    
                    // обрезка изображения до точного размера
                    if(exec($this->imPath.'convert '.$filename_dest.' 
                                -gravity '.$this->img['gravity'].'
                                -quality 100 
                                -crop '.$this->img['width'].'x'.$this->img['height'].'+0+0 +repage
                                '.$filename_dest))
                        $this->error('im_croping_err');
                }
            } else {
                if( ! $this->img['height']) {
                    if( !$this->im->thumbnailImage($this->img['width'], 0) )
                            $this->error('im_prop_w_err');
                } else if( ! $this->img['width']) {
                    if( ! $this->im->thumbnailImage(0, $this->img['height']) )
                            $this->error('im_prop_h_err');
                } else {
                    if( $this->img['autofit_nocrop'] )
                    {
                        if( ! empty($this->img['autofit_nocrop_bg'])) {
                            $bg_clr = $this->img['autofit_nocrop_bg']; // формат: 0xFFFFFF
                            //
                        }
                        if( ! $this->im->thumbnailImage($this->img['width'], $this->img['height'], true) )
                            $this->error('im_croping_err');
                    } else {
                        // фиксированной ширины и высоты, только center-center
                        if( ! $this->im->cropThumbnailImage($this->img['width'], $this->img['height']) )
                            $this->error('im_croping_err');
                        $this->im->setImagePage(0, 0, 0, 0); // фикс для gif
                    }
                }
            }
        }
        // ужимать не сохраняя пропорции
        else 
        {
            if($this->imShell)
            {
                if( ! $this->img['height'] && $this->img['width']>0)
                {
                  /* не указана высота  thumbnaila (выставляем высоту оригинального изображения) */  
                  if(exec($this->imPath.'convert '.$filename_orig.' 
                        -scale '.$this->img['width'].'x'.$this->img['orig_height'].'!
                        -quality '.$this->img['quality'].' '.$filename_dest))
                    $this->error('im_unprop_h_err'); 
                }                              
                else if( ! $this->img['width']&& $this->img['height']>0)
                {
                  /* не указана ширина thumbnaila (выставляем ширину оригинального изображения) */     
                  if(exec($this->imPath.'convert '.$filename_orig.'
                        -scale '.$this->img['orig_width'].'x'.$this->img['height'].'!
                        -quality '.$this->img['quality'].' '.$filename_dest))
                    $this->error('im_unprop_w_err');
                }                               
                else{
                  if(exec($this->imPath.'convert '.$filename_orig.' 
                        -scale '.$this->img['width'].'x'.$this->img['height'].'!
                        -quality '.$this->img['quality'].' '.$filename_dest))
                     $this->error('im_unprop_err');   
                }
            } else {
                if( ! $this->im->thumbnailImage($this->img['width'], $this->img['height']) )
                    $this->error('im_unprop_err'); 
            }
        } 
        
        } while(false);
                                           
        // нанесение watermark
        if($this->img['watermark'] && $this->img['watermark_src'])
        {
           $this->watermarkPrintIM();
        }
        
        // закругление углов
        if($this->img['round_corners'])
        {
            $this->roundCornersIM($this->img['filename'], $this->img['round_corners_color'], $this->img['round_corners_radius'], $this->img['round_corners_rate']);
        }

        if( ! $this->imShell) {
            $this->im->writeImage($this->img['filename']);
        }
        
        $this->clearOriginalImage();
     }
     
    /**
     * Приводим положение картинки к формату ImageMagick
     * @param mixed $vertical - положение по вертикали
     * @param mixed $horizontal - положение по горизонтали
     * @param mixed $pos
     */
    private function convertDimentionsIM($vertical, $horizontal, $pos = false)
    {
         if( ! empty($pos))
         {
             $i_w = $pos['i_w'];
             $i_h = $pos['i_h'];
             $t_w = $pos['t_w'];
             $t_h = $pos['t_h'];
             $paddingV = $pos['p_v'];
             $paddingH = $pos['p_h'];
             
             $x_right  = $i_w - $t_w - $paddingH;
             $x_center = round(($i_w - $t_w)/2);
             $y_center = round(($i_h - $t_h)/2);
             $y_bottom = $i_h - $t_h - $paddingV;
             
             $gravity = array(           
                'top-left'      => array($paddingH, $paddingV), // x - horizontal, y- vertical             
                'top-center'    => array($x_center, $paddingV),
                'top-right'     => array($x_right,  $paddingV),
                'center-left'   => array($paddingH, $y_center),
                'center-center' => array($x_center, $y_center),
                'center-right'  => array($x_right,  $y_center),
                'bottom-left'   => array($paddingH, $y_bottom),
                'bottom-center' => array($x_center, $y_bottom),
                'bottom-right'  => array($x_right,  $y_bottom),
             );
             return $gravity[$vertical.'-'.$horizontal];
         } else {
             $gravity = array(
                'top-left'      => array('NorthWest', IMagick::GRAVITY_NORTHWEST),
                'top-center'    => array('North',     IMagick::GRAVITY_NORTH),
                'top-right'     => array('NorthEast', IMagick::GRAVITY_NORTHEAST),
                'center-left'   => array('West',      IMagick::GRAVITY_WEST),
                'center-center' => array('Center',    IMagick::GRAVITY_CENTER),
                'center-right'  => array('East',      IMagick::GRAVITY_EAST),
                'bottom-left'   => array('SouthWest', IMagick::GRAVITY_SOUTHWEST),
                'bottom-center' => array('South',     IMagick::GRAVITY_SOUTH),
                'bottom-right'  => array('SouthEast', IMagick::GRAVITY_SOUTHEAST),
             );
             return $gravity[$vertical.'-'.$horizontal][ ($this->imShell ? 0 : 1) ];
         }
     }

    /**
     * Наносим watermark нa изображение с помощью Image Magick
     */
    private function watermarkPrintIM()
    {
        $sGravity = $this->convertDimentionsIM($this->img['watermark_pos_y'], $this->img['watermark_pos_x']);

        $sTmpWatermarkFile = $this->imTmpPath.'def_w_mark.png';
        
        // на основе изображения
        if(file_exists($this->img['watermark_src']))
        {
            /** 
             * если указано что wm надо ужимать вместе с картинкой
             * то получаем размеры загружаемого wm
             * и уменьшаем его пропорционально с основным изображением
             */
            if($this->imShell)
            {
                if($this->img['watermark_resizable'])
                {
                    $sParam = exec($this->imPath.'identify '.$this->img['watermark_src']);
                    $aParam = split(' ', $sParam);
                    $aSize  = split('x',$aParam[2]);
                    $nWMwidth  = intval($aSize[0] * $this->img['coef_width']);
                    $nWMheight = intval($aSize[1] * $this->img['coef_height']);
                    if( exec($this->imPath.'convert '.$this->img['watermark_src'].' -scale '.$nWMwidth.'x'.$nWMheight.' -quality '.$this->img['quality'].' '.$sTmpWatermarkFile) )
                        $this->error('im_wmresize_err');
                }
                
                // накладываем wm на thumbnail
                if( exec($this->imPath.'composite -dissolve 100 -gravity '.$sGravity.' -geometry +'.$this->img['watermark_padding_h'].'+'.$this->img['watermark_padding_v'].' '.$sTmpWatermarkFile.' '. $this->img['filename'].' '.$this->img['filename']) )
                    $this->error('im_wmadd_err');
                
                // накладываем wm на оригинал
                if($this->img['watermark_on_original'])
                {
                   if(exec($this->imPath.'composite -dissolve 100 -gravity '.$sGravity.' -geometry +'.$this->img['watermark_padding_h'].'+'.$this->img['watermark_padding_v'].' '. $this->img['watermark_src'] .' '. $this->img['orig_filename'].' '.$this->img['orig_filename']))
                        $this->error('im_wmorig_err');
                }
            } 
            else 
            {
                $wm = new Imagick($this->img['watermark_src']);
                $wmSize = array($wm->getImageWidth(), $wm->getImageHeight());
                if($this->img['watermark_resizable']) {
                    $wmSize[0] = intval($wmSize[0] * $this->img['coef_width']);  //width
                    $wmSize[1] = intval($wmSize[1] * $this->img['coef_height']); //height
                    $wm->thumbnailImage( $wmSize[1], $wmSize[0] );
                }

                // накладываем wm на thumbnail
                $aGravity = $this->convertDimentionsIM($this->img['watermark_pos_y'], $this->img['watermark_pos_x'],
                            array('t_w'=>$wmSize[0],'t_h'=>$wmSize[1],
                                  'i_w'=>$this->im->getImageWidth(),'i_h'=>$this->im->getImageHeight(),
                                  'p_v'=>$this->img['watermark_padding_v'], 'p_h'=>$this->img['watermark_padding_h']));
                
                // накладывает watermark на thumbnail
                if( ! $this->im->compositeImage( $wm, Imagick::COMPOSITE_OVER, $aGravity[0], $aGravity[1] ) )
                    $this->error('im_wmadd_err'); 
                
                // накладываем wm на оригинал
                if($this->img['watermark_on_original'])
                {                   
                   $imSrc = new Imagick($this->img['orig_filename']);
                   
                   $aGravitySrc = $this->convertDimentionsIM($this->img['watermark_pos_y'], $this->img['watermark_pos_x'],
                                array('t_w'=>$wmSize[0],'t_h'=>$wmSize[1],
                                      'i_w'=>$imSrc->getImageWidth(),'i_h'=>$imSrc->getImageHeight(),
                                      'p_v'=>$this->img['watermark_padding_v'], 'p_h'=>$this->img['watermark_padding_h']));

                   if( ! $imSrc->compositeImage( $wm, Imagick::COMPOSITE_OVER, $aGravitySrc[0], $aGravitySrc[1] ) )
                        $this->error('im_wmorig_err');                     
                   else {
                       $imSrc->writeImage( $this->img['orig_filename'] );
                   }
                   $imSrc->destroy();   
                } 
                
                $wm->destroy();            
            }
        }
        else // на основе текста
        {
            //задаем цвет шрифта wm в формате #ffffff
            $sText      = $this->img['watermark_src'];
            $sTextColor = $this->img['watermark_font_color'];
            $sFont      = $this->sFontDir.'/'.$this->img['watermark_font'];
            $nFontSize  = $this->img['watermark_font_size'];
            $nCoef = 1;
            if($this->img['watermark_resizable']) {
                // размер шрифта уменьшается пропорционально картинке
                $nCoef = ($this->img['coef_width']>$this->img['coef_height'] ? $this->img['coef_height'] : $this->img['coef_width']);
                $nFontSize = round($nCoef * $nFontSize);
            }

            if($this->imShell) 
            {       
                // создает изображение png из текста переданного как wm (размер шрифта не изменяется)
                if(exec($this->imPath.'convert -background "none"  -fill "'.$sTextColor.'" -font '.$sFont.' -pointsize '.($nFontSize).' label:"'.addslashes($sText).'" '.$sTmpWatermarkFile)) 
                    $this->error('im_wmorig_err'); 
                
                // накладывает watermark на thumbnail
                if(exec($this->imPath.'composite -dissolve 100 -gravity '.$sGravity.' -geometry +'.($this->img['watermark_padding_h'] * $nCoef).'+'.$this->img['watermark_padding_v']*$nCoef.' '.$sTmpWatermarkFile.' '.$this->img['filename'] .' '.$this->img['filename']))
                    $this->error('im_wmcreate_err');   

                // накладывает watermark на оригинал изображения
                if($this->img['watermark_on_original']) {
                   if(exec($this->imPath.'composite -dissolve 100 -gravity '.$sGravity.' -geometry +'.$this->img['watermark_padding_h'].'+'.$this->img['watermark_padding_v'].' '.$sTmpWatermarkFile.' '. $this->img['orig_filename'].' '.$this->img['orig_filename']))
                        $this->error('im_wmorig_err');
                }
            } 
            else 
            {
                $wmText = new ImagickDraw();
                $wmText->setGravity( IMagick::GRAVITY_CENTER );
                $wmText->setFont( $sFont );
                $wmText->setFontSize( $nFontSize );
                $wmText->setFillColor( $sTextColor ); 
                
                $wm = new Imagick();
                $wmTextProps = $wm->queryFontMetrics( $wmText, $sText );
                $wmW = intval( $wmTextProps['textWidth'] );
                $wmH = intval( $wmTextProps['textHeight'] );
                $wm->newImage( $wmW, $wmH, new ImagickPixel( 'transparent' ), 'png' );
                $wm->setImageVirtualPixelMethod(Imagick::VIRTUALPIXELMETHOD_TRANSPARENT);
                $wm->annotateImage( $wmText, 0, 0, 0, $sText );
                
                $aGravity = $this->convertDimentionsIM($this->img['watermark_pos_y'], $this->img['watermark_pos_x'],
                            array('t_w'=>$wmW,'t_h'=>$wmH,
                                  'i_w'=>$this->im->getImageWidth(),'i_h'=>$this->im->getImageHeight(),
                                  'p_v'=>$this->img['watermark_padding_v'], 'p_h'=>$this->img['watermark_padding_h']));
                
                // накладывает watermark на thumbnail
                //$this->im->annotateImage( $wmText, $aGravity[0], $aGravity[1], 0, $sText );
                
                if( ! $this->im->compositeImage( $wm, Imagick::COMPOSITE_OVER, $aGravity[0], $aGravity[1] ) )
                    $this->error('im_wmcreate_err');   

                // накладывает watermark на оригинал изображения
                if($this->img['watermark_on_original'])
                {
                   $imSrc = new Imagick($this->img['orig_filename']);
                   
                   $aGravitySrc = $this->convertDimentionsIM($this->img['watermark_pos_y'], $this->img['watermark_pos_x'],
                                array('t_w'=>$wmW,'t_h'=>$wmH,
                                      'i_w'=>$imSrc->getImageWidth(),'i_h'=>$imSrc->getImageHeight(),
                                      'p_v'=>$this->img['watermark_padding_v'], 'p_h'=>$this->img['watermark_padding_h'] ));

                   if( ! $imSrc->compositeImage( $wm, Imagick::COMPOSITE_OVER, $aGravitySrc[0], $aGravitySrc[1] ) )
                        $this->error('im_wmorig_err'); 
                   else {
                       $imSrc->writeImage( $this->img['orig_filename'] );
                   }
                   $imSrc->destroy();
                }
                
                $wmText->destroy();
                $wm->destroy();
            }
        }
            
        // удаление временного файла с wm
        $this->img['orig_filename'] = $this->img['src'];
        if(file_exists($sTmpWatermarkFile)) {
            unlink($sTmpWatermarkFile);
        }
    }
    
    /**
     * Создание закругленных уголков с помощью Image Magick
     */ 
    private function roundCornersIM($filename, $cornercolor, $radius=5, $rate=5)
    {   
        // Если png/gif тогда углы прозрачные
        if($this->img['format'] == IMAGETYPE_GIF || $this->img['format'] == IMAGETYPE_PNG) {
            $cornercolor = false;
        }
        
        if($radius<=0) return false;
        if($radius > 100) $radius = 100;
        if($rate <=0) $rate = 5;
        else if($rate > 20) $rate = 20;
                
        $width  = $this->img['width'];
        $height = $this->img['height'];
        $radius = ($width<=$height)?((($width/100)*$radius)/2):((($height/100)*$radius)/2); 

        if($cornercolor === false)
        {
            $sOldName = $filename;
            $sNewName = $filename;
            if($this->img['format']!==IMAGETYPE_PNG) {
                $nLength  = strrpos($filename,'.');
                $sNewName = substr($filename,0,$nLength).'.png';
            }
            
            if($this->imShell)
            {
                // если углы прозрачные закругляем уголки без заливки цветом
                $cornercolor = 'transparent';
                if(exec($this->imPath.'convert "'.$sOldName.'" -border 0 -format "roundrectangle 0,0 %[fx:w],%[fx:h] '.$radius.','.$radius.'" info: > '.$this->imTmpPath.'tmp.mvg'))
                    $this->error('no_rouncorners');
                if(exec($this->imPath.'convert  "'.$sOldName.'" -border 0 -matte -channel RGBA -threshold -1 -background '.$cornercolor.' -fill none  -strokewidth 0 -draw "@'.$this->imTmpPath.'tmp.mvg"  '.$this->imTmpPath.'__overlay.png'))
                    $this->error('no_rouncorners');
                if(exec($this->imPath.'convert "'.$sOldName.'" -border 0 -matte -channel RGBA -threshold -1 -background '.$cornercolor.' -fill white  -strokewidth 0 -draw "@'.$this->imTmpPath.'tmp.mvg" '.$this->imTmpPath.'__mask.png '))
                    $this->error('no_rouncorners');
                 
                if(exec($this->imPath.'convert "'.$sOldName.'" -matte -bordercolor '.$cornercolor.'  -border 0 '.$this->imTmpPath.'__mask.png -compose DstIn -composite '.$this->imTmpPath.'__overlay.png -compose Over -composite  -quality 95 "'.$sNewName.'"  '))
                    $this->error('no_rouncorners');
                 
                if($sOldName != $sNewName) {
                    unlink($sOldName);
                    rename($sNewName, $sOldName);
                }   
                unlink($this->imTmpPath.'__mask.png'); 
                unlink($this->imTmpPath.'__overlay.png'); 
            } else {         
                $this->im->setImageFormat('png');
                $this->im->roundCorners($radius, $radius);
            }
        } 
        else
        {
            if($this->imShell)
            {
                // если углы не прозрачные закругляем уголки и заливаем из выставленным цветом 
                if(exec($this->imPath.'convert "'.$filename.'"  -border 0 -format "fill '.$cornercolor.' rectangle 0,0 %[fx:w],%[fx:h]" info: > '.$this->imTmpPath.'tmp.mvg'))
                    $this->error('no_rouncorners'); 
                if(exec( $this->imPath.'convert "'.$filename.'" -matte -channel RGBA -threshold -1 -draw "@'.$this->imTmpPath.'tmp.mvg" PNG:'.$this->imTmpPath.'__underlay.png'))
                   $this->error('no_rouncorners');
                if(exec($this->imPath.'convert '.$this->imTmpPath.'__underlay.png ( "'.$filename.'" ( +clone -threshold -1 -draw "fill black polygon 0,0 0,'.$radius.' '.$radius.',0 fill white circle '.$radius.','.$radius.' '.$radius.',0" ( +clone -flip ) -compose Multiply -composite ( +clone -flop ) -compose Multiply -composite -blur 1x1 ) +matte -compose CopyOpacity -composite ) -matte -compose over -composite "'.$filename.'"'))
                    $this->error('no_rouncorners');
                
                unlink($this->imTmpPath.'__underlay.png');
            } else {
                $this->im->setImageFormat('png');
                $this->im->roundCorners($radius, $radius);
//                $bg = new Imagick();
//                $bg->newImage($this->im->getImageWidth(), $this->im->getImageHeight(), new ImagickPixel($cornercolor));
//                $bg->compositeImage($this->im, Imagick::COMPOSITE_OVER, 0, 0);
//                $this->im->setImage($bg);
            }
        }
        
        if(file_exists($this->imTmpPath.'tmp.mvg')) {
            unlink($this->imTmpPath.'tmp.mvg'); 
        }
        
        $this->img['orig_filename'] = $this->img['src'];
        
        return $this->errors->no();
     }

    public function getErrors()
    {
        return $this->errors->show();
    }
           
    /**
    * Устанавливает лимит оперативной памяти
    * @param int $nSize - объем памяти в мегабайтах
    */
    public function setMemoryLimit($nSize)
    {
        $nSize = intval($nSize);
        if($nSize<=0) $nSize=1;
        ini_set('memory_limit', $nSize.'M');
    }

    /**
    * Указание пути к шрифтам
    * @param string путь к директории c файлами шрифтов
    */
    function setFontDir($sPath)
    {
        $this->sFontDir = $sPath;
    }

    function getAutofitResultSize($nWidth = false, $nHeight = false)
    {
        if( ! $nHeight) # не указана высота, возвращаем autofit-высоту
        {
          return intval( ($nWidth/$this->img['orig_width'])*$this->img['orig_height'] );
        }
        else if( ! $nWidth) # не указана ширина, возвращаем autofit-ширину
        {
          return intval( ($nHeight/$this->img['orig_height'])*$this->img['orig_width'] );
        }         
    }

    function getOriginalWidth()
    {
        return (int)$this->img['src_width'];
    }

    function getOriginalHeight()
    {
        return (int)$this->img['src_height'];
    }
	public function isVertical()
    {
        return $this->getOriginalWidth() < $this->getOriginalHeight();
    }

}    
