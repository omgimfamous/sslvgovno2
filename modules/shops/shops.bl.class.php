<?php

abstract class ShopsBase extends Module
    implements IModuleWithSvc
{
    /** @var ShopsModel */
    var $model = null;
    var $securityKey = '425ea0b5fe88d011dbbe85b29173e741';

    # Типы ссылок соц. сетей
    const SOCIAL_LINK_FACEBOOK      = 1;
    const SOCIAL_LINK_VKONTAKTE     = 2;
    const SOCIAL_LINK_ODNOKLASSNIKI = 4;
    const SOCIAL_LINK_GOOGLEPLUS    = 8;
    const SOCIAL_LINK_YANDEX        = 16;
    const SOCIAL_LINK_MAILRU        = 32;

    # Статус магазина
    const STATUS_REQUEST    = 0; # заявка на открытие (используется совместно с premoderation()==true)
    const STATUS_ACTIVE     = 1; # активен
    const STATUS_NOT_ACTIVE = 2; # неактивен
    const STATUS_BLOCKED    = 3; # заблокирован

    # Настройки категорий
    const CATS_ROOTID  = 1; # ID "Корневой категории" (изменять не рекомендуется)
    const CATS_MAXDEEP = 2; # Максимальная глубина вложенности категорий (допустимые варианты: 1,2)

    # Типы отображения списка
    const LIST_TYPE_LIST = 1; # строчный вид
    const LIST_TYPE_MAP  = 3; # карта

    # ID Услуг
    const SERVICE_FIX  = 64; # закрепление
    const SERVICE_MARK = 128; # выделение

    # Жалобы
    const CLAIM_OTHER = 1024; # тип жалобы: "Другое"

    public function init()
    {
        parent::init();

        $this->module_title = 'Магазины';

        bff::autoloadEx(array(
                'ShopsLogo'         => array('app', 'modules/shops/shops.logo.php'),
                'ShopsCategoryIcon' => array('app', 'modules/shops/shops.category.icon.php'),
            )
        );
    }

    public function sendmailTemplates()
    {
        $aTemplates = array(
            'shops_shop_sendfriend' => array(
                'title'       => 'Магазины: отправить другу',
                'description' => 'Уведомление, отправляемое по указанному email адресу',
                'vars'        => array(
                    '{shop_title}' => 'Название магазина',
                    '{shop_link}'  => 'Ссылка на страницу магазина'
                ),
                'impl'        => true,
                'priority'    => 18,
            ),
            'shops_open_success' => array(
                'title'       => 'Магазины: уведомление об активации магазина',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> с оповещением об успешном открытии магазина (после проверки модератором)',
                'vars'        => array(
                    '{name}'       => 'Имя',
                    '{email}'      => 'Email',
                    '{shop_id}'    => 'ID магазина',
                    '{shop_title}' => 'Название магазина',
                    '{shop_link}'  => 'Ссылка на магазин',
                ),
                'impl'        => true,
                'priority'    => 19,
            ),
        );

        return $aTemplates;
    }

    /**
     * Shortcut
     * @return Shops
     */
    public static function i()
    {
        return bff::module('shops');
    }

    /**
     * Shortcut
     * @return ShopsModel
     */
    public static function model()
    {
        return bff::model('shops');
    }

    /**
     * Формирование URL
     * @param string $key ключ
     * @param array $opts доп. параметры
     * @param boolean $dynamic динамическая ссылка
     * @return string
     */
    public static function url($key, array $opts = array(), $dynamic = false)
    {
        $base = static::urlBase(LNG, $dynamic);
        $link = '';
        switch ($key) {
            # список магазинов (geo)
            case 'search':
                $link = 'shops/' . (!empty($opts['keyword']) ? $opts['keyword'] . '/' : '');
                break;
            # просмотр страницы магазина (geo)
            case 'shop.view':
                $link = 'shop/';
                break;
            # страница продвижения магазина
            case 'shop.promote':
                return $base . '/shop/promote?' . http_build_query($opts);
                break;
            # заявка на закрепление магазина за пользователем
            case 'request':
                return $base . '/shop/request' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма открытия магазина
            case 'my.open':
                return $base . '/cabinet/shop/open' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма открытия магазина
            case 'my.shop':
                return $base . '/cabinet/shop' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
        }

        # формируем ссылку с учетом указанной области (region), [города (city)]
        # либо с учетом текущих настроек фильтра по региону
        return Geo::url($opts, $dynamic) . $link;
    }

    /**
     * Формирование URL страниц магазина
     * @param string $shopLink ссылка на магазин
     * @param string $tab ключ страницы магазина
     * @param array $opts доп. параметры
     * @return string
     */
    public static function urlShop($shopLink, $tab = '', array $opts = array())
    {
        return $shopLink . (!empty($tab) ? '/' . $tab . '/' : '') . (!empty($opts) ? '?' . http_build_query($opts) : '');
    }

    /**
     * URL для формы связи с магазином
     * @param string $shopLink ссылка на магазин
     * @return string
     */
    public static function urlContact($shopLink)
    {
        return static::urlShop($shopLink, 'contact');
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        return array(
            'pages'  => array(
                'search'          => array(
                    't'      => 'Поиск (все категории)',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(),
                ),
                'search-category' => array(
                    't'       => 'Поиск в категории' . (static::categoriesEnabled() ? ' магазинов' : ''),
                    'list'    => true,
                    'i'       => true,
                    'macros'  => array(
                        'category'           => array('t' => 'Название текущей категории'),
                        'categories'         => array('t' => 'Название всех категорий'),
                        'categories.reverse' => array('t' => 'Название всех категорий<br />(обратный порядок)'),
                    ),
                    'fields'  => array(
                        'breadcrumb' => array(
                            't'    => 'Хлебная крошка',
                            'type' => 'text',
                        ),
                        'titleh1' => array(
                            't'    => 'Заголовок H1',
                            'type' => 'text',
                        ),
                        'seotext' => array(
                            't'    => 'SEO текст',
                            'type' => 'wy',
                        ),
                    ),
                    'inherit' => static::categoriesEnabled(),
                ),
                'shop-view'       => array(
                    't'      => 'Просмотр магазина',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'title'       => array('t' => 'Название магазина'),
                        'description' => array('t' => 'Описание магазина (до 150 символов)'),
                        'country'     => array('t' => 'Страна магазина'),
                        'region'      => array('t' => 'Регион магазина'),
                    ),
                    'fields' => array(
                        'share_title'       => array(
                            't'    => 'Заголовок (поделиться в соц. сетях)',
                            'type' => 'text',
                        ),
                        'share_description' => array(
                            't'    => 'Описание (поделиться в соц. сетях)',
                            'type' => 'textarea',
                        ),
                        'share_sitename'    => array(
                            't'    => 'Название сайта (поделиться в соц. сетях)',
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'macros' => array(
                'region' => array('t' => 'Регион поиска'),
            ),
        );
    }

    /**
     * Инициализация компонента работы с логотипом магазина
     * @param integer $nShopID ID магазина
     * @return ShopsLogo объект
     */
    public function shopLogo($nShopID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsLogo();
        }
        $i->setRecordID($nShopID);

        return $i;
    }

    /**
     * Включена ли премодерация магазинов
     * @return bool
     */
    public static function premoderation()
    {
        return (bool)config::sys('shops.premoderation', TYPE_BOOL);
    }

    /**
     * Включены ли категории магазинов (true), false - используются категории объявлений
     * @return bool
     */
    public static function categoriesEnabled()
    {
        return (bool)config::sys('shops.categories', TYPE_BOOL);
    }

    /**
     * Максимально допустимое кол-во категорий магазинов, связываемых с магазинами, 0 - без ограничений
     * @return integer
     */
    public static function categoriesLimit()
    {
        return config::sys('shops.categories.limit', 5);
    }

    /**
     * Инициализация компонента обработки иконок основных категорий ShopsCategoryIcon
     * @param mixed $nCategoryID ID категории
     * @return ShopsCategoryIcon component
     */
    public static function categoryIcon($nCategoryID = false)
    {
        static $i;
        if (!isset($i)) {
            $i = new ShopsCategoryIcon();
        }
        $i->setRecordID($nCategoryID);

        return $i;
    }

    /**
     * Формирование хлебных крошек
     * @param integer $nCategoryID ID категории (на основе которой выполняем формирование)
     * @param string $sMethodName имя метода
     * @param array $aOptions доп. параметры: city, region
     */
    protected function categoryCrumbs($nCategoryID, $sMethodName, array $aOptions = array())
    {
        $model = (static::categoriesEnabled() ? $this->model : BBS::model());
        $aData = $model->catParentsData($nCategoryID, array('id', 'title', 'keyword', 'breadcrumb'));
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                # ссылка
                $aOptions['keyword'] = $v['keyword'];
                $v['link'] = static::url('search', $aOptions);
                # активируем
                $v['active'] = ($v['id'] == $nCategoryID);
            }
            unset($v);
        }
        if (isset($aOptions['keyword'])) {
            unset($aOptions['keyword']);
        }
        $aData = array(
                array(
                    'id'     => 0,
                    'breadcrumb'  => _t('search', 'Магазины'),
                    'link'   => Shops::url('search', $aOptions),
                    'active' => empty($aData)
                )
            ) + $aData;

        return $aData;
    }

    /**
     * Проверка данных магазина
     * @param integer $nShopID ID магазина
     * @param array $aData @ref данные магазина
     */
    public function validateShopData($nShopID = 0, &$aData = array())
    {
        $this->input->postm(array(
                'title'     => array(TYPE_NOTAGS, 'len' => 50), # название
                'cats'      => TYPE_ARRAY_UINT, # категории
                'descr'     => array(TYPE_NOTAGS, 'len' => 600), # описание (чем занимается)
                'skype'     => array(TYPE_NOTAGS, 'len' => 32), # skype
                'icq'       => array(TYPE_NOTAGS, 'len' => 20), # icq
                'phones'    => TYPE_ARRAY_NOTAGS, # телефоны
                'site'      => array(TYPE_NOTAGS, 'len' => 200), # сайт
                'social'    => array(
                    TYPE_ARRAY_ARRAY, # соц. сети
                    't' => TYPE_UINT, # тип ссылки
                    'v' => array(TYPE_NOTAGS, 'len' => 300), # ссылка
                ),
                # адрес
                'region_id' => TYPE_UINT, # регион (город или 0)
                'addr_addr' => array(TYPE_NOTAGS, 'len' => 300), # точный адрес
                'addr_lat'  => TYPE_NUM, # координаты на карте
                'addr_lon'  => TYPE_NUM, # координаты на карте
            ), $aData, (!bff::adminPanel() ? false : 'shop_')
        );

        if (bff::adminPanel()) {
            $aData['import'] = $this->input->post('shop_import', TYPE_BOOL); # доступность импорта
        }

        if (Request::isPOST()) {
            do {
                if (empty($aData['title'])) {
                    $this->errors->set(_t('shops', 'Укажите название магазина'), 'title');
                } else {
                    $aData['title'] = trim($aData['title'], ' -');
                    if (mb_strlen($aData['title']) < 2) {
                        $this->errors->set(_t('shops', 'Название магазина слишком короткое'), 'title');
                    }
                }

                # URL keyword
                $aData['keyword'] = trim(preg_replace('/[^a-z0-9\-]/', '', mb_strtolower(
                            func::translit($aData['title'])
                        )
                    ), '- '
                );

                # категории
                if (!static::categoriesEnabled()) {
                    unset($aData['cats']);
                } else {
                    if (empty($aData['cats'])) {
                        $this->errors->set(_t('shops', 'Укажите категорию магазина'), 'cats');
                    }
                }

                # чистим описание, дополнительно:
                $aData['descr'] = $this->input->cleanTextPlain($aData['descr']);
                if (mb_strlen($aData['descr']) < 12) {
                    $this->errors->set(_t('shops', 'Опишите подробнее чем занимается ваш магазин'), 'descr');
                }

                # чистим контакты
                Users::i()->cleanUserData($aData, array('phones', 'skype', 'icq', 'site'), array(
                        'phones_limit' => static::phonesLimit(),
                    )
                );

                # соц. сети (корректируем ссылки)
                $aSocial = array();
                $aSocialTypes = static::socialLinksTypes();
                foreach ($aData['social'] as $v) {
                    if (strlen($v['v']) >= 5 && array_key_exists($v['t'], $aSocialTypes)) {
                        if (stripos($v['v'], 'http') !== 0) {
                            $v['v'] = 'http://' . $v['v'];
                        }
                        $v['v'] = str_replace(array('"', '\''), '', $v['v']);
                        $aSocial[] = array(
                            't' => $v['t'],
                            'v' => $v['v'],
                        );
                    }
                }
                $limit = static::socialLinksLimit();
                if ($limit > 0 && sizeof($aSocial) > $limit) {
                    $aSocial = array_slice($aSocial, 0, $limit);
                }
                $aData['social'] = $aSocial;

                # регион
                if ($aData['region_id']) {
                    if (!Geo::isCity($aData['region_id'])) {
                        $this->errors->set(_t('shops', 'Город указан некорректно'), 'region');
                    }
                }
                if (!$aData['region_id']) {
                    $this->errors->set(_t('shops', 'Укажите регион деятельности магазина'), 'region');
                }
                if (!Geo::coveringType(Geo::COVERING_COUNTRY)) {
                    $regionData = Geo::regionData($aData['region_id']);
                    if (!$regionData || !Geo::coveringRegionCorrect($regionData)) {
                        $this->errors->set(_t('shops', 'Город указан некорректно'));
                        break;
                    }
                }

                # разворачиваем регион: region_id => reg1_country, reg2_region, reg3_city
                $aRegions = Geo::model()->regionParents($aData['region_id']);
                $aData = array_merge($aData, $aRegions['db']);

                # проквочиваем название
                $aData['title_edit'] = $aData['title'];
                $aData['title'] = HTML::escape($aData['title_edit']);

                # формируем URL магазина
                $sLink = static::url('shop.view', array(
                        'region' => $aRegions['keys']['region'],
                        'city'   => $aRegions['keys']['city']
                    ), true
                );
                if ($nShopID) {
                    $sLink .= $aData['keyword'] . '-' . $nShopID;
                } else {
                    # дополняем в ShopsModel::shopSave
                }
                $aData['link'] = $sLink;

            } while (false);
        }
    }

    /**
     * Удаления магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopDelete($nShopID)
    {
        if (!$nShopID || empty($nShopID)) {
            return false;
        }
        $aData = $this->model->shopData($nShopID, array('id', 'user_id', 'logo', 'status'));
        if (empty($aData)) {
            return false;
        }

        # удаляем магазин
        $res = $this->model->shopDelete($nShopID);
        if (!$res) {
            return false;
        }

        if ($aData['user_id']) {
            # удаляем связь пользователя с магазином
            Users::model()->userSave($aData['user_id'], array('shop_id' => 0));
            # отвязываем связанные с магазином объявления
            BBS::model()->itemsUnlinkShop($nShopID);
            # актуализируем счетчик заявок
            if ($aData['status'] == static::STATUS_REQUEST) {
                $this->updateRequestsCounter(-1);
            }
        }

        # удаляем логотип
        $this->shopLogo($nShopID)->delete(false, $aData['logo']);

        return true;
    }

    /**
     * Получение списка доступных причин жалобы на магазин
     * @return array
     */
    protected function getShopClaimReasons()
    {
        return array(
            1                 => _t('shops', 'Неверная рубрика'),
            2                 => _t('shops', 'Запрещенный товар/услуга'),
            8                 => _t('shops', 'Неверный адрес'),
            self::CLAIM_OTHER => _t('shops', 'Другое'),
        );
    }

    /**
     * Актуализация счетчика необработанных жалоб на магазины
     * @param integer|null $increment
     */
    public function claimsCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->claimsListing(array('viewed' => 0), true);
            config::save('shops_claims', $count, true);
        } else {
            config::saveCount('shops_claims', $increment, true);
        }
    }

    /**
     * Формирование текста описания жалобы, с учетом отмеченных причин
     * @param integer $nReasons битовое поле причин жалобы
     * @param string $sComment комментарий к жалобе
     * @return string
     */
    protected function getItemClaimText($nReasons, $sComment)
    {
        $reasons = $this->getShopClaimReasons();
        if (!empty($nReasons) && !empty($reasons)) {
            $res = array();
            foreach ($reasons as $rk => $rv) {
                if ($rk != self::CLAIM_OTHER && $rk & $nReasons) {
                    $res[] = $rv;
                }
            }
            if (($nReasons & self::CLAIM_OTHER) && !empty($sComment)) {
                $res[] = $sComment;
            }

            return join(', ', $res);
        }

        return '';
    }

    /**
     * Получение списка доступных типов для ссылок соц. сетей
     * @param boolean $bSelectOptions в формате HTML::selectOptions
     * @param integer $nSelectedID ID выбранного типа
     * @return array
     */
    public static function socialLinksTypes($bSelectOptions = false, $nSelectedID = 0)
    {
        $aTypes = array(
            self::SOCIAL_LINK_FACEBOOK      => array(
                'id'    => self::SOCIAL_LINK_FACEBOOK,
                'title' => _t('shops', 'Facebook'),
                'icon'  => 'fb'
            ),
            self::SOCIAL_LINK_VKONTAKTE     => array(
                'id'    => self::SOCIAL_LINK_VKONTAKTE,
                'title' => _t('shops', 'Вконтакте'),
                'icon'  => 'vk'
            ),
            self::SOCIAL_LINK_ODNOKLASSNIKI => array(
                'id'    => self::SOCIAL_LINK_ODNOKLASSNIKI,
                'title' => _t('shops', 'Одноклассники'),
                'icon'  => 'od'
            ),
            self::SOCIAL_LINK_GOOGLEPLUS    => array(
                'id'    => self::SOCIAL_LINK_GOOGLEPLUS,
                'title' => _t('shops', 'Google+'),
                'icon'  => 'gg'
            ),
            self::SOCIAL_LINK_YANDEX        => array(
                'id'    => self::SOCIAL_LINK_YANDEX,
                'title' => _t('shops', 'Yandex'),
                'icon'  => 'ya'
            ),
            self::SOCIAL_LINK_MAILRU        => array(
                'id'    => self::SOCIAL_LINK_MAILRU,
                'title' => _t('shops', 'Мой мир'),
                'icon'  => 'mm'
            ),
        );
        if ($bSelectOptions) {
            return HTML::selectOptions($aTypes, $nSelectedID, false, 'id', 'title');
        }

        return $aTypes;
    }

    /**
     * Работа со счетчиком кол-ва новых запросов на открытие / закрепление магазина
     * @param integer $nIncrement , пример: -2, -1, 1, 2
     * @param boolean $bJoin true - закрепление, false - открытие
     */
    public function updateRequestsCounter($nIncrement, $bJoin = false)
    {
        config::saveCount('shops_requests', $nIncrement, true);
        config::saveCount('shops_requests_' . ($bJoin ? 'join' : 'open'), $nIncrement, true);
    }

    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой пользователя
     * @param integer $nUserID ID пользователя
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     */
    public function onUserBlocked($nUserID, $bBlocked)
    {
        $aUserData = Users::model()->userData($nUserID, array('shop_id'));
        if (empty($aUserData['shop_id'])) {
            return;
        }

        if ($bBlocked) {
            # при блокировке пользователя -> блокируем его магазин
            $this->model->shopSave($aUserData['shop_id'], array(
                    'status_prev = status',
                    'status'         => self::STATUS_BLOCKED,
                    'blocked_reason' => 'Аккаунт пользователя заблокирован',
                )
            );
        } else {
            # при разблокировке -> разблокируем
            $this->model->shopSave($aUserData['shop_id'], array(
                    'status = (CASE status_prev WHEN ' . self::STATUS_BLOCKED . ' THEN ' . self::STATUS_NOT_ACTIVE . ' ELSE status_prev END)',
                    'status_prev' => self::STATUS_BLOCKED,
                    //'blocked_reason' => '', # оставляем последнюю причину блокировки
                )
            );
        }
    }

    /**
     * Метод обрабатывающий ситуацию с закреплением магазина за пользователем
     * @param integer $nUserID ID пользователя
     * @param integer $nShopID ID магазина
     */
    public function onUserShopCreated($nUserID, $nShopID)
    {
        # Привязываем магазин к пользователю
        Users::model()->userSave($nUserID, array('shop_id' => $nShopID));
        if (bff::adminPanel()) {
            Users::i()->userSessionUpdate($nUserID, array('shop_id' => $nShopID), false);
        } else {
            $this->security->updateUserInfo(array('shop_id' => $nShopID));
        }

        # Привязываем объявления пользователя к магазину
        # - при включенной премодерации - привязка выполняется на этапе одобрения заявки
        if (!static::premoderation() && BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP)) {
            BBS::model()->itemsLinkShop($nUserID, $nShopID);
        }
    }

    # --------------------------------------------------------
    # Активация услуг

    /**
     * Активация услуги/пакета услуг для Магазина
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги/пакета услуг
     * @param mixed $aSvcData данные об услуге(*)/пакете услуг или FALSE
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean true - успешная активация, false - ошибка активации
     */
    public function svcActivate($nItemID, $nSvcID, $aSvcData = false, array &$aSvcSettings = array())
    {
        if (!$nSvcID) {
            $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

            return false;
        }
        if (empty($aSvcData)) {
            $aSvcData = Svc::model()->svcData($nSvcID);
            if (empty($aSvcData)) {
                $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

                return false;
            }
        }

        # получаем данные о магазине
        if (empty($aShopData)) {
            $aShopData = $this->model->shopData($nItemID, array(
                    'id',
                    'status', # ID, статус
                    'svc', # битовое поле активированных услуг
                    'reg2_region',
                    'reg3_city', # ID региона(области), ID города
                    'svc_fixed_to', # дата окончания "Закрепления"
                    'svc_marked_to'
                )
            ); # дата окончания "Выделение"
        }

        # проверяем статус магазина
        if (empty($aShopData) || $aShopData['status'] != self::STATUS_ACTIVE) {
            $this->errors->set(_t('shops', 'Для указанного магазина невозможно активировать данную услугу'));

            return false;
        }

        # активируем пакет услуг
        if ($aSvcData['type'] == Svc::TYPE_SERVICEPACK) {
            $aServices = (isset($aSvcData['svc']) ? $aSvcData['svc'] : array());
            if (empty($aServices)) {
                $this->errors->set(_t('shops', 'Неудалось активировать пакет услуг'));

                return false;
            }
            $aServicesID = array();
            foreach ($aServices as $v) {
                $aServicesID[] = $v['id'];
            }
            $aServices = Svc::model()->svcData($aServicesID, array('*'));
            if (empty($aServices)) {
                $this->errors->set(_t('shops', 'Неудалось активировать пакет услуг'));

                return false;
            }

            # проходимся по услугам, входящим в пакет
            # активируем каждую из них
            $nSuccess = 0;
            foreach ($aServices as $k => $v) {
                # при пакетной активации, период действия берем из настроек пакета услуг
                $v['cnt'] = $aSvcData['svc'][$k]['cnt'];
                if (!empty($v['cnt'])) {
                    $v['period'] = $v['cnt'];
                }
                $res = $this->svcActivateService($nItemID, $v['id'], $v, $aShopData, true, $aSvcSettings);
                if ($res) {
                    $nSuccess++;
                }
            }

            return true;
        } else {
            # активируем услугу
            return $this->svcActivateService($nItemID, $nSvcID, $aSvcData, $aShopData, false, $aSvcSettings);
        }
    }

    /**
     * Активация услуги для Магазина
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги
     * @param mixed $aSvcData данные об услуге(*) или FALSE
     * @param mixed $aShopData @ref данные о магазине или FALSE
     * @param boolean $bFromPack услуга активируется из пакета услуг
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean|integer
     *      1, true - услуга успешно активирована,
     *      2 - услуга успешно активирована без необходимости списывать средства со счета пользователя
     *      false - ошибка активации услуги
     */
    protected function svcActivateService($nItemID, $nSvcID, $aSvcData = false, &$aShopData = false, $bFromPack = false, array &$aSvcSettings = array())
    {
        if (empty($nItemID) || empty($aShopData) || empty($nSvcID)) {
            $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

            return false;
        }
        if (empty($aSvcData)) {
            $aSvcData = Svc::model()->svcData($nSvcID);
            if (empty($aSvcData)) {
                $this->errors->set(_t('svc', 'Неудалось активировать услугу'));

                return false;
            }
        }

        $nSvcID = intval($nSvcID);
        $aShopData['svc'] = intval($aShopData['svc']);

        # период действия услуги (в днях)
        # > при пакетной активации, период действия берется из настроек активируемого пакета услуг
        $nPeriodDays = (!empty($aSvcData['period']) ? intval($aSvcData['period']) : 1);
        if ($nPeriodDays < 1) {
            $nPeriodDays = 1;
        }

        $sNow = $this->db->now();
        $aUpdate = array();
        $mResult = true;
        switch ($nSvcID) {
            case self::SERVICE_FIX: # Закрепление
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aShopData['svc'] & $nSvcID) ? strtotime($aShopData['svc_fixed_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                # помечаем срок действия услуги
                $aUpdate['svc_fixed_to'] = date('Y-m-d H:i:s', $to);
                # ставим выше среди закрепленных
                $aUpdate['svc_fixed_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aShopData['svc'] | $nSvcID);
            }
                break;
            case self::SERVICE_MARK: # Выделение
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aShopData['svc'] & $nSvcID) ? strtotime($aShopData['svc_marked_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                # помечаем срок действия услуги
                $aUpdate['svc_marked_to'] = date('Y-m-d H:i:s', $to);
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aShopData['svc'] | $nSvcID);
            }
                break;
        }

        $res = $this->model->shopSave($nItemID, $aUpdate);
        if (!empty($res)) {
            # актуализируем данные о магазине для корректной пакетной активации услуг
            if (!empty($aUpdate)) {
                foreach ($aUpdate as $k => $v) {
                    $aShopData[$k] = $v;
                }
            }

            return $mResult;
        }

        return false;
    }

    /**
     * Формируем описание счета активации услуги (пакета услуг)
     * @param integer $nItemID ID магазина
     * @param integer $nSvcID ID услуги
     * @param array|boolean $aData false или array('item'=>array('id',...),'svc'=>array('id','type'))
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return string
     */
    public function svcBillDescription($nItemID, $nSvcID, $aData = false, array &$aSvcSettings = array())
    {
        $aSvc = (!empty($aData['svc']) ? $aData['svc'] :
            Svc::model()->svcData($nSvcID));

        $aShop = (!empty($aData['item']) ? $aData['item'] :
            $this->model->shopData($nItemID, array('id', 'title', 'link')));

        $sLink = (!empty($aShop['link']) ? 'href="' . $aShop['link'] . '" class="j-bills-shops-item-link" data-item="' . $nItemID . '"' : 'href=""');

        if ($aSvc['type'] == Svc::TYPE_SERVICE) {
            switch ($nSvcID) {
                case self::SERVICE_FIX:
                {
                    return _t('shops', 'Закрепление магазина<br /><small><a [link]>[title]</a></small>', array(
                            'link'  => $sLink,
                            'title' => $aShop['title']
                        )
                    );
                }
                    break;
                case self::SERVICE_MARK:
                {
                    return _t('shops', 'Выделение магазина<br /><small><a [link]>[title]</a></small>', array(
                            'link'  => $sLink,
                            'title' => $aShop['title']
                        )
                    );
                }
                    break;
            }
        } else {
            if ($aSvc['type'] == Svc::TYPE_SERVICEPACK) {
                return _t('shops', 'Пакет услуг "[pack]" <br /><small><a [link]>[title]</a></small>',
                    array('pack' => $aSvc['title'], 'link' => $sLink, 'title' => $aShop['title'])
                );
            }
        }
    }

    /**
     * Инициализация компонента обработки иконок услуг/пакетов услуг ShopsSvcIcon
     * @param mixed $nSvcID ID услуги / пакета услуг
     * @return ShopsSvcIcon component
     */
    public static function svcIcon($nSvcID = false)
    {
        static $i;
        if (!isset($i)) {
            require_once static::i()->module_dir . 'shops.svc.icon.php';
            $i = new ShopsSvcIcon();
        }
        $i->setRecordID($nSvcID);

        return $i;
    }

    /**
     * Период: 1 раз в час
     */
    public function svcCron()
    {
        if (!bff::cron()) {
            return;
        }

        $this->model->svcCron();
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        # услуги (services, packs)
        $svc = Svc::model();
        $data = $svc->svcListing(0, $this->module_name, array(), false);
        foreach ($data as $v) {
            if (!empty($v['settings'])) {
                $langFields = ($v['type'] == Svc::TYPE_SERVICE ?
                    $this->model->langSvcServices :
                    $this->model->langSvcPacks);

                foreach ($langFields as $kk => $vv) {
                    if (isset($v['settings'][$kk][$from])) {
                        $v['settings'][$kk][$to] = $v['settings'][$kk][$from];
                    }
                }
                $svc->svcSave($v['id'], array('settings' => $v['settings']));
            }
        }
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function onGeoUrlTypeChanged($prevType, $nextType)
    {
        $this->model->shopsGeoUrlTypeChanged($prevType, $nextType);
    }

    /**
     * Кол-во доступных телефонов (0 - без ограничений)
     * @return integer
     */
    public static function phonesLimit()
    {
        return config::sys('shops.phones.limit', 5);
    }

    /**
     * Кол-во доступных ссылок соц.сетей (0 - без ограничений)
     * @return integer
     */
    public static function socialLinksLimit()
    {
        return config::sys('shops.social.limit', 5);
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('shop'.DS.'logo', 'images') => 'dir', # изображения магазинов
            bff::path('shop'.DS.'cats', 'images') => 'dir', # изображения категорий
            bff::path('svc', 'images')   => 'dir', # изображения платных услуг
            bff::path('tmp', 'images')   => 'dir', # tmp
        ));
    }
}