<?php

/**
 * Права доступа группы:
 *  - site: Настройки сайта
 *      - instructions: Инструкции
 */
class Site extends SiteBase
{
    #---------------------------------------------------------------------------------------
    # Настройки сайта

    public function siteconfig()
    {
        if (!$this->haveAccessTo('settings')) {
            return $this->showAccessDenied();
        }

        $geoUrlKey = 'geo_url';
        $geoUrlDefault = Geo::URL_SUBDOMAIN;
        $geoUrlSettings = array(
            Geo::URL_SUBDOMAIN => array('t' => 'Регион в поддомене', 'ex' => Request::scheme() . '://<strong>kiev</strong>.' . SITEHOST),
            Geo::URL_SUBDIR    => array('t' => 'Регион в поддиректории', 'ex' => SITEURL . '/<strong>kiev</strong>/'),
            Geo::URL_NONE      => array('t' => 'Без региона', 'ex' => SITEURL)
        );

        $geoCoveringKey = 'geo_covering';
        $geoCoveringDefault = Geo::COVERING_COUNTRY;
        $geoCoveringLevels = array(
            Geo::lvlCountry => 'Выберите страну...',
            Geo::lvlRegion  => 'Выберите область...',
            Geo::lvlCity    => 'Выберите город...'
        );
        $geoCoveringSettings = array(
            Geo::COVERING_COUNTRIES => array('t' => 'Страны (несколько)'),
            Geo::COVERING_COUNTRY   => array('t' => 'Страна'),
            Geo::COVERING_REGION    => array('t' => 'Область'),
            Geo::COVERING_CITIES    => array('t' => 'Города (несколько)'),
            Geo::COVERING_CITY      => array('t' => 'Город'),
        );

        if (Request::isAJAX()) {
            $aResponse = array();
            switch ($this->input->getpost('act')) {
                case 'geo-covering-options':
                {
                    $level = $this->input->post('lvl', TYPE_UINT);
                    $parentID = $this->input->post('id', TYPE_UINT);
                    if (!$parentID || !$level) {
                        $this->errors->reloadPage();
                        break;
                    }
                    $levelQuery = $level + 1;
                    $aList = Geo::i()->regionsList($levelQuery, $parentID);
                    $aResponse['options'] = HTML::selectOptions($aList, 0, $geoCoveringLevels[$levelQuery], 'id', 'title');
                    $aResponse['items'] = array();
                    foreach ($aList as &$v) {
                        $aResponse['items'][$v['id']] = $v['title'];
                    }
                    unset($v);
                }
                break;
            }
            $this->ajaxResponseForm($aResponse);
        }

        $sCurrentTab = $this->input->postget('tab');
        if (empty($sCurrentTab)) {
            $sCurrentTab = 'general';
        }

        $aLangFields = array(
            'title'                => TYPE_STR,
            'title_admin'          => TYPE_STR,
            'offline_reason'       => TYPE_STR,
            'copyright'            => TYPE_STR,
            # страница "Контакты"
            'contacts_form_title'  => TYPE_STR, # заголовок страницы
            'contacts_form_text'   => TYPE_STR, # текст страницы
            'contacts_form_title2' => TYPE_STR, # заголовок формы
        );

        if (Request::isPOST() && $this->input->post('saveconfig', TYPE_BOOL)) {

            $conf = $this->input->postm(array(
                    'enabled' => TYPE_UINT,
                    'geo_default_coords' => TYPE_NOTAGS,
                )
            );

            {
                # geo-url:
                $conf[$geoUrlKey] = $this->input->post($geoUrlKey, TYPE_NOTAGS);
                if (!array_key_exists($conf[$geoUrlKey], $geoUrlSettings)) {
                    $conf[$geoUrlKey] = $geoUrlDefault;
                }
                $geoUrlPrev = Geo::urlType();
                if ($geoUrlPrev != $conf[$geoUrlKey]) {
                    bff::i()->callModules('onGeoUrlTypeChanged', array($geoUrlPrev, $conf[$geoUrlKey]));
                }
                
                # geo-covering:
                $conf[$geoCoveringKey] = $this->input->post($geoCoveringKey, TYPE_NOTAGS);
                $conf['geo_covering_lvl'] = $this->input->post('geo_covering_lvl', TYPE_ARRAY_ARRAY);
                $geoResetCache = false;
                if (!array_key_exists($conf[$geoCoveringKey], $geoCoveringSettings)) {
                    $conf[$geoCoveringKey] = $geoCoveringDefault;
                }
                foreach ($geoCoveringLevels as $k => $v) {
                    $conf['geo_covering_lvl' . $k] = 0;
                }
                foreach ($conf['geo_covering_lvl'][$conf[$geoCoveringKey]] as $k => $v) {
                    $conf['geo_covering_lvl' . $k] = $v;
                    if (!is_array($v) && $v != Geo::coveringRegion($k)) {
                        $geoResetCache = true;
                    }
                }
                unset($conf['geo_covering_lvl']);
                if ($conf[$geoCoveringKey] == Geo::COVERING_CITIES) {
                    $conf['geo_covering_lvl' . Geo::lvlCity] = join(',', $conf['geo_covering_lvl' . Geo::lvlCity]);
                    if ($conf['geo_covering_lvl' . Geo::lvlCity] != Geo::coveringRegion(Geo::lvlCity)) {
                        $geoResetCache = true;
                    }
                } else if ($conf[$geoCoveringKey] == Geo::COVERING_COUNTRIES) {
                    if (count($conf['geo_covering_lvl' . Geo::lvlCountry]) == 1) {
                        $conf[$geoCoveringKey] = Geo::COVERING_COUNTRY;
                        $conf['geo_covering_lvl' . Geo::lvlCountry] = reset($conf['geo_covering_lvl' . Geo::lvlCountry]);
                        $geoResetCache = true;
                    } else {
                        $conf['geo_covering_lvl' . Geo::lvlCountry] = join(',', $conf['geo_covering_lvl' . Geo::lvlCountry]);
                        if ($conf['geo_covering_lvl' . Geo::lvlCountry] != Geo::coveringRegion(Geo::lvlCountry)) {
                            $geoResetCache = true;
                        }
                    }
                }
                if ($conf[$geoCoveringKey] != Geo::coveringType()) {
                    $geoResetCache = true;
                }
                if ($geoResetCache) {
                    Geo::i()->resetCache();
                }
            }
            $this->input->postm_lang($aLangFields, $conf);
            $this->db->langFieldsModify($conf, $aLangFields, $conf);

            config::save($conf);
            config::update($this->adminLink('siteconfig&tab=' . $sCurrentTab . '&errno=' . Errors::SUCCESS));
        }

        $aConfig = config::$data;
        $aConfig = array_map('stripslashes', $aConfig);
        $this->db->langFieldsSelect($aConfig, $aLangFields);

        $aData = $aConfig;

        # counters
        $itemsCounters = array();
        $aData['bbs_items_total_publicated'] = BBS::model()->itemsPublicatedCounter();
        if ($aData['bbs_items_total_publicated'] > 0) {
            $itemsCounters['items'] = tpl::declension($aData['bbs_items_total_publicated'], 'объявление;объявления;объявлений');
        }
        if (bff::shopsEnabled()) {
            $aData['shops_total_active'] = Shops::model()->shopsActiveCounter();
            if ($aData['shops_total_active'] > 0) {
                $itemsCounters['shops'] = tpl::declension($aData['shops_total_active'], 'магазин;магазина;магазинов');
            }
        }
        $aData['geo_disabled'] = (sizeof($itemsCounters) > 0 && !FORDEV);
        $aData['geo_disabled_counters'] = $itemsCounters;

        # geo-url:
        if (empty($aData[$geoUrlKey]) || !array_key_exists($aData[$geoUrlKey], $geoUrlSettings)) {
            $aData[$geoUrlKey] = $geoUrlDefault;
        }
        $aData['geo_url_settings'] = $geoUrlSettings;

        # geo-covering:
        if (empty($aData[$geoCoveringKey]) || !array_key_exists($aData[$geoCoveringKey], $geoCoveringSettings)) {
            $aData[$geoCoveringKey] = $geoCoveringDefault;
        }
        $aData['geo_covering_settings'] = $geoCoveringSettings;
        $aData['geo_covering_lvl'] = array();
        $parentID = 0;
        $items = array();
        foreach ($geoCoveringLevels as $lvl => $title) {
            $current = Geo::coveringRegion($lvl);
            $items = Geo::i()->regionsList($lvl, $parentID);
            $aData['geo_covering_lvl'][$lvl] = array(
                'selected' => $current,
                'items'    => $items,
                'options'  => HTML::selectOptions($items, $current, $title, 'id', 'title')
            );
            $parentID = $current;
        }
        unset($parentID, $items);
        if (Geo::coveringType(Geo::COVERING_CITIES)) {
            $aCitiesSelected = Geo::coveringRegion();
            $aData['geo_covering_cities'] = Geo::model()->regionsList(Geo::lvlCity, array('id' => $aCitiesSelected), 0, 0
                , 'FIELD(R.id, ' . join(',', $aCitiesSelected) . ')' /* MySQL only */
            );
            foreach ($aData['geo_covering_cities'] as &$v) {
                $v['items_cnt'] = 0;
                if ($aData['bbs_items_total_publicated'] > 0) {
                    $v['items_cnt'] += BBS::model()->itemsPublicatedCounter(array('reg3_city' => $v['id']));
                }
                if (bff::shopsEnabled() && $aData['shops_total_active'] > 0) {
                    $v['items_cnt'] += Shops::model()->shopsActiveCounter(array('reg3_city' => $v['id']));
                }
            }
            unset($v);
        } else if (Geo::coveringType(Geo::COVERING_COUNTRIES)) {
            $aCountriesSelected = Geo::coveringRegion();
            $aData['geo_covering_countries'] = Geo::model()->regionsList(Geo::lvlCountry, array('id' => $aCountriesSelected), 0, 0
                , 'FIELD(R.id, ' . join(',', $aCountriesSelected) . ')' /* MySQL only */
            );
            foreach ($aData['geo_covering_countries'] as &$v) {
                $v['items_cnt'] = 0;
                if ($aData['bbs_items_total_publicated'] > 0) {
                    $v['items_cnt'] += BBS::model()->itemsPublicatedCounter(array('reg1_country' => $v['id']));
                }
                if (bff::shopsEnabled() && $aData['shops_total_active'] > 0) {
                    $v['items_cnt'] += Shops::model()->shopsActiveCounter(array('reg1_country' => $v['id']));
                }
            }
            unset($v);
        }

        $aData['geo_default_coords'] = Geo::mapDefaultCoords();

        # tabs
        $aData['tabs'] = array(
            'general' => array('t' => 'Общие настройки'),
            'offline' => array('t' => 'Выключение сайта'),
            'contact' => array('t' => 'Форма контактов'),
            'geo'     => array('t' => 'Регионы'),
        );
        if (!isset($aData['tabs'][$sCurrentTab])) {
            $sCurrentTab = key($aData['tabs']);
        }
        $aData['tab'] = $sCurrentTab;

        return $this->viewPHP($aData, 'admin.siteconfig');
    }

