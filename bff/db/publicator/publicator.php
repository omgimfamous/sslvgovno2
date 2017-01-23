<?php namespace bff\db;

/**
 * Компонент Publicator
 * @version 1.8
 * @modified 29.jan.2014
 */

use bff\utils\TextParser;
use bff\utils\VideoParser;
use CImagesUploader, HTML;

class Publicator extends \Module
{
    # типы контентных блоков
    const blockTypeText = 1; # текст
    const blockTypePhoto = 2; # фотография
    const blockTypeVideo = 3; # видео
    const blockTypeSubtitle = 4; # подзаголовок
    const blockTypeGallery = 5; # фотогалерея
    const blockTypeQuote = 6; # цитата
    const blockTypeMap = 7; # карта TODO

    # константы размеров изображений
    const szThumbnail = 't'; # thumbnail - в форме редактирования
    const szView = 'v'; # view - при просмотре
    const szZoom = 'z'; # zoom - при просмотре (zoom)
    const szOriginal = 'o'; # original - оригинал
    /** @var CImagesUploader загрузчик изображений Photo */
    protected $photoUploader = false;
    /** @var CImagesUploader загрузчик изображений Gallery */
    protected $galleryUploader = false;

    /** @var string название модуля, инициировавшего работу с Publicator */
    public $owner_module = '';
    /** @var array|boolean Мультиязычность */
    public $langs = false;

    /** @var string метод парсинга текста Wysiwyg редактора */
    public $textParserMethod = 'parseWysiwygText';

    /**
     * Инициализируем компонент
     * @core-doc
     * @param string $sOwnerModuleName название модуля выполняющего инициализацию
     * @param array $aSettings настройки компонента
     * @param bool $bCheckRequest выполнять проверку запроса к публикатору
     */
    public function __construct($sOwnerModuleName = '', array $aSettings = array(), $bCheckRequest = true)
    {
        $this->initModuleAsComponent('publicator', PATH_CORE . 'db' . DS . 'publicator');
        $this->init();

        # настройки по-умолчанию
        $this->setSettings(array(
                'owner_module'         => $sOwnerModuleName,
                'controls'             => array(),
                # список доступных типов контента (по-умолчанию: все)
                'use_wysiwyg'          => true,
                # использовать WYSIWYG вместо textarea
                'use_reformator'       => false,
                # использовать Reformator совместно с WYSIWYG редактором
                'wysiwyg_scripts'      => false,
                # разрешать теги <script> в WYSIWYG
                'wysiwyg_iframes'      => false,
                # разрешать теги <iframe> в WYSIWYG
                # заголовок
                'title'                => false,
                # использовать основной заголовок
                # изображения - общие настройки (photo,gallery)
                'images_path'          => '',
                # абсолютный путь к папке с изображениями
                'images_path_tmp'      => '',
                # временный абсолютный путь к папке с изображениями (если пустой: images_path/tmp)
                'images_url'           => '',
                # URL к папке с изображениями
                'images_url_tmp'       => '',
                # временный URL к папке с изображениями (если пустой: images_url/tmp)
                'images_original'      => false,
                # сохранять оригинальное изображение (фото+галерея)
                'images_quality'       => 85,
                # качество изображений
                # фото
                'photo_sz_th'          => array('width' => 156),
                # th-изображение (при редактировании)
                'photo_sz_view'        => array('width' => 600),
                # view-изображение (при просмотре)
                'photo_sz_zoom'        => false,
                # большое изображение (при просмотре)
                'photo_align'          => false,
                # настройка: align (left-center-right)
                'photo_zoom'           => false,
                # настройка: zoom
                'photo_wysiwyg'        => true,
                # использовать ли WYSIWYG для описания фотографии
                # фотогалереи
                'gallery_sz_th'        => array('width' => 72, 'height' => 72),
                # th-изображение (при редактировании) - только квадрат!
                'gallery_sz_view'      => array('width' => 600, 'height' => 300),
                # большого изображение (при просмотре)
                'gallery_sz_extra'     => array(),
                # дополнительные необходимые размеры изображений, формат: array(ключ=>array(),...)
                'gallery_photos_limit' => 0,
                # максимально допустимое кол-во фотографий в фотогалерее (0 - без ограничения)
                # видео
                'video_width'          => 480,
                # ширина видео-блока
                'video_height'         => 385,
                # высота видео-блока
            )
        );

        # настройки от модуля
        foreach (array(
                     'photo_sz_th',
                     'photo_sz_view',
                     'photo_sz_zoom',
                     'gallery_sz_th',
                     'gallery_sz_view',
                     'gallery_sz_extra'
                 ) as $k) {
            if (isset($aSettings[$k]) && empty($aSettings[$k])) {
                unset($aSettings[$k]);
            }
        }
        if (!empty($aSettings['gallery_sz_extra'])) { # проверяем на корректность доп. размеры
            foreach ($aSettings['gallery_sz_extra'] as $k => $v) {
                if (empty($v) || empty($k) || in_array($k, array(
                            self::szThumbnail,
                            self::szView,
                            self::szZoom,
                            self::szOriginal
                        )
                    )
                ) {
                    unset($aSettings['gallery_sz_extra'][$k]);
                }
                if (!empty($v['o'])) { # запрещаем сохранение оригинала в доп. размерах
                    $aSettings['gallery_sz_extra'][$k]['o'] = false;
                }
            }
        }
        $this->setSettings($aSettings);

        # подключаем необходимые js-файлы
        if (\bff::adminPanel()) {
            \tpl::includeJS('publicator', true);
            if ($this->sett['use_wysiwyg']) {
                \tpl::includeJS('wysiwyg', true);
                if ($this->sett['use_reformator']) {
                    \tpl::includeJS('reformator/reformator', true);
                }
            }
        } else {
            # возможность добавления тегов <script>, <iframe> в WYSIWYG
            # доступна только из admin панели
            $this->sett['wysiwyg_scripts'] = false;
            $this->sett['wysiwyg_iframes'] = false;
        }

        # включаем zoom-настройку если не выключена явно
        if (!empty($aSettings['photo_sz_zoom']) && !isset($aSettings['photo_zoom'])) {
            $this->sett['photo_zoom'] = true;
        }

        # обрабатываем ajax-запросы публикатора
        if ($bCheckRequest) {
            $this->checkRequest();
        }
    }

