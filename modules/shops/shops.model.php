<?php

define('TABLE_SHOPS', DB_PREFIX . 'shops'); // магазины
define('TABLE_SHOPS_REQUESTS', DB_PREFIX . 'shops_requests'); // заявки на прикрепление
define('TABLE_SHOPS_CATEGORIES', DB_PREFIX . 'shops_categories'); // категории магазинов
define('TABLE_SHOPS_CATEGORIES_LANG', DB_PREFIX . 'shops_categories_lang'); // категории магазинов - lang
define('TABLE_SHOPS_IN_CATEGORIES', DB_PREFIX . 'shops_in_categories'); // связь магазинов с категориями
define('TABLE_SHOPS_IN_CATEGORIES_BBS', DB_PREFIX . 'shops_in_categories_bbs'); // связь магазинов с категориями объявлений (bbs)
define('TABLE_SHOPS_CLAIMS', DB_PREFIX . 'shops_claims'); // жалобы на магазины
define('TABLE_SHOPS_SVC_PRICE', DB_PREFIX . 'shops_svc_price'); // настройки региональной стоисмости платных услуг

use bff\db\NestedSetsTree;

class ShopsModel extends Model
{
    /** @var ShopsBase */
    public $controller;

    /** @var NestedSetsTree для категорий */
    public $treeCategories;
    public $langCategories = array(
        'title'        => TYPE_NOTAGS, # название
        'mtitle'       => TYPE_NOTAGS, # meta-title
        'mkeywords'    => TYPE_NOTAGS, # meta-keywords
        'mdescription' => TYPE_NOTAGS, # meta-description
        'seotext'      => TYPE_STR, # seotext
        'titleh1'      => TYPE_STR, # H1
        'breadcrumb'   => TYPE_STR, # хлебная крошка
    );

    public $langSvcServices = array(
        'title_view'       => TYPE_STR, # название
        'description'      => TYPE_STR, # описание (краткое)
        'description_full' => TYPE_STR, # описание (подробное)
    );

    public $langSvcPacks = array(
        'title_view'       => TYPE_NOTAGS, # название
        'description'      => TYPE_STR, # описание (краткое)
        'description_full' => TYPE_STR, # описание (подробное)
    );

    public function init()
    {
        parent::init();

        # подключаем nestedSets для категорий
        if (Shops::categoriesEnabled()) {
            $this->treeCategories = new NestedSetsTree(TABLE_SHOPS_CATEGORIES);
            $this->treeCategories->init();
        }
    }

    /**
     * Список магазинов по фильтру (admin)
     * @param array $aFilterRaw фильтр списка (требует подготовки)
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function shopsListing(array $aFilterRaw, $bCount = false, $sqlOrderBy = '', $sqlLimit = '')
    {
        $aFilter = array();
        foreach (array('status', ':status', 'moderated', ':owner') as $k) {
            if (isset($aFilterRaw[$k])) {
                $aFilter[$k] = $aFilterRaw[$k];
            }
        }
        if (!empty($aFilterRaw['q'])) {
            $aFilter[':q'] = array(
                '(S.id = :q_id OR S.title LIKE :q_title)',
                ':q_id'    => intval($aFilterRaw['q']),
                ':q_title' => '%' . $aFilterRaw['q'] . '%',
            );
        }
        if (!empty($aFilterRaw['u'])) {
            $aFilter[':u'] = array(
                '(S.user_id = :u_id OR U.email LIKE :u_email)',
                ':u_id'    => intval($aFilterRaw['u']),
                ':u_email' => $aFilterRaw['u'] . '%',
            );
        }

        if ($bJoinCategories = !empty($aFilterRaw['cat'])) {
            $aFilter[':cat'] = array(
                'C.category_id = :cat',
                ':cat' => $aFilterRaw['cat'],
            );
            $categoriesTable = (Shops::categoriesEnabled() ? TABLE_SHOPS_IN_CATEGORIES :
                TABLE_SHOPS_IN_CATEGORIES_BBS);
        }
        $aFilter = $this->prepareFilter($aFilter, 'S');

        if ($bCount) {
            return (integer)$this->db->one_data('SELECT COUNT(S.id)
                                FROM ' . TABLE_SHOPS . ' S
                                    LEFT JOIN ' . TABLE_USERS . ' U ON S.id = U.shop_id
                                     ' . ($bJoinCategories ? ' INNER JOIN ' . $categoriesTable . ' C ON S.id = C.shop_id' : '') . '
                                ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT S.id, S.created, S.title, S.link, S.status, S.moderated,
                    U.login as user_login, U.email, U.user_id
                    FROM ' . TABLE_SHOPS . ' S
                         LEFT JOIN ' . TABLE_USERS . ' U ON S.id = U.shop_id
                         ' . ($bJoinCategories ? ' INNER JOIN ' . $categoriesTable . ' C ON S.id = C.shop_id' : '') . '
                    ' . $aFilter['where'] . '
                    GROUP BY S.id
                    ORDER BY S.' . $sqlOrderBy . ' ' . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Данные о магазинах для списка пользователей (admin)
     * @param array $aUserID ID пользователей
     * @return array|mixed
     */
    public function shopsDataToUsersListing(array $aUserID)
    {
        if (empty($aUserID)) {
            return array();
        }

        return $this->db->select_key('SELECT id, user_id, link, title
            FROM ' . TABLE_SHOPS . '
            WHERE user_id IN (' . join(',', $aUserID) . ')', 'user_id'
        );
    }

    /**
     * Данные о магазинах для списка сообщений (Мои сообщения)
     * @param array $aShopID ID магазинов
     * @return array|mixed
     */
    public function shopsDataToMessages(array $aShopID)
    {
        if (empty($aShopID)) {
            return array();
        }

        return $this->db->select_key('SELECT id, title, logo, keyword
            FROM ' . TABLE_SHOPS . '
            WHERE id IN (' . join(',', $aShopID) . ')', 'id'
        );
    }

