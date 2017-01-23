<?php

class BBSItemImages extends CImagesUploaderTable
{
    /**
     * Константы размеров
     */
    const szSmall = 's'; # small - форма, список-обычный, список-карта: balloon, просмотр(thumbnails)
    const szMedium = 'm'; # medium - список-галерея
    const szView = 'v'; # view - просмотр
    const szZoom = 'z'; # zoom - просмотр zoom
    const szOrginal = 'o'; # original - оригинальное изображение

    protected function initSettings()
    {
        $this->path = bff::path('items', 'images');
        $this->pathTmp = bff::path('tmp', 'images');
        $this->url = bff::url('items', 'images');
        $this->urlTmp = bff::url('tmp', 'images');

        $this->tableRecords = TABLE_BBS_ITEMS;
        $this->tableImages = TABLE_BBS_ITEMS_IMAGES;

        $this->folderByID = true; # раскладываем файлы изображений по папкам (id(5)=>0, id(1005)=>1, ...)
        $this->filenameLetters = 8; # кол-во символов в названии файла
        $this->limit = BBS::CATS_PHOTOS_MAX; # лимит фотографий у объявления
        $this->maxSize = 5242880; # 2мб (2мб: 2097152, 5мб: 5242880)

        $this->minWidth = 150;
        $this->minHeight = 150;
        $this->maxWidth = 5000;
        $this->maxHeight = 5000;

        # настройки водяного знака
        $watermark = $this->watermarkSettings();
        $watermarkSettings = array();
        if (!empty($watermark['file']['path'])) {
            $watermarkSettings = array(
                'watermark'       => true,
                'watermark_src'   => $watermark['file']['path'],
                'watermark_pos_x' => $watermark['pos_x'],
                'watermark_pos_y' => $watermark['pos_y'],
            );
        }

        # размеры изображений
        $this->sizes = array(
            self::szSmall   => array(
                'width'    => 98,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 98)
            ),
            self::szMedium  => array(
                'width'    => 180,
                'height'   => false,
                'vertical' => array('width' => false, 'height' => 160)
            ),
            self::szView    => array(
                    'width'    => 670,
                    'height'   => false,
                    'vertical' => array('width' => false, 'height' => 447) + $watermarkSettings,
                ) + $watermarkSettings,
            self::szZoom    => array(
                    'width'    => 1000,
                    'height'   => false,
                    'vertical' => array('width' => false, 'height' => 670) + $watermarkSettings,
                ) + $watermarkSettings,
            self::szOrginal => array('o' => true),
        );

