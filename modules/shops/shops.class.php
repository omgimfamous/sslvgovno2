<?php

class Shops extends ShopsBase
{
    /**
     * Routing
     */
    public function route()
    {
        $res = bff::route(array(
                # просмотр магазина + страницы
                'shop/(.*)\-([\d]+)(.*)'           => 'shops/view/id=$2&tab=$3',
                # просмотр магазина + страницы (при изменении формирования URL)
                '(.*)/shop/(.*)\-([\d]+)(.*)'      => 'shops/view/id=$3&tab=$4',
                # открытие магазина, продвижение, ...
                'shop/(open|logo|promote|request)' => 'shops/$1/',
                # поиск магазинов
                'shops/(.*)'                       => 'shops/search/cat=$1',
                # поиск магазинов (при изменении формирования URL)
                '(.*)/shops/(.*)'                  => 'shops/search/cat=$2',
            ), true
        );

        if ($res['event'] === false || !method_exists($this, $res['event'])) {
            $this->errors->error404();
        }

        if (Request::isGET()) {
            bff::setActiveMenu('//shops');
        }

        return $this->$res['event']();
    }

    /**
     * Поиск и результаты поиска
     * @return mixed
     */
    public function search()
    {
        $pageSize = config::sys('shops.search.pagesize', 8);
        $f = $this->searchFormData();
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

        # seo данные
        $seoKey = '';
        $seoNoIndex = false;
        $seoData = array(
            'page'   => &$f['page'],
            'region' => Geo::regionTitle(($f_region ? $f_region : Geo::defaultCountry())),
        );

        # Данные о категории:
        $catID = 0;
        $catData = array();
        $catFields = array('id', 'numlevel', 'enabled');
        $catModel = (static::categoriesEnabled() ? $this->model : BBS::model());
        if (!Request::isAJAX()) {
            $catKey = $this->input->get('cat', TYPE_STR);
            $catKey = trim($catKey, ' /\\');
            if (!empty($catKey)) {
                $catData = $catModel->catDataByFilter(array('keyword' => $catKey), array_merge($catFields, array(
                            'pid',
                            'subs',
                            'keyword',
                            'numleft',
                            'numright',
                            'enabled',
                            'title',
                            'mtitle',
                            'mkeywords',
                            'mdescription',
                            'mtemplate',
                            'seotext',
                            'titleh1'
                        )
                    )
                );
                if (empty($catData) || !$catData['enabled']) {
                    $this->errors->error404();
                }
                bff::filter('shops-search-category', $catData);
                $catID = $f_c = $catData['id'];
                $catData['crumbs'] = $this->categoryCrumbs($catID, __FUNCTION__);

                # seo: Поиск в категории
                $seoKey = 'search-category';
                $metaCategories = array();
                foreach ($catData['crumbs'] as $k => &$v) {
                    if ($k) {
                        $metaCategories[] = $v['title'];
                    }
                }
                unset($v);
                $seoData['category'] = $catData['title'];
                $seoData['categories'] = join(', ', $metaCategories);
                $seoData['categories.reverse'] = join(', ', array_reverse($metaCategories, true));
                if (!static::categoriesEnabled()) {
                    $catData['mtemplate'] = 1;
                }
            } else {
                # seo: Поиск (все категории)
                $seoKey = 'search';
            }
        } else {
            $catID = $f_c;
            $catData = $catModel->catData($catID, $catFields);
            if (empty($catData) || !$catData['enabled']) {
                $catID = 0;
            }
        }
        if (!$catID) {
            $f_c = $f_ct = 0;
            $catKey = '';
            $catData = array('id' => 0);
            if (!Request::isAJAX()) {
                $catData['crumbs'] = $this->categoryCrumbs(0, __FUNCTION__);
            }
        }

        # Формируем запрос поиска:
        $sqlTablePrefix = 'S.';
        $sql = array(
            'status' => self::STATUS_ACTIVE,
        );
        if (static::premoderation()) {
            $sql[':mod'] = $sqlTablePrefix . 'moderated > 0';
        }
        if ($f_region) {
            $aRegion = Geo::regionData($f_region);
            switch ($aRegion['numlevel']) {
                case Geo::lvlCountry:  $sql['reg1_country'] = $f_region; break;
                case Geo::lvlRegion:   $sql['reg2_region']  = $f_region; break;
                case Geo::lvlCity:     $sql['reg3_city']    = $f_region; break;
            }
        }
        $seoResetCounter = sizeof($sql); # всю фильтрацию ниже скрываем от индексации
        if (strlen($f_q) > 1) {
            $sql[] = array(
                '(' . $sqlTablePrefix . 'title LIKE (:query) OR ' . $sqlTablePrefix . 'descr LIKE (:query))',
                ':query' => "%$f_q%"
            );
        }
        if ($f_lt == self::LIST_TYPE_MAP) {
            # на карту выводим только с корректно указанными координатами
            $sql[':addr'] = $sqlTablePrefix . 'addr_lat!=0';
            $seoResetCounter++;
        }

        # Выполняем поиск магазинов:
        $aData = array('items' => array(), 'pgn' => '');

        $nTotal = $this->model->shopsList($sql, $f_c, true);
        if ($nTotal > 0) {
            $aPgnLinkQuery = $f;
            if ($f['c']) {
                unset($aPgnLinkQuery['c']);
            }
            if ($f['region']) {
                unset($aPgnLinkQuery['region']);
            }
            if ($f['lt'] == self::LIST_TYPE_LIST) {
                unset($aPgnLinkQuery['lt']);
            }
            $oPgn = new Pagination($nTotal, $pageSize, array(
                'link'  => static::url('search', array('keyword' => $catKey)),
                'query' => $aPgnLinkQuery
            ));
            $aData['items'] = $this->model->shopsList($sql, $f_c, false, $oPgn->getLimitOffset());
            if (!empty($aData['items'])) {
                foreach ($aData['items'] as &$v) {
                    $v['logo'] = ShopsLogo::url($v['id'], $v['logo'], ShopsLogo::szList);
                    $v['link'] = static::urlDynamic($v['link']);
                    $v['phones'] = (!empty($v['phones']) ? func::unserialize($v['phones']) : array());
                    $v['social'] = (!empty($v['social']) ? func::unserialize($v['social']) : array());
                    $v['has_contacts'] = ($v['phones'] || $v['social'] || !empty($v['skype']) || !empty($v['icq']));
                    $v['ex'] = $v['id_ex'] . '-' . $v['id'];
                    unset($v['id_ex']);
                    if ($f_lt == self::LIST_TYPE_MAP) {
                        unset($v['region_id'], $v['region_title'], $v['addr_addr'],
                        $v['has_contacts'], $v['phones'], $v['site'], $v['skype'], $v['icq'], $v['social']);
                    }
                }
                unset($v);
            }
            $aData['pgn'] = $oPgn->view();
            $f['page'] = $oPgn->getCurrentPage();
        }

        $nNumStart = ($f_page <= 1 ? 1 : (($f_page - 1) * $pageSize) + 1);
        if (Request::isAJAX()) { # ajax ответ
            $this->ajaxResponseForm(array(
                    'list'  => $this->searchList(bff::device(), $f_lt, $aData['items'], $nNumStart),
                    'items' => &$aData['items'],
                    'pgn'   => $aData['pgn'],
                    'total' => $nTotal,
                )
            );
        }

        # seo
        $this->urlCorrection(static::url('search', array('keyword' => $catKey)));
        $this->seo()->robotsIndex(!(sizeof($sql) - $seoResetCounter) && !$seoNoIndex);
        $this->seo()->canonicalUrl(static::url('search', array('keyword' => $catKey), true),
            array('page' => $f['page'])
        );
        # подготавливаем хлебные крошки для подстановки макросов
        $catData['crumbs_macros'] = array();
        foreach ($catData['crumbs'] as &$v) { $catData['crumbs_macros'][] = &$v['breadcrumb']; } unset($v);
        $this->setMeta($seoKey, $seoData, $catData, array(
            'titleh1' => array('ignore' => array((!$f_region ? 'region' : ''),)),
            'crumbs_macros' => array('ignore' => array((!$f_region ? 'region' : ''),'category')),
        ));

        $aData['total'] = $nTotal;
        $aData['num_start'] = $nNumStart;
        $aData['cat'] =& $catData;
        $aData['f'] =& $f;

        return $this->viewPHP($aData, 'search');
    }

