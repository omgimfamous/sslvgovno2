<?php

class Geo extends GeoBase
{
    public function ajax()
    {
        $aResponse = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            /**
             * Список выбора фильтрации по региону
             * @param int $nCountryID ID страны
             */
            case 'filter-desktop-step1':
            {
                $nCountryID = $this->input->post('region_id', TYPE_UINT);
                $aResponse['html'] = $this->filterData('desktop-country-step1', $nCountryID);
            }
            break;
            /**
             * Список выбора фильтрации по городу
             * @param int $nRegionID ID области(региона)
             */
            case 'filter-desktop-step2':
            {
                $nRegionID = $this->input->post('region_id', TYPE_UINT);
                $aResponse = $this->filterData('desktop-country-step2', $nRegionID);
            }
            break;
            /**
             * Autocomplete для городов/областей
             * - выбор фильтра региона, мобильная версия
             */
            case 'filter-phone-suggest':
            {
                $_POST['reg'] = 1; # города + области
                if (static::coveringType(static::COVERING_COUNTRIES)) {
                    $_POST['country'] = 1; # + страны
                }
                $aData = $this->regionSuggest(true);
                foreach ($aData as &$v) {
                    if ($v['numlevel'] == self::lvlCity) {
                        $v['link'] = static::url(array('region' => $v['pkey'], 'city' => $v['keyword']));
                    } else if ($v['numlevel'] == self::lvlRegion) {
                        $v['link'] = static::url(array('region' => $v['keyword'], 'city' => false));
                    } else {
                        $v['link'] = static::url(array('country' => $v['keyword']));
                    }
                }
                unset($v);
                $aData = array('list' => $aData, 'highlight' => true, 'q' => $this->input->post('q', TYPE_NOTAGS));
                $aResponse['html'] = $this->viewPHP($aData, 'filter.phone.suggest');
            }
            break;
            /**
             * Autocomplete для городов/областей
             */
            case 'region-suggest':
            {
                $this->regionSuggest(false);
            }
            break;
            /**
             * Список районов города
             * @param int $nCityID ID города
             * @param bool $bOptions true - в формате select::options, false - array
             */
            case 'districts-list':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $bOptions = $this->input->postget('opts', TYPE_BOOL);
                if (!$nCityID) {
                    $this->errors->impossible();
                    break;
                }
                if ($bOptions) {
                    $aResponse['districts'] = static::districtOptions($nCityID, 0, _t('filter', 'Не указан'));
                } else {
                    $aResponse['districts'] = static::districtList($nCityID);
                }
            }
            break;
            /**
             * Список станций метро города для формы ОБ
             * @param int $nCityID ID города
             */
            case 'form-metro':
            {
                $nCityID = $this->input->postget('city', TYPE_UINT);
                $aData = static::cityMetro($nCityID, 0, false);
                $aResponse['data'] = $aData['data'];
                $aResponse['branches'] = $this->viewPHP($aData, 'form.metro.step1');
                $aResponse['stations'] = array();
                foreach ($aData['data'] as $k => $v) {
                    $v['city_id'] = $nCityID;
                    $aResponse['stations'][$k] = $this->viewPHP($v, 'form.metro.step2');
                }
            }
            break;
            case 'country-presuggest':
            {
                $nCountryID = $this->input->postget('country', TYPE_UINT);
                $mResult = false;
                if ($nCountryID) {
                    $aData = static::regionPreSuggest($nCountryID, true);
                    $mResult = array();
                    foreach ($aData as $v) {
                        $mResult[] = array($v['id'], $v['title'], $v['metro'], $v['pid']);
                    }
                }
                $this->ajaxResponse($mResult);
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
        }
        $this->ajaxResponseForm($aResponse);
    }

    public function filterForm($deviceID = false)
    {
        if (empty($deviceID)) {
            $deviceID = bff::device();
        }
        $aData['device'] = $deviceID;

        return $this->viewPHP($aData, 'filter.form');
    }

    public function filterData($sDataType, $nParentID = 0)
    {
        switch ($sDataType) {
            case 'desktop-countries-step0': # выбор страны (COVERING_COUNTRIES)
            {
                $aData = array();
                $aData['countries'] = static::countriesList();
                foreach ($aData['countries'] as &$v) {
                    $v['link'] = static::url(array('country' => $v['keyword']));
                } unset($v);
                return $this->viewPHP($aData, 'filter.desktop.countries');
            }
            break;
            case 'desktop-country-step1': # выбор области/региона (COVERING_COUNTRY)
            {
                $aData = static::regionList($nParentID ? $nParentID : Geo::coveringRegion());
                $aResult = array();
                foreach ($aData as $v) {
                    $letter = mb_substr($v['title'], 0, 1);
                    $v['link'] = static::url(array('region' => $v['keyword']));
                    $aResult[$letter][] = $v;
                }
                $nCols = 3;
                $nInCol = ceil(sizeof($aData) / $nCols);
                $aData = array('regions' => $aResult, 'step' => 1, 'cols' => $nCols, 'in_col' => $nInCol);
                return $this->viewPHP($aData, 'filter.desktop.country');
            }
            break;
            case 'desktop-country-step2': # выбор города (COVERING_COUNTRY)
            {
                $aResponse = array('html' => '', 'region' => array());
                do {
                    $nSelectedID = 0;
                    $aRegion = self::regionData($nParentID);
                    if (empty($aRegion) || !in_array($aRegion['numlevel'], array(
                                self::lvlRegion,
                                self::lvlCity
                            )
                        )
                    ) {
                        break;
                    }
                    if ($aRegion['numlevel'] == self::lvlCity) {
                        $nParentID = $aRegion['pid'];
                        $nSelectedID = $aRegion['id'];
                        $aRegion = self::regionData($nParentID);
                        if (empty($aRegion) || $aRegion['numlevel'] != self::lvlRegion) {
                            break;
                        }
                    }

                    $aRegion['link'] = static::url(array('region' => $aRegion['keyword']));

                    $aData = self::cityList($nParentID);
                    $aResult = array();
                    if (!empty($aData)) {
                        foreach ($aData as $v) {
                            $letter = mb_substr($v['title'], 0, 1);
                            $v['link'] = static::url(array('region' => $aRegion['keyword'], 'city' => $v['keyword']));
                            $v['active'] = ($nSelectedID == $v['id']);
                            $aResult[$letter][] = $v;
                        }
                    }

                    $nCols = 4;
                    if (sizeof($aData) <= 20 && sizeof($aData) > 8) $nCols = 3;
                    $aData = array(
                        'cities' => $aResult,
                        'cols'   => $nCols,
                        'in_col' => ceil(sizeof($aData) / $nCols),
                        'region' => $aRegion,
                        'step'   => 2
                    );
                    $aResponse['html'] = $this->viewPHP($aData, 'filter.desktop.country');
                } while (false);
                if (Request::isAJAX()) {
                    return $aResponse;
                } else {
                    echo $aResponse['html'];
                }
            }
            break;
            case 'desktop-region': # выбор города (COVERING_REGION)
            {
                $aRegion = static::regionData(static::coveringRegion());
                if (empty($aRegion)) {
                    $aRegion = array('id' => 0, 'keyword' => '');
                }
                $aRegion['link'] = static::url(array('region' => $aRegion['keyword']));

                $aData = static::cityList($aRegion['id']);
                $nSelectedID = static::filter('id');
                $aResult = array();
                if (!empty($aData)) {
                    foreach ($aData as $v) {
                        $letter = mb_substr($v['title'], 0, 1);
                        $v['link'] = static::url(array('region' => $aRegion['keyword'], 'city' => $v['keyword']));
                        $v['active'] = ($nSelectedID == $v['id']);
                        $aResult[$letter][] = $v;
                    }
                }

                $nCols = 4;
                $aData = array(
                    'cities' => $aResult,
                    'cols'   => $nCols,
                    'in_col' => ceil(sizeof($aData) / $nCols),
                    'region' => $aRegion
                );

                return $this->viewPHP($aData, 'filter.desktop.region');
            }
            break;
            case 'desktop-cities': # выбор города (COVERING_CITIES)
            {
                $aData = static::cityListByID(static::coveringRegion());
                $nTotal = sizeof($aData);
                $nSelectedID = static::filter('id');
                $aResult = array();
                if (!empty($aData)) {
                    foreach ($aData as &$v) {
                        $letter = mb_substr($v['title'], 0, 1);
                        $v['link'] = static::url(array('city' => $v['keyword']));
                        $v['active'] = ($nSelectedID == $v['id']);
                        $aResult[$letter][] = $v;
                    }
                    unset($v);
                }

                $nCols = 4;
                $sColsClass = 'span3';
                foreach (array(
                             10 => array(1, 'span12'),
                             15 => array(2, 'span6'),
                             22 => array(3, 'span4')
                         ) as $n => $cols) {
                    if ($nTotal <= $n) {
                        $nCols = $cols[0];
                        $sColsClass = $cols[1];
                        break;
                    }
                }

                $aData = array(
                    'cities' => $aData,
                    'cities_letters' => $aResult,
                    'total' => $nTotal,
                    'cols' => $nCols,
                    'cols_class' => $sColsClass,
                    'in_col' => ceil(sizeof($aData) / $nCols),
                    'link_all' => static::url(array('region' => '')),
                );

                return $this->viewPHP($aData, 'filter.desktop.cities');
            }
            break;
            case 'phone-presuggest': # выбор города / странв
            {
                if (!Geo::coveringType(static::COVERING_COUNTRIES)) {
                    $nParentID = static::defaultCountry();
                }
                if ($nParentID) {
                    $aData = static::regionPreSuggest($nParentID, true);
                    foreach ($aData as &$v) {
                        if ($v['numlevel'] == self::lvlCity) {
                            $v['link'] = static::url(array('region' => $v['pkey'], 'city' => $v['keyword']));
                        } else {
                            $v['link'] = static::url(array('region' => $v['keyword'], 'city' => false));
                        }
                    }
                    unset($v);
                } else {
                    $aData = static::countriesList();
                    foreach ($aData as &$v) {
                        $v['link'] = static::url(array('country' => $v['keyword']));
                    }
                    unset($v);
                }
                $aData = array('list' => $aData, 'highlight' => false);

                return $this->viewPHP($aData, 'filter.phone.suggest');
            }
            break;
        }
    }

}