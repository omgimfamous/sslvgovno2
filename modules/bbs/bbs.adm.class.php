<?php

/**
 * Права доступа группы:
 *  - bbs: Объявления
 *      - items-listing: Просмотр списка объявлений
 *      - items-edit: Управление объявлениями (добавление/редактирование/удаление)
 *      - items-moderate: Модерация объявлений (блокирование/одобрение/продление/активация)
 *      - items-comments: Управление комментариями
 *      - items-press: Управление печатью в прессу
 *      - items-import: Импорт объявлений
 *      - items-export: Управление печатью в прессу
 *      - claims-listing: Просмотр списка жалоб
 *      - claims-edit: Управление жалобами (модерация/удаление)
 *      - categories: Управление категориями
 *      - types: Управление типами категорий
 *      - svc: Управление услугами
 *      - settings: Дополнительные настройки
 */
class BBS extends BBSBase
{

    public function init()
    {
        parent::init();
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # объявления

    public function listing()
    {
        if (!$this->haveAccessTo('items-listing')) {
            return $this->showAccessDenied();
        }
        $aData = array('f' => array(), 'shops_on' => bff::shopsEnabled());

        $sAction = $this->input->get('act', TYPE_STR);
        if ($sAction) {
            $aResponse = array();
            switch ($sAction) {
                case 'delete':
                {
                    if (!$this->haveAccessTo('items-edit')) {
                        $this->errors->accessDenied();
                        break;
                    }

                    $nItemID = $this->input->postget('id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aDataEmail = $this->model->itemData2Email($nItemID);

                    $res = $this->model->itemsDelete(array($nItemID), true);
                    # объявление было удалено
                    if ($res) {
                        if ($aDataEmail !== false) {
                            bff::sendMailTemplate(array(
                                    'name'       => $aDataEmail['name'],
                                    'email'      => $aDataEmail['email'],
                                    'item_id'    => $aDataEmail['item_id'],
                                    'item_link'  => $aDataEmail['item_link'],
                                    'item_title' => $aDataEmail['item_title'],
                                ), 'bbs_item_deleted', $aDataEmail['email']
                            );
                        }
                    } else {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'dev-items-links-rebuild':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                    }

                    $this->model->itemsLinksRebuild();

                    $this->adminRedirect(Errors::SUCCESS);
                }
                break;
                case 'dev-items-publicate-all-unpublicated':
                {
                    if (!FORDEV) {
                        $this->showAccessDenied();
                    }

                    $res = $this->model->itemsPublicateAllUnpublicated();
                    if ($res > 0) {
                        $this->moderationCounterUpdate();
                    }

                    $this->adminRedirect(Errors::SUCCESS);
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $f = $this->input->postgetm(array(
            'page'          => TYPE_UINT,
            'cat'           => TYPE_UINT,
            'region'        => TYPE_UINT,
            'status'        => TYPE_UINT,
            'title'         => array(TYPE_NOTAGS, 'len' => 150),
            'uid'           => array(TYPE_NOTAGS, 'len' => 150), # ID / E-mail пользователя
            'shopid'        => TYPE_UINT, # ID магазина
            'moderate_list' => TYPE_UINT, # доп. фильтр объявлений "на модерации"
        ));

        # формируем фильтр списка объявлений
        # - исключаем формирующиеся(недооформленные) объявления из списка
        $sql = array();
        if ($f['cat'] > 0) {
            $cats = array();
            for ($i = 1; $i <= static::CATS_MAXDEEP; $i++) {
                $cats[] = 'I.cat_id' . $i . ' = :catID';
            }
            $sql[':cond1'] = array('(' . join(' OR ', $cats) . ')', ':catID' => $f['cat']);
        }

        switch ($f['status']) {
            case 0:
            { # Опубликованные
                $sql['status'] = self::STATUS_PUBLICATED;
                if (static::premoderation()) {
                    $sql[':mod'] = 'I.moderated > 0';
                }
            }
            break;
            case 2:
            { # Снятые с публикации
                $sql['status'] = self::STATUS_PUBLICATED_OUT;
                $sql['deleted'] = 0;
            }
            break;
            case 3:
            { # На модерации
                if (!isset($sql[':moderated'])) {
                    $sql[':mod'] = 'I.moderated != 1';
                }
                if ($f['moderate_list'] == 1) { # отредактированные
                    $sql[':mod'] = 'I.moderated > 1';
                } elseif ($f['moderate_list'] == 2) { # импортированные
                    $sql[':import'] = 'I.import > 0';
                }

                $sql[':ublocked'] = 'U.blocked = 0';
                $sql['deleted'] = 0;
                $sql['status'] = array(self::STATUS_PUBLICATED, self::STATUS_BLOCKED);
            }
            break;
            case 4:
            { # Неактивированные
                $sql['status'] = self::STATUS_NOTACTIVATED;
            }
            break;
            case 5:
            { # Заблокированные
                $sql['status'] = self::STATUS_BLOCKED;
            }
            break;
            case 6:
            { # Удаленные
                $sql['deleted'] = 1;
            }
            break;
            case 7:
            { # Все
                # ...
            }
            break;
        }

        if (!empty($f['uid'])) {
            $nUserID = 0;
            if ($this->input->isEmail($f['uid'])) {
                $aUserData = Users::model()->userDataByFilter(array('email' => $f['uid']), array('user_id'));
                if (!empty($aUserData['user_id'])) {
                    $nUserID = intval($aUserData['user_id']);
                }
            } else {
                $nUserID = intval($f['uid']);
            }
            if (!empty($nUserID)) {
                $sql[] = array('I.user_id = :userid', ':userid' => $nUserID);
            }
        }

        if (!empty($f['title'])) {
            if (intval($f['title']) > 0) {
                $sql[] = array('I.id = :id', ':id' => intval($f['title']));
            } else {
                $sql[] = array('I.title LIKE :title', ':title' => '%' . $f['title'] . '%');
            }
        }

        if ($f['shopid'] > 0 && $aData['shops_on']) {
            $sql[] = array('I.shop_id = :shopID', ':shopID' => $f['shopid']);
        } else {
            $f['shopid'] = '';
        }

        if ($f['region']) {
            $aRegions = Geo::regionData($f['region']);
            switch ($aRegions['numlevel']) {
                case Geo::lvlCountry: $sql['reg1_country'] = $f['region']; break;
                case Geo::lvlRegion:  $sql['reg2_region']  = $f['region']; break;
                case Geo::lvlCity:    $sql['reg3_city']    = $f['region']; break;
            }
        }

        $nCount = $this->model->itemsListing($sql, true);
        $aData['orders'] = array('created' => 'desc');
        $f += $this->prepareOrder($orderBy, $orderDirection, 'created' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);
        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;
        $aData['f'] = $f;

        $oPgn = new Pagination($nCount, 20, '#', 'jItems.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['list'] = $this->model->itemsListing($sql, false, array(), $oPgn->getLimitOffset(), "$orderBy $orderDirection");
        $aData['list'] = $this->viewPHP($aData, 'admin.items.listing.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponse(array(
                    'list'   => $aData['list'],
                    'pgn'    => $aData['pgn'],
                    'filter' => $f,
                )
            );
        }

        $aData['cats_select'] = $this->model->catsOptions('adm-items-listing', $f['cat'], 'Все разделы');

        return $this->viewPHP($aData, 'admin.items.listing');
    }


    public function listing_press()
    {
        if (!$this->haveAccessTo('items-press')) {
            return $this->showAccessDenied();
        }

        $sAction = $this->input->getpost('act', TYPE_STR);
        if (!empty($sAction)) {
            $aResponse = array();
            switch ($sAction) {
                case 'press':
                {
                    # тип: 1 - отмеченные, 2 - все
                    $nType = $this->input->post('type', TYPE_UINT);

                    $sDate = $this->input->post('date', TYPE_STR);
                    $nDate = (!empty($sDate) ? strtotime($sDate) : false);
                    if (empty($nDate) || $nDate === -1) {
                        $this->errors->set('Дата публикации указана некорректно');
                        break;
                    }

                    if ($nType == 1) { # отмеченные
                        $aItemsID = $this->input->post('i', TYPE_ARRAY_UINT);
                        if (empty($aItemsID)) {
                            $this->errors->set('Необходимо отметить объявления для печати');
                            break;
                        }
                    } else { # все
                        $aItems = $this->model->itemsDataByFilter(
                            array(
                                'svc_press_status' => self::PRESS_STATUS_PAYED,
                                'status'           => array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT),
                                'deleted'          => 0
                            ), array('id')
                        );
                        if (empty($aItems)) {
                            $this->errors->set('Нет объявлений доступных для печати');
                            break;
                        }
                        $aItemsID = array_keys($aItems);
                    }

                    $aResponse['updated'] = $this->model->itemsSave($aItemsID, array(
                            'svc_press_date'   => date('Y.m.d', $nDate),
                            'svc_press_status' => self::PRESS_STATUS_PUBLICATED
                        )
                    );
                    $this->pressCounterUpdate(-intval($aResponse['updated']));

                    $aResponse['updated'] = tpl::declension($aResponse['updated'], _t('bbs', 'объявление;объявления;объявлений'));
                }
                break;
                case 'export':
                case 'export-check':
                {
                    # тип: 1 - отмеченные, 2 - все
                    $nType = $this->input->postget('type', TYPE_UINT);
                    if ($nType == 1) { # отмеченные
                        $aItemsID = $this->input->postget('i', TYPE_ARRAY_UINT);
                        if (empty($aItemsID)) {
                            $this->errors->set('Необходимо отметить объявления для печати');
                            break;
                        }
                    } else { # все
                        $aItems = $this->model->itemsDataByFilter(
                            array(
                                'svc_press_status' => self::PRESS_STATUS_PAYED,
                                'status'           => array(self::STATUS_PUBLICATED, self::STATUS_PUBLICATED_OUT),
                                'deleted'          => 0
                            ), array('id')
                        );
                        if (empty($aItems)) {
                            $this->errors->set('Нет объявлений доступных для печати');
                            break;
                        }
                        $aItemsID = array_keys($aItems);
                    }
                    if ($sAction == 'export') {
                        $import = $this->itemsImport();

                        $filename = 'press';

                        header('Content-Disposition: attachment; filename=' . $filename . '.xml');
                        header("Content-Type: application/force-download");
                        header('Pragma: private');
                        header('Cache-control: private, must-revalidate');

                        echo $import->exportPrintXML($aItemsID);
                        bff::shutdown();
                    }
                } break;
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aData = array('f' => array());
        $aData['orders'] = array('svc_press_date' => 'desc');

        $f = array();
        $this->input->postgetm(array(
                'page'    => TYPE_UINT,
                'status'  => TYPE_UINT,
                'pressed' => TYPE_STR,
            ), $f
        );

        # формируем фильтр списка объявлений
        $sql = array();

        switch ($f['status']) {
            case self::PRESS_STATUS_PUBLICATED: # Опубликованные в прессе
            {
                $sql['svc_press_status'] = self::PRESS_STATUS_PUBLICATED;
                $nPressed = strtotime($f['pressed']);
                if (!empty($nPressed) && $nPressed !== -1) {
                    $sql['svc_press_date'] = date('Y-m-d', $nPressed);
                }
            }
            break;
            case static::PRESS_STATUS_PUBLICATED_EARLIER: # Предыдущие публикации
            {
                $sql['svc_press_status'] = 0;
                $sql[':svc_press_date_last'] = 'svc_press_date_last != "0000-00-00"';
                $nPressed = strtotime($f['pressed']);
                if (!empty($nPressed) && $nPressed !== -1) {
                    $sql['svc_press_date_last'] = date('Y-m-d', $nPressed);
                }
            }
            break;
            case self::PRESS_STATUS_PAYED: # Ожидают публикации в прессе
            default:
                {
                    $sql['svc_press_status'] = self::PRESS_STATUS_PAYED;
                    $f['status'] = self::PRESS_STATUS_PAYED;
                }
            break;
        }
        $nCount = $this->model->itemsListing($sql, true);
        $f += $this->prepareOrder($orderBy, $orderDirection, 'svc_press_date' . tpl::ORDER_SEPARATOR . 'desc', $aData['orders']);
        $f['order'] = $orderBy . tpl::ORDER_SEPARATOR . $orderDirection;
        $aData['f'] = $f;
        $oPgn = new Pagination($nCount, 15, '#', 'jItemsPress.page(' . Pagination::PAGE_ID . '); return false;');
        $aData['pgn'] = $oPgn->view();
        $aData['list'] = $this->model->itemsListing($sql, false, array(), $oPgn->getLimitOffset(), "$orderBy $orderDirection");
        $aData['list'] = $this->viewPHP($aData, 'admin.items.press.ajax');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['tabs'] = array(
            BBS::PRESS_STATUS_PAYED      => array('t' => 'Ожидают публикации'),
            BBS::PRESS_STATUS_PUBLICATED => array('t' => 'Опубликованные'),
            BBS::PRESS_STATUS_PUBLICATED_EARLIER  => array('t' => 'Предыдущие публикации')
        );

        return $this->viewPHP($aData, 'admin.items.press');
    }


    public function add()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $this->validateItemData($aData, 0);

        if (Request::isPOST()) { # ajax
            $aResponse = array('id' => 0);
            $nUserID = $this->input->post('user_id', TYPE_UINT);
            if (!$nUserID) {
                $this->errors->set('E-mail адрес пользователя указан некорректно', 'email');
            } else {
                $aUserData = Users::model()->userData($nUserID, array('name', 'phones', 'skype', 'icq', 'shop_id'));
                if (empty($aUserData)) {
                    $this->errors->set('E-mail адрес пользователя указан некорректно', 'email');
                } else {
                    foreach ($aUserData as $k => $v) {
                        $aData[$k] = $aUserData[$k];
                    }
                }
                $aData['shop_id'] = $this->publisherCheck($aUserData['shop_id'], 'shop');
                if ($aData['shop_id'] && !Shops::model()->shopActive($aData['shop_id'])) {
                    $this->errors->set('Размещение объявления доступно только от активированного магазина');
                }
            }

            if ($this->errors->no()) {
                # публикуем
                $aData['user_id'] = $nUserID;
                $aData['publicated'] = $this->db->now();
                $aData['publicated_order'] = $this->db->now();
                $aData['publicated_to'] = $this->getItemPublicationPeriod();
                $aData['status'] = self::STATUS_PUBLICATED;
                $aData['moderated'] = 1;

                $nItemID = $this->model->itemSave(0, $aData, 'd');
                if ($nItemID > 0) {
                    $aResponse['id'] = $nItemID;
                    $this->itemImages($nItemID)->saveTmp('img');
                }
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aData['id'] = 0;
        $aData['images'] = array();
        $aData['imgcnt'] = 0;
        $aData['img'] = $this->itemImages(0);
        # выбор категории
        $aData['cats'] = $this->model->catsOptionsByLevel(array(), array('empty' => 'Выбрать'));
        $aData['cat'] = array();
        # город и метро
        $aData['city_data'] = array();
        $aData['city_metro'] = Geo::cityMetro();
        if (Geo::coveringType(Geo::COVERING_CITY)) {
    		$aData['city_id'] = Geo::coveringRegion();
    		$aData['city_data'] = Geo::regionData($aData['city_id']);
            $aData['city_metro'] = Geo::cityMetro($aData['city_id'], 0, true);
		}

        return $this->viewPHP($aData, 'admin.form');
    }

    public function edit()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!$nItemID) {
            $this->showImpossible(true);
        }

        if (Request::isPOST()) { # ajax
            $aResponse = array();
            $sAction = $this->input->get('act', TYPE_STR);
            switch ($sAction) {
                case 'info': # сохранение данных вкладки "Описание"
                {
                    $aItemData = $this->model->itemData($nItemID, array(
                            'city_id',
                            'cat_id',
                            'video',
                            'video_embed'
                        ), true
                    );
                    $this->validateItemData($aData, $nItemID, $aItemData);

                    if (static::publisher(static::PUBLISHER_USER_OR_SHOP)) {
                        $aData['shop_id'] = $this->publisherCheck($aItemData['user_shop_id'], 'shop');
                        if ($aData['shop_id'] && !Shops::model()->shopActive($aData['shop_id'])) {
                            $this->errors->set('Размещение объявления доступно только от активированного магазина');
                        }
                    }
                    if ($this->errors->no()) {
                        $this->model->itemSave($nItemID, $aData, 'd');
                    }

                    $this->ajaxResponseForm($aResponse);
                }
                break;
                case 'comments-init': # инициализация вкладки "Комментарии"
                {
                    $aData['id'] = $nItemID;
                    $aData['edit_allowed'] = $this->haveAccessTo('items-comments');
                    $aData['comments'] = $this->itemComments()->admListing($nItemID);
                    $aResponse['html'] = $this->viewPHP($aData, 'admin.form.comments');
                }
                break;
                case 'claims-init': # инициализация вкладки "Жалобы"
                {
                    $aData['id'] = $nItemID;
                    $aData['edit_allowed'] = $this->haveAccessTo('claims-edit');
                    $aData['claims'] = $this->model->claimsListing(array('item_id' => $nItemID));
                    foreach ($aData['claims'] as &$v) {
                        $v['message'] = $this->getItemClaimText($v['reason'], $v['message']);
                    }
                    unset($v);

                    $aResponse['html'] = $this->viewPHP($aData, 'admin.items.claims');
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->itemData($nItemID, array(), true);
        if (!$aData) {
            $this->showImpossible(true);
        }

        # формируем форму дин. свойств, типы
        $aData['cat'] = $this->itemFormByCategory($aData['cat_id'], $aData);

        # выбор категории
        $aData['cats'] = $this->model->catParentsID($aData['cat_id']);
        $aData['cats'] = $this->model->catsOptionsByLevel($aData['cats'], array('empty' => 'Выбрать'));

        # формируем данные об изображениях
        $aData['img'] = $this->itemImages($nItemID);
        $aData['images'] = $aData['img']->getData($aData['imgcnt']);

        # город, метро
        $aData['city_data'] = Geo::regionData($aData['city_id']);
        $aData['city_metro'] = Geo::cityMetro($aData['city_id'], $aData['metro_id'], true);

        $this->itemComments()->admListingIncludes(); # подключаем js+css для вкладки "Комментарии" / "Жалобы"
        return $this->viewPHP($aData, 'admin.form');
    }

    public function img()
    {
        if (!$this->haveAccessTo('items-edit')) {
            return $this->showAccessDenied();
        }

        $nItemID = $this->input->getpost('item_id', TYPE_UINT);
        $oImages = $this->itemImages($nItemID);
        $aResponse = array();
        $sAction = $this->input->getpost('act');

        switch ($sAction) {
            case 'upload': # загрузка изображений
            {

                $mResult = $oImages->uploadQQ();
                $aResponse = array('success' => ($mResult !== false && $this->errors->no()));

                if ($mResult !== false) {
                    $aResponse = array_merge($aResponse, $mResult);
                    $aResponse = array_merge($aResponse, $oImages->getURL($mResult, array(
                                BBSItemImages::szSmall,
                                BBSItemImages::szMedium,
                                BBSItemImages::szView
                            ), empty($nItemID)
                        )
                    );
                }
                $aResponse['errors'] = $this->errors->get();
                $this->ajaxResponse($aResponse, true, false, true);
            }
            break;
            case 'saveorder': # сохранение порядка изображений
            {
                $img = $this->input->post('img', TYPE_ARRAY);
                if (!$oImages->saveOrder($img, false, true)) {
                    $this->errors->impossible();
                }
            }
            break;
            case 'delete': # удаление изображений
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->impossible();
                    break;
                }
                if ($nImageID) {
                    $bSuccess = $oImages->deleteImage($nImageID);
                    if ($bSuccess) {
                        # фото удалено, отправляем email-уведомление
                        $aDataEmail = $this->model->itemData2Email($nItemID);
                        if ($aDataEmail !== false && ! User::isCurrent(intval($aDataEmail['user_id']))) {
                            bff::sendMailTemplate(array(
                                    'name'       => $aDataEmail['name'],
                                    'email'      => $aDataEmail['email'],
                                    'item_id'    => $aDataEmail['item_id'],
                                    'item_link'  => $aDataEmail['item_link'],
                                    'item_title' => $aDataEmail['item_title'],
                                ), 'bbs_item_photo_deleted', $aDataEmail['email']
                            );
                        }
                    }
                } else {
                    $oImages->deleteTmpFile($sFilename);
                }
            }
            break;
            case 'delete-all': # удаление всех изображений
            {
                if ($nItemID) {
                    $oImages->deleteAllImages(true);
                } else {
                    $sFilename = $this->input->post('filenames', TYPE_ARRAY_STR);
                    $oImages->deleteTmpFile($sFilename);
                }
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    public function comments_ajax()
    {
        if (!$this->haveAccessTo('items-comments')) {
            return $this->showAccessDenied();
        }

        $this->itemComments()->admAjax();
    }

    public function comments_mod()
    {
        if (!$this->haveAccessTo('items-comments')) {
            return $this->showAccessDenied();
        }

        return $this->itemComments()->admListingModerate(15);
    }

    public function claims()
    {
        if (!$this->haveAccessTo('claims-listing')) {
            return $this->showAccessDenied();
        }

        if (Request::isAJAX()) {
            switch ($this->input->get('act', TYPE_STR)) {
                case 'delete': # удаляем жалобу
                {
                    if (!$this->haveAccessTo('claims-edit')) {
                        $this->ajaxResponse(Errors::ACCESSDENIED);
                    }

                    $nClaimID = $this->input->post('claim_id', TYPE_UINT);
                    if ($nClaimID) {
                        $aData = $this->model->claimData($nClaimID, array('id', 'viewed'));
                        if (empty($aData)) {
                            $this->ajaxResponse(Errors::IMPOSSIBLE);
                        }

                        $aResponse = array('counter_update' => false);
                        $res = $this->model->claimDelete($nClaimID);
                        if ($res && !$aData['viewed']) {
                            $this->claimsCounterUpdate(-1);
                            $aResponse['counter_update'] = true;
                        }
                        $aResponse['res'] = $res;
                        $this->ajaxResponse($aResponse);
                    }
                }
                break;
                case 'viewed': # отмечаем жалобу как прочитанную
                {
                    if (!$this->haveAccessTo('claims-edit')) {
                        $this->ajaxResponse(Errors::ACCESSDENIED);
                    }

                    $nClaimID = $this->input->post('claim_id', TYPE_UINT);
                    if ($nClaimID) {
                        $res = $this->model->claimSave($nClaimID, array('viewed' => 1));
                        if ($res) {
                            $this->claimsCounterUpdate(-1);
                        }
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
            }
            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $aData = $this->input->getm(array(
                'item'    => TYPE_UINT,
                'page'    => TYPE_UINT,
                'perpage' => TYPE_UINT,
                'status'  => TYPE_UINT,
            )
        );

        $aFilter = array();
        if ($aData['item']) {
            $aFilter['item_id'] = $aData['item'];
        }
        switch ($aData['status']) {
            case 1:
            {
                /* все */
            }
            break;
            default:
            {
                $aFilter['viewed'] = 0;
            }
            break;
        }

        $nCount = $this->model->claimsListing($aFilter, true);

        $aPerpage = $this->preparePerpage($aData['perpage'], array(20, 40, 60));

        $sFilter = http_build_query($aData);
        unset($aData['page']);
        $oPgn = new Pagination($nCount, $aData['perpage'], $this->adminLink("claims&$sFilter&page=" . Pagination::PAGE_ID));
        $aData['pgn'] = $oPgn->view();

        $aData['claims'] = ($nCount > 0 ?
            $this->model->claimsListing($aFilter, false, $oPgn->getLimitOffset()) :
            array());
        foreach ($aData['claims'] as &$v) {
            $v['message'] = $this->getItemClaimText($v['reason'], $v['message']);
        }
        unset($v);

        $aData['perpage'] = $aPerpage;

        return $this->viewPHP($aData, 'admin.items.claims.listing');
    }

    public function ajax()
    {
        $aResponse = array();
        switch ($this->input->get('act', TYPE_STR)) {
            case 'item-info':
            {
                /**
                 * Краткая информация об ОБ (popup)
                 * @param integer 'id' ID объявления
                 */
                $nItemID = $this->input->get('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array(
                        'id',
                        'user_id',
                        'shop_id',
                        'cat_id',
                        'created',
                        'status',
                        'status_prev',
                        'status_changed',
                        'claims_cnt',
                        'blocked_num',
                        'blocked_reason',
                        'moderated',
                        'deleted',
                        'publicated',
                        'publicated_to',
                        'publicated_order'
                    )
                );
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData['user'] = Users::model()->userData($aData['user_id'], array(
                        'email',
                        'name',
                        'blocked',
                        'shop_id'
                    )
                );
                if ($aData['shop_id'] && $aData['shop_id'] == $aData['user']['shop_id'] && bff::shopsEnabled()) {
                    $aData['shop'] = Shops::model()->shopData($aData['shop_id'], array('id', 'link', 'title'));
                }
                $aData['cats_path'] = $this->model->catParentsData($aData['cat_id'], array('id', 'title'));
                echo $this->viewPHP($aData, 'admin.items.info');
                exit;
            }
            break;
            case 'item-form-cat':
            {
                /**
                 * Форма ОБ, дополнительные поля в зависимости от категории
                 * @param integer 'cat_id' ID категории
                 */
                if (!$this->haveAccessTo('items-edit')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nCategoryID = $this->input->post('cat_id', TYPE_UINT);
                $aResponse['id'] = $nCategoryID;

                do {
                    $aData = $this->itemFormByCategory($nCategoryID);
                    if (empty($aData)) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aResponse = array_merge($aData, $aResponse);
                } while (false);
            }
            break;
            case 'item-block':
            {
                /**
                 * Блокировка объявления (если уже заблокирован => изменение причины блокировки)
                 * @param string 'blocked_reason' причина блокировки
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $sBlockedReason = $this->input->postget('blocked_reason', TYPE_NOTAGS, array('len' => 1000));
                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array('status', 'status_prev', 'deleted', 'user_id'));
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $bBlocked = ($aData['status'] == self::STATUS_BLOCKED);

                $aUpdate = array(
                    'moderated'      => 1,
                    'blocked_reason' => $sBlockedReason,
                );

                if (!$bBlocked) {
                    $aUpdate[] = 'blocked_num = blocked_num + 1';
                    $aUpdate[] = 'status_prev = status';
                    $aUpdate['status'] = self::STATUS_BLOCKED;
                }

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if ($res && !$bBlocked) {
                    $bBlocked = true;

                    # отправляем email-уведомление о блокировке ОБ
                    do {
                        if ($aData['status'] == self::STATUS_NOTACTIVATED) {
                            break;
                        }
                        if (!empty($aData['deleted'])) {
                            break;
                        }
                        if (!$aData['user_id']) {
                            break;
                        }

                        $aDataEmail = $this->model->itemData2Email($nItemID);
                        if (empty($aDataEmail)) {
                            break;
                        }

                        bff::sendMailTemplate(array(
                                'name'           => $aDataEmail['name'],
                                'email'          => $aDataEmail['email'],
                                'item_id'        => $aDataEmail['item_id'],
                                'item_link'      => $aDataEmail['item_link'],
                                'item_title'     => $aDataEmail['item_title'],
                                'blocked_reason' => $sBlockedReason
                            ), 'bbs_item_blocked', $aDataEmail['email']
                        );
                    } while (false);
                }

                # обновляем счетчик "на модерации"
                $this->moderationCounterUpdate();

                $aResponse['blocked'] = $bBlocked;
                $aResponse['reason'] = $sBlockedReason;
            }
            break;
            case 'item-activate':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItemData = $this->model->itemData($nItemID, array('status', 'user_id'));
                if (empty($aItemData) || $aItemData['status'] != self::STATUS_NOTACTIVATED) {
                    $this->errors->impossible();
                    break;
                }
                $aUserData = Users::model()->userData($aItemData['user_id'], array('activated', 'blocked'));
                if (empty($aUserData)) {
                    $this->errors->impossible();
                    break;
                }
                if (!$aUserData['activated']) {
                    $this->errors->set('Невозможно активировать объявление для неактивированного пользователя');
                    break;
                }
                if ($aUserData['blocked']) {
                    $this->errors->set('Невозможно активировать объявление для заблокированного пользователя');
                    break;
                }

                $res = $this->model->itemSave($nItemID, array(
                        'activate_key'     => '', # чистим ключ активации
                        'publicated'       => $this->db->now(),
                        'publicated_order' => $this->db->now(),
                        'publicated_to'    => $this->getItemPublicationPeriod(),
                        'status_prev'      => self::STATUS_NOTACTIVATED,
                        'status'           => self::STATUS_PUBLICATED,
                        'moderated'        => 1,
                    )
                );
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                }
            }
            break;
            case 'item-approve':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItemData = $this->model->itemData($nItemID, array('status', 'publicated', 'publicated_to'));
                if (empty($aItemData) || $aItemData['status'] == self::STATUS_NOTACTIVATED) {
                    $this->errors->impossible();
                    break;
                }

                $aUpdate = array(
                    'moderated' => 1
                );

                if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    /**
                     * В случае если "Одобряем" заблокированное ОБ
                     * => значит оно после блокировки было отредактировано пользователем
                     * => следовательно если его период публикации еще не истек => "Публикуем",
                     *    в противном случае переводим в статус "Период публикации завершился"
                     */
                    $newStatus = self::STATUS_PUBLICATED_OUT;
                    $now = time();
                    $from = strtotime($aItemData['publicated']);
                    $to = strtotime($aItemData['publicated_to']);
                    if (!empty($from) && !empty($to) && $now >= $from && $now < $to) {
                        $newStatus = self::STATUS_PUBLICATED;
                    }
                    $aUpdate[] = 'status_prev = status';
                    $aUpdate['status'] = $newStatus;
                }

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                }
            }
            break;
            case 'items-approve':
            {
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }


                $ids = $this->input->post('i', TYPE_ARRAY_UINT);

                $items = $this->model->itemsDataByFilter(array('id' => $ids), array(
                        'id',
                        'status',
                        'publicated',
                        'publicated_to'
                    )
                );
                if (!$items || empty($items)) {
                    $this->errors->unknownRecord();
                    break;
                }

                $save = array();
                $blocked = array(self::STATUS_PUBLICATED=>array(), self::STATUS_PUBLICATED_OUT=>array());
                foreach ($items as $id => &$item) {
                    if ($item['status'] == self::STATUS_NOTACTIVATED) {
                        unset($items[$id]);
                    } elseif ($item['status'] == self::STATUS_BLOCKED) {
                        /**
                         * В случае если "Одобряем" заблокированное ОБ
                         * => значит оно после блокировки было отредактировано пользователем
                         * => следовательно если его период публикации еще не истек => "Публикуем",
                         *    в противном случае переводим в статус "Период публикации завершился"
                         */
                        $now = time();
                        $from = strtotime($item['publicated']);
                        $to = strtotime($item['publicated_to']);
                        if (!empty($from) && !empty($to) && $now >= $from && $now < $to) {
                            $blocked[self::STATUS_PUBLICATED][] = $id;
                        } else {
                            $blocked[self::STATUS_PUBLICATED_OUT][] = $id;
                        }
                    } else {
                        $save[] = $id;
                    }
                } unset($item);

                $aUpdate = array(
                    'moderated' => 1
                );

                $updated = 0;
                if (!empty($save)) {
                    $updated = (int)$this->model->itemsSave($save, $aUpdate);
                }

                if (!empty($blocked)) {
                    $aUpdate[] = 'status_prev = status';
                    foreach ($blocked as $newStatus => $items) {
                        if (empty($items)) continue;
                        $aUpdate['status'] = $newStatus;
                        $updated += (int)$this->model->itemsSave($items, $aUpdate);
                    }
                }

                # обновляем счетчик "на модерации"
                $this->moderationCounterUpdate();

                $aResponse = array(
                    'updated'    => $updated,
                    'success'    => true,
                );
            }
            break;
            case 'item-refresh':
            {
                /**
                 * Продление публикации ОБ
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItem = $this->model->itemData($nItemID, array(
                        'id',
                        'status',
                        'moderated',
                        'publicated',
                        'publicated_to'
                    )
                );
                if (empty($aItem)) {
                    $this->errors->unknownRecord();
                    break;
                }

                switch ($aItem['status']) {
                    case self::STATUS_NOTACTIVATED:
                    {
                        $this->errors->set('Невозможно продлить публикацию неактивированного объявления');
                    }
                    break;
                    case self::STATUS_BLOCKED:
                    {
                        $this->errors->set('Невозможно продлить публикацию, поскольку объявление ' .
                            ($aItem['moderated'] == 0 ? 'ожидает проверки' : 'отклонено')
                        );
                    }
                    break;
                    case self::STATUS_PUBLICATED:
                    {
                        # продлеваем от даты завершения срока публикации
                        $this->model->itemSave($nItemID, array(
                                'publicated_to' => $this->getItemRefreshPeriod($aItem['publicated_to'])
                            )
                        );
                    }
                    break;
                    case self::STATUS_PUBLICATED_OUT:
                    {
                        # продлеваем от текущего момента + публикуем
                        $aUpdate = array(
                            'publicated_to' => $this->getItemRefreshPeriod(),
                            'status_prev = status',
                            'status'        => self::STATUS_PUBLICATED,
                            'moderated'     => 1,
                        );

                        /**
                         * Если разница между датой снятия с публикации и текущей датой
                         * более 7 дней, тогда поднимаем объявление вверх.
                         * в противном случае: оставлем дату старта публикации(pulicated)
                         *   и дату порядка публикации(publicated_order) прежними
                         */
                        $toOld = strtotime($aItem['publicated_to']);
                        $bUpdatePublicatedOrder = ((BFF_NOW - $toOld) > 604800);
                        if ($bUpdatePublicatedOrder) {
                            $aUpdate['publicated'] = $this->db->now();
                            $aUpdate['publicated_order'] = $this->db->now();
                        }

                        $res = $this->model->itemSave($nItemID, $aUpdate);
                        if (empty($res)) {
                            $this->errors->impossible();
                        } else {
                            # обновляем счетчик "на модерации"
                            $this->moderationCounterUpdate();
                        }
                    }
                    break;
                    default:
                    {
                        $this->errors->set('Текущий статус объявления указан некорректно');
                    }
                    break;
                }
            }
            break;
            case 'item-unpublicate':
            {
                /**
                 * Снимаем ОБ с публикации
                 * @param integer 'id' ID объявления
                 */
                if (!$this->haveAccessTo('items-moderate')) {
                    $this->errors->accessDenied();
                    break;
                }

                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aItem = $this->model->itemData($nItemID, array(
                        'id',
                        'status',
                        'moderated',
                        'publicated',
                        'publicated_to'
                    )
                );
                if (empty($aItem) || $aItem['status'] != self::STATUS_PUBLICATED) {
                    $this->errors->impossible();
                    break;
                }

                $aUpdate = array(
                    'status_prev = status',
                    'status'        => self::STATUS_PUBLICATED_OUT,
                    'moderated'     => 1,
                    'publicated_to' => $this->db->now(),
                    # оставляем все текущие услуги активированными
                );

                $res = $this->model->itemSave($nItemID, $aUpdate);
                if (empty($res)) {
                    $this->errors->impossible();
                } else {
                    # обновляем счетчик "на модерации"
                    $this->moderationCounterUpdate();
                }
            }
            break;
            case 'item-user':
            {
                $sEmail = $this->input->post('q', TYPE_NOTAGS);
                $sEmail = $this->input->cleanSearchString($sEmail);
                $aFilter = array(
                    'blocked'   => 0,
                    'activated' => 1,
                );

                if (is_numeric($sEmail)) {
                    $aFilter['user_id'] = $sEmail;
                } else {
                    $aFilter[':email'] = array(
                        (Users::model()->userEmailCrypted() ? 'BFF_DECRYPT(email)' : 'email') . ' LIKE :email',
                        ':email' => $sEmail . '%'
                    );
                }

                if (static::publisher(static::PUBLISHER_SHOP)) {
                    $aFilter[':shop'] = 'shop_id > 0';
                }
                $aUsers = Users::model()->usersList($aFilter, array('user_id', 'email', 'shop_id'));
                $aResponse = array();
                foreach ($aUsers as $v) {
                    $aResponse[] = array($v['user_id'], $v['email'], $v['shop_id']);
                }

                $this->ajaxResponse($aResponse);
            }
            break;
            case 'import-info':
            {
                /**
                 * Подробная информация об импорте ОБ (popup)
                 * @param integer 'id' ID импорта
                 */
                $nImportID = $this->input->get('id', TYPE_UINT);
                if (!$nImportID) {
                    $this->errors->unknownRecord();
                    break;
                }

                $aData = $this->model->importData($nImportID);
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                }
                $aData['user'] = Users::model()->userData($aData['user_id'], array('email','blocked'));

                $settings = func::unserialize($aData['settings']);
                if (!$settings) {
                    $this->errors->set('Ошибка чтения настроек импорта');
                    break;
                }

                $aParents = $this->model->catParentsData($settings['catId']);
                if (!$aParents) {
                    $this->errors->unknownRecord();
                    break;
                }
                $catTitles = array();
                foreach ($aParents as $v) $catTitles[] = $v['title'];
                $settings['cat_title'] = join(' / ', $catTitles);

                $settings['user'] = Users::model()->userData($settings['userId'], array('user_id', 'email', 'name', 'blocked', 'shop_id'));
                if ($settings['shop'] > 0 && bff::shopsEnabled()) {
                    $settings['shop'] = Shops::model()->shopData($settings['shop'], array('id', 'link', 'title'));
                }
                $aData['settings'] = &$settings;

                $statusList = $this->itemsImport()->getStatusList();
                $aData['status_title'] = ( isset($statusList[$aData['status']]) ? $statusList[$aData['status']] : '?' );

                echo $this->viewPHP($aData, 'admin.items.import.info');
                exit;
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # категории

    public function categories_listing()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $aData = array();
        $sAct = $this->input->get('act', TYPE_STR);
        if (!empty($sAct)) {
            switch ($sAct) {
                case 'subs-list':
                {
                    $nCategoryID = $this->input->postget('category', TYPE_UINT);
                    if (!$nCategoryID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $aData['cats'] = $this->model->catsListing(array('pid' => $nCategoryID));
                    $aData['deep'] = self::CATS_MAXDEEP;

                    $this->ajaxResponse(array(
                            'list' => $this->viewPHP($aData, 'admin.categories.listing.ajax'),
                            'cnt'  => sizeof($aData['cats'])
                        )
                    );
                }
                break;
                case 'toggle':
                {

                    $nCategoryID = $this->input->get('rec', TYPE_UINT);
                    if ($this->model->catToggle($nCategoryID, 'enabled')) {
                        $this->ajaxResponseForm(array('reload' => true));
                    }
                }
                break;
                case 'rotate':
                {

                    if ($this->model->catsRotate()) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'delete':
                {

                    $nCategoryID = $this->input->post('rec', TYPE_UINT);
                    if (FORDEV) {
                        if ($this->model->catDeleteDev($nCategoryID)) {
                            $this->ajaxResponse(Errors::SUCCESS);
                        }
                    } elseif ($this->model->catDelete($nCategoryID)) {
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'dev-export':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    $sType = $this->input->getpost('type', TYPE_STR);
                    switch ($sType) {
                        case 'txt':
                        default:
                        {
                            $aData = $this->model->catsExport('txt');
                            header('Content-disposition: attachment; filename=categories_export.txt');
                            header('Content-type: text/plain');
                            foreach ($aData as &$v) {
                                echo str_repeat("\t", $v['numlevel'] - 1) . ($v['subs'] ? '-' : '+') . ' ' . $v['id'] . ' '. $v['title'] . "\n";
                            }
                            unset($v);
                        }
                        break;
                    }
                    exit;
                }
                break;
                case 'dev-treevalidate':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    set_time_limit(0);
                    ignore_user_abort(true);

                    return $this->model->treeCategories->validate(true);
                }
                break;
                case 'dev-delete-all':
                {
                    if (!FORDEV) {
                        return $this->showAccessDenied();
                    }

                    if ($this->model->catDeleteAll()) {
                        $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
                    }
                }
                break;
            }

            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $aFilter = array();
        $sCatState = $this->input->cookie(config::sys('cookie.prefix') . 'bbs_cats_state');
        $aCatExpandedID = (!empty($sCatState) ? explode('.', $sCatState) : array());
        $aCatExpandedID = array_map('intval', $aCatExpandedID);
        $aCatExpandedID[] = 1;
        $aFilter['pid'] = $aCatExpandedID;

        $aData['cats'] = $this->model->catsListing($aFilter);
        $aData['deep'] = self::CATS_MAXDEEP;
        $aData['cats'] = $this->viewPHP($aData, 'admin.categories.listing.ajax');

        return $this->viewPHP($aData, 'admin.categories.listing');
    }

    public function categories_packetActions()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        $aData = array();
        if (Request::isAJAX())
        {
            $updated = 0;

            do {

                $actions = $this->input->post('actions', TYPE_ARRAY_BOOL);
                $actions = $this->input->clean_array($actions, array(
                    'currency_default' => TYPE_BOOL,
                    'photos_max'       => TYPE_BOOL,
                    'list_type'        => TYPE_BOOL,
                ));
                if ( ! array_sum($actions)) {
                    $this->errors->set('Отметьте как минимум одну из доступных настроек');
                    break;
                }
                $catsFields = array('id');

                # валюта по-умолчанию
                if ($actions['currency_default']) {
                    $currencyID = $this->input->post('currency_default', TYPE_UINT);
                    if ( ! $currencyID) {
                        $this->errors->set('Валюта по-умолчанию указана некорректно');
                        break;
                    }
                    $catsFields[] = 'price_sett';
                }

                # максимально доступное кол-во фотографий
                if ($actions['photos_max']) {
                    $photosMax = $this->input->post('photos_max', TYPE_UINT);
                    if ($photosMax < self::CATS_PHOTOS_MIN) {
                        $photosMax = self::CATS_PHOTOS_MIN;
                    }
                    if ($photosMax > self::CATS_PHOTOS_MAX) {
                        $photosMax = self::CATS_PHOTOS_MAX;
                    }
                    $catsFields[] = 'photos';
                }

                # вид списка по-умолчанию
                if ($actions['list_type']) {
                    $nListType = $this->input->post('list_type', TYPE_UINT);
                    if ( ! in_array($nListType, array(static::LIST_TYPE_LIST, static::LIST_TYPE_GALLERY, static::LIST_TYPE_MAP))) {
                        $actions['list_type'] = false;
                    } else {
                        $catsFields[] = 'list_type';
                        $catsFields[] = 'addr';
                    }
                }

                $data = $this->model->catsDataByFilter(array(), $catsFields);
                if (empty($data)) {
                    $this->errors->set('Неудалось найти категории');
                    break;
                }
                foreach ($data as &$v)
                {
                    if ($actions['currency_default']) {
                        if (!isset($v['price_sett']) || !isset($v['price_sett']['curr'])) {
                            continue;
                        }
                        $v['price_sett']['curr'] = $currencyID;
                    }
                    if ($actions['photos_max']) {
                        $v['photos'] = $photosMax;
                    }
                    if ($actions['list_type']) {
                        if ($nListType != static::LIST_TYPE_MAP ||
                            ($nListType == static::LIST_TYPE_MAP && $v['addr'])) {
                            $v['list_type'] = $nListType;
                        }
                    }
                    $res = $this->model->catSave($v['id'], $v);
                    if ( ! empty($res)) $updated++;
                } unset($v);
            } while(false);

            $this->ajaxResponseForm(array('updated'=>$updated));
        }

        return $this->viewPHP($aData, 'admin.categories.packetActions');
    }

    public function categories_add()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $aData = $this->validateCategoryData(0);

        if (Request::isPOST()) {

            if ($this->errors->no()) {
                $nCategoryID = $this->model->catSave(0, $aData);
                if ($nCategoryID) {
                    # ...
                }
                $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
            }
            $aData = $_POST;
        }

        $aData['id'] = 0;
        $aData['pid_options'] = $this->model->catsOptions('adm-category-form-add', $aData['pid']);

        return $this->viewPHP($aData, 'admin.categories.form');
    }

    public function categories_edit()
    {
        if (!$this->haveAccessTo('categories')) {
            return $this->showAccessDenied();
        }

        $bAllowEditParent = true;
        $nCategoryID = $this->input->getpost('id', TYPE_UINT);
        if (!$nCategoryID) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
        }

        $aData = $this->model->catData($nCategoryID, '*', true);
        if (!$aData) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, 'categories_listing');
        }

        if (Request::isPOST()) {

            $bCopySettingsToSubs = ($this->input->post('copy_to_subs', TYPE_BOOL) && FORDEV);
            $aDataSave = $this->validateCategoryData($nCategoryID);

            if ($this->errors->no()) {
                # смена parent-категории
                if ($bAllowEditParent && !$bCopySettingsToSubs && $aDataSave['pid'] != $aData['pid']) {
                    $this->model->catChangeParent($nCategoryID, $aDataSave['pid']);
                    # очищаем состояние списка категорий из-за смены порядка вложенности
                    Request::deleteCOOKIE(config::sys('cookie.prefix') . 'bbs_cats_state', $this->security->getAdminPath());
                }
                $res = $this->model->catSave($nCategoryID, $aDataSave);
                if (!empty($res)) {
                    # если keyword был изменен и есть вложенные подкатегории:
                    # > перестраиваем полный путь подкатегорий (и items::link)
                    if ($aData['keyword_edit'] != $aDataSave['keyword_edit'] && $aData['node'] > 1) {
                        $this->model->catSubcatsRebuildKeyword($nCategoryID, $aData['keyword_edit']);
                    }
                    # сбрасываем кеш дин. свойств категории
                    $this->dpSettingsChanged($nCategoryID, 0, 'cat-edit');
                }

                if ($this->model->catIsMain($nCategoryID, $aDataSave['pid'])) {
                    $aUpdate = array();
                    $oIcon = self::categoryIcon($nCategoryID);
                    foreach ($oIcon->getVariants() as $iconField => $v) {
                        $oIcon->setVariant($iconField);
                        $aIconData = $oIcon->uploadFILES($iconField, true, false);
                        if (!empty($aIconData)) {
                            $aUpdate[$iconField] = $aIconData['filename'];
                        } else {
                            if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                if ($oIcon->delete(false)) {
                                    $aUpdate[$iconField] = '';
                                }
                            }
                        }
                    }

                    if (!empty($aUpdate)) {
                        $this->model->catSave($nCategoryID, $aUpdate);
                    }
                }
                # копируем настройки во все подкатегории
                if ($bCopySettingsToSubs) {
                    $this->model->catDataCopyToSubs($nCategoryID);
                    $this->adminRedirect(Errors::SUCCESS, bff::$event . '&id=' . $nCategoryID);
                }
                $this->adminRedirect(Errors::SUCCESS, 'categories_listing');
            }
            $aData = $_POST;
        } else {
            $this->validateCategoryPriceSettings($aData['price_sett']);
        }

        $aData['pid_editable'] = $bAllowEditParent;
        if ($bAllowEditParent) {
            $aData['pid_options'] = $this->model->catsOptions('adm-category-form-edit', $aData['pid'], false, array(
                    'id'       => $nCategoryID,
                    'numleft'  => $aData['numleft'],
                    'numright' => $aData['numright'],
                )
            );
        } else {
            $aData['pid_options'] = $this->model->catParentsData($nCategoryID, array('id', 'title'), false, false);
        }

        return $this->viewPHP($aData, 'admin.categories.form');
    }

    # -------------------------------------------------------------------------------------------------------------------------------
    # типы категории

    public function types_listing($nCategoryID)
    {
        if (!$this->haveAccessTo('types')) {
            return '';
        }
        $aData['cat_id'] = $nCategoryID;
        $aData['cats'] = $this->model->catParentsData($nCategoryID, array('id', 'title'));
        $aData['types'] = $this->model->cattypesListing(array($this->db->prepareIN('T.cat_id', array_keys($aData['cats']))));
        $aData['list'] = $this->viewPHP($aData, 'admin.types.listing.ajax');
        if (Request::isAJAX()) {
            return $aData['list'];
        }

        return $this->viewPHP($aData, 'admin.types.listing');
    }

    public function types()
    {
        $aResponse = array();
        do {
            if (!$this->haveAccessTo('types')) {
                $this->errors->accessDenied();
                break;
            }

            $nCategoryID = $this->input->getpost('cat_id', TYPE_UINT);
            if (!$nCategoryID) {
                $this->errors->impossible();
                break;
            }

            switch ($this->input->postget('act', TYPE_STR)) {
                case 'toggle':
                {

                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if (!$this->model->cattypeToggle($nTypeID, 'enabled')) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'rotate':
                {

                    if (!$this->model->cattypesRotate($nCategoryID)) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'form':
                {
                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if ($nTypeID) {
                        $aData = $this->model->cattypeData($nTypeID, '*', true);
                    } else {
                        $this->validateCategoryTypeData($aData, $nCategoryID, 0);
                        $aData['id'] = 0;
                    }

                    $aData['form'] = $this->viewPHP($aData, 'admin.types.form');
                    $aResponse = $aData;
                }
                break;
                case 'delete':
                {

                    $nTypeID = $this->input->get('type_id', TYPE_UINT);
                    if (!$this->model->cattypeDelete($nTypeID)) {
                        $this->errors->impossible();
                    }
                }
                break;
                case 'add':
                {

                    $this->validateCategoryTypeData($aData, $nCategoryID, 0);
                    if ($this->errors->no()) {
                        $this->model->cattypeSave(0, $nCategoryID, $aData);
                        $aResponse['list'] = $this->types_listing($nCategoryID);
                    }
                }
                break;
                case 'edit':
                {

                    $nTypeID = $this->input->post('type_id', TYPE_UINT);
                    if (!$nTypeID) {
                        $this->errors->impossible();
                        break;
                    }
                    $this->validateCategoryTypeData($aData, $nCategoryID, $nTypeID);
                    if ($this->errors->no()) {
                        $this->model->cattypeSave($nTypeID, $nCategoryID, $aData);
                        $aResponse['list'] = $this->types_listing($nCategoryID);
                    }
                }
                break;
                default:
                    $this->errors->impossible();
            }
        } while (false);

        $this->ajaxResponseForm($aResponse);
    }

    public function settings()
    {
        if (!$this->haveAccessTo('settings')) {
            return $this->showAccessDenied();
        }

        $sCurrentTab = $this->input->postget('tab');
        if (empty($sCurrentTab)) {
            $sCurrentTab = 'general';
        }

        $aLang = array(
            'form_add'  => TYPE_STR,
            'form_edit' => TYPE_STR,
        );

        $aCats = $this->model->catsListing(array(
            'numlevel' => 1,
            'enabled'  => 1,
            'pid'      => 1,
        ));
        $aCatsLimit = array();
        foreach ($aCats as $v) {
            $aCatsLimit[ 's'.$v['id'] ] = $v['title'];
        }


        if (Request::isPOST() && $this->input->post('save', TYPE_BOOL)) {

            $aData = $this->input->postm(array(
                    'item_publication_period'            => TYPE_UINT,
                    'item_refresh_period'                => TYPE_UINT,
                    'item_share_code'                    => TYPE_STR,
                    'item_unpublicated_soon'             => TYPE_ARRAY_UINT,
                    'item_unpublicated_soon_messages'    => TYPE_UINT,
                    'categories_filter_level'            => TYPE_UINT,
                    'items_import'                       => TYPE_UINT,
                    'items_contacts'                     => TYPE_UINT,
                    'items_limits_user'                  => TYPE_UINT,
                    'items_limits_user_common'           => TYPE_UINT,
                    'items_limits_user_category'         => TYPE_ARRAY_UINT,
                    'items_limits_user_category_default' => TYPE_UINT,
                    'items_limits_shop'                  => TYPE_UINT,
                    'items_limits_shop_common'           => TYPE_UINT,
                    'items_limits_shop_category'         => TYPE_ARRAY_UINT,
                    'items_limits_shop_category_default' => TYPE_UINT,
                    'items_spam_duplicates'              => TYPE_BOOL,
                    'items_spam_minuswords'              => TYPE_ARRAY_STR
                )
            );

            # срок публикации
            if (!$aData['item_publication_period']) {
                $aData['item_publication_period'] = 30;
            } else {
                if ($aData['item_publication_period'] > 1000) {
                    $aData['item_publication_period'] = 1000;
                }
            }

            # срок продления
            if (!$aData['item_refresh_period']) {
                $aData['item_refresh_period'] = 30;
            } else {
                if ($aData['item_refresh_period'] > 1000) {
                    $aData['item_refresh_period'] = 1000;
                }
            }

            # водяной знак
            $this->itemImages()->watermarkSave('images_watermark',
                $this->input->post('images_watermark_delete', TYPE_BOOL),
                $this->input->post('images_watermark_pos_x', TYPE_NOTAGS),
                $this->input->post('images_watermark_pos_y', TYPE_NOTAGS)
            );

            # оповещение о завершении публикации
            $aData['item_unpublicated_soon'] = serialize($aData['item_unpublicated_soon']);
            if (!$aData['item_unpublicated_soon_messages']) {
                 $aData['item_unpublicated_soon_messages'] = 30;
            }

            # уровень подкатегории в фильтре
            if ($aData['categories_filter_level'] < 2) {
                $aData['categories_filter_level'] = 2;
            }

            # Лимитирование объявлений
            foreach ($aData['items_limits_user_category'] as $k => $v) {
                if ( ! array_key_exists('s'.$k, $aCatsLimit)) {
                    unset($aData['items_limits_user_category'][$k]);
                }
            }
            $aData['items_limits_user_category'] = serialize($aData['items_limits_user_category']);

            foreach ($aData['items_limits_shop_category'] as $k => $v) {
                if ( ! array_key_exists('s'.$k, $aCatsLimit)) {
                    unset($aData['items_limits_shop_category'][$k]);
                }
            }
            $aData['items_limits_shop_category'] = serialize($aData['items_limits_shop_category']);

            foreach ($aData['items_spam_minuswords'] as &$v) {
                $v = preg_replace('/[^[:alpha:]]+/iu', ',', $v);
                $v = explode(',', $v);
                foreach ($v as $kk => & $vv) {
                    if (empty($vv) || mb_strlen($vv) < 2) {
                        unset($v[$kk]);
                        continue;
                    }
                    $vv = mb_strtolower($vv);
                } unset($vv);
                $v = array_unique($v);
            } unset($v);
            $aData['items_spam_minuswords'] = serialize($aData['items_spam_minuswords']);

            $this->input->postm_lang($aLang, $aData);
            $this->db->langFieldsModify($aData, $aLang, $aData);

            $this->configSave($aData);

            $this->adminRedirect(Errors::SUCCESS, 'settings&tab=' . $sCurrentTab);
        }

        $aData = $this->configLoad();
        foreach ($this->locale->getLanguages() as $lng) {
            foreach ($aLang as $k => $v) {
                if (!isset($aData[$k . '_' . $lng])) {
                    $aData[$k . '_' . $lng] = '';
                }
            }
        }

        $aData['tab'] = $sCurrentTab;
        $aData['tabs'] = array(
            'general' => array('t' => 'Общие настройки'),
            'limits'  => array('t' => 'Лимиты'),
            'spam'    => array('t' => 'Спам фильтр'),
            'images'  => array('t' => 'Изображения'),
            //'instructions' => array('t' => 'Инструкции'),
            'share'   => array('t' => 'Поделиться'),
        );

        $aData['images_watermark'] = $this->itemImages()->watermarkSettings();
        $aData['images_watermark']['exists'] = (!empty($aData['images_watermark']['file']['path']) &&
            file_exists($aData['images_watermark']['file']['path']));

        $aData['item_unpublicated_soon_days'] = $this->getUnpublicatedDays();
        $aData['item_unpublicated_soon'] = func::unserialize($aData['item_unpublicated_soon']);

        if (!isset($aData['items_import'])) {
            $aData['items_import'] = BBS::IMPORT_ACCESS_ADMIN;
        }
        $aData['import_active_shops'] = ( bff::shopsEnabled() ?
            Shops::model()->shopsActiveCounter(array('import' => 1)) : 0 );

        if (!isset($aData['categories_filter_level'])) {
            $aData['categories_filter_level'] = 3;
        }

        if (empty($aData['items_contacts'])) {
            $aData['items_contacts'] = 1;
        }
        if (!isset($aData['items_limits_user'])) {
            $aData['items_limits_user'] = BBS::LIMITS_NONE;
        }
        if (!isset($aData['items_limits_shop'])) {
            $aData['items_limits_shop'] = BBS::LIMITS_NONE;
        }

        $aData['aCatsLimit'] = $aCatsLimit;

        $aCats = func::array_transparent($aCats, 'id', true);
        $aData['items_limits_user_category'] = (isset($aData['items_limits_user_category']) ? func::unserialize($aData['items_limits_user_category']) : array());
        if ( ! empty($aData['items_limits_user_category'])) {
            uksort($aData['items_limits_user_category'], function ($a, $b) use ($aCats) {
                return $aCats[$a]['numleft'] > $aCats[$b]['numleft'];
            });
        }
        $aData['items_limits_shop_category'] = (isset($aData['items_limits_shop_category']) ? func::unserialize($aData['items_limits_shop_category']) : array());
        if ( ! empty($aData['items_limits_shop_category'])) {
            uksort($aData['items_limits_shop_category'], function ($a, $b) use ($aCats) {
                return $aCats[$a]['numleft'] > $aCats[$b]['numleft'];
            });
        }

        $aData['items_spam_minuswords'] = (isset($aData['items_spam_minuswords']) ? func::unserialize($aData['items_spam_minuswords']) : array());
        if ( ! empty($aData['items_spam_minuswords'])) {
            foreach ($aData['items_spam_minuswords'] as & $v) {
                if (is_array($v)) {
                    $v = join(', ', $v);
                }
            } unset($v);
        }
        return $this->viewPHP($aData, 'admin.settings');
    }

    public function import()
    {
        $access = array(
            'import' => $this->haveAccessTo('items-import'),
            'export' => $this->haveAccessTo('items-export'),
        );
        if (!$access['import'] && !$access['export']) {
            return $this->showAccessDenied();
        }

        $aSettings = array(
            'catId'   => $this->input->get('catId', TYPE_UINT),
            'state'   => $this->input->get('state', TYPE_UINT),
            'langKey' => $this->input->get('langKey', TYPE_NOTAGS),
        );

        $import = $this->itemsImport();
        switch ($this->input->get('act')) {
            case 'export':
                $countOnly = $this->input->get('count', TYPE_BOOL);
                $import->export($aSettings, $countOnly);
                break;
            case 'import-template':
                $import->importTemplate($aSettings);
                break;
            case 'import-cancel':
                $importID = $this->input->get('id', TYPE_UINT);
                $import->importCancel($importID);
                $this->ajaxResponseForm();
                break;
            default:
                if (Request::isPOST() && !empty($_FILES))
                {
                    $aResponse = array();
                    $aSettings = array(
                        # категория
                        'catId'  => $this->input->post('cat_id', TYPE_UINT),
                        # пользователь (владелец импортируемых объявлений)
                        'userId' => $this->input->post('user_id', TYPE_UINT),
                        # закреплять за магазином
                        'shop'   => $this->input->post('shop', TYPE_UINT),
                        # итоговый статус объявлений
                        'state'  => $this->input->post('state', TYPE_UINT),
                    );
                    if ( ! $aSettings['catId']) {
                        $this->errors->set('Укажите категорию');
                    }
                    if ( ! $aSettings['userId']) {
                        $this->errors->set('Укажите пользователя');
                    }

                    if ($this->errors->no()) {
                        $aResponse['id'] = $import->importStart('file', $aSettings);
                    }
                    $this->iframeResponseForm($aResponse);
                }

                $f = array();
                $this->input->postgetm(array(
                        'page' => TYPE_UINT,
                    ), $f
                );

                $aData['f'] = $f;

                $tabCurrent = 'import';
                $tab = $this->input->get('tab', TYPE_NOTAGS);
                if (!empty($tab) && !empty($access[$tab])) {
                    $tabCurrent = $tab;
                }

                $tab_list = $this->input->postget('tab_list', TYPE_NOTAGS);
                if (empty($tab_list)) $tab_list = 'admin';
                $is_admin = ($tab_list == 'admin');

                $sqlFilter = array();
                $sqlFields = array();
                if (Request::isAJAX()) {
                    $uid = $this->input->post('uid', TYPE_UINT);
                    $uemail = $this->input->post('uemail', TYPE_NOTAGS);

                    if (!empty($uid)) {
                        $sqlFilter['user_id'] = $uid;
                    } elseif (is_numeric($uemail)) {
                        $sqlFilter['user_id'] = $uemail;
                    }
                }

                $sqlFilter['is_admin'] = ($is_admin ? 1 : 0);
                $nCount = $this->model->importListing($sqlFields, $sqlFilter, false, false, true);
                $oPgn = new Pagination($nCount, 15, '#', 'jBbsImportsList.page('.Pagination::PAGE_ID.'); return false;');
                $aData['pgn'] = $oPgn->view(array('arrows'=>false));
                $aData['list'] = $this->model->importListing($sqlFields, $sqlFilter, $oPgn->getLimitOffset(), 'created DESC');
                if (!empty($aData['list'])) {
                    foreach ($aData['list'] as &$v) {
                        $v['comment_text'] = '';
                        $comment = func::unserialize($v['status_comment']);
                        if ($comment) {
                            if ($v['status'] == BBSItemsImport::STATUS_FINISHED) {
                                $details = array();
                                if ($v['items_ignored'] > 0) {
                                    $details[] = _t('bbs.import', 'пропущено: [count]', array('count' => '<strong>'.$v['items_ignored'].'</strong>'));
                                }
                                if (!empty($comment['success'])) {
                                    $details[] = _t('bbs.import', 'добавлено: [count]', array('count' => '<strong>'.$comment['success'].'</strong>'));
                                }
                                if (!empty($comment['updated'])) {
                                    $details[] = _t('bbs.import', 'обновлено: [count]', array('count' => '<strong>'.$comment['updated'].'</strong>'));
                                }
                                if (!empty($details)) {
                                    $v['comment_text'] = implode(', ', $details);
                                }
                            } elseif (isset($comment['message'])) {
                                $v['comment_text'] = $comment['message'];
                            }
                        }
                        $file = func::unserialize($v['filename']);
                        $v['filename'] = $import->getImportPath(true, $file['filename']);
                    } unset($v);
                }

                $aData['list'] = $this->viewPHP($aData, 'admin.items.imports.ajax');

                if (Request::isAJAX()) {
                    $this->ajaxResponse(array(
                            'list'   => $aData['list'],
                            'pgn'    => $aData['pgn'],
                            'filter' => $f,
                        )
                    );
                }

                $aData['tab_form'] = $tabCurrent;
                $aData['tab_list'] = $tab_list;
                $aData['tabs'] = array();
                if ($access['import']) {
                    $aData['tabs']['import'] = array('t' => 'Импорт');
                }
                if ($access['export']) {
                    $aData['tabs']['export'] = array('t' => 'Экспорт');
                }

                $aData['cats'] = $this->model->catsOptionsByLevel(array(), array('empty' => 'Выбрать'));

                return $this->viewPHP($aData, 'admin.items.import');
                break;
        }
    }

    /**
     * Обработка данных категории
     * @param integer $nCategoryID ID категории
     * @return array $aData данные
     */
    protected function validateCategoryData($nCategoryID = 0)
    {
        $aData['pid'] = $this->input->postget('pid', TYPE_UINT);
        $aParams = array(
            'price'          => TYPE_BOOL,
            'price_sett'     => TYPE_ARRAY,
            'addr'           => TYPE_BOOL,
            'addr_metro'     => TYPE_BOOL,
            'photos'         => TYPE_UINT,
            'seek'           => TYPE_BOOL,
            'list_type'      => TYPE_UINT,
            'owner_business' => TYPE_BOOL,
            'owner_search'   => (Request::isPOST() ? TYPE_ARRAY_UINT : TYPE_UINT),
            'keyword_edit'   => TYPE_NOTAGS,
            'mtemplate'      => TYPE_BOOL, # Использовать общий шаблон SEO
        );
        $this->input->postm($aParams, $aData);
        $this->input->postm_lang($this->model->langCategories, $aData);

        $this->validateCategoryPriceSettings($aData['price_sett']);

        if (Request::isPOST()) {
            do {
                # основная категория обязательна
                if (!$aData['pid']) {
                    $this->errors->set('Укажите основную категорию');
                    break;
                } else {
                    $parent = $this->model->catData($aData['pid'], array('seek', 'addr', 'addr_metro'));
                    if (empty($parent)) {
                        $this->errors->set('Основная категория указана некорректно');
                        break;
                    } else {
                        # наследуем настройки из основной категории:
                        # тип размещения "ищу"
                        if (!$aData['seek'] && $parent['seek']) {
                            $aData['seek'] = 1;
                        }
                        # адрес
                        if (!$aData['addr'] && $parent['addr']) {
                            $aData['addr'] = $parent['addr'];
                        }
                        # метро
                        if (!$aData['addr_metro'] && $parent['addr_metro']) {
                            $aData['addr_metro'] = $parent['addr_metro'];
                        }
                    }
                }
                # название обязательно
                if (empty($aData['title'][LNG])) {
                    $this->errors->set('Укажите название');
                    break;
                }
                foreach ($aData['title'] as $k => $v) {
                    $aData['title'][$k] = str_replace(array('"'), '', $v);
                }

                # лимит фотографий
                if ($aData['photos'] > self::CATS_PHOTOS_MAX) {
                    $aData['photos'] = self::CATS_PHOTOS_MAX;
                } else {
                    if ($aData['photos'] < self::CATS_PHOTOS_MIN) {
                        $aData['photos'] = self::CATS_PHOTOS_MIN;
                    }
                }

                # keyword
                $sKeyword = $aData['keyword_edit'];
                if (empty($sKeyword) && !empty($aData['title'][LNG])) {
                    $sKeyword = mb_strtolower(func::translit($aData['title'][LNG]));
                }
                $sKeyword = preg_replace('/[^a-z0-9_\-]/', '', mb_strtolower($sKeyword));
                if (empty($sKeyword)) {
                    $this->errors->set('Keyword указан некорректно');
                    break;
                }
                # проверяем уникальность keyword'a в пределах основной категории
                $res = $this->model->catDataByFilter(array(
                        'pid'          => $aData['pid'],
                        'keyword_edit' => $sKeyword,
                        array('C.id!=:id', ':id' => $nCategoryID)
                    ), array('id')
                );
                if (!empty($res)) {
                    $this->errors->set('Указанный keyword уже используется, укажите другой');
                    break;
                }
                $aData['keyword_edit'] = $sKeyword;

                # строим полный путь "parent-keyword / ... / keyword"
                $aKeywordsPath = array();
                if ($aData['pid'] > self::CATS_ROOTID) {
                    $aParentCatData = $this->model->catData($aData['pid'], array('keyword'));
                    if (empty($aParentCatData)) {
                        $this->errors->set('Основная категория указана некорректно');
                        break;
                    } else {
                        $aKeywordsPath = explode('/', $aParentCatData['keyword']);
                    }
                }
                $aKeywordsPath[] = $sKeyword;
                $aKeywordsPath = join('/', $aKeywordsPath);
                $aData['keyword'] = $aKeywordsPath;

                $aData['owner_search'] = array_sum($aData['owner_search']);

                # тип списка по-умолчанию
                if ( ! in_array($aData['list_type'], array(static::LIST_TYPE_LIST, static::LIST_TYPE_GALLERY, static::LIST_TYPE_MAP))) {
                    $aData['list_type'] = 0;
                } else if ($aData['list_type'] == static::LIST_TYPE_MAP && ! $aData['addr']) {
                    $aData['list_type'] = 0;
                }

            } while (false);
        } else {
            if (!$nCategoryID) {
                $aData['mtemplate'] = 1;
            }
        }

        return $aData;
    }

    /**
     * Обработка данных категории: настройки цены
     * @param integer $nCategoryID ID категории
     * @return array $aData данные
     */
    protected function validateCategoryPriceSettings(&$aSettings)
    {
        $this->input->clean_array($aSettings, array(
                'title'     => TYPE_ARRAY_STR,
                'curr'      => TYPE_UINT,
                'ranges'    => TYPE_ARRAY,
                'ex'        => (Request::isPOST() ? TYPE_ARRAY_UINT : TYPE_UINT),
                'mod_title' => TYPE_ARRAY_STR,
            )
        );

        if (Request::isPOST()) {
            $ranges = & $aSettings['ranges'];
            if (!empty($ranges) && is_array($ranges)) {
                foreach ($ranges as $k => &$v) {
                    $v['from'] = floatval(trim(strip_tags($v['from'])));
                    $v['to'] = floatval(trim(strip_tags($v['to'])));

                    if (empty($v['from']) && empty($v['to'])) {
                        unset($ranges[$k]);
                        continue;
                    }
                }
            } else {
                $ranges = array();
            }
            $aSettings['ex'] = array_sum($aSettings['ex']);
        }
    }

    /**
     * Обработка данных типа категории
     * @param array $aData @ref данные
     * @param integer $nCategoryID ID категории
     * @param integer $nTypeID ID типа
     */
    protected function validateCategoryTypeData(&$aData, $nCategoryID, $nTypeID)
    {
        $this->input->postm_lang($this->model->langCategoriesTypes, $aData);

        if (Request::isPOST()) {
            if ($this->errors->no()) {
                # ...
            }
        }
    }

    # ------------------------------------------------------------------------------------------------------------------------------
    # Услуги / Пакеты услуг

    public function svc_services()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $svc = Svc::model();

        if (Request::isPOST()) {
            $aResponse = array();

            switch ($this->input->getpost('act')) {
                case 'update':
                {

                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $aData = $svc->svcData($nSvcID, array('id', 'type', 'keyword'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICE) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $this->svcValidateData($nSvcID, Svc::TYPE_SERVICE, $aDataSave);

                    if ($this->errors->no()) {
                        # загружаем иконки
                        $oIcon = self::svcIcon($nSvcID);
                        $oIcon->setAssignErrors(false);
                        foreach ($oIcon->getVariants() as $iconField => $v) {
                            $oIcon->setVariant($iconField);
                            $aIconData = $oIcon->uploadFILES($iconField, true, false);
                            if (!empty($aIconData)) {
                                $aDataSave[$iconField] = $aIconData['filename'];
                            } else {
                                if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                    if ($oIcon->delete(false)) {
                                        $aDataSave[$iconField] = '';
                                    }
                                }
                            }
                        }

                        # сохраняем
                        $svc->svcSave($nSvcID, $aDataSave);
                    }
                }
                break;
                case 'reorder': # сортировка услуг
                {

                    $aSvc = $this->input->post('svc', TYPE_ARRAY_UINT);
                    $svc->svcReorder($aSvc, Svc::TYPE_SERVICE);
                }
                break;
                default:
                {
                    $this->errors->impossible();
                }
                break;
            }

            $this->iframeResponseForm($aResponse);
        }

        $aData = array(
            'svc'  => $svc->svcListing(Svc::TYPE_SERVICE, $this->module_name, array(
                        (!self::PRESS_ON ? 'press' : '')
                    )
                ),
            'cats' => $this->model->catsOptions('adm-svc-prices-ex', 0, 'Выберите категорию'),
        );

        # Подготавливаем данные о региональной стоимости услуг для редактирования
        $aData['price_ex'] = $this->model->svcPriceExEdit();

        return $this->viewPHP($aData, 'admin.svc.services');
    }

    /**
     * Пакеты услуг
     */
    public function svc_packs()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $svc = Svc::model();

        if (Request::isPOST()) {
            $aResponse = array();

            switch ($this->input->getpost('act')) {
                case 'update': # сохранение
                {
                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aData = $svc->svcData($nSvcID, array('id', 'type'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICEPACK) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $this->svcValidateData($nSvcID, Svc::TYPE_SERVICEPACK, $aDataSave);

                    if ($this->errors->no()) {
                        # загружаем иконки
                        $oIcon = self::svcIcon($nSvcID);
                        $oIcon->setAssignErrors(false);
                        foreach ($oIcon->getVariants() as $iconField => $v) {
                            $oIcon->setVariant($iconField);
                            $aIconData = $oIcon->uploadFILES($iconField, true, false);
                            if (!empty($aIconData)) {
                                $aDataSave[$iconField] = $aIconData['filename'];
                            } else {
                                if ($this->input->post($iconField . '_del', TYPE_BOOL)) {
                                    if ($oIcon->delete(false)) {
                                        $aDataSave[$iconField] = '';
                                    }
                                }
                            }
                        }

                        # сохраняем информацию о пакете
                        $svc->svcSave($nSvcID, $aDataSave);
                    }
                }
                break;
                case 'reorder': # сортировка пакетов
                {

                    $aSvc = $this->input->post('svc', TYPE_ARRAY_UINT);
                    $svc->svcReorder($aSvc, Svc::TYPE_SERVICEPACK);
                }
                break;
                case 'del': # удаление пакета услуг
                {

                    $nSvcID = $this->input->post('id', TYPE_UINT);
                    if (!$nSvcID) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aData = $svc->svcData($nSvcID, array('id', 'type'));
                    if (empty($aData) || $aData['type'] != Svc::TYPE_SERVICEPACK) {
                        $this->errors->unknownRecord();
                        break;
                    }
                    $aResponse['redirect'] = $this->adminLink(bff::$event);
                    $bSuccess = $svc->svcDelete($nSvcID);
                    if (empty($bSuccess)) {
                        $this->errors->impossible();
                    }
                    $this->ajaxResponseForm($aResponse);
                }
                break;
                default:
                {
                    $this->errors->impossible();
                }
                break;
            }

            $this->iframeResponseForm($aResponse);
        }

        $aData = array(
            'packs' => $svc->svcListing(Svc::TYPE_SERVICEPACK, $this->module_name),
            'svc'   => $svc->svcListing(Svc::TYPE_SERVICE, $this->module_name),
            'curr'  => Site::currencyDefault(false),
        );

        return $this->viewPHP($aData, 'admin.svc.packs');
    }

    /**
     * Добавление Пакета услуг
     */
    public function svc_packs_create()
    {
        if (!$this->haveAccessTo('svc')) {
            return $this->showAccessDenied();
        }

        $aData = $this->input->postm(array(
                'title'   => TYPE_NOTAGS,
                'keyword' => TYPE_NOTAGS,
            )
        );

        $svc = Svc::model();

        if (Request::isPOST()) {

            if (empty($aData['title'])) {
                $this->errors->set('Название указано некорректно');
            }

            if (empty($aData['keyword'])) {
                $this->errors->set('Keyword указан некорректно');
            } else {
                if ($svc->svcKeywordExists($aData['keyword'], $this->module_name)) {
                    $this->errors->set('Указанный keyword уже используется');
                }
            }

            if ($this->errors->no()) {
                $aData['type'] = Svc::TYPE_SERVICEPACK;

                $this->svcValidateData(0, $aData['type'], $aDataSave);
                $aData = array_merge($aData, $aDataSave);
                $aData['module'] = $this->module_name;
                $aData['module_title'] = 'Объявления';
                $nSvcID = $svc->svcSave(0, $aData);
                $bSuccess = !empty($nSvcID);

                $this->adminRedirect(($bSuccess ? Errors::SUCCESS : Errors::IMPOSSIBLE), 'svc_packs');
            }
        }

        return $this->viewPHP($aData, 'admin.svc.packs.create');
    }

    /**
     * Проверка данных услуги / пакета услуг
     * @param integer $nSvcID ID услуги / пакета услуг
     * @param integer $nType тип Svc::TYPE_
     * @param array $aData @ref проверенные данные
     */
    protected function svcValidateData($nSvcID, $nType, &$aData)
    {
        $aParams = array(
            'price' => TYPE_PRICE,
        );

        if ($nType == Svc::TYPE_SERVICE) {
            $aSettings = array(
                'period'   => TYPE_UINT, # период действия услуги
                'color'    => TYPE_NOTAGS, # цвет
                'add_form' => TYPE_BOOL, # в форме добавления
                'on'       => TYPE_BOOL, # включена
            );

            $aData = $this->input->postm($aParams);
            $aData['settings'] = $this->input->postm($aSettings);
            $this->input->postm_lang($this->model->langSvcServices, $aData['settings']);
            $aData['title'] = $aData['settings']['title_view'][LNG];

            if ($aData['settings']['period'] < 1) {
                $aData['settings']['period'] = 1;
            }

            if (Request::isPOST()) {
                $priceEx = $this->input->post('price_ex', array(
                        TYPE_ARRAY_ARRAY,
                        'price'   => TYPE_PRICE,
                        'cats'    => TYPE_ARRAY_UINT,
                        'regions' => TYPE_ARRAY_UINT,
                    )
                );
                $this->model->svcPriceExSave($nSvcID, $priceEx);
            }
        } else {
            if ($nType == Svc::TYPE_SERVICEPACK) {
                $aData = $this->input->postm($aParams);
                $aSettings = array(
                    'color'    => TYPE_NOTAGS, # цвет
                    'add_form' => TYPE_BOOL, # в форме добавления
                    'on'       => TYPE_BOOL, # включен
                );
                $aSettings = $this->input->postm($aSettings);

                # услуги, входящие в пакет
                $aSvc = $this->input->post('svc', array(
                        TYPE_ARRAY_ARRAY,
                        'id'  => TYPE_UINT,
                        'cnt' => TYPE_UINT,
                    )
                );
                foreach ($aSvc as $k => $v) {
                    if (!$v['id'] ||
                        # исключаем услуги, у которых неуказано кол-во (кроме SERVICE_PRESS)
                        ($v['id'] != self::SERVICE_PRESS && !$v['cnt'])
                    ) {
                        unset($aSvc[$k]);
                    }
                }
                $aSettings['svc'] = $aSvc;

                # текстовые поля
                $this->input->postm_lang($this->model->langSvcPacks, $aSettings);

                if (!$nSvcID) {
                    $sTitle = $this->input->post('title', TYPE_STR);
                    foreach ($this->locale->getLanguages() as $lng) {
                        $aSettings['title_view'][$lng] = $sTitle;
                    }
                    $aData['title'] = $sTitle;
                } else {
                    $aData['title'] = $aSettings['title_view'][LNG];
                }
                $aData['settings'] = $aSettings;

                if (Request::isPOST()) {
                    #
                }
            }
        }
    }


}