    #---------------------------------------------------------------------------------------
    # Инструкции

    public function instructions()
    {

        /**
         * Формат:
         * array(
         *   'Название закладки' => array(
         *      'уникальный ключ' => array(
         *          't' => 'Название инструкции',
         *          'field' => 'Тип поля' // доступные: 'wy' - wysiwyg, 'text' - input::text
         *      )
         *   ),
         *   'Название закладки' => array(...)
         * )
         */

        return $this->instructionsForm(array());
    }

    #---------------------------------------------------------------------------------------
    #AJAX

    public function ajax()
    {
        if (!$this->security->haveAccessToAdminPanel()) {
            $this->ajaxResponse(Errors::ACCESSDENIED);
        }

        switch ($this->input->get('act', TYPE_STR)) {
            case 'crop-image-init':
            {
                $p = $this->input->postm(array(
                        'folder'   => TYPE_STR,
                        'filename' => TYPE_STR,
                        'sizes'    => TYPE_ARRAY_ARRAY,
                        'ratio'    => TYPE_UNUM,
                        'module'   => TYPE_STR,
                    )
                );

                if (empty($p['sizes']) || empty($p['filename'])) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                if ($p['module'] == 'publications') {
                    # для модуля Publications, формат формирования путей к изображениям
                    # может изменяться в зависимости от типа публикации
                    $oPublications = bff::module('publications');
                    $pp = $this->input->postm(array(
                            'type'       => TYPE_UINT,
                            'item_id'    => TYPE_UINT,
                            'publicated' => TYPE_STR,
                        )
                    );
                    if (!$pp['type']) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    //custom: не отображать crop-preview шириной 350px
                    if (isset($p['sizes'][2]) && $p['sizes'][2][0] == 350) {
                        unset($p['sizes'][2]);
                    }

                    $oTypeSettings = $oPublications->getTypeSettings($pp['type']);

                    $aItemData = array(
                        'id'         => $pp['item_id'],
                        'size'       => $oTypeSettings->imgp['size_orig'],
                        'filename'   => $p['filename'],
                        'publicated' => $pp['publicated']
                    );

                    $p['url'] = $oPublications->getImagesPath(true, $aItemData, $oTypeSettings);
                    $path = $oPublications->getImagesPath(false, $aItemData, $oTypeSettings);
                } else {
                    if (empty($p['folder'])) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }
                    $p['url'] = bff::url($p['folder'], 'images') . $p['filename'];
                    $path = bff::path($p['folder'], 'images') . $p['filename'];
                }

                if (!file_exists($path)) {
                    $this->ajaxResponse(Errors::IMPOSSIBLE);
                }

                $dim = getimagesize($path);
                $p['width'] = $dim[0];
                $p['height'] = $dim[1];

                $aResponse = $p;
                $aResponse['html'] = $this->viewPHP($p, 'admin.crop.image');
                $aResponse['res'] = $this->errors->no();
                $this->ajaxResponse($aResponse);
            }
            break;
            case 'generate-keyword':
            {
                $sTitle = $this->input->post('title', TYPE_STR);
                $this->ajaxResponse(array('res' => true, 'keyword' => mb_strtolower(func::translit($sTitle))));
            }
            break;
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }


}