        # размеры изображений, полный URL которых необходимо кешировать
        $this->useFav = true;
        foreach (array(self::szSmall, self::szMedium) as $v) {
            # ключ размера => поле в базе
            $this->sizesFav[$v] = 'img_' . $v;
        }
    }

    /**
     * Получение данных о текущих настройках водяного знака
     * @param mixed $settings array - сохраняем настройки, false - получаем текущие
     * @param array
     */
    public function watermarkSettings($settings = false)
    {
        $configKey = 'bbs_item_images_wm';
        if ($settings === false) {
            $settings = config::get($configKey);
            $settings = (!empty($settings) && strpos($settings, 'a:') === 0 ? func::unserialize($settings) : array());
            $settings = $this->input->clean_array($settings, array(
                    'file'  => TYPE_ARRAY,
                    'pos_x' => TYPE_STR,
                    'pos_y' => TYPE_STR,
                )
            );

            return $settings;
        } else {
            $settings = array_merge($this->watermarkSettings(), (array)$settings);
            config::save($configKey, serialize($settings));
        }
    }

    /**
     * Сохранение настроек водяного знака
     * @param string $fileUpload ключ для загрузки файла
     * @param boolean $fileDelete выполнить удаление файла (ранее загруженного)
     * @param string $positionX ключ позиции знака по-вертикали
     * @param string $positionY ключ позиции знака по-горизонтали
     */
    public function watermarkSave($fileUpload, $fileDelete, $positionX, $positionY)
    {
        $settings = array();

        if (!in_array($positionX, array('left', 'center', 'right'))) {
            $positionX = 'right';
        }
        $settings['pos_x'] = $positionX;

        if (!in_array($positionY, array('top', 'center', 'bottom'))) {
            $positionY = 'bottom';
        }
        $settings['pos_y'] = $positionY;

        if ($fileDelete) {
            $current = $this->watermarkSettings();
            if (!empty($current['file']['path']) && file_exists($current['file']['path'])) {
                unlink($current['file']['path']);
            }
            $settings['file'] = array();
        }

        if (!empty($_FILES[$fileUpload]) && $_FILES[$fileUpload]['error'] != UPLOAD_ERR_NO_FILE) {
            $uploader = new \bff\files\Attachment($this->path, 5242880); # до 5мб.
            $uploader->setFiledataAsString(false);
            $uploader->setAllowedExtensions(array('jpg', 'jpeg', 'png', 'gif'));
            $file = $uploader->uploadFILES($fileUpload);
            if (!empty($file)) {
                $file['path'] = $this->path . $file['filename'];
                $file['url'] = $this->url . $file['filename'];
                $settings['file'] = $file;
            }
        }

        $this->watermarkSettings($settings);
    }

    public function urlDefault($sSizePrefix)
    {
        return $this->url . 'def-' . $sSizePrefix . '.png';
    }

    /**
     * Получаем данные об изображениях указанных объявлений
     * @param array $aItemsID ID объявлений
     * @return array массив параметров изображений сформированных: array(itemID=>data, ...)
     */
    public function getItemsImagesData($aItemsID)
    {
        if (empty($aItemsID)) {
            return array();
        }
        if (!is_array($aItemsID)) {
            $aItemsID = array($aItemsID);
        }
        
        $aData = $this->db->select('SELECT * FROM ' . $this->tableImages . '
                    WHERE ' . $this->fRecordID . ' IN (' . join(',', $aItemsID) . ')
                    ORDER BY num'
        );
        if (!empty($aData)) {
            $aData = func::array_transparent($aData, $this->fRecordID, false);
        }

        return $aData;
    }

    /**
     * Проверяем наличие данных о загруженном изображении по ID изображения
     * @param integer $nImageID ID изображения
     * @return boolean true - есть данные
     */
    public function imageDataExists($nImageID)
    {
        if (empty($nImageID)) {
            return false;
        }

        $res = $this->db->one_data('SELECT id FROM ' . $this->tableImages . '
                    WHERE id = :imageID AND ' . $this->fRecordID . ' = :recordID
                    LIMIT 1', array(':imageID' => $nImageID, ':recordID' => $this->recordID)
        );
        return ! empty($res);
    }

    /**
     * Получаем дату самого последнего добавленного изображения
     * @param boolean $buildHash сформировать hash на основе даты
     * @return integer|string
     */
    public function getLastUploaded($buildHash = true)
    {
        $lastUploaded = $this->db->one_data('SELECT MAX(created) FROM ' . $this->tableImages . '
                    WHERE ' . $this->fRecordID . ' = :id
                    LIMIT 1', array(':id' => $this->recordID)
        );
        if (!empty($lastUploaded)) {
            $lastUploaded = strtotime($lastUploaded);
        } else {
            $lastUploaded = mktime(0, 0, 0, 1, 1, 2000);
        }

        return ($buildHash ? $this->getLastUploadedHash($lastUploaded) : $lastUploaded);
    }

    /**
     * Формируем hash на основе даты самого последнего добавленного изображения
     * @return integer
     */
    public function getLastUploadedHash($lastUploaded)
    {
        $base64 = base64_encode($lastUploaded);

        return md5(strval($lastUploaded - 1000) . SITEHOST . $base64) . '.' . $base64;
    }

    /**
     * Выполняем проверку, загружались ли новые изображения
     * @param string $lastUploaded hash даты последнего загруженного изображения
     * @return boolean
     */
    public function newImagesUploaded($lastUploaded)
    {
        # проверка hash'a
        if (empty($lastUploaded) || ($dot = strpos($lastUploaded, '.')) !== 32) {
            return true;
        }
        $date = intval(base64_decode(mb_substr($lastUploaded, $dot + 1)));
        if ($this->getLastUploadedHash($date) !== $lastUploaded) {
            return true;
        }
        # выполнялась ли загрузка новых изображений
        if ($this->getLastUploaded(false) > intval($date)) {
            return true;
        }

        return false;
    }
    
    /**
     * Возвращает ключ максимального размера изображений
     * @return string
     */
    public function getMaxSizeKey()
    {
        $sizes = $this->getSizes();
        return key(end($sizes));
    }

}