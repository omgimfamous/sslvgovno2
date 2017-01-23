<?php

/**
 * Используемые таблицы:
 * TABLE_REGIONS - таблица регионов (города, области/регионы, страны)
 * TABLE_REGIONS_METRO - таблица веток/станций метро
 * TABLE_REGIONS_DISTRICTS - таблица районов города
 * TABLE_REGIONS_GEOIP - таблица соответствия IP <> регионам (городам)
 */

class GeoModelBase extends Model
{
    public $langRegions = array(
        'title' => TYPE_NOTAGS, # название
    );

    public $langDistricts = array(
        'title' => TYPE_NOTAGS, # название
    );

    public $langMetro = array(
        'title' => TYPE_NOTAGS, # название
    );

    # ----------------------------------------------------------------------------------------------------
    # Регионы

    /**
     * Формируем список регионов (стран / областей / городов)
     * @param int|array $mNumlevel тип региона(нескольких регионов) (Geo::lvl_)
     * @param array $aFilter фильтр регионов
     * @param int $nSelectedID текущий активный регион (из выбираемого списка) или 0
     * @param int $nLimit
     * @param string $sOrderBy
     * @return mixed
     */
    public function regionsList($mNumlevel, array $aFilter, $nSelectedID = 0, $nLimit = 0, $sOrderBy = '')
    {
        $aBind = array();
        if (is_array($mNumlevel)) {
            $aFilter[':numlevel'] = 'R.numlevel IN (' . join(',', $mNumlevel) . ')';
        } else {
            $aFilter['numlevel'] = $mNumlevel;
        }
        if (!empty($nSelectedID) && $nSelectedID > 0 && isset($aFilter['enabled'])) {
            unset($aFilter['enabled']);
            $aFilter[':EnabledOrSel'] = '(R.enabled = 1 OR R.id = :sel)';
            $aBind[':sel'] = $nSelectedID;
        }

        $aFilter = $this->prepareFilter($aFilter, 'R', $aBind);

        return $this->db->select_key('SELECT R.*, R.title_' . LNG . ' as title,
                               P.keyword as pkey, P.title_' . LNG . ' as ptitle
                      FROM ' . TABLE_REGIONS . ' R
                         LEFT JOIN ' . TABLE_REGIONS . ' P ON R.pid = P.id
                      ' . $aFilter['where'] . '
                      ORDER BY ' . (!empty($sOrderBy) ? $sOrderBy : 'R.main DESC, R.num') . '
                      ' . (!empty($nLimit) ? $this->db->prepareLimit(0, $nLimit) : ''), 'id', $aFilter['bind']
        );
    }