    /**
     * Формирование страницы редактирования
     * @core-doc
     * @param array|string $mData данные публикатора
     * @param integer $nRecordID ID записи
     * @param string $sFieldName имя поля, например 'content'
     * @param string $sJSObjectName имя js-переменной (для хранения js-объекта возвращаемого bffPublicator.init)
     * @param array|boolean $mControls доступные действия; варианты: [self::blockType, ...] или FALSE (берем из настроек)
     * @param boolean $bDebug true - инициализация в debug-режиме
     * @return string HTML
     */
    public function form($mData, $nRecordID, $sFieldName, $sJSObjectName = '', $mControls = false, $bDebug = false)
    {
        $aData = array(
            'id'        => $nRecordID,
            'fieldname' => $sFieldName,
            'js_object' => $sJSObjectName,
            'content'   => $this->unserialize($mData),
            'debug'     => $bDebug,
        );

        # подготавливаем URL фотографий
        $this->photosPrepareURL($aData['content'], $nRecordID);

        if ($mControls === false) {
            $mControls = $this->sett['controls'];
        }
        if (empty($mControls)) {
            $mControls = array(
                self::blockTypeText,
                self::blockTypePhoto,
                self::blockTypeGallery,
                self::blockTypeVideo,
                self::blockTypeSubtitle
            );
        }
        $aData['controls'] = $mControls;

        return $this->viewPHP($aData, 'form');
    }

    /**
     * Формирование страницы просмотра
     * @core-doc
     * @param array|string $aData данные публикатора
     * @param integer $nRecordID ID записи
     * @param string|boolean $sTemplate шаблон
     * @param string|boolean $sTemplateDir путь к директории шаблона
     * @param array $aExtra доп. данные
     * @return string HTML
     */
    public function view($aData, $nRecordID, $sTemplate = false, $sTemplateDir = false, $aExtra = array())
    {
        $aData = $this->unserialize($aData);
        # подготавливаем URL фотографий
        $this->photosPrepareURL($aData, $nRecordID);

        if ($aData !== false && !empty($aData)) {
            $aData['id'] = $nRecordID;
            $aData['extra'] = $aExtra;
            if ($sTemplate !== false) {
                return $this->viewPHP($aData, $sTemplate, $sTemplateDir);
            }

            return $this->viewPHP($aData, 'view');
        } else {
            return '';
        }
    }