    public function searchForm()
    {
        $aData['f'] = $this->searchFormData();

        return $this->viewPHP($aData, 'search.form');
    }

    public function searchFormData(&$dataUpdate = false)
    {
        static $data;
        if (isset($data)) {
            if ($dataUpdate !== false) {
                $data = $dataUpdate;
            }

            return $data;
        }

        $aParams = array(
            'c'    => TYPE_UINT, # id категорий
            'q'    => TYPE_NOTAGS, # поисковая строка
            'qm'   => TYPE_NOTAGS, # поисковая строка
            'lt'   => TYPE_UINT, # тип списка (self::LIST_TYPE_)
            'cnt'  => TYPE_BOOL, # только кол-во
            'page' => TYPE_UINT, # страница
        );

        $data = $this->input->postgetm($aParams);

        # поисковая строка
        $device = bff::device();
        $data['q'] = $this->input->cleanSearchString(
            (in_array($device, array(bff::DEVICE_DESKTOP, bff::DEVICE_TABLET)) ? $data['q'] : $data['qm']), 80
        );
        # страница
        if (!$data['page']) {
            $data['page'] = 1;
        }
        # регион
        $data['region'] = Geo::filter('id'); # user

        return $data;
    }

    /**
     * Формирование результатов поиска (список магазинов)
     * @param string $deviceID тип устройства
     * @param integer $nListType тип списка (self::LIST_TYPE_)
     * @param array $aShops @ref данные о найденных магазинах
     * @param integer $nNumStart изначальный порядковый номер
     * @param array $aExtra доп. данные
     * @return mixed
     */
    public function searchList($deviceID, $nListType, array &$aShops, $nNumStart = 1)
    {
        static $prepared = false;
        if (!$prepared) {
            $prepared = true;
            foreach ($aShops as &$v) {
                $v['num'] = $nNumStart++; # порядковый номер (для карты)
            }
            unset($v);
        }

        if (empty($aShops)) {
            return $this->showInlineMessage(array(
                    '<br />',
                    _t('bbs', 'Магазинов по вашему запросу не найдено')
                )
            );
        }

        $aTemplates = array(
            self::LIST_TYPE_LIST => 'search.list.list',
            self::LIST_TYPE_MAP  => 'search.list.map',
        );
        $aData = array();
        $aData['items'] = &$aShops;
        $aData['device'] = $deviceID;
        return $this->viewPHP($aData, $aTemplates[$nListType]);
    }

