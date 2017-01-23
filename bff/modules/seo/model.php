<?php

define('TABLE_LANDING_PAGES',      DB_PREFIX . 'landingpages');
define('TABLE_LANDING_PAGES_LANG', DB_PREFIX . 'landingpages_lang');

class SEOModelBase extends Model
{
    /** @var SeoBase */
    protected $controller;
    public $langLandingPages = array(
        'mtitle'        => TYPE_NOTAGS,  # Meta Title
        'mkeywords'     => TYPE_NOTAGS,  # Meta Keywords
        'mdescription'  => TYPE_NOTAGS,  # Meta Description
    );

    public function init()
    {
        parent::init();

        if (SEO::landingPagesEnabled()) {
            # Добавляем доп. поля посадочных страниц
            $extraFields = SEO::landingPagesFields();
            if (!empty($extraFields)) {
                foreach ($extraFields as $k=>$v) {
                    if (is_string($k) && !isset($this->langLandingPages[$k])) {
                        $this->langLandingPages[$k] = (!empty($v['type']) && $v['type'] == 'wy' ? TYPE_STR : TYPE_NOTAGS);
                    }
                }
            }
        }
    }

    # --------------------------------------------------------------------
    # Посадочные страницы

    /**
     * Список страниц (admin)
     * @param array $aFilter фильтр списка страниц
     * @param bool $bCount только подсчет кол-ва страниц
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function landingpagesListing(array $aFilter, $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        $aFilter = $this->prepareFilter($aFilter, 'LP');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(LP.id) FROM '.TABLE_LANDING_PAGES.' LP '.$aFilter['where'], $aFilter['bind']);
        }

        return $this->db->select('SELECT LP.id, LP.landing_uri, LP.title, LP.enabled
               FROM '.TABLE_LANDING_PAGES.' LP
               '.$aFilter['where']
               .( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '')
               .$sqlLimit, $aFilter['bind']);
    }

    /**
     * Список страниц (frontend)
     * @param array $aFilter фильтр списка страниц
     * @param bool $bCount только подсчет кол-ва страниц
     * @param string $sqlLimit
     * @param string $sqlOrder
     * @return mixed
     */
    public function landingpagesList(array $aFilter = array(), $bCount = false, $sqlLimit = '', $sqlOrder = '')
    {
        if ( ! $bCount) $aFilter[':lang'] = $this->db->langAnd(false, 'LP', 'LPL');
        $aFilter = $this->prepareFilter($aFilter, 'LP');

        if ($bCount) {
            return $this->db->one_data('SELECT COUNT(LP.id) FROM '.TABLE_LANDING_PAGES.' LP '.$aFilter['where'], $aFilter['bind']);
        }

        $aData = $this->db->select('SELECT LP.id
                                  FROM '.TABLE_LANDING_PAGES.' LP, '.TABLE_LANDING_PAGES_LANG.' LPL
                                  '.$aFilter['where'].'
                                  '.( ! empty($sqlOrder) ? ' ORDER BY '.$sqlOrder : '').'
                                  '.$sqlLimit, $aFilter['bind']);

        if ( ! empty($aData))
        {
            //
        }

        return $aData;
    }

    /**
     * Формирование данных о посадочных страницах для файла Sitemap.xml
     * @param boolean $callback
     * @return array|callable [['l'=>'url страницы','m'=>'дата последних изменений'],...]
     */
    public function landingpagesSitemapXmlData($callback = true)
    {
        if ($callback) {
            return function($count = false, callable $callback = null){
                if ($count) {
                    return $this->db->one_data('SELECT COUNT(*) FROM '.TABLE_LANDING_PAGES.' WHERE enabled = 1');
                } else {
                    $languageKey = $this->locale->getDefaultLanguage();
                    $this->db->select_iterator('
                        SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                        FROM '.TABLE_LANDING_PAGES.'
                        WHERE enabled = 1
                        ORDER BY modified DESC',
                        array(':format' => '%Y-%m-%d'),
                        function (&$row) use ($languageKey, &$callback) {
                            $row['l'] = bff::urlBase(false, $languageKey).$row['l'];
                            $callback($row);
                        });
                }
                return false;
            };
        }

        $aData = $this->db->select('SELECT landing_uri AS l, DATE_FORMAT(modified, :format) as m
                                  FROM '.TABLE_LANDING_PAGES.'
                                  WHERE enabled = 1
                                  ORDER BY modified DESC', array(
                                    ':format' => '%Y-%m-%d',
                                  ));
        if (!empty($aData)) {
            $languageKey = $this->locale->getDefaultLanguage();
            foreach ($aData as &$v) {
                $v['l'] = bff::urlBase(false, $languageKey).$v['l'];
            } unset ($v);
            return $aData;
        } else {
            return array();
        }
    }