    /**
     * Обработчик ajax-запросов
     * @core-doc
     */
    public function ajax()
    {
        switch ($this->input->get('act', TYPE_STR)) {
            case 'img_upload': # загрузка изображений
            {
                $nRecordID = $this->input->post('id', TYPE_UINT);
                $nBlockType = $this->input->post('block_type', TYPE_UINT);
                $bTmp = empty($nRecordID);

                $aResponse = array();
                switch ($nBlockType) {
                    case self::blockTypeGallery:
                    {
                        $aResult = $this->initGalleryUploader($nRecordID)->uploadSWF();
                        if (!empty($aResult)) {
                            $aURL = $this->galleryUploader->getURL($aResult, $this->getGallerySizes(), $bTmp);
                            $aResponse = array_merge($aResult, $aURL);
                        }
                    }
                        break;
                    case self::blockTypePhoto:
                    default:
                        {
                        $aResult = $this->initPhotoUploader($nRecordID)->uploadSWF();
                        if (!empty($aResult)) {
                            $aURL = $this->photoUploader->getURL($aResult, $this->getPhotoSizes(), $bTmp);
                            $aResponse = array_merge($aResult, $aURL);
                            $aResponse['view'] = join(';', $this->photoUploader->getImageParams($aResult, array(self::szView), $bTmp));
                        }
                        }
                        break;
                }

                if (empty($aResult)) {
                    $aResponse['success'] = false;
                    $aResponse['error'] = $this->errors->get(true);
                } else {
                    $aResponse['success'] = true;
                }

                $this->ajaxResponse($aResponse, 1); //json echo (swfupload)
            }
            break;
            case 'img_delete': # удаление изображений
            {
                $nRecordID = $this->input->post('id', TYPE_UINT);
                $aPhotos = $this->input->post('photos', TYPE_ARRAY_ARRAY);
                if (!empty($aPhotos)) {
                    $this->photosDelete($aPhotos, $nRecordID);
                }
                $this->ajaxResponseForm();
            }
            break;
            case 'video_validate': # проверка корректности ссылки на видео
            {
                $aResp = array('correct' => true);
                do {
                    $sURL = $this->input->post('url', TYPE_STR);
                    if (empty($sURL)) {
                        $aResp['correct'] = false;
                        break;
                    }

                    $oVideoParser = new VideoParser();
                    $aEmbed = $oVideoParser->embed($sURL);
                    if (!empty($aEmbed) && $aEmbed['provider_id'] !== VideoParser::PROVIDER_UNKNOWN) {
                        $aResp['source'] = $aEmbed['provider_name'];
                        $aResp['url'] = $aEmbed['flash_url'];
                        break;
                    }

                    $aResp['correct'] = false;
                } while (false);

                $this->ajaxResponse($aResp);
            }
            break;
        }

        $this->ajaxResponse(\Errors::IMPOSSIBLE);
    }

