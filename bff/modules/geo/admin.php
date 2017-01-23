<?php

/**
 * Права доступа группы:
 *  - site: Настройки сайта
 *      - regions: Регионы (добавление/редактирование/удаление (стран/областей/городов))
 */

use bff\utils\Files;

class GeoModule extends GeoModuleBase
{
    # -------------------------------------------------------------------------
    # Регионы

    public function regions()
    {
        if (static::manageRegions(self::lvlCountry)) {
            return $this->regions_country();
        }

        return $this->regions_city();
    }

    protected function regions_listing(&$aData, $sTemplate)
    {
        $aData['content'] = $this->viewPHP($aData, $sTemplate, $this->module_dir_tpl_core);

        return $this->viewPHP($aData, 'admin.regions.listing', $this->module_dir_tpl_core);
    }

    public function regions_country()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $aData = array(
            'numlevel'  => self::lvlCountry,
            'country'   => $this->input->get('country', TYPE_UINT),
            'pid'       => 0,
            'main'      => 0,
            'rotate'    => 1,
            'countries' => $this->model->regionsListing(0, false, array('pid' => 0), array(), 'num')
        );

        return $this->regions_listing($aData, 'admin.regions.listing.country');
    }

    public function regions_region()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nCountryID = $this->input->get('country', TYPE_UINT);
        if (empty($nCountryID)) {
            $nCountryID = key($this->regionsList(self::lvlCountry));
        }

        $aData = array(
            'numlevel' => self::lvlRegion,
            'country'  => $nCountryID,
            'pid'      => $nCountryID,
            'main'     => $this->input->get('main', TYPE_BOOL),
        );
        $aData['rotate'] = true;

        $aFilter = array('pid' => $aData['pid'], 'numlevel' => $aData['numlevel']);
        if ($aData['main']) {
            $aFilter[':main'] = 'main > 0';
            $aData['regions'] = $this->model->regionsListing($nCountryID, false, $aFilter, array(), 'main');
        } else {
            $nCount = $this->model->regionsListing($nCountryID, true, $aFilter, array());

            $this->generatePagenation($nCount, 1000, $this->adminLink(bff::$event . "&country=$nCountryID&{pageId}"), $sqlLimit);

            $aData['regions'] = $this->model->regionsListing($nCountryID, false, $aFilter, array(), 'num', $sqlLimit);
        }

        if (static::manageRegions(self::lvlCountry)) {
            $aData['country_options'] = $this->regionsOptions(self::lvlCountry, $nCountryID);
        }

        return $this->regions_listing($aData, 'admin.regions.listing.region');
    }

    public function regions_city()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nLimit = 50;
        $nCountryID = $this->input->get('country', TYPE_UINT);
        if (empty($nCountryID)) {
            $nCountryID = key($this->regionsList(self::lvlCountry));
        }

        $nRegionID = $this->input->get('pid', TYPE_UINT);

        $aData = array(
            'numlevel' => self::lvlCity,
            'country'  => $nCountryID,
            'pid'      => $nRegionID,
            'main'     => $this->input->get('main', TYPE_BOOL),
        );

        $aFilter = array('numlevel' => $aData['numlevel']);
        if ($nRegionID) {
            $aFilter['pid'] = $nRegionID;
            $nLimit = 1000;
        }
        $bRotate = true;

        if ($aData['main']) {
            $aFilter[':main'] = 'main > 0';
            $aData['cities'] = $this->model->regionsListing($nCountryID, false, $aFilter, array(), 'main');
        } else {
            $aBind = array();
            $aData['city'] = $this->input->get('city', TYPE_NOTAGS);
            if ($aData['city']) {
                $aFilter[':title'] = 'title_' . LNG . ' LIKE (:city)';
                $aBind[':city'] = $aData['city'] . '%';
            }
            $nCount = $this->model->regionsListing($nCountryID, true, $aFilter, $aBind);
            $this->generatePagenation($nCount, $nLimit, $this->adminLink(bff::$event . "&country=$nCountryID&pid=$nRegionID&{pageId}"), $sqlLimit);

            if (!$nRegionID && static::manageRegions(self::lvlRegion)) {
                $bRotate = false;
            }
            $aData['cities'] = $this->model->regionsListing($nCountryID, false, $aFilter, $aBind, ($bRotate ? 'num' : 'title_' . LNG), $sqlLimit);
        }

        $aData['rotate'] = $bRotate;

        if (static::manageRegions(self::lvlCountry)) {
            $aData['country_options'] = $this->regionsOptions(self::lvlCountry, $nCountryID);
        }
        $aData['region_options'] = $this->regionsOptions(self::lvlRegion, $nRegionID, $nCountryID, array(0, 'Все'));

        return $this->regions_listing($aData, 'admin.regions.listing.city');
    }

    public function regions_city_form()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nCountryID = $this->input->getpost('country', TYPE_UINT);
        $nRegionID = $this->input->getpost('pid', TYPE_UINT);
        $nCityID = $this->input->getpost('id', TYPE_UINT);

        $sRedirect = 'regions_city&country=' . $nCountryID . '&pid=' . $nRegionID;

        if (!$nCityID) {
            $this->adminRedirect(Errors::UNKNOWNRECORD, $sRedirect);
        }

        $aData = $this->model->regionData(array('id' => $nCityID), true);
        if (empty($aData)) {
            $this->adminRedirect(Errors::IMPOSSIBLE, $sRedirect);
        }

        if (Request::isPOST()) {
            $this->input->postm_lang($this->model->langRegions, $aDataSave);
            $this->input->postm(array(
                    'pid'     => TYPE_UINT,
                    'ycoords' => TYPE_STR,
                    'keyword' => TYPE_STR,
                    'metro'   => TYPE_BOOL,
                ), $aDataSave
            );

            if (!$this->model->regionKeywordIsUnique($aDataSave['keyword'], $nCityID, self::lvlCity)) {
                $this->errors->set('Указанный URL-keyword уже используется');
            }

            if ($this->errors->no()) {
                $this->model->regionSave($nCityID, $aDataSave);

                $this->resetCache(self::lvlCity, $nCountryID);
                if ($aData['pid'] != $aDataSave['pid']) {
                    # область(регион) была изменена
                }

                $this->adminRedirect(Errors::SUCCESS, $sRedirect);
            }
            $aData = array_merge($aData, $aDataSave);
            $aData = HTML::escape($aData);
        }

        $aData['redirect'] = $this->adminLink($sRedirect);
        $aData['regions'] = $this->model->regionsListing($aData['country'], false, array('numlevel' => self::lvlRegion));
        $aData['regions_options'] = HTML::selectOptions($aData['regions'], $aData['pid'], (empty($aData['regions']) ? '-----' : false), 'id', 'title');
        $aData['districts'] = $this->model->districtsListing($nCityID, false);
        $aData['country_title'] = static::regionTitle($aData['country']);

        \tpl::includeJS(array(
                'http://api-maps.yandex.ru/2.0/?load=package.full&coordorder=' . static::$ymapsCoordOrder . '&lang=ru-RU&onerror=onYMapError',
                'maps.editor'
            ), true
        );

        return $this->viewPHP($aData, 'admin.regions.form.city', $this->module_dir_tpl_core);
    }

    public function regions_city_districts()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nDistrictID = $this->input->postget('id', TYPE_UINT);
        $nCityID = $this->input->getpost('city', TYPE_UINT);
        $nRegionID = $this->input->getpost('pid', TYPE_UINT);
        $nCountryID = $this->input->getpost('country', TYPE_UINT);

        $sRedirect = 'regions_city&country=' . $nCountryID . '&pid=' . $nRegionID;

        if (\Request::isAJAX()) {
            if (!$nCityID || !$nDistrictID) {
                $this->ajaxResponse(Errors::IMPOSSIBLE);
            }

            switch ($this->input->get('act')) {
                case 'edit':
                {
                    $aRegionData = $this->model->districtData($nDistrictID, true);
                    $this->ajaxResponseForm($aRegionData);
                }
                break;
                case 'delete':
                {
                    $this->model->districtDelete($nDistrictID);
                    $this->resetCache(self::lvlDistrict, $nCityID);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
                break;
            }

            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        if ($nCityID && Request::isPOST()) {
            switch ($this->input->postget('act')) {
                case 'add':
                {
                    $aData = $this->validateDistrictData(0);
                    if ($this->errors->no()) {
                        $this->model->districtSave(0, $nCityID, $aData);
                        $this->resetCache(self::lvlDistrict, $nCityID);
                    }
                }
                break;
                case 'edit':
                {
                    if (!$nDistrictID) {
                        $this->errors->unknownRecord();
                    } else {
                        $aData = $this->validateDistrictData($nDistrictID);
                    }

                    if ($this->errors->no()) {
                        $this->model->districtSave($nDistrictID, $nCityID, $aData);
                        $this->resetCache(self::lvlDistrict, $nCityID);
                    }
                }
                break;
            }

            $this->adminRedirect(Errors::SUCCESS, $sRedirect);
        }

        $this->adminRedirect(Errors::IMPOSSIBLE, $sRedirect);
    }

    /**
     * Обрабатываем параметры района
     * @param int $nDistrictID ID района или 0
     * @return array параметры района
     */
    protected function validateDistrictData($nDistrictID)
    {
        $aData = array();

        $this->input->postm_lang($this->model->langDistricts, $aData);
        $this->input->postm(array(
                'ybounds' => TYPE_STR,
                'ypoly'   => TYPE_STR,
            ), $aData
        );

        if (Request::isPOST()) {
        }

        return $aData;
    }

    # -------------------------------------------------------------------------
    # Метро

    public function regions_metro()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nCountryID = $this->input->get('country', TYPE_UINT);
        if (empty($nCountryID)) {
            $nCountryID = key($this->regionsList(self::lvlCountry));
        }

        $nCityID = $this->input->getpost('city', TYPE_UINT);

        if (Request::isAJAX()) {
            switch ($this->input->getpost('act')) {
                case 'edit':
                {
                    $nMetroID = $this->input->get('id', TYPE_UINT);
                    if (!$nMetroID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $aData = $this->model->metroData($nMetroID, true);
                    if (empty($aData)) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    $aData['branches'] = $this->model->metroBranchesOptions($aData['city_id'], $aData['pid'], 'Не выбрана');
                    $aData = array('form' => $this->viewPHP($aData, 'admin.regions.form.metro', $this->module_dir_tpl_core));
                    $this->ajaxResponse($aData);
                }
                break;
                case 'rotate':
                {
                    $this->db->rotateTablednd(TABLE_REGIONS_METRO, '', 'id', 'num', true);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
                break;
                case 'delete':
                {
                    $nMetroID = $this->input->get('id', TYPE_UINT);
                    if (!$nMetroID) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    $res = $this->model->metroDelete($nMetroID);
                    if ($res) {
                        $this->resetCache(self::lvlMetro, $nCityID);
                    }

                    $this->ajaxResponse(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;
            }
        } else {
            if (\Request::isPOST()) {
                switch ($this->input->postget('act')) {
                    case 'add-finish':
                    {
                        $aData = $this->validateMetroData(0);
                        if ($this->errors->no()) {
                            $nMetroID = $this->model->metroSave(0, $aData);
                            if ($nMetroID) {
                                $this->resetCache(self::lvlMetro, $nCityID);
                            }
                        }
                    }
                    break;
                    case 'edit-finish':
                    {
                        $nMetroID = $this->input->post('id', TYPE_UINT);
                        if (!$nMetroID) {
                            $this->errors->unknownRecord();
                        }

                        $aData = $this->validateMetroData($nMetroID);
                        $isBranch = $aData['branch'];
                        unset($aData['branch']);
                        if ($this->errors->no()) {
                            $this->model->metroSave($nMetroID, $aData);
                            $this->resetCache(self::lvlMetro, $nCityID);
                            if ($isBranch) {
                                $aUpdate = array();
                                if (!empty($aUpdate)) {
                                    $this->model->metroSave($nMetroID, $aUpdate);
                                }
                            }
                        }
                    }
                    break;
                }

                $this->adminRedirect(Errors::SUCCESS, bff::$event . '&country=' . $nCountryID . '&city=' . $nCityID);
            }
        }

        $aCity = $this->model->metroCities($nCountryID);
        if (!$nCityID && !empty($aCity)) {
            $firstCity = reset($aCity);
            $nCityID = $firstCity['id'];
        }
        $aData['city_options'] = HTML::selectOptions($aCity, $nCityID, (empty($aCity) ? 'Нет доступных городов' : false), 'id', 'title');
        $aData['city'] = $nCityID;

        $aData['items'] = $this->model->metroListing($nCityID);

        $aDataForm = $this->validateMetroData(0);
        $aDataForm['id'] = 0;
        $aDataForm['branches'] = $this->model->metroBranchesOptions($nCityID, 0, 'Не выбрана');
        $aData['form'] = $this->viewPHP($aDataForm, 'admin.regions.form.metro', $this->module_dir_tpl_core);
        unset($aDataForm);

        $aData['country'] = $nCountryID;
        if (static::manageRegions(self::lvlCountry)) {
            $aData['country_options'] = $this->regionsOptions(self::lvlCountry, $nCountryID);
        }

        return $this->viewPHP($aData, 'admin.regions.listing.metro', $this->module_dir_tpl_core);
    }

    /**
     * Обрабатываем параметры метро/странции метро
     * @param int $nMetroID ID метро/станции метро или 0
     * @return array параметры метро/странции метро
     */
    protected function validateMetroData($nMetroID)
    {
        $aData = array();

        $this->input->postm_lang($this->model->langMetro, $aData);

        $aParams = array(
            'pid'    => TYPE_UINT, // ID ветки метро
            'branch' => TYPE_BOOL, // 1 - ветка метро, 0 - станция метро
            'color'  => array(TYPE_NOTAGS, 'len' => 20), // цвет ветки / станции
        );
        if (!$nMetroID) {
            $aParams['city_id'] = TYPE_UINT; // город
        }

        $this->input->postm($aParams, $aData);

        foreach ($aData['title'] as $k => $v) {
            $aData['title'][$k] = str_replace('"', '', $v);
        }
        $aData['color'] = str_replace('"', '', $aData['color']);

        return $aData;
    }

    # -------------------------------------------------------------------------
    # Общие

    public function dev_keywords_uniqueness()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        # пустые
        $aRegions = $this->db->select('SELECT R.id, R.title_' . LNG . ' as title
            FROM ' . TABLE_REGIONS . ' R
                LEFT JOIN ' . TABLE_REGIONS . ' PR ON R.pid = PR.id
            WHERE R.keyword = :empty', array(':empty' => '')
        );
        if (!empty($aRegions)) {
            foreach ($aRegions as &$v) {
                $this->db->update(TABLE_REGIONS, array(
                        'keyword' => trim(mb_strtolower(func::translit(str_replace(array('.,'), '', $v['title']))), ' -'),
                    ), array(
                        'id' => $v['id']
                    )
                );
            }
        }

        # корректировка дубликатов
        $aRegions = $this->db->select('SELECT COUNT(id) as cnt, GROUP_CONCAT(id) as id
            FROM ' . TABLE_REGIONS . '
            WHERE numlevel IN (' . self::lvlRegion . ',' . self::lvlCity . ')
            GROUP BY keyword
            HAVING cnt > 1
        '
        );
        if (!empty($aRegions)) {
            $aRegionsID = array();
            foreach ($aRegions as $v) {
                if ($v['cnt'] > 1 && !empty($v['id'])) {
                    $id = explode(',', $v['id']);
                    if (!empty($id)) {
                        foreach (array_map('intval', $id) as $vv) {
                            $aRegionsID[] = $vv;
                        }
                    }
                }
            }
            $aRegionsID = array_unique($aRegionsID);
            if (!empty($aRegionsID)) {
                $aRegions = $this->db->select('SELECT R.id, R.keyword, PR.keyword as pkeyword
                    FROM ' . TABLE_REGIONS . ' R
                        LEFT JOIN ' . TABLE_REGIONS . ' PR ON R.pid = PR.id
                    WHERE ' . $this->db->prepareIN('R.id', $aRegionsID)
                );
                if (!empty($aRegions)) {
                    foreach ($aRegions as $v) {
                        $this->db->update(TABLE_REGIONS, array(
                                'keyword' => $v['keyword'] . '-' . $v['pkeyword']
                            ), array('id' => $v['id'])
                        );
                    }
                }
            }
        }

        $this->adminRedirect(Errors::SUCCESS, 'regions');
    }

    public function dev_regions_ipgeobase()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        if (\Request::isAJAX()) {
            $aCountries = $this->regionsList(self::lvlCountry, 0);
            $nCityMathed = 0;
            if (!empty($aCountries)) {
                foreach ($aCountries as $v) {
                    if ($v['title_ru'] == 'Украина') {
                        $nCityMathed += $this->dev_regions_ipgeobase_sync($v['id'], 'Украина');
                    } else {
                        if ($v['title_ru'] == 'Россия') {
                            $nCityMathed += $this->dev_regions_ipgeobase_sync($v['id'], 'округ');
                        }
                    }
                }
            }

            $this->ajaxResponseForm(array('city_mathed' => $nCityMathed));
        }

        $aData = array();

        return $this->viewPHP($aData, 'admin.regions.ipgeobase', $this->module_dir_tpl_core);
    }

    protected function dev_regions_ipgeobase_sync($countryID, $countryKey)
    {
        static $bIpRangesSynced = false;

        $sFolder = PATH_BASE . 'files' . DS . 'ipgeobase' . DS;
        $nCityMatched = 0;

        # 1. Cинхронизируем города:
        # - совмещаем города из IpGeoBase c городами в таблице TABLE_REGIONS (по русскому названию города)
        $sContent = Files::getFileContent($sFolder . 'cities.txt');
        $sContent = iconv('WINDOWS-1251', 'UTF-8', $sContent);
        $sContent = explode(PHP_EOL, $sContent);

        /**
         * 0 - id города
         * 1 - название города
         * 2 - название региона(области)
         * 3 - название округа
         * 4 - широта центра города
         * 5 - долгота центра города
         */
        $keyId = 0;
        $keyTitle = 1;

        $aCities = array();
        foreach ($sContent as &$v) {
            # фильтруем города требуемой страны
            $city = explode("\t", $v);
            if (!empty($city) && !empty($city[$keyId]) && stripos($city[3], $countryKey) !== false) {
                $aCities[] = $city;
            }
        }
        unset($v);
        unset($sContent);

        foreach ($aCities as &$v) {
            $res = $this->db->update(TABLE_REGIONS, array('geo_id' => intval($v[$keyId])),
                array('title_ru = :title', 'numlevel' => self::lvlCity, 'country' => $countryID),
                array(':title' => $v[$keyTitle])
            );
            if (!empty($res)) {
                $nCityMatched++;
            }
        }
        unset($v);

        # 2. Формируем таблицу ip - диапазонов:
        if (!$bIpRangesSynced) {
            $this->db->exec('TRUNCATE TABLE ' . TABLE_REGIONS_GEOIP);
            $this->db->exec('ALTER TABLE ' . TABLE_REGIONS_GEOIP . ' DROP INDEX range_start');

            $sContent = Files::getFileContent($sFolder . 'cidr_optim.txt');
            $sContent = iconv('WINDOWS-1251', 'UTF-8', $sContent);
            $sContent = explode(PHP_EOL, $sContent);

            /**
             * 0 - начало блока (a*256*256*256+b*256*256+c*256+d)
             * 1 - конец блока (e*256*256*256+f*256*256+g*256+h)
             * 2 - блок адресов: a.b.c.d - e.f.g.h
             * 3 - страна: 'RU'
             * 4 - id города
             */

            $data = array();
            foreach ($sContent as &$v) {
                # фильтруем города требуемой страны
                $v = explode("\t", $v);
                if (empty($v[3]) || !($v[3] == 'UA' || $v[3] == 'RU')) {
                    continue;
                }

                list($ip_start, $ip_end) = explode(' - ', $v[2]);
                $data[] = array(
                    'ip_start'     => $ip_start,
                    'ip_end'       => $ip_end,
                    'range_start'  => $v[0],
                    'range_end'    => $v[1],
                    'country_code' => $v[3],
                    'city_id'      => $v[4],
                );
            }
            unset($v);

            foreach (array_chunk($data, 40) as $ch) {
                $this->db->multiInsert(TABLE_REGIONS_GEOIP, $ch);
            }

            $this->db->exec('ALTER TABLE ' . TABLE_REGIONS_GEOIP . ' ADD UNIQUE (range_start)');

            $bIpRangesSynced = true;
        }

        return $nCityMatched;
    }

    public function regions_ajax()
    {
        if (!$this->haveAccessTo('regions', 'site')) {
            return $this->showAccessDenied();
        }

        $nCountryID = $this->input->postget('country', TYPE_UINT);
        $nNumlevel = $this->input->postget('lvl', TYPE_UINT);

        switch ($this->input->get('act')) {
            case 'region-delete': # удаление: страна, область(регион), город
            {
                $nRecordID = $this->input->postget('rec', TYPE_UINT);
                if (!$nRecordID) {
                    break;
                }

                $res = $this->model->regionDelete($nRecordID);
                if ($res) {
                    $this->resetCache($nNumlevel, $nCountryID);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
            }
            break;
            case 'region-toggle': # включение/выключение: страна, область(регион), город
            {
                $nRecordID = $this->input->postget('rec', TYPE_UINT);
                if (!$nRecordID) {
                    break;
                }

                $bMain = $this->input->get('main', TYPE_BOOL); # переключатель статуса "главный": область(регион), город

                $res = $this->model->regionToggle($nRecordID, ($bMain ? 'main' : 'enabled'));
                if ($res) {
                    $this->resetCache($nNumlevel, $nCountryID);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
            }
            break;
            case 'region-edit': # начало редактирования: страна, область(регион), город
            {
                $nRegionID = $this->input->get('id', TYPE_UINT);
                if (!$nRegionID) {
                    break;
                }

                $aData = $this->model->regionData(array('id' => $nRegionID), true);

                if (empty($aData)) {
                    break;
                }

                echo $this->viewPHP($aData, 'admin.regions.form', $this->module_dir_tpl_core);
                exit;
            }
            break;
            case 'region-save': # завершение добавления/редактирование: страна, область(регион), город
            {
                $nRegionID = $this->input->post('id', TYPE_UINT);
                $nNumlevel = $this->input->post('numlevel', TYPE_UINT);
                $this->input->postm_lang($this->model->langRegions, $aData);
                if (!$nRegionID) {
                    $this->input->postm(array(
                            'pid'      => TYPE_UINT,
                            'country'  => TYPE_UINT,
                            'numlevel' => TYPE_UINT,
                            'keyword'  => TYPE_NOTAGS,
                            'main'     => TYPE_BOOL,
                            'metro'    => TYPE_BOOL,
                        ), $aData
                    );

                    if (empty($aData['numlevel'])) {
                        break;
                    }
                } else {
                    $this->input->postm(array(
                            'keyword' => TYPE_NOTAGS,
                            'metro'   => TYPE_BOOL,
                        ), $aData
                    );
                }

                if (!static::manageRegions(self::lvlMetro)) {
                    unset($aData['metro']);
                }

                if (array_key_exists('title', $this->model->langRegions)) {
                    foreach ($aData['title'] as &$v) {
                        if ($v == '') {
                            $this->errors->set('Название должно быть указано для каждого языка');
                            break;
                        }
                    }
                    unset($v);
                } else {
                    if (empty($aData['title'])) {
                        $this->errors->set('Название указано некорректно');
                    }
                }

                $aData['keyword'] = preg_replace('/[^A-Za-z0-9\_\-]/', '', mb_strtolower($aData['keyword']));
                if (in_array($nNumlevel, array(self::lvlRegion, self::lvlCity))) {
                    if (!$this->model->regionKeywordIsUnique($aData['keyword'], $nRegionID, $nNumlevel)) {
                        $this->errors->set('Указанный URL-keyword уже используется');
                    }
                }
                if ($this->errors->no()) {
                    $res = $this->model->regionSave($nRegionID, $aData);
                    if ($res) {
                        $this->resetCache($nNumlevel, $nCountryID);
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
            }
            break;
            case 'region-rotate': # перемещение: область(регион), город
            {
                $bMain = $this->input->get('main', TYPE_BOOL);

                $aCond = array('numlevel = ' . $nNumlevel);
                if ($bMain) {
                    $aCond[] = 'main > 0';
                    $sOrderField = 'main';
                } else {
                    $sOrderField = 'num';
                }
                $res = $this->model->regionsRotate($aCond, $sOrderField);
                if ($res) {
                    $this->resetCache($nNumlevel, $nCountryID);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
            }
            break;
            case 'country-rotate': # перемещение: страна
            {
                $res = $this->model->regionsRotate(array('pid' => 0));
                if ($res) {
                    $this->resetCache(self::lvlCountry);
                    $this->ajaxResponse(Errors::SUCCESS);
                }
            }
            break;
            case 'reset-cache': # полный сброс кеша
            {
                # удаляем кеш модуля Geo
                Cache::singleton('geo')->flush('geo');
                $this->ajaxResponseForm();
            }
            break;
        }

        $this->ajaxResponse(Errors::IMPOSSIBLE);
    }

}