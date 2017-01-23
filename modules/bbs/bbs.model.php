<?php

use bff\db\NestedSetsTree;

class BBSModel extends Model
{
    /** @var BBS */
    protected $controller;

    /** @var NestedSetsTree для категорий */
    public $treeCategories;
    public $langCategories = array(
        'title'                 => TYPE_NOTAGS, // название
        'mtitle'                => TYPE_NOTAGS, // meta-title
        'mkeywords'             => TYPE_NOTAGS, // meta-keywords
        'mdescription'          => TYPE_NOTAGS, // meta-description
        'seotext'               => TYPE_STR, // seotext
        'titleh1'               => TYPE_STR, // H1
        'breadcrumb'            => TYPE_STR, // хлебная крошка
        'type_offer_form'       => TYPE_STR, // тип "предложение" в форме
        'type_offer_search'     => TYPE_STR, // тип "предложение" при поиске
        'type_seek_form'        => TYPE_STR, // тип "ищу" в форме
        'type_seek_search'      => TYPE_STR, // тип "ищу" при поиске
        'owner_private_form'    => TYPE_STR, // тип "представителя" в форме
        'owner_private_search'  => TYPE_STR, // тип "представителя" при поиске
        'owner_business_form'   => TYPE_STR, // тип "представителя" в форме
        'owner_business_search' => TYPE_STR, // тип "представителя" при поиске
        'subs_filter_title'     => TYPE_STR, // заголовок для подкатегорий в фильтре
    );

    public $langCategoriesTypes = array(
        'title' => TYPE_STR, // название
    );

    public $langSvcServices = array(
        'title_view'       => TYPE_STR, // название
        'description'      => TYPE_STR, // описание (краткое)
        'description_full' => TYPE_STR, // описание (подробное)
    );

    public $langSvcPacks = array(
        'title_view'       => TYPE_NOTAGS, // название
        'description'      => TYPE_STR, // описание (краткое)
        'description_full' => TYPE_STR, // описание (подробное)
    );

    /** @var array список шифруемых полей в таблице TABLE_BBS_ITEMS */
    protected $cryptItems = array();

    const ITEMS_ENOTIFY_UNPUBLICATESOON = 1;

    public function init()
    {
        parent::init();

        # подключаем nestedSets для категорий
        $this->treeCategories = new NestedSetsTree(TABLE_BBS_CATEGORIES);
        $this->treeCategories->init();
    }

    # --------------------------------------------------------------------
    # Объявления