    /**
     * Cтраница магазина
     */
    public function view()
    {
        $shopID = $this->input->get('id', TYPE_UINT);
        if (!$shopID) {
            $this->errors->error404();
        }

        $shop = $this->model->shopDataSidebar($shopID);
        if (empty($shop) || $shop['status'] == static::STATUS_REQUEST) {
            $this->errors->error404();
        }
        if ($shop['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->showInlineMessage(_t('shops', 'Магазин был временно деактивирован модератором'));
        }
        if ($shop['status'] == static::STATUS_BLOCKED) {
            return $this->showInlineMessage(_t('shops', 'Магазин был заблокирован модератором по причине:<br /><b>[reason]</b>',
                    array('reason' => $shop['blocked_reason'])
                )
            );
        }

        if ($userID = $shop['user_id']) {
            $user = Users::model()->userData($userID, array('login', 'activated', 'blocked', 'blocked_reason'));
            if (empty($user) || !$user['activated']) {
                return $this->showInlineMessage(_t('shops', 'Ошибка просмотра страницы магазина. Обратитесь к администратору'));
            }
            if ($user['blocked']) {
                return $this->showInlineMessage(_t('users', 'Аккаунт владельца магазина был заблокирован по причине:<br /><b>[reason]</b>',
                        array('reason' => $user['blocked_reason'])
                    )
                );
            }
            $shop['user'] = & $user;
        }

        # Подготовка данных
        $shopID = $shop['id'];
        $shop['skype'] = (!empty($shop['skype']) ? mb_substr($shop['skype'], 0, 2) . 'xxxxx' : '');
        $shop['icq'] = (!empty($shop['icq']) ? mb_substr($shop['icq'], 0, 2) . 'xxxxx' : '');
        $shop['has_contacts'] = ($shop['phones'] || !empty($shop['skype']) || !empty($shop['icq']) || !empty($shop['social']));
        $shop['addr_map'] = (!empty($shop['addr_addr']) && (floatval($shop['addr_lat']) || floatval($shop['addr_lon'])));
        $shop['descr'] = nl2br($shop['descr']);
        
        # Разделы
        $tab = trim($this->input->getpost('tab', TYPE_NOTAGS), ' /');
        $tabs = array(
            'items'   => array(
                't'   => _t('shops', 'Объявления магазина'),
                'm'   => 'BBS',
                'ev'  => 'shop_items',
                'url' => static::urlShop($shop['link']),
                'a'   => false
            ),
            'contact' => array(
                't'   => _t('shops', 'Написать сообщение'),
                'm'   => 'Shops',
                'ev'  => 'shop_contact',
                'url' => static::urlContact($shop['link']),
                'a'   => false
            ),
        );
        if (User::shopID() == $shopID || !$shop['user_id']) {
            unset($tabs['contact']);
        }
        if (!isset($tabs[$tab])) {
            $tab = 'items';
        }
        $tabs[$tab]['a'] = true;

        $data = array(
            'shop'        => &$shop,
            'social'      => static::socialLinksTypes(),
            'url_promote' => Shops::url('shop.promote', array('id' => $shopID, 'from' => 'view')),
            'has_owner'   => !empty($shop['user_id']),
            'is_owner'    => User::isCurrent($shop['user_id']),
        );

        if ($data['has_owner']) {
            $data += array(
                'content' => call_user_func(array(bff::module($tabs[$tab]['m']), $tabs[$tab]['ev']), $shopID, $shop),
                'tab'     => $tab,
                'tabs'    => &$tabs,
            );
        } else {
            # SEO: Страница магазина (без владельца)
            $this->urlCorrection($shop['link']);
            $this->seo()->canonicalUrl($shop['link_dynamic']);
            $this->setMeta('shop-view', array(
                    'title'       => $shop['title'],
                    'description' => tpl::truncate($shop['descr'], 150),
                    'region'      => ($shop['region_id'] ? $shop['region_title'] : ''),
                    'country'     => (!empty($shop['country']['title']) ? $shop['country']['title'] : ''),
                    'page'        => 1,
                ), $shop
            );
            $this->seo()->setSocialMetaOG($shop['share_title'], $shop['share_description'], $shop['logo'], $shop['link'], $shop['share_sitename']);
            $data['content'] = '';
        }
        if ($tab == 'items') {
            $data['share_code'] = config::get('shops_shop_share_code');
        }

        return $this->viewPHP($data, 'view');
    }

    /**
     * Форма связи с магазином
     * @param integer $shopID ID магазина
     * @param array $shopData данные магазина
     */
    public function shop_contact($shopID, $shopData)
    {
        if (User::isCurrent($shopData['user_id'])) {
            $this->redirect($shopData['link']);
        }

        # SEO:
        $this->urlCorrection(static::urlContact($shopData['link']));
        $this->seo()->robotsIndex(false);
        bff::setMeta(_t('shops', 'Отправить сообщение магазину [shop]', array('shop' => $shopData['title'])));

        if (Request::isPOST()) {
            Users::i()->writeFormSubmit(User::id(), $shopData['user_id'], 0, false, $shopID);
        }

        return $this->viewPHP($shopData, 'view.contact');
    }

    /**
     * Продвижение магазина
     * @param getpost ::uint 'id' - ID магазина
     */
    public function promote()
    {
        $aData = array();
        bff::setMeta(_t('shops', 'Продвижение магазина'));
        $sFrom = $this->input->postget('from', TYPE_NOTAGS);
        $nUserID = User::id();
        $nSvcID = $this->input->postget('svc', TYPE_UINT);
        $nShopID = $this->input->getpost('id', TYPE_UINT);
        $aShop = $this->model->shopData($nShopID, array(
                'id',
                'user_id',
                'status',
                'blocked_reason',
                'region_id',
                'title',
                'link',
                'svc',
                'svc_fixed_to',
                'svc_marked_to',
            )
        );
        if (!empty($_GET['success'])) {
            return $this->showInlineMessage(array(
                    _t('shops', 'Вы успешно активировали услугу для магазина'),
                    '<br />',
                    (!empty($aShop) ? _t('shops', '<a [link]>[title]</a>', array(
                            'link'  => 'href="' . $aShop['link'] . '"',
                            'title' => $aShop['title'],
                        )
                    ) : '')
                )
            );
        }
        if (!$nUserID) {
            $nUserID = $aShop['user_id'];
        }
        $aPaySystems = Bills::getPaySystems(true);

        $aSvc = $this->model->svcData();
        $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvc), $aShop['region_id']);
        foreach ($aSvcPrices as $k => $v) {
            if (!empty($v)) {
                $aSvc[$k]['price'] = $v;
            }
        }

        $nUserBalance = $this->security->getUserBalance();

