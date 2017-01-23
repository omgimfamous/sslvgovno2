<?php

/**
 * Компонент обработки импорта / экспорта объявлений
 * @version 0.151
 * @modified 20.dec.2014
 */

use \bff\utils\Files;

class BBSItemsImport extends Component
{
    /** @var BBS */
    protected $bbs;
    /** @var string Путь к директории файлов импорта */
    protected $importPath = '';
    /** @var int Количество обрабатываемых объявлений за раз */
    protected $importProcessingStep = 100;
    /** @var int Кол-во мегабайт требуемых на экспорт 1000 записей */
    protected $export_mbPer1000 = 15;

    const STATUS_WAITING    = 1;  # ожидает обработки
    const STATUS_PROCESSING = 2;  # обрабатывается
    const STATUS_FINISHED   = 4;  # обработка завершена
    const STATUS_CANCELED   = 8;  # отменен
    const STATUS_ERROR      = 32; # ошибка
    
    const ATTRIBUTE_TYPE = 'items-import-export'; # значение аттрибута type для тега bbs

    public function init()
    {
        parent::init();
        $this->bbs = BBS::i();
        $this->importPath = self::getImportPath();
    }

    /**
     * Инициализация импорта из файла
     * @param string $fileKey ключ файла импорта в _FILES
     * @param array $settings параметры импорта
     *  catId  - ID категории
     *  userId - ID пользователя (владельца импортируемых объявлений)
     *  shop   - ID магазина
     *  state  - статус импортируемых объявлений
     * @return integer ID успешно созданного импорта или false
     */
    public function importStart($fileKey, array $settings)
    {
        $isAdmin = bff::adminPanel();

        # 1. загрузка файла
        $uploader = new \bff\files\Attachment($this->importPath, 0);
        $uploader->setFiledataAsString(false);
        $uploader->setAllowedExtensions(array('xml'));
        if ($isAdmin) {
            # для администратора, ограничиваем размер файла 25mb
            $uploader->setMaxSize(1048576 * 25);
        } else {
            # для фронтенда, ограничиваем размер файла 10mb
            $uploader->setMaxSize(1048576 * 10);
        }
        $file = $uploader->uploadFILES($fileKey);

        if (!$file || empty($file)) {
            $this->errors->set(_t('bbs.import', 'Не удалось загрузить файл импорта'));
            return false;
        }

        $filePath = $this->importPath.$file['filename'];
        $file['hash'] = md5_file($filePath);
        unset($file['error']);

        # 2. проверка структуры файла
        $doc = new DOMDocument();
        if (!$doc->load($filePath)) {
            $this->errors->set(_t('bbs.import', 'Файл импорта не соответствует требуемой структуре (#1)'));
            return false;
        }

        $bbs = $doc->getElementsByTagName('bbs');
        if ($bbs->length == 0 || $bbs->item(0)->getAttribute('type') != self::ATTRIBUTE_TYPE) {
            $this->errors->set(_t('bbs.import', 'Файл импорта не соответствует требуемой структуре (#2)'));
            return false;
        }

        $items = $doc->getElementsByTagName('item');
        if ($items->length == 0) {
            $this->errors->set(_t('bbs.import', 'Не удалось найти объявления для импорта'));
            return false;
        }

        # проверяем категорию
        $aParents = $this->bbs->model->catParentsData($settings['catId'], array('id'));
        if (empty($aParents)) {
            $this->errors->set(_t('bbs.import', 'Категория указана некорректно'));
            return false;
        }
        $aParent = reset($aParents);

        # проверяем пользователя - владельца объявления
        if ($isAdmin)
        {
            if (!$settings['userId']) {
                $this->errors->set(_t('bbs.import', 'Укажите владельца объявлений'));
                return false;
            }
            $aUserData = Users::model()->userData($settings['userId'], array('shop_id'));
            if (empty($aUserData)) {
                $this->errors->set(_t('bbs.import', 'Пользователь был указан некорректно'));
                return false;
            }
            # корректируем магазин
            if ($settings['shop'] > 0) {
                $_POST['shop_import'] = !empty($settings['shop']);
                $settings['shop'] = $this->bbs->publisherCheck($aUserData['shop_id'], 'shop_import');
            }
        } else {
            # корректируем магазин
            $_POST['shop_import'] = !empty($settings['shop']);
            $settings['shop'] = $this->bbs->publisherCheck($settings['shop'], 'shop_import');
            if ( ! $settings['shop']) {
                $this->errors->reloadPage();
                return false;
            }
        }

        $aData = array(
            'settings'       => serialize($settings),
            'filename'       => serialize($file),
            'user_id'        => User::id(),
            'user_ip'        => Request::remoteAddress(),
            'cat_id'         => $aParent['id'],
            'items_total'    => $items->length,
            'status'         => self::STATUS_WAITING,
            'status_changed' => $this->db->now(),
            'is_admin'       => $isAdmin,
            'created'        => $this->db->now(),
        );

        # 3. сохранение настроек импорта в базу
        return $this->bbs->model->importSave(0,$aData);
    }

    /**
     * Поиск и обработка импорта по крону
     * Рекомендуемый период: раз в 7 минут
     */
    public function importCron()
    {
        # обрабатываемый
        $task = $this->bbs->model->importListing(
                array('I.id'),
                array('status'=>self::STATUS_PROCESSING, 'working'=>1),
                1
            );

        # нет обрабатываемых - первый из ожидающих
        if (empty($task)) {
            $task = $this->bbs->model->importListing(
                    array('I.id'),
                    array('status'=>self::STATUS_WAITING, 'working'=>0),
                    1,
                    'I.created ASC'
                );
        }

        if (!empty($task)) {
            $task = reset($task);
            $this->importContinue($task['id']);
            return;
        }
    }