    /**
     * Список магазинов по фильтру (frontend)
     * @param array $aFilter фильтр списка
     * @param integer $nCategoryID ID категории
     * @param bool $bCount только подсчет кол-ва
     * @param string $sqlLimit
     * @return mixed
     */
    public function shopsList(array $aFilter, $nCategoryID, $bCount = false, $sqlLimit = '')
    {
        $sqlFields = array(
            'id',
            'id_ex',
            'title',
            'link',
            'descr',
            'logo',
            'site',
            'phones',
            'skype',
            'icq',
            'social',
            'addr_addr',
            'addr_lat',
            'addr_lon',
            'region_id',
            'items'
        );
        $sqlFields = 'S.' . join(',S.', $sqlFields) . ', R.title_' . LNG . ' as region_title';
        $sqlFields .= ', ((S.svc & ' . Shops::SERVICE_MARK . ') > 0) as svc_marked
                       , ((S.svc & ' . Shops::SERVICE_FIX . ') > 0) as svc_fixed';
        $sqlOrderBy = 'svc_fixed DESC, S.svc_fixed_order DESC, S.items_last DESC';

        if ($nCategoryID > 0) {
            $sCategoriesTable = (Shops::categoriesEnabled() ? TABLE_SHOPS_IN_CATEGORIES : TABLE_SHOPS_IN_CATEGORIES_BBS);

            $aFilter[':cat'] = array('C.category_id = :cat', ':cat' => $nCategoryID);
            $aFilter = $this->prepareFilter($aFilter, 'S');
            if ($bCount) {
                return (integer)$this->db->one_data('SELECT COUNT(S.id)
                                    FROM ' . TABLE_SHOPS . ' S
                                         INNER JOIN ' . $sCategoriesTable . ' C ON S.id = C.shop_id
                                    ' . $aFilter['where'],
                    $aFilter['bind']
                );
            }

            return $this->db->select('SELECT ' . $sqlFields . '
                                    FROM ' . TABLE_SHOPS . ' S
                                         INNER JOIN ' . $sCategoriesTable . ' C ON S.id = C.shop_id
                                         LEFT JOIN ' . TABLE_REGIONS . ' R ON R.id = S.region_id
                                    ' . $aFilter['where'] . '
                                    ORDER BY ' . $sqlOrderBy . ' ' . $sqlLimit,
                $aFilter['bind']
            );
        } else {
            $aFilter = $this->prepareFilter($aFilter, 'S');
            if ($bCount) {
                return (integer)$this->db->one_data('SELECT COUNT(S.id)
                                    FROM ' . TABLE_SHOPS . ' S
                                    ' . $aFilter['where'],
                    $aFilter['bind']
                );
            } else {
                return $this->db->select('SELECT ' . $sqlFields . '
                            FROM ' . TABLE_SHOPS . ' S
                                LEFT JOIN ' . TABLE_REGIONS . ' R ON R.id = S.region_id
                            ' . $aFilter['where'] . '
                            GROUP BY S.id
                            ORDER BY ' . $sqlOrderBy . ' ' . $sqlLimit, $aFilter['bind']
                );
            }
        }
    }