        if (Request::isPOST()) {
            $ps = $this->input->getpost('ps', TYPE_STR);
            if (!$ps || !array_key_exists($ps, $aPaySystems)) {
                $ps = key($aPaySystems);
            }
            $nPaySystem = $aPaySystems[$ps]['id'];
            $sPaySystemWay = $aPaySystems[$ps]['way'];

            $aResponse = array();
            do {
                if (!bff::servicesEnabled()) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nShopID || empty($aShop) || $aShop['status'] != self::STATUS_ACTIVE) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nSvcID || !isset($aSvc[$nSvcID])) {
                    $this->errors->set(_t('shops', 'Выберите услугу'));
                    break;
                }
                $aSvcSettings = array();
                $nSvcPrice = $aSvc[$nSvcID]['price'];
                # конвертируем сумму в валюту для оплаты по курсу
                $pay = Bills::getPayAmount($nSvcPrice, $ps);

                if ($ps == 'balance' && $nUserBalance >= $nSvcPrice) {
                    # активируем услугу (списываем со счета пользователя)
                    $aResponse['redirect'] = static::url('shop.promote', array(
                            'id'      => $nShopID,
                            'success' => 1,
                            'from'    => $sFrom
                        )
                    );
                    $aResponse['activated'] = $this->svc()->activate($this->module_name, $nSvcID, false, $nShopID, $nUserID, $nSvcPrice, $pay['amount'], $aSvcSettings);
                } else {
                    # создаем счет для оплаты
                    $nBillID = $this->bills()->createBill_InPay($nUserID, $nUserBalance,
                        $nSvcPrice,
                        $pay['amount'],
                        $pay['currency'],
                        Bills::STATUS_WAITING,
                        $nPaySystem, $sPaySystemWay,
                        _t('bills', 'Пополнение счета через [system]', array('system' => $this->bills()->getPaySystemTitle($nPaySystem))),
                        $nSvcID, true, # помечаем необходимость активации услуги сразу после оплаты
                        $nShopID, $aSvcSettings
                    );
                    if (!$nBillID) {
                        $this->errors->set(_t('bills', 'Ошибка создания счета'));
                        break;
                    }
                    $aResponse['pay'] = true;
                    # формируем форму оплаты для системы оплаты
                    $aResponse['form'] = $this->bills()->buildPayRequestForm($nPaySystem, $sPaySystemWay, $nBillID, $pay['amount']);
                }
            } while (false);
            $this->ajaxResponseForm($aResponse);
        }

        if (!$nShopID || empty($aShop)) {
            return $this->showInlineMessage(_t('shops', 'Магазин был удален, либо ссылка указана некорректно'));
        }
        # проверяем статус ОБ
        if ($aShop['status'] == self::STATUS_BLOCKED) {
            return $this->showInlineMessage(_t('shops', 'Магазин был заблокирован модератором, причина: [reason]', array(
                        'reason' => $aShop['blocked_reason']
                    )
                )
            );
        } else {
            if ($aShop['status'] != self::STATUS_ACTIVE) {
                return $this->showInlineMessage(_t('shops', 'Возможность продвижение магазина будет доступна после его проверки модератором.'));
            }
        }
        $aData['shop'] =& $aShop;

        # способы оплаты
        $aData['curr'] = Site::currencyDefault();
        $aData['ps'] =& $aPaySystems;
        reset($aPaySystems);
        $aData['ps_active_key'] = key($aPaySystems);
        foreach ($aPaySystems as $k => &$v) {
            $v['active'] = ($k == $aData['ps_active_key']);
        }
        unset($v);

        # список услуг
        foreach ($aSvc as &$v) {
            $v['active'] = ($v['id'] == $nSvcID);
            $aSvcPrices[$v['id']] = $v['price'];
        }
        unset($v);
        $aData['svc'] =& $aSvc;
        $aData['svc_id'] = $nSvcID;
        $aData['svc_prices'] =& $aSvcPrices;

        $aData['user_balance'] =& $nUserBalance;
        $aData['from'] = $sFrom;

        $this->seo()->robotsIndex(false);

        return $this->viewPHP($aData, 'promote');
    }

    /**
     * Подача заявки на закрепление магазина за пользователем
     * @param getpost ::uint 'id' - ID магазина
     */
    public function request()
    {
        $shopID = $this->input->postget('id', TYPE_UINT);

        $aResponse = array();
        do {
            if (!Request::isPOST() || User::shopID()) {
                $this->errors->reloadPage();
                break;
            }
            $shopData = $this->model->shopData($shopID, array('status', 'user_id'));
            if (!$shopID || empty($shopData) || $shopData['user_id'] > 0 ||
                $shopData['status'] != self::STATUS_ACTIVE
            ) {
                $this->errors->reloadPage();
                break;
            }

            if (User::id()) {
                $data = User::data(array('name', 'email', 'phone'));
            } else {
                $data = $this->input->postm(array(
                        'name'  => array(TYPE_NOTAGS, 'len' => 50),
                        'phone' => array(TYPE_NOTAGS, 'len' => 50),
                        'email' => array(TYPE_NOTAGS, 'len' => 100),
                    )
                );
                $data['name'] = preg_replace('/[^a-zа-яёїієґ\-\s0-9]+/iu', '', $data['name']);
                if (empty($data['name'])) {
                    $this->errors->set(_t('shops', 'Укажите ваше имя'), 'name');
                }
                if (empty($data['phone'])) {
                    $this->errors->set(_t('shops', 'Укажите ваш номер телефона'), 'phone');
                }
                if (!$this->input->isEmail($data['email'])) {
                    $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                }
            }

            $data['description'] = $this->input->post('description', TYPE_NOTAGS, array('len' => 3000));
            if (mb_strlen($data['description']) < 10) {
                $this->errors->set(_t('shops', 'Расскажите как вы связаны с данным магазином немного подробнее'), 'description');
            }

            if ($this->errors->no()) {
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-request-join', (User::id() ? 5 : 15))) {
                    break;
                }

                $data['shop_id'] = $shopID;

                $res = $this->model->requestSave(0, $data);
                if (!$res) {
                    $this->errors->reloadPage();
                } else {
                    $this->updateRequestsCounter(1, true);
                }
            }

        } while (false);

        $this->ajaxResponseForm($aResponse);
    }

    /**
     * Кабинет пользователя: Магазин
     * Список объявлений, добавленных от "магазина"
     */
    public function my_shop()
    {
        $nShopID = User::shopID();
        if (!$nShopID) {
            # магазин не создан => отправляем на форму заявки на открытие
            $this->redirect(static::url('my.open'));
        }
        $aData = $this->model->shopData($nShopID, array(
                'id',
                'title',
                'link',
                'status',
                'moderated',
                'blocked_reason'
            )
        );
        # ошибка получения данных о магазине
        if (empty($aData)) {
            bff::log('Неудалось получить данные о магазине #' . $nShopID . ' [shops::my_shop]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка формирования списка объявлений.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }
        # магазин заблокирован
        if ($aData['status'] == static::STATUS_BLOCKED) {
            return $this->formStatus('edit.blocked', $aData);
        }
        # магазин деактивирован
        if ($aData['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->formStatus('edit.notactive', $aData);
        } # результат открытия магазина (ожидание проверки модератора)
        else {
            if (!empty($_GET['success']) || $aData['status'] == static::STATUS_REQUEST) {
                $this->security->setTokenPrefix('');

                return $this->formStatus('add.success', $aData);
            }
        }

        return BBS::i()->my_items($nShopID);
    }

    /**
     * Кабинет пользователя: Настройки магазина
     */
    public function my_settings()
    {
        $nShopID = User::shopID();

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                # проверка токена + реферера
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $this->validateShopData($nShopID, $aData);

                if (!$this->errors->no()) {
                    break;
                }

                # отправляем на модерацию: смена название, описания, либо если заблокирован
                $aDataPrev = $this->model->shopData($nShopID, array('title', 'descr', 'status'));
                foreach (array('title','descr') as $k) {
                    if ($aDataPrev[$k] != $aData[$k]) {
                        $aData['moderated'] = 2;
                    }
                }
                if ($aDataPrev['status'] == static::STATUS_BLOCKED) {
                    $aData['moderated'] = 2;
                }

                # сохраняем настройки магазина
                $this->model->shopSave($nShopID, $aData);
                $aResponse['refill'] = array(
                    'title' => $aData['title'],
                    'descr' => $aData['descr']
                );

            } while (false);

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->shopData($nShopID, '*', true);
        # ошибка получения данных о магазине
        if (empty($aData)) {
            bff::log('Неудалось получить данные о магазине #' . $nShopID . ' [shops::my_settings]');

            return $this->showInlineMessage(array(
                    _t('shops', 'Ошибка редактирования настроек магазина.'),
                    '<br />',
                    _t('shops', 'Для выяснения причины обратитесь к администратору.'),
                )
            );
        }
        # магазин деактивирован
        if ($aData['status'] == static::STATUS_NOT_ACTIVE) {
            return $this->formStatus('edit.notactive', $aData);
        } # результат открытия магазина (ожидание проверки модератора)
        else {
            if ($aData['status'] == static::STATUS_REQUEST) {
                $this->security->setTokenPrefix('');

                return $this->formStatus('edit.moderating', $aData);
            }
        }

        return $this->form($nShopID, $aData);
    }

    /**
     * Кабинет пользователя: Открытие магазина
     */
    public function my_open()
    {
        if (!User::id()) {
            return $this->showInlineMessage(_t('shops', 'Открытие магазина доступно только для авторизованных пользователей'), array('auth' => true));
        }

        $this->security->setTokenPrefix('my-settings');

        $this->validateShopData(0, $aData);

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                # проверка токена + реферера
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                if (User::shopID()) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->errors->no()) {
                    break;
                }

                $aData['user_id'] = User::id();
                $aData['moderated'] = 0; # помечаем на модерацию
                if (static::premoderation()) {
                    $aData['status'] = self::STATUS_REQUEST;
                } else {
                    $aData['status'] = self::STATUS_ACTIVE;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-open', 60)) {
                    break;
                }

                # создаем магазин
                $nShopID = $this->model->shopSave(0, $aData);
                if (!$nShopID) {
                    $this->errors->set(_t('shops', 'Ошибка открытия магазина, обратитесь в службу поддержки.'));
                    break;
                } else {
                    # связываем пользователя с магазином
                    $this->onUserShopCreated(User::id(), $nShopID);
                    # сохраняем логотип
                    $sLogoFilename = $this->input->postget('logo', TYPE_STR);
                    if (!empty($sLogoFilename)) {
                        $this->shopLogo($nShopID)->untemp($sLogoFilename, true);
                    }
                    if (static::premoderation()) {
                        $this->updateRequestsCounter(1);
                    }
                }

                $aResponse['id'] = $nShopID;
                $aResponse['refill'] = array(
                    'title' => $aData['title'],
                    'descr' => $aData['descr']
                );
                $aResponse['redirect'] = static::url('my.shop', array('success' => 1));
            } while (false);

            $this->ajaxResponseForm($aResponse);
        }

        # Данные о пользователе
        $aUserData = User::data(array(
                'email',
                'phones',
                'skype',
                'icq',
                'region_id',
                'addr_addr',
                'addr_lat',
                'addr_lon',
                'shop_id'
            ), true
        );
        if ($aUserData['shop_id']) {
            $this->redirect(static::url('my.shop'));
        }
        # Корректируем регион пользователя
        if ($aUserData['region_id']) {
            $regionData = Geo::regionData($aUserData['region_id']);
            if ($regionData && !Geo::coveringRegionCorrect($regionData)) {
                $aUserData['region_id'] = 0;
                if (Geo::coveringType(Geo::COVERING_CITY)) {
                    $aUserData['region_id'] = Geo::coveringRegion();
                }
            }
            unset($regionData);
        }
        $aData = array_merge($aData, $aUserData);

        $aData['logo'] = '';
        $aData['add_text'] = config::get('shops_form_add_' . LNG, '');
        bff::setMeta(_t('shops', 'Открытие магазина'));

        return $this->form(0, $aData);
    }

    /**
     * Формирование формы открытия/редактирования настроек магазина
     * @param integer $nShopID ID магазина
     * @param array $aData @ref настроки магазина
     * @return HTML
     */
    protected function form($nShopID, array &$aData = array())
    {
        $aData['id'] = $nShopID;

        # логотип
        $oLogo = $this->shopLogo($nShopID);
        $aData['logo_preview'] = ShopsLogo::url($nShopID, (!empty($aData['logo']) ? $aData['logo'] : false),
            ShopsLogo::szList, false, true
        );
        $aData['logo_maxsize'] = $oLogo->getMaxSize(false);
        $aData['logo_maxsize_format'] = $oLogo->getMaxSize(true);

        # категории
        if (($aData['cats_on'] = static::categoriesEnabled())) {
            $aData['cats'] = $this->model->shopCategoriesIn($nShopID, ShopsCategoryIcon::SMALL);
            foreach ($aData['cats'] as &$v) {
                if ($v['pid'] > static::CATS_ROOTID) {
                    $v['icon'] = $v['picon'];
                    $v['title'] = $v['ptitle'] . ' &raquo; ' . $v['title'];
                    unset($v['picon'], $v['ptitle']);
                }
            }
            unset($v);
            $aData['cats_main'] = $this->catsList('form', 'init');
        } else {
            $aData['cats'] = array();
            $aData['cats_main'] = '';
        }

        # координаты по-умолчанию
        Geo::mapDefaultCoordsCorrect($aData['addr_lat'], $aData['addr_lon']);

        return $this->viewPHP($aData, 'my.form');
    }

    /**
     * Отображение статуса магазина
     * @param string $sFormStatus статус
     * @param array $aData @ref данные магазина
     * @return string
     */
    private function formStatus($sFormStatus, array &$aData = array())
    {
        $aData['form_status'] = $sFormStatus;

        return $this->viewPHP($aData, 'my.form.status');
    }

    /**
     * Загрузка / удаление логотипа магазина
     */
    public function logo()
    {
        $this->security->setTokenPrefix('my-settings');
        $nShopID = User::shopID();

        switch ($this->input->getpost('act')) {
            case 'upload': # загрузка
            {
                $bTmp = !$nShopID;
                if (!$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    $mResult = false;
                } else {
                    $mResult = $this->shopLogo($nShopID)->uploadQQ(true, !$bTmp);
                }

                $aResponse = array('success' => ($mResult !== false && $this->errors->no()));
                if ($mResult !== false) {
                    $aResponse = array_merge($aResponse, $mResult);
                    $aResponse['preview'] = ShopsLogo::url($nShopID, $mResult['filename'], ShopsLogo::szList, $bTmp);
                    if ($bTmp) {
                        $this->shopLogo($nShopID)->deleteTmp($this->input->postget('tmp', TYPE_STR));
                    } else {
                        # отправляем на пост-модерацию: смена логотипа
                        $this->model->shopSave($nShopID, array('moderated' => 2));
                    }
                }
                $aResponse['errors'] = $this->errors->get(true);

                $this->ajaxResponse($aResponse, 1);
            }
            break;
            case 'delete': # удаление
            {
                $aResponse = array();
                $oLogo = $this->shopLogo($nShopID);
                if ($this->security->validateToken(true, false)) {
                    if ($nShopID) {
                        $oLogo->delete(true);
                    } else {
                        $oLogo->deleteTmp($this->input->post('fn', TYPE_NOTAGS));
                    }
                    if ($this->errors->no()) {
                        $aResponse['preview'] = $oLogo->urlDefault(ShopsLogo::szList);
                    }
                }

                $this->ajaxResponseForm($aResponse);
            }
            break;
        }
    }

    public function ajax()
    {
        $response = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            case 'shop-contacts': # Просмотр контактов магазина в блоке справа
            {
                $ex = $this->input->post('ex', TYPE_STR);
                if (empty($ex)) {
                    $this->errors->reloadPage();
                    break;
                }
                list($ex, $shopID) = explode('-', $ex);

                $shop = $this->model->shopData($shopID, array(
                        'id',
                        'id_ex',
                        'status',
                        'phones',
                        'skype',
                        'icq',
                        'social'
                    )
                );

                if (empty($shop) || $shop['id_ex'] != $ex || $shop['status'] != static::STATUS_ACTIVE ||
                    !$this->security->validateToken(true, false)
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-contacts', 1)) {
                    break;
                }

                if (!empty($shop['phones'])) {
                    if (!bff::deviceDetector(bff::DEVICE_PHONE)) {
                        $phones = array();
                        foreach ($shop['phones'] as $v) $phones[] = $v['v'];
                        $response['phones'] = '<span><img src="' . Users::contactAsImage($phones) . '" /></span>';
                    } else {
                        $phones = '<span>'; $i = 1;
                        foreach ($shop['phones'] as $v) {
                            $phone = HTML::obfuscate($v['v']);
                            $phones .= '<a href="tel:'.$phone.'">'.$phone.'</a>';
                            if ($i++ < sizeof($shop['phones'])) {
                                $phones .= ', ';
                            }
                        }
                        $phones .= '</span>';
                        $response['phones'] = $phones;
                    }
                }
                if (!empty($shop['skype'])) {
                    $skype = HTML::obfuscate($shop['skype']);
                    $response['skype'] = '<a href="skype:' . $skype . '?call" rel="nofollow">' . $skype . '</a>';
                }
                if (!empty($shop['icq'])) {
                    $response['icq'] = HTML::obfuscate($shop['icq']);
                }

                $page = $this->input->postget('page', TYPE_STR);
                if ($page == 'list') {
                    $response['listType'] = $this->input->postget('lt', TYPE_UINT);
                    $response['social'] = & $shop['social'];
                    $response['socialTypes'] = Shops::socialLinksTypes();
                    $response = array(
                        'html' => $this->viewPHP($response, 'search.list.contacts')
                    );
                }
            }
            break;
            case 'shop-contacts-list': # Просмотр контактов магазина в списке
            {
                $ex = $this->input->post('ex', TYPE_STR);
                if (empty($ex)) {
                    $this->errors->reloadPage();
                    break;
                }
                list($ex, $shopID) = explode('-', $ex);

                $data = $this->model->shopData($shopID, array(
                        'id',
                        'id_ex',
                        'status',
                        'items',
                        'title',
                        'link',
                        'logo',
                        'phones',
                        'skype',
                        'icq',
                        'social',
                        'region_id',
                        'addr_addr'
                    )
                );

                if (empty($data) || $data['id_ex'] != $ex || $data['status'] != static::STATUS_ACTIVE ||
                    !$this->security->validateToken(true, false)
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-contacts-list', 1)) {
                    break;
                }

                $data['region_title'] = (isset($data['region_id']) ? Geo::regionTitle($data['region_id']) : '');

                if (!empty($data['phones'])) {
                    $phones = array();
                    foreach ($data['phones'] as $v) {
                        $phones[] = $v['v'];
                    }
                    $data['phones'] = '<span><img src="' . Users::contactAsImage($phones) . '" /></span>';
                }
                if (!empty($data['skype'])) {
                    $skype = HTML::obfuscate($data['skype']);
                    $data['skype'] = '<a href="skype:' . $skype . '?call" rel="nofollow">' . $skype . '</a>';
                }
                if (!empty($data['icq'])) {
                    $data['icq'] = HTML::obfuscate($data['icq']);
                }
                $data['has_contacts'] = ($data['phones'] || $data['social'] || !empty($data['skype']) || !empty($data['icq']));

                $data['logo'] = ShopsLogo::url($shopID, $data['logo'], ShopsLogo::szList);
                $data['device'] = $this->input->postget('device', TYPE_STR);
                $data['listType'] = $this->input->postget('lt', TYPE_UINT);
                $data['socialTypes'] = Shops::socialLinksTypes();
                $response['html'] = $this->viewPHP($data, 'search.list.contacts');
            }
            break;
            case 'shop-claim': # Пожаловаться
            {
                $nShopID = $this->input->postget('id', TYPE_UINT);
                if (!$nShopID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $nReason = $this->input->post('reason', TYPE_ARRAY_UINT);
                $nReason = array_sum($nReason);
                $sMessage = $this->input->post('comment', TYPE_STR);
                $sMessage = $this->input->cleanTextPlain($sMessage, 1000, false);

                if (!$nReason) {
                    $this->errors->set(_t('shops', 'Укажите причину'));
                    break;
                } else {
                    if ($nReason & self::CLAIM_OTHER) {
                        if (mb_strlen($sMessage) < 10) {
                            $this->errors->set(_t('shops', 'Опишите причину подробнее'), 'comment');
                            break;
                        }
                    }
                }

                if (!User::id()) {
                    $response['captcha'] = false;
                    if (!CCaptchaProtection::correct($this->input->cookie('c2'), $this->input->post('captcha', TYPE_NOTAGS))) {
                        $response['captcha'] = true;
                        $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                        break;
                    }
                } else {
                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('shops-claim')) {
                        break;
                    }
                }

                $nClaimID = $this->model->claimSave(0, array(
                        'reason'  => $nReason,
                        'message' => $sMessage,
                        'shop_id' => $nShopID,
                    )
                );

                if ($nClaimID > 0) {
                    $this->claimsCounterUpdate(1);
                    $this->model->shopSave($nShopID, array(
                            'claims_cnt = claims_cnt + 1'
                        )
                    );
                    if (!User::id()) {
                        Request::deleteCOOKIE('c2');
                    }
                }
            }
            break;
            case 'shop-sendfriend': # Поделиться с другом
            {
                $nShopID = $this->input->postget('id', TYPE_UINT);
                if (!$nShopID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 150));
                if (!$this->input->isEmail($sEmail, false)) {
                    $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                    break;
                }

                $aData = $this->model->shopData($nShopID, array('title', 'link'));
                if (empty($aData)) {
                    $this->errors->reloadPage();
                    break;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if (Site::i()->preventSpam('shops-sendfriend')) {
                    $aResponse['later'] = true;
                    break;
                }

                bff::sendMailTemplate(array(
                        'shop_title' => $aData['title'],
                        'shop_link'  => $aData['link'],
                    ), 'shops_shop_sendfriend', $sEmail
                );
            }
            break;
        }

        $this->ajaxResponseForm($response);
    }

    /**
     * Список выбора категорий
     * @param string $type тип списка
     * @param string $device тип устройства bff::DEVICE_ или 'init'
     * @param int $parentID ID parent-категории
     */
    public function catsList($type = '', $device = '', $parentID = 0)
    {
        if (Request::isAJAX()) {
            $type = $this->input->getpost('act', TYPE_STR);
            $device = $this->input->post('device', TYPE_STR);
            $parentID = $this->input->post('parent', TYPE_UINT);
        }

        list($model, $ICON, $ICON_SMALL, $ICON_BIG, $ROOT_ID) = (static::categoriesEnabled() ?
            array(
                $this->model,
                static::categoryIcon(0),
                ShopsCategoryIcon::SMALL,
                ShopsCategoryIcon::BIG,
                self::CATS_ROOTID
            ) :
            array(
                BBS::model(),
                BBS::categoryIcon(0),
                BBSCategoryIcon::SMALL,
                BBSCategoryIcon::BIG,
                BBS::CATS_ROOTID
            ));

        switch ($type) {
            case 'search': # поиск: фильтр категории
            {
                $urlListing = static::url('search');
                $cut2levels = true;

                if ($device == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_BIG . ' as icon',
                            'shops',
                            'subs'
                        );
                        $aParent = $model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if ($cut2levels && $aParent['numlevel'] == 2) {
                                $aParent['subs'] = 0;
                            }
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $model->catsList($type, $device, $parentID, $ICON_BIG);
                    if (!empty($aData)) {
                        foreach ($aData as &$v) {
                            $v['l'] = $urlListing . $v['k'] . '/';
                            $v['i'] = $ICON->url($v['id'], $v['i'], $ICON_BIG);
                            $v['active'] = ($v['id'] == $selectedID);
                        }
                        unset($v);
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = $urlListing . $aParent['keyword'] . '/';
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем настройки основной категории
                                $aParentsID = $model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array(
                                'cats'       => $aData,
                                'parent'     => $aParent,
                                'step'       => 2,
                                'cut2levels' => $cut2levels
                            );
                            $aData = $this->viewPHP($aData, 'search.cats.desktop');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array('html' => $aData));
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aData, 'total' => config::get('shops_total_active', 0), 'step' => 1);

                        return $this->viewPHP($aData, 'search.cats.desktop');
                    }
                } else {
                    if ($device == bff::DEVICE_PHONE) {
                        $selectedID = 0;
                        if ($parentID > $ROOT_ID) {
                            $parentData = array(
                                'id',
                                'pid',
                                'numlevel',
                                'numleft',
                                'numright',
                                'title',
                                'keyword',
                                'icon_' . $ICON_SMALL . ' as icon',
                                'subs'
                            );
                            $aParent = $model->catData($parentID, $parentData);
                            if (!empty($aParent)) {
                                if ($cut2levels && $aParent['numlevel'] == 2) {
                                    $aParent['subs'] = 0;
                                }
                                if (!$aParent['subs']) {
                                    # в данной категории нет подкатегорий
                                    # формируем список подкатегорий ее parent-категории
                                    $aParent = $model->catData($aParent['pid'], $parentData);
                                    if (!empty($aParent)) {
                                        $selectedID = $parentID;
                                        $parentID = $aParent['id'];
                                    }
                                }
                            }
                        }
                        $aData = $model->catsList($type, $device, $parentID, $ICON_SMALL);
                        if (!empty($aData)) {
                            foreach ($aData as $k => $v) {
                                $aData[$k]['l'] = $urlListing . $v['k'] . '/';
                                $aData[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                                $aData[$k]['active'] = ($v['id'] == $selectedID);
                            }
                        }
                        if ($parentID > $ROOT_ID) {
                            if (!empty($aParent)) {
                                $aParent['link'] = $urlListing . $aParent['keyword'] . '/';
                                $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                                if ($aParent['main']) {
                                    $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                                } else {
                                    # глубже второго уровня, получаем иконку основной категории
                                    $aParentsID = $model->catParentsID($aParent, false);
                                    if (!empty($aParentsID[1])) {
                                        $aParentMain = $model->catData($aParentsID[1], array(
                                                'id',
                                                'icon_' . $ICON_SMALL . ' as icon'
                                            )
                                        );
                                        $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                    }
                                }
                                $aData = array(
                                    'cats'       => $aData,
                                    'parent'     => $aParent,
                                    'step'       => 2,
                                    'cut2levels' => $cut2levels
                                );
                                $aData = $this->viewPHP($aData, 'search.cats.phone');
                                if (Request::isAJAX()) {
                                    $this->ajaxResponseForm(array('html' => $aData));
                                } else {
                                    return $aData;
                                }
                            } else {
                                $this->errors->impossible();
                                $this->ajaxResponseForm(array('html' => ''));
                            }
                        } else {
                            $aData = array('cats' => $aData, 'step' => 1);

                            return $this->viewPHP($aData, 'search.cats.phone');
                        }
                    }
                }
            }
            break;
            case 'form': # форма магазина: выбор категории
            {
                $ICON = static::categoryIcon(0);
                if ($device == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_BIG . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($type, $device, $parentID, $ICON_BIG);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_BIG);
                            $aCats[$k]['active'] = ($v['id'] == $selectedID);
                        }
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2);
                            $aData = $this->viewPHP($aData, 'my.form.cats.desktop');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array(
                                        'html' => $aData,
                                        'cats' => $aCats,
                                        'pid'  => $aParent['pid']
                                    )
                                );
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aCats, 'step' => 1);

                        return $this->viewPHP($aData, 'my.form.cats.desktop');
                    }
                } else if ($device == bff::DEVICE_PHONE) {
                    $selectedID = 0;
                    if ($parentID > $ROOT_ID) {
                        $parentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_SMALL . ' as icon',
                            'subs'
                        );
                        $aParent = $this->model->catData($parentID, $parentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $parentData);
                                if (!empty($aParent)) {
                                    $selectedID = $parentID;
                                    $parentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($type, $device, $parentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                            $aCats[$k]['active'] = ($v['id'] == $selectedID);
                        }
                    }
                    if ($parentID > $ROOT_ID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == $ROOT_ID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $ICON->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $ICON->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2);
                            $aData = $this->viewPHP($aData, 'my.form.cats.phone');
                            if (Request::isAJAX()) {
                                $this->ajaxResponseForm(array(
                                        'html' => $aData,
                                        'cats' => $aCats,
                                        'pid'  => $aParent['pid']
                                    )
                                );
                            } else {
                                return $aData;
                            }
                        } else {
                            $this->errors->impossible();
                            $this->ajaxResponseForm(array('html' => ''));
                        }
                    } else {
                        $aData = array('cats' => $aCats, 'step' => 1);

                        return $this->viewPHP($aData, 'my.form.cats.phone');
                    }
                } else if ($device == 'init') {
                    /**
                     * Формирование данных об основных категориях
                     * для jShopsForm.init({catsMain:DATA});
                     */
                    $aCats = $this->model->catsList('form', bff::DEVICE_PHONE, $parentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $ICON->url($v['id'], $v['i'], $ICON_SMALL);
                        }
                    }

                    return $aCats;
                }
            }
            break;
        }
    }

    /**
     * Пересчет счетчиков магазинов
     * - периодичность = актуальность счетчиков магазинов, рекомендуемая: каждые 15 минут
     */
    public function shopsCronCounters()
    {
        if (!bff::cron()) return;
        $this->model->shopsCronCounters();
    }

}