    /**
     * Обработка импорта по крону
     * @param integer $importID ID импорта
     * @return boolean
     */
    protected function importContinue($importID)
    {
        if (empty($importID)) return false;
        
        $aData = $this->bbs->model->importData($importID);
        if (empty($aData)) return false;

        # меняем статус на "обрабатывается"
        if ($aData['status'] == self::STATUS_WAITING) {
            $this->bbs->model->importSave($importID, array(
                'status'         => self::STATUS_PROCESSING,
                'status_changed' => $this->db->now(),
                'working'        => 1,
            ));
        }
        $processed = $aData['items_processed'];
        $ignored   = $aData['items_ignored'];
        $isAdmin   = ! empty($aData['is_admin']);

        # проверяем настройки
        $file = func::unserialize($aData['filename']);
        if (!$file) {
            $this->importError($importID, 'Ошибка чтения параметров файла');
            return false;
        }
        $settings = func::unserialize($aData['settings']);
        if (!$settings) {
            $this->importError($importID, 'Ошибка чтения параметров импорта');
            return false;
        }
        
        # загружаем файл, проверяем его хеш
        $filePath = $this->importPath . $file['filename'];
        if (md5_file($filePath) != $file['hash']) {
            $this->importError($importID, 'MD5 файла импорта не совпадает');
            return false;
        }

        # статистика
        $stat = ( isset($settings['stat']) ? $settings['stat'] : array() );
        foreach (array('cat','title','success','updated') as $k) { if ( ! isset($stat[$k])) $stat[$k] = 0; }
        if (!isset($settings['success'])) { $settings['success'] = 0; }
        
        $dp = $this->bbs->dp();

        $import_catID  = $settings['catId'];
        $import_userID = $settings['userId'];
        $import_shopID = $settings['shop'];

        # тип владельца: частное лицо / бизнес
        $ownerType = ( $import_shopID ? BBS::OWNER_BUSINESS : BBS::OWNER_PRIVATE );
        # дата завершения публикации
        $publicatedTo = $this->bbs->getItemPublicationPeriod();
        $catFields = array(
            'id', 'pid', 'subs', 'numlevel', 'numleft', 'numright', 'price', 'price_sett', 'addr', 'keyword', 'photos',
        );
        $catsData = array();
        $catParents = array();
        $regionsData = array();
        $catsData[$import_catID] = $this->bbs->model->catData($import_catID, $catFields);
        if (empty($catsData[$import_catID])) {
            $this->importError($importID, 'Неудалось получить данные категории по id='.$import_catID);
            return false;
        }
        $priceEx = array(
            'free'=>BBS::PRICE_EX_FREE, 'exchange'=>BBS::PRICE_EX_EXCHANGE, 'mod'=>BBS::PRICE_EX_MOD,
        );

        $aUserData = Users::model()->userData($import_userID, array('name','phones','skype','icq'));
        if (empty($aUserData)) {
            $this->importError($importID, 'Неудалось получить данные пользователя по id='.$import_userID);
            return false;
        }

        $doc = new DOMDocument();
        if (!$doc->load($filePath)) {
            $this->importError($importID, 'Неудалось открыть файл "'.$filePath.'"');
            return false;
        }

        $aMParams = array();
        $params = $doc->getElementsByTagName('params');
        if ($params->length > 0) {
            $params = $params->item(0)->getElementsByTagName('param');
            foreach ($params as $param) {
                switch ($param->getAttribute('type')){
                    case $dp::typeRadioGroup:
                    case $dp::typeCheckboxGroup:
                    case $dp::typeSelect:
                        $values = $param->getElementsByTagName('value');
                        foreach ($values as $v) {
                            $aMParams[$param->getAttribute('field')][$v->getAttribute('id')] = $v->nodeValue;
                        }
                        break;
                }
            }
        }
        
        $items = $doc->getElementsByTagName('item');
        for ($ic = $processed; $ic < $processed+$this->importProcessingStep; $ic++)
        {
            $data = $items->item($ic);
            if (is_null($data)) break;

            $item = array();
            $catID = $import_catID;

            # ID объявления
            $itemID = (int)$data->getAttribute('id');
            $itemExists = $this->bbs->model->itemData($itemID, array('user_id','status','moderated','cat_id','city_id','video',
                                                                     'name','skype','icq','phones','title_edit','imgcnt'));
            if (empty($itemExists)) {
                $isUpdate = false; # создаем новое объявление
                $itemID = 0;
                $item['user_id'] = $import_userID;
                $item['shop_id'] = $import_shopID;
            } else {
                $isUpdate = true; # обновляем существующее объявление
                if ( ! $isAdmin) {
                    if ($itemExists['user_id'] != $import_userID) {
                        # объявление закреплено за другим пользователем, игнорируем <item>
                        $ignored++;
                        continue;
                    }
                }
            }

            $images = array();

            $nodeKeys = array();
            foreach ($data->childNodes as $node)
            {
                $nodeKeys[] = strval($node->nodeName);
                switch ($node->nodeName) {
                    case 'title': # заголовок
                        $title = strval($node->nodeValue);
                        $this->input->clean($title, TYPE_NOTAGS, true, array('len'=>100));
                        if (empty($title)) {
                            if ($isUpdate) {
                                $title = $itemExists['title_edit'];
                            } else {
                                # добавление: заголовок не может быть пустым, игнорируем <item>
                                $stat['title']++;
                                $ignored++;
                                continue 3;
                            }
                        }
                        $item['title_edit'] = $title;
                        $item['title'] = HTML::escape($title);
                        break;
                    case 'description': # описание
                        $item['descr'] = $this->input->cleanTextPlain(strval($node->nodeValue), 3000, false);
                        break;
                    case 'user': # пользователь
                        # игнорируем данные <user>
                        break;
                    case 'category': # категория
                        if ($isUpdate) {
                            $catID = $itemExists['cat_id']; # обновление: не меняем категорию
                        } else {
                            $v = (int)$node->nodeValue; # ID категории или 0
                            if ($v > 0) $catID = $v;
                        }
                        if (!isset($catsData[$catID])) {
                            $catsData[$catID] = $this->bbs->model->catData($catID, $catFields);
                        }
                        # 1. неудалось получить данные категории по ID
                        # 2. есть подкатегории => необходимо указывать самую "глубокую" подкатегорию
                        if (empty($catsData[$catID]) || $catsData[$catID]['subs'] > 0) {
                            $stat['cat']++; # игнорируем <item>
                            $ignored++;
                            continue 3;
                        }
                        break;
                    case 'geo': # geo данные
                        # город
                        $city = $node->getElementsByTagName('city');
                        $item['city_id'] = ( $city->length ? (int)$city->item(0)->getAttribute('id') : 0 );
                        if ($item['city_id'] <=0 && $isUpdate) $item['city_id'] = $itemExists['city_id'];
                        # станция метро
                        $station = $node->getElementsByTagName('station');
                        $item['metro_id'] = ( $station->length ? (int)$station->item(0)->getAttribute('id') : 0 );
                        # точный адрес
                        $addr = $node->getElementsByTagName('addr');
                        if ($addr->length) {
                            $item['addr_addr'] = strval($addr->item(0)->nodeValue);
                            $this->input->clean($item['addr_addr'], TYPE_NOTAGS, true, array('len'=>400));
                        }
                        # точный адрес: координаты на карте
                        $addr_lat = $node->getElementsByTagName('lat');
                        if ($addr_lat->length) $item['addr_lat'] = floatval($addr_lat->item(0)->nodeValue);
                        $addr_lon = $node->getElementsByTagName('lon');
                        if ($addr_lon->length) $item['addr_lon'] = floatval($addr_lon->item(0)->nodeValue);
                        break;
                    case 'price': # цена
                        $item['price'] = floatval($node->nodeValue);
                        $item['price_curr'] = (int)$node->getAttribute('currency');
                        $item['price_ex'] = BBS::PRICE_EX_PRICE;
                        foreach ($priceEx as $k=>$v) {
                            if ((int)$node->getAttribute($k)) {
                                $item['price_ex'] += $v; break;
                            }
                        }

                        $item['price_search'] = Site::currencyPriceConvertToDefault($item['price'], $item['price_curr']);
                        break;
                    case 'images': # изображения
                        $imagesList = $node->getElementsByTagName('image');
                        foreach ($imagesList as $image) {
                            $images[] = array(
                                'id'  => ( $image->hasAttribute('id') ? intval($image->getAttribute('id')) : 0 ),
                                'url' => strval($image->nodeValue),
                            );
                        } unset($imagesList);
                        break;
                    case 'contacts': # контакты
                        # телефоны
                        $phones = array();
                        foreach ($node->getElementsByTagName('phones') as $phone) {
                            $phones[] = strval($phone->nodeValue);
                        }
                        if (!empty($phones)) {
                            $phones = Users::validatePhones($phones, Users::i()->profilePhonesLimit);
                        } elseif ($isUpdate) {
                            $phones = $itemExists['phones'];
                        }
                        $item['phones'] = $phones;

                        # контакты: имя, skype, icq
                        foreach (array('name','skype','icq') as $k) {
                            $v = $node->getElementsByTagName($k);
                            if ($v->length) {
                                $v = strval($v->item(0)->nodeValue);
                                if (empty($v)) {
                                    if ($isUpdate) {
                                        $v = $itemExists[$k];
                                    } else {
                                        $v = ( ! empty($aUserData[$k]) ? $aUserData[$k] : '' );
                                    }
                                }
                                $item[$k] = $v;
                            } else if ($isUpdate) {
                                $item[$k] = $itemExists[$k];
                            }
                        }
                        Users::i()->cleanUserData($item, array('name','skype','icq'));

                        # контакты: "masked" версия
                        $contacts = array(
                            'skype'  => (!empty($item['skype']) ? mb_substr($item['skype'], 0, 2) . 'xxxxx' : ''),
                            'icq'    => (!empty($item['icq']) ? mb_substr($item['icq'], 0, 2) . 'xxxxx' : ''),
                            'phones' => array(),
                        );
                        foreach ($item['phones'] as $v) $contacts['phones'][] = $v['m'];
                        $item['contacts'] = serialize($contacts);
                        
                        break;
                    case 'video': # видео
                        $video = strval($node->nodeValue);
                        if (empty($video)) {
                            if ( ! $isUpdate) {
                                $item['video'] = '';
                                $item['video_embed'] = '';
                            }
                        } else {
                            if ($isUpdate) {
                                if ($itemExists['video'] == $video) {
                                    continue;
                                }
                            }
                            $video = $this->bbs->itemVideo()->parse($video);
                            if ( ! empty($video['video_url'])) {
                                $item['video'] = $video['video_url'];
                                $item['video_embed'] = serialize($video);
                            }
                        }
                        break;
                    case 'params': # дин. свойства
                        $params = $node->getElementsByTagName('param');
                        foreach ($params as $param) {
                            $field = $param->getAttribute('field');
                            $nodeValue = strval($param->nodeValue);
                            switch ($param->getAttribute('type')) {
                                case $dp::typeRadioGroup:
                                case $dp::typeCheckboxGroup:
                                case $dp::typeSelect:
                                    $paramValue = $param->getAttribute('value');
                                    if (empty($paramValue) && isset($aMParams[$field])) {
                                        foreach ($aMParams[$field] as $k=>$v) {
                                            if ($v == $nodeValue) {
                                                $paramValue = $k;
                                            }
                                        }
                                    }
                                    break;
                                default:
                                    $paramValue = $nodeValue;
                                    break;
                            }
                            $item['f' . $field] = $paramValue;
                        }
                        break;
                } # ^ switch
            } # ^ foreach

            # проверяем наличие обязательных блоков данных в <item>
            foreach (array('title', 'category', 'geo') as $k) {
                if ( ! in_array($k, $nodeKeys)) {
                    $ignored++;
                    continue; # игнорируем <item>
                }
            }

            # учитываем настройки категории:
            $catData = $catsData[$catID];
            # 1) подробный адрес и карта
            if (empty($catData['addr'])) {
                foreach (array('addr_addr','addr_lat','addr_lon') as $k) {
                    if (isset($item[$k])) unset($item[$k]);
                }
            }
            # 2) настройки цены
            if (!empty($catData['price'])) {
                if (!empty($item['price_ex'])) {
                    if ($catData['price_sett']['ex'] <= BBS::PRICE_EX_PRICE) {
                        $item['price_ex'] = BBS::PRICE_EX_PRICE;
                    } else {
                        foreach ($priceEx as $v) {
                            if ( ! ($catData['price_sett']['ex'] & $v) && ($item['price_ex'] & $v) ) {
                                $item['price_ex'] -= $v;
                            }
                        }
                    }
                }
            } else {
                foreach ($item as $k=>&$v) {
                    if (strpos($k, 'price')===0) unset($item[$k]);
                } unset($v);
            }

            # разворачиваем данные о регионе: city_id => reg1_country, reg2_region, reg3_city
            if (!isset($regionsData[$item['city_id']])) {
                $regionsData[$item['city_id']] = Geo::model()->regionParents($item['city_id']);
            }
            $item = array_merge($item, $regionsData[$item['city_id']]['db']);

            # формируем URL объявления (@items.search@translit-ID.html)
            $sLink = BBS::url('items.search', array(
                'keyword' => $catData['keyword'],
                'region'  => $regionsData[$item['city_id']]['keys']['region'],
                'city'    => $regionsData[$item['city_id']]['keys']['city'],
            ), true);
            $item['keyword'] = mb_strtolower(func::translit($item['title']));
            $item['keyword'] = preg_replace("/\-+/", '-', preg_replace('/[^a-z0-9_\-]/', '', $item['keyword']));
            $item['link'] = $sLink . $item['keyword'] . '-' . ($isUpdate ? $itemID.'.html' : '');

            if ( ! $isUpdate)
            {
                # подготавливаем ID категорий ОБ для сохранения в базу:
                # cat_id(выбранная, самая глубокая), cat_id1, cat_id2, cat_id3 ...
                $item['cat_id'] = $catID;
                if (!isset($catParents[$catID])) {
                    $catParents[$catID] = $this->bbs->model->catParentsID($catData, true);
                }
                foreach ($catParents[$catID] as $k => $v) {
                    $item['cat_id' . $k] = $v;
                }

                # статус объявления
                if ($settings['state'] == BBS::STATUS_PUBLICATED) {
                    $item['status'] = BBS::STATUS_PUBLICATED;
                    $item['publicated'] = $this->db->now();
                    $item['publicated_order'] = $this->db->now();
                    $item['publicated_to'] = $publicatedTo;
                } else {
                    $item['status'] = BBS::STATUS_PUBLICATED_OUT;
                }
                $item['moderated'] = ($isAdmin ? 1 : 0);

                # помечаем как импортированное
                $item['import'] = $importID; # ID импорта
                # тип объявления
                $item['cat_type'] = BBS::TYPE_OFFER;
                # тип владельца
                $item['owner_type'] = $ownerType;
            } else {
                 # игнорируем пустые поля
                foreach (array('descr','metro_id','addr_addr','addr_lat','addr_lon',) as $k) {
                    if (isset($item[$k]) && empty($item[$k])) unset($item[$k]);
                }
                if (!$isAdmin) {
                    if ($itemExists['status'] == BBS::STATUS_BLOCKED) {
                        $item['moderated'] = 0; # помечаем на модерацию (после блокировки)
                    } else if ($itemExists['moderated']) {
                        $item['moderated'] = 2; # помечаем на постмодерацию
                    }
                }
            }
            
            # сохраняем объявление
            $itemID_Saved = $this->bbs->model->itemSave($itemID, $item);
            if ( ! $itemID_Saved) {
                bff::log('Импорт объявлений: ошибка
                        '.($itemID ? 'обновления данных об объявлении #'.$itemID :
                        'создания нового объявления').' при импорте #'.$importID);
                continue;
            }
            if ( ! $isUpdate) {
                $itemID = $itemID_Saved;
                $stat['success']++; # кол-во добавленных
            } else {
                $stat['updated']++; # кол-во обновленных
            }

            # загружаем изображения
            if (!empty($images)) {
                $this->errors->clear();
                $oImages = $this->bbs->itemImages($itemID);
                $oImages->setAssignErrors(false);
                $imagesUploaded = ($isUpdate ? $itemExists['imgcnt'] : 0);
                foreach ($images as $image) {
                    # учитываем максимально доступное кол-во фотографий в категории (настройка категории)
                    if ($imagesUploaded >= $catData['photos']) {
                        continue;
                    }
                    # обновление: ID изображения указан и есть в базе, пропускаем
                    if ($isUpdate && $image['id'] > 0 && $oImages->imageDataExists($image['id'])) {
                        continue;
                    }
                    # загружаем изображение по URL
                    $ext = Files::getExtension($image['url']); if (empty($ext)) $ext = 'jpg';
                    $tempFile = bff::path('tmp', 'images').func::generator(10).'.'.$ext;
                    if (Files::downloadFile($image['url'], $tempFile, false)) {
                        $res = $oImages->uploadFromFile($tempFile, false);
                        if ($res === false || file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                        if ($res !== false) {
                            $imagesUploaded++;
                        }
                    }
                }
            }
        } # ^ for
        
        $aUpdate = array();
        if ($ic >= $items->length) {
            $aUpdate['status'] = self::STATUS_FINISHED;
            $aUpdate['status_changed'] = $this->db->now();
            $aUpdate['status_comment'] = serialize($stat);
        } else {
            $settings['stat'] = $stat;
        }
        
        $aUpdate['settings'] = serialize($settings);
        $aUpdate['items_processed'] = $ic;
        $aUpdate['items_ignored'] = $ignored;
        $this->bbs->model->importSave($importID, $aUpdate);

        # обновляем счетчик объявлений на модерации
        if ( ! $isAdmin) {
            $this->bbs->moderationCounterUpdate();
        }
    }