    /**
     * Подготовка данных перед сохранением (при добавлении / редактировании)
     * @core-doc
     * @param array $aData данные публикатора
     * @param integer $nRecordID ID записи
     * @return array
     */
    public function dataPrepare($aData, $nRecordID)
    {
        $useLangs = $this->useLangs();
        $searchContent = array();
        $aPhotos = array();
        $aContentTypes = array(
            self::blockTypeText     => 0,
            self::blockTypeQuote    => 0,
            self::blockTypePhoto    => 0,
            self::blockTypeGallery  => 0,
            self::blockTypeVideo    => 0,
            self::blockTypeSubtitle => 0
        );
        if (!empty($aData) && is_array($aData)) {
            $aData['t'] = $this->prepareText((isset($aData['t']) ? $aData['t'] : false), false);
            if ($aData['t'] !== false) {
                if ($useLangs) {
                    $aData['t'] = array_map('strip_tags', $aData['t']);
                    $aData['t'] = array_map('htmlspecialchars', $aData['t']);
                    foreach ($this->langs as $lng) {
                        $searchContent[$lng][] = $aData['t'][$lng];
                    }
                } else {
                    $aData['t'] = htmlspecialchars(strip_tags($aData['t']));
                    $searchContent[] = $aData['t'];
                }
            }

            $aData['menu'] = array();

            $aPhotosDelete = false;
            $aBlocks = & $aData['b'];
            if (empty($aBlocks)) $aBlocks = array();
            foreach ($aBlocks as $k => &$b) {
                if (!isset($b['type'])) {
                    unset($aBlocks[$k]);
                    continue;
                }

                $isDelete = !empty($b['del']);
                switch ($b['type']) {
                    # Текст
                    case self::blockTypeText:
                    {
                        /**
                         * text  - описание (string или [lng=>string,...])
                         */
                        if ($isDelete) break;
                        $b['text'] = $this->prepareText((isset($b['text']) ? $b['text'] : false), $this->sett['use_wysiwyg']);
                        if ($b['text'] !== false) {
                            if ($useLangs) {
                                foreach ($this->langs as $lng) {
                                    $searchContent[$lng][] = $b['text'][$lng];
                                }
                            } else {
                                $searchContent[] = $b['text'];
                            }
                        } else {
                            unset($aBlocks[$k]);
                            continue;
                        }
                    }
                    break;

                    # Цитата
                    case self::blockTypeQuote:
                    {
                        /**
                         * text  - описание (string или [lng=>string,...]) чистим от тегов, кроме br,a
                         */
                        if ($isDelete) break;
                        $b['text'] = $this->prepareText((isset($b['text']) ? $b['text'] : false), false);
                        if ($b['text'] !== false) {
                            if ($useLangs) {
                                foreach ($this->langs as $lng) {
                                    $searchContent[$lng][] = strip_tags($b['text'][$lng], '<br><a>');
                                }
                            } else {
                                $searchContent[] = strip_tags($b['text'], '<br><a>');
                            }
                        } else {
                            unset($aBlocks[$k]);
                            continue;
                        }
                    }
                    break;

                    # Фото
                    case self::blockTypePhoto:
                    {
                        /**
                         * photo - filename
                         * text  - описание (string или [lng=>string,...])
                         * align - настройка: расположение string (left,center,right)
                         * zoom  - настройка: zoom
                         * view  - параметры view: 'width;height'
                         */
                        if (empty($b['photo'])) {
                            unset($aBlocks[$k]);
                            continue;
                        } else {
                            $aPhotos[] = $b['photo'];
                            if ($isDelete) {
                                $aPhotosDelete[] = array($b['photo'], $b['type']);
                                break;
                            }
                            $b['alt'] = ''; // alt - атрибут
                            $b['text'] = $this->prepareText((isset($b['text']) ? $b['text'] : false), ($this->sett['use_wysiwyg'] && $this->sett['photo_wysiwyg']));
                            if ($b['text'] !== false) {
                                if ($useLangs) {
                                    foreach ($this->langs as $lng) {
                                        $searchContent[$lng][] = $b['text'][$lng];
                                        $b['alt'][$lng] = HTML::escape(strip_tags($b['text'][$lng]));
                                    }
                                } else {
                                    $searchContent[] = $b['text'];
                                    $b['alt'] = HTML::escape(strip_tags($b['text']));
                                }
                            }
                            $b['align'] = (!empty($b['align']) && in_array($b['align'], array(
                                    'left',
                                    'center',
                                    'right'
                                )
                            ) ? $b['align'] : 'center');
                            $b['zoom'] = !empty($b['zoom']);
                            $b['view'] = strval($b['view']);
                        }

                    }
                    break;

                    # Галерея
                    case self::blockTypeGallery:
                    {
                        /**
                         * p - фотографии [
                         *      [
                         *          photo - filename
                         *          del   - пометка "требуется удаление"
                         *          text  - описание (string или [lng=>string,...])
                         *      ], ...
                         * ]
                         */
                        if (empty($b['p'])) {
                            unset($aBlocks[$k]);
                            continue;
                        }
                        $aGalleryPhotos = array();
                        foreach ($b['p'] as $gk => $gp) {
                            if (empty($gp['photo'])) {
                                unset($b['p'][$gk]);
                                continue;
                            } else {
                                $aPhotos[] = $gp['photo'];
                                if ($isDelete || !empty($gp['del'])) {
                                    $aPhotosDelete[] = array($gp['photo'], $b['type']);
                                    unset($b['p'][$gk]);
                                    continue;
                                }
                                $b['p'][$gk]['alt'] = ''; # alt - атрибут
                                $b['p'][$gk]['text'] = $this->prepareText((isset($gp['text']) ? $gp['text'] : false), false);
                                # добавляем описания к фотографиям галереи в поле для поиска
                                if ($gp['text'] !== false) {
                                    if ($useLangs) {
                                        foreach ($this->langs as $lng) {
                                            $searchContent[$lng][] = $gp['text'][$lng];
                                            $b['p'][$gk]['alt'][$lng] = HTML::escape(strip_tags($gp['text'][$lng]));
                                        }
                                    } else {
                                        $searchContent[] = $gp['text'];
                                        $b['p'][$gk]['alt'] = HTML::escape(strip_tags($gp['text']));
                                    }
                                }
                                $aGalleryPhotos[] = $b['p'][$gk];
                            }
                        }
                        $b['p'] = $aGalleryPhotos; # корректируем индексы
                        if (empty($b['p'])) {
                            unset($aBlocks[$k]);
                            continue;
                        }
                    }
                    break;

                    # Видео
                    case self::blockTypeVideo:
                    {
                        /**
                         * video  - video-ссылка (string)
                         * source - источник: 'youtube',... (string) @see ajax::video_validate
                         */
                        if ($isDelete) break;

                        if (empty($b['video'])) {
                            unset($aBlocks[$k]);
                            continue;
                        } else {
//                            $b['text'] = $this->prepareText( (isset($b['text']) ? $b['text'] : false), false );
//                            if($b['text'] !== false) {
//                                if($useLangs) {
//                                    foreach($this->langs as $lng) {
//                                        $searchContent[$lng][] = $b['text'][$lng];
//                                    }
//                                } else {
//                                    $searchContent[] = $b['text'];
//                                }
//                            }
                        }
                    }
                    break;

                    # Подзаголовок
                    case self::blockTypeSubtitle:
                    {
                        /**
                         * text  - описание (string или [lng=>string,...])
                         * a     - сформированный на основе текста якорь (для формирования меню)
                         * size  - размер подзаголовка
                         */
                        if ($isDelete) break;

                        $text = $this->prepareText((isset($b['text']) ? $b['text'] : false), false);
                        if ($text !== false) {
                            $b['text'] = $text;
                            if ($useLangs) {
                                $b['a'] = array();
                                foreach ($this->langs as $lng) {
                                    $b['a'][$lng] = mb_strtolower(\func::translit($text[$lng]));
                                    $searchContent[$lng][] = $text[$lng];
                                }
                            } else {
                                $b['a'] = mb_strtolower(\func::translit($text));
                                $searchContent[] = $text;
                            }
                            $aData['menu'][] = array('t' => $b['text'], 'a' => $b['a']);
                        } else {
                            unset($aBlocks[$k]);
                            continue;
                        }

                        $b['size'] = intval($b['size']);
                        if (!$b['size']) $b['size'] = 2;
                        elseif ($b['size'] > 6) $b['size'] = 6;
                    }
                    break;

                    # ?
                    default:
                        $isDelete = true;
                }

                if ($isDelete) {
                    unset($aBlocks[$k]);
                } else {
                    $aContentTypes[$b['type']]++;
                }

                if (!empty($aPhotosDelete)) {
                    $this->photosDelete($aPhotosDelete, $nRecordID);
                }
            }
        } else {
            $aData = array('t' => '', 'b' => array());
        }

        $aResult = array(
            'content'       => serialize($aData),
            'photos'        => $aPhotos,
            'content_types' => $aContentTypes,
        );

        if ($useLangs) {
            $aResult['content_search'] = array();
            foreach ($this->langs as $lng) {
                $aResult['content_search'][$lng] = (!empty($searchContent[$lng]) ? strip_tags(join(' ', $searchContent[$lng])) : '');
            }
        } else {
            $aResult['content_search'] = strip_tags(join(' ', $searchContent));
        }

        return $aResult;
    }