    /**
     * Данные о магазине
     * @param integer $nShopID ID магазина
     * @param array $aFields ключи требуемых данных
     * @param bool $bEdit для редактирования
     * @return array|mixed
     */
    public function shopData($nShopID, $aFields = array(), $bEdit = false)
    {
        if (empty($aFields)) {
            return array();
        }
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aFields) . '
                       FROM ' . TABLE_SHOPS . '
                       WHERE id = :id
                       LIMIT 1', array(':id' => $nShopID)
        );
        if (empty($aData)) {
            return array();
        }

        if ($bEdit) {
            # берем title для редактирования
            $aData['title'] = $aData['title_edit'];
            $aData['region_title'] = (isset($aData['region_id']) ? Geo::regionTitle($aData['region_id']) : '');
        }

        if (isset($aData['social'])) {
            $aData['social'] = (!empty($aData['social']) ? func::unserialize($aData['social']) : array());
        }
        if (isset($aData['phones'])) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }
        if (!empty($aData['link'])) {
            $aData['link'] = Shops::urlDynamic($aData['link']);
        }

        return $aData;
    }

    /**
     * Данные о магазине для правого блока (просмотр страниц магазина)
     * @param integer $nShopID ID магазина
     * @return array
     */
    public function shopDataSidebar($nShopID)
    {
        $aData = $this->shopData($nShopID, array('*', 'link as link_dynamic'));
        if (empty($aData)) {
            return array();
        }

        $aData['logo_small'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szList);
        $aData['logo'] = ShopsLogo::url($nShopID, $aData['logo'], ShopsLogo::szView);
        $aData['region_title'] = (isset($aData['region_id']) ? Geo::regionTitle($aData['region_id']) : '');
        $aData['country'] = Geo::regionData($aData['reg1_country']);
        $aData['region'] = Geo::regionData($aData['reg2_region']);
        $aData['city'] = Geo::regionData($aData['reg3_city']);

        return $aData;
    }

    /**
     * Сохранение данных магазина
     * @param integer $nShopID ID магазина
     * @param array $aData данные
     * @return mixed
     */
    public function shopSave($nShopID, array $aData)
    {
        if (isset($aData['social'])) {
            if (!is_array($aData['social'])) {
                $aData['social'] = array();
            }
            $aData['social'] = serialize($aData['social']);
        }
        if (isset($aData['cats'])) {
            $aCats = $aData['cats'];
            unset($aData['cats']);
        }
        if (isset($aData['status']) || isset($aData['status_prev'])) {
            $aData['status_changed'] = $this->db->now();
        }
        if ($nShopID) {
            $res = $this->db->update(TABLE_SHOPS, $aData, array('id' => $nShopID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['id_ex'] = func::generator(6);
            $res = $nShopID = $this->db->insert(TABLE_SHOPS, $aData, 'id');
            if ($nShopID && isset($aData['link'])) {
                # дополняем ссылку
                $this->db->update(TABLE_SHOPS, array(
                        'link' => $aData['link'] . $aData['keyword'] . '-' . $nShopID
                    ), array('id' => $nShopID)
                );
            }
        }
        if (Shops::categoriesEnabled() && isset($aCats) && $nShopID) {
            $this->shopSaveCategories($nShopID, $aCats);
        }

        return $res;
    }

    /**
     * Сохранение связи магазина с категориями
     * @param integer $nShopID ID магазина
     * @param array $aCategoriesID @ref ID категорий
     */
    public function shopSaveCategories($nShopID, array $aCategoriesID)
    {
        $this->db->delete(TABLE_SHOPS_IN_CATEGORIES, array('shop_id' => $nShopID));

        $aCategoriesID = array_unique($aCategoriesID);
        if (empty($aCategoriesID)) {
            return;
        }
        # проверяем допустимый лимит
        if (($nLimit = Shops::categoriesLimit()) && sizeof($aCategoriesID) > $nLimit) {
            $aCategoriesID = array_slice($aCategoriesID, 0, $nLimit);
        }

        $sql = array();
        $i = 1;
        foreach ($aCategoriesID as $v) {
            $sql[] = array(
                'shop_id'     => $nShopID,
                'category_id' => $v,
                'is_parent'   => 0,
                'num'         => $i++,
            );
        }
        # сохраняем связь (по 25 за запрос)
        foreach (array_chunk($sql, 25) as $v) {
            $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES, $v);
        }

        # дополнительно сохраняем связь с основными категориями
        $parentsID = $this->db->select_one_column('SELECT pid FROM ' . TABLE_SHOPS_CATEGORIES . '
                            WHERE id IN(' . join(',', $aCategoriesID) . ')'
        );
        if (!empty($parentsID)) {
            $parentsID = array_unique($parentsID);
            $sql = array();
            foreach ($parentsID as $v) {
                if ($v != Shops::CATS_ROOTID) {
                    $sql[] = array(
                        'shop_id'     => $nShopID,
                        'category_id' => $v,
                        'is_parent'   => 1,
                    );
                }
            }
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_SHOPS_IN_CATEGORIES, $v);
            }
        }
    }

    /**
     * Формирование списка категорий в которые входит магазин
     * @param integer $nShopID ID магазина
     * @param string $sIconSize размер иконки
     * @return array
     */
    public function shopCategoriesIn($nShopID, $sIconSize)
    {
        if (!$nShopID) {
            return array();
        }
        $sIconField = 'C.icon_' . $sIconSize . ' as icon';
        $aData = (array)$this->db->select('SELECT C.id, C.pid, L.title, ' . $sIconField . '
                FROM ' . TABLE_SHOPS_IN_CATEGORIES . ' I
                 INNER JOIN ' . TABLE_SHOPS_CATEGORIES . ' C ON C.id = I.category_id
                 INNER JOIN ' . TABLE_SHOPS_CATEGORIES_LANG . ' L ON L.id = I.category_id AND L.lang = :lang
                WHERE I.shop_id = :shop AND I.is_parent = 0
                ORDER BY I.num', array(':shop' => $nShopID, ':lang' => LNG)
        );
        if (!empty($aData)) {
            $oCategoryIcon = Shops::categoryIcon(0);
            $aParentID = array();
            foreach ($aData as &$v) {
                $v['icon'] = $oCategoryIcon->url($v['id'], $v['icon'], $sIconSize);
                if ($v['pid'] > Shops::CATS_ROOTID) {
                    $aParentID[] = $v['pid'];
                }
            }
            unset($v);
            if (!bff::adminPanel() && Shops::CATS_MAXDEEP == 2 && sizeof($aParentID) > 0) {
                $aParentData = (array)$this->db->select_key('SELECT C.id, L.title, ' . $sIconField . '
                    FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                         ' . TABLE_SHOPS_CATEGORIES_LANG . ' L
                    WHERE C.id IN (' . join(',', $aParentID) . ')
                      AND C.id = L.id AND L.lang = :lang', 'id',
                    array(':lang' => LNG)
                );
                if (!empty($aParentData)) {
                    foreach ($aData as &$v) {
                        if (isset($aParentData[$v['pid']])) {
                            $v['ptitle'] = $aParentData[$v['pid']]['title'];
                            $v['picon'] = $oCategoryIcon->url($v['pid'], $aParentData[$v['pid']]['icon'], $sIconSize);
                        }
                    }
                    unset($v);
                }
            }
        }

        return $aData;
    }

    /**
     * Подсчет кол-ва объявлений, связанных с магазином
     * @param integer $nShopID
     * @return integer
     */
    public function shopItemsCounter($nShopID)
    {
        if (empty($nShopID) || $nShopID < 0) {
            return 0;
        }

        return (int)BBS::model()->itemsList(array('shop_id' => $nShopID), true);
    }

    /**
     * Получение информации об активации магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopActive($nShopID)
    {
        return ($this->shopStatus($nShopID) == Shops::STATUS_ACTIVE);
    }

    /**
     * Текущий статус магазина
     * @param integer $nShopID ID магазина
     * @return integer
     */
    public function shopStatus($nShopID)
    {
        $aShopData = $this->shopData($nShopID, array('status'));
        if (empty($aShopData)) {
            return 0;
        }

        return $aShopData['status'];
    }

    /**
     * Получаем общее кол-во активных магазинов
     * @param array $aFilter доп. фильтр
     * @return integer
     */
    public function shopsActiveCounter(array $aFilter = array())
    {
        $aFilter['status'] = Shops::STATUS_ACTIVE;
        $aFilter = $this->prepareFilter($aFilter);

        return (int)$this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_SHOPS . '
                ' . $aFilter['where'], $aFilter['bind']
        );
    }

    /**
     * Актуализация счетчиков магазинов (cron)
     * Рекомендуемый период: раз в 10 минут
     */
    public function shopsCronCounters()
    {
        $this->db->begin();

        if (Shops::categoriesEnabled()) {
            # пересчет кол-ва магазинов в категориях магазинов (TABLE_SHOPS_CATEGORIES::shops)
            $this->db->exec('UPDATE ' . TABLE_SHOPS_CATEGORIES . ' SET shops = 0');
            $this->db->exec('UPDATE ' . TABLE_SHOPS_CATEGORIES . ' C,
                     ( SELECT SC.category_id as id, COUNT(DISTINCT SC.shop_id) as shops
                       FROM ' . TABLE_SHOPS_IN_CATEGORIES . ' as SC
                         INNER JOIN ' . TABLE_SHOPS . ' S ON SC.shop_id = S.id AND S.status = ' . Shops::STATUS_ACTIVE . '
                       GROUP BY 1 ) as X
                SET C.shops = X.shops
                WHERE C.id = X.id
            '
            );
        }

        # пересчет связи магазинов с категориями объявлений (TABLE_SHOPS_IN_CATEGORIES_BBS)
        $this->db->exec('TRUNCATE TABLE ' . TABLE_SHOPS_IN_CATEGORIES_BBS);
        for ($i = 1; $i <= 2; $i++) {
            if ($i > BBS::CATS_MAXDEEP) {
                break;
            }
            $this->db->exec('INSERT INTO ' . TABLE_SHOPS_IN_CATEGORIES_BBS . ' (shop_id, category_id, numlevel, items)
                ( SELECT S.id as shop_id, I.cat_id' . $i . ', ' . $i . ', COUNT(I.id) as items
                FROM ' . TABLE_SHOPS . ' as S
                  LEFT JOIN ' . TABLE_BBS_ITEMS . ' as I ON I.shop_id = S.id
                WHERE I.status = ' . BBS::STATUS_PUBLICATED . '
                    ' . (BBS::premoderation() ? ' AND I.moderated > 0' : '') . '
                GROUP BY 1, 2
                ORDER BY shop_id ASC, items DESC)'
            );
        }

        # пересчет кол-ва опубликованных объявлений в магазинах (TABLE_SHOPS::items)
        $this->db->exec('UPDATE ' . TABLE_SHOPS . ' SET items = 0, items_last = :last',
            array(':last' => '0000-00-00 00:00:00')
        );
        $this->db->exec('UPDATE ' . TABLE_SHOPS . ' S,
                    ( SELECT I.shop_id, SUM(I.items) as items
                      FROM ' . TABLE_SHOPS_IN_CATEGORIES_BBS . ' as I
                      WHERE I.numlevel = 1
                      GROUP BY I.shop_id ) as X
                SET S.items = X.items
                WHERE S.id = X.shop_id
        '
        );
        # обновление даты последнего опубликованного объявления в магазинах (TABLE_SHOPS::items_last)
        $this->db->exec('UPDATE ' . TABLE_SHOPS . ' S,
                    ( SELECT I.shop_id, MAX(I.publicated_order) as last_publicated
                      FROM ' . TABLE_BBS_ITEMS . ' as I
                      WHERE I.shop_id > 0 AND I.status = ' . BBS::STATUS_PUBLICATED . '
                            ' . (BBS::premoderation() ? ' AND I.moderated > 0' : '') . '
                      GROUP BY I.shop_id ) as X
                SET S.items_last = X.last_publicated
                WHERE S.id = X.shop_id
        '
        );

        # пересчет кол-ва магазинов в категориях объявлений (TABLE_BBS_CATEGORIES::shops)
        $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' SET shops = 0');
        for ($i = 1; $i <= 2; $i++) {
            if ($i > BBS::CATS_MAXDEEP) {
                break;
            }
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' C,
                     ( SELECT S.category_id as id, COUNT(DISTINCT S.shop_id) as shops
                       FROM ' . TABLE_SHOPS_IN_CATEGORIES_BBS . ' as S
                       WHERE S.numlevel = ' . $i . '
                       GROUP BY 1 ) as X
                SET C.shops = X.shops
                WHERE C.numlevel = ' . $i . ' AND C.id = X.id
            '
            );
        }

        # пересчет общего кол-ва активных магазинов (config::site)
        config::save('shops_total_active', $this->shopsActiveCounter(), true);

        $this->db->commit();
    }

    /**
     * Удаление магазина
     * @param integer $nShopID ID магазина
     * @return boolean
     */
    public function shopDelete($nShopID)
    {
        # связь с категориями (TABLE_SHOPS_IN_CATEGORIES) удаляется по внешнему ключу
        # связь с жалобами (TABLE_SHOPS_CLAIMS) удаляется по внешнему ключу
        return $this->db->delete(TABLE_SHOPS, array('id' => $nShopID));
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL магазинов
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function shopsGeoUrlTypeChanged($prevType, $nextType)
    {
        if ($prevType == $nextType) {
            return;
        }

        $aData = $this->db->select('SELECT
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_SHOPS . ' S
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON S.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON S.reg3_city = RC.id
            WHERE S.reg3_city > 0 AND S.reg2_region > 0
            GROUP BY S.reg3_city
            ORDER BY S.reg3_city
        '
        );

        $coveringType = Geo::coveringType();

        if ($prevType == Geo::URL_SUBDOMAIN) {
            foreach ($aData as &$v) {
                switch ($nextType) {
                    case Geo::URL_SUBDIR:
                        $to = '//{sitehost}/' . $v['city'] . '/';
                        break;
                    case Geo::URL_NONE:
                        if ($coveringType == Geo::COVERING_CITY) {
                            continue 2;
                        }
                        $to = '//{sitehost}/';
                        break;
                }
                switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                        $from = '//' . $v['city'] . '.{sitehost}/';
                        break;
                    case Geo::COVERING_CITY:
                        $from = '//{sitehost}/';
                        break;
                }
                $this->db->update(TABLE_SHOPS,
                    array('link = REPLACE(link, :from, :to)'),
                    array('reg3_city = :city AND reg2_region = :region'),
                    array(
                        ':from'   => $from,
                        ':to'     => $to,
                        ':city'   => $v['city_id'],
                        ':region' => $v['region_id'],
                    )
                );
            }
            unset($v);
        } else {
            if ($prevType == Geo::URL_SUBDIR) {
                foreach ($aData as &$v) {
                    switch ($nextType) {
                        case Geo::URL_SUBDOMAIN:
                        {
                            switch ($coveringType) {
                                case Geo::COVERING_COUNTRIES:
                                case Geo::COVERING_COUNTRY:
                                case Geo::COVERING_REGION:
                                case Geo::COVERING_CITIES:
                                    $to = '//' . $v['city'] . '.{sitehost}/';
                                    break;
                                case Geo::COVERING_CITY:
                                    $to = '//{sitehost}/';
                                    break;
                            }
                        }
                            break;
                        case Geo::URL_NONE:
                            if ($coveringType == Geo::COVERING_CITY) {
                                continue 2;
                            }
                            $to = '//{sitehost}/';
                            break;
                    }
                    switch ($coveringType) {
                        case Geo::COVERING_COUNTRIES:
                        case Geo::COVERING_COUNTRY:
                        case Geo::COVERING_REGION:
                        case Geo::COVERING_CITIES:
                            $from = '//{sitehost}/' . $v['city'] . '/';
                            break;
                        case Geo::COVERING_CITY:
                            $from = '//{sitehost}/';
                            break;
                    }
                    $this->db->update(TABLE_SHOPS,
                        array('link = REPLACE(link, :from, :to)'),
                        array('reg3_city = :city AND reg2_region = :region'),
                        array(
                            ':from'   => $from,
                            ':to'     => $to,
                            ':city'   => $v['city_id'],
                            ':region' => $v['region_id'],
                        )
                    );
                }
                unset($v);
            } else {
                if ($prevType == Geo::URL_NONE && $coveringType != Geo::COVERING_CITY) {
                    foreach ($aData as &$v) {
                        switch ($nextType) {
                            case Geo::URL_SUBDOMAIN:
                                $to = '//' . $v['city'] . '.{sitehost}/';
                                break;
                            case Geo::URL_SUBDIR:
                                $to = '//{sitehost}/' . $v['city'] . '/';
                                break;
                        }
                        $this->db->update(TABLE_SHOPS,
                            array('link = REPLACE(link, :from, :to)'),
                            array('reg3_city = :city AND reg2_region = :region'),
                            array(
                                ':from'   => '//{sitehost}/',
                                ':to'     => $to,
                                ':city'   => $v['city_id'],
                                ':region' => $v['region_id'],
                            )
                        );
                    }
                    unset($v);
                }
            }
        }
    }

    /**
     * Перестраиваем URL всех магазинов
     */
    public function shopsLinksRebuild()
    {
        $this->db->select_iterator('SELECT S.id, S.keyword,
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_SHOPS . ' S
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON S.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON S.reg3_city = RC.id
            ORDER BY S.id', array(), function($v) {
            $link = Shops::url('shop.view', array(
                        'region' => $v['region'],
                        'city'   => $v['city']
                    ), true
                ) . $v['keyword'] . '-' . $v['id'];

            $this->db->update(TABLE_SHOPS, array('link' => $link), array('id' => $v['id']));
        });
    }

    # --------------------------------------------------------------------
    # Заявки на закрепление

    /**
     * Список заявок (admin)
     * @param array $aFilter фильтр списка заявок
     * @param bool $bCount только подсчет кол-ва заявок
     * @param string $sqlLimit
     * @return mixed
     */
    public function requestsListing(array $aFilter, $bCount = false, $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'R');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(R.id) FROM ' . TABLE_SHOPS_REQUESTS . ' R ' . $aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT R.id, R.created, R.name, R.email, R.viewed, R.user_ip,
                    R.user_id, U.email as user_email
               FROM ' . TABLE_SHOPS_REQUESTS . ' R
                    LEFT JOIN ' . TABLE_USERS . ' U ON R.user_id = U.user_id
               ' . $aFilter['where']
            . ' ORDER BY R.created DESC'
            . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Получение данных заявки
     * @param integer $nRequestID ID заявки
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function requestData($nRequestID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT R.*
                    FROM ' . TABLE_SHOPS_REQUESTS . ' R
                    WHERE R.id = :id',
                array(':id' => $nRequestID)
            );

        } else {
            //
        }

        return $aData;
    }

    /**
     * Сохранение заявки
     * @param integer $nRequestID ID заявки
     * @param array $aData данные заявки
     * @return boolean|integer
     */
    public function requestSave($nRequestID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        if ($nRequestID > 0) {
            $aData['modified'] = $this->db->now(); # Дата изменения
            $res = $this->db->update(TABLE_SHOPS_REQUESTS, $aData, array('id' => $nRequestID));

            return !empty($res);
        } else {
            $aData['created'] = $this->db->now(); # Дата создания
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['user_id'] = User::id(); # Пользователь
            $aData['user_ip'] = Request::remoteAddress(true); # IP адрес

            $nRequestID = $this->db->insert(TABLE_SHOPS_REQUESTS, $aData);
            if ($nRequestID > 0) {
                //
            }

            return $nRequestID;
        }
    }

    /**
     * Переключатели заявки
     * @param integer $nRequestID ID заявки
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function requestToggle($nRequestID, $sField)
    {
        switch ($sField) {
            case '?':
            {
                // $this->toggleInt(TABLE_SHOPS_REQUESTS, $nRequestID, $sField, 'id');
            }
            break;
        }
    }

    /**
     * Удаление заявки
     * @param integer $nRequestID ID заявки
     * @return boolean
     */
    public function requestDelete($nRequestID)
    {
        if (empty($nRequestID)) return false;
        $res = $this->db->delete(TABLE_SHOPS_REQUESTS, array('id' => $nRequestID));
        if (!empty($res)) {
            return true;
        }

        return false;
    }

    # ----------------------------------------------------------------
    # Жалобы

    public function claimsListing($aFilter, $bCount = false, $sqlLimit = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'CL');

        if ($bCount) {
            return (int)$this->db->one_data('SELECT COUNT(CL.id)
                                FROM ' . TABLE_SHOPS_CLAIMS . ' CL
                                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        return $this->db->select('SELECT CL.*, U.name, U.login, U.blocked as ublocked, U.deleted as udeleted
                                FROM ' . TABLE_SHOPS_CLAIMS . ' CL
                                    LEFT JOIN ' . TABLE_USERS . ' U ON CL.user_id = U.user_id
                                ' . $aFilter['where'] . '
                            ORDER BY CL.created DESC' . $sqlLimit, $aFilter['bind']
        );
    }

    public function claimData($nClaimID, $aFields = array())
    {
        if (empty($aFields)) $aFields = '*';
        $aParams = array();
        if (!is_array($aFields)) $aFields = array($aFields);
        foreach ($aFields as $v) {
            $aParams[] = $v;
        }

        return $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_SHOPS_CLAIMS . '
                       WHERE id = :cid
                       LIMIT 1', array(':cid' => $nClaimID)
        );
    }

    public function claimSave($nClaimID, $aData)
    {
        if ($nClaimID) {
            return $this->db->update(TABLE_SHOPS_CLAIMS, $aData, array('id' => $nClaimID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['user_id'] = User::id();
            $aData['user_ip'] = Request::remoteAddress();

            return $this->db->insert(TABLE_SHOPS_CLAIMS, $aData, 'id');
        }
    }

    public function claimDelete($nClaimID)
    {
        if (!$nClaimID) return false;

        return $this->db->delete(TABLE_SHOPS_CLAIMS, array('id' => $nClaimID));
    }

    # ----------------------------------------------------------------
    # Категории магазинов (используются при Shops::categoriesEnabled())

    /**
     * Данные для формирования списка категорий
     * @param string $type тип списка категорий
     * @param string $device тип устройства
     * @param integer $parentID ID parent-категории
     * @param string $iconVariant размер иконки
     * @return mixed
     */
    public function catsList($type, $device, $parentID, $iconVariant)
    {
        $filter = array(
            'C.pid != 0',
            'C.enabled = 1',
        );

        switch ($type) {
            case 'search':
            case 'form':
            {
                if ($device == bff::DEVICE_DESKTOP) {
                    //$iconVariant = ShopsCategoryIcon::BIG;
                    if ($parentID > 0) {
                        $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                    } else {
                        $filter[] = 'C.numlevel = 1';
                    }
                } else if ($device == bff::DEVICE_PHONE) {
                    //$iconVariant = ShopsCategoryIcon::SMALL;
                    if ($parentID > 0) {
                        $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                    } else {
                        $filter[] = 'C.numlevel = 1';
                    }
                }
            }
                break;
        }

        $filter[] = $this->db->langAnd(false, 'C', 'CL');
        $filter = $this->prepareFilter($filter);

        return $this->db->select('SELECT C.id, C.pid, C.icon_' . $iconVariant . ' as i, CL.title as t, C.keyword as k,
                                         (C.numright-C.numleft)>1 as subs, C.numlevel as lvl
                            FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                                 ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
                            ' . $filter['where'] . '
                            ORDER BY C.numleft ASC', $filter['bind']
        );
    }

    /**
     * Список категорий магазинов
     * @param array $aFilter
     * @return mixed
     */
    public function catsListing(array $aFilter = array())
    {
        $aFilter = $this->prepareFilter($aFilter);

        return $this->db->select('SELECT C.id, C.pid, C.enabled, C.numlevel,
                                IF(C.numright-C.numleft>1,1,0) as node, C.title, COUNT(S.shop_id) as shops
                            FROM ' . TABLE_SHOPS_CATEGORIES . ' C
                                LEFT JOIN ' . TABLE_SHOPS_IN_CATEGORIES . ' S ON S.category_id = C.id
                            ' . $aFilter['where'] . '
                            GROUP BY C.id
                            ORDER BY C.numleft ASC', $aFilter['bind']
        );
    }

    public function catData($nCategoryID, $aFields = array(), $bEdit = false)
    {
        if (empty($nCategoryID)) return array();

        return $this->catDataByFilter(array('id' => $nCategoryID), $aFields, $bEdit);
    }

    public function catDataByFilter($aFilter, $aFields = array(), $bEdit = false)
    {
        $aParams = array();
        if (empty($aFields) || $bEdit) $aFields = '*';
        if ($aFields == '*') {
            $aParams = array($aFields);
        } else {
            if (!is_array($aFields)) {
                $aFields = array($aFields);
            }
            foreach ($aFields as $v) {
                if (isset($this->langCategories[$v])) {
                    $v = 'CL.' . $v;
                } elseif ($v == 'subs') {
                    $v = '((C.numright-C.numleft)>1) as subs';
                } else {
                    $v = 'C.' . $v;
                }
                $aParams[] = $v;
            }
        }

        $aFilter[':lng'] = $this->db->langAnd(false, 'C', 'CL');
        $aFilter = $this->prepareFilter($aFilter, 'C');

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                            ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        if ($bEdit) {
            $aData['node'] = ($aData['numright'] - $aData['numleft']);
            if (!Request::isPOST()) {
                $this->db->langSelect($aData['id'], $aData, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
            }
        }

        return $aData;
    }

    public function catSave($nCategoryID, $aData)
    {
        if ($nCategoryID) {
            # запрет именения parent'a
            if (isset($aData['pid'])) unset($aData['pid']);
            $aData['modified'] = $this->db->now();
            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
            $aDataNonLang = array_diff_key($aData, $this->langCategories);
            if (isset($aData['title'][LNG])) $aDataNonLang['title'] = $aData['title'][LNG];

            return $this->db->update(TABLE_SHOPS_CATEGORIES, $aDataNonLang, array('id' => $nCategoryID));
        } else {
            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if (!$nCategoryID) return 0;
            unset($aData['pid']);
            $aData['created'] = $this->db->now();
            $this->catSave($nCategoryID, $aData);

            return $nCategoryID;
        }
    }

    public function catDelete($nCategoryID)
    {
        if (!$nCategoryID) return false;

        # проверяем наличие подкатегорий
        $aData = $this->catData($nCategoryID, '*', true);
        if ($aData['node'] > 1) {
            $this->errors->set('Невозможно удалить категорию с подкатегориями');

            return false;
        }

        # проверяем наличие связанных с категорией магазинов
        # ...

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($nCategoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');

            return false;
        }

        return true;
    }

    public function catDeleteAll()
    {
        # чистим таблицу категорий (+ зависимости по внешним ключам)
        $this->db->exec('DELETE FROM ' . TABLE_SHOPS_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('ALTER TABLE ' . TABLE_SHOPS_CATEGORIES . ' AUTO_INCREMENT = 2');

        # создаем корневую директорию
        $nRootID = Shops::CATS_ROOTID;
        $sRootTitle = 'Корневой раздел';
        $aData = array(
            'id'       => $nRootID,
            'pid'      => 0,
            'numleft'  => 1,
            'numright' => 2,
            'numlevel' => 0,
            'title'    => $sRootTitle,
            'keyword'  => 'root',
            'enabled'  => 1,
            'created'  => $this->db->now(),
        );
        $res = $this->db->insert(TABLE_SHOPS_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_SHOPS_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    public function catToggle($nCategoryID, $sField)
    {
        if (!$nCategoryID) return false;

        switch ($sField) {
            case 'enabled':
            {
                $res = $this->toggleInt(TABLE_SHOPS_CATEGORIES, $nCategoryID, 'enabled', 'id');
                if ($res) {
                    $aCategoryData = $this->catData($nCategoryID, array('numleft', 'numright', 'enabled'));
                    if (!empty($aCategoryData)) {
                        $this->db->update(TABLE_SHOPS_CATEGORIES, array(
                                'enabled' => $aCategoryData['enabled'],
                            ), array(
                                'numleft > :left AND numright < :right'
                            ), array(
                                ':left'  => $aCategoryData['numleft'],
                                ':right' => $aCategoryData['numright'],
                            )
                        );
                    }
                }

                return $res;
            }
            break;
        }

        return false;
    }

    public function catsRotate()
    {
        return $this->treeCategories->rotateTablednd();
    }

    /**
     * Получаем данные о parent-категориях
     * @param int $nCategoryID ID категории
     * @param array $aFields требуемые поля parent-категорий
     * @param bool $bIncludingSelf включая категорию $nCategoryID
     * @param bool $bExludeRoot исключая данные о корневом элементе
     * @return array|mixed
     */
    public function catParentsData($nCategoryID, array $aFields = array(
        'id',
        'title',
        'keyword'
    ), $bIncludingSelf = true, $bExludeRoot = true
    ) {
        if ($nCategoryID <= 0) return array();
        $aParentsID = $this->treeCategories->getNodeParentsID($nCategoryID, ($bExludeRoot ? ' AND id != ' . Shops::CATS_ROOTID : ''), $bIncludingSelf);
        if (empty($aParentsID)) return array();
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
        }
        $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
            FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                 ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
            WHERE C.id IN(' . join(',', $aParentsID) . ')
            ' . $this->db->langAnd(true, 'C', 'CL') . '
            ORDER BY C.numleft
        '
        );

        return func::array_transparent($aParentsData, 'id', true);
    }

    /**
     * Получаем данные о parent-категориях
     * @param array|integer $mCategoryData ID категории или данные о текущей категории: id, pid, numlevel, numleft, numright, ...
     * @param bool $bIncludingSelf включать текущую в итоговых список
     * @param bool $bExludeRoot исключить корневую категорию
     * @return array array(lvl=>id, ...)
     */
    public function catParentsID($mCategoryData, $bIncludingSelf = true, $bExludeRoot = true)
    {
        if (!is_array($mCategoryData)) {
            $mCategoryData = $this->catDataByFilter(array('id' => $mCategoryData), array(
                    'id',
                    'pid',
                    'numlevel',
                    'numleft',
                    'numright'
                )
            );
            if (empty($mCategoryData)) return array();
        }

        $aParentsID = array();
        if (!$bExludeRoot) {
            $aParentsID[0] = 1;
        }
        if ($mCategoryData['numlevel'] == 1) {
            if ($bIncludingSelf)
                $aParentsID[1] = $mCategoryData['id'];
        } else if ($mCategoryData['numlevel'] == 2) {
            $aParentsID[1] = $mCategoryData['pid'];
            if ($bIncludingSelf)
                $aParentsID[2] = $mCategoryData['id'];
        } else {
            $aData = $this->db->select('SELECT id, numlevel FROM ' . TABLE_SHOPS_CATEGORIES . '
                                    WHERE numleft <= ' . $mCategoryData['numleft'] . ' AND numright > ' . $mCategoryData['numright'] .
                ($bExludeRoot ? ' AND numlevel > 0' : '') . '
                                    ORDER BY numleft'
            );
            $aParentsID = array();
            if (!empty($aData)) {
                foreach ($aData as $v) {
                    $aParentsID[$v['numlevel']] = $v['id'];
                }
            }
            if ($bIncludingSelf) {
                $aParentsID[] = $mCategoryData['id'];
            }
        }

        return $aParentsID;
    }

    /**
     * Формирование списка подкатегорий
     * @param integer $nCategoryID ID категории
     * @param mixed $mOptions формировать select-options (@see HTML::selectOptions) или FALSE
     * @return array|string
     */
    public function catSubcatsData($nCategoryID, $mOptions = false)
    {
        $aData = $this->db->select('SELECT C.id, CL.title
                    FROM ' . TABLE_SHOPS_CATEGORIES . ' C,
                         ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL
                    WHERE C.pid = :pid ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft', array(':pid' => $nCategoryID)
        );
        if (empty($mOptions)) {
            return $aData;
        } else {
            return HTML::selectOptions($aData, $mOptions['sel'], $mOptions['empty'], 'id', 'title');
        }
    }

    /**
     * Обработка редактирования keyword'a в категории с подменой его в путях подкатегорий
     * @param integer $nCategoryID ID категории
     * @param string $sKeywordPrev предыдущий keyword
     * @return boolean
     */
    public function catSubcatsRebuildKeyword($nCategoryID, $sKeywordPrev)
    {
        $aCatData = $this->catData($nCategoryID, array('pid', 'keyword', 'numleft', 'numright', 'numlevel'));
        if (empty($aCatData)) return false;
        if ($aCatData['pid'] == Shops::CATS_ROOTID) {
            $sFrom = $sKeywordPrev . '/';
        } else {
            $aParentCatData = $this->catData($aCatData['pid'], array('keyword'));
            if (empty($aParentCatData)) return false;
            $sFrom = $aParentCatData['keyword'] . '/' . $sKeywordPrev . '/';
        }

        # перестраиваем полный путь подкатегорий
        $nCatsUpdated = $this->db->update(TABLE_SHOPS_CATEGORIES,
            array('keyword = REPLACE(keyword, :from, :to)'),
            'numleft > :left AND numright < :right',
            array(
                ':from'  => $sFrom,
                ':to'    => $aCatData['keyword'] . '/',
                ':left'  => $aCatData['numleft'],
                ':right' => $aCatData['numright']
            )
        );

        return !empty($nCatsUpdated);
    }

    /**
     * Является ли категория основной
     * @param integer $nCategoryID ID категории
     * @param integer $nParentID ID parent-категории (для избежания запроса к БД) или false
     * @return boolean true - основная, false - подкатегория
     */
    public function catIsMain($nCategoryID, $nParentID = false)
    {
        if (!empty($nParentID)) {
            return ($nParentID == Shops::CATS_ROOTID);
        } else {
            $nNumlevel = $this->treeCategories->getNodeNumlevel($nCategoryID);
            return ($nNumlevel == 1);
        }
    }

    /**
     * Формирование выпадающего списка категорий
     * @param string $sType тип требуемого списка
     * @param int $nSelectedID ID выбранной категории
     * @param string|bool $mEmptyOpt параметры значения по-умолчанию
     * @param array $aExtra доп. настройки
     * @return string select::options
     */
    public function catsOptions($sType, $nSelectedID = 0, $mEmptyOpt = false, array $aExtra = array())
    {
        $sqlWhere = array($this->db->langAnd(false, 'C', 'CL'));
        $bCountShops = false;
        switch ($sType) {
            case 'adm-category-form-add':
                $sqlWhere[] = 'C.numlevel < ' . Shops::CATS_MAXDEEP;
                $bCountShops = true;
                break;
            case 'adm-shops-listing':
                $sqlWhere[] = '(C.numlevel IN(1,2) ' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
                break;
            case 'adm-shop-form':
                $sqlWhere[] = 'C.numlevel IN (1,2)';
                break;
        }

        // TODO
        $aData = $this->db->select('SELECT C.id, C.pid, CL.title, C.numlevel, C.numleft, C.numright, 0 as disabled
                        ' . ($bCountShops ? ', COUNT(S.id) as shops ' : '') . '
                   FROM ' . TABLE_SHOPS_CATEGORIES_LANG . ' CL,
                        ' . TABLE_SHOPS_CATEGORIES . ' C
                        ' . ($bCountShops ? ' LEFT JOIN ' . TABLE_SHOPS_IN_CATEGORIES . ' S ON C.id = S.category_id ' : '') . '
                   WHERE ' . join(' AND ', $sqlWhere) . '
                   GROUP BY C.id
                   ORDER BY C.numleft'
        );
        if (empty($aData)) $aData = array();

        if ($sType == 'adm-category-form-add') {
            foreach ($aData as &$v) {
                $v['disabled'] = ($v['numlevel'] > 0 && $v['shops'] > 0);
            }
            unset($v);
        } else if ($sType == 'adm-shop-form') {
            foreach ($aData as &$v) {
                # запрещаем выбор категорий с вложенными подкатегориями
                $v['disabled'] = (($v['numright'] - $v['numleft']) > 1);
            }
            unset($v);
        }

        $sHTML = '';
        $bUsePadding = (stripos(Request::userAgent(), 'chrome') === false);
        foreach ($aData as $v) {
            $sHTML .= '<option value="' . $v['id'] . '" data-pid="' . $v['pid'] . '" ' .
                ($bUsePadding && $v['numlevel'] > 1 ? 'style="padding-left:' . ($v['numlevel'] * 10) . 'px;" ' : '') .
                ($nSelectedID == $v['id'] ? ' selected="selected"' : '') .
                ($v['disabled'] ? ' disabled="disabled"' : '') .
                '>' . (!$bUsePadding && $v['numlevel'] > 1 ? str_repeat('&nbsp;&nbsp;', $v['numlevel']) : '') . $v['title'] . '</option>';
        }

        if ($mEmptyOpt !== false) {
            $nValue = 0;
            if (is_array($mEmptyOpt)) {
                $nValue = key($mEmptyOpt);
                $mEmptyOpt = current($mEmptyOpt);
            }
            $sHTML = '<option value="' . $nValue . '" class="bold">' . $mEmptyOpt . '</option>' . $sHTML;
        }

        return $sHTML;
    }

    # ----------------------------------------------------------------
    # Услуги / Пакеты услуг

    /**
     * Данные об услугах (frontend)
     * @param integer $nTypeID ID типа Svc::type...
     * @return array
     */
    public function svcPromoteData($nTypeID)
    {
        if ($nTypeID == Svc::TYPE_SERVICE || empty($nTypeID)) {
            $aData = $this->db->select_key('SELECT id, keyword, price, settings
                            FROM ' . TABLE_SVC . ' WHERE type = :type',
                'keyword', array(':type' => Svc::TYPE_SERVICE)
            );

            if (empty($aData)) return array();

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;

        } elseif ($nTypeID == Svc::TYPE_SERVICEPACK) {
            $aData = $this->db->select('SELECT id, keyword, price, settings
                                FROM ' . TABLE_SVC . ' WHERE type = :type ORDER BY num',
                array(':type' => Svc::TYPE_SERVICEPACK)
            );

            foreach ($aData as $k => $v) {
                $sett = func::unserialize($v['settings']);
                unset($v['settings']);
                # оставляем текущую локализацию
                foreach ($this->langSvcPacks as $lngK => $lngV) {
                    $sett[$lngK] = (isset($sett[$lngK][LNG]) ? $sett[$lngK][LNG] : '');
                }
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;
        }
    }

    /**
     * Данные об услугах для формы, страницы продвижения
     * @return array
     */
    public function svcData()
    {
        $aFilter = array('module' => 'shops');
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_key('SELECT S.*
                                    FROM ' . TABLE_SVC . ' S
                                    ' . $aFilter['where']
            . ' ORDER BY S.type, S.num',
            'id', $aFilter['bind']
        );
        if (empty($aData)) return array();

        $oIcon = Shops::svcIcon();
        foreach ($aData as $k => &$v) {
            $v['id'] = intval($v['id']);
            $v['disabled'] = false;
            $sett = func::unserialize($v['settings']);
            unset($v['settings']);
            if (!empty($sett)) {
                $v = array_merge($sett, $v);
            }
            $v['title_view'] = (isset($v['title_view'][LNG]) ? $v['title_view'][LNG] : '');
            $v['description'] = (isset($v['description'][LNG]) ? $v['description'][LNG] : '');
            $v['description_full'] = (isset($v['description_full'][LNG]) ? $v['description_full'][LNG] : '');
            $v['icon_b'] = $oIcon->url($v['id'], $v['icon_b'], ShopsSvcIcon::BIG);
            $v['icon_s'] = $oIcon->url($v['id'], $v['icon_s'], ShopsSvcIcon::SMALL);
            # исключаем выключенные услуги
            if (empty($v['on'])) unset($aData[$k]);
        }
        unset($v);

        return $aData;
    }

    /**
     * Получение региональной стоимости услуги в зависимости от города
     * @param array $svcID ID услуг
     * @param integer $cityID ID города
     * @return array - региональной стоимость услуг для указанного региона
     */
    public function svcPricesEx(array $svcID, $cityID)
    {
        if (empty($svcID) || !$cityID) return array();
        $result = array_fill_keys($svcID, 0);

        $cityData = Geo::regionData($cityID);
        if (empty($cityData) || !Geo::isCity($cityData) || !$cityData['pid']) return $result;

        # получаем доступные варианты региональной стоимости услуг
        $prices = $this->db->select('SELECT * FROM ' . TABLE_SHOPS_SVC_PRICE . '
                    WHERE ' . $this->db->prepareIN('svc_id', $svcID) . '
                    ORDER BY num'
        );
        if (empty($prices)) return array();

        foreach ($svcID as $id) {
            # город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['region_id'] == $cityData['country']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
        }

        return $result;
    }

    /**
     * Загрузка настроек региональной стоимости услуг, для редактирования
     * @return array
     */
    public function svcPriceExEdit()
    {
        $aResult = array();
        $aData = $this->db->select('SELECT * FROM ' . TABLE_SHOPS_SVC_PRICE . ' ORDER BY svc_id, id, num');
        if (!empty($aData)) {
            $aRegionsID = array();
            foreach ($aData as $v) {
                if (!isset($aResult[$v['svc_id']])) {
                    $aResult[$v['svc_id']] = array();
                }
                if (!isset($aResult[$v['svc_id']][$v['id']])) {
                    $aResult[$v['svc_id']][$v['id']] = array('price' => $v['price'], 'regions' => array());
                }
                if ($v['region_id'] > 0) {
                    $aResult[$v['svc_id']][$v['id']]['regions'][] = $v['region_id'];
                    $aRegionsID[] = $v['region_id'];
                }
            }

            $aRegionsID = array_unique($aRegionsID);
            $aLvl = array(Geo::lvlRegion, Geo::lvlCity);
            $bCountries = Geo::coveringType(Geo::COVERING_COUNTRIES);
            if($bCountries){
                $aLvl[] = Geo::lvlCountry;
            }
            $aRegionsData = Geo::model()->regionsList($aLvl, array('id' => $aRegionsID));
            if($bCountries){
                $aCountries = Geo::countriesList();
            }

            foreach ($aResult as &$v) {
                foreach ($v as &$vv) {
                    $vv['regions'] = array_unique($vv['regions']);
                    $regionsResult = array();
                    foreach ($vv['regions'] as $id) {
                        if (isset($aRegionsData[$id])) {
                            if($bCountries){
                                $r = $aRegionsData[$id];
                                if($r['numlevel'] == Geo::lvlCountry){
                                    $t = $r['title'];
                                }else{
                                    $t = $aCountries[ $r['country'] ]['title'].' / '.$r['title'];
                                }
                            }else{
                                $t = $aRegionsData[$id]['title'];
                            }
                            $regionsResult[] = array('id' => $id, 't' => $t);
                        }

                    }
                    $vv['regions'] = $regionsResult;
                }
                unset($vv);
            }
            unset($v);
        }

        return $aResult;
    }

    /**
     * Сохранение настроек региональной стоимости услуг
     * @param integer $nSvcID ID услуг
     * @param array $aData данные
     */
    public function svcPriceExSave($nSvcID, array $aData)
    {
        if ($nSvcID <= 0) return;

        $sql = array();
        $id = 1;
        $num = 1;
        foreach ($aData as $v) {
            if ($v['price'] <= 0 || empty($v['regions'])) {
                continue;
            }

            $v['regions'] = array_unique($v['regions']);
            foreach ($v['regions'] as $region) {
                $sql[] = array(
                    'id'        => $id,
                    'svc_id'    => $nSvcID,
                    'price'     => $v['price'],
                    'region_id' => $region,
                    'num'       => $num++,
                );
            }
            $id++;
        }

        $this->db->delete(TABLE_SHOPS_SVC_PRICE, array('svc_id' => $nSvcID));
        if (!empty($sql)) {
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_SHOPS_SVC_PRICE, $v);
            }
        }
    }

    public function svcCron()
    {
        $sNow = $this->db->now();
        $sEmpty = '0000-00-00 00:00:00';

        # Деактивируем услугу "Выделение"
        $this->db->exec('UPDATE ' . TABLE_SHOPS . '
            SET svc = (svc - ' . Shops::SERVICE_MARK . '), svc_marked_to = :empty
            WHERE (svc & ' . Shops::SERVICE_MARK . ') AND svc_marked_to <= :now',
            array(':now' => $sNow, ':empty' => $sEmpty)
        );

        # Деактивируем услугу "Закрепление"
        $this->db->exec('UPDATE ' . TABLE_SHOPS . '
            SET svc = (svc - ' . Shops::SERVICE_FIX . '), svc_fixed_to = :empty, svc_fixed_order = :empty
            WHERE (svc & ' . Shops::SERVICE_FIX . ') AND svc_fixed_to <= :now',
            array(':now' => $sNow, ':empty' => $sEmpty)
        );
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_SHOPS_CATEGORIES => array('type' => 'table', 'fields' => $this->langCategories),
        );
    }
}