    /**
     * Формируем список регионов (стран / областей / городов) - adm
     * @param int $nCountryID ID страны
     * @param bool $bCount только подсчет кол-ва
     * @param array $aFilter фильтр регионов
     * @param array $aBind
     * @param string $sqlOrder
     * @param string $sqlLimit
     * @return mixed
     */
    public function regionsListing($nCountryID, $bCount, $aFilter = array(), $aBind = array(), $sqlOrder = '', $sqlLimit = '')
    {
        $aFilter['country'] = $nCountryID;
        $aFilter = $this->prepareFilter($aFilter, false, $aBind);
        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(*)
                                      FROM ' . TABLE_REGIONS . '
                                      ' . $aFilter['where'],
                $aFilter['bind']
            );
        }

        return $this->db->select('SELECT id, pid, title_' . LNG . ' as title, enabled, keyword, main, metro, num
                                  FROM ' . TABLE_REGIONS . '
                                  ' . $aFilter['where'] . '
                                  ' . (!empty($sqlOrder) ? ' ORDER BY ' . $sqlOrder : '') .
            $sqlLimit, $aFilter['bind']
        );
    }

    public function regionsExportList($nNumlevel, array $aFilter = array())
    {
        switch($nNumlevel)
        {
            case Geo::lvlCountry:
            case Geo::lvlRegion:
            case Geo::lvlCity:
                $aFilter['numlevel'] = $nNumlevel;
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->select('SELECT id, title_' . LNG . ' as title, metro
                    FROM ' . TABLE_REGIONS . '
                    ' . $aFilter['where'] . '
                    ORDER BY main DESC, num',
                    $aFilter['bind']
                );
                break;
            case Geo::lvlMetro:
                $aFilter = $this->prepareFilter($aFilter);
                return $this->db->select_key('SELECT id, pid, title_' . LNG . ' as title, branch
                    FROM ' . TABLE_REGIONS_METRO . '
                    '.$aFilter['where'].'
                    ORDER BY pid, num', 'id', $aFilter['bind']
                );
                break;
        }
    }

    /**
     * Получение данных о регионе
     * @param array $aFilter фильтр
     * @param bool $bEdit для редактирования
     * @return mixed
     */
    public function regionData(array $aFilter, $bEdit = false)
    {
        $aFilter = $this->prepareFilter($aFilter, 'R');
        $aData = $this->db->one_array('SELECT R.*, P.keyword as pkey
                FROM ' . TABLE_REGIONS . ' R
                    LEFT JOIN ' . TABLE_REGIONS . ' P ON R.pid = P.id
                ' . $aFilter['where'], $aFilter['bind']
        );
        if (!empty($aData)) {
            if ($bEdit) {
                $this->db->langFieldsSelect($aData, $this->langRegions);
            } else {
                $aData['title'] = $aData['title_' . LNG];
            }
        }

        return $aData;
    }

    /**
     * Получаем ID региона(города) по IP адресу исходя из данных в таблице TABLE_REGIONS_GEOIP
     * @param string|bool $sIpAddr IP адрес или FALSE - текущий
     * @return mixed
     */
    public function regionDataByIp($sIpAddr = false)
    {
        $nRegionID = 0;
        $nIpAddr = ($sIpAddr !== false ? sprintf("%u", ip2long($sIpAddr)) : Request::remoteAddress(true));
        if (!empty($nIpAddr)) {
            $nRegionID = $this->db->one_data('SELECT G.city_id
                    FROM ' . TABLE_REGIONS_GEOIP . ' G
                WHERE G.range_start <= :ip
                ORDER BY G.range_start DESC
                LIMIT 1', array(':ip' => $nIpAddr)
            );
            if (empty($nRegionID)) {
                $nRegionID = 0;
            }
        }

        return $this->regionData(array('geo_id' => $nRegionID));
    }

    public function regionNumlevel($nRegionID)
    {
        return (int)$this->db->one_data('SELECT numlevel FROM ' . TABLE_REGIONS . '
                WHERE id = :id', array(':id' => $nRegionID)
        );
    }

    public function regionSave($nRegionID, array $aData = array())
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langRegions, $aData);
        if (isset($aData['declension'])) {
            $aData['declension'] = serialize($aData['declension']);
        }

        if ($nRegionID > 0) {
            return $this->db->update(TABLE_REGIONS, $aData, array('id' => $nRegionID));
        } else {
            # в случае если регионы не используются, получаем порядковый номер исходя из городов входящих в страну
            if ($aData['numlevel'] == Geo::lvlCity && !$aData['pid'] && !Geo::manageRegions(Geo::lvlRegion)) {
                $nNum = $this->db->one_data('SELECT MAX(num) FROM ' . TABLE_REGIONS . ' WHERE country = :country', array(':country' => $aData['country']));
            } else {
                $nNum = $this->db->one_data('SELECT MAX(num) FROM ' . TABLE_REGIONS . ' WHERE pid = :pid', array(':pid' => $aData['pid']));
            }
            $aData['num'] = intval($nNum) + 1;

            if (!empty($aData['main'])) {
                $nMain = $this->db->one_data('SELECT MAX(main) FROM ' . TABLE_REGIONS . '
                                    WHERE main > 0 AND numlevel = :nl', array(':nl' => $aData['numlevel'])
                );
                $aData['main'] = intval($nMain) + 1;
            }

            return $this->db->insert(TABLE_REGIONS, $aData, 'id');
        }
    }

    public function regionsRotate($aCond = '', $sOrderField = 'num')
    {
        if (!empty($aCond)) {
            $aCond = ' AND ' . join(' AND ', $aCond);
        }

        return $this->db->rotateTablednd(TABLE_REGIONS, $aCond, 'id', $sOrderField);
    }

    public function regionToggle($nRegionID, $sField = 'enabled')
    {
        switch ($sField) {
            case 'enabled':
            {
                return $this->toggleInt(TABLE_REGIONS, $nRegionID);
            }
            break;
            case 'main':
            {
                $aData = $this->db->one_array('SELECT pid, main, numlevel FROM ' . TABLE_REGIONS . ' WHERE id = :id LIMIT 1', array(':id' => $nRegionID));
                if (empty($aData)) {
                    return false;
                }
                $aUpdate = array();
                if ($aData['main']) {
                    $aUpdate['main'] = 0;
                } else {
                    $nMain = $this->db->one_data('SELECT MAX(main) FROM ' . TABLE_REGIONS . '
                                    WHERE main > 0 AND numlevel = :nl', array(':nl' => $aData['numlevel'])
                    );
                    $aUpdate['main'] = intval($nMain) + 1;
                }

                return $this->db->update(TABLE_REGIONS, $aUpdate, array('id' => $nRegionID));
            }
            break;
        }
    }

    public function regionDelete($nRegionID)
    {
        return $this->db->delete(TABLE_REGIONS, array('(id = :id OR pid = :id)'), array(':id' => $nRegionID));
    }

    /**
     * Проверка на уникальность URL-keyword'a региона
     * @param string $sKeyword URL-keyword
     * @param integer $nRegionID ID региона
     * @param integer $nRegionLevel тип региона (Geo::lvl_)
     * @return string
     */
    public function regionKeywordIsUnique($sKeyword, $nRegionID, $nRegionLevel)
    {
        if (empty($sKeyword)) {
            return false;
        }
        $aData = $this->regionData(array(
                'keyword' => $sKeyword,
                array('R.id != :id', ':id' => $nRegionID)
            )
        );

        return empty($aData);
    }

    public function regionParents($nRegionID)
    {
        $aResult = array(
            'db'   => array('reg1_country' => 0, 'reg2_region' => 0, 'reg3_city' => 0),
            'keys' => array('region' => '', 'city' => '')
        );

        do {
            if (!$nRegionID) {
                break;
            }

            $aData = $this->db->one_array('SELECT R1.id, R1.pid, R1.keyword, R1.country, R1.numlevel as lvl,
                                R2.keyword as parent_keyword
                            FROM ' . TABLE_REGIONS . ' R1
                                LEFT JOIN ' . TABLE_REGIONS . ' R2 ON R1.pid = R2.id
                            WHERE R1.id = :id', array(':id' => $nRegionID)
            );
            if (empty($aData)) {
                break;
            }

            switch ($aData['lvl']) {
                case Geo::lvlCountry:
                {
                    $aResult['db']['reg1_country'] = $nRegionID;
                }
                break;
                case Geo::lvlRegion:
                {
                    $aResult['db']['reg1_country'] = $aData['pid'];
                    $aResult['db']['reg2_region'] = $nRegionID;
                    $aResult['keys']['region'] = $aData['keyword'];
                }
                break;
                case Geo::lvlCity:
                {
                    $aResult['db']['reg1_country'] = $aData['country'];
                    $aResult['db']['reg2_region'] = $aData['pid'];
                    $aResult['keys']['region'] = $aData['parent_keyword'];
                    $aResult['db']['reg3_city'] = $nRegionID;
                    $aResult['keys']['city'] = $aData['keyword'];
                }
                break;
            }

        } while (false);

        return $aResult;
    }

    public function regionsCountriesAndRegions($bEnabled = true)
    {
        $aData = $this->db->select('SELECT R.id, R.pid, R.title_' . LNG . ' as title
                               FROM ' . TABLE_REGIONS . ' R, ' . TABLE_REGIONS . ' R2
                               WHERE R.numlevel IN(' . Geo::lvlCountry . ',' . Geo::lvlRegion . ')
                                 ' . ($bEnabled ? ' AND R.enabled = 1 ' : '') . '
                                 AND (R.pid = 0 OR (R.pid = R2.id' . ($bEnabled ? ' AND R2.enabled = 1' : '') . '))
                               ORDER BY R.main DESC, R.num'
        );

        return $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
    }

    # ---------------------------------------------------------------------------------
    # Районы

    /**
     * Формируем список районов города - frontend
     * @param int $nCityID ID города
     * @param array $aFilter фильтр районов
     * @param array $aBind
     * @return mixed
     */
    public function districtsList($nCityID, array $aFilter = array(), array $aBind = array())
    {
        $aFilter['city_id'] = $nCityID;
        $aFilter = $this->prepareFilter($aFilter, false, $aBind);

        return $this->db->select_key('SELECT id, title_' . LNG . ' as t, ybounds, ypoly
                                  FROM ' . TABLE_REGIONS_DISTRICTS . '
                                  ' . $aFilter['where'] . '
                                  ORDER BY 1', 'id', $aFilter['bind']
        );
    }

    public function districtsListing($nCityID)
    {
        return $this->db->select('SELECT id, title_' . LNG . ' as title
                           FROM ' . TABLE_REGIONS_DISTRICTS . '
                           WHERE city_id = :city
                           ORDER BY title_' . LNG, array(':city' => $nCityID)
        );
    }

    public function districtData($nDistrictID, $bEdit = false)
    {
        $aData = $this->db->one_array('SELECT *
                FROM ' . TABLE_REGIONS_DISTRICTS . '
                WHERE id = :id', array(':id' => $nDistrictID)
        );
        if ($bEdit) {
            $this->db->langFieldsSelect($aData, $this->langDistricts);
        }

        return $aData;
    }

    public function districtSave($nDistrictID, $nCityID, array $aData = array())
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langDistricts, $aData);

        $aData['city_id'] = $nCityID;
        if ($nDistrictID > 0) {
            return $this->db->update(TABLE_REGIONS_DISTRICTS, $aData, array('id' => $nDistrictID));
        } else {
            return $this->db->insert(TABLE_REGIONS_DISTRICTS, $aData, 'id');
        }
    }

    public function districtDelete($nDistrictID)
    {
        return $this->db->delete(TABLE_REGIONS_DISTRICTS, $nDistrictID);
    }

    # ---------------------------------------------------------------------------------
    # Метро

    public function metroListing($nCityID) // adm
    {
        $aData = $this->db->select('SELECT id, pid, title_' . LNG . ' as title, color, branch
                                FROM ' . TABLE_REGIONS_METRO . '
                                WHERE city_id = :city
                                ORDER BY pid, num', array(':city' => $nCityID)
        );

        if (!empty($aData)) {
            $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
        }

        return $aData;
    }

    public function metroList($nCityID, $bGetBranches = true) // frontend
    {
        if ($bGetBranches) {
            $aData = $this->db->select('SELECT id, pid, title_' . LNG . ' as t, color, branch as b
                                    FROM ' . TABLE_REGIONS_METRO . '
                                    WHERE city_id = :city
                                    ORDER BY pid, num', array(':city' => $nCityID)
            );

            if (!empty($aData)) {
                $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'st');
            }
        } else {
            $aData = $this->db->select('SELECT id, title_' . LNG . ' as t, color
                                    FROM ' . TABLE_REGIONS_METRO . '
                                    WHERE city_id = :city AND branch = 0
                                    ORDER BY num', array(':city' => $nCityID)
            );
            $aData = func::array_transparent($aData, 'id', true);
        }

        return $aData;
    }

    public function metroSave($nMetroID, array $aData)
    {
        if (empty($aData)) {
            return false;
        }

        $this->db->langFieldsModify($aData, $this->langMetro, $aData);

        if ($nMetroID > 0) {
            return $this->db->update(TABLE_REGIONS_METRO, $aData, array('id' => $nMetroID));
        } else {
            $nNum = (int)$this->db->one_data('SELECT MAX(num)
                            FROM ' . TABLE_REGIONS_METRO . ' WHERE pid = :pid', array(':pid' => $aData['pid'])
            );
            $aData['num'] = $nNum + 1;

            return $this->db->insert(TABLE_REGIONS_METRO, $aData);
        }
    }

    public function metroData($nMetroID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT * FROM ' . TABLE_REGIONS_METRO . ' WHERE id = :id', array(':id' => $nMetroID));
            if (!empty($aData)) {
                $this->db->langFieldsSelect($aData, $this->langMetro);
            }
        } else {
            if (Geo::$useMetroBranches) {
                $aData = $this->db->one_array('SELECT S.id, S.pid, S.city_id, S.title_' . LNG . ' as title, B.title_' . LNG . ' as branch_title
                    FROM ' . TABLE_REGIONS_METRO . ' S
                         LEFT JOIN ' . TABLE_REGIONS_METRO . ' B ON B.id = S.pid
                    WHERE S.id = :id', array(':id' => $nMetroID)
                );
            } else {
                $aData = $this->db->one_array('SELECT S.id, S.pid, S.city_id, S.title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS_METRO . ' S
                    WHERE S.id = :id', array(':id' => $nMetroID)
                );
            }
        }

        return $aData;
    }

    public function metroBranchesOptions($nCityID, $nSelectedID = 0, $mEmpty = false)
    {
        $aData = $this->db->select('SELECT id, title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS_METRO . '
                    WHERE city_id = :city
                      AND pid = 0
                      AND branch = 1
                    ORDER BY num
                    ', array(':city' => $nCityID)
        );

        return HTML::selectOptions($aData, $nSelectedID, $mEmpty, 'id', 'title');
    }

    /**
     * Список городов с метро
     * @param integer|bool $nCountryID ID страны или false (страна по-умолчанию)
     * @return mixed
     */
    public function metroCities($nCountryID = false)
    {
        if ($nCountryID === false) {
            $nCountryID = Geo::defaultCountry();
        }

        return $this->db->select('SELECT id, title_' . LNG . ' as title
                    FROM ' . TABLE_REGIONS . '
                    WHERE numlevel = ' . Geo::lvlCity . '
                      AND metro = 1
                      AND country = :country
                    ORDER BY main, num', array(':country' => $nCountryID)
        );
    }

    /**
     * Удаление станции метро
     * @param int $nMetroID ID станции/ветки метро
     * @return bool
     */
    public function metroDelete($nMetroID)
    {
        $res = $this->db->delete(TABLE_REGIONS_METRO, $nMetroID);

        return !empty($res);
    }

    public function getLocaleTables()
    {
        return array(
            TABLE_REGIONS           => array('type' => 'fields', 'fields' => $this->langRegions),
            TABLE_REGIONS_METRO     => array('type' => 'fields', 'fields' => $this->langMetro),
            TABLE_REGIONS_DISTRICTS => array('type' => 'fields', 'fields' => $this->langDistricts),
        );
    }
}