    /**
     * Обновление данных перед сохранением
     * Выполняем при добавлении для переноса загруженных изображений из временной директории в постоянную
     * @core-doc
     * @param array $aData данные публикатора
     * @param integer $nRecordID ID записи
     */
    public function dataUpdate($aData, $nRecordID)
    {
        if (empty($aData) || !$nRecordID) return;

        $aPhotos = array();

        if (is_array($aData) && isset($aData['photos'])) {
            $aPhotos = $aData['photos'];
        } else {
            if (!empty($aData)) {
                $aData = $this->unserialize($aData);
                if ($aData !== false && is_array($aData)) {
                    if (!empty($aData['b'])) {
                        $aPhotos = $this->getPhotosByBlocks($aData['b']);
                    }
                }
            }
        }

        if (!empty($aPhotos)) {
            $this->photosUntemp($aPhotos, $nRecordID);
        }
    }

    /**
     * Удаление данных
     * @core-doc
     * @param array|string $aData данные публикатора
     * @param integer $nRecordID ID записи
     */
    public function dataDelete($aData, $nRecordID)
    {
        if (empty($aData)) return;

        $aData = $this->unserialize($aData);
        if (!(is_array($aData) && !empty($aData['b']))) return;

        $aPhotosDelete = $this->getPhotosByBlocks($aData['b']);
        if (!empty($aPhotosDelete)) {
            $this->photosDelete($aPhotosDelete, $nRecordID);
        }
    }