    /**
     * Получение данных страницы
     * @param integer $nLandingpageID ID страницы
     * @param boolean $bEdit при редактировании
     * @return array
     */
    public function landingpageData($nLandingpageID, $bEdit = false)
    {
        if ($bEdit) {
            $aData = $this->db->one_array('SELECT LP.*
                    FROM '.TABLE_LANDING_PAGES.' LP
                    WHERE LP.id = :id',
                    array(':id'=>$nLandingpageID));
            if ( ! empty($aData)) {
                $this->db->langSelect($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);
            }
        } else {
            //
        }
        return $aData;
    }

    /**
     * Получение данных страницы по URI
     * @param string $landingUri URI посадочной страницы
     * @param boolean $enabledOnly только включенные
     * @return array|boolean
     */
    public function landingpageDataByURI($landingUri, $enabledOnly = true)
    {
        if (empty($landingUri)) {
            return false;
        }
        $aData = $this->db->one_array('SELECT P.*, PL.*
                FROM '.TABLE_LANDING_PAGES.' P,
                     '.TABLE_LANDING_PAGES_LANG.' PL
                WHERE P.landing_uri = :uri'.($enabledOnly ? ' AND P.enabled = 1' : '').$this->db->langAnd(true, 'P', 'PL'),
                array(':uri'=>$landingUri));
        if (empty($aData)) {
            return false;
        }
        return $aData;
    }

    /**
     * Сохранение страницы
     * @param integer $nLandingpageID ID страницы
     * @param array $aData данные страницы
     * @return boolean|integer
     */
    public function landingpageSave($nLandingpageID, array $aData)
    {
        if (empty($aData)) return false;

        if ($nLandingpageID > 0)
        {
            $aData['modified'] = $this->db->now(); # Дата изменения

            $res = $this->db->update(TABLE_LANDING_PAGES, array_diff_key($aData, $this->langLandingPages), array('id'=>$nLandingpageID));

            $this->db->langUpdate($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);

            return ! empty($res);
        }
        else
        {
            $aData['created']  = $this->db->now(); # Дата создания
            $aData['modified'] = $this->db->now(); # Дата изменения
            $aData['user_id']  = User::id(); # Пользователь
            $aData['user_ip']  = Request::remoteAddress(true); # IP адрес

            $nLandingpageID = $this->db->insert(TABLE_LANDING_PAGES, array_diff_key($aData, $this->langLandingPages));
            if ($nLandingpageID > 0) {
                $this->db->langInsert($nLandingpageID, $aData, $this->langLandingPages, TABLE_LANDING_PAGES_LANG);
                //
            }
            return $nLandingpageID;
        }
    }

    /**
     * Переключатели страницы
     * @param integer $nLandingpageID ID страницы
     * @param string $sField переключаемое поле
     * @return mixed @see toggleInt
     */
    public function landingpageToggle($nLandingpageID, $sField)
    {
        switch ($sField) {
            case 'enabled': { # Включен
                return $this->toggleInt(TABLE_LANDING_PAGES, $nLandingpageID, $sField, 'id');
            } break;
        }
    }

    /**
     * Удаление страницы
     * @param integer $nLandingpageID ID страницы
     * @return boolean
     */
    public function landingpageDelete($nLandingpageID)
    {
        if (empty($nLandingpageID)) return false;
        $res = $this->db->delete(TABLE_LANDING_PAGES, array('id'=>$nLandingpageID));
        if ( ! empty($res)) {
            $this->db->delete(TABLE_LANDING_PAGES_LANG, array('id'=>$nLandingpageID));
            return true;
        }
        return false;
    }

}