    /**
     * Список объявлений (admin)
     * @param array $aFilter фильтр списка объявлений
     * @param bool $bCount только подсчет кол-ва объявлений
     * @param array $aBind подстановочные данные
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function itemsListing(array $aFilter, $bCount = false, array $aBind = array(), $sqlLimit = '', $sqlOrder = '') //admin
    {
        $aFilter = $this->prepareFilter($aFilter, 'I', $aBind);

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I
                     INNER JOIN ' . TABLE_USERS . ' U ON U.user_id = I.user_id
                     LEFT JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id1
                 ' . $aFilter['where'], $aFilter['bind']
            );
        }

        return $this->db->select('SELECT I.id, I.link, I.title, I.created, I.svc_press_status, I.svc_press_date, I.svc_press_date_last,
                    I.imgcnt, I.comments_cnt, I.status, I.deleted, I.moderated, I.import,
                    I.user_ip, U.user_id, C.title as cat_title,  I.cat_id1
               FROM ' . TABLE_BBS_ITEMS . ' I
                 INNER JOIN ' . TABLE_USERS . ' U ON U.user_id = I.user_id
                 LEFT JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id1
               ' . $aFilter['where'] . '
               ORDER BY I.' . $sqlOrder . '
               ' . $sqlLimit, $aFilter['bind']
        );
    }

    /**
     * Список объявлений по фильтру (frontend)
     * @param array $aFilter фильтр списка объявлений
     * @param bool $bCount только подсчет кол-ва объявлений
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @param integer $nListCurrencyID ID текущей валюты, формируемого списка ОБ или 0
     * @return mixed
     */
    public function itemsList(array $aFilter = array(), $bCount = false, $sqlLimit = '', $sqlOrder = '', $nListCurrencyID = 0)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I ' . $aFilter['where'], $aFilter['bind']);
        }
        $bDistricts = Geo::districtsEnabled();

        $aFilter['bind'][':lang'] = LNG;
        $aData = $this->db->select('SELECT I.id, I.title, I.link, I.img_s, I.img_m, I.imgcnt as imgs,
                                         I.addr_lat as lat, I.addr_lon as lon, I.addr_addr,
                                         I.price, I.price_curr, I.price_ex,'.($bDistricts ? 'I.district_id,' : '').'
                                         ((I.svc & ' . BBS::SERVICE_MARK . ') > 0) as svc_marked,
                                         ((I.svc & ' . BBS::SERVICE_FIX . ') > 0) as svc_fixed,
                                         ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                                         ((I.svc & ' . BBS::SERVICE_UP . ') > 0) as svc_up,
                                         I.publicated,
                                         I.modified,
                                         C.price_sett, C.price as price_on, CL.title as cat_title,
                                         R.title_'.LNG.' AS city_title
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    LEFT JOIN ' . TABLE_REGIONS . ' R ON I.city_id = R.id
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') . '
                                  ' . $sqlLimit, $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        if ($bDistricts) {
            $aDistricts = array();
            foreach ($aData as $v) {
                if ($v['district_id']) {
                    $aDistricts[] = $v['district_id'];
                }
            }
            if ( ! empty($aDistricts)) {
                $aDistricts = array_unique($aDistricts);
                $aDistricts = $this->db->select_key('SELECT id, title_' . LNG . ' as t
                                  FROM ' . TABLE_REGIONS_DISTRICTS . '
                                  WHERE ' . $this->db->prepareIN('id', $aDistricts));
            }
        }

        $aFavoritesID = $this->controller->getFavorites(User::id());
        foreach ($aData as &$v) {
            # помечаем избранные
            $v['fav'] = in_array($v['id'], $aFavoritesID);
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $nListCurrencyID);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][LNG]) ? $v['price_sett']['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link']);
            # район города
            if ($bDistricts && $v['district_id'] && ! empty($aDistricts[ $v['district_id'] ])) {
                $v['district_title'] = $aDistricts[ $v['district_id'] ]['t'];
            }
        }
        unset($v);

        return $aData;
    }
    
    /**
     * Список объявлений по фильтру для экспорта
     * @param array $aFilter фильтр списка объявлений
     * @param string $lang выбранный язык
     * @param array $aFields список дополнительных полей для выборки
     * @param bool $bCount только подсчёт
     * @param mixed $nLimit ограничение выборки, false - без ограничения
     * @return mixed
     */
    public function itemsListExport(array $aFilter = array(), $lang = LNG, $aFields = array(), $bCount = false, $nLimit = false)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I', array(':lang'=>$lang));

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(I.id)
                      FROM ' . TABLE_BBS_ITEMS . ' I
                        INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                      ' . $aFilter['where'], $aFilter['bind']);
        }

        $aFields = ( ! empty($aFields) ? ', '.implode(',',$aFields) : '');
        $aData = $this->db->select_key('SELECT I.id, I.title_edit as title, I.descr, I.user_id, I.shop_id, I.cat_id, I.city_id,
                                        R.title_' . $lang . ' as city_title, I.metro_id, RM.title_' . $lang . ' as metro_title,
                                        U.email, I.addr_addr, I.addr_lat, I.addr_lon,
                                        I.price, I.price_curr, I.price_ex, I.phones, I.skype, I.icq, I.name, U.phone as u_phone,
                                        U.phones as u_phones, U.skype as u_skype, U.icq as u_icq, I.video
                                        '.$aFields.'
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                                    INNER JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                                    INNER JOIN ' . TABLE_REGIONS . ' R ON I.city_id = R.id
                                    LEFT JOIN ' . TABLE_REGIONS_METRO . ' RM ON I.metro_id = RM.id
                                  ' . $aFilter['where'] .
                                  (!empty($nLimit) ? $this->db->prepareLimit(0, $nLimit) : ''), 'id', $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        return $aData;
    }

    /**
     * Данные об объявлениях для экспорта на печать
     * @param array $aItemsID ID объявлений для экспорта
     * @param string $lang ключ языка
     * @return array ['items' - данные об объявлениях]
     */
    public function itemsListExportPrint(array $aItemsID, $lang = LNG)
    {
        $aFilter = array(
            'id' => $aItemsID,
        );

        $aFilter = $this->prepareFilter($aFilter, 'I');
        $aData = array();

        $aData['items'] = $this->db->select('
            SELECT I.*,
                   R.title_'.$lang.' AS city, RM.title_'.$lang.' AS metro,
                   U.email, U.phones as u_phones, U.skype as u_skype, U.icq as u_icq
            FROM ' . TABLE_BBS_ITEMS . ' I
                LEFT JOIN ' . TABLE_REGIONS_METRO . ' RM ON I.metro_id = RM.id,
                '.TABLE_USERS.' U,
                '.TABLE_REGIONS.' R
            ' . $aFilter['where'].' AND I.user_id = U.user_id AND I.reg3_city = R.id
            ORDER BY I.id', $aFilter['bind']
        );

        $aCats = array(); # ID категорий
        $aRegs = array(); # ID регионов
        foreach ($aData['items'] as &$v) {
            foreach (array('cat_id', 'cat_id1', 'cat_id2', 'cat_id3', 'cat_id4') as $c) {
                if (!$v[$c]) continue;
                if (!in_array($v[$c], $aCats)){
                    $aCats[] = $v[$c];
                }
            }
            foreach (array('reg1_country', 'reg2_region') as $c) {
                if (!$v[$c]) continue;
                if (!in_array($v[$c], $aRegs)) {
                    $aRegs[] = $v[$c];
                }
            }
        } unset($v);

        # Названия категорий (полный путь)
        if (!empty($aCats)) {
            $aFl = array(
                'lang' => $lang,
                'id' => $aCats,
            );
            $aFl = $this->prepareFilter($aFl, 'L');
            $aCats = $this->db->select_key('
                SELECT L.id, L.title
                FROM ' . TABLE_BBS_CATEGORIES_LANG . ' L
                ' . $aFl['where'], 'id', $aFl['bind']);

            foreach ($aData['items'] as &$v) {
                $v['category'] = $aCats[ $v['cat_id'] ]['title'];
                $sPath = '';
                foreach (array('cat_id1', 'cat_id2', 'cat_id3', 'cat_id4') as $c) {
                    if (!$v[$c]) continue;
                    if ($sPath) $sPath .= ' / ';
                    $sPath .= $aCats[ $v[$c] ]['title'];
                }
                $v['category_path'] = $sPath;
            } unset($v);
        }

        # Названия регионов
        if (!empty($aRegs)) {
            $aFl = array(
                'id' => $aRegs,
            );
            $aFl = $this->prepareFilter($aFl, 'R');
            $aRegs = $this->db->select_key('
                SELECT R.id, R.title_'.$lang.' AS title
                FROM ' . TABLE_REGIONS . ' R
                ' . $aFl['where'], 'id', $aFl['bind']);
            foreach ($aData['items'] as &$v) {
                $v['country'] = $aRegs[ $v['reg1_country'] ]['title'];
                $v['region'] = $aRegs[ $v['reg2_region'] ]['title'];
            } unset($v);
        }

        return $aData;
    }

    /**
     * Список "моих" объявлений по фильтру (frontend)
     * @param array $aFilter фильтр списка объявлений
     * @param bool $bCount только подсчет кол-ва объявлений
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @param integer $nListCurrencyID ID текущей валюты, формируемого списка ОБ или 0
     * @return mixed
     */
    public function itemsListMy(array $aFilter = array(), $bCount = false, $sqlLimit = '', $sqlOrder = '', $nListCurrencyID = 0)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I ' . $aFilter['where'], $aFilter['bind']);
        }

        $aFilter['bind'][':lang'] = LNG;
        $aData = $this->db->select('SELECT I.id, I.title, I.link, I.img_s, I.imgcnt as imgs,
                                         I.status, I.moderated, I.publicated, I.publicated_to,
                                         I.views_item_total, I.views_contacts_total,
                                         I.messages_total, I.messages_new,
                                         I.price, I.price_curr, I.price_ex,
                                         ((I.svc & ' . BBS::SERVICE_MARK . ') > 0) as svc_marked,
                                         ((I.svc & ' . BBS::SERVICE_FIX . ') > 0) as svc_fixed,
                                         ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                                         C.price_sett, C.price as price_on, CL.title as cat_title
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY I.' . $sqlOrder : '') . '
                                  ' . $sqlLimit, $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        foreach ($aData as &$v) {
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $nListCurrencyID);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][LNG]) ? $v['price_sett']['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link']);
        }
        unset($v);

        return $aData;
    }

    /**
     * Список объявлений для переписки во внутренней почте (frontend)
     * @param array $aItemID ID объявлений
     * @param integer $nListCurrencyID ID текущей валюты, формируемого списка ОБ или 0
     * @return mixed
     */
    public function itemsListChat(array $aItemID = array(), $nListCurrencyID = 0)
    {
        $aFilter = $this->prepareFilter(array('id' => $aItemID), 'I', array(':lang' => LNG));

        $aData = $this->db->select_key('SELECT I.id, I.title, I.link, I.img_s, I.imgcnt as imgs,
                                         I.status, I.price, I.price_curr, I.price_ex,
                                         C.price_sett, C.price as price_on, CL.title as cat_title
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.cat_id AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY I.' . $sqlOrder : ''),
            'id', $aFilter['bind']
        );
        if (empty($aData)) {
            return array();
        }

        foreach ($aData as &$v) {
            # форматируем цену
            if ($v['price_on'] = (!empty($v['price_on']))) {
                $v['price'] = tpl::itemPrice($v['price'], $v['price_curr'], $v['price_ex'], $nListCurrencyID);
                if (($v['price_mod'] = ($v['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $v['price_sett'] = func::unserialize($v['price_sett']);
                    $v['price_mod'] = (!empty($v['price_sett']['mod_title'][LNG]) ? $v['price_sett']['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $v['price_mod'] = '';
                }
            } else {
                $v['price_mod'] = '';
            }
            unset($v['price_curr'], $v['price_ex'], $v['price_sett']);
            # формируем ссылку
            $v['link'] = BBS::urlDynamic($v['link']);
        }
        unset($v);

        return $aData;
    }

    /**
     * Помечаем все новые сообщения переписки связанные с объявлениями как "прочитанные"
     * @param array $aItemsID (ID объявления => кол-во прочитанных, ...)
     */
    public function itemsListChatSetReaded(array $aItemsID = array())
    {
        if (empty($aItemsID)) {
            return;
        }
        $aUpdateData = array();
        foreach ($aItemsID as $k => $i) {
            $aUpdateData[] = "WHEN $k THEN (messages_new - $i)";
        }
        if (!empty($aUpdateData)) {
            $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
                SET messages_new = CASE id ' . join(' ', $aUpdateData) . ' ELSE messages_new END
                WHERE id IN (' . join(',', array_keys($aItemsID)) . ')'
            );
        }
    }

    /**
     * Список категорий, в которые входят объявления
     * @param array $aFilter фильтр списка объявлений
     * @param integer $nNumlevel уровень категорий
     * @return array
     */
    public function itemsListCategories(array $aFilter, $nNumlevel = 1)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');
        $aFilter['bind'][':lang'] = LNG;
        if (empty($nNumlevel) || $nNumlevel < 1 || $nNumlevel > BBS::CATS_MAXDEEP) {
            $nNumlevel = 1;
        }

        $sCatField = 'cat_id' . $nNumlevel;
        $aData = $this->db->select_key('SELECT C.id, C.pid, CL.title, COUNT(I.id) as items
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.' . $sCatField . '
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON CL.id = I.' . $sCatField . ' AND CL.lang = :lang
                                  ' . $aFilter['where'] . '
                                  GROUP BY I.' . $sCatField . '
                                  ORDER BY C.numleft', 'id',
            $aFilter['bind']
        );

        return (is_array($aData) ? $aData : array());
    }

    /**
     * Быстрый поиск объявлений (frontend)
     * @param array $aFilter фильтр списка
     * @param bool $bCount только подсчет кол-ва объявлений
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function itemsQuickSearch(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I ' . $aFilter['where'], $aFilter['bind']);
        }

        $aData = $this->db->select_key('SELECT I.id, I.title, I.link, I.imgcnt,
                                    I.price, I.price_curr, I.price_ex, C.price as price_on
                                  FROM ' . TABLE_BBS_ITEMS . ' I
                                    INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') . '
                                  ' . $sqlLimit, 'id', $aFilter['bind']
        );
        if (!empty($aData)) {
            # формируем ссылки на изображения ОБ
            $oImages = $this->controller->itemImages();
            $aImages = $oImages->getItemsImagesData(array_keys($aData));
            foreach ($aData as $id => &$v) {
                $v['img'] = array();
                if ($v['imgcnt'] > 0 && !empty($aImages[$id])) {
                    $aItemImages = $aImages[$id];
                    $oImages->setRecordID($id);
                    foreach ($aItemImages as $img) {
                        $v['img'][] = $oImages->getURL($img, BBSItemImages::szSmall);
                    }
                }
                # формируем ссылку
                $v['link'] = BBS::urlDynamic($v['link']);
            }
            unset($v);
        }

        return $aData;
    }

    /**
     * Сохранение/обновление данных об объявлении
     * @param integer $nItemID ID объявления
     * @param array $aData данные
     * @param mixed $sDynpropsDataKey ключ данных дин. свойств или FALSE
     * @return bool|int
     */
    public function itemSave($nItemID, $aData, $sDynpropsDataKey = false)
    {
        if (empty($aData)) {
            return false;
        }
        if (!empty($sDynpropsDataKey) && !empty($aData['cat_id'])) {
            $aDataDP = $this->controller->dpSave($aData['cat_id'], $sDynpropsDataKey);
            $aData = array_merge($aData, $aDataDP);
        }

        if (array_key_exists('phones', $aData)) {
            if (empty($aData['phones']) || !is_array($aData['phones'])) {
                $aData['phones'] = array();
            }
            $aData['phones'] = serialize($aData['phones']);
        }
        if (isset($aData['status']) || isset($aData['status_prev'])) {
            $aData['status_changed'] = $this->db->now();
        }

        $aData['modified'] = $this->db->now();
        if ($nItemID) {
            # обновляем данные об объявлении
            return $this->db->update(TABLE_BBS_ITEMS, $aData, array('id' => $nItemID), array(), $this->cryptItems);
        } else {
            # создаем объявление
            $aData['user_ip'] = Request::remoteAddress(); # IP адрес текущего пользователя
            $aData['created'] = $this->db->now();
            $nItemID = $this->db->insert(TABLE_BBS_ITEMS, $aData, 'id', array(), $this->cryptItems);
            if ($nItemID) {
                if (!empty($aData['user_id']) && $aData['status'] != BBS::STATUS_NOTACTIVATED) {
                    # накручиваем счетчик кол-ва объявлений пользователя (+1)
                    $this->security->userCounter('items', 1, $aData['user_id'], true);
                }
                if (isset($aData['link'])) {
                    # дополняем ссылку + "ID.html"
                    $this->db->update(TABLE_BBS_ITEMS, array(
                            'link' => $aData['link'] . $nItemID . '.html'
                        ), array('id' => $nItemID)
                    );
                }
            }

            return $nItemID;
        }
    }

    /**
     * Данные об объявлении
     * @param integer $nItemID ID объявления
     * @param array $aFields
     * @param bool $bEdit
     * @return mixed
     */
    public function itemData($nItemID, array $aFields = array(), $bEdit = false)
    {
        return $this->itemDataByFilter(array('id' => $nItemID), $aFields, $bEdit);
    }

    /**
     * Данные об объявлении
     * @param array $aFilter
     * @param array $aFields
     * @param bool $bEdit
     * @return mixed
     */
    public function itemDataByFilter($aFilter, array $aFields = array(), $bEdit = false)
    {
        $aFilter = $this->prepareFilter($aFilter, 'I');

        if (empty($aFields)) {
            $aFields = array('*') + (!empty($this->cryptItems) ? $this->cryptItems : array());
        }

        $aParams = array();
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }
        foreach ($aFields as $v) {
            if (in_array($v, $this->cryptItems)) {
                $aParams[] = "BFF_DECRYPT(I.$v) as $v";
            } else {
                $aParams[] = 'I.' . $v;
            }
        }
        $aParams[] = 'U.email, U.phone_number, U.blocked as user_blocked, U.shop_id as user_shop_id';

        if ($bEdit) {
            # берем title для редактирования
            $aParams[] = 'I.title_edit as title';
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_ITEMS . ' I
                            INNER JOIN ' . TABLE_USERS . ' U ON U.user_id = I.user_id
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        if (isset($aData['phones'])) {
            $aData['phones'] = (!empty($aData['phones']) ? func::unserialize($aData['phones']) : array());
        }
        if (isset($aData['link'])) {
            $aData['link'] = BBS::urlDynamic($aData['link']);
        }

        return $aData;
    }

    /**
     * Получение данных объявления для отправки email-уведомления
     * @param integer $nItemID ID объявления
     * @return array|bool
     */
    public function itemData2Email($nItemID)
    {
        $aData = $this->db->one_array('SELECT I.id as item_id, I.status, I.deleted,
                    I.link as item_link, I.title as item_title,
                    I.user_id, U.name, U.email, U.blocked as user_blocked
                    FROM ' . TABLE_BBS_ITEMS . ' I,
                         ' . TABLE_USERS . ' U
                    WHERE I.id = :id
                      AND I.user_id = U.user_id', array(':id' => $nItemID)
        );

        do {
            if (empty($aData)) {
                break;
            }
            # ОБ удалялось пользователем
            if (!empty($aData['deleted'])) {
                break;
            }
            # ОБ неактивировано
            if ((int)$aData['status'] === BBS::STATUS_NOTACTIVATED) {
                break;
            }
            # Проверяем владельца:
            # - незарегистрированный
            if (empty($aData['user_id'])) {
                break;
            }
            # - заблокирован
            if (!empty($aData['user_blocked'])) {
                break;
            }

            # Формируем ссылку:
            $aData['item_link'] = BBS::urlDynamic($aData['item_link']);

            return $aData;
        } while (false);

        return false;
    }

    /**
     * Получение данных ОБ для страницы просмотра ОБ
     * @param integer $nItemID ID объявления
     * @return array
     */
    public function itemDataView($nItemID)
    {
        if (empty($nItemID) || $nItemID < 0) {
            return array();
        }

        $data = $this->db->one_array('SELECT I.*,
                            ((I.svc & ' . BBS::SERVICE_QUICK . ') > 0) as svc_quick,
                            CL.title as cat_title, C.addr as cat_addr,
                            C.price as price_on, C.price_sett,
                            U.phone_number, U.phone_number_verified
                       FROM ' . TABLE_BBS_ITEMS . ' I,
                            ' . TABLE_USERS . ' U,
                            ' . TABLE_BBS_CATEGORIES . ' C
                            LEFT JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL ON ' . $this->db->langAnd(false, 'C', 'CL') . '
                       WHERE I.id = :id
                         AND I.cat_id = C.id
                         AND I.user_id = U.user_id
                       LIMIT 1', array(':id' => $nItemID)
        );

        if (!empty($data)) {
            # форматируем цену
            if ($data['price_on']) {
                if (isset($data['price_sett'])) {
                    $priceSett =& $data['price_sett'];
                    $priceSett = (!empty($priceSett) ? func::unserialize($priceSett) : array());
                    if ($priceSett === false) {
                        $priceSett = array();
                    }
                }
                $data['price'] = tpl::itemPrice($data['price'], $data['price_curr'], $data['price_ex']);
                if (($data['price_mod'] = ($data['price_ex'] & BBS::PRICE_EX_MOD))) {
                    $data['price_mod'] = (!empty($priceSett['mod_title'][LNG]) ? $priceSett['mod_title'][LNG] : _t('bbs', 'Торг возможен'));
                } else {
                    $data['price_mod'] = '';
                }
                unset($data['price_curr'], $data['price_ex'], $data['price_sett'], $data['price_search']);
            }

            # формируем данные о городе и метро
            # формируем данные о регионе ОБ
            $data['country'] = Geo::regionData($data['reg1_country']);
            $data['country_title'] = (!empty($data['country']['title']) ? $data['country']['title'] : '');
            $data['region'] = Geo::regionData($data['reg2_region']);
            $data['region_title'] = (!empty($data['region']['title']) ? $data['region']['title'] : '');
            $data['city'] = Geo::regionData($data['reg3_city']);
            $data['city_title'] = (!empty($data['city']['title']) ? $data['city']['title'] : '');
            if ($data['metro_id']) {
                $data['metro_data'] = Geo::model()->metroData($data['metro_id'], false);
            }
            # район
            if (Geo::districtsEnabled() && $data['district_id']) {
                $data['district_data'] = Geo::model()->districtData($data['district_id']);
                if (empty($data['district_data'])) {
                    $aData['district_id'] = 0;
                } else {
                    $data['district_data']['title'] = $data['district_data']['title_'.LNG];
                }
            }

            # телефоны
            $data['phones'] = (!empty($data['phones']) ? func::unserialize($data['phones']) : array());

            # контакты
            $data['contacts'] = (!empty($data['contacts']) ? func::unserialize($data['contacts']) : array());

            # телефон регистрации
            if (Users::registerPhoneContacts() && $data['phone_number'] && $data['phone_number_verified']) {
                $phoneNumber = array('v'=>$data['phone_number'],'m'=>mb_substr($data['phone_number'], 0, 2) . 'x xxx xxxx');
                array_unshift($data['phones'], $phoneNumber);
                if (!isset($data['contacts']['phones'])) {
                    $data['contacts']['phones'] = array();
                }
                array_unshift($data['contacts']['phones'], $phoneNumber['m']);
            }

            $data['contacts']['has'] = ($data['phones'] || $data['skype'] || $data['icq']);

            # дин. свойства
            if ($this->controller->dp()->cacheKey) {
                $sql = $this->controller->dpPrepareSelectFieldsQuery('', $data['cat_id']);
                if ( ! empty($sql)) {
                    $dp = $this->db->one_array('SELECT '.$sql.' FROM '.TABLE_BBS_ITEMS.' WHERE id = :id', array(':id'=>$nItemID));
                    if ( ! empty($dp) && is_array($dp)) {
                        foreach ($dp as $k=>$v) {
                            if ( ! isset($data[$k])) {
                                $data[$k] = $v;
                            }
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Получение данных о нескольких объявлениях по фильтру
     * @param array $aFilter
     * @param array $aFields
     * @param string $sqlOrder
     * @param mixed $nLimit
     * @return mixed
     */
    public function itemsDataByFilter(array $aFilter, array $aFields = array(), $sqlOrder = '', $nLimit = false)
    {
        $aFilter = $this->prepareFilter($aFilter);

        $aParams = array();
        if (empty($aFields)) {
            $aFields = array('*');
        }
        if (!is_array($aFields)) {
            $aFields = array($aFields);
        }
        foreach ($aFields as $v) {
            $aParams[] = $v;
        }

        return $this->db->select_key('SELECT ' . join(',', $aParams) . '
                                  FROM ' . TABLE_BBS_ITEMS . '
                                  ' . $aFilter['where']
            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
            . (!empty($nLimit) ? $this->db->prepareLimit(0, $nLimit) : ''),
            'id',
            $aFilter['bind']
        );
    }

    /**
     * Обновляем данные об объявлениях
     * @param array $aItemsID ID объявлений
     * @param array $aData данные
     * @return integer кол-во обновленных объявлений
     */
    public function itemsSave(array $aItemsID, array $aData)
    {
        if (empty($aItemsID) || empty($aData)) {
            return 0;
        }

        return $this->db->update(TABLE_BBS_ITEMS, $aData, array('id' => $aItemsID));
    }

    /**
     * Отвязываем объявления от магазина (при его удалении)
     * @param integer $nShopID ID магазина
     * @return integer кол-во затронутых объявлений
     */
    public function itemsUnlinkShop($nShopID)
    {
        if (empty($nShopID) || $nShopID <= 0) {
            return 0;
        }

        return $this->db->update(TABLE_BBS_ITEMS, array('shop_id' => 0), array('shop_id' => $nShopID));
    }

    /**
     * Привязываем объявления пользователя к магазину
     * @param integer $nShopID ID магазина
     * @return integer кол-во затронутых объявлений
     */
    public function itemsLinkShop($nUserID, $nShopID)
    {
        if (empty($nUserID) || $nUserID < 0 || empty($nShopID) || $nShopID < 0) {
            return 0;
        }

        return $this->db->update(TABLE_BBS_ITEMS, array('shop_id' => $nShopID), array('user_id' => $nUserID));
    }

    /**
     * Получаем общее кол-во объявлений, ожидающих модерации
     * @return integer
     */
    public function itemsModeratingCounter()
    {
        $aFilter = $this->prepareFilter(array(
                ':mod'    => 'I.moderated != 1',
                'deleted' => 0,
                'status'  => array(BBS::STATUS_PUBLICATED, BBS::STATUS_BLOCKED),
            ), 'I'
        );

        return (int)$this->db->one_data('SELECT COUNT(I.id)
                FROM ' . TABLE_BBS_ITEMS . ' I
                    INNER JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id AND U.blocked = 0
                ' . $aFilter['where'], $aFilter['bind']
        );
    }

    /**
     * Получаем общее кол-во опубликованных объявлений
     * @param array $aFilter доп. фильтр
     * @return integer
     */
    public function itemsPublicatedCounter(array $aFilter = array())
    {
        $aFilter['status'] = BBS::STATUS_PUBLICATED;
        if (BBS::premoderation()) {
            $aFilter[':mod'] = 'moderated > 0';
        }
        $aFilter = $this->prepareFilter($aFilter);

        return (int)$this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_BBS_ITEMS . '
                ' . $aFilter['where'], $aFilter['bind']
        );
    }

    /**
     * Публикация нескольких объявлений по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsPublicate(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        $aItems = $this->itemsDataByFilter($aFilter, array('id'));
        if (empty($aItems)) {
            return 0;
        }

        return $this->itemsSave(array_keys($aItems), array(
                'status_prev = status',
                'status'           => BBS::STATUS_PUBLICATED,
                'publicated_to'    => $this->controller->getItemPublicationPeriod(), # от текущей даты
                'publicated_order' => $this->db->now(),
            )
        );
    }

    /**
     * Публикация всех на текущий момент снятых с публикации объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsPublicateAllUnpublicated()
    {
        return (int)$this->db->update(TABLE_BBS_ITEMS, array(
                'status_prev = status',
                'status'           => BBS::STATUS_PUBLICATED,
                'status_changed'   => $this->db->now(),
                'publicated_to'    => $this->controller->getItemPublicationPeriod(),
                'publicated_order' => $this->db->now(),
            ),
            array(
                'status' => BBS::STATUS_PUBLICATED_OUT,
                'deleted' => 0,
            )
        );
    }

    /**
     * Продление нескольких объявлений по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsRefresh(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        $aItems = $this->itemsDataByFilter($aFilter, array('id', 'publicated_to'));
        if (empty($aItems)) {
            return 0;
        }

        $updated = 0;
        foreach ($aItems as &$v)
        {
            $res = $this->itemSave($v['id'], array(
                # от даты завершения публикации объявления
                'publicated_to' => $this->controller->getItemRefreshPeriod($v['publicated_to']),
            ));
            if ( ! empty($res)) $updated++;
        } unset($v);
        return $updated;
    }

    /**
     * Снятие нескольких объявлений с публикации по фильтру
     * @param array $aFilter фильтр требуемых объявлений
     * @return integer кол-во затронутых объявлений
     */
    public function itemsUnpublicate(array $aFilter)
    {
        if (empty($aFilter)) {
            return 0;
        }

        $aItems = $this->itemsDataByFilter($aFilter, array('id'));
        if (empty($aItems)) {
            return 0;
        }

        return $this->itemsSave(array_keys($aItems), array(
                'status_prev = status',
                'status'        => BBS::STATUS_PUBLICATED_OUT,
                'publicated_to' => $this->db->now(),
            )
        );
    }

    /**
     * Удаление нескольких объявлений одного пользователя
     * @param array $aItemsID ID удаляемых объявлений
     * @param bool $bUserCounterUpdate выполнять актуализацию счетчика объявлений пользователя
     * @return int кол-во удаленных объявлений
     */
    public function itemsDelete(array $aItemsID, $bUserCounterUpdate)
    {
        if (empty($aItemsID)) {
            return 0;
        }

        $aData = $this->db->select('SELECT I.id, I.user_id, I.status, I.cat_id, I.cat_id1, I.cat_id2, I.cat_id3, I.cat_type,
                                           I.imgcnt, I.svc_press_status, I.claims_cnt, I.messages_total, I.moderated,
                                           U.activated as user_activated
                                    FROM ' . TABLE_BBS_ITEMS . ' I
                                        LEFT JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                                    WHERE ' . $this->db->prepareIN('I.id', $aItemsID)
        );
        if (empty($aData)) {
            return 0;
        }

        $nUserCounterDecrement = 0;
        $bClaimsCounterUpdate = false;
        $bModerationCounterUpdate = false;
        $bPressCounterUpdate = false;
        $bMessagesUnlink = false;

        $aItemsID = array();
        $oImages = $this->controller->itemImages();
        foreach ($aData as &$v) {
            # удаляем изображения
            if ($v['imgcnt'] > 0) {
                $oImages->setRecordID($v['id']);
                $oImages->deleteAllImages(false);
            }
            if ($bUserCounterUpdate && $v['status'] != BBS::STATUS_NOTACTIVATED) {
                $nUserCounterDecrement++;
            }
            if ($v['messages_total'] > 0) {
                $bMessagesUnlink = true;
            }
            if ($v['claims_cnt'] > 0) {
                $bClaimsCounterUpdate = true;
            }
            if ($v['svc_press_status'] > 0) {
                $bPressCounterUpdate = true;
            }
            if ($v['moderated'] != 1) {
                $bModerationCounterUpdate = true;
            }
            $aItemsID[] = $v['id'];
        }
        unset($v);

        $this->db->delete(TABLE_BBS_ITEMS_FAV, array('item_id' => $aItemsID));
        $this->db->delete(TABLE_BBS_ITEMS_VIEWS, array('item_id' => $aItemsID));
        $this->db->delete(TABLE_BBS_ITEMS_CLAIMS, array('item_id' => $aItemsID));

        if (BBS::commentsEnabled()) {
            # удаляем комментарии
            $this->db->delete(TABLE_BBS_ITEMS_COMMENTS, array('item_id' => $aItemsID));
            # пересчитываем кол-во непромодерированных комментариев
            $this->controller->itemComments()->updateUnmoderatedAllCounter(NULL);
        }

        $res = $this->db->delete(TABLE_BBS_ITEMS, array('id' => $aItemsID));

        if (!empty($res)) {
            # удаляем связь сообщений внутренней почты с удаляемыми объявлениями
            if ($bMessagesUnlink) {
                InternalMail::model()->unlinkMessagesItemsID($aItemsID);
            }

            # актуализируем счетчик необработанных жалоб
            if ($bClaimsCounterUpdate) {
                $this->controller->claimsCounterUpdate(null);
            }

            # актуализируем счетчик ожидающих печати
            if ($bPressCounterUpdate) {
                $this->controller->pressCounterUpdate(null);
            }

            # актуализируем счетчик объявлений пользователя
            if ($bUserCounterUpdate && $nUserCounterDecrement > 0) {
				$aItemData = reset($aData);
				if($this->security->userCounter('items', false, $aItemData['user_id'], true) >0) {
					$this->security->userCounter('items', -$nUserCounterDecrement, $aItemData['user_id'], true);
				}
            }

            # актуализируем счетчик "на модерации"
            if ($bModerationCounterUpdate) {
                $this->controller->moderationCounterUpdate(null);
            }
        }

        return intval($res);
    }

    /**
     * Получаем ID избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @return array|mixed
     */
    public function itemsFavData($nUserID)
    {
        if (empty($nUserID)) {
            return array();
        }

        return $this->db->select_one_column('SELECT item_id
                FROM ' . TABLE_BBS_ITEMS_FAV . '
                WHERE user_id = :userID GROUP BY item_id',
            array(':userID' => $nUserID)
        );
    }

    /**
     * Сохранение избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @param array $aItemID ID объявлений
     * @return integer|mixed кол-во сохраненных ОБ или FALSE
     */
    public function itemsFavSave($nUserID, array $aItemID)
    {
        if (empty($aItemID) || !$nUserID) {
            return 0;
        }

        $aData = array();
        foreach ($aItemID as $id) {
            $aData[] = array(
                'item_id' => $id,
                'user_id' => $nUserID,
            );
        }

        return $this->db->multiInsert(TABLE_BBS_ITEMS_FAV, $aData);
    }

    /**
     * Удаление избранных ОБ пользователя
     * @param integer $nUserID ID пользователя
     * @param integer|boolean|array $mItemID ID объявления(-ний) или FALSE (всех избранных объявлений)
     * @return mixed
     */
    public function itemsFavDelete($nUserID, $mItemID = false)
    {
        $aCond = array('user_id' => $nUserID);
        if ($mItemID !== false) {
            $aCond['item_id'] = $mItemID;
        }

        return $this->db->delete(TABLE_BBS_ITEMS_FAV, $aCond);
    }

    /**
     * Накручиваем счетчик просмотров
     * @param integer $nItemID ID объявления
     * @param string $sViewType тип просмотра: 'item'=>просмотр ОБ, 'contacts'=>просмотр контактов ОБ
     * @param integer $nViewsToday текущий счетчик просмотров ОБ за сегодня или 0
     * @return boolean
     */
    public function itemViewsIncrement($nItemID, $sViewType, $nViewsToday = 0)
    {
        if (empty($nItemID) || !in_array($sViewType, array('item', 'contacts'))) {
            return false;
        }

        $sDate = date('Y-m-d');
        $sField = $sViewType . '_views';

        # TABLE_BBS_ITEMS_VIEWS:
        # 1. пытаемся вначале обновить статистику
        # поскольку запись о статистике за сегодня уже может быть создана
        $res = $this->db->update(TABLE_BBS_ITEMS_VIEWS,
            array($sField . ' = ' . $sField . ' + 1'),
            array('item_id' => $nItemID, 'period' => $sDate)
        );

        # обновить не получилось
        if (empty($res) && empty($nViewsToday)) {
            # 2. начинаем подсчет статистики за сегодня
            $res = $this->db->insert(TABLE_BBS_ITEMS_VIEWS, array(
                    'item_id' => $nItemID,
                    $sField   => 1,
                    'period'  => $sDate,
                ), false
            );
        }

        # TABLE_BBS_ITEMS:
        # 3. накручиваем счетчик просмотров ОБ/Контактов за сегодня (+ общий)
        if (!empty($res)) {
            $this->db->update(TABLE_BBS_ITEMS, array(
                    'views_total = views_total + 1',
                    'views_today = views_today + 1',
                    'views_' . $sViewType . '_total = views_' . $sViewType . '_total + 1',
                ), array('id' => $nItemID)
            );
        }

        return !empty($res);
    }

    /**
     * Получаем данные о статистике просмотров ОБ
     * @param integer $nItemID ID объявления
     * @return array
     */
    public function itemViewsData($nItemID)
    {
        $aResult = array('data' => array(), 'from' => '', 'to' => '', 'total' => 0, 'today' => 0);

        do {
            if (empty($nItemID)) {
                break;
            }

            $aData = $this->db->select('SELECT SUM(item_views) as item, SUM(contacts_views) as contacts, period
                        FROM ' . TABLE_BBS_ITEMS_VIEWS . '
                        WHERE item_id = :id
                        GROUP BY period
                        ORDER BY period ASC', array(':id' => $nItemID)
            );
            if (empty($aData)) {
                break;
            }
            foreach ($aData as $k => $v) {
                $aData[$k]['total'] = $v['item'] + $v['contacts'];
                $aData[$k]['date'] = $v['period'];
                unset($aData[$k]['period']);
            }

            $aItemData = $this->itemData($nItemID, array('views_total', 'views_today'));
            if (empty($aItemData)) {
                break;
            }
            $aResult['total'] = $aItemData['views_total'];
            $aResult['today'] = $aItemData['views_today'];

            $view = current($aData);
            $aResult['from'] = $view['date']; # от
            $nFrom = strtotime($view['date']);

            $view = end($aData);
            $aResult['to'] = $view['date']; # до
            $nTo = strtotime($view['date']);

            reset($aData);

            # дополняем днями, за которые статистика отсутствует
            $nDay = 86400;
            $nTotalDays = (($nTo - $nFrom) / $nDay) + 1;
            if ($nTotalDays > sizeof($aData)) {
                $aDataFull = array();
                foreach ($aData as $v) {
                    $aDataFull[$v['date']] = $v;
                }
                $aDataResult = array();
                for ($i = $nFrom; $i <= $nTo; $i += $nDay) {
                    $sDate = date('Y-m-d', $i);
                    if (isset($aDataFull[$sDate])) {
                        $aDataResult[$sDate] = $aDataFull[$sDate];
                    } else {
                        $aDataResult[$sDate] = array('item' => 0, 'contacts' => 0, 'total' => 0, 'date' => $sDate);
                    }
                }
                unset($aDataFull);
                $aData = array_values($aDataResult);
            }

            $aResult['data'] = $aData;

        } while (false);

        return $aResult;
    }

    /**
     * Актуализация статуса объявлений (cron)
     * Рекомендуемый период: раз в 10 минут
     */
    public function itemsCronStatus()
    {

            # Удаляем неактивированные объявления по прошествии суток
            $aItemsID = $this->db->select_one_column('SELECT id
                FROM ' . TABLE_BBS_ITEMS . '
                WHERE status = :status
                  AND activate_expire <= :now',
                array(
                    ':status' => BBS::STATUS_NOTACTIVATED,
                    ':now'    => $this->db->now()
                )
            );
            if (!empty($aItemsID)) {
                $this->itemsDelete($aItemsID, false);
                # email уведомления не отправляем, поскольку email адреса не подтверджались
            }

            # Снимаем с публикации просроченные объявления
            $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
                SET status = ' . BBS::STATUS_PUBLICATED_OUT . ',
                    status_prev = ' . BBS::STATUS_PUBLICATED . ',
                    status_changed = :now
                WHERE status = ' . BBS::STATUS_PUBLICATED . '
                  AND publicated_to <= :now',
                array(':now' => $this->db->now())
            );

        # Выполняем пересчет счетчиков ОБ (items):
        # Категории
        $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' SET items = 0');
        for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES . ' C,
                    (SELECT I.cat_id' . $i . ' as id, COUNT(I.id) as items
                        FROM ' . TABLE_BBS_ITEMS . ' I
                            LEFT JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                        WHERE I.status = ' . BBS::STATUS_PUBLICATED . ' AND (U.user_id IS NULL OR U.blocked = 0)
                          AND I.cat_id' . $i . '!=0
                     GROUP BY I.cat_id' . $i . ') as X
                SET C.items = X.items
                WHERE C.numlevel = ' . $i . ' AND C.id = X.id
            '
            );
        }
        # Типы категорий
        if (BBS::CATS_TYPES_EX) {
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES_TYPES . ' SET items = 0');
            $this->db->exec('UPDATE ' . TABLE_BBS_CATEGORIES_TYPES . ' T,
                     (SELECT I.cat_type as id, COUNT(I.id) as items
                        FROM ' . TABLE_BBS_ITEMS . ' I
                            LEFT JOIN ' . TABLE_USERS . ' U ON I.user_id = U.user_id
                        WHERE I.status = ' . BBS::STATUS_PUBLICATED . ' AND (U.user_id IS NULL OR U.blocked = 0)
                          AND I.cat_type != 0
                     GROUP BY I.cat_type) as X
                SET T.items = X.items
                WHERE T.id = X.id
            '
            );
        }
        # Пересчет общего кол-ва активных объявлений (config::site)
        config::save('bbs_items_total_publicated', $this->itemsPublicatedCounter(), true);
        # Актуализируем счетчик "на модерации"
        $this->controller->moderationCounterUpdate();
    }

    /**
     * Полное удаление удаленных пользователем объявлений через X дней после окончания публикации
     */
    public function itemsCronDelete()
    {

        $nDays = config::sys('bbs.delete.timeout', 0);
        if (!$nDays) return;

        $aItemsID = $this->db->select_one_column('SELECT id
            FROM '.TABLE_BBS_ITEMS.'
            WHERE deleted > 0 AND status = :status AND publicated_to < :date
        ', array(
            ':status' => BBS::STATUS_PUBLICATED_OUT,
            ':date'   => date('Y-m-d H:i:s', strtotime('- '.$nDays.' days')),
        ));
        if (!empty($aItemsID)) {
            $this->itemsDelete($aItemsID, false);
        }
    }

    /**
     * Актуализация статистики объявлений (cron)
     * Рекомендуемый период: раз в сутки (в 00:00)
     */
    public function itemsCronViews()
    {
        # Обнуляем статистику просмотров за сегодня
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . ' SET views_today = 0');

        # Удаляем историю просмотров старше X месяцев
        $this->db->exec('DELETE FROM ' . TABLE_BBS_ITEMS_VIEWS . '
            WHERE period < DATE_SUB(:now, INTERVAL 1 MONTH)', array(':now' => $this->db->now())
        );
    }

    /**
     * Данные об объявлениях для "Уведомления о завершении публикации объявлений" (cron)
     * @param array $days список дней за сколько необходимо отправить уведомление
     * @param integer $limit ограничение на выборку
     * @param string|boolean $date дата в формате Y-m-d
     * @return array
     */
    public function itemsCronUnpublicateSoon(array $days, $limit = 100, $date = false)
    {
        if (empty($days)) return array();
        if (empty($date)) return array();
        if (empty($limit) || $limit < 0) {
            $limit = 100;
        }

        $aFilter = array(
            'I.status = '.BBS::STATUS_PUBLICATED,
            'DATEDIFF(I.publicated_to,STR_TO_DATE(:date, :format)) IN ('.join(',', $days).')',
            'E.item_id IS NULL',
            'U.user_id = I.user_id',
            'US.user_id = I.user_id',
            'U.blocked = 0',
            'U.activated = 1',
            'U.enotify & '.Users::ENOTIFY_NEWS,
        );
        if(BBS::premoderation()) {
            $aFilter[] = 'I.moderated > 0';
        }

        $aFilter = $this->prepareFilter($aFilter, '', array(
            ':date'   => $date,
            ':type'   => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
            ':format' => '%Y-%m-%d',
        ));

        $data = $this->db->select_key('SELECT I.id as item_id, I.title as item_title, I.link as item_link, U.email,
                        I.user_id, U.user_id_ex, US.last_login,
                        DATEDIFF(I.publicated_to,STR_TO_DATE(:date, :format)) as days
                    FROM ' . TABLE_BBS_ITEMS . ' as I
                        LEFT JOIN '.TABLE_BBS_ITEMS_ENOTIFY.' E ON E.item_id = I.id AND sended = :date AND message_type = :type,
                         '. TABLE_USERS .' as U,
                         '. TABLE_USERS_STAT .' as US
                    '. $aFilter['where']
                     . $this->db->prepareLimit(0, $limit), 'item_id', $aFilter['bind']);

        if(empty($data)) $data = array();
        return $data;
    }

    /**
     * Уведомления о завершении срока публикации:
     * Работа со списком объявлений отправленных уведомлений о завершении срока публикации
     * @param integer $id ID объявления
     * @param string $date дата в формаре "Y-m-d"
     * @return bool true -
     */
    public function itemsCronUnpublicateSended($itemID, $date)
    {
        $data = $this->db->select_data(TABLE_BBS_ITEMS_ENOTIFY, 'item_id', array(
            'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
            'item_id'      => $itemID,
            'sended'       => $date,
        ));
        if (empty($data)) {
            $this->db->insert(TABLE_BBS_ITEMS_ENOTIFY, array(
                'item_id'      => $itemID,
                'sended'       => $date,
                'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
            ), false);
            return false;
        }
        return true;
    }

    /**
     * Очистка списка объявлений для которых выполнялась отправка уведомлений
     * о завершении срока публикации за указанную дату.
     * @param string $date дата в формате "Y-m-d"
     * @return mixed
     */
    public function itemsCronUnpublicateClearLast($date)
    {
        if (empty($date)) return false;
        return $this->db->delete(TABLE_BBS_ITEMS_ENOTIFY, array(
            'sended < :date',
            'message_type' => self::ITEMS_ENOTIFY_UNPUBLICATESOON,
        ), array(
            ':date' => $date,
        ));
    }

    /**
     * Получение объявлений для формирования файла Sitemap.xml (cron)
     * return callable callback-генератор строк вида array [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function itemsSitemapXmlData()
    {
        $aFilter = array(
            'status' => BBS::STATUS_PUBLICATED
        );
        if (BBS::premoderation()) {
            $aFilter[] = 'moderated > 0';
        }

        return function($count = false, callable $callback = null) use ($aFilter){
            if ($count) {
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->one_data('SELECT COUNT(*) FROM '.TABLE_BBS_ITEMS.' '.$aFilter['where'], $aFilter['bind']);
            } else {
                $aFilter = $this->prepareFilter($aFilter, '', array(
                    ':format' => '%Y-%m-%d',
                ));
                $this->db->select_iterator('
                    SELECT link as l, DATE_FORMAT(modified, :format) as m
                    FROM ' . TABLE_BBS_ITEMS . '
                    '. $aFilter['where'] .'
                    ORDER BY modified DESC',
                $aFilter['bind'],
                function(&$item) use (&$callback){
                    $item['l'] = BBS::urlDynamic($item['l']);
                    $callback($item);
                });
            }
            return false;
        };
    }

    /**
     * Получение текущей позиции опубликованного объявления в категории
     * @param integer $nItemID ID объявления
     * @param integer $nCategoryID ID основной категории
     * @param integer $nLimit ограничение поиска в списке
     * @return integer текущая позиция объявления
     */
    public function itemPositionInCategory($nItemID, $nCategoryID, $nLimit = 15)
    {
        if (empty($nLimit) || $nLimit < 0) {
            $nLimit = 30;
        }

        $nPosition = 0;
        do {
            # получаем список первых объявлений в категории
            $aItems = $this->itemsDataByFilter(array(
                    'status'  => BBS::STATUS_PUBLICATED,
                    'cat_id1' => $nCategoryID
                ), array('id'), 'publicated_order DESC', $nLimit
            );
            if (empty($aItems)) {
                break; # нет среди первых
            }
            # ищем $nItemID среди найденных
            $i = 1;
            foreach ($aItems as $id => $v) {
                if ($id == $nItemID) {
                    $nPosition = $i;
                    break;
                }
                $i++;
            }
        } while (false);

        return $nPosition;
    }

    /**
     * Обработка смены типа формирования geo-зависимых URL объявлений
     * @param string $prevType предыдущий тип формирования (Geo::URL_)
     * @param string $nextType следующий тип формирования (Geo::URL_)
     */
    public function itemsGeoUrlTypeChanged($prevType, $nextType)
    {
        if ($prevType == $nextType) {
            return;
        }

        $aData = $this->db->select('SELECT
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_BBS_ITEMS . ' I
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON I.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON I.reg3_city = RC.id
            WHERE I.reg3_city > 0 AND I.reg2_region > 0
            GROUP BY I.reg3_city
            ORDER BY I.reg3_city
        '
        );

        $coveringType = Geo::coveringType();

        if ($prevType == Geo::URL_SUBDOMAIN) {
            foreach ($aData as &$v) {
                switch ($nextType) {
                    case Geo::URL_SUBDIR:
                    {
                        $to = '//{sitehost}/' . $v['city'] . '/';
                    }
                    break;
                    case Geo::URL_NONE:
                    {
                        if ($coveringType == Geo::COVERING_CITY) {
                            continue 2;
                        }
                        $to = '//{sitehost}/';
                    }
                    break;
                }
                switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                    {
                        $from = '//' . $v['city'] . '.{sitehost}/';
                    }
                    break;
                    case Geo::COVERING_CITY:
                    {
                        $from = '//{sitehost}/';
                    }
                    break;
                }
                $this->db->update(TABLE_BBS_ITEMS,
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
                            {
                                $to = '//' . $v['city'] . '.{sitehost}/';
                            }
                            break;
                            case Geo::COVERING_CITY:
                            {
                                $to = '//{sitehost}/';
                            }
                            break;
                            }
                        }
                        break;
                        case Geo::URL_NONE:
                        {
                            if ($coveringType == Geo::COVERING_CITY) {
                                continue 2;
                            }
                            $to = '//{sitehost}/';
                        }
                        break;
                    }
                    switch ($coveringType) {
                    case Geo::COVERING_COUNTRIES:
                    case Geo::COVERING_COUNTRY:
                    case Geo::COVERING_REGION:
                    case Geo::COVERING_CITIES:
                    {
                        $from = '//{sitehost}/' . $v['city'] . '/';
                    }
                    break;
                    case Geo::COVERING_CITY:
                    {
                        $from = '//{sitehost}/';
                    }
                    break;
                    }
                    $this->db->update(TABLE_BBS_ITEMS,
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
                            {
                                $to = '//' . $v['city'] . '.{sitehost}/';
                            }
                            break;
                            case Geo::URL_SUBDIR:
                            {
                                $to = '//{sitehost}/' . $v['city'] . '/';
                            }
                            break;
                        }
                        $this->db->update(TABLE_BBS_ITEMS,
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
     * Перестраиваем URL всех объявлений
     */
    public function itemsLinksRebuild()
    {
        $this->db->select_iterator('SELECT I.id, I.keyword, C.keyword as cat_keyword,
                RR.keyword as region, RR.id as region_id,
                RC.keyword as city, RC.id as city_id
            FROM ' . TABLE_BBS_ITEMS . ' I
                 INNER JOIN ' . TABLE_BBS_CATEGORIES . ' C ON C.id = I.cat_id
                 INNER JOIN ' . TABLE_REGIONS . ' RR ON I.reg2_region = RR.id
                 INNER JOIN ' . TABLE_REGIONS . ' RC ON I.reg3_city = RC.id
            ORDER BY I.id
        ', array(), function($v) {
            $link = BBS::url('items.search', array(
                    'keyword' => $v['cat_keyword'],
                    'region'  => $v['region'],
                    'city'    => $v['city'],
                ), true
            );
            $link .= $v['keyword'] . '-' . $v['id'] . '.html';

            $this->db->update(TABLE_BBS_ITEMS, array('link' => $link), array('id' => $v['id']));
        });
    }

    /**
     * Количество объявлений по фильтру
     * @param array $aFilter
     * @return integer
     */
    public function itemsCount($aFilter)
    {
        $aFilter = $this->prepareFilter($aFilter);
        return (int)$this->db->one_data('SELECT COUNT(*) FROM '.TABLE_BBS_ITEMS.$aFilter['where'], $aFilter['bind']);
    }

    # ----------------------------------------------------------------
    # Импорт объявлений

    /**
     * Получение списка импорта (admin)
     * @param array $aFields выбираемые поля
     * @param array $aFilter фильтр списка
     * @param mixed $nLimit ограничение выборки, false - без ограничения
     * @param string $sqlOrder
     * @param bool $bCount только подсчёт кол-ва
     * @return mixed
     */
    public function importListing(array $aFields = array(), array $aFilter = array(), $nLimit = false, $sqlOrder = '', $bCount = false) //adm
    {
        if (empty($aFields)) {
            $aFields[] = 'I.*';
            $aFields[] = 'C.title as cat_title';
        }
        
        $aFilter = $this->prepareFilter($aFilter,'I');

        if ($bCount) {
            return (int)$this->db->one_data('SELECT COUNT(I.id)'
                        . 'FROM ' . TABLE_BBS_ITEMS_IMPORT . ' I ' . $aFilter['where']
                        , $aFilter['bind']);
        }

        if ($nLimit) {
            if (is_integer($nLimit)) $nLimit = $this->db->prepareLimit(0, $nLimit);
        } else $nLimit = '';

        return $this->db->select('SELECT ' . join(',', $aFields) . '
                            FROM ' . TABLE_BBS_ITEMS_IMPORT . ' I
                                LEFT JOIN ' . TABLE_BBS_CATEGORIES . ' C ON I.cat_id = C.id '
                            . $aFilter['where']
                            . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '')
                            . $nLimit, $aFilter['bind']);
    }

    /**
     * Сохранение данных об импорте объявлений
     * @param integer $nImportID ID импорта или 0
     * @param array $aData
     */
    public function importSave($nImportID, array $aData)
    {
        if ($nImportID) {
            return $this->db->update(TABLE_BBS_ITEMS_IMPORT, $aData, array('id' => $nImportID));
        } else {
            return $this->db->insert(TABLE_BBS_ITEMS_IMPORT, $aData, 'id');
        }
    }
    
    /**
     * Обновление данных об импорте объявлений по фильтру
     * @param array $aFilter фильтр
     * @param array $aData данные
     */
    public function importUpdateByFilter(array $aFilter = array(), array $aData = array())
    {
        if(empty($aFilter)) return;
        if(empty($aData)) return;
        $aFilter = $this->prepareFilter($aFilter);
        $aFilter['where'] = substr($aFilter['where'], 6);
        
        return $this->db->update(TABLE_BBS_ITEMS_IMPORT, $aData, $aFilter['where'], $aFilter['bind']);
    }

    /**
     * Получение данных об импорте объявлений по ID
     * @param integer $nImportID ID импорта или 0
     * @return mixed
     */
    public function importData($nImportID)
    {
        if (empty($nImportID)) return false;
        
        return $this->db->one_array('SELECT *
            FROM '.TABLE_BBS_ITEMS_IMPORT.'
            WHERE id = :id', array(':id'=>$nImportID));
    }

    # ----------------------------------------------------------------
    # Категории объявлений

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
        case 'index':
        {
            if ($device == bff::DEVICE_DESKTOP) {
                $filter[] = 'C.numlevel < 3';
            } else {
                if ($device == bff::DEVICE_PHONE) {
                    if ($parentID > 0) {
                        $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                    } else {
                        $filter[] = 'C.numlevel = 1';
                    }
                }
            }
        }
            break;
        case 'form':
        case 'search':
        {
            if ($device == bff::DEVICE_DESKTOP) {
                if ($parentID > 0) {
                    $filter[':pid'] = array('C.pid = :pid', ':pid' => $parentID);
                } else {
                    $filter[] = 'C.numlevel = 1';
                }
            } else if ($device == bff::DEVICE_PHONE) {
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
                                         C.items, (C.numright-C.numleft)>1 as subs, C.numlevel as lvl
                            FROM ' . TABLE_BBS_CATEGORIES . ' C,
                                 ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                            ' . $filter['where'] . '
                            ORDER BY C.numleft ASC', $filter['bind']
        );
    }

    public function catsListSitemap($iconVariant)
    {
        return $this->db->select('SELECT C.id, C.pid, C.icon_' . $iconVariant . ' as icon, CL.title, C.keyword
                            FROM ' . TABLE_BBS_CATEGORIES . ' C,
                                 ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                            WHERE C.enabled = 1 AND C.pid != 0 AND C.numlevel <= 2
                              AND ' . $this->db->langAnd(false, 'C', 'CL') . '
                            ORDER BY C.numleft ASC'
        );
    }

    public function catsListing(array $aFilter) //adm
    {
        $aFilter = $this->prepareFilter($aFilter);

        return $this->db->select('SELECT C.id, C.pid, C.enabled, C.addr, C.price, C.numlevel,
                                IF(C.numright-C.numleft>1,1,0) as node, C.title, C.items, C.numleft
                            FROM ' . TABLE_BBS_CATEGORIES . ' C
                            ' . $aFilter['where'] . '
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
        if(isset($aFilter['lang'])) {
            $lang = $aFilter['lang'];
            unset($aFilter['lang']);
        } else $lang = LNG;
        
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
        
        $aFilter[':lng'] = $this->db->langAnd(false, 'C', 'CL', $lang);
        $aFilter = $this->prepareFilter($aFilter, 'C');
        $aFilter['where'] = str_replace('C.lang = :lang','CL.lang = :lang AND C.id = CL.id',$aFilter['where']);
        
        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_CATEGORIES . ' C,
                            ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                       ' . $aFilter['where'] . '
                       LIMIT 1', $aFilter['bind']
        );

        if (isset($aData['price_sett'])) {
            $priceSett =& $aData['price_sett'];
            $priceSett = (!empty($priceSett) ? func::unserialize($priceSett) : array());
            if ($priceSett === false) $priceSett = array();
            if (!isset($priceSett['ranges'])) $priceSett['ranges'] = array();
            if (!isset($priceSett['ex'])) $priceSett['ex'] = BBS::PRICE_EX_PRICE;
        }

        if ($bEdit) {
            $aData['node'] = ($aData['numright'] - $aData['numleft']);
            if (!Request::isPOST()) {
                $this->db->langSelect($aData['id'], $aData, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
            }
        }

        return $aData;
    }
    
    /**
     * Получение списка категорий по фильтру
     * @param array $aFilter список фильтров
     * @param array $aFields список полей которые нужно получить
     * @return array|boolean
     */
    public function catsDataByFilter(array $aFilter, $aFields = array())
    {
        $aParams = array();
        if (empty($aFields)) $aFields = '*';
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
        
        $aData = $this->db->select('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_CATEGORIES . ' C,
                            ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                       ' . $aFilter['where'] . ' ORDER BY C.numleft', $aFilter['bind']
        );
        if ($aData)
        {
            foreach($aData as &$v)
            {
                if (isset($v['price_sett'])) {
                    $priceSett =& $v['price_sett'];
                    $priceSett = (!empty($priceSett) ? unserialize($priceSett) : array());
                    if ($priceSett === false) $priceSett = array();
                    if (!isset($priceSett['ranges'])) $priceSett['ranges'] = array();
                    if (!isset($priceSett['ex'])) $priceSett['ex'] = BBS::PRICE_EX_PRICE;
                }
            } unset($v);
        } else {
            $aData = array();
        }

        return $aData;
    }
    
    /**
     * Получаем данные о child-категориях всех уровней вложенности
     * @param int $numleft левая граница
     * @param int $numright правая граница
     * @param string $langKey язык записей
     * @param array $aFields требуемые поля child-категорий
     * @return array|mixed
     */
    public function catChildsTree($numleft, $numright, $langKey = LNG, $aFields = array())
    {
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
        }
        
        return $this->db->select_key('SELECT ' . join(',', $aFields) . '
                            FROM ' . TABLE_BBS_CATEGORIES . ' C
                                LEFT JOIN ' . TABLE_BBS_CATEGORIES_LANG . ' CL USING (id)
                            WHERE C.numleft > :left AND C.numright < :right AND CL.lang = :lang
                            ORDER BY C.numleft ASC', 'id',
                            array(':left'=>$numleft,':right'=>$numright,':lang'=>$langKey)
        );
    }

    /**
     * Копирование настроек категории в подкатегории
     * @param integer $nCategoryID ID категории
     * @return boolean
     */
    public function catDataCopyToSubs($nCategoryID)
    {
        # шаг 1:
        $aData = $this->db->one_array('SELECT numleft, numright, numlevel,
                seek, price, price_sett, photos, owner_business, owner_search, addr, addr_metro
                FROM ' . TABLE_BBS_CATEGORIES . '
                WHERE id = :id', array(':id' => $nCategoryID)
        );
        if (empty($aData)) {
            return false;
        }
        # нет подкатегорий
        if (($aData['numright'] - $aData['numleft']) == 1) {
            return true;
        }
        # получаем ID подкатегорий
        $aSubsID = $this->db->select_one_column('SELECT id FROM ' . TABLE_BBS_CATEGORIES . '
            WHERE numleft > :left AND numright < :right AND numlevel = :lvl', array(
                ':left'  => $aData['numleft'],
                ':right' => $aData['numright'],
                ':lvl'   => $aData['numlevel'] + 1,
            )
        );
        unset($aData['numleft'], $aData['numright'], $aData['numlevel']);
        $this->db->update(TABLE_BBS_CATEGORIES, $aData, array('id' => $aSubsID));

        # шаг2:
        $aLangs = $this->locale->getLanguages();
        $aLangFields = array(
            'type_offer_form',
            'type_offer_search',
            'type_seek_form',
            'type_seek_search',
            'owner_private_form',
            'owner_private_search',
            'owner_business_form',
            'owner_business_search',
        );
        foreach ($aLangs as $lang) {
            $aData2 = $this->db->one_array('SELECT ' . join(', ', $aLangFields) . '
                FROM ' . TABLE_BBS_CATEGORIES_LANG . '
                WHERE id = :id AND lang = :lang',
                array(':id' => $nCategoryID, ':lang' => $lang)
            );

            $this->db->update(TABLE_BBS_CATEGORIES_LANG, $aData2,
                array('id' => $aSubsID, 'lang' => $lang)
            );
        }
    }

    public function catSave($nCategoryID, $aData)
    {
        if ($nCategoryID) {
            # запрет именения parent'a
            if (isset($aData['pid'])) unset($aData['pid']);
            $aData['modified'] = $this->db->now();
            if (isset($aData['price_sett'])) $aData['price_sett'] = serialize($aData['price_sett']);
            $this->db->langUpdate($nCategoryID, $aData, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
            $aDataNonLang = array_diff_key($aData, $this->langCategories);
            if (isset($aData['title'][LNG])) $aDataNonLang['title'] = $aData['title'][LNG];

            return $this->db->update(TABLE_BBS_CATEGORIES, $aDataNonLang, array('id' => $nCategoryID));
        } else {
            $nCategoryID = $this->treeCategories->insertNode($aData['pid']);
            if (!$nCategoryID) return 0;
            unset($aData['pid']);
            $aData['created'] = $this->db->now();
            $this->catSave($nCategoryID, $aData);

            return $nCategoryID;
        }
    }

    /**
     * Смена parent-категории
     * @param integer $nCategoryID ID перемещаемой категории
     * @param integer $nNewParentID ID новой parent-категории
     * @return boolean
     */
    public function catChangeParent($nCategoryID, $nNewParentID)
    {
        return $this->treeCategories->changeParent($nCategoryID, $nNewParentID);
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

        # проверяем наличие связанных с категорией ОБ
        $nItems = $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I WHERE I.cat_id = :id', array(':id' => $nCategoryID));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить категорию с объявлениями');

            return false;
        }

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($nCategoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');

            return false;
        }

        # удаляем иконки
        $oIcon = BBS::categoryIcon($nCategoryID);
        foreach ($oIcon->getVariants() as $k => $v) {
            $oIcon->setVariant($k);
            $oIcon->delete(false, $aData[$k]);
        }

        return true;
    }

    public function catDeleteDev($categoryID)
    {
        # проверяем наличие связанных с категорией ОБ
        $cats = ' I.cat_id = :id ';
        for ($i = 1; $i <= BBS::CATS_MAXDEEP; $i++) {
            $cats .= ' OR I.cat_id'.$i.' = :id ';
        }
        $nItems = $this->db->one_data('SELECT COUNT(I.id) FROM ' . TABLE_BBS_ITEMS . ' I WHERE ('.$cats.')', array(':id' => $categoryID));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить категорию с объявлениями');
            return false;
        }

        $data = $this->catData($categoryID);
        # удаляем иконку категории
        $oIcon = BBS::categoryIcon($categoryID);
        foreach ($oIcon->getVariants() as $k => $v) {
            $oIcon->setVariant($k);
            $oIcon->delete(false, $data[$k]);
        }
        $fields = array_keys($oIcon->getVariants());
        $fields[] = 'id';

        # удаляем иконки вложенных категорий
        $children = $this->catChildsTree($data['numleft'], $data['numright'], LNG, $fields);
        if ( ! empty($children)) {
            foreach ($children as $v) {
                $oIcon->setRecordID($v['id']);
                foreach ($oIcon->getVariants() as $k => $vv) {
                    $oIcon->setVariant($k);
                    $oIcon->delete(false, $v[$k]);
                }
            }
        }

        # удаляем
        $aDeleteID = $this->treeCategories->deleteNode($categoryID);
        if (empty($aDeleteID)) {
            $this->errors->set('Ошибка удаления категории');
            return false;
        } else {
            //
        }

        return true;
    }

    public function catDeleteAll()
    {
        # чистим таблицу категорий (+ зависимости по внешним ключам)
        $this->db->exec('DELETE FROM ' . TABLE_BBS_CATEGORIES . ' WHERE id > 0');
        $this->db->exec('ALTER TABLE ' . TABLE_BBS_CATEGORIES . ' AUTO_INCREMENT = 2');

        # создаем корневую директорию
        $nRootID = BBS::CATS_ROOTID;
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
        $res = $this->db->insert(TABLE_BBS_CATEGORIES, $aData);
        if (!empty($res)) {
            $aDataLang = array('title' => array());
            foreach ($this->locale->getLanguages() as $lng) {
                $aDataLang['title'][$lng] = $sRootTitle;
            }
            $this->db->langInsert($nRootID, $aDataLang, $this->langCategories, TABLE_BBS_CATEGORIES_LANG);
        }

        return !empty($res);
    }

    public function catToggle($nCategoryID, $sField)
    {
        if (!$nCategoryID) return false;

        switch ($sField) {
        case 'addr_map':
        {
            return $this->toggleInt(TABLE_BBS_CATEGORIES, $nCategoryID, 'addr', 'id');
        }
            break;
        case 'enabled':
        {
            $res = $this->toggleInt(TABLE_BBS_CATEGORIES, $nCategoryID, 'enabled', 'id');
            if ($res) {
                $aCategoryData = $this->catData($nCategoryID, array('numleft', 'numright', 'enabled'));
                if (!empty($aCategoryData)) {
                    $this->db->update(TABLE_BBS_CATEGORIES, array(
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

    public function catsExport($type, $lang = LNG)
    {
        $aData = array();
        if (empty($type) || $type == 'txt') {
            $aData = $this->db->select('SELECT C.id, C.numlevel, ((C.numright-C.numleft)>1) as subs, CL.title
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE C.id != :rootID AND C.id = CL.id AND CL.lang = :lang
                ORDER BY C.numleft
            ', array(':rootID' => BBS::CATS_ROOTID, ':lang' => $lang)
            );
        }
        if (empty($aData)) {
            $aData = array();
        }

        return $aData;
    }

    /**
     * Получаем данные о parent-категориях
     * @param int|array $mCategoryData ID категории или данные о ней [id,numleft,numright]
     * @param array $aFields требуемые поля parent-категорий
     * @param bool $bIncludingSelf включая категорию $mCategoryData
     * @param bool $bExludeRoot исключая данные о корневом элементе
     * @return array|mixed
     */
    public function catParentsData($mCategoryData, array $aFields = array(
        'id',
        'title',
        'keyword'
    ), $bIncludingSelf = true, $bExludeRoot = true
    ) {
        if (empty($aFields)) $aFields[] = 'id';
        foreach ($aFields as $k => $v) {
            if ($v == 'id' || array_key_exists($v, $this->langCategories)) $aFields[$k] = 'CL.' . $v;
            if ($v == 'subs') { $aFields[$k] = '((C.numright-C.numleft)>1) as subs'; }
        }

        if (is_array($mCategoryData))
        {
            if (empty($mCategoryData)) return array();
            foreach (array('id','numleft','numright') as $k) {
                if (!isset($mCategoryData[$k])) return array();
            }
            $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE ((C.numleft <= ' . $mCategoryData['numleft'] . ' AND C.numright > ' . $mCategoryData['numright'] . ')' . ($bIncludingSelf ? ' OR C.id = ' . $mCategoryData['id'] : '') . ')
                    '.( $bExludeRoot ? ' AND C.id != ' . BBS::CATS_ROOTID : '' ).'
                ' . $this->db->langAnd(true, 'C', 'CL') . '
                ORDER BY C.numleft
            '
            );
        } else {
            if ($mCategoryData <= 0) return array();
            $aParentsID = $this->treeCategories->getNodeParentsID($mCategoryData, ($bExludeRoot ? ' AND id != ' . BBS::CATS_ROOTID : ''), $bIncludingSelf);
            if (empty($aParentsID)) return array();

            $aParentsData = $this->db->select('SELECT ' . join(',', $aFields) . '
                FROM ' . TABLE_BBS_CATEGORIES . ' C,
                     ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                WHERE C.id IN(' . join(',', $aParentsID) . ')
                ' . $this->db->langAnd(true, 'C', 'CL') . '
                ORDER BY C.numleft
            '
            );
        }

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
            $aData = $this->db->select('SELECT id, numlevel FROM ' . TABLE_BBS_CATEGORIES . '
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
                    FROM ' . TABLE_BBS_CATEGORIES . ' C,
                         ' . TABLE_BBS_CATEGORIES_LANG . ' CL
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
        if ($aCatData['pid'] == BBS::CATS_ROOTID) {
            $sFrom = $sKeywordPrev . '/';
        } else {
            $aParentCatData = $this->catData($aCatData['pid'], array('keyword'));
            if (empty($aParentCatData)) return false;
            $sFrom = $aParentCatData['keyword'] . '/' . $sKeywordPrev . '/';
        }

        # перестраиваем полный путь подкатегорий
        $nCatsUpdated = $this->db->update(TABLE_BBS_CATEGORIES,
            array('keyword = REPLACE(keyword, :from, :to)'),
            'numleft > :left AND numright < :right',
            array(
                ':from'  => $sFrom,
                ':to'    => $aCatData['keyword'] . '/',
                ':left'  => $aCatData['numleft'],
                ':right' => $aCatData['numright']
            )
        );
        if (!empty($nCatsUpdated)) {
            # перестраиваем ссылки в объявлениях
            $sPrefix = '/search/';
            $this->db->update(TABLE_BBS_ITEMS,
                array('link = REPLACE(link, :from, :to)'),
                'cat_id' . $aCatData['numlevel'] . ' = :cat',
                array(
                    ':from' => $sPrefix . $sFrom,
                    ':to'   => $sPrefix . $aCatData['keyword'] . '/',
                    ':cat'  => $nCategoryID,
                )
            );

            return true;
        }

        return false;
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
            return ($nParentID == BBS::CATS_ROOTID);
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
        $bCountItems = false;
        switch ($sType) {
            case 'adm-items-listing':
            {
                $sqlWhere[] = '(C.numlevel = 1' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
            }
            break;
            case 'adm-shops-listing':
            {
                $sqlWhere[] = '(C.numlevel = 1 ' . ($nSelectedID > 0 ? ' OR C.id = ' . $nSelectedID : '') . ')';
            }
            break;
            case 'adm-category-form-add':
            {
                $sqlWhere[] = 'C.numlevel < ' . BBS::CATS_MAXDEEP;
                $bCountItems = true;
            }
            break;
            case 'adm-category-form-edit':
            {
                $sqlWhere[] = '( ! (C.numleft > ' . $aExtra['numleft'] . ' AND C.numright < ' . $aExtra['numright'] . ')
                                    AND C.id != ' . $aExtra['id'] . ')';
                $bCountItems = true;
            }
            break;
            case 'adm-svc-prices-ex':
            {
                $sqlWhere[] = 'C.numlevel IN(1,2)';
            }
            break;
        }

        $aData = $this->db->select('SELECT C.id, C.pid, CL.title, C.numlevel, C.numleft, C.numright, 0 as disabled
                        ' . ($bCountItems ? ', COUNT(I.id) as items ' : '') . '
                   FROM ' . TABLE_BBS_CATEGORIES_LANG . ' CL,
                        ' . TABLE_BBS_CATEGORIES . ' C
                        ' . ($bCountItems ? ' LEFT JOIN ' . TABLE_BBS_ITEMS . ' I ON C.id = I.cat_id ' : '') . '
                   WHERE ' . join(' AND ', $sqlWhere) . '
                   GROUP BY C.id
                   ORDER BY C.numleft'
        );
        if (empty($aData)) $aData = array();

        if ($sType == 'adm-category-form-add') {
            foreach ($aData as &$v) {
                $v['disabled'] = ($v['numlevel'] > 0 && $v['items'] > 0);
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

    /**
     * Формирование списков категорий (при добавлении/редактировании объявления в админ панели, при поиске в категории)
     * @param integer $nCategoryID ID категории [lvl=>selectedCatID, ...]
     * @param mixed $mOptions формировать select-options или возвращать массивы данных о категориях
     * @param boolean $bPrepareURLKeywords подготовить keyword'ы для построения ссылок
     * @return array [lvl=>[a=>id выбранной,cats=>список категорий(массив или options)],...]
     */
    public function catsOptionsByLevel($aCategoriesID, $mOptions = false, $bPrepareURLKeywords = false)
    {
        $aData = array();
        if (empty($aCategoriesID)) $aCategoriesID = array(1 => 0);

        # формируем список уровней для которых необходимо получить категории
        $aLevels = array();
        $bFill = true;
        $parentID = BBS::CATS_ROOTID;
        foreach ($aCategoriesID as $lvl => $catID) {
            if ($catID || $bFill) {
                $aLevels[$lvl] = $parentID;
                if (!$catID) break;
                $parentID = $catID;
            } else {
                break;
            }
        }

        if (empty($aLevels)) return $aData;

        $sQuery = 'SELECT C.id, CL.title as t, CL.breadcrumb as cr, C.keyword as k, C.numlevel as lvl
                    FROM ' . TABLE_BBS_CATEGORIES . ' C,
                         ' . TABLE_BBS_CATEGORIES_LANG . ' CL
                    WHERE C.numlevel IN (' . join(',', array_keys($aLevels)) . ')
                      AND C.pid IN(' . join(',', $aLevels) . ')
                      ' . $this->db->langAnd(true, 'C', 'CL') . '
                    ORDER BY C.numleft';
        $aData = $this->db->select($sQuery);
        if (empty($aData)) return array();

        $aLevels = array();
        foreach ($aData as $v) {
            $aLevels[$v['lvl']][$v['id']] = $v;
        }
        unset($aData);

        if (!empty($mOptions)) {
            foreach ($aCategoriesID as $lvl => $nSelectedID) {
                if (isset($aLevels[$lvl])) {
                    $aCategoriesID[$lvl] = array(
                        'a'    => $nSelectedID,
                        'cats' => HTML::selectOptions($aLevels[$lvl], $nSelectedID, $mOptions['empty'], 'id', 't'),
                    );
                } else {
                    $aCategoriesID[$lvl] = array(
                        'a'    => $nSelectedID,
                        'cats' => false,
                    );
                }
            }
        } elseif ($bPrepareURLKeywords) {
            foreach ($aCategoriesID as $lvl => $nSelectedID) {
                $aCategoriesID[$lvl] = array(
                    'a'    => $nSelectedID,
                    'cats' => (isset($aLevels[$lvl]) ? $aLevels[$lvl] : false),
                );
            }
        }

        return $aCategoriesID;
    }

    /**
     * Формируем запрос по фильтру "цена"
     * @param array $aPriceFilter настройки фильтра "цена": 'r'=>диапазоны, 'f'=>от, 't'=>до
     * @param array $aCategoryData @ref данные категории
     * @param string $sTablePrefix префикс таблицы, формат: 'T.'
     * @return string
     */
    public function preparePriceQuery($aPriceFilter, array &$aCategoryData, $sTablePrefix)
    {
        if (empty($aPriceFilter) || empty($aCategoryData['price_sett'])) return '';

        $nPriceCurrID = Site::currencyData($aCategoryData['price_sett']['curr'], 'id');
        $sPriceField = $sTablePrefix . 'price_search';

        $sql = array();
        # от - до
        if (!empty($aPriceFilter['f']) || !empty($aPriceFilter['t'])) {
            $nPriceFromTo = (!empty($aPriceFilter['c']) ? $aPriceFilter['c'] : $nPriceCurrID);
            $from = Site::currencyPriceConvertToDefault($aPriceFilter['f'], $nPriceFromTo);
            $to = Site::currencyPriceConvertToDefault($aPriceFilter['t'], $nPriceFromTo);
            if ($from > 0 && $to > 0 && $from >= $to) $from = 0;
            $sql[] = '(' . ($from > 0 ? "$sPriceField >= " . $from . ($to > 0 ? " AND $sPriceField <= " . $to : '') : "$sPriceField <= " . $to) . ')';
        }
        # диапазоны
        if (!empty($aCategoryData['price_sett']['ranges']) && !empty($aPriceFilter['r'])) {
            foreach ($aPriceFilter['r'] as $v) {
                if (isset($aCategoryData['price_sett']['ranges'][$v])) {
                    $v = $aCategoryData['price_sett']['ranges'][$v];
                    $v['from'] = Site::currencyPriceConvertToDefault($v['from'], $nPriceCurrID);
                    $v['to'] = Site::currencyPriceConvertToDefault($v['to'], $nPriceCurrID);
                    $sql[] = '(' . ($v['from'] ? "$sPriceField >= " . $v['from'] . ($v['to'] ? " AND $sPriceField <= " . $v['to'] : '') : "$sPriceField <= " . $v['to']) . ')';
                }
            }
        }

        return (!empty($sql) ? '(' . join(' OR ', $sql) . ')' : '');
    }

    # ----------------------------------------------------------------
    # Типы категорий объявлений

    public function cattypesListing($aFilter)
    {
        if (empty($aFilter)) {
            $aFilter = array();
        }

        $aFilter[] = 'T.cat_id = C.id';
        $aFilter = $this->prepareFilter($aFilter);

        return $this->db->select('SELECT T.*, T.title_' . LNG . ' as title, C.title as cat_title
                    FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' T,
                         ' . TABLE_BBS_CATEGORIES . ' C
                    ' . $aFilter['where'] . '
                    ORDER BY C.numleft, T.num ASC', $aFilter['bind']
        );
    }

    public function cattypeData($nTypeID, $aFields = array(), $bEdit = false)
    {
        if (empty($aFields)) $aFields = '*';

        $aParams = array();
        if (!is_array($aFields)) $aFields = array($aFields);
        foreach ($aFields as $v) {
            $aParams[] = $v;
        }

        $aData = $this->db->one_array('SELECT ' . join(',', $aParams) . '
                       FROM ' . TABLE_BBS_CATEGORIES_TYPES . '
                       WHERE id = :id
                       LIMIT 1', array(':id' => $nTypeID)
        );

        if ($bEdit) {
            $this->db->langFieldsSelect($aData, $this->langCategoriesTypes);
        }

        return $aData;
    }

    public function cattypeSave($nTypeID, $nCategoryID, $aData)
    {
        if ($nTypeID) {
            $aData['modified'] = $this->db->now();
            $this->db->langFieldsModify($aData, $this->langCategoriesTypes, $aData);

            return $this->db->update(TABLE_BBS_CATEGORIES_TYPES, $aData, array('id'=>$nTypeID));
        } else {
            $nNum = (integer)$this->db->one_data('SELECT MAX(num) FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' WHERE cat_id = ' . $nCategoryID);
            $aData['num'] = $nNum + 1;
            $aData['cat_id'] = $nCategoryID;
            $aData['created'] = $aData['modified'] = $this->db->now();
            $this->db->langFieldsModify($aData, $this->langCategoriesTypes, $aData);

            return $this->db->insert(TABLE_BBS_CATEGORIES_TYPES, $aData, 'id');
        }
    }

    public function cattypeDelete($nTypeID)
    {
        if (!$nTypeID || !BBS::CATS_TYPES_EX) return false;

        # удаляем только "свободный" тип
        $nItems = $this->db->one_data('SELECT COUNT(id) FROM ' . TABLE_BBS_ITEMS . ' WHERE cat_type = :id', array(':id' => $nTypeID));
        if (!empty($nItems)) {
            $this->errors->set('Невозможно удалить тип категории с объявлениями');

            return false;
        }

        $res = $this->db->delete(TABLE_BBS_CATEGORIES_TYPES, array('id' => $nTypeID));

        return !empty($res);
    }

    public function cattypeToggle($nTypeID, $sField)
    {
        if (!$nTypeID) return false;

        switch ($sField) {
            case 'enabled':
            {
                return $this->toggleInt(TABLE_BBS_CATEGORIES_TYPES, $nTypeID, 'enabled', 'id');
            }
            break;
        }

        return false;
    }

    public function cattypesRotate($nCategoryID)
    {
        return $this->db->rotateTablednd(TABLE_BBS_CATEGORIES_TYPES, ' AND cat_id = ' . $nCategoryID);
    }

    /**
     * Формирование списка типов, привязанных к категории
     * @param integer $nCategoryID ID категории
     * @param mixed $mOptions формировать select-options или FALSE
     * @return array|string
     */
    public function cattypesByCategory($nCategoryID, $mOptions = false)
    {
        $aData = array();
        do {
            if (empty($nCategoryID)) break;

            $aCategoryParentsID = $this->catParentsID($nCategoryID);
            if (empty($aCategoryParentsID)) break;

            $aData = $this->db->select_key('SELECT T.id, T.title_' . LNG . ' as title, T.items
                FROM ' . TABLE_BBS_CATEGORIES_TYPES . ' T, ' . TABLE_BBS_CATEGORIES . ' C
                WHERE T.cat_id IN (' . join(',', $aCategoryParentsID) . ') AND T.cat_id = C.id
                ORDER BY C.numleft, T.num ASC', 'id'
            );
        } while (false);

        if (!empty($mOptions)) {
            return HTML::selectOptions($aData, $mOptions['sel'], $mOptions['empty'], 'id', 'title');
        } else {
            return $aData;
        }
    }

    /**
     * Формирование списка простых типов (BBS::TYPE_)
     * @param array $aCategoryData данные о категории
     * @param bool $bSearch для поиска
     * @return array|string
     */
    public function cattypesSimple(array $aCategoryData, $bSearch)
    {
        if (empty($aCategoryData) || !isset($aCategoryData['seek'])) return array();
        $aTypes = array(
            BBS::TYPE_OFFER => array('id' => BBS::TYPE_OFFER, 'title' => ''),
            BBS::TYPE_SEEK  => array('id' => BBS::TYPE_SEEK, 'title' => ''),
        );
        if ($bSearch) {
            $aTypes[BBS::TYPE_OFFER]['title'] = (!empty($aCategoryData['type_offer_search']) ? $aCategoryData['type_offer_search'] : _t('bbs', 'Объявления'));
            $aTypes[BBS::TYPE_SEEK]['title'] = (!empty($aCategoryData['type_seek_search']) ? $aCategoryData['type_seek_search'] : _t('bbs', 'Объявления'));
        } else {
            $aTypes[BBS::TYPE_OFFER]['title'] = (!empty($aCategoryData['type_offer_form']) ? $aCategoryData['type_offer_form'] : _t('bbs', 'Предлагаю'));
            $aTypes[BBS::TYPE_SEEK]['title'] = (!empty($aCategoryData['type_seek_form']) ? $aCategoryData['type_seek_form'] : _t('bbs', 'Ищу'));
        }
        if (!$aCategoryData['seek']) unset($aTypes[BBS::TYPE_SEEK]);

        return $aTypes;
    }

    # ----------------------------------------------------------------
    # Жалобы

    public function claimsListing($aFilter, $bCount = false, $sqlLimit = '')
    {

        if ($bCount) {
            $aFilter = $this->prepareFilter($aFilter, 'CL');
            return (int)$this->db->one_data('SELECT COUNT(CL.id)
                                FROM ' . TABLE_BBS_ITEMS_CLAIMS . ' CL
                                ' . $aFilter['where'], $aFilter['bind']
            );
        }

        $aFilter[':jitem'] = 'CL.item_id = I.id ';
        $aFilter = $this->prepareFilter($aFilter, 'CL');
        return $this->db->select('SELECT CL.*, U.name, U.login, U.blocked as ublocked, U.deleted as udeleted, I.link
                                FROM ' . TABLE_BBS_ITEMS_CLAIMS . ' CL
                                    LEFT JOIN ' . TABLE_USERS . ' U ON CL.user_id = U.user_id,
                                    '.TABLE_BBS_ITEMS.' I
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
                       FROM ' . TABLE_BBS_ITEMS_CLAIMS . '
                       WHERE id = :cid
                       LIMIT 1', array(':cid' => $nClaimID)
        );
    }

    public function claimSave($nClaimID, $aData)
    {
        if ($nClaimID) {
            return $this->db->update(TABLE_BBS_ITEMS_CLAIMS, $aData, array('id' => $nClaimID));
        } else {
            $aData['created'] = $this->db->now();
            $aData['user_id'] = User::id();
            $aData['user_ip'] = Request::remoteAddress();

            return $this->db->insert(TABLE_BBS_ITEMS_CLAIMS, $aData, 'id');
        }
    }

    public function claimDelete($nClaimID)
    {
        if (!$nClaimID) return false;

        return $this->db->delete(TABLE_BBS_ITEMS_CLAIMS, array('id' => $nClaimID));
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

            if (isset($aData['press']) && !BBS::PRESS_ON) {
                unset($aData['press']);
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
                // оставляем текущую локализацию
                foreach ($this->langSvcPacks as $lngK => $lngV) {
                    $sett[$lngK] = (isset($sett[$lngK][LNG]) ? $sett[$lngK][LNG] : '');
                }
                $aData[$k] = array_merge($v, $sett);
            }

            return $aData;
        }
    }

    /**
     * Данные об услугах для формы добавления объявления (BBS), страницы продвижения, страницы списка услуг
     * @return array
     */
    public function svcData()
    {
        $aFilter = array('module' => 'bbs');
        $aFilter = $this->prepareFilter($aFilter, 'S');

        $aData = $this->db->select_key('SELECT S.*
                                    FROM ' . TABLE_SVC . ' S
                                    ' . $aFilter['where']
            . ' ORDER BY S.type, S.num',
            'id', $aFilter['bind']
        );
        if (empty($aData)) return array();

        $oIcon = BBS::svcIcon();
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
            $v['icon_b'] = $oIcon->url($v['id'], $v['icon_b'], BBSSvcIcon::BIG);
            $v['icon_s'] = $oIcon->url($v['id'], $v['icon_s'], BBSSvcIcon::SMALL);
            # исключаем выключенные услуги
            if (empty($v['on']) ||
                ($v['id'] == BBS::SERVICE_PRESS && !BBS::PRESS_ON)
            ) {
                unset($aData[$k]);
            }
        }
        unset($v);

        return $aData;
    }

    /**
     * Получение региональной стоимости услуги в зависимости от категории/города
     * @param array $svcID ID услуг
     * @param integer $categoryID ID категории любого уровня
     * @param integer $cityID ID города
     * @return array - региональной стоимость услуг для указанной категории/региона
     */
    public function svcPricesEx(array $svcID, $categoryID, $cityID)
    {
        if (empty($svcID) || !$categoryID || !$cityID) return array();
        $result = array_fill_keys($svcID, 0);

        $cityData = Geo::regionData($cityID);
        if (empty($cityData) || !Geo::isCity($cityData) || !$cityData['pid']) return $result;

        $catParents = $this->catParentsID($categoryID);
        if (empty($catParents) || !isset($catParents[1])) return $result;
        $categoryID1 = $catParents[1];
        $categoryID2 = (isset($catParents[2]) ? $catParents[2] : 0);

        # получаем доступные варианты региональной стоимости услуг
        $prices = $this->db->select('SELECT * FROM ' . TABLE_BBS_SVC_PRICE . '
                    WHERE ' . $this->db->prepareIN('svc_id', $svcID) . ' AND category_id IN(:cat1, :cat2)
                    ORDER BY num',
            array(':cat1' => $categoryID1, ':cat2' => $categoryID2)
        );
        if (empty($prices)) return array();

        foreach ($svcID as $id) {
            # категория(2) + город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2) + регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2) + страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == $cityData['country']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + город
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityID) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + регион(область)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityData['pid']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1) + страна
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == $cityData['country']) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(2)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID2 && $v['region_id'] == 0) {
                    $result[$id] = $v['price'];
                    continue 2;
                }
            }
            # категория(1)
            foreach ($prices as $v) {
                if ($v['svc_id'] == $id && $v['category_id'] == $categoryID1 && $v['region_id'] == 0) {
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
        $aData = $this->db->select('SELECT * FROM ' . TABLE_BBS_SVC_PRICE . ' ORDER BY svc_id, id, num');
        if (!empty($aData)) {
            $aRegionsID = array();
            foreach ($aData as $v) {
                if (!isset($aResult[$v['svc_id']])) {
                    $aResult[$v['svc_id']] = array();
                }
                if (!isset($aResult[$v['svc_id']][$v['id']])) {
                    $aResult[$v['svc_id']][$v['id']] = array(
                        'price'   => $v['price'],
                        'cats'    => array(),
                        'regions' => array()
                    );
                }
                if ($v['category_id'] > 0) $aResult[$v['svc_id']][$v['id']]['cats'][] = $v['category_id'];
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
                    $vv['cats'] = array_unique($vv['cats']);
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
            if ($v['price'] <= 0 || empty($v['cats'])) {
                continue;
            }

            $v['cats'] = array_unique($v['cats']);
            $v['regions'] = array_unique($v['regions']);
            foreach ($v['cats'] as $cat) {
                foreach ($v['regions'] as $region) {
                    $sql[] = array(
                        'id'          => $id,
                        'svc_id'      => $nSvcID,
                        'price'       => $v['price'],
                        'category_id' => $cat,
                        'region_id'   => $region,
                        'num'         => $num++,
                    );
                }
                if (empty($v['regions'])) {
                    $sql[] = array(
                        'id'          => $id,
                        'svc_id'      => $nSvcID,
                        'price'       => $v['price'],
                        'category_id' => $cat,
                        'region_id'   => 0,
                        'num'         => $num++,
                    );
                }
            }
            $id++;
        }

        $this->db->delete(TABLE_BBS_SVC_PRICE, array('svc_id' => $nSvcID));
        if (!empty($sql)) {
            foreach (array_chunk($sql, 25) as $v) {
                $this->db->multiInsert(TABLE_BBS_SVC_PRICE, $v);
            }
        }
    }

    public function svcCron()
    {
        $sNow = $this->db->now();
        $sEmpty = '0000-00-00 00:00:00';

        # Деактивируем услугу "Выделение"
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
            SET svc = (svc - ' . BBS::SERVICE_MARK . '), svc_marked_to = :empty
            WHERE (svc & ' . BBS::SERVICE_MARK . ') AND svc_marked_to <= :now',
            array(':now' => $sNow, ':empty' => $sEmpty)
        );

        # Деактивируем услугу "Срочно"
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
            SET svc = (svc - ' . BBS::SERVICE_QUICK . '), svc_quick_to = :empty
            WHERE (svc & ' . BBS::SERVICE_QUICK . ') AND svc_quick_to <= :now',
            array(':now' => $sNow, ':empty' => $sEmpty)
        );

        # Деактивируем услугу "Закрепление"
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
            SET svc = (svc - ' . BBS::SERVICE_FIX . '), svc_fixed_to = :empty, svc_fixed_order = :empty
            WHERE (svc & ' . BBS::SERVICE_FIX . ') AND svc_fixed_to <= :now',
            array(':now' => $sNow, ':empty' => $sEmpty)
        );

        # Деактивируем услугу "Премиум"
 #       if (BBS::PREMIUM_ON) {
            $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
                SET svc = (svc - ' . BBS::SERVICE_PREMIUM . '), svc_premium_to = :empty, svc_premium_order = :empty
                WHERE (svc & ' . BBS::SERVICE_PREMIUM . ') AND svc_premium_to <= :now',
                array(':now' => $sNow, ':empty' => $sEmpty)
            );
 #       }

        # Снимаем пометку об активации услуги "Поднятие", выполненную 3 и более дней назад
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
            SET svc = (svc - ' . BBS::SERVICE_UP . ')
            WHERE (svc & ' . BBS::SERVICE_UP . ') AND svc_up_date <= :now',
            array(':now' => date('Y-m-d', strtotime('-3 days')))
        );

        # Разрешаем повторную печать в прессе для отправленных на печать на следующий день
        $this->db->exec('UPDATE ' . TABLE_BBS_ITEMS . '
            SET svc_press_date_last = svc_press_date, svc_press_date = :empty, svc_press_status = 0
            WHERE svc_press_status = :status AND svc_press_date <= :now',
            array(
                ':status' => BBS::PRESS_STATUS_PUBLICATED,
                ':empty'  => '0000-00-00',
                ':now'    => date('Y-m-d'),
            )
        );

    }

    public function getLocaleTables()
    {
        return array(
            TABLE_BBS_CATEGORIES                => array('type' => 'table', 'fields' => $this->langCategories),
            TABLE_BBS_CATEGORIES_TYPES          => array('type' => 'fields', 'fields' => $this->langCategoriesTypes),
            TABLE_BBS_CATEGORIES_DYNPROPS       => array(
                'type'   => 'fields',
                'fields' => array(
                    'title'       => TYPE_NOTAGS,
                    'description' => TYPE_NOTAGS
                )
            ),
            TABLE_BBS_CATEGORIES_DYNPROPS_MULTI => array('type' => 'fields', 'fields' => array('name' => TYPE_NOTAGS)),
        );
    }
}