    /**
     * Проверка необходимости обработки внутренного ajax-запроса публикатора
     * @core-doc
     */
    public function checkRequest()
    {
        if ($this->input->get('publicator', TYPE_BOOL)) {
            $this->ajax();
        }
    }

    # ------------------------------------------------------------------------------------------------------------
    # protected методы

    /**
     * Получение всех файлов фотографий из блоков
     * @param array $aBlocks блоки данных публикатора
     * @return array
     */
    protected function getPhotosByBlocks($aBlocks)
    {
        $aPhotos = array();
        if (empty($aBlocks)) return $aPhotos;

        foreach ($aBlocks as $b) {
            if (!isset($b['type'])) continue;
            # photo
            if ($b['type'] == self::blockTypePhoto && !empty($b['photo'])) {
                $aPhotos[] = array($b['photo'], $b['type']);
            }
            # gallery
            if ($b['type'] == self::blockTypeGallery && !empty($b['p'])) {
                foreach ($b['p'] as $p) {
                    $aPhotos[] = array($p['photo'], $b['type']);
                }
            }
        }

        return $aPhotos;
    }

    /**
     * Подготовка URL фотографий
     * @param array $aContent @ref данные публикатора
     * @param integer $nRecordID ID записи
     */
    protected function photosPrepareURL(&$aContent, $nRecordID)
    {
        if (empty($aContent['b'])) return;

        $bTmp = empty($nRecordID);
        $this->initPhotoUploader($nRecordID);
        $this->initGalleryUploader($nRecordID);
        $aPhotoSizes = $this->getPhotoSizes();
        $aGallerySizes = $this->getGallerySizes();

        foreach ($aContent['b'] as $k => $v) {
            switch ($v['type']) {
                case self::blockTypePhoto:
                {
                    $aContent['b'][$k]['url'] = $this->photoUploader->getURL(array('filename' => $v['photo']), $aPhotoSizes, $bTmp);
                }
                break;
                case self::blockTypeGallery:
                {
                    if (!empty($v['p'])) {
                        foreach ($v['p'] as $gk => $gv) {
                            $aContent['b'][$k]['p'][$gk]['url'] = $this->galleryUploader->getURL(array('filename' => $gv['photo']), $aGallerySizes, $bTmp);
                        }
                    }
                }
                break;
            }
        }
    }