    /**
     * Формирование шаблона для импорта
     * @param array $settings параметры
     */
    public function importTemplate(array $settings)
    {
        if (empty($settings['catId']))
            return;
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }
        
        $catData = $this->bbs->model->catDataByFilter(
            array('id' => $settings['catId'], 'lang' => $settings['langKey']),
            array('title', 'numleft', 'numright', 'id', 'pid', 'numlevel')
        );
        
        $limit = 1;
        $filename = 'import';
        
        header('Content-Disposition: attachment; filename=' . $filename . '.xml');
        header("Content-Type: application/force-download");
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');

        # формируем файл, аналогичный экспорту, с одним элементом в качестве примера
        echo $this->exportFile($catData, $settings['langKey'], $limit, BBS::STATUS_PUBLICATED);
        exit;
    }

    /**
     * Отмена импорта по ID
     * @param integer $importID ID импорта
     */
    public function importCancel($importID)
    {

        $this->bbs->model->importUpdateByFilter(array(
            'status'    => array(self::STATUS_WAITING, self::STATUS_PROCESSING),
            'id'        => $importID,
        ), array(
            'status'         => self::STATUS_CANCELED,
            'status_changed' => $this->db->now(),
        ));
   }

    /**
     * Экспорт объявлений
     * @param array $settings параметры экспорта
     * @param bool $countOnly только подсчет кол-ва
     */
    public function export(array $settings, $countOnly = false)
    {
        if (empty($settings['catId'])) {
            $this->errors->set(_t('bbs.import', 'Категория указана некорректно'));
            return;
        }
        if (empty($settings['langKey'])) {
            $settings['langKey'] = LNG;
        }

        switch ((int)$settings['state']) {
            case 1: # только опубликованные
                $status = BBS::STATUS_PUBLICATED;
                break;
            default: # все
                $status = array(BBS::STATUS_PUBLICATED, BBS::STATUS_PUBLICATED_OUT);
        }
        
        $catData = $this->bbs->model->catDataByFilter(
            array('id' => $settings['catId'], 'lang' => $settings['langKey']),
            ( $countOnly ? array('numlevel') : array('title', 'numleft', 'numright', 'id', 'pid', 'addr', 'numlevel') )
        );

        if ($countOnly)
        {
            # выполняем расчет кол-ва необходимой для экспорта памяти и текущие настройки php

            # memory limit > байты
            sscanf(ini_get('memory_limit'), '%u%c', $number, $suffix);
            if (isset($suffix)) {
                $memoryLimit = $number * pow(1024, strpos('KMG', $suffix) + 1);
            }

            $filter = array(
                'cat_id' . $catData['numlevel'] => $settings['catId'],
                'status' => $status,
                'deleted' => 0
            );
            if ( ! bff::adminPanel()) {
                $filter[] = 'moderated > 0';
            }
            $count = $this->bbs->model->itemsListExport($filter, $settings['langKey'], array(), true);

            # меньше 3 тыс. кол-во памяти сильно не меняется
            if ($count > 3000) {
                if ($count < 10000)
                    $c = 0.6;
                elseif ($count < 20000)
                    $c = 0.5;
                elseif ($count < 30000)
                    $c = 0.35;
                else
                    $c = 0.3;
                $memory = $count / 1000 * $this->export_mbPer1000 * $c;
            } else
                $memory = 35;

            $memory *= pow(1024, 2); # переводим в байты

            if ($memory > $memoryLimit) {
                $aResponse = array(
                    'count'   => tpl::declension($count, _t('bbs', 'объявление;объявления;объявлений')),
                    'warning' => _t('bbs.import', 'При выполнении экспорта с выбранными параметрами будет превышен лимит выделенной памяти [memoryLimit]. Требуется памяти - [memoryNeeded]',
                        array('memoryLimit'=>tpl::filesize($memoryLimit), 'memoryNeeded'=>tpl::filesize($memory)))
                );
            } else {
                $aResponse = array(
                    'count' => tpl::declension($count, _t('bbs', 'объявление;объявления;объявлений')),
                );
            }
            $this->bbs->ajaxResponseForm($aResponse);
        }

        $limit = false;

        $filename = 'export';

        header('Content-Disposition: attachment; filename=' . $filename . '.xml');
        header("Content-Type: application/force-download");
        header('Pragma: private');
        header('Cache-control: private, must-revalidate');

        echo $this->exportFile($catData, $settings['langKey'], $limit, $status);
        exit;
    }
    
    /**
     * Генерирует XML файл для экспорта / для шаблона импорта объявлений
     * @param array $catData данные категории
     * @param string $langKey ключ языка выгрузки
     * @param mixed $limit лимит объявлений в выгрузке, false - без ограничений
     * @param int|array $status ограничение на выгрузку по статусу
     * @return string
     */
    protected function exportFile(array $catData, $langKey, $limit, $status)
    {
        $isAdmin = bff::adminPanel();
        $isImportTemplate = ($limit === 1);
        $dom = new DomDocument('1.0', 'UTF-8');

        # категория
        $categories = $dom->createElement('categories');
        $category = $dom->createElement('category', $catData['title']);
        $category->setAttribute('id', $catData['id']);
        $category->setAttribute('pid', $catData['pid']);
        $categories->appendChild($category);

        # подкатегории
        $subcats = $this->bbs->model->catChildsTree($catData['numleft'], $catData['numright'], $langKey, array('id', 'pid', 'title'));
        if ($subcats) {
            foreach ($subcats as $cat) {
                $category = $dom->createElement('category', $cat['title']);
                $category->setAttribute('id', $cat['id']);
                $category->setAttribute('pid', $cat['pid']);
                $categories->appendChild($category);
            }
        }

        # валюты
        $aCurrency = Site::model()->currencyData(false);
        $currencies = $dom->createElement('currencies');
        foreach ($aCurrency as $aCurr) {
            $curr = $dom->createElement('currency', $aCurr['title']);
            $curr->setAttribute('id', $aCurr['id']);
            $currencies->appendChild($curr);
        }

        # дин. свойства
        $this->bbs->dp()->setCurrentLanguage($langKey);
        $aDynprops = $this->bbs->dp()->getByOwner($catData['id'], true, true, false);
        $itemFields = array();
        $itemParamFields = array();
        if ($aDynprops) {
            $params = $dom->createElement('params');
            foreach ($aDynprops as $param) {
                $par = $dom->createElement('param');
                $par->setAttribute('id', $param['id']);
                $par->setAttribute('title', $param['title_' . $langKey]);
                $par->setAttribute('field', $param['data_field']);
                $par->setAttribute('type', $param['type']);

                $itemParamFields[$param['data_field']] = array(
                    'title' => $param['title_' . $langKey],
                    'type' => $param['type'],
                );
                $itemFields[] = 'I.f' . $param['data_field'];

                if (!empty($param['multi'])) {
                    foreach ($param['multi'] as $value) {
                        if ($value['value'] != '0') {
                            $itemParamFields[$param['data_field']]['multi'][$value['value']] = $value['name'];
                            $valueField = $dom->createElement('value', $value['name']);
                            $valueField->setAttribute('id', $value['value']);
                            $par->appendChild($valueField);
                        }
                    }
                }

                $params->appendChild($par);
            }
        }

        # регионы + станции метро
        $coveringType = Geo::coveringType();
        $aFilter = array('main>0', 'enabled' => 1);
        $sOrderBy = 'main';
        switch ($coveringType) {
            case Geo::COVERING_COUNTRY:
                $aFilter['country'] = Geo::coveringRegion();
                break;
            case Geo::COVERING_REGION:
                $aFilter['pid'] = Geo::coveringRegion();
                break;
            case Geo::COVERING_CITIES:
                $aFilter['id'] = Geo::coveringRegion();
                $sOrderBy = 'FIELD(R.id, ' . join(',', $aFilter['id']) . ')'; /* MySQL only */
                unset($aFilter[0]); # main>0
                break;
            case Geo::COVERING_CITY:
                $aFilter['id'] = Geo::coveringRegion();
                break;
        }
        $aCities = Geo::model()->regionsListingData(Geo::lvlCity, $aFilter, $langKey, $sOrderBy);
        if ($aCities) {
            $aCityId = array_keys($aCities);
            $aStations = Geo::model()->metroStationsList(array('city_id' => $aCityId), $langKey);
            $aMetro = array();
            if ($aStations) {
                foreach ($aStations as $station) {
                    $aMetro[$station['city_id']][] = $station;
                }
            }
            $cities = $dom->createElement('cities');
            foreach ($aCities as $aCity) {
                $city = $dom->createElement('city');
                $cityTitle = $dom->createElement('title', $aCity['title']);
                $city->appendChild($cityTitle);
                $city->setAttribute('id', $aCity['id']);
                $city->setAttribute('region', $aCity['parentTitle']);
                if (isset($aMetro[$aCity['id']])) {
                    $metro = $dom->createElement('metro');
                    foreach ($aMetro[$aCity['id']] as $aM) {
                        $station = $dom->createElement('station', $aM['title']);
                        $station->setAttribute('id', $aM['id']);
                        $metro->appendChild($station);
                    }
                    $city->appendChild($metro);
                }
                $cities->appendChild($city);
            }
        }

        # объявления
        $itemsFilter = array(
            'cat_id' . $catData['numlevel'] => $catData['id'],
            'status'  => $status,
            'deleted' => 0,
        );
        if ( ! $isAdmin) {
            $itemsFilter[] = 'moderated > 0';
        }

        $aData = $this->bbs->model->itemsListExport($itemsFilter, $langKey, $itemFields, false, $limit);
        if ($aData)
        {
            $aItemId = array_keys($aData);
            $i = new BBSItemImages();
            $aImage = $i->getItemsImagesData($aItemId);
            $imagesKey = $i->getMaxSizeKey();

            $items = $dom->createElement('items');
            foreach ($aData as &$v)
            {
                $item = $dom->createElement('item');
                $item->setAttribute('id', ($isAdmin ? $v['id'] : mt_rand(1,1000))); # ID объявления

                # заголовок
                if ( ! $isAdmin) $v['title'] = _t('bbs.import', 'Заголовок объявления');
                $item->appendChild( $dom->createElement('title', $v['title']) );
                # описание
                if ( ! $isAdmin) $v['descr'] = _t('bbs.import', 'Подробное описание объявления');
                $item->appendChild( $dom->createElement('description', $v['descr']) );

                # пользователь (владелец объявления)
                if ($isAdmin) {
                    $user = $dom->createElement('user', $v['email']);
                    $user->setAttribute('id', $v['user_id']);
                    $user->setAttribute('shop', $v['shop_id']);
                    $item->appendChild($user);
                }

                # категория
                $cat = $dom->createElement('category', $v['cat_id']);
                if ($v['cat_id'] == $catData['id'])
                    $cat->setAttribute('title', $catData['title']);
                else
                    $cat->setAttribute('title', $subcats[$v['cat_id']]['title']);
                $item->appendChild($cat);

                # geo
                $geo = $dom->createElement('geo');
                $geo_city = $dom->createElement('city', $v['city_title']);
                $geo_city->setAttribute('id', $v['city_id']);
                $geo_station = $dom->createElement('station', $v['metro_title']);
                $geo_station->setAttribute('id', $v['metro_id']);
                $geo->appendChild($geo_city);
                $geo->appendChild($geo_station);
                $geo->appendChild( $dom->createElement('addr', $v['addr_addr']) );
                $geo->appendChild( $dom->createElement('lat', $v['addr_lat']) );
                $geo->appendChild( $dom->createElement('lon', $v['addr_lon']) );
                $item->appendChild($geo);

                # цена
                $price = $dom->createElement('price', $v['price']);
                $price->setAttribute('currency', $v['price_curr']);
                $price->setAttribute('free', ($v['price_ex'] & BBS::PRICE_EX_FREE));
                $price->setAttribute('exchange', ($v['price_ex'] & BBS::PRICE_EX_EXCHANGE));
                $price->setAttribute('mod', ($v['price_ex'] & BBS::PRICE_EX_MOD));
                $item->appendChild($price);

                # изображения
                if (isset($aImage[$v['id']])) {
                    $images = $dom->createElement('images');
                    if ( ! $isAdmin) {
                        # пример пользователю: оставляем 2 изображения
                        $aImage[$v['id']] = array_slice($aImage[$v['id']], 0, 2, true);
                    }
                    $i->setRecordID($v['id']);
                    foreach ($aImage[$v['id']] as $image) {
                        $url = $i->getURL($image, $imagesKey);
                        if (mb_strpos($url, '//') === 0) {
                            $url = Request::scheme() . ':' . $url;
                        }
                        $img = $dom->createElement('image', $url);
                        $img->setAttribute('id', $image['id']);
                        $images->appendChild($img);
                    }
                    $item->appendChild($images);
                }

                # контакты: name, phones, skype, icq
                # игнорируем <contacts> при импорте и включенной настройке "отображать контакты объявления из профиля"
                if (!$isImportTemplate || !$this->bbs->getItemContactsFromProfile())
                {
                    $contacts = $dom->createElement('contacts');

                    $v['phones'] = func::unserialize( !empty($v['phones']) ? $v['phones'] : $v['u_phones'] );

                    if ( ! $isAdmin) {
                        if (empty($v['skype']))
                            $v['skype'] = $v['u_skype'];
                        if (empty($v['icq']))
                            $v['icq'] = $v['u_icq'];
                    }

                    $contacts->appendChild( $dom->createElement('name', $v['name']) );

                    if ($v['phones']) {
                        $phones = $dom->createElement('phones');
                        foreach ($v['phones'] as $phone) {
                            if (is_array($phone))
                                $phone = $phone['v'];
                            $ph = $dom->createElement('phone', $phone);
                            $phones->appendChild($ph);
                        }
                        $contacts->appendChild($phones);
                    }

                    $contacts->appendChild( $dom->createElement('skype', $v['skype']) );
                    $contacts->appendChild( $dom->createElement('icq', $v['icq']) );
                    $item->appendChild($contacts);
                }

                # видео
                $item->appendChild( $dom->createElement('video', $v['video']) );

                # дин. свойства
                $itemParams = $dom->createElement('params');
                foreach ($itemParamFields as $fieldId => $value) {
                    $field = 'f' . $fieldId;
                    if (!isset($value['multi'])) {
                        $paramValue = $v[$field];
                        $fieldValue = 0;
                    } else {
                        $paramValue = (isset($value['multi'][$v[$field]]) ? $value['multi'][$v[$field]] : 0);
                        $fieldValue = $v[$field];
                    }

                    $itemParam = $dom->createElement('param', $paramValue);
                    $itemParam->setAttribute('field', $fieldId);
                    $itemParam->setAttribute('type', $value['type']);
                    $itemParam->setAttribute('value', $fieldValue);
                    $itemParam->setAttribute('title', $value['title']);

                    $itemParams->appendChild($itemParam);
                }
                $item->appendChild($itemParams);

                $items->appendChild($item);
            }
            unset($v);
        }

        $bbs = $dom->createElement('bbs');
        $bbs->setAttribute('type', self::ATTRIBUTE_TYPE);
        $bbs->appendChild( $dom->createElement('title', config::get('title_' . $langKey, SITEHOST)) );
        $bbs->appendChild( $dom->createElement('url', SITEHOST) );
        $bbs->appendChild( $dom->createElement('locale', $langKey) );
        $bbs->appendChild($categories);
        $bbs->appendChild($currencies);
        if (!empty($cities))
            $bbs->appendChild($cities);
        if (!empty($params))
            $bbs->appendChild($params);
        if (!empty($items))
            $bbs->appendChild($items);
        $dom->appendChild($bbs);

        $dom->formatOutput = true;
        return $dom->saveXML();
    }
    
    /**
     * Отменяем все импорты пользователя
     * @param int $nUserID ID пользователя
     */
    public function cancelUserImport($nUserID)
    {
        $this->bbs->model->importUpdateByFilter(array(
            'status'  => array(self::STATUS_WAITING, self::STATUS_PROCESSING),
            'user_id' => $nUserID
        ), array(
            'status'         => self::STATUS_CANCELED,
            'status_changed' => $this->db->now(),
            'status_comment' => serialize(array('message'=>_t('bbs.import', 'Блокировка пользователя')))
        ));
    }
    
    /**
     * Закрываем задачу с кодом и комментарием ошибки
     * @param integer $importID ID импорта
     * @param string $message комментарий для администратора
     */
    protected function importError($importID, $message = '')
    {
        if (empty($importID)) return;
        if (!empty($message)) {
            $this->bbs->model->importSave($importID, array(
                'status'         => self::STATUS_ERROR,
                'status_changed' => $this->db->now(),
                'status_comment' => serialize(array('message'=>$message))
            ));
            bff::log('Импорт объявлений: ошибка при обработке импорта #'.$importID.', '.$message);
        }
    }
    
    /**
     * Возвращает путь к директории с файлами импорта (файлу импорта)
     * @param bool $url - true вернуть в виде url, false - если абсолютный путь
     * @param string $filename имя файла или пустая строка
     * @return string
     */
    public static function getImportPath($url = false, $filename = '')
    {
        if (!$url) {
            return bff::path('import') . $filename;
        } else {
            $url = bff::url('import') . $filename;
            if (mb_strpos($url, '//') === 0) {
                $url = Request::scheme() . ':' . $url;
            }
            return $url;
        }
    }

    /**
     * Формирование списка доступных статусов импорта с описанием
     * @return array
     */
    public static function getStatusList()
    {
         return array(
            self::STATUS_WAITING      => _t('bbs.import','ожидает'),
            self::STATUS_PROCESSING   => _t('bbs.import','в процессе'),
            self::STATUS_FINISHED     => _t('bbs.import','завершён'),
            self::STATUS_CANCELED     => _t('bbs.import','отменён'),
            self::STATUS_ERROR        => _t('bbs.import','завершён с ошибкой')
         );
    }

    /**
     * Генерирует XML файл для экспорта на печать по ID объявлений
     * @param array $aItemsID id объявлений для экспорта
     * @param string $langKey ключ языка выгрузки
     * @return string
     */
    public function exportPrintXML(array $aItemsID, $langKey = LNG)
    {
        $dom = new DomDocument('1.0', 'UTF-8');

        $aData = $this->bbs->model->itemsListExportPrint($aItemsID, $langKey);
        if (!empty($aData['items']))
        {
            $i = new BBSItemImages();
            $aImage = $i->getItemsImagesData($aItemsID);
            $imagesKey = $i->getMaxSizeKey();

            $aCurrency = Site::model()->currencyData(false);
            $aDynprops = array();

            $items = $dom->createElement('items');
            foreach ($aData['items'] as &$v)
            {
                $item = $dom->createElement('item');
                $item->setAttribute('id', $v['id']); # ID объявления

                # заголовок
                $item->appendChild( $dom->createElement('title', $v['title']) );
                # описание
                $item->appendChild( $dom->createElement('description', $v['descr']) );

                # пользователь (владелец объявления)
                $user = $dom->createElement('user', $v['email']);
                $user->setAttribute('id', $v['user_id']);
                $user->setAttribute('shop', $v['shop_id']);
                $item->appendChild($user);

                # категория
                $cat = $dom->createElement('category', $v['category']);
                $cat->setAttribute('id', $v['cat_id']);
                $cat->setAttribute('pid', $v['cat_id1']);
                $item->appendChild($cat);

                $cat = $dom->createElement('category_path', $v['category_path']);
                $item->appendChild($cat);

                # geo
                $geo = $dom->createElement('geo');
                $geo_country = $dom->createElement('country', $v['country']);
                $geo_country->setAttribute('id', $v['reg1_country']);
                $geo->appendChild($geo_country);
                $geo_region = $dom->createElement('region', $v['region']);
                $geo_region->setAttribute('id', $v['reg2_region']);
                $geo->appendChild($geo_region);
                $geo_city = $dom->createElement('city', $v['city']);
                $geo_city->setAttribute('id', $v['reg3_city']);
                $geo->appendChild($geo_city);
                if ($v['metro_id']) {
                    $geo_station = $dom->createElement('station', $v['metro']);
                    $geo_station->setAttribute('id', $v['metro_id']);
                    $geo->appendChild($geo_station);
                }
                $geo->appendChild($dom->createElement('addr', $v['addr_addr']));
                $geo->appendChild($dom->createElement('lat', $v['addr_lat']));
                $geo->appendChild($dom->createElement('lon', $v['addr_lon']));
                $item->appendChild($geo);

                # цена
                if (!empty($aCurrency[ $v['price_curr'] ])) {
                    $price = $dom->createElement('price', $v['price']);
                    $price->setAttribute('currency', $v['price_curr']);
                    $price->setAttribute('free', ($v['price_ex'] & BBS::PRICE_EX_FREE));
                    $price->setAttribute('exchange', ($v['price_ex'] & BBS::PRICE_EX_EXCHANGE));
                    $price->setAttribute('mod', ($v['price_ex'] & BBS::PRICE_EX_MOD));
                    $price->setAttribute('title', $aCurrency[$v['price_curr']]['title']);
                    $price->setAttribute('short', $aCurrency[$v['price_curr']]['title_short']);
                    $item->appendChild($price);
                }

                # изображения
                if (isset($aImage[$v['id']])) {
                    $images = $dom->createElement('images');
                    $i->setRecordID($v['id']);
                    foreach ($aImage[$v['id']] as $image) {
                        $url = $i->getURL($image, $imagesKey);
                        if (mb_strpos($url, '//') === 0) {
                            $url = Request::scheme() . ':' . $url;
                        }
                        $img = $dom->createElement('image', $url);
                        $img->setAttribute('id', $image['id']);
                        $images->appendChild($img);
                    }
                    $item->appendChild($images);
                }

                # контакты: name, phones, skype, icq
                $contacts = $dom->createElement('contacts');

                $v['phones'] = func::unserialize( $this->bbs->getItemContactsFromProfile() ? $v['u_phones'] : $v['phones'] );

                if (empty($v['skype']))
                    $v['skype'] = $v['u_skype'];
                if (empty($v['icq']))
                    $v['icq'] = $v['u_icq'];
                $contacts->appendChild($dom->createElement('name', $v['name']));
                if ($v['phones']) {
                    $phones = $dom->createElement('phones');
                    foreach ($v['phones'] as $phone) {
                        if (is_array($phone))
                            $phone = $phone['v'];
                        $ph = $dom->createElement('phone', $phone);
                        $phones->appendChild($ph);
                    }
                    $contacts->appendChild($phones);
                }
                if (!empty($v['skype'])) {
                    $contacts->appendChild($dom->createElement('skype', $v['skype']));
                }
                if (!empty($v['icq'])) {
                    $contacts->appendChild($dom->createElement('icq', $v['icq']));
                }
                $item->appendChild($contacts);

                # видео
                if (!empty($v['video'])) {
                    $item->appendChild($dom->createElement('video', $v['video']));
                }

                # дин. свойства
                if (!isset($aDynprops[ $v['cat_id'] ])) {
                    $aDynprops[$v['cat_id']] = $this->bbs->dp()->getByOwner($v['cat_id'], true, true, false);
                }
                $itemParams = $dom->createElement('params');
                foreach ($aDynprops[$v['cat_id']] as $vv) {
                    $field = 'f'.$vv['data_field'];

                    if ( ! empty($vv['multi'])) {
                        $paramValue = 0;
                        foreach ($vv['multi'] as $vvm) {
                            if ($vvm['value'] == $v[$field]) {
                                $paramValue = $vvm['name']; break;
                            }
                        }
                        $fieldValue = $v[ $field ];
                    } else {
                        $paramValue = $v[ $field ];
                        $fieldValue = false;
                    }

                    $itemParam = $dom->createElement('param', $paramValue);
                    $itemParam->setAttribute('type', $vv['type']);
                    if ($fieldValue !== false) {
                        $itemParam->setAttribute('value', $fieldValue);
                    }
                    $itemParam->setAttribute('title', $vv['title']);

                    $itemParams->appendChild($itemParam);
                }
                $item->appendChild($itemParams);

                $items->appendChild($item);
            }
            unset($v);
        }

        $bbs = $dom->createElement('bbs');
        $bbs->setAttribute('type', 'items-export-press');
        $bbs->setAttribute('date', date(DATE_ISO8601));
        $bbs->appendChild( $dom->createElement('title', config::get('title_' . $langKey, SITEHOST)) );
        $bbs->appendChild( $dom->createElement('url', SITEHOST) );
        $bbs->appendChild( $dom->createElement('locale', $langKey) );
        if (!empty($items)) {
            $bbs->appendChild($items);
        }
        $dom->appendChild($bbs);
        $dom->formatOutput = true;
        return $dom->saveXML();
    }

}