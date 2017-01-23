<?php

use bff\db\Dynprops;

# cookie-ключ для избранных ОБ
define('BBS_FAV_COOKIE', config::sys('db.prefix') . 'fav');

abstract class BBSBase extends Module
implements IModuleWithSvc
{
    /** @var BBSModel */
    public $model = null;
    public $securityKey = 'f59d7393ccf683db2a63b795f75cd9e3';

    # Статус объявления
    const STATUS_NOTACTIVATED = 1; # не активировано
    const STATUS_PUBLICATED = 3; # опубликованное
    const STATUS_PUBLICATED_OUT = 4; # истекший срок публикации
    const STATUS_BLOCKED = 5; # заблокированное

    # Настройки категорий
    const CATS_ROOTID = 1; # ID "Корневой категории" (изменять не рекомендуется)
    const CATS_TYPES_EX = false; # Использовать расширенные типы категорий (TABLE_BBS_CATEGORIES_TYPES)
    const CATS_PHOTOS_MIN = 8; # Минимально допустимое кол-во фотографий
    const CATS_PHOTOS_MAX = 12; # Максимально допустимое кол-во фотографий

    /**
     * Максимальная глубина вложенности категорий.
     *  - при изменении, не забыть привести в соответствие столбцы cat_id(1-n) в таблице TABLE_BBS_ITEMS
     *  - минимальное значение = 1
     */
    const CATS_MAXDEEP = 4;

    # Тип размещения объявления
    const TYPE_OFFER = 0; # Тип размещения: "предлагаю"
    const TYPE_SEEK = 1; # Тип размещения: "ищу"

    # Тип владельца объявления
    const OWNER_PRIVATE = 1; # Частное лицо (Владелец, Собственник)
    const OWNER_BUSINESS = 2; # Бизнес (Агенство, Посредник)

    # Тип пользователя, публикующего объявление
    const PUBLISHER_USER = 'user';
    const PUBLISHER_SHOP = 'shop';
    const PUBLISHER_USER_OR_SHOP = 'user-or-shop';
    const PUBLISHER_USER_TO_SHOP = 'user-to-shop';

    # Тип доп. модификаторов цены
    const PRICE_EX_PRICE = 0; # Базовая настройка цены
    const PRICE_EX_MOD = 1; # Модификатор (Торг, По результатам собеседования)
    const PRICE_EX_EXCHANGE = 2; # Обмен
    const PRICE_EX_FREE = 4; # Бесплатно (Даром)

    # ID Услуг
    const SERVICE_UP = 1; # поднятие
    const SERVICE_MARK = 2; # выделение
    const SERVICE_FIX = 4; # закрепление
    const SERVICE_PREMIUM = 8; # премиум
    const SERVICE_PRESS = 16; # в прессу
    const SERVICE_QUICK = 32; # срочно

    # Публикация объявления в прессе
    const PRESS_ON = true; # доступна ли услуга "публикации объявления в прессе"
    const PRESS_STATUS_PAYED = 1; # статус: публикация в прессе оплачена
    const PRESS_STATUS_PUBLICATED = 2; # статус: опубликовано в прессе
    const PRESS_STATUS_PUBLICATED_EARLIER = 3; # раннее опубликованные (только для фильтра)

    # Жалобы
    const CLAIM_OTHER = 1024; # тип жалобы: "Другое"

    # Типы отображения списка
    const LIST_TYPE_LIST    = 1; # строчный вид
    const LIST_TYPE_GALLERY = 2; # галерея
    const LIST_TYPE_MAP     = 3; # карта
    
     # Доступность импорта объявлений
    const IMPORT_ACCESS_ADMIN  = 0; # администратор
    const IMPORT_ACCESS_CHOSEN = 1; # избранные магазины
    const IMPORT_ACCESS_ALL    = 2; # все

    # Лимитирование объявлений
    const LIMITS_NONE = 0;     # без ограничений
    const LIMITS_COMMON = 1;   # общий лимит
    const LIMITS_CATEGORY = 2; # в категорию

    public function init()
    {
        parent::init();

        $this->module_title = 'Объявления';

        bff::autoloadEx(array(
                'BBSItemImages'   => array('app', 'modules/bbs/bbs.item.images.php'),
                'BBSItemsImport'  => array('app', 'modules/bbs/bbs.items.import.php'),
                'BBSCategoryIcon' => array('app', 'modules/bbs/bbs.category.icon.php'),
            )
        );
        # инициализируем модуль дин. свойств
        if (strpos(bff::$event, 'dynprops') === 0) {
            $this->dp();
        }
    }

    public function sendmailTemplates()
    {
        $aTemplates = array(
            'bbs_item_activate'      => array(
                'title'       => 'Объявления: активация объявления',
                'description' => 'Уведомление, отправляемое <u>незарегистрированному пользователю</u> после добавления объявления',
                'vars'        => array(
                    '{name}'          => 'Имя',
                    '{email}'         => 'Email',
                    '{activate_link}' => 'Ссылка активации объявления'
                )
            ,
                'impl'        => true,
                'priority'    => 10,
            ),
            'bbs_item_sendfriend'    => array(
                'title'       => 'Объявления: отправить другу',
                'description' => 'Уведомление, отправляемое по указанному email адресу',
                'vars'        => array(
                    '{item_id}'    => 'ID объявления',
                    '{item_title}' => 'Заголовок объявления',
                    '{item_link}'  => 'Ссылка на объявление'
                )
            ,
                'impl'        => true,
                'priority'    => 11,
            ),
            'bbs_item_deleted'       => array(
                'title'       => 'Объявления: удаление объявления',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае удаления объявления модератором',
                'vars'        => array(
                    '{name}'       => 'Имя',
                    '{email}'      => 'Email',
                    '{item_id}'    => 'ID объявления',
                    '{item_title}' => 'Заголовок объявления',
                    '{item_link}'  => 'Ссылка на объявление'
                )
            ,
                'impl'        => true,
                'priority'    => 14,
            ),
            'bbs_item_photo_deleted' => array(
                'title'       => 'Объявления: удаление фотографии объявления',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае удаления фотографии объявления модератором',
                'vars'        => array('{name}'       => 'Имя',
                                       '{email}'      => 'Email',
                                       '{item_id}'    => 'ID объявления',
                                       '{item_title}' => 'Заголовок объявления',
                                       '{item_link}'  => 'Ссылка на объявление'
                )
            ,
                'impl'        => true,
                'priority'    => 15,
            ),
            'bbs_item_blocked'       => array(
                'title'       => 'Объявления: блокировка объявления',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае блокировке объявления модератором',
                'vars'        => array(
                    '{name}'           => 'Имя',
                    '{email}'          => 'Email',
                    '{item_id}'        => 'ID объявления',
                    '{item_title}'     => 'Заголовок объявления',
                    '{item_link}'      => 'Ссылка на объявление',
                    '{blocked_reason}' => 'Причина блокировки'
                )
            ,
                'impl'        => true,
                'priority'    => 16,
            ),
            'bbs_item_unpublicated_soon'       => array(
                'title'       => 'Объявления: уведомление о завершении публикации',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> с оповещением о завершении публикации его объявления',
                'vars'        => array(
                    '{item_id}'        => 'ID объявления',
                    '{item_title}'     => 'Заголовок объявления',
                    '{item_link}'      => 'Ссылка на объявление',
                    '{days_in}'        => 'Кол-во дней до завершения публикации',
                    '{publicate_link}' => 'Ссылка продления публикации',
                    '{svc_up}'         => 'Ссылка "поднять"',
                    '{svc_quick}'      => 'Ссылка "сделать срочным"',
                    '{svc_fix}'        => 'Ссылка "закрепить"',
                    '{svc_mark}'       => 'Ссылка "выделить"',
                    '{svc_press}'      => 'Ссылка "печать в прессе"',
                    '{svc_premium}'    => 'Ссылка "премиум"',
                )
            ,
                'impl'        => true,
                'priority'    => 17,
            ),
        );
        if( ! static::PRESS_ON){
            unset($aTemplates['bbs_item_unpublicated_soon']['vars']['{svc_press}']);
        }

        if (static::commentsEnabled()) {
            $aTemplates['bbs_item_comment'] = array(
                'title'       => 'Объявления: новый комментарий к объявлению',
                'description' => 'Уведомление, отправляемое <u>пользователю</u> в случае нового комментария к объявлению',
                'vars'        => array('{name}'       => 'Имя',
                                       '{email}'      => 'Email',
                                       '{item_id}'    => 'ID объявления',
                                       '{item_title}' => 'Заголовок объявления',
                                       '{item_link}'  => 'Ссылка на объявление',
                )
            ,
                'impl'        => true,
                'priority'    => 17,
            );
        }

        return $aTemplates;
    }

    /**
     * Shortcut
     * @return BBS
     */
    public static function i()
    {
        return bff::module('bbs');
    }

    /**
     * Shortcut
     * @return BBSModel
     */
    public static function model()
    {
        return bff::model('bbs');
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
        switch ($key) {
            # поиск объявлений (geo)
            case 'items.search':
            {
                # формируем ссылку с учетом указанной области (region), [города (city)]
                # либо с учетом текущих настроек фильтра по региону
                return Geo::url($opts, $dynamic) . 'search/' . (!empty($opts['keyword']) ? $opts['keyword'] . '/' : '');
                break;
            }
            # форма добавление объявления
            case 'item.add':
                return $base . '/item/add' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # форма редактирования объявления
            case 'item.edit':
                return $base . '/item/edit?' . http_build_query($opts);
                break;
            # страница продвижения объявления
            case 'item.promote':
                return $base . '/item/promote' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # список моих объявлений
            case 'my.items':
                return $base . '/cabinet/items' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # импорт
            case 'my.import':
                return $base . '/cabinet/import' . (!empty($opts) ? '?' . http_build_query($opts) : '');
                break;
            # список избранных объявлений
            case 'my.favs':
                return $base . '/cabinet/favs';
                break;
            # ссылка активации объявления
            case 'item.activate':
                return $base . '/item/activate?' . http_build_query($opts);
                break;
        }
    }

    /**
     * Описание seo шаблонов страниц
     * @return array
     */
    public function seoTemplates()
    {
        $templates = array(
            'pages'  => array(
                'search'          => array(
                    't'      => 'Поиск (все категории)',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(),
                ),
                'search-category' => array(
                    't'       => 'Поиск в категории',
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
                    'inherit' => true,
                ),
                'view'            => array(
                    't'      => 'Просмотр объявления',
                    'list'   => false,
                    'i'      => true,
                    'macros' => array(
                        'title'              => array('t' => 'Заголовок'),
                        'description'        => array('t' => 'Описание (до 150 символов)'),
                        'price'              => array('t' => 'Стоимость'),
                        'city'               => array('t' => 'Город'),
                        'region'             => array('t' => 'Область'),
                        'country'            => array('t' => 'Страна'),
                        'category'           => array('t' => 'Текущая категория объявления'),
                        'categories'         => array('t' => 'Название всех категорий'),
                        'categories.reverse' => array('t' => 'Название всех категорий<br />(обратный порядок)'),
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
                'add'             => array(
                    't'             => 'Добавление объявления',
                    'list'          => false,
                    'i'             => true,
                    'macros'        => array(),
                    'macros.ignore' => array('region'),
                ),
                'user-items'      => array(
                    't'      => 'Объявления пользователя',
                    'list'   => true,
                    'i'      => true,
                    'macros' => array(
                        'name'   => array('t' => 'Имя пользователя'),
                        'country' => array('t' => 'Страна пользователя'),
                        'region' => array('t' => 'Регион пользователя'),
                    )
                ),
            ),
            'macros' => array(
                'region' => array('t' => 'Регион поиска'),
            ),
        );

        return $templates;
    }

    /**
     * Включена ли премодерация объявлений
     * @return bool
     */
    public static function premoderation()
    {
        return (bool)config::sys('bbs.premoderation', TYPE_BOOL);
    }

    /**
     * Получение настройки: доступный тип пользователя публикующего объявление
     * @param array|string|null $type проверяемый тип
     * @return mixed
     */
    public static function publisher($type = null)
    {
        $sys = config::sys('bbs.publisher', static::PUBLISHER_USER);
        if ($sys === static::PUBLISHER_SHOP && !bff::moduleExists('shops')) {
            $sys = static::PUBLISHER_USER;
        }
        if (empty($type)) {
            return $sys;
        }

        return (is_array($type) ? in_array($sys, $type, true) : ($type === $sys));
    }

    /**
     * Проверка типа публикующего пользователя
     * @param integer $shopID ID магазина пользователя, публикующего объявление
     * @param string $shopUseField название поля, отвечающего за использование магазина для публикации
     * @return integer итоговый ID магазина, закрепляемый за публикуемым объявлением
     */
    public function publisherCheck($shopID, $shopUseField = 'shop')
    {
        switch (static::publisher()) {
            # только пользователь (добавление объявлений доступно сразу, объявления размещаются только от "частного лица")
            case static::PUBLISHER_USER:
            {
                return 0;
            }
            break;
            # только магазин (добавление объявлений доступно после открытия магазина, только от "магазина")
            case static::PUBLISHER_SHOP:
            {
                if (!$shopID) {
                    if (bff::adminPanel()) {
                        $this->errors->set('Указанный пользователь не создал магазин', 'email');
                    } else {
                        $this->errors->reloadPage();
                    }

                    return 0;
                }
            }
            break;
            # пользователь и магазин (добавление объявлений доступно сразу только от "частного лица", после открытия магазина - объявления размещаются только от "магазина")
            case static::PUBLISHER_USER_TO_SHOP:
            {
                return ($shopID && bff::moduleExists('shops') ? $shopID : 0);
            }
            break;
            # пользователь или магазин (добавление объявлений доступно сразу только от "частного лица",
            # после открытия магазина - объявления размещаются от "частного лица" или "магазина")
            case static::PUBLISHER_USER_OR_SHOP:
            {
                $byShop = $this->input->postget($shopUseField, TYPE_BOOL);
                if (!$byShop || !$shopID || !bff::moduleExists('shops')) {
                    return 0;
                }
            }
            break;
        }

        return $shopID;
    }

    /**
     * Инициализация компонента работы с дин. свойствами
     * @return \bff\db\Dynprops объект
     */
    public function dp()
    {
        static $dynprops = null;
        if (isset($dynprops)) {
            return $dynprops;
        }

        # подключаем "Динамические свойства"
        $dynprops = new Dynprops('cat_id',
            TABLE_BBS_CATEGORIES,
            TABLE_BBS_CATEGORIES_DYNPROPS,
            TABLE_BBS_CATEGORIES_DYNPROPS_MULTI,
            1); # полное наследование
        $this->attachComponent('dynprops', $dynprops);

        $dynprops->setSettings(array(
                'module_name'          => $this->module_name,
                'typesAllowed'         => array(
                    Dynprops::typeCheckboxGroup,
                    Dynprops::typeRadioGroup,
                    Dynprops::typeRadioYesNo,
                    Dynprops::typeCheckbox,
                    Dynprops::typeSelect,
                    Dynprops::typeInputText,
                    Dynprops::typeTextarea,
                    Dynprops::typeNumber,
                    Dynprops::typeRange,
                ),
                'langs'                => $this->locale->getLanguages(false),
                'langText'             => array(
                    'yes'    => _t('bbs', 'Да'),
                    'no'     => _t('bbs', 'Нет'),
                    'all'    => _t('bbs', 'Все'),
                    'select' => _t('bbs', 'Выбрать'),
                ),
                'cache_method'         => 'BBS_dpSettingsChanged',
                'typesAllowedParent'   => array(Dynprops::typeSelect),
                /**
                 * Настройки доступных int/text столбцов динамических свойств для хранения числовых/тестовых данных.
                 * При изменении, не забыть привести в соответствие столбцы f(1-n) в таблице TABLE_BBS_ITEMS
                 */
                'datafield_int_last'   => 15,
                'datafield_text_first' => 16,
                'datafield_text_last'  => 20,
                'searchRanges'         => true,
                'cacheKey'             => false,
            )
        );
        $dynprops->extraSettings(array(
                'in_seek'   => array('title' => 'заполнять в объявлениях типа "Ищу"', 'input' => 'checkbox'),
                'num_first' => array('title' => 'отображать перед наследуемыми (первым)', 'input' => 'checkbox'),
            )
        );
        $dynprops->setCurrentLanguage(LNG);

        return $dynprops;
    }

    /**
     * Получаем дин. свойства категории
     * @param integer $nCategoryID ID категории
     * @param boolean $bResetCache обнулить кеш
     * @return mixed
     */
    public function dpSettings($nCategoryID, $bResetCache = false)
    {
        if ($nCategoryID <= 0) {
            return array();
        }

        $cache = Cache::singleton('bbs-dp', 'file');
        $cacheKey = 'cat-dynprops-' . LNG . '-' . $nCategoryID;
        if ($bResetCache) {
            # сбрасываем кеш настроек дин. свойств категории
            return $cache->delete($cacheKey);
        } else {
            if (($aSettings = $cache->get($cacheKey)) === false) { # ищем в кеше
                $aSettings = $this->dp()->getByOwner($nCategoryID, true, true, false);
                $cache->set($cacheKey, $aSettings); # сохраняем в кеш
            }

            return $aSettings;
        }
    }

    /**
     * Метод вызываемый модулем \bff\db\Dynprops, в момент изменения настроек дин. свойств категории
     * @param integer $nCategoryID id категории
     * @param integer $nDynpropID id дин.свойства
     * @param string $sEvent событие, генерирующее вызов метода
     * @return mixed
     */
    public function dpSettingsChanged($nCategoryID, $nDynpropID, $sEvent)
    {
        if (empty($nCategoryID)) {
            return false;
        }
        $this->dpSettings($nCategoryID, true);
    }

    /**
     * Формирование SQL запроса для сохранения дин.свойств
     * @param integer $nCategoryID ID подкатегории
     * @param string $sFieldname ключ в $_POST массиве
     * @return array
     */
    public function dpSave($nCategoryID, $sFieldname = 'd')
    {
        $aData = $this->input->post($sFieldname, TYPE_ARRAY);

        $aDynpropsData = array();
        foreach ($aData as $props) {
            foreach ($props as $id => $v) {
                $aDynpropsData[$id] = $v;
            }
        }

        $aDynprops = $this->dp()->getByID(array_keys($aDynpropsData), true);

        return $this->dp()->prepareSaveDataByID($aDynpropsData, $aDynprops, 'update', true);
    }

    /**
     * Формирование формы редактирования/фильтра дин.свойств
     * @param integer $nCategoryID ID категории
     * @param boolean $bSearch формирование формы поиска
     * @param array|boolean $aData данные или FALSE
     * @param array $aExtra доп.данные
     * @return string HTML template
     */
    protected function dpForm($nCategoryID, $bSearch = true, $aData = array(), $aExtra = array())
    {
        if (empty($nCategoryID)) {
            return '';
        }

        if (bff::adminPanel()) {
            if ($bSearch) {
                $aForm = $this->dp()->form($nCategoryID, $aData, true, true, 'd', 'search.inline', false, $aExtra);
            } else {
                $aForm = $this->dp()->form($nCategoryID, $aData, true, false, 'd', 'form.table', false, $aExtra);
            }
        } else {
            if (!$bSearch) {
                $aForm = $this->dp()->form($nCategoryID, $aData, true, false, 'd', 'item.form.dp', $this->module_dir_tpl, $aExtra);
            }
        }

        return (!empty($aForm['form']) ? $aForm['form'] : '');
    }

    /**
     * Отображение дин. свойств
     * @param integer $nCategoryID ID категории
     * @param array $aData данные
     */
    public function dpView($nCategoryID, $aData)
    {
        $sKey = 'd';
        if (!bff::adminPanel()) {
            $aForm = $this->dp()->form($nCategoryID, $aData, true, false, $sKey, 'item.view.dp', $this->module_dir_tpl);
        } else {
            $aForm = $this->dp()->form($nCategoryID, $aData, true, false, $sKey, 'view.table');
        }

        return (!empty($aForm['form']) ? $aForm['form'] : '');
    }

    /**
     * Подготовка запроса полей дин. свойств на основе значений "cache_key"
     * @param string $sPrefix префикс таблицы, например "I."
     * @param int $nCategoryID ID категории
     * @return string
     */
    public function dpPrepareSelectFieldsQuery($sPrefix = '', $nCategoryID = 0)
    {
        if (empty($nCategoryID) || $nCategoryID<0) {
            return '';
        }

        $fields = array();
        foreach($this->dpSettings($nCategoryID) as $v)
        {
            $f = $sPrefix.$this->dp()->datafield_prefix.$v['data_field'];
            if (!empty($v['cache_key'])) {
               $f .= ' as `'.$v['cache_key'].'`';
            }
            $fields[] = $f;
        }
        return (!empty($fields) ? join(', ', $fields) : '');
    }

    /**
     * Инициализация компонента BBSItemImages
     * @param integer $nItemID ID объявления
     * @return BBSItemImages component
     */
    public function itemImages($nItemID = 0)
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemImages();
        }
        $i->setRecordID($nItemID);

        return $i;
    }


    /**
     * Инициализация компонента BBSItemVideo
     * @return BBSItemVideo component
     */
    public function itemVideo()
    {
        static $i;
        if (!isset($i)) {
            include_once $this->module_dir . 'bbs.item.video.php';
            $i = new BBSItemVideo();
        }

        return $i;
    }

    /**
     * Инициализация компонента BBSItemComments
     * @return BBSItemComments component
     */
    public function itemComments()
    {
        static $i;
        if (!isset($i)) {
            include_once $this->module_dir . 'bbs.item.comments.php';
            $i = new BBSItemComments();
        }

        return $i;
    }

    /**
     * Включены ли комментарии
     * @return bool
     */
    public static function commentsEnabled()
    {
        return (bool)config::sys('bbs.comments', TYPE_BOOL);
    }

    /**
     * Инициализация компонента BBSItemsSearchSphinx
     * @return BBSItemsSearchSphinx component
     */
    public function itemsSearchSphinx()
    {
        static $i;
        if (!isset($i)) {
            include_once $this->module_dir . 'bbs.items.search.sphinx.php';
            $i = new BBSItemsSearchSphinx();
        }

        return $i;
    }

    /**
     * Инициализация компонента импорта/экспорта объявлений
     * @return BBSItemsImport component
     */
    public function itemsImport()
    {
        static $i;
        if (!isset($i)) {
            $i = new BBSItemsImport();
            $i->init();
        }

        return $i;
    }

    /**
     * Доступна ли возможность редактирования категории при редактировании ОБ
     * @return bool
     */
    public static function categoryFormEditable()
    {
        return (bool)config::sys('bbs.form.category.edit', TYPE_BOOL);
    }

    /**
     * Инициализация компонента обработки иконок основных категорий BBSCategoryIcon
     * @param mixed $nCategoryID ID категории
     * @return BBSCategoryIcon component
     */
    public static function categoryIcon($nCategoryID = false)
    {
        static $i;
        if (!isset($i)) {
            require_once static::i()->module_dir . 'bbs.category.icon.php';
            $i = new BBSCategoryIcon();
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
        $aData = $this->model->catParentsData($nCategoryID, array('id', 'title', 'keyword', 'breadcrumb'));
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                # ссылка
                $aOptions['keyword'] = $v['keyword'];
                $v['link'] = static::url('items.search', $aOptions);
                # название
                //$v['title'] = $v['title'];
                # активируем
                $v['active'] = ($v['id'] == $nCategoryID);
            }
            unset($v);
        } else {
            if ($sMethodName == 'search') {
                $aData = array(array('id' => 0, 'breadcrumb' => _t('search', 'Объявления'), 'active' => true));
            } else {
                $aData = array();
            }
        }

        return $aData;
    }

    /**
     * Подготовка данных списка ОБ
     * @param array $aItems @ref данные о найденных ОБ
     * @param integer $nListType тип списка (BBS::LIST_TYPE_)
     * @param integer $nNumStart изначальный порядковый номер
     */
    protected function itemsListPrepare(array &$aItems, $nListType, $nNumStart = 1)
    {
        # формируем URL изображений
        $aImageSize = array(
            self::LIST_TYPE_LIST    => array('sz' => BBSItemImages::szSmall, 'field' => 'img_s'),
            self::LIST_TYPE_GALLERY => array('sz' => BBSItemImages::szMedium, 'field' => 'img_m'),
            self::LIST_TYPE_MAP     => array('sz' => BBSItemImages::szSmall, 'field' => 'img_s'),
        );
        if (!isset($aImageSize[$nListType])) {
            $nListType = key($aImageSize);
        }
        $aImageSize = $aImageSize[$nListType];
        $sImageDefault = $this->itemImages()->urlDefault($aImageSize['sz']);

        $i = $nNumStart;
        foreach ($aItems as &$v) {
            # порядковый номер (для карты)
            $v['num'] = $i++;
            # подставляем заглушку для изображения
            if (!$v['imgs']) {
                $v[$aImageSize['field']] = $sImageDefault;
            }
            # форматируем дату публикации
            $v['publicated'] = tpl::datePublicated($v['publicated']);
        }
        unset($v);
    }

    /**
     * Получаем данные о категории для формы добавления/редактирования объявления
     * @param int $nCategoryID ID категории
     * @param array $aItemData параметры объявления
     * @param array $aFieldsExtra дополнительно необходимые данные о категории
     * @return array
     */
    protected function itemFormByCategory($nCategoryID, $aItemData = array(), $aFieldsExtra = array())
    {
        # получаем данные о категории:
        $aFields = array(
            'id',
            'pid',
            'addr',
            'photos',
            'subs',
            'price',
            'price_sett',
            'seek',
            'type_offer_form',
            'type_seek_form',
            'owner_business',
            'owner_private_form',
            'owner_business_form'
        );
        if (!empty($aFieldsExtra)) {
            $aFields = array_merge($aFields, $aFieldsExtra);
            $aFields = array_unique($aFields);
        }
        $aData = $this->model->catDataByFilter(array('id' => $nCategoryID), $aFields);
        if (empty($aData)) {
            return array();
        }

        if ($aData['subs'] > 0) {
            # есть подкатегории => формируем список подкатегорий
            if (bff::adminPanel()) {
                $aData['cats'] = $this->model->catSubcatsData($nCategoryID, array('sel' => 0, 'empty' => 'Выбрать'));
            }
            $aData['types'] = false;
        } else {
            # формируем форму дин. свойств:
            $aData['dp'] = $this->dpForm($nCategoryID, false, $aItemData);
            # формируем список типов:
            if (self::CATS_TYPES_EX) {
                $aData['types'] = $this->model->cattypesByCategory($nCategoryID);
            } else {
                $aData['types'] = $this->model->cattypesSimple($aData, false);
            }
            if (empty($aData['types'])) {
                $aData['types'] = false;
            }
        }

        $aData['edit'] = !empty($aItemData);
        # корректируем необходимые данные объявления
        $aData['item'] = $this->input->clean_array($aItemData, array(
                'cat_type'   => TYPE_UINT,
                'price'      => TYPE_PRICE,
                'price_curr' => TYPE_UINT,
                'price_ex'   => TYPE_UINT,
                'owner_type' => TYPE_UINT,
            )
        );
        if (bff::adminPanel()) {
            $aData['form'] = $this->viewPHP($aData, 'admin.form.category');
        } else {
            if (!$aData['edit']) {
                $aData['item']['price'] = '';
            }
            $aData['form'] = $this->viewPHP($aData, 'item.form.cat.form');
            $aData['owner'] = $this->viewPHP($aData, 'item.form.cat.owner');
        }

        return $aData;
    }

    /**
     * Получаем ID объявлений, добавленных текущим пользователем в избранные
     * @param integer $nUserID ID пользователя или 0
     * @param integer $bOnlyCounter только счетчик кол-ва
     * @return array|integer ID избранных объявлений или только счетчик
     */
    public function getFavorites($nUserID = 0, $bOnlyCounter = false)
    {
        if ($nUserID) {
            # для авторизованного => достаем из базы
            $aItemsID = $this->model->itemsFavData($nUserID);
            if (empty($aItemsID)) {
                $aItemsID = array();
            }
        } else {
            # для неавторизованного => достаем из куков
            $itemsCookie = $this->input->cookie(BBS_FAV_COOKIE, TYPE_STR);
            if (!empty($itemsCookie)) {
                $aItemsID = explode('.', $itemsCookie);
                $this->input->clean($aItemsID, TYPE_ARRAY_UINT);
            } else {
                $aItemsID = array();
            }
        }

        return ($bOnlyCounter ? sizeof($aItemsID) : $aItemsID);
    }

    /**
     * Пересохраняем избранные ОБ пользователя($nUserID) из куков и БД
     * @param integer $nUserID ID пользователя
     */
    public function saveFavoritesToDB($nUserID)
    {
        do {
            if (empty($nUserID)) {
                break;
            }
            # переносим избранные ОБ из куков в БД
            $itemsCookie = $this->getFavorites(0);
            if (empty($itemsCookie)) {
                break;
            }

            $itemsExists = $this->getFavorites($nUserID);

            # пропускаем уже существующие в БД
            if (!empty($itemsExists)) {
                $itemsNew = array();
                foreach ($itemsCookie as $id) {
                    if (!in_array($id, $itemsExists)) {
                        $itemsNew[] = $id;
                    }
                }
            } else {
                $itemsNew = $itemsCookie;
            }

            if (!empty($itemsNew)) {
                # сохраняем
                $res = $this->model->itemsFavSave($nUserID, $itemsNew);

                # удаляем из куков
                if (!empty($res)) {
                    Request::deleteCOOKIE(BBS_FAV_COOKIE);
                }

                # обновляем счетчик избранных пользователя
                Users::model()->userSave($nUserID, false, array(
                        'items_fav' => sizeof($itemsExists) + sizeof($itemsNew)
                    )
                );
            }

        } while (false);
    }

    /**
     * Проверяем, находится ли ОБ в избранных
     * @param integer $nItemID ID объявления
     * @param integer $nUserID ID пользователя или 0
     * @return bool true - избранное, false - нет
     */
    public function isFavorite($nItemID, $nUserID)
    {
        if (empty($nItemID)) {
            return false;
        }
        $aFavorites = $this->getFavorites($nUserID);

        return in_array($nItemID, $aFavorites);
    }

    /**
     * Получение списка доступных причин жалобы на объявление
     * @return array
     */
    protected function getItemClaimReasons()
    {
        return array(
            1                 => _t('item-claim', 'Неверная рубрика'),
            2                 => _t('item-claim', 'Запрещенный товар/услуга'),
            4                 => _t('item-claim', 'Объявление не актуально'),
            8                 => _t('item-claim', 'Неверный адрес'),
            // ...
            self::CLAIM_OTHER => _t('item-claim', 'Другое'),
        );
    }

    /**
     * Формирование текста описания жалобы, с учетом отмеченных причин
     * @param integer $nReasons битовое поле причин жалобы
     * @param string $sComment комментарий к жалобе
     * @return string
     */
    protected function getItemClaimText($nReasons, $sComment)
    {
        $reasons = $this->getItemClaimReasons();
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
     * Актуализация счетчика необработанных жалоб на объявления
     * @param integer|null $increment
     */
    public function claimsCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->claimsListing(array('viewed' => 0), true);
            config::save('bbs_items_claims', $count, true);
        } else {
            config::saveCount('bbs_items_claims', $increment, true);
        }
    }

    /**
     * Актуализация счетчика объявлений ожидающих модерации
     * @param integer|null $increment
     */
    public function moderationCounterUpdate($increment = null)
    {
        if (empty($increment)) {
            $count = $this->model->itemsModeratingCounter();
            config::save('bbs_items_moderating', $count, true);
        } else {
            config::saveCount('bbs_items_moderating', $increment, true);
        }
    }

    /**
     * Получаем срок публикации объявления в днях
     * @param mixed $mFrom дата, от которой выполняется подсчет срока публикации
     * @param string $mFormat тип требуемого результата, строка = формат даты, false - unixtime
     * @return int
     */
    public function getItemPublicationPeriod($mFrom = false, $mFormat = 'Y-m-d H:i:s')
    {
        $nDays = config::get('bbs_item_publication_period', 30);
        if ($nDays <= 0) {
            $nDays = 7;
        }

        if (empty($mFrom) || is_bool($mFrom)) {
            $mFrom = strtotime($this->db->now());
        } else if (is_string($mFrom)) {
            $mFrom = strtotime($mFrom);
            if ($mFrom === false) {
                $mFrom = strtotime($this->db->now());
            }
        }

        $nPeriod = strtotime('+' . $nDays . ' days', $mFrom);
        if (!empty($mFormat)) {
            return date($mFormat, $nPeriod);
        } else {
            return $nPeriod;
        }
    }

    /**
     * Получаем срок продления объявления в днях
     * @param mixed $mFrom дата, от которой выполняется подсчет срока публикации
     * @param string $mFormat тип требуемого результата, строка = формат даты, false - unixtime
     * @return int
     */
    public function getItemRefreshPeriod($mFrom = false, $mFormat = 'Y-m-d H:i:s')
    {
        $nDays = config::get('bbs_item_refresh_period', 0);
        if ($nDays <= 0) {
            $nDays = 7;
        }

        if (empty($mFrom) || is_bool($mFrom)) {
            $mFrom = $this->db->now();
        }
        if (is_string($mFrom)) {
            $mFrom = strtotime($mFrom);
            if ($mFrom === false) {
                $mFrom = strtotime($this->db->now());
            }
        }
        $nPeriod = strtotime('+' . $nDays . ' days', $mFrom);
        if (!empty($mFormat)) {
            return date($mFormat, $nPeriod);
        } else {
            return $nPeriod;
        }
    }

    /**
     * Брать контакты объявления из профиля (пользователя / магазина)
     * @return boolean
     */
    public function getItemContactsFromProfile()
    {
        return (config::get('bbs_items_contacts', 0) === 2);
    }

    /**
     * Формируем ключ активации ОБ
     * @return array (code, link, expire)
     */
    protected function getActivationInfo()
    {
        $aData = array();
        $aData['key'] = md5(uniqid(SITEHOST . 'ASDAS(D90--00];&%#97665.,:{}' . BFF_NOW, true));
        $aData['link'] = static::url('item.activate', array('c' => $aData['key']));
        $aData['expire'] = date('Y-m-d H:i:s', strtotime('+1 day'));

        return $aData;
    }

    /**
     * Обработка данных объявления
     * @param array $aData @ref обработанные данные
     * @param integer $nItemID ID объявления
     * @param array $aItemData данные объявления (при редактировании)
     */
    protected function validateItemData(&$aData, $nItemID, $aItemData = array())
    {
        $aParams = array(
            'cat_id'     => TYPE_UINT, # категория
            'cat_type'   => TYPE_UINT, # тип объявления
            'owner_type' => TYPE_UINT, # тип владельца
            'title'      => array(TYPE_NOTAGS, 'len' => 100), # заголовок
            'descr'      => array(TYPE_NOTAGS, 'len' => 3000), # описание
            'video'      => array(TYPE_STR, 'len' => 1500), # видео ссылка (теги допустимы)
            # цена
            'price'      => TYPE_PRICE, # сумма
            'price_curr' => TYPE_UINT, # валюта
            'price_ex'   => TYPE_ARRAY, # модификаторы цены (торг, обмен, ...)
            # регион
            'city_id'    => TYPE_UINT, # город
            'district_id'=> TYPE_UINT, # район
            'metro_id'   => TYPE_UINT, # станция метро
            # адрес
            'addr_addr'  => array(TYPE_NOTAGS, 'len' => 400), # адрес
            'addr_lat'   => TYPE_NUM, # адрес, координата LAT
            'addr_lon'   => TYPE_NUM, # адрес, координата LON
            # контакты
            'name'       => array(TYPE_NOTAGS, 'len' => 50), # имя
            'phones'     => TYPE_ARRAY_NOTAGS, # телефоны
            'skype'      => array(TYPE_NOTAGS, 'len' => 32), # skype
            'icq'        => array(TYPE_NOTAGS, 'len' => 20), # icq
        );

        $this->input->postm($aParams, $aData);
        $byShop = $this->input->postget('shop', TYPE_BOOL);
        $catID = $aData['cat_id'];

        if (Request::isPOST()) {
            do {
                if (!$catID) {
                    $this->errors->set(_t('bbs', 'Укажите категорию'));
                }
                # Категория
                $catData = $this->model->catData($catID, array(
                        'id',
                        'pid',
                        'subs',
                        'numlevel',
                        'numleft',
                        'numright',
                        'addr',
                        'price',
                        'keyword',
                        'photos'
                    )
                );
                if (empty($catData) || $catData['subs'] > 0) {
                    $this->errors->set(_t('bbs', 'Категория указана некорректно'));
                }
                if ($nItemID && !static::categoryFormEditable() && !bff::adminPanel() &&
                    $catID != $aItemData['cat_id']
                ) {
                    $this->errors->set(_t('bbs', 'Ваше объявление было закреплено за этой категорией. Вы не можете изменить её.'));
                }

                # Заголовок
                if (empty($aData['title'])) {
                    $this->errors->set(_t('bbs', 'Укажите заголовок объявления'), 'title');
                } elseif (mb_strlen($aData['title']) < 5) {
                    $this->errors->set(_t('bbs', 'Заголовок слишком короткий'), 'title');
                }
                $aData['title'] = trim(preg_replace('/\s+/', ' ', $aData['title']));
                $aData['title'] = \bff\utils\TextParser::antimat($aData['title']);

                # Описание
                $aData['descr'] = $this->input->cleanTextPlain($aData['descr'], false, false);
                if (mb_strlen($aData['descr']) < 12) {
                    $this->errors->set(_t('bbs', 'Описание слишком короткое'), 'descr');
                }
                $aData['descr'] = trim(preg_replace('/\s{2,}$/m', '', $aData['descr']));
                $aData['descr'] = preg_replace('/ +/', ' ', $aData['descr']);
                $aData['descr'] = \bff\utils\TextParser::antimat($aData['descr']);

                # Данные пользователя
                if (bff::adminPanel() && !$nItemID) {
                    # данные пользователя при добавлении из админ. панели формируются позже
                } else {
                    Users::i()->cleanUserData($aData, array('name', 'skype', 'icq'));
                    $aData['phones'] = Users::validatePhones($aData['phones'], Users::i()->profilePhonesLimit);
                    if (empty($aData['name']) || mb_strlen($aData['name']) < 3) {
                        if ( ! $byShop && ! $this->getItemContactsFromProfile()) {
                            $this->errors->set(_t('bbs', 'Имя слишком короткое'), 'name');
                        }
                    }
                }

                # Город
                if (!$aData['city_id']) {
                    $this->errors->set(_t('bbs', 'Укажите город'));
                    break;
                } else {
                    if (!Geo::isCity($aData['city_id'])) {
                        $this->errors->set(_t('bbs', 'Город указан некорректно'));
                        break;
                    }
                }
                if (!Geo::coveringType(Geo::COVERING_COUNTRY)) {
                    $cityData = Geo::regionData($aData['city_id']);
                    if (!$cityData || !Geo::coveringRegionCorrect($cityData)) {
                        $this->errors->set(_t('bbs', 'Город указан некорректно'));
                        break;
                    }
                }
                if ($aData['city_id'] && $aData['district_id']) {
                    $aDistricts = Geo::districtList($aData['city_id']);
                    if (empty($aDistricts) || !array_key_exists($aData['district_id'], $aDistricts)) {
                        $aData['district_id'] = 0;
                    }
                }

                if (!$this->errors->no()) {
                    break;
                }

                # Изображения (выставляем лимит для последующей загрузки)
                $this->itemImages($nItemID)->setLimit($catData['photos']);

                # Видео
                if (empty($aData['video'])) {
                    $aData['video_embed'] = '';
                } else {
                    if (!$nItemID || ($nItemID && $aData['video'] != $aItemData['video'])) {
                        $aVideo = $this->itemVideo()->parse($aData['video']);
                        $aData['video_embed'] = serialize($aVideo);
                        if (!empty($aVideo['video_url'])) {
                            $aData['video'] = $aVideo['video_url'];
                        }
                    }
                }

                # Адрес
                if (empty($catData['addr'])) {
                    unset($aData['addr_addr'], $aData['addr_lat'], $aData['addr_lon']);
                }

                # Контакты (masked версия)
                $aContacts = array(
                    'phones' => array(),
                    'skype'  => (!empty($aData['skype']) ? mb_substr($aData['skype'], 0, 2) . 'xxxxx' : ''),
                    'icq'    => (!empty($aData['icq']) ? mb_substr($aData['icq'], 0, 2) . 'xxxxx' : ''),
                );
                foreach ($aData['phones'] as $v) {
                    $aContacts['phones'][] = $v['m'];
                }
                $aData['contacts'] = serialize($aContacts);
                unset($aContacts);

                # разворачиваем данные о регионе: city_id => reg1_country, reg2_region, reg3_city
                $aRegions = Geo::model()->regionParents($aData['city_id']);
                $aData = array_merge($aData, $aRegions['db']);

                # корректируем цену:
                $aData['price_ex'] = array_sum($aData['price_ex']);
                if ($catData['price']) {
                    # конвертируем цену в основную по курсу (для дальнейшего поиска)
                    $aData['price_search'] = Site::currencyPriceConvertToDefault($aData['price'], $aData['price_curr']);
                }

                # эскейпим заголовок
                $aData['title_edit'] = $aData['title'];
                $aData['title'] = HTML::escape($aData['title_edit']);

                # формируем URL-keyword на основе title
                $aData['keyword'] = mb_strtolower(func::translit($aData['title']));
                $aData['keyword'] = preg_replace("/\-+/", '-', preg_replace('/[^a-z0-9_\-]/', '', $aData['keyword']));

                # формируем URL объявления (@items.search@translit-ID.html)
                $sLink = static::url('items.search', array(
                        'keyword' => $catData['keyword'],
                        'region'  => $aRegions['keys']['region'],
                        'city'    => $aRegions['keys']['city'],
                    ), true
                );
                $sLink .= $aData['keyword'] . '-';
                if ($nItemID) {
                    $sLink .= $nItemID . '.html';
                } // если ID == 0, дополняем в BBSModel::itemSave
                $aData['link'] = $sLink;

                # подготавливаем ID категорий ОБ для сохранения в базу:
                # cat_id(выбранная, самая глубокая), cat_id1, cat_id2, cat_id3 ...
                $catParents = $this->model->catParentsID($catData, true);
                foreach ($catParents as $k => $v) {
                    $aData['cat_id' . $k] = $v;
                }
                # заполняем все оставшиеся уровни категорий нулями
                for ($i = static::CATS_MAXDEEP; $i>0; $i--) {
                    if (!isset($aData['cat_id' . $i])) {
                        $aData['cat_id' . $i] = 0;
                    }
                }

            } while (false);
        } else {
            $aData['cats'] = array();
        }
    }

    /**
     * Является ли текущий пользователь владельцем объявления
     * @param integer $nItemID ID объявления
     * @param integer|bool $nItemUserID ID пользователя объявления или FALSE (получаем из БД)
     * @return boolean
     */
    protected function isItemOwner($nItemID, $nItemUserID = false)
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return false;
        }

        if ($nItemUserID === false) {
            $aData = $this->model->itemData($nItemID, array('user_id', 'deleted'));
            # ОБ не найдено или помечено как "удаленное"
            if (empty($aData) || $aData['deleted']) {
                return false;
            }

            $nItemUserID = $aData['user_id'];
        }

        return ($nItemUserID > 0 && $nUserID == $nItemUserID);
    }

    /**
     * Метод обрабатывающий ситуацию с активацией пользователя
     * @param integer $nUserID ID пользователя
     */
    public function onUserActivated($nUserID)
    {
        # активируем объявления пользователя
        $aItems = $this->model->itemsDataByFilter(
            array(
                'user_id' => $nUserID,
                'status'  => self::STATUS_NOTACTIVATED,
                'deleted' => 0
            ),
            array('id')
        );
        if (!empty($aItems)) {
            $res = (int)$this->model->itemsSave(array_keys($aItems), array(
                    'activate_key'     => '', # чистим ключ активации
                    'publicated'       => $this->db->now(),
                    'publicated_order' => $this->db->now(),
                    'publicated_to'    => $this->getItemPublicationPeriod(),
                    'status_prev'      => self::STATUS_NOTACTIVATED,
                    'status'           => self::STATUS_PUBLICATED,
                    'moderated'        => 0, # помечаем на модерацию
                )
            );
            if ($res > 0) {
                # накручиваем счетчик кол-ва объявлений авторизованного пользователя
                $this->security->userCounter('items', $res, $nUserID); # +N
                # обновляем счетчик "на модерации"
                $this->moderationCounterUpdate();
            }
        }
    }

    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой пользователя
     * @param integer $nUserID ID пользователя
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     */
    public function onUserBlocked($nUserID, $bBlocked)
    {
        if ($bBlocked) {
            # при блокировке пользователя -> блокируем все его объявления
            $aItems = $this->model->itemsDataByFilter(
                array(
                    'user_id' => $nUserID,
                    'status IN(' . self::STATUS_PUBLICATED . ',' . self::STATUS_PUBLICATED_OUT . ')',
                    'deleted' => 0
                ),
                array('id')
            );
            if (!empty($aItems)) {
                $this->model->itemsSave(array_keys($aItems), array(
                        'blocked_num = blocked_num + 1',
                        'status_prev = status',
                        'status'         => self::STATUS_BLOCKED,
                        'blocked_reason' => 'Аккаунт пользователя заблокирован',
                    )
                );
            }
            # при блокировке пользователя -> отменяем все его импорты объявлений
            $this->itemsImport()->cancelUserImport($nUserID);
        } else {
            # при разблокировке -> разблокируем
            $aItems = $this->model->itemsDataByFilter(
                array(
                    'user_id' => $nUserID,
                    'status'  => self::STATUS_BLOCKED,
                    'deleted' => 0
                ),
                array('id')
            );
            if (!empty($aItems)) {
                $this->model->itemsSave(array_keys($aItems), array(
                        'status = (CASE status_prev WHEN ' . self::STATUS_BLOCKED . ' THEN ' . self::STATUS_PUBLICATED_OUT . ' ELSE status_prev END)',
                        'status_prev' => self::STATUS_BLOCKED,
                        //'blocked_reason' => '', # оставляем последнюю причину блокировки
                    )
                );
            }
        }
    }

    /**
     * Метод обрабатывающий ситуацию с блокировкой/разблокировкой магазина
     * @param integer $nShopID ID магазина
     * @param boolean $bBlocked true - заблокирован, false - разблокирован
     */
    public function onShopBlocked($nShopID, $bBlocked)
    {
        if ($bBlocked) {
            # при блокировке магазина -> блокируем все объявления от этого магазина
            $aItems = $this->model->itemsDataByFilter(
                array(
                    'shop_id' => $nShopID,
                    'status IN(' . self::STATUS_PUBLICATED . ',' . self::STATUS_PUBLICATED_OUT . ')',
                    'deleted' => 0
                ),
                array('id')
            );
            if (!empty($aItems)) {
                $this->model->itemsSave(array_keys($aItems), array(
                        'blocked_num = blocked_num + 1',
                        'status_prev = status',
                        'status'         => self::STATUS_BLOCKED,
                        'blocked_reason' => 'Аккаунт магазина заблокирован',
                    )
                );
            }
        } else {
            # при разблокировке -> разблокируем
            $aItems = $this->model->itemsDataByFilter(
                array(
                    'shop_id' => $nShopID,
                    'status'  => self::STATUS_BLOCKED,
                    'deleted' => 0
                ),
                array('id')
            );
            if (!empty($aItems)) {
                $this->model->itemsSave(array_keys($aItems), array(
                        'status = (CASE status_prev WHEN ' . self::STATUS_BLOCKED . ' THEN ' . self::STATUS_PUBLICATED_OUT . ' ELSE status_prev END)',
                        'status_prev' => self::STATUS_BLOCKED,
                        //'blocked_reason' => '', # оставляем последнюю причину блокировки
                    )
                );
            }
        }
    }

    # --------------------------------------------------------
    # Активация услуг

    /**
     * Активация услуги/пакета услуг для Объявления
     * @param integer $nItemID ID объявления
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

        # получаем данные об объявлении
        if (empty($aItemData)) {
            $aItemData = $this->model->itemData($nItemID, array(
                    'id',
                    'status',
                    'deleted', # ID, статус, флаг "ОБ удалено"
                    'publicated_to', # дата окончания публикации
                    'svc', # битовое поле активированных услуг
                    'svc_up_activate', # кол-во оставшихся оплаченных поднятий (оплаченных пакетно)
                    'reg2_region',
                    'reg3_city', # ID региона(области), ID города
                    'cat_id1',
                    'cat_id2', # ID основной категории, ID подкатегории
                    'svc_fixed_to', # дата окончания "Закрепления"
                    'svc_premium_to', # дата окончания "Премиум"
                    'svc_marked_to', # дата окончания "Выделение"
                    'svc_quick_to', # дата окончания "Срочно"
                    'svc_press_status'
                )
            ); # статус "Печать в прессе"
        }

        # проверяем статус объявления
        if (empty($aItemData) || $aItemData['deleted']) {
            $this->errors->set(_t('bbs', 'Для указанного объявления невозможно активировать данную услугу'));

            return false;
        }

        # активируем пакет услуг
        if ($aSvcData['type'] == Svc::TYPE_SERVICEPACK) {
            $aServices = (isset($aSvcData['svc']) ? $aSvcData['svc'] : array());
            if (empty($aServices)) {
                $this->errors->set(_t('bbs', 'Неудалось активировать пакет услуг'));

                return false;
            }
            $aServicesID = array();
            foreach ($aServices as $v) {
                $aServicesID[] = $v['id'];
            }
            $aServices = Svc::model()->svcData($aServicesID, array('*'));
            if (empty($aServices)) {
                $this->errors->set(_t('bbs', 'Неудалось активировать пакет услуг'));

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
                $res = $this->svcActivateService($nItemID, $v['id'], $v, $aItemData, true, $aSvcSettings);
                if ($res) {
                    $nSuccess++;
                }
            }

            return true;
        } else {
            # активируем услугу
            return $this->svcActivateService($nItemID, $nSvcID, $aSvcData, $aItemData, false, $aSvcSettings);
        }
    }

    /**
     * Активация услуги для Объявления
     * @param integer $nItemID ID объявления
     * @param integer $nSvcID ID услуги
     * @param mixed $aSvcData данные об услуге(*) или FALSE
     * @param mixed $aItemData @ref данные об объявлении или FALSE
     * @param boolean $bFromPack услуга активируется из пакета услуг
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return boolean|integer
     *      1, true - услуга успешно активирована,
     *      2 - услуга успешно активирована без необходимости списывать средства со счета пользователя
     *      false - ошибка активации услуги
     */
    protected function svcActivateService($nItemID, $nSvcID, $aSvcData = false, &$aItemData = false, $bFromPack = false, array &$aSvcSettings = array())
    {
        if (empty($nItemID) || empty($aItemData) || empty($nSvcID)) {
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
        $aItemData['svc'] = intval($aItemData['svc']);

        # период действия услуги (в днях)
        # > при пакетной активации, период действия берется из настроек активируемого пакета услуг
        $nPeriodDays = (!empty($aSvcData['period']) ? intval($aSvcData['period']) : 1);
        if ($nPeriodDays < 1) {
            $nPeriodDays = 1;
        }

        $sNow = $this->db->now();
        $publicatedTo = strtotime($aItemData['publicated_to']);
        $aUpdate = array();
        $mResult = true;
        switch ($nSvcID) {
            case self::SERVICE_UP: # Поднятие
            {
                $nPosition = $this->model->itemPositionInCategory($nItemID, $aItemData['cat_id1']);

                if ($bFromPack) {
                    if ($nPosition === 1) {
                        # если ОБ находится на первой позиции в основной категории
                        # НЕ выполняем "поднятие", только помечаем доступное для активации кол-во поднятий
                        $aUpdate['svc_up_activate'] = $aSvcData['cnt'];
                        break;
                    }
                    # при "поднятии" пакетно помечаем доступное для активации кол-во "поднятий"
                    # -1 ("поднятие" при активации пакета услуг)
                    $aUpdate['svc_up_activate'] = ($aSvcData['cnt'] - 1);
                } else {
                    if ($nPosition == 1) {
                        $this->errors->set(_t('svc', 'Объявление находится на первой позиции, нет необходимости выполнять его поднятие'));

                        return false;
                    }
                    # если есть неиспользованные "поднятия", используем их
                    if (!empty($aItemData['svc_up_activate'])) {
                        $aUpdate['svc_up_activate'] = ($aItemData['svc_up_activate'] - 1);
                        $mResult = 2; # без списывание средств со счета
                    }
                }
                # если объявление закреплено, поднимаем также среди закрепленных
                if ($aItemData['svc'] & static::SERVICE_FIX) {
                    $aUpdate['svc_fixed_order'] = $sNow;
                }
                $aUpdate['publicated_order'] = $sNow;
                $aUpdate['svc_up_date'] = date('Y-m-d');
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_MARK: # Выделение
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_marked_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_marked_to'] = $toStr;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_QUICK: # Срочно
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_quick_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_quick_to'] = $toStr;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_FIX: # Закрепление
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_fixed_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_fixed_to'] = $toStr;
                # ставим выше среди закрепленных
                $aUpdate['svc_fixed_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_PREMIUM: # Премиум
            {
                # считаем дату окончания действия услуги
                $to = strtotime('+' . $nPeriodDays . ' days', (
                        # если услуга уже активна => продлеваем срок действия
                    ($aItemData['svc'] & $nSvcID) ? strtotime($aItemData['svc_premium_to']) :
                        # если неактивна => активируем на требуемый период от текущей даты
                        time()
                    )
                );
                $toStr = date('Y-m-d H:i:s', $to);
                # в случае если дата публикация объявления завершается раньше окончания услуги:
                if ($publicatedTo < $to) {
                    # продлеваем период публикации
                    $aUpdate['publicated_to'] = $toStr;
                }
                # помечаем срок действия услуги
                $aUpdate['svc_premium_to'] = $toStr;
                # ставим выше среди "премиум" объявлений
                $aUpdate['svc_premium_order'] = $sNow;
                # помечаем активацию услуги
                $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
            }
            break;
            case self::SERVICE_PRESS: # Печать в прессе
            {
                if (!self::PRESS_ON) {
                    break;
                }

                switch ($aItemData['svc_press_status']) {
                    case self::PRESS_STATUS_PAYED:
                    {
                        if (!$bFromPack) {
                            $this->errors->set(_t('svc', 'Объявление будет опубликовано в прессе в ближайшее время'));
                        }

                        return false;
                    }
                    break;
                    case self::PRESS_STATUS_PUBLICATED:
                    {
                        if (!$bFromPack) {
                            $this->errors->set(_t('svc', 'Объявление уже опубликовано в прессе'));
                        }

                        return false;
                    }
                    break;
                    default:
                    {
                        # помечаем на "Публикацию в прессе"
                        $aUpdate['svc_press_status'] = self::PRESS_STATUS_PAYED;
                        # помечаем активацию услуги
                        $aUpdate['svc'] = ($aItemData['svc'] | $nSvcID);
                    }
                    break;
                }
            }
            break;
        }

        $res = $this->model->itemSave($nItemID, $aUpdate);
        if (!empty($res)) {
            if ($nSvcID == self::SERVICE_PRESS) {
                # +1 к счетчику "печать в прессе"
                $this->pressCounterUpdate(1);
            }
            # актуализируем данные об объявлении для корректной пакетной активации услуг
            if (!empty($aUpdate)) {
                foreach ($aUpdate as $k => $v) {
                    $aItemData[$k] = $v;
                }
            }

            return $mResult;
        }

        return false;
    }

    /**
     * Формируем описание счета активации услуги (пакета услуг)
     * @param integer $nItemID ID Объявления
     * @param integer $nSvcID ID услуги
     * @param array|boolean $aData false или array('item'=>array('id',...),'svc'=>array('id','type'))
     * @param array $aSvcSettings @ref дополнительные параметры услуги/нескольких услуг
     * @return string
     */
    public function svcBillDescription($nItemID, $nSvcID, $aData = false, array &$aSvcSettings = array())
    {
        $aSvc = (!empty($aData['svc']) ? $aData['svc'] :
            Svc::model()->svcData($nSvcID));

        $aItem = (!empty($aData['item']) ? $aData['item'] :
            $this->model->itemData($nItemID, array('id', 'keyword', 'title', 'link')));

        $sLink = (!empty($aItem['link']) ? 'href="' . $aItem['link'] . '" class="j-bills-bbs-item-link" data-item="' . $nItemID . '"' : 'href=""');

        if ($aSvc['type'] == Svc::TYPE_SERVICE) {
            switch ($nSvcID) {
                case self::SERVICE_UP:
                {
                    return _t('bbs', 'Поднятие объявления в списке<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                                     'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_MARK:
                {
                    return _t('bbs', 'Выделение объявления<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                             'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_FIX:
                {
                    return _t('bbs', 'Закрепление объявления<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                               'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_PREMIUM:
                {
                    return _t('bbs', 'Премиум размещение<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                           'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_QUICK:
                {
                    return _t('bbs', 'Срочное размещение<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                           'title' => $aItem['title']
                        )
                    );
                }
                break;
                case self::SERVICE_PRESS:
                {
                    return _t('bbs', 'Размещение объявления в прессе<br /><small><a [link]>[title]</a></small>', array('link'  => $sLink,
                                                                                                                       'title' => $aItem['title']
                        )
                    );
                }
                break;
            }
        } else {
            if ($aSvc['type'] == Svc::TYPE_SERVICEPACK) {
                return _t('bbs', 'Пакет услуг "[pack]" <br /><small><a [link]>[title]</a></small>',
                    array('pack' => $aSvc['title'], 'link' => $sLink, 'title' => $aItem['title'])
                );
            }
        }
    }

    /**
     * Инициализация компонента обработки иконок услуг/пакетов услуг BBSSvcIcon
     * @param mixed $nSvcID ID услуги / пакета услуг
     * @return BBSSvcIcon component
     */
    public static function svcIcon($nSvcID = false)
    {
        static $i;
        if (!isset($i)) {
            require_once static::i()->module_dir . 'bbs.svc.icon.php';
            $i = new BBSSvcIcon();
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
     * Актуализация счетчика объявлений ожидающих печати в прессе
     * @param integer|null $increment
     */
    public function pressCounterUpdate($increment)
    {
        if (empty($increment)) {
            $count = $this->model->itemsListing(array('svc_press_status' => self::PRESS_STATUS_PAYED), true);
            config::save('bbs_items_press', $count, true);
        } else {
            config::saveCount('bbs_items_press', $increment, true);
        }
    }

    /**
     * Обработка копирования данных локализации
     */
    public function onLocaleDataCopy($from, $to)
    {
        # услуги (services, packs)
        if (bff::servicesEnabled()) {
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
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function onGeoUrlTypeChanged($prevType, $nextType)
    {
        $this->model->itemsGeoUrlTypeChanged($prevType, $nextType);
    }
    
    /**
     * Получение списка возможных дней для оповещения о завершении публикации объявления
     * @return array
     */
    protected function getUnpublicatedDays()
    {
        return array(1,2,5);
    }
    
    /**
     * Получение ключа для модерации объявлений на фронтенде
     * @param integer $itemID ID объявления
     * @param string|boolean $checkKey ключ для проверки или FALSE
     * @return string|boolean
     */
    public static function moderationUrlKey($itemID = 0, $checkKey = false)
    {
        if ($checkKey !== false) {
            if (empty($checkKey) || strlen($checkKey) != 5) {
                return false;
            }
        }

        $key = substr(hash('sha256', $itemID . SITEHOST), 0, 5);
        if ($checkKey !== false) {
            return ($key === $checkKey);
        }

        return $key;
    }
    
    /**
     * Получаеv уровень подкатегории, отображаемый в фильтре
     * @param boolean $asSetting как указано в настройке
     * @return integer
     */
    public static function catsFilterLevel($asSetting = false)
    {
        $level = config::get('bbs_categories_filter_level', 0);
        if ($level < 2) $level = 2;
        return ( $asSetting ? $level : $level - 1 );
    }

    /**
     * Получаем данные о самой глубокой из parent-категорий не отображаемой в фильтре
     * @param array $catData @ref данные о текущей категории поиска
     * @return array
     */
    public function catsFilterParent(array &$catData)
    {
        return $this->model->catDataByFilter(array(
            'numlevel' => self::catsFilterLevel(),
            'numleft <= ' . $catData['numleft'],
            'numright > ' . $catData['numright'],
        ), array('id','pid','title'));
    }

    /**
     * Получаем данные для формирование фильтров подкатегорий
     * @param array $catData @ref данные о текущей категории поиска
     * @return array
     */
    public function catsFilterData(array &$catData)
    {
        $filterLevel = self::catsFilterLevel();
        $urlSearch = static::url('items.search');
        if ($catData['numlevel'] < $filterLevel) return array();

        # получаем основные категории
        $cats = $this->model->catParentsData($catData, array('id','pid','numlevel','keyword','subs_filter_title as subs_title','subs',));
        foreach ($cats as $k=>&$v) {
            if ($v['numlevel'] < $filterLevel || ! $v['subs']) {
                unset($cats[$k]);
            }
            $v['link'] = $urlSearch.$v['keyword'].'/'; unset($v['keyword']);
            $v['subs'] = array();
        } unset($v);
        if (empty($cats)) return array();
        $catsID = array_keys($cats);

        # получаем подкатегории
        $subcats = $this->model->catsDataByFilter(array('pid'=>$catsID), array('id','pid','title','keyword'));
        foreach ($subcats as &$v) {
            $v['link'] = $urlSearch.$v['keyword'].'/'; unset($v['keyword']);
            $v['active'] = (in_array($v['id'], $catsID) || $v['id'] == $catData['id']);
            $cats[$v['pid']]['subs'][$v['id']] = $v;
        } unset($v, $subcats);

        return $cats;
    }
    
    /**
     * Проверка возможности импорта объявлений
     * @return bool
     */
    public static function importAllowed()
    {
        if (bff::adminPanel()) return true;
        if (!bff::shopsEnabled()) return false;

        $shopID = User::shopID();
        if ($shopID <= 0) return false;

        $shopData = Shops::model()->shopData($shopID, array('status','import'));
        if (empty($shopData)) {
            bff::log('Ошибка получение данных о магазине #'.$shopID);
            return false;
        } else {
            if ($shopData['status'] != Shops::STATUS_ACTIVE) {
                return false;
            }
        }

        $access = config::get('bbs_items_import', self::IMPORT_ACCESS_ADMIN);
        switch($access)
        {
            case self::IMPORT_ACCESS_ADMIN: {
                return false;
            } break;
            case self::IMPORT_ACCESS_CHOSEN: {
                if ($shopData['import'] > 0) return true;
            } break;
            case self::IMPORT_ACCESS_ALL: {
                return true;
            } break;
        }

        return false;
    }

    /**
     * Проверка превышения лимита добавления новых объявлений
     * @param integer $nUserID ID пользователя
     * @param integer $nShopID ID магазина
     * @param int $nCatID ID категории или 0
     * @param int $nLimit @ref значение лимита для выбранного режима
     * @return bool true - лимит превышен, false - нет
     */
    protected function itemsLimitExceeded($nUserID, $nShopID, $nCatID = 0, & $nLimit = 0)
    {
        if ( ! $nUserID) {
            return false;
        }
        $now = date('Y-m-d 00:00:00');
        $mode = 'user';
        $aFilter = array(
            'user_id'  => $nUserID,
            ':created' => array('created >= :now', ':now' => $now),
            'shop_id'  => $nShopID,
        );

        if ($nShopID) {
            if (static::importAllowed()) { # если разрешен импорт - лимитирование не действует
                return false;
            }
            $mode = 'shop';
        }
        switch (config::get('bbs_items_limits_'.$mode, static::LIMITS_NONE)) {
            case static::LIMITS_COMMON: # общий лимит
                $nLimit = config::get('bbs_items_limits_'.$mode.'_common', 0);
                if ($nLimit > 0) {
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                }
                break;
            case static::LIMITS_CATEGORY: # лимит по категориям
                if ( ! $nCatID) {
                    break;
                }
                $nLimit = config::get('bbs_items_limits_'.$mode.'_category_default', 0);
                $aLimit = func::unserialize(config::get('bbs_items_limits_'.$mode.'_category', false));
                if ($nLimit > 0 && (empty($aLimit) || ! isset($aLimit[$nCatID]))) {
                    # общий для всех категорий
                    if ( ! empty($aLimit)) {
                        # исключим перечисленные категории
                        $aFilter[':cat_id1'] = $this->db->prepareIN('cat_id1', array_keys($aLimit), true);
                    }
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                    break;
                }
                if (isset($aLimit[$nCatID]) && $aLimit[$nCatID] > 0) { # лимит в конкретной категории
                    $nLimit = $aLimit[$nCatID];
                    $aFilter['cat_id1'] = $nCatID;
                    $nCnt = $this->model->itemsCount($aFilter);
                    if ($nCnt >= $nLimit) {
                        return true;
                    }
                }
                break;
            case static::LIMITS_NONE: # без ограничений
                break;
        }
        return false;
    }

    /**
     * Поиск "минус слов" в строке
     * @param  string $sString строка
     * @param string $sWord @ref найденное слово
     * @return bool true - нашли минус слово, false - нет
     */
    protected function spamMinusWordsFound($sString, & $sWord = '')
    {
        static $aMinusWords = false;
        if ( ! $aMinusWords) {
            $aMinusWords = func::unserialize(config::get('bbs_items_spam_minuswords', false));
        }

        if (empty($aMinusWords[LNG])) return false;

        # разбиваем текст на отдельные слова
        $aString = preg_replace('/[^[:alpha:]]+/iu', ',', $sString);
        $aString = explode(',', $aString);
        foreach ($aString as $k => &$v) {
            if (empty($v)) {
                unset($aString[$k]);
                continue;
            }
            $v = mb_strtolower($v);
        } unset($v);
        $aString = array_unique($aString);
        foreach ($aMinusWords[LNG] as $v) {
            foreach ($aString as $vv) {
                if (mb_strpos($vv, $v) !== false) {
                    $sWord = $v;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Проверка дублирования пользователем объявлений
     * @param integer $nUserID ID пользователя
     * @param array $aData @ref данные ОБ, ищем по заголовку 'title' и/или описанию 'descr'
     * @return bool true - нашли похожее объявление, false - нет
     */
    protected function spamDuplicatesFound($nUserID, &$aData)
    {
        if (!$nUserID) return false;
        if (!config::get('bbs_items_spam_duplicates', false)) return false;

        $query = array(0=>array());
        if (!empty($aData['title'])) {
            $query[0][] = 'title LIKE :title';
            $query[':title'] = $aData['title'];
        }
        if (!empty($aData['descr'])) {
            $query[0][] = 'descr LIKE :descr';
            $query[':descr'] = $aData['descr'];
        }
        if (!empty($query[0])) {
            $query[0] = '('.join(' OR ', $query[0]).')';
            if ($this->model->itemsCount(array(
                'user_id' => $nUserID,
                ':query'  => $query,
            ))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array_merge(parent::writableCheck(), array(
            bff::path('items', 'images') => 'dir', # изображения объявлений
            bff::path('cats', 'images')  => 'dir', # изображения категорий
            bff::path('svc', 'images')   => 'dir', # изображения платных услуг
            bff::path('tmp', 'images')   => 'dir', # tmp
            bff::path('import')          => 'dir', # импорт
        ));
    }

    /**
     * Генерация хеша для авторизации при просмотре
     * @param array $aData @ref данные пользователя user_id, user_id_ex, last_login
     * @return string
     */
    protected function userAuthHash(&$userData)
    {
        return mb_strtolower(hash('sha256', $userData['user_id'].$userData['user_id_ex'].md5($userData['last_login']).$userData['last_login']));
    }

}