    /**
     * Удаление файлов изображения
     * @param array $aPhotos массив удаляемых файлов изображений [[filename,blockType],...]
     * @param integer $nRecordID ID записи
     * @return boolean
     */
    protected function photosDelete($aPhotos, $nRecordID = 0)
    {
        if (empty($aPhotos)) return false;

        $bTmp = empty($nRecordID);
        $this->initPhotoUploader($nRecordID);
        $this->initGalleryUploader($nRecordID);
        foreach ($aPhotos as $v) {
            if (empty($v[0]) || empty($v[1])) continue;
            $aImage = array('filename' => $v[0]);
            switch (intval($v[1])) {
                case self::blockTypePhoto:
                    $this->photoUploader->deleteFile($aImage, $bTmp);
                    break;
                case self::blockTypeGallery:
                    $this->galleryUploader->deleteFile($aImage, $bTmp);
                    break;
            }
        }
    }

    /**
     * Пересохранение файлов изображения из временной(tml) директории в постоянную
     * @param array $aPhotos названия файлов [[filename,blockType],...]
     * @param integer $nRecordID ID записи
     * @return integer кол-во перемещенных изображений
     */
    protected function photosUntemp($aPhotos, $nRecordID)
    {
        if (empty($aPhotos) || !is_array($aPhotos) || empty($nRecordID)) {
            return 0;
        }

        $aPhoto = array();
        $aGallery = array();
        foreach ($aPhotos as $v) {
            if (empty($v[0]) || empty($v[1])) continue;
            switch (intval($v[1])) {
                case self::blockTypePhoto:
                    $aPhoto[] = strval($v[0]);
                    break;
                case self::blockTypeGallery:
                    $aGallery[] = strval($v[0]);
                    break;
            }
        }

        if (!empty($aPhoto)) {
            $this->initPhotoUploader($nRecordID)->saveTmp($aPhoto);
        }
        if (!empty($aGallery)) {
            $this->initGalleryUploader($nRecordID)->saveTmp($aGallery);
        }

        return (sizeof($aPhoto) + sizeof($aGallery));
    }

    /**
     * Получаем префиксы размеров для Photo
     * @param array $aExclude префиксы размеров, которые следует исключить из результата
     * @return array
     */
    protected function getPhotoSizes(array $aExclude = array())
    {
        $aSizes = array(
            self::szThumbnail,
            self::szView,
        );
        if ($this->sett['images_original']) {
            $aSizes[] = self::szOriginal;
        }
        if (!empty($this->sett['photo_sz_zoom'])) {
            $aSizes[] = self::szZoom;
        }
        if (!empty($aExclude)) {
            foreach ($aExclude as $v) {
                if (($k = array_search($v, $aSizes)) !== false) {
                    unset($aSizes[$k]);
                }
            }
        }

        return $aSizes;
    }

    /**
     * Инициализируем загрузчик Photo
     * @param integer $nRecordID ID записи
     * @return CImagesUploader
     */
    protected function initPhotoUploader($nRecordID)
    {
        if ($this->photoUploader === false) {
            # инициализируем загрузчик изображений для Photo
            $aSizes = array(
                self::szThumbnail => $this->sett['photo_sz_th'],
                self::szView      => $this->sett['photo_sz_view'],
            );
            if (!empty($this->sett['photo_sz_zoom'])) {
                $aSizes[self::szZoom] = $this->sett['photo_sz_zoom'];
            }
            if ($this->sett['images_original']) {
                $aSizes[self::szOriginal] = array('o' => true);
            }
            $this->photoUploader = new CImagesUploader();
            $this->photoUploader->setSizes($aSizes);
            $this->photoUploader->setPath($this->sett['images_path'],
                (!empty($this->sett['images_path_tmp']) ? $this->sett['images_path_tmp'] :
                    $this->sett['images_path'] . 'tmp' . DS)
            );
            $this->photoUploader->setURL($this->sett['images_url'],
                (!empty($this->sett['images_url_tmp']) ? $this->sett['images_url_tmp'] :
                    $this->sett['images_url'] . 'tmp/')
            );
        }

        $this->photoUploader->setRecordID($nRecordID);

        return $this->photoUploader;
    }

    /**
     * Получаем префиксы размеров для Gallery
     * @param array $aExclude префиксы размеров, которые следует исключить из результата
     * @return array
     */
    protected function getGallerySizes(array $aExclude = array())
    {
        $aSizes = array(
            self::szThumbnail,
            self::szView,
        );
        if ($this->sett['images_original']) {
            $aSizes[] = self::szOriginal;
        }
        if (!empty($this->sett['gallery_sz_extra'])) {
            foreach ($this->sett['gallery_sz_extra'] as $k => $v) {
                $aSizes[] = $k;
            }
        }
        if (!empty($aExclude)) {
            foreach ($aExclude as $v) {
                if (($k = array_search($v, $aSizes)) !== false) {
                    unset($aSizes[$k]);
                }
            }
        }

        return $aSizes;
    }

    /**
     * Инициализируем загрузчик Gallery
     * @param integer $nRecordID ID записи
     * @return CImagesUploader
     */
    protected function initGalleryUploader($nRecordID)
    {
        if ($this->galleryUploader === false) {
            # инициализируем загрузчик изображений для Gallery
            $aSizes = array(
                self::szThumbnail => $this->sett['gallery_sz_th'],
                self::szView      => $this->sett['gallery_sz_view'],
            );
            if (!empty($this->sett['gallery_sz_extra'])) {
                foreach ($this->sett['gallery_sz_extra'] as $k => $v) {
                    $aSizes[$k] = $v;
                }
            }
            if ($this->sett['images_original']) {
                $aSizes[self::szOriginal] = array('o' => true);
            }
            $this->galleryUploader = new CImagesUploader();
            $this->galleryUploader->setSizes($aSizes);
            $this->galleryUploader->setPath($this->sett['images_path'],
                (!empty($this->sett['images_path_tmp']) ? $this->sett['images_path_tmp'] :
                    $this->sett['images_path'] . 'tmp' . DS)
            );
            $this->galleryUploader->setURL($this->sett['images_url'],
                (!empty($this->sett['images_url_tmp']) ? $this->sett['images_url_tmp'] :
                    $this->sett['images_url'] . 'tmp/')
            );
        }

        $this->galleryUploader->setRecordID($nRecordID);

        return $this->galleryUploader;
    }

    /**
     * Обработка текста
     * @param string $sText текст
     * @param boolean $bWysiwygText true - текст сформирован при помощи WYSIWYG
     * @return array|bool|string
     */
    protected function prepareText($sText, $bWysiwygText = true)
    {
        static $parser, $parserParams = array();
        if (!isset($parser)) {
            $parser = new TextParser();
            if (!method_exists($parser, $this->textParserMethod)) {
                $parser = false;
            } else {
                $parserParams = array(
                    'scripts' => $this->sett['wysiwyg_scripts'],
                    'iframes' => $this->sett['wysiwyg_iframes']
                );
            }
        }

        if ($this->useLangs()) {
            if ($sText === false || empty($sText) || !is_array($sText)) {
                return false;
            }
            $sText = array_map('trim', $sText);
            if ($bWysiwygText && $parser) {
                $parserMethod = $this->textParserMethod;
                foreach ($sText as $lng => $txt) {
                    $sText[$lng] = $parser->$parserMethod($txt, $parserParams);
                }
            } else {
                $sText = array_map('strip_tags', $sText);
            }

            return $sText;
        } else {
            if ($sText === false) {
                return false;
            }
            $sText = trim($sText);
            if ($bWysiwygText && $parser) {
                $parserMethod = $this->textParserMethod;
                $sText = $parser->$parserMethod($sText, $parserParams);
            } else {
                $sText = strip_tags($sText);
            }

            return ($sText == '' ? false : $sText);
        }
    }

    /**
     * Выполняем десериализацию данных с проверкой корректности сериализации
     * @core-doc
     * @param string|array $mData данные
     * @return array|mixed
     */
    public function unserialize($mData)
    {
        return (is_array($mData) ? $mData :
            ((!empty($mData) && is_string($mData) && mb_strpos($mData, 'a:') === 0) ?
                unserialize($mData) :
                array()));
    }

    /**
     * Используется ли мультиязычность
     * @return boolean
     */
    protected function useLangs()
    {
        return !empty($this->langs);
    }

}