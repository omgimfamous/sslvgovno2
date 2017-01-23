<?php

class BBS extends BBSBase
{
    public function init()
    {
        parent::init();

        if (bff::$class == $this->module_name && Request::isGET()) {
            bff::setActiveMenu('//index');
        }
    }

    /**
     * Блок последних / премиум объявлений на главной
     * @return string HTML
     */
    public function indexLastBlock()
    {
        $nLimit = config::sys('bbs.index.last.limit', 10);
        if ($nLimit > 30) $nLimit = 30;
        if (!$nLimit) return '';

        $aData = array();
        $sLimit = $this->db->prepareLimit(0, $nLimit);
        $sqlTablePrefix = 'I.';
        $sOrder = $sqlTablePrefix.'publicated_order DESC';

        $aFilter = array(
            'status' => self::STATUS_PUBLICATED,
        );
        if (static::premoderation()) {
            $aFilter[':mod'] = $sqlTablePrefix . 'moderated > 0';
        }

        $aData['title'] = _t('bbs', 'Последние объявления');
        $aSvc = $this->svc()->model()->svcData(BBS::SERVICE_PREMIUM, array('id'));
        if (!empty($aSvc['on'])) {
            $aData['title'] = _t('bbs', 'Премиум объявления');
            $sOrder = $sqlTablePrefix.'svc_premium_order DESC';
            $aFilter[':premium'] = $sqlTablePrefix . 'svc & ' . BBS::SERVICE_PREMIUM . ' > 0 ';
        }

        $aData['items'] = $this->model->itemsList($aFilter, false, $sLimit, $sOrder);
        if (empty($aData['items'])) return '';

        return $this->viewPHP($aData, 'index.last.block');
    }

    /**
     * Список выбора категорий
     * @param string $sType тип списка
     * @param string $mDevice тип устройства bff::DEVICE_ или 'init'
     * @param int $nParentID ID parent-категории
     */
    public function catsList($sType = '', $mDevice = '', $nParentID = 0)
    {
        $showAll = false;
        
        if (Request::isAJAX()) {
            $sType = $this->input->getpost('act', TYPE_STR);
            $mDevice = $this->input->post('device', TYPE_STR);
            $nParentID = $this->input->post('parent', TYPE_UINT);
            $showAll = $this->input->post('showAll', TYPE_BOOL);
        }
        $sListingUrl = static::url('items.search');
        $oIcon = self::categoryIcon(0);
        $ICON_BIG = BBSCategoryIcon::BIG;
        $ICON_SMALL = BBSCategoryIcon::SMALL;
        switch ($sType) {
            case 'index': # список категории на главной
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # desktop+tablet
                {
                    /** @var integer $nSubSlice - максимально допустимое видимое кол-во подкатегорий */
                    $nSubSlice = 5;
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aData)) {
                        $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'sub');
                        foreach ($aData as $k => $v) {
                            $v['l'] = $sListingUrl . $v['k'] . '/';
                            $v['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            foreach ($v['sub'] as $kk => $vv) {
                                $v['sub'][$kk]['l'] = $sListingUrl . $vv['k'] . '/';
                            }
                            $v['subn'] = sizeof($v['sub']); # всего подкатегорий
                            $v['sub'] = array_slice($v['sub'], 0, $nSubSlice); # оставляем не более {$nSubSlice}
                            $v['subv'] = sizeof($v['sub']); # кол-во отображаемых подкатегорий
                            $aData[$k] = $v;
                        }
                    }
                    $aData = array('cats' => $aData);

                    return $this->viewPHP($aData, 'index.cats.desktop');
                } else {
                    if ($mDevice == bff::DEVICE_PHONE) {
                        $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                        if (!empty($aData)) {
                            foreach ($aData as $k => $v) {
                                $aData[$k]['l'] = $sListingUrl . $v['k'] . '/';
                                $aData[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            }
                        }
                        if ($nParentID > self::CATS_ROOTID) {
                            # список подкатегорий
                            $aParent = array(
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
                            $aParent = $this->model->catData($nParentID, $aParent);
                            if (!empty($aParent)) {
                                $aParent['link'] = $sListingUrl . $aParent['keyword'] . '/';
                                $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                                if ($aParent['main']) {
                                    $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                                } else {
                                    # глубже второго уровня, получаем иконку основной категории
                                    $aParentsID = $this->model->catParentsID($aParent, false);
                                    if (!empty($aParentsID[1])) {
                                        $aParentMain = $this->model->catData($aParentsID[1], array(
                                                'id',
                                                'icon_' . $ICON_SMALL . ' as icon'
                                            )
                                        );
                                        $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                    }
                                }
                                $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
                                $aData = $this->viewPHP($aData, 'index.cats.phone');
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
                            # список основных категорий
                            $aData = array('cats' => $aData, 'step' => 1);

                            return $this->viewPHP($aData, 'index.cats.phone');
                        }
                    }
                }
            }
            break;
            case 'search': # фильтр категории
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
                            'id',
                            'pid',
                            'numlevel',
                            'numleft',
                            'numright',
                            'title',
                            'keyword',
                            'icon_' . $ICON_BIG . ' as icon',
                            'items',
                            'subs'
                        );
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aData)) {
                        foreach ($aData as &$v) {
                            $v['l'] = $sListingUrl . $v['k'] . '/';
                            $v['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            $v['active'] = ($v['id'] == $nSelectedID);
                        }
                        unset($v);
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = $sListingUrl . $aParent['keyword'] . '/';
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем настройки основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
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
                        $nTotal = config::get('bbs_items_total_publicated', 0);
                        $aData = array('cats' => $aData, 'total' => $nTotal, 'step' => 1);

                        return $this->viewPHP($aData, 'search.cats.desktop');
                    }
                } else if ($mDevice == bff::DEVICE_PHONE) {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
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
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aData = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                    if (!empty($aData)) {
                        foreach ($aData as $k => $v) {
                            $aData[$k]['l'] = $sListingUrl . $v['k'] . '/';
                            $aData[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            $aData[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['link'] = $sListingUrl . $aParent['keyword'] . '/';
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aData, 'parent' => $aParent, 'step' => 2);
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
            break;
            case 'form': # форма объявления: выбор категории
            {
                if ($mDevice == bff::DEVICE_DESKTOP) # (desktop+tablet)
                {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
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
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_BIG);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_BIG);
                            $aCats[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_BIG);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_BIG . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_BIG);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2, 'showAll' => $showAll);
                            $aData = $this->viewPHP($aData, 'item.form.cat.desktop');
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
                        $aData = array('cats' => $aCats, 'step' => 1, 'showAll' => $showAll);
                        return $this->viewPHP($aData, 'item.form.cat.desktop');
                    }
                } else if ($mDevice == bff::DEVICE_PHONE) {
                    $nSelectedID = 0;
                    if ($nParentID > self::CATS_ROOTID) {
                        $aParentData = array(
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
                        $aParent = $this->model->catData($nParentID, $aParentData);
                        if (!empty($aParent)) {
                            if (!$aParent['subs']) {
                                # в данной категории нет подкатегорий
                                # формируем список подкатегорий ее parent-категории
                                $aParent = $this->model->catData($aParent['pid'], $aParentData);
                                if (!empty($aParent)) {
                                    $nSelectedID = $nParentID;
                                    $nParentID = $aParent['id'];
                                }
                            }
                        }
                    }
                    $aCats = $this->model->catsList($sType, $mDevice, $nParentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                            $aCats[$k]['active'] = ($v['id'] == $nSelectedID);
                        }
                    }
                    if ($nParentID > self::CATS_ROOTID) {
                        if (!empty($aParent)) {
                            $aParent['main'] = ($aParent['pid'] == self::CATS_ROOTID);
                            if ($aParent['main']) {
                                $aParent['icon'] = $oIcon->url($aParent['id'], $aParent['icon'], $ICON_SMALL);
                            } else {
                                # глубже второго уровня, получаем иконку основной категории
                                $aParentsID = $this->model->catParentsID($aParent, false);
                                if (!empty($aParentsID[1])) {
                                    $aParentMain = $this->model->catData($aParentsID[1], array(
                                            'id',
                                            'icon_' . $ICON_SMALL . ' as icon'
                                        )
                                    );
                                    $aParent['icon'] = $oIcon->url($aParentsID[1], $aParentMain['icon'], $ICON_SMALL);
                                }
                            }
                            $aData = array('cats' => $aCats, 'parent' => $aParent, 'step' => 2, 'showAll' => $showAll);
                            $aData = $this->viewPHP($aData, 'item.form.cat.phone');
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
                        $aData = array('cats' => $aCats, 'step' => 1, 'showAll' => $showAll);
                        return $this->viewPHP($aData, 'item.form.cat.phone');
                    }
                } else if ($mDevice == 'init') {
                    /**
                     * Формирование данных об основных категориях
                     * для jForm.init({catsMain:DATA});
                     */
                    $aCats = $this->model->catsList('form', bff::DEVICE_PHONE, $nParentID, $ICON_SMALL);
                    if (!empty($aCats)) {
                        foreach ($aCats as $k => $v) {
                            $aCats[$k]['i'] = $oIcon->url($v['id'], $v['i'], $ICON_SMALL);
                        }
                    }

                    return $aCats;
                }
            }
            break;
        }
    }

    /**
     * Поиск и результаты поиска
     */
    public function search()
    {
        $nPerpage = config::sys('bbs.search.pagesize', 12);
        $f = $this->searchFormData();
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

        # SEO данные
        $seoKey = '';
        $seoNoIndex = false;
        $seoData = array(
            'page'   => & $f['page'],
            'region' => Geo::regionTitle(($f_region ? $f_region : Geo::defaultCountry())),
        );

        # формируем данные о текущей категории:
        $catID = 0;
        $catData = array();
        $catFields = array(
            'id',
            'numlevel',
            'seek',
            'addr',
            'addr_metro',
            'keyword',
            'owner_business',
            'owner_search',
            'price',
            'price_sett',
            'photos',
            'enabled',
            'subs_filter_title',
        );
        if (!Request::isAJAX()) {
            $catKey = $this->input->get('cat', TYPE_STR);
            $catKey = trim($catKey, ' /\\');
            if (!empty($catKey)) {
                $catData = $this->model->catDataByFilter(array('keyword' => $catKey), array_merge($catFields, array(
                            'pid',
                            'subs',
                            'numleft',
                            'numright',
                            'numlevel',
                            'enabled',
                            'title',
                            'mtitle',
                            'mkeywords',
                            'mdescription',
                            'mtemplate',
                            'seotext',
                            'titleh1',
                            'type_offer_search',
                            'type_seek_search',
                            'owner_private_search',
                            'owner_business_search',
                            'list_type',
                        )
                    )
                );

                if (empty($catData) || !$catData['enabled']) $this->errors->error404();

                # категории в фильтре:
                if (DEVICE_DESKTOP_OR_TABLET) {
                    $filterLevel = self::catsFilterLevel();
                    # корректируем данные выпадающего списка - выбранной категории
                    $dropdown = array('id' => $catData['id'], 'title' => $catData['title']);
                    if ($catData['numlevel'] > $filterLevel) {
                        $parentData = $this->catsFilterParent($catData);
                        $dropdown['id'] = $parentData['pid'];
                        $dropdown['title'] = $parentData['title'];
                    } else if ($catData['numlevel'] == $filterLevel) {
                        $dropdown['id'] = $catData['pid'];
                    }
                    $catData['dropdown'] = $dropdown;
                    # формируем данные для фильтров подкатегорий - выбранной категории
                    if ($catData['numlevel'] >= $filterLevel) {
                        $catData['subs_filter'] = $this->catsFilterData($catData);
                    }
                }

                bff::filter('bbs-search-category', $catData);
                $catID = $f_c = $catData['id'];

                # хлебные крошки
                $catData['crumbs'] = $this->categoryCrumbs($catID, __FUNCTION__);

                # типы категорий
                if (self::CATS_TYPES_EX) {
                    $catData['types'] = $this->model->cattypesByCategory($catID);
                    if (!empty($catData['types'])) {
                        if (!isset($catData['types'][$f_ct])) $f_ct = key($catData['types']);
                        foreach ($catData['types'] as &$v) {
                            if ($v['items'] >= 1000) $v['items'] = number_format($v['items'], 0, '', ' ');
                        }
                        unset($v);
                    }
                } else {
                    $catData['types'] = $this->model->cattypesSimple($catData, true);
                }

                # корректируем тип списка
                if (!$catData['addr'] && $f_lt == self::LIST_TYPE_MAP) {
                    $f_lt = self::LIST_TYPE_LIST;
                }

                # SEO: Поиск в категории
                $seoKey = 'search-category';
                $metaCategories = array();
                foreach ($catData['crumbs'] as $k => &$v) {
                    if ($k) $metaCategories[] = $v['title'];
                }
                unset($v);
                $seoData['category'] = $catData['title'];
                $seoData['categories'] = join(', ', $metaCategories);
                $seoData['categories.reverse'] = join(', ', array_reverse($metaCategories, true));
            } else {
                # SEO: Поиск (все категории)
                $seoKey = 'search';
            }

            # тип списка по-умолчанию
            if (!$f_lt) {
                if ( ! empty($catData['list_type'])) {
                    $f_lt = $catData['list_type'];
                } else {
                    $listType = config::sys('bbs.search.list.type', 0);
                    if ($listType > 0) {
                        $f_lt = $listType;
                    }
                }
            }
        } else {
            $catID = $f_c;
            $catData = $this->model->catData($catID, $catFields);
            if (empty($catData) || !$catData['enabled']) $catID = 0;
        }
        if (!$catID) {
            $f_c = $f_ct = 0;
            $catData = array('id' => 0, 'addr' => 0, 'seek' => false, 'price' => false, 'keyword' => '');
            if (!Request::isAJAX()) {
                $catData['crumbs'] = $this->categoryCrumbs(0, __FUNCTION__);
            }
        }

        # Формируем запрос поиска:
        $sqlTablePrefix = 'I.';
        $sql = array(
            'status' => self::STATUS_PUBLICATED,
        );
        if (static::premoderation()) {
            $sql[':mod'] = $sqlTablePrefix . 'moderated > 0';
        }
        if ($f_c > 0) {
            $sql['cat_id' . $catData['numlevel']] = $f_c;
        }
        if (self::CATS_TYPES_EX) {
            if ($f_ct > 0) $sql['cat_type'] = $f_ct;
        } else {
            if ($catData['seek'] > 0) $sql['cat_type'] = $f_ct;
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
            $sql[] = array($sqlTablePrefix . 'title LIKE (:query)', ':query' => "%$f_q%");
        }
        if ($f_lt == self::LIST_TYPE_MAP) {
            # на карту выводим только с корректно указанными координатами
            $sql[':addr'] = $sqlTablePrefix . 'addr_lat!=0';
            $seoResetCounter++;
        }

        switch (bff::device()) {
            case bff::DEVICE_DESKTOP:
            case bff::DEVICE_TABLET:
            {
                # дин. свойства:
                if ($catID > 0) {
                    $dp = $this->dp()->prepareSearchQuery($f['d'], $f['dc'], $this->dpSettings($catID), $sqlTablePrefix);
                    if (!empty($dp)) $sql[':dp'] = $dp;
                }
                # с фото:
                if ($f['ph']) $sql[] = 'imgcnt > 0';
                # тип владельца:
                if (!empty($f['ow']) && $catData['owner_search']) $sql['owner_type'] = $f['ow'];
                # цена:
                if ($catID > 0 && $catData['price']) {
                    $priceQuery = $this->model->preparePriceQuery($f['p'], $catData, $sqlTablePrefix);
                    if (!empty($priceQuery)) $sql[':price'] = $priceQuery;
                }
                # район:
                if (!empty($f['rd'])) {
                    $sql['district_id'] = $f['rd'];
                }
                # метро:
                if (!empty($f['rm'])) {
                    $sql['metro_id'] = $f['rm'];
                }
            }
            break;
            case bff::DEVICE_PHONE:
            {
                # дин. свойства:
                if ($catID > 0) {
                    $dp = $this->dp()->prepareSearchQuery($f['md'], $f['mdc'], $this->dpSettings($catID), $sqlTablePrefix);
                    if (!empty($dp)) $sql[':dp'] = $dp;
                }
                # с фото:
                if ($f['mph']) $sql[] = 'imgcnt > 0';
                # тип владельца:
                if (!empty($f['mow']) && $catData['owner_search']) $sql['owner_type'] = $f['mow'];
                # цена:
                if ($catID > 0 && $catData['price']) {
                    $priceQuery = $this->model->preparePriceQuery(array('r' => array($f['mp'])), $catData, $sqlTablePrefix);
                    if (!empty($priceQuery)) $sql[':price'] = $priceQuery;
                }
                # район:
                if ($f['mrd']) {
                    $sql['district_id'] = $f['mrd'];
                }
            }
            break;
        }

        # Выполняем поиск ОБ:
        $sqlOrderBy = 'svc_fixed DESC, ' . $sqlTablePrefix . 'svc_fixed_order DESC, ' . $sqlTablePrefix . 'publicated_order DESC';
        switch ($f_sort) {
            case 'price-desc':
                $sqlOrderBy = $sqlTablePrefix . 'price_search DESC';
                $seoNoIndex = true;
                break;
            case 'price-asc':
                $sqlOrderBy = $sqlTablePrefix . 'price_search ASC';
                $seoNoIndex = true;
                break;
        }
        $aData = array('items' => array(), 'pgn' => '');

        $nTotal = $this->model->itemsList($sql, true);
        if ($nTotal > 0) {
            # pagination links
            $aPgnLinkQuery = $f;
            if ($f['c']) unset($aPgnLinkQuery['c']);
            if ($f['region']) unset($aPgnLinkQuery['region']);
            if ($f['lt'] == self::LIST_TYPE_LIST) unset($aPgnLinkQuery['lt']);
            if ($f['sort'] == 'new') unset($aPgnLinkQuery['sort']);
            $nPgnPrice = 0;
            if (!empty($f['p'])) foreach ($f['p'] as &$v) {
                if (!empty($v)) $nPgnPrice++;
            }
            unset($v);
            if (!$nPgnPrice) unset($aPgnLinkQuery['p']);
            $oPgn = new Pagination($nTotal, $nPerpage, array(
                'link' => static::url('items.search', array('keyword' => $catData['keyword'])),
                'query' => $aPgnLinkQuery,
            ));
            # list
            $bUseCategoryCurrency = (bool)config::sys('bbs.search.category.currency', TYPE_BOOL);
            if ($bUseCategoryCurrency && empty($catData['price_sett']['curr'])) {
                $catData['price_sett']['curr'] = Site::currencyDefault('id');
            }
            $aData['items'] = $this->model->itemsList($sql, false, $oPgn->getLimitOffset(), $sqlOrderBy, ($bUseCategoryCurrency ? $catData['price_sett']['curr'] : 0));
            $aData['pgn'] = $oPgn->view();
            $f['page'] = $oPgn->getCurrentPage();
        }

        $nNumStart = ($f_page <= 1 ? 1 : (($f_page - 1) * $nPerpage) + 1);
        if (Request::isAJAX()) { # ajax ответ
            $this->ajaxResponseForm(array(
                    'list'  => $this->searchList(bff::device(), $f_lt, $aData['items'], $nNumStart),
                    'items' => &$aData['items'],
                    'pgn'   => $aData['pgn'],
                    'total' => $nTotal,
                )
            );
        }

        # SEO
        $this->seo()->robotsIndex(!(sizeof($sql) - $seoResetCounter) && !$seoNoIndex);
        $this->seo()->canonicalUrl(static::url('items.search', array('keyword' => $catData['keyword']), true),
            array('page' => $f['page'], 'ct' => $f_ct)
        );
        # подготавливаем хлебные крошки для подстановки макросов
        $catData['crumbs_macros'] = array();
        foreach ($catData['crumbs'] as &$v) { $catData['crumbs_macros'][] = &$v['breadcrumb']; } unset($v);
        $this->setMeta($seoKey, $seoData, $catData, array(
                'titleh1' => array('ignore' => array((!$f_region ? 'region' : ''),)),
                'crumbs_macros' => array('ignore' => array((!$f_region ? 'region' : ''),'category','city')),
            )
        );

        $aData['total'] = $nTotal;
        $aData['num_start'] = $nNumStart;
        $aData['cat'] = & $catData;
        $aData['f'] = & $f;
        return $this->viewPHP($aData, 'search');
    }

    /**
     * Форма поиска
     */
    public function searchForm()
    {
        $aData['f'] = $this->searchFormData();
        $aData['f']['seek'] = (!self::CATS_TYPES_EX && $aData['f']['ct'] == self::TYPE_SEEK);

        if (DEVICE_PHONE) {
            # определяем наличие отмеченных фильтров
            $f = & $aData['f'];
            $aData['f_filter_active'] = (
                !empty($f['d']) /* дин. св-ва */ || !empty($f['mp']) /* цена */ ||
                !empty($f['mph']) /* фото */ || !empty($f['mow']) /* тип владельца */
            );
        }

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
            'ct'   => TYPE_UINT, # тип категории (продам, куплю, ...)
            'q'    => TYPE_NOTAGS, # поисковая строка
            'lt'   => TYPE_UINT, # тип списка (self::LIST_TYPE_)
            'sort' => TYPE_NOTAGS, # сортировка
            'cnt'  => TYPE_BOOL, # только кол-во
            'page' => TYPE_UINT, # страница
        );
        if (DEVICE_DESKTOP_OR_TABLET) {
            $aParams += array(
                'd'  => TYPE_ARRAY, # дин. свойства
                'dc' => TYPE_ARRAY, # дин. свойства (child)
                'p'  => array(
                    TYPE_ARRAY, # цена
                    'f' => TYPE_PRICE, # от
                    't' => TYPE_PRICE, # до
                    'c' => TYPE_UINT, # ID валюты для "от-до"
                    'r' => TYPE_ARRAY_UINT, # диапазоны
                ),
                'rd' => TYPE_ARRAY_UINT, # район
                'rm' => TYPE_ARRAY_UINT, # метро
                'ph' => TYPE_BOOL, # с фото
                'ow' => TYPE_ARRAY_UINT, # тип владельца
            );
        }
        if (DEVICE_PHONE) {
            $aParams += array(
                'mq'  => TYPE_NOTAGS, # поисковая строка
                'md'  => TYPE_ARRAY, # дин. свойства
                'mdc' => TYPE_ARRAY, # дин. свойства (child)
                'mp'  => TYPE_UINT, # цена (только ID диапазона или 0)
                'mph' => TYPE_BOOL, # с фото
                'mow' => TYPE_ARRAY_UINT, # тип владельца
                'mrd' => TYPE_UINT, # район
            );
        }

        $data = $this->input->postgetm($aParams);

        # поисковая строка
        $device = bff::device();
        $data['q'] = $this->input->cleanSearchString(
            (in_array($device, array(
                    bff::DEVICE_DESKTOP,
                    bff::DEVICE_TABLET
                )
            ) ? $data['q'] : (isset($data['mq']) ? $data['mq'] : '')), 80
        );
        # страница
        if (!$data['page']) $data['page'] = 1;
        # регион
        $data['region'] = Geo::filter('id'); # user

        return $data;
    }

    /**
     * Формирование результатов поиска (список ОБ)
     * @param string $mDeviceID тип устройства
     * @param integer $nListType тип списка (self::LIST_TYPE_)
     * @param array $aItems @ref данные о найденных ОБ
     * @param integer $nNumStart изначальный порядковый номер
     * @return mixed
     */
    public function searchList($mDeviceID, $nListType, array &$aItems, $nNumStart = 1)
    {
        static $prepared = false;
        if (!$prepared) {
            $prepared = true;
            $this->itemsListPrepare($aItems, $nListType, $nNumStart);
        }
        if (empty($mDeviceID)) $mDeviceID = bff::device();

        if (empty($aItems)) {
            return $this->showInlineMessage(array(
                    '<br />',
                    _t('bbs', 'Объявлений по вашему запросу не найдено')
                )
            );
        }

        $aTemplates = array(
            bff::DEVICE_DESKTOP => 'search.list.desktop',
            bff::DEVICE_TABLET  => 'search.list.desktop',
            bff::DEVICE_PHONE   => 'search.list.phone',
        );
        $aData = array();
        $aData['items'] = &$aItems;
        $aData['list_type'] = $nListType;
        return $this->viewPHP($aData, $aTemplates[$mDeviceID]);
    }

    /**
     * Быстрый поиск ОБ по строке
     * @param post ::string 'q' - строка поиска
     */
    public function searchQuick()
    {
        $nLimit = config::sys('bbs.search.quick.limit', 3);
        $sQuery = $this->input->post('q', TYPE_NOTAGS, array('len' => 80));
        $sQuery = $this->input->cleanSearchString($sQuery, 80);
        $f = $this->input->postm(array(
                'c'  => TYPE_UINT, # категория
                'ct' => TYPE_UINT, # тип категории
            )
        );
        $nRegionID = Geo::filter('id'); # user
        $aData = array();

        if (config::sys('sphinx.enabled')) {
            # поиск Sphinx
            $sphinx = $this->itemsSearchSphinx();
            $sphinx->sphinx->SetFilter('status', array(self::STATUS_PUBLICATED));
            if (static::premoderation()) {
                $sphinx->sphinx->SetFilter('moderated', array(1, 2));
            }
            if ($nRegionID > 0) {
                if (Geo::isCity($nRegionID)) {
                    $sphinx->sphinx->SetFilter('reg3_city', array($nRegionID));
                } else {
                    $sphinx->sphinx->SetFilter('reg2_region', array($nRegionID));
                }
            }
            $aSearchResult = $sphinx->searchItems($sQuery, false, 0, false, $nLimit);
            if (empty($aSearchResult) || empty($aSearchResult['count'])) {
                # ничего не нашли
                $aData['items'] = array();
            } else {
                # нашли, получаем данные по ID (сортируем в том порядке, в котором вернул Sphinx)
                $sFoundItemsID = join(',', $aSearchResult['id']);
                $sql = array('I.id IN (' . $sFoundItemsID . ')');
                $aData['items'] = $this->model->itemsQuickSearch($sql, false, '', 'FIELD(I.id, ' . $sFoundItemsID . ')'); # MySQL only
            }
        } else {
            # поиск MySQL
            $sql = array('status' => self::STATUS_PUBLICATED);
            if (static::premoderation()) {
                $sql[':mod'] = 'I.moderated > 0';
            }
            if ($nRegionID > 0) {
                if (Geo::isCity($nRegionID)) {
                    $sql[] = array('reg3_city = :city', ':city' => $nRegionID);
                } else {
                    $sql[] = array('reg2_region = :region', ':region' => $nRegionID);
                }
            }
            $sql[':Q'] = array('I.title LIKE :Q', ':Q' => '%' . $sQuery . '%');
            # сортируем в порядке публикации
            $aData['items'] = $this->model->itemsQuickSearch($sql, false, $this->db->prepareLimit(0, $nLimit), 'I.publicated_order DESC');
        }

        $aData['cnt'] = sizeof($aData['items']);
        foreach ($aData['items'] as &$v) {
            if (sizeof($v['img']) > 4) $v['img'] = array_slice($v['img'], 0, 4);
        }
        unset($v);
        $aData['items'] = $this->viewPHP($aData, 'search.quick');
        $this->ajaxResponseForm($aData);
    }

    /**
     * Просмотр ОБ
     * @param getpost ::uint 'id' - ID объявления
     * @param get ::string 'from' - откуда выполняется переход на страницу просмотра (add,edit,adm)
     */
    public function view()
    {
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        $nUserID = User::id();

        if (Request::isPOST()) {
            $aResponse = array();
            switch ($this->input->getpost('act', TYPE_STR)) {
                case 'contact-form': # Отправка сообщения из формы "Свяжитесь с автором"
                {
                    Users::i()->writeFormSubmit($nUserID, 0, $nItemID, true, -1);
                }
                break;
                case 'claim': # Пожаловаться
                {
                    if (!$nItemID || !$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $nReason = $this->input->post('reason', TYPE_ARRAY_UINT);
                    $nReason = array_sum($nReason);
                    $sMessage = $this->input->post('comment', TYPE_STR);
                    $sMessage = $this->input->cleanTextPlain($sMessage, 1000, false);

                    if (!$nReason) {
                        $this->errors->set(_t('item-claim', 'Укажите причину'));
                        break;
                    } else if ($nReason & self::CLAIM_OTHER) {
                        if (mb_strlen($sMessage) < 10) {
                            $this->errors->set(_t('item-claim', 'Опишите причину подробнее'), 'comment');
                            break;
                        }
                    }

                    if (!$nUserID) {
                        $aResponse['captcha'] = false;
                        if (!CCaptchaProtection::correct($this->input->cookie('c2'), $this->input->post('captcha', TYPE_STR))) {
                            $aResponse['captcha'] = true;
                            $this->errors->set(_t('', 'Результат с картинки указан некорректно'), 'captcha');
                            break;
                        }
                    } else {
                        # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                        if (Site::i()->preventSpam('bbs-claim')) {
                            break;
                        }
                    }

                    $nClaimID = $this->model->claimSave(0, array(
                            'reason'  => $nReason,
                            'message' => $sMessage,
                            'item_id' => $nItemID,
                        )
                    );

                    if ($nClaimID > 0) {
                        $this->claimsCounterUpdate(1);
                        $this->model->itemSave($nItemID, array(
                                'claims_cnt = claims_cnt + 1'
                            )
                        );
                        if (!$nUserID) {
                            Request::deleteCOOKIE('c2');
                        }
                    }
                }
                break;
                case 'sendfriend': # Поделиться с другом
                {
                    $aResponse['later'] = false;
                    if (!$nItemID || !$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 150));
                    if (!$this->input->isEmail($sEmail, false)) {
                        $this->errors->set(_t('', 'E-mail адрес указан некорректно'), 'email');
                        break;
                    }

                    $aData = $this->model->itemData($nItemID, array('id', 'title', 'link'));
                    if (empty($aData)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('bbs-sendfriend')) {
                        $aResponse['later'] = true;
                        break;
                    }

                    bff::sendMailTemplate(array(
                            'item_id'    => $nItemID,
                            'item_title' => $aData['title'],
                            'item_link'  => $aData['link'],
                        ), 'bbs_item_sendfriend', $sEmail
                    );
                }
                break;
                case 'views-stat': # График статистики просмотров ОБ
                {
                    if (!$nItemID || !$this->security->validateReferer()) {
                        $this->errors->reloadPage();
                        break;
                    }

                    # получаем данные
                    $aStat = $this->model->itemViewsData($nItemID);

                    if (($aResponse['empty'] = empty($aStat['data']))) {
                        $this->errors->set(_t('bbs', 'Статистика просмотров для данного объявления отсутствует'));
                        break;
                    }

                    $aStat['promote_url'] = static::url('item.promote', array('id' => $nItemID, 'from' => 'view'));
                    $aResponse['popup'] = $this->viewPHP($aStat, 'item.view.statistic');
                    $aResponse['stat'] = & $aStat;
                    $aResponse['lang'] = array(
                        'y_title'        => _t('view', 'Количество просмотров'),
                        'total'          => _t('view', 'Всего'),
                        'item_views'     => _t('view', 'Просмотры объявления'),
                        'contacts_views' => _t('view', 'Просмотры контактов'),
                        'months'         => explode(',', _t('view', 'Января,Февраля,Марта,Апреля,Мая,Июня,Июля,Августа,Сентября,Октября,Ноября,Декабря')),
                        'shortMonths'    => explode(',', _t('view', 'янв,фев,мар,апр,май,июн,июл,авг,сен,окт,ноя,дек')),
                        //'weekdays' => explode(',', _t('view', 'Понедельник,Вторник,Среда,Четверг,Пятница,Суббота,Воскресенье')),
                    );
                }
                break;
                default:
                {
                    $this->errors->reloadPage();
                }
                break;
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = $this->model->itemDataView($nItemID);
        if (empty($aData)) $this->errors->error404();

        # Авторизуем пользователя в случае если был выполнен переход по ссылке с ключем ?auth=X
        if (!$nUserID) {
            $nUserID = $this->userAuthGetParam($aData['user_id']);
        }

        # SEO: корректируем ссылку
        $this->urlCorrection(static::urlDynamic($aData['link']));

        $nCatID = $aData['cat_id'];
        $aUrlOptions = array('region' => $aData['region'], 'city' => $aData['city']);
        $aData['list_url'] = static::url('items.search', $aUrlOptions);
        $aData['cats'] = $this->categoryCrumbs($nCatID, __FUNCTION__, $aUrlOptions);

        # владелец
        $aData['owner'] = $this->isItemOwner($nItemID, $aData['user_id']);

        # модерация объявления
        $aData['moderation'] = $moderation = static::moderationUrlKey($nItemID, $this->input->get('mod', TYPE_STR));

        # проверяем статус ОБ
        if ($aData['status'] != self::STATUS_PUBLICATED) {
            if ($aData['deleted']) {
                if (!empty($aData['cats'])) {
                    # возвращаем на список объявлений категории (в которой находилось данное объявление до удаления)
                    foreach (array_reverse($aData['cats']) as $v) {
                        if ($v['id']) $this->redirect($v['link']);
                    }
                    $this->redirect($aData['list_url']);
                }

                return $this->showForbidden(_t('view', 'Просмотр объявления'), _t('view', 'Объявление было удалено либо заблокировано модератором'));
            }
            if (!$moderation) {
                if ($aData['status'] == self::STATUS_BLOCKED && !$aData['owner']) {
                    return $this->showForbidden(
                        _t('view', 'Объявление заблокировано'),
                        _t('view', 'Причина блокировки:<br />[reason]', array('reason' => nl2br($aData['blocked_reason'])))
                    );
                }
                if ($aData['status'] == self::STATUS_NOTACTIVATED) {
                    return $this->showSuccess(_t('view', 'Просмотр объявления'), _t('view', 'Объявление еще неактивировано пользователем'));
                }
                if ($aData['moderated'] && $aData['status'] != self::STATUS_PUBLICATED_OUT && $aData['status'] != self::STATUS_BLOCKED && !$aData['owner']) {
                    return $this->showForbidden(
                        _t('view', 'Данное объявление находится на модерации'),
                        _t('view', 'После проверки оно будет вновь опубликовано')
                    );
                }
            }
            # self::STATUS_PUBLICATED_OUT => отображаем снятые с публикации
        } else if (!$aData['moderated'] && static::premoderation() && !$moderation && !$aData['owner']) {
            return $this->showForbidden(
                _t('view', 'Данное объявление находится на модерации'),
                _t('view', 'После проверки оно будет опубликовано')
            );
        }

        # информация о владельце
        $aData['user'] = Users::model()->userDataSidebar($aData['user_id']);

        # информация о магазине
        if ($aData['shop_id'] && $aData['user']['shop_id'] > 0 && bff::shopsEnabled()) {
            $aData['shop'] = Shops::model()->shopDataSidebar($aData['user']['shop_id']);
            if ($aData['shop']) {
                $aData['name'] = $aData['shop']['title'];
            }
        }
        $aData['is_shop'] = ($aData['shop_id'] && !empty($aData['shop']));

        # подставляем контактные данные из профиля
        if ($this->getItemContactsFromProfile())
        {
            if ($aData['is_shop']) {
                $contactsData = &$aData['shop'];
            } else {
                $contactsData = &$aData['user'];
                $aData['name'] = $contactsData['name'];
            }
            $contacts = array(
                'phones' => array(),
                'skype'  => (!empty($contactsData['skype']) ? mb_substr($contactsData['skype'], 0, 2) . 'xxxxx' : ''),
                'icq'    => (!empty($contactsData['icq']) ? mb_substr($contactsData['icq'], 0, 2) . 'xxxxx' : ''),
            );
            foreach ($contactsData['phones'] as $v) $contacts['phones'][] = $v['m'];
            $contacts['has'] = ($contacts['phones'] || $contacts['skype'] || $contacts['icq']);
            $aData['contacts'] = &$contacts; unset($contactsData);
        }

        # изображения
        $oImages = $this->itemImages($nItemID);
        $aData['images'] = $oImages->getData($aData['imgcnt']);
        if (!empty($aData['images'])) {
            $aData['image_view'] = $oImages->getURL(reset($aData['images']), BBSItemImages::szView);
            $lngPhoto = _t('view', 'изображение');
            $i = 1;
            foreach ($aData['images'] as &$v) {
                $v['t'] = $aData['title'] . ' - ' . $lngPhoto . ' ' . $i++;
                $v['url_small'] = $oImages->getURL($v, BBSItemImages::szSmall);
                $v['url_view'] = $oImages->getURL($v, BBSItemImages::szView);
                $v['url_zoom'] = $oImages->getURL($v, BBSItemImages::szZoom);
            }
            unset($v);
        } else {
            $aData['image_view'] = $oImages->urlDefault(BBSItemImages::szView);
        }

        # дин. свойства
        $aData['dynprops'] = $this->dpView($nCatID, $aData);

        # версия для печати
        if ($this->input->get('print', TYPE_BOOL) && $aData['status'] == self::STATUS_PUBLICATED) {
            View::setLayout('print');
            $this->seo()->robotsIndex(false);

            return $this->viewPHP($aData, 'item.view.print');
        }

        # комментарии
        $aData['comments'] = '';
        if (static::commentsEnabled()) {
            $aData['comments'] = $this->comments(array(
                'itemID' => $nItemID,
                'itemUserID' => $aData['user_id'],
                'itemStatus' => $aData['status'],
            ));
        }

        # похожие
        if ($aData['status'] == self::STATUS_PUBLICATED && !(!$aData['moderated'] && static::premoderation()) && ! $moderation)
        {
            $i = sizeof($aData['cats']);
            $j = $i;
            foreach (array_reverse($aData['cats']) as $v) {
                $aSimilarFilter = array();
                if (static::premoderation()) {
                    $aSimilarFilter[] = 'moderated > 0';
                }
                $aSimilarFilter[($i == $j ? 'cat_id' : 'cat_id' . $i)] = $v['id'];
                $aSimilarFilter['status'] = self::STATUS_PUBLICATED;
                $aSimilarFilter[] = 'id!=' . $nItemID;
                $aData['similar'] = $this->model->itemsList($aSimilarFilter, false, $this->db->prepareLimit(0, config::sys('bbs.view.similar.limit', 3)), 'publicated_order DESC'
                );
                if (!empty($aData['similar'])) {
                    $this->itemsListPrepare($aData['similar'], self::LIST_TYPE_LIST);
                    break;
                }
                $i--;
            } unset($i, $j);
            $aData['similar'] = $this->viewPHP($aData['similar'], 'item.view.similar');
        } else {
            $aData['similar'] = '';
        }

        # избранное ОБ
        $aData['fav'] = $this->isFavorite($nItemID, $nUserID);

        # откуда пришли
        $aData['from'] = $this->input->get('from', TYPE_STR);

        # накручиваем счетчик просмотров если:
        # - не владелец
        # - не переход из админ панели
        # - не перешли с этой же страницы
        if (!$aData['owner'] && $aData['from'] != 'adm') {
            $sReferer = Request::referer();
            if (empty($sReferer) || mb_strpos($sReferer, '-' . $nItemID . '.html') === false) {
                if ($this->model->itemViewsIncrement($nItemID, 'item', $aData['views_today'])) {
                    $aData['views_total']++;
                    $aData['views_today']++;
                }
            }
        }

        # SEO: Просмотр объявления
        $this->seo()->canonicalUrl($aData['link']);
        $metaCategories = array(); $aData['cats_macros'] = array();
        foreach ($aData['cats'] as $k => &$v) {
            if ($k) $metaCategories[] = $v['title'];
            $aData['cats_macros'][] = &$v['breadcrumb'];
        } unset($v);
        $this->setMeta('view', array(
                'title'              => $aData['title'],
                'description'        => tpl::truncate($aData['descr'], 150),
                'price'              => ($aData['price_on'] ? $aData['price'] . (!empty($aData['price_mod']) ? ' ' . $aData['price_mod'] : '') : ''),
                'city'               => $aData['city_title'],
                'region'             => $aData['region_title'],
                'country'            => $aData['country_title'],
                'category'           => $aData['cat_title'],
                'categories'         => join(', ', $metaCategories),
                'categories.reverse' => join(', ', array_reverse($metaCategories, true)),
            ), $aData, array(
                'cats_macros'=>array('replace'=>array('region'=>'city'), 'ignore'=>array('category')),
            )
        );
        $seoSocialImages = array();
        foreach ($aData['images'] as &$v) {
            $seoSocialImages[] = $v['url_view'];
        }
        unset($v);
        $this->seo()->setSocialMetaOG($aData['share_title'], $aData['share_description'], $seoSocialImages, $aData['link'], $aData['share_sitename']);

        # promote
        $aData['promote_url'] = static::url('item.promote', array('id' => $nItemID, 'from' => 'view'));

        # код "Поделиться"
        $aData['share_code'] = config::get('bbs_item_share_code');

        return $this->viewPHP($aData, 'item.view');
    }

    /**
     * Добавление ОБ
     * @param get ::uint 'cat' - ID категории по-умолчанию
     */
    public function add()
    {
        if (!empty($_GET['success']))
        {
            return $this->itemStatus('new', $this->input->get('id', TYPE_UINT));
        }

        $this->security->setTokenPrefix('bbs-item-form');
        $nItemID = 0;
        $nShopID = User::shopID();
        $nUserID = User::id();
        $registerPhone = Users::registerPhone();
        $publisherOnlyShop = static::publisher(static::PUBLISHER_SHOP) ||
            (static::publisher(static::PUBLISHER_USER_TO_SHOP) && $nShopID);

        $this->validateItemData($aData, 0);

        if (Request::isPOST()) {
            $aResponse = array('id' => 0);
            $bNeedActivation = false;
            $users = Users::i();

            do {

                if ( ! $this->errors->no()) {
                    break;
                }

                # проверка токена(для авторизованных) + реферера
                if (!$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                # антиспам фильтр: минус слова
                if ($this->spamMinusWordsFound($aData['title'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами заголовке присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }
                if ($this->spamMinusWordsFound($aData['descr'], $sWord)) {
                    $this->errors->set(_t('bbs', 'В указанном вами описании присутствует запрещенное слово "[word]"', array('word' => $sWord)));
                    break;
                }

                if (!$nUserID) {
                    # проверяем IP для неавторизованных
                    $mBanned = $users->checkBan(true);
                    if ($mBanned) {
                        $this->errors->set(_t('users', 'В доступе отказано по причине: [reason]', array('reason' => $mBanned)));
                        break;
                    }
                    # проверка доступности публикации от "магазина"
                    if ($publisherOnlyShop) {
                        $this->errors->reloadPage();
                        break;
                    }
                    # номер телефона
                    if ($registerPhone) {
                        $phone = $this->input->post('phone', TYPE_NOTAGS, array('len' => 30));
                        if (!$this->input->isPhoneNumber($phone)) {
                            $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                            break;
                        }
                    }
                    # регистрируем нового или задействуем существующего пользователя
                    $sEmail = $this->input->post('email', TYPE_NOTAGS, array('len' => 100)); # E-mail
                    if (!$this->input->isEmail($sEmail)) {
                        $this->errors->set(_t('users', 'E-mail адрес указан некорректно'));
                        break;
                    }
                    $aUserData = $users->model->userDataByFilter(($registerPhone ?
                        array('phone_number'=>$phone) :
                        array('email' => $sEmail)),
                        array('user_id', 'email', 'shop_id', 'activated', 'activate_key',
                              'phone_number', 'phone_number_verified', 'blocked', 'blocked_reason')
                    );
                    if (empty($aUserData)) {
                        # проверяем уникальность email адреса
                        if ($registerPhone && $users->model->userEmailExists($sEmail, $aUserData['user_id'])) {
                            $this->errors->set(_t('users', 'Пользователь с таким e-mail адресом уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                                    array('link_forgot' => 'href="' . Users::url('forgot') . '"')
                                ), 'email'
                            );
                            break;
                        }
                        # регистрируем нового пользователя
                        # подставляем данные в профиль из объявления
                        $aRegisterData = array('email'=>$sEmail,'phone'=>'');
                        if ($registerPhone) $aRegisterData['phone_number'] = $phone;
                        foreach (array('name','skype','icq','city_id'=>'region_id') as $k=>$v) {
                            if (is_int($k)) $k = $v;
                            if ( ! empty($aData[$k])) $aRegisterData[$v] = $aData[$k];
                        }
                        # сохраняем первый телефон в отдельное поле
                        if (!empty($aData['phones'])) {
                            $aPhoneFirst = reset($aData['phones']);
                            $aRegisterData['phone'] = $aPhoneFirst['v'];
                        }
                        $aRegisterData['phones'] = serialize($aData['phones']);
                        $aUserData = $users->userRegister($aRegisterData);
                        if (empty($aUserData['user_id'])) {
                            $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                            break;
                        }
                    } else {
                        # пользователь существует и его аккаунт заблокирован
                        if ($aUserData['blocked']) {
                            $this->errors->set(_t('users', 'В доступе отказано по причине: [reason]', array('reason' => $aUserData['blocked_reason'])));
                            break;
                        }
                        if (empty($aUserData['activate_key'])) {
                            $aActivation = $users->updateActivationKey($aUserData['user_id']);
                            $aUserData['activate_key'] = $aActivation['key'];
                        }
                    }
                    $nUserID = $aUserData['user_id'];
                    $bNeedActivation = true;
                    $aUserData['email'] = $sEmail;
                } else {
                    # проверка доступности публикации объявления
                    $aData['shop_id'] = $this->publisherCheck($nShopID, 'shop');
                    if ($aData['shop_id'] && !Shops::model()->shopActive($nShopID)) {
                        $this->errors->set(_t('item-form', 'Размещение объявления доступно только от активированного магазина'));
                    }
                    # если пользователь авторизован и при этом не вводил номер телефона ранее
                    if ($registerPhone)
                    {
                        $aUserData = User::data(array('phone_number', 'phone_number_verified'), true);
                        if (empty($aUserData['phone_number']) || !$aUserData['phone_number_verified']) {
                            $phone = $this->input->post('phone', TYPE_NOTAGS, array('len'=>30));
                            if (!$this->input->isPhoneNumber($phone)) {
                                $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                                break;
                            }
                            if ($users->model->userPhoneExists($phone, $nUserID)) {
                                $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован.'), 'phone');
                                break;
                            }
                            $aActivation = $users->getActivationInfo();
                            $users->model->userSave($nUserID, array(
                                'activate_key'    => $aActivation['key'],
                                'activate_expire' => $aActivation['expire'],
                                'phone_number'    => $phone,
                            ));
                            $aUserData['activate_key'] = $aActivation['key'];
                            $bNeedActivation = true;
                        }
                    }
                }

                # проверим лимитирование объявлений
                $nCheckShopID = (!empty($aData['shop_id']) ? $aData['shop_id'] : 0);
                if ($this->itemsLimitExceeded($nUserID, $nCheckShopID, $aData['cat_id1'], $limit)) {
                    $limit = tpl::declension($limit, _t('bbs', 'объявление;объявления;объявлений'));
                    if (config::get('bbs_items_limits_'.($nCheckShopID?'shop':'user')) == static::LIMITS_CATEGORY) {
                        $this->errors->set(_t('bbs', 'Возможность публикации объявлений в данную категорию на сегодня исчерпана ([limit] в сутки).', array('limit' => $limit)));
                    } else {
                        $this->errors->set(_t('bbs', 'Возможность публикации объявлений на сегодня исчерпана ([limit] в сутки).', array('limit' => $limit)));
                    }
                    break;
                }

                # антиспам фильтр: проверка дубликатов
                if ($this->spamDuplicatesFound($nUserID, $aData)) {
                    $this->errors->set(_t('bbs', 'Вы уже публиковали аналогичное объявление. Воспользуйтесь функцией поднятия объявления'));
                    break;
                }

                # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                if ($this->errors->no()) {
                    Site::i()->preventSpam('bbs-add', 20);
                }

                if (!$this->errors->no()) break;

                if ($bNeedActivation) {
                    $aData['status'] = self::STATUS_NOTACTIVATED;
                    $aActivation = $this->getActivationInfo();
                    $aData['activate_key'] = $aActivation['key'];
                    $aData['activate_expire'] = $aActivation['expire'];
                } else {
                    $aData['status'] = self::STATUS_PUBLICATED;
                    $aData['publicated'] = $this->db->now();
                    $aData['publicated_order'] = $this->db->now();
                    $aData['publicated_to'] = $this->getItemPublicationPeriod();
                    $aData['moderated'] = 0; # помечаем на модерацию
                }

                # создаем объявление
                $aData['user_id'] = $nUserID;
                $nItemID = $this->model->itemSave(0, $aData, 'd');
                if (!$nItemID) {
                    $this->errors->set(_t('item-form', 'Ошибка публикации объявления, обратитесь в службу поддержки.'));
                    break;
                } else {
                    # если у пользователя в профиле не заполнено поле "город" берём его из объявления
                    if (User::id() && $aData['city_id'] && !User::data('region_id', true)) {
                        $users->model->userSave(User::id(), array('region_id'=>$aData['city_id']));
                    }
                    # обновляем счетчик объявлений "на модерации"
                    if (isset($aData['moderated']) && empty($aData['moderated'])) {
                        $this->moderationCounterUpdate(1);
                    }
                }

                $aResponse['id'] = $nItemID;

                # сохраняем / загружаем изображения
                $oImages = $this->itemImages($nItemID);
                if ($this->input->post('images_type', TYPE_STR) == 'simple') {
                    # загружаем
                    if (!empty($_FILES)) {
                        for ($i = 1; $i <= $oImages->getLimit(); $i++) {
                            $oImages->uploadFILES('images_simple_' . $i);
                        }
                        # удаляем загруженные через "удобный способ"
                        $aImages = $this->input->post('images', TYPE_ARRAY_STR);
                        $oImages->deleteImages($aImages);
                    }
                } else {
                    # перемещаем из tmp-директории в постоянную
                    $oImages->saveTmp('images');
                }

                # требуется активация:
                if ($bNeedActivation) {
                    if ($registerPhone) {
                        # отправляем sms c кодом активации
                        $users->sms(false)->sendActivationCode($phone, $aUserData['activate_key']);
                    } else {
                        # отправляем письмо cо ссылкой на активацию объявления
                        $aMailData = array(
                            'name'          => $aData['name'],
                            'email'         => $aUserData['email'],
                            'activate_link' => $aActivation['link'] . '_' . $nItemID
                        );
                        bff::sendMailTemplate($aMailData, 'bbs_item_activate', $aUserData['email']);
                    }
                }

                $aResponse['successPage'] = static::url('item.add', array(
                        'id'      => $nItemID,
                        'ak'      => (!empty($aActivation['key']) ? substr($aActivation['key'], 1, 8) : ''),
                        'success' => 1,
                        'svc'     => $this->input->post('svc', TYPE_UINT)
                    )
                );
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        $aData['status'] = self::STATUS_NOTACTIVATED;
        $aData['title_meta'] = _t('item-form', 'Разместить объявление');

        # подставляем данные пользователя:
        if ($nUserID) {
            $aUserData = Users::model()->userData($nUserID, array(
                    'name',
                    'email',
                    'phone_number',
                    'phone_number_verified',
                    'phones',
                    'skype',
                    'icq',
                    'region_id as city_id',
                    'shop_id'
                )
            );
            $aData = array_merge($aData, $aUserData);
        } else {
            $aData['phone_number'] = '';
            $aData['phone_number_verified'] = 0;
        }

        # проверка доступности публикации от "магазина"
        $aData['shop_id'] = 0;
        if ($publisherOnlyShop) {
            if (!$nUserID) {
                return $this->showInlineMessage(_t('item-form', 'Публикация объявления доступна только для авторизованных пользователей'),
                    array('auth' => true)
                );
            }
            if (!$nShopID) {
                return $this->showInlineMessage(_t('item-form', 'Для публикация объявления вам необходимо <a [link]>открыть магазин</a>.',
                        array('link' => 'href="' . Shops::url('my.open') . '"')
                    )
                );
            } else if (!Shops::model()->shopActive($nShopID)) {
                return $this->showInlineMessage(_t('item-form', 'Размещение объявления доступно только при <a [link]>активированном магазине</a>.',
                        array('link' => 'href="' . Shops::url('my.shop') . '"')
                    )
                );
            }
        }

        # SEO: Добавление объявления
        $this->urlCorrection(static::url('item.add'));
        $this->seo()->canonicalUrl(static::url('item.add', array(), true));
        $this->setMeta('add');

        return $this->form($nItemID, $aData);
    }

    /**
     * Редактирование ОБ
     * @param getpost ::uint 'id' - ID объявления
     */
    public function edit()
    {
        $this->security->setTokenPrefix('bbs-item-form');
        $nUserID = User::id();
        $nShopID = User::shopID();
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!empty($_GET['success']) && Request::isGET()) {
            # Результат редактирования
            return $this->itemStatus('edit', $nItemID);
        }

        if (Request::isPOST()) {
            $aResponse = array();
            do {
                if (!$nItemID ||
                    !$nUserID ||
                    !$this->security->validateToken()
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                $aItemData = $this->model->itemData($nItemID, array(
                        'user_id',
                        'city_id',
                        'cat_id',
                        'status',
                        'publicated_order',
                        'video',
                        'imgcnt',
                        'title',
                        'descr',
                        'moderated'
                    )
                );
                if (empty($aItemData)) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->isItemOwner($nItemID, $aItemData['user_id'])) {
                    $this->errors->set(_t('item-form', 'Вы не является владельцем данного объявления.'));
                    break;
                }

                # проверка статуса объявления
                $aData['shop_id'] = $this->publisherCheck($nShopID, 'shop');
                if ($aData['shop_id'] && !Shops::model()->shopActive($nShopID)) {
                    $this->errors->set(_t('item-form', 'Ваш магазин был <a [link]>деактивирован или заблокирован</a>.<br/>Невозможно разместить объявление от магазина.', array(
                                'link' => 'href="' . Shops::url('my.shop') . '" target="_blank"'
                            )
                        )
                    );
                    break;
                }

                # проверяем данные
                $this->validateItemData($aData, $nItemID, $aItemData);



                if (!$this->errors->no()) break;

                if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    # объявление заблокировано, помечаем на проверку модератору
                    $aData['moderated'] = 0;
                }

                # помечаем на модерацию при изменении: названия, описания
                if ($aData['title'] != $aItemData['title'] || $aData['descr'] != $aItemData['descr']) {
                    if ($aItemData['moderated']) $aData['moderated'] = 2;
                }

                # сохраняем
                $bSuccess = $this->model->itemSave($nItemID, $aData, 'd');
                if ($bSuccess) {
                    # сохраняем / загружаем изображения
                    $oImages = $this->itemImages($nItemID);
                    if ($this->input->post('images_type', TYPE_STR) == 'simple') {
                        # загружаем
                        if (!empty($_FILES) && $aItemData['imgcnt'] < $oImages->getLimit()) {
                            for ($i = 1; $i <= $oImages->getLimit(); $i++) {
                                $oImages->uploadFILES('images_simple_' . $i);
                            }
                        }
                    } else {
                        # сохраняем порядок изображений
                        $aImages = $this->input->post('images', TYPE_ARRAY_STR);
                        $oImages->saveOrder($aImages, false);
                    }

                    # помечаем на модерацию при изменении: фотографий
                    if ($oImages->newImagesUploaded($this->input->post('images_hash', TYPE_STR))) {
                        if ($aItemData['moderated']) {
                            $this->model->itemSave($nItemID, array('moderated' => 2), false);
                            $aData['moderated'] = 2;
                        }
                    }

                    # счетчик "на модерации"
                    if (isset($aData['moderated'])) {
                        $this->moderationCounterUpdate();
                    }
                }

                # URL страницы "успешно"
                $aResponse['successPage'] = static::url('item.edit', array(
                        'id'      => $nItemID,
                        'success' => 1,
                        'svc'     => $this->input->post('svc', TYPE_UINT)
                    )
                );
            } while (false);

            $this->iframeResponseForm($aResponse);
        }

        if (!$nItemID || !$nUserID) $this->errors->error404();

        $aData = $this->model->itemData($nItemID, array(), true);
        if (empty($aData)) $this->errors->error404();

        $aData['title_meta'] = _t('item-form', 'Редактирование объявления');
        if ($aData['user_id'] != $nUserID) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Вы не являетесь владельцем данного объявления'));
        }
        if ($aData['status'] == self::STATUS_NOTACTIVATED) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Объявление еще неактивировано'));
        }
        if ($aData['status'] == self::STATUS_BLOCKED && !$aData['moderated']) {
            return $this->showForbidden($aData['title_meta'], _t('item-form', 'Объявление ожидает проверки модератора'));
        }

        bff::setMeta($aData['title_meta']);
        $this->seo()->robotsIndex(false);
        $this->seo()->robotsFollow(false);

        return $this->form($nItemID, $aData);
    }

    /**
     * Формирование шаблона формы добавления / редактирования ОБ
     * @param integer $nItemID ID ОБ
     * @param array $aData @ref данные ОБ
     * @return string HTML
     */
    protected function form($nItemID, array &$aData)
    {
        # id
        $aData['id'] = $nItemID;

        # изображения
        $aData['img'] = $this->itemImages($nItemID);
        if ($nItemID > 0) {
            $aImages = $aData['img']->getData($aData['imgcnt']);
            $aData['images'] = array();
            foreach ($aImages as $v) {
                $aData['images'][] = array(
                    'id'       => $v['id'],
                    'tmp'      => false,
                    'filename' => $v['filename'],
                    'i'        => $aData['img']->getURL($v, BBSItemImages::szSmall, false)
                );
            }
            $aData['imghash'] = $aData['img']->getLastUploaded();
        } else {
            $aData['images'] = array();
            $aData['imgcnt'] = 0;
            $aData['imghash'] = '';
        }

        # категория
        $catID = & $aData['cat_id'];
        if (!$nItemID && !empty($_GET['cat'])) {
            # предварительный выбор, при добавлении (?cat=X)
            $catID = $this->input->get('cat', TYPE_UINT);
        }
        # формируем форму дин. свойств, типы, цену
        $aData['cat_data'] = $this->itemFormByCategory($catID, $aData);
        if (empty($aData['cat_data']) || $aData['cat_data']['subs'] > 0) {
            # ID категории указан некорректно (невалидный или есть подкатегории)
            $catID = 0;
        }
        # полный путь текущей выбранной категории + иконка основной категории
        $aData['cat_path'] = array();
        if ($catID > 0) {
            $catPath = $this->model->catParentsData($catID, array('id', 'title', 'icon_s'));
            foreach ($catPath as $v) {
                $aData['cat_path'][] = $v['title'];
            }
            $catParent = reset($catPath);
            $aData['cat_data']['icon'] = static::categoryIcon()->url($catParent['id'], $catParent['icon_s'], BBSCategoryIcon::SMALL);
        }

        # доступные для активации услуги
        $aData['curr'] = Site::currencyDefault();
        $aData['svc_data'] = $this->model->svcData();

        # город
        if (Geo::coveringType(Geo::COVERING_CITY)) {
            $aData['city_id'] = Geo::coveringRegion();
        }
        $aData['city_data'] = Geo::regionData($aData['city_id']);

        # публикация от "магазина"
        $nShopID = User::shopID();
        $aData['publisher'] = static::publisher();
        if ($aData['shop'] = ($nShopID && $aData['publisher'] != static::PUBLISHER_USER && bff::moduleExists('shops'))) {
            $aData['publisher_only_shop'] = static::publisher(array(
                    static::PUBLISHER_SHOP,
                    static::PUBLISHER_USER_TO_SHOP
                )
            );
            $aData['shop_data'] = Shops::model()->shopData($nShopID,
                array('phones', 'skype', 'icq', 'reg3_city as city_id', 'addr_addr', 'addr_lat', 'addr_lon', 'status'),
                false
            );
            if (empty($aData['shop_data']) || $aData['shop_data']['status'] != Shops::STATUS_ACTIVE) {
                $aData['shop_data'] = false;
            } else {
                unset($aData['shop_data']['status']);
                $aData['shop_data']['city_data'] = Geo::regionData($aData['shop_data']['city_id']);
                # при публикации только от "магазина" - подставляем контакты магазина
                if ($aData['publisher_only_shop'] && !$nItemID) {
                    $aData['shop_data']['metro_id'] = 0;
                    foreach ($aData['shop_data'] as $k => $v) $aData[$k] = $v;
                }
            }
        }

        # метро
        $aData['metro_data'] = Geo::cityMetro($aData['city_id'], $aData['metro_id'], false);
        if (empty($aData['metro_data']['sel']['id'])) $aData['metro_id'] = 0;

        # координаты по-умолчанию
        Geo::mapDefaultCoordsCorrect($aData['addr_lat'], $aData['addr_lon']);

        return $this->viewPHP($aData, 'item.form');
    }

    /**
     * Страница результата добавления / редактирование / управления ОБ
     * @param string $state ключ результата
     * @param integer $nItemID ID объявления
     * @param array $aData дополнительные данные
     * @return string HTML
     */
    protected function itemStatus($state, $nItemID, array $aData = array())
    {
        $title = '';

        do {
            # получаем данные об объявлении
            if (!$nItemID) {
                $state = false;
                break;
            }
            $aItemData = $this->model->itemData($nItemID, array(
                    'id',
                    'user_id',
                    'title',
                    'link',
                    'status',
                    'status_prev',
                    'activate_key',
                    'svc',
                    'svc_up_activate',
                    'svc_premium_to',
                    'svc_marked_to',
                    'svc_press_status',
                    'svc_press_date',
                    'cat_id1',
                    'cat_id',
                )
            );
            if (empty($aItemData)) {
                $state = false;
                break;
            };

            if ($state == 'new' || $state == 'edit') {
                # активация услуги
                $nSvcID = $this->input->get('svc', TYPE_UINT);
                if ($nSvcID > 0 && bff::servicesEnabled()) {
                    $this->redirect(static::url('item.promote', array(
                                'id'   => $nItemID,
                                'svc'  => $nSvcID,
                                'from' => $state
                            )
                        )
                    );
                }

                # проверяем владельца
                $nUserID = User::id();
                $nItemUserID = $aItemData['user_id'];
                if ($nUserID && $nItemUserID != $nUserID) {
                    break;
                }

                if ($aItemData['status'] == self::STATUS_NOTACTIVATED) {
                    # проверка корректности перехода, по совпадению части подстроки ключа активации
                    $activateCodePart = $this->input->get('ak', TYPE_STR);
                    if (stripos($aItemData['activate_key'], $activateCodePart) === false) {
                        $this->redirect(static::urlBase()); # не совпадают
                        break;
                    }

                    # Шаг активации объявления + телефона
                    $users = $this->users();
                    $registerPhone = Users::registerPhone();
                    $aData['new_user'] = false;
                    if (!$nUserID) {
                        # Запрещаем изменение номера телефона в случае если объявление добавляет
                        # неавторизованный пользователь от имени зарегистрированного с наличием одного и более активированных ОБ
                        $nUserItemsCounter = $this->model->itemsList(array('user_id'=>$nItemUserID, ':status'=>'status!='.static::STATUS_NOTACTIVATED), true);
                        if ($nUserItemsCounter > 1) {
                            $aData['new_user'] = true;
                        }
                    }
                    if ($registerPhone && Request::isAJAX())
                    {
                        $userData = $users->model->userData($nItemUserID, array(
                            'email', 'name', 'activated', 'activate_key', 'password', 'password_salt',
                            'phone_number', 'phone_number_verified',
                        ));
                        $act = $this->input->postget('act');
                        $response = array();
                        if (!$this->security->validateReferer() || empty($userData)) {
                            $this->errors->reloadPage(); $act = '';
                        }
                        $userPhone = $userData['phone_number'];
                        switch ($act)
                        {
                            # Проверка кода подтверждения
                            case 'code-validate':
                            {
                                $code = $this->input->postget('code', TYPE_NOTAGS);
                                if (mb_strtolower($code) !== $userData['activate_key']) {
                                    $this->errors->set(_t('users', 'Код подтверждения указан некорректно'), 'phone');
                                    break;
                                }
                                # Активируем аккаунт + объявления
                                if (empty($userData['activated']))
                                {
                                    $password = func::generator(12); # генерируем новый пароль
                                    $res = $users->model->userSave($nItemUserID, array(
                                        'phone_number_verified' => 1, 'activated' => 1, 'activate_key' => '',
                                        'password' => $this->security->getUserPasswordMD5($password, $userData['password_salt']),
                                    ));
                                    if ($res) {
                                        bff::i()->callModules('onUserActivated', array($nItemUserID));
                                        # Авторизуем
                                        $users->userAuth($nItemUserID, 'user_id', $password, false);
                                        # Отправляем письмо об успешной регистрации
                                        bff::sendMailTemplate(array(
                                            'email'    => $userData['email'],
                                            'password' => $password,
                                            'phone'    => $userData['phone_number'],
                                        ), 'users_register_phone', $userData['email']);
                                    } else {
                                        bff::log('bbs: Ошибка активации аккаунта пользователя по коду подтверждения [user-id="'.$nItemUserID.'"]');
                                        $this->errors->set(_t('users', 'Ошибка регистрации, обратитесь к администратору'));
                                        break;
                                    }
                                } else {
                                    # Активируем только объявление
                                    bff::i()->callModules('onUserActivated', array($nItemUserID));
                                    # Авторизуем
                                    if (!$nUserID) {
                                        $users->userAuth($nItemUserID, 'user_id', $userData['password']);
                                    }
                                    # Помечаем успешное подтверждение номера телефона
                                    if (!$userData['phone_number_verified']) {
                                        $users->model->userSave($nItemUserID, array('phone_number_verified' => 1));
                                    }
                                }
                                $response['redirect'] = static::url('item.add', array(
                                    'id' => $nItemID, 'success' => 1, 'activated' => 1,
                                ));
                            } break;
                            # Повторная отправка кода подтверждения - OK
                            case 'code-resend':
                            {
                                $activationNew = $users->updateActivationKey($nItemUserID);
                                if ($activationNew) {
                                    $users->sms()->sendActivationCode($userPhone, $activationNew['key']);
                                }
                            } break;
                            # Смена номера телефона - OK
                            case 'phone-change':
                            {
                                if ($aData['new_user']) {
                                    $this->errors->reloadPage();
                                    break;
                                }
                                $phone = $this->input->postget('phone', TYPE_NOTAGS, array('len'=>30));
                                if (!$this->input->isPhoneNumber($phone)) {
                                    $this->errors->set(_t('users', 'Номер телефона указан некорректно'), 'phone');
                                    break;
                                }
                                if ($phone === $userPhone) {
                                    break;
                                }
                                if ($users->model->userPhoneExists($phone, $nItemUserID)) {
                                    if ($nUserID) {
                                        $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован.'), 'phone');
                                    } else {
                                        $this->errors->set(_t('users', 'Пользователь с таким номером телефона уже зарегистрирован. <a [link_forgot]>Забыли пароль?</a>',
                                                array('link_forgot' => 'href="' . Users::url('forgot') . '"')
                                            ), 'phone'
                                        );
                                    }
                                    break;
                                }
                                $activationNew = $users->updateActivationKey($nItemUserID);
                                $res = $users->model->userSave($nItemUserID, array(
                                    'phone_number' => $phone,
                                    'phone_number_verified' => 0,
                                ));
                                if (!$res) {
                                    bff::log('bbs: Ошибка обновления номера телефона [user-id="'.$nItemUserID.'"]');
                                    $this->errors->reloadPage();
                                } else {
                                    $response['phone'] = '+'.$phone;
                                    $users->sms()->sendActivationCode($phone, $activationNew['key']);
                                }
                            } break;
                        }

                        $this->ajaxResponseForm($response);
                    }

                    $state = 'new.notactivated'.($registerPhone?'.phone':'');
                    $title = _t('bbs', 'Спасибо! Осталось всего лишь активировать объявление!');
                } else if ($aItemData['status'] == self::STATUS_BLOCKED) {
                    $state = 'edit.blocked.wait';
                    $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                } else {
                    if ($state == 'new') {
                        $state = 'new.publicated';
                        if (!empty($aData['activated']) || $this->input->get('activated', TYPE_BOOL)) {
                            # активировали
                            $title = _t('bbs', 'Вы успешно активировали объявление!');
                        } else {
                            $title = _t('bbs', 'Вы успешно создали объявление!');
                        }
                    } else {
                        if ($this->input->get('pub', TYPE_BOOL)) # изменился статус публикации
                        {
                            # опубликовали
                            if ($aItemData['status'] == self::STATUS_PUBLICATED &&
                                $aItemData['status_prev'] == self::STATUS_PUBLICATED_OUT
                            ) {
                                $state = 'edit.publicated';
                                $title = _t('bbs', 'Вы успешно опубликовали объявление!');
                            } # сняли с публикации
                            else if ($aItemData['status'] == self::STATUS_PUBLICATED_OUT &&
                                $aItemData['status_prev'] == self::STATUS_PUBLICATED
                            ) {
                                $state = 'edit.publicated.out';
                                $title = _t('bbs', 'Вы успешно сняли объявление с публикации!');
                            } else {
                                $state = 'edit.normal';
                                $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                            }
                        } else {
                            # отредактировали без изменения статуса
                            $state = 'edit.normal';
                            $title = _t('bbs', 'Вы успешно отредактировали объявление!');
                        }
                    }
                }
            } else if ($state == 'promote.success') {
                $title = _t('bbs', 'Продвижение объявления');
            } else {
                $this->errors->error404();
            }

        } while (false);

        if ($state === false) {
            $this->errors->error404();
        }

        $aData['user'] = Users::model()->userData($aItemData['user_id'], array('email', 'phone_number', 'phone_number_verified', 'activated', 'name'));
        $aData['state'] = $state;
        $aData['item'] = & $aItemData;
        $aData['back'] = Request::referer(static::urlBase());
        $aData['from'] = $this->input->getpost('from', TYPE_STR);

        bff::setMeta($title);
        $this->seo()->robotsIndex(false);

        return $this->showShortPage($title, $this->viewPHP($aData, 'item.status'));
    }

    /**
     * Продвижение ОБ
     * @param getpost ::uint 'id' - ID объявления
     */
    public function promote()
    {
        $aData = array();
        $sTitle = _t('bbs', 'Продвижение объявления');
        $sFrom = $this->input->postget('from', TYPE_NOTAGS);
        $nUserID = User::id();
        $nSvcID = $this->input->postget('svc', TYPE_UINT);
        $nItemID = $this->input->getpost('id', TYPE_UINT);
        if (!empty($_GET['success'])) {
            return $this->itemStatus('promote.success', $nItemID);
        }

        $aItem = $this->model->itemData($nItemID, array(
                'user_id',
                'id',
                'status',
                'deleted',
                'blocked_reason',
                'cat_id',
                'city_id',
                'title',
                'link',
                'svc',
                'svc_up_activate',
                'svc_fixed_to',
                'svc_premium_to',
                'svc_marked_to',
                'svc_press_status',
                'svc_press_date',
                'svc_press_date_last',
                'svc_quick_to',
            )
        );
        if (!$nUserID) {
            # Авторизуем пользователя в случае если был выполнен переход по ссылке с ключем ?auth=X
            $nUserID = $this->userAuthGetParam($aItem['user_id']);
        }
        $aPaySystems = Bills::getPaySystems($nUserID>0);

        $aSvc = $this->model->svcData();
        $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvc), $aItem['cat_id'], $aItem['city_id']);
        foreach ($aSvcPrices as $k => $v) {
            if (!empty($v)) $aSvc[$k]['price'] = $v;
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
                if (!$nItemID || empty($aItem) || $aItem['deleted'] || in_array($aItem['status'], array(
                            self::STATUS_BLOCKED,
                            self::STATUS_PUBLICATED_OUT
                        )
                    )
                ) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$nSvcID || !isset($aSvc[$nSvcID])) {
                    $this->errors->set(_t('bbs', 'Выберите услугу'));
                    break;
                }
                $aSvcSettings = array();
                $nSvcPrice = $aSvc[$nSvcID]['price'];
                # конвертируем сумму в валюту для оплаты по курсу
                $pay = Bills::getPayAmount($nSvcPrice, $ps);

                if ($ps == 'balance' && $nUserBalance >= $nSvcPrice) {
                    # активируем услугу (списываем со счета пользователя)
                    $aResponse['redirect'] = static::url('item.promote', array(
                            'id'      => $nItemID,
                            'success' => 1,
                            'from'    => $sFrom
                        )
                    );
                    $aResponse['activated'] = $this->svc()->activate($this->module_name, $nSvcID, false, $nItemID, $nUserID, $nSvcPrice, $pay['amount'], $aSvcSettings);
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
                        $nItemID, $aSvcSettings
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

        if (!$nItemID || empty($aItem)) {
            return $this->showForbidden($sTitle, _t('bbs', 'Объявление не найдено, либо ссылка указана некорректно'));
        }
        # проверяем статус ОБ
        if ($aItem['deleted']) {
            return $this->showForbidden($sTitle, _t('bbs', 'Объявление было удалено'));
        } else if ($aItem['status'] == self::STATUS_BLOCKED) {
            return $this->showForbidden($sTitle,
                _t('bbs', 'Объявление было заблокировано модератором, причина: [reason]', array(
                        'reason' => $aItem['blocked_reason']
                    )
                )
            );
        } else if ($aItem['status'] == self::STATUS_PUBLICATED_OUT) {
            return $this->showForbidden($sTitle,
                _t('bbs', 'Необходимо опубликовать объявление для дальнейшего его продвижения.')
            );
        }
        $aData['item'] = & $aItem;

        $this->urlCorrection(static::url('item.promote'));

        # способы оплаты
        $aData['curr'] = Site::currencyDefault();
        $aData['ps'] = & $aPaySystems;
        reset($aPaySystems);
        $aData['ps_active_key'] = key($aPaySystems);
        foreach ($aPaySystems as $k => &$v) {
            $v['active'] = ($k == $aData['ps_active_key']);
        }
        unset($v);

        # список услуг
        foreach ($aSvc as &$v) {
            $v['active'] = ($v['id'] == $nSvcID);
            if ($v['id'] == self::SERVICE_UP && $aItem['svc_up_activate'] > 0) {
                $v['price'] = 0;
            }
            if ($v['id'] == self::SERVICE_PRESS && $aItem['svc_press_status'] > 0) {
                $v['disabled'] = true;
                if ($v['active']) {
                    $v['active'] = false;
                    $nSvcID = 0;
                }
            }
            $aSvcPrices[$v['id']] = $v['price'];
        }
        unset($v);
        $aData['svc'] = & $aSvc;
        $aData['svc_id'] = $nSvcID;
        $aData['svc_prices'] = & $aSvcPrices;

        $aData['user_balance'] = & $nUserBalance;
        $aData['from'] = $sFrom;

        # SEO
        $this->seo()->robotsIndex(false);
        bff::setMeta($sTitle);

        return $this->viewPHP($aData, 'item.promote');
    }


    /**
     * Управление изображениями ОБ (ajax)
     * @param getpost ::uint 'item_id' - ID объявления
     * @param getpost ::string 'act' - action
     */
    public function img()
    {
        $this->security->setTokenPrefix('bbs-item-form');

        $nItemID = $this->input->getpost('item_id', TYPE_UINT);
        $oImages = $this->itemImages($nItemID);
        $aResponse = array();

        switch ($this->input->getpost('act')) {
            case 'upload': # загрузка
            {
                $aResponse = array('success' => false);
                do {
                    if (!$this->security->validateToken(true, false)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    if ($nItemID) {
                        if (!$this->isItemOwner($nItemID)) {
                            $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                            break;
                        }
                    }

                    $result = $oImages->uploadQQ();

                    $aResponse['success'] = ($result !== false && $this->errors->no());
                    if ($aResponse['success']) {
                        $aResponse = array_merge($aResponse, $result);
                        $aResponse['tmp'] = empty($nItemID);
                        $aResponse['i'] = $oImages->getURL($result, BBSItemImages::szSmall, $aResponse['tmp']);
                        unset($aResponse['dir'], $aResponse['srv']);
                    }
                } while (false);

                $aResponse['errors'] = $this->errors->get();
                $this->ajaxResponse($aResponse, true, false, true);
            }
            break;
            case 'delete': # удаление
            {
                $nImageID = $this->input->post('image_id', TYPE_UINT);
                $sFilename = $this->input->post('filename', TYPE_STR);

                # неуказан ID изображения ни filename временного
                if (!$nImageID && empty($sFilename)) {
                    $this->errors->reloadPage();
                    break;
                }

                if (!$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                if ($nItemID) {

                    # проверяем доступ на редактирование
                    if (!$this->isItemOwner($nItemID)) {
                        $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                        break;
                    }
                }

                if ($nImageID) {
                    # удаляем изображение по ID
                    $oImages->deleteImage($nImageID);
                } else {
                    # удаляем временное
                    $oImages->deleteTmpFile($sFilename);
                }
            }
            break;
            case 'delete-tmp': # удаление временных
            {
                $aFilenames = $this->input->post('filenames', TYPE_ARRAY_STR);
                $oImages->deleteTmpFile($aFilenames);
            }
            break;
            default:
            {
                $this->errors->reloadPage();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    /**
     * Активация ОБ
     * @param get ::string 'c' - ключ активации
     */
    public function activate()
    {
        $langActivateTitle = _t('bbs', 'Активация объявления');

        $sCode = $this->input->get('c', TYPE_STR); # ключ активации + ID объявления
        list($sCode, $nItemID) = explode('_', (!empty($sCode) && (strpos($sCode, '_') !== false) ? $sCode : '_'), 2);
        $nItemID = $this->input->clean($nItemID, TYPE_UINT);

        # 1. Получаем данные об ОБ:
        $aData = $this->model->itemData($nItemID, array(
                'user_id',
                'status',
                'activate_key',
                'activate_expire',
                'deleted',
                'blocked_reason'
            )
        );
        if (empty($aData)) {
            # не нашли такого объявления
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Объявление не найдено. Возможно период действия ссылки активации вашего объявления истек.', array('link_add' => 'href="' . static::url('item.add') . '"'))
            );
        }
        if ($aData['activate_key'] != $sCode || strtotime($aData['activate_expire']) < BFF_NOW) {
            # код неверный
            #  или
            # срок действия кода активации устек
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Срок действия ссылки активации истек либо она некорректна. Пожалуйста, <a [link_add]>добавьте новое объявление</a>.', array('link_add' => 'href="' . static::url('item.add') . '"'))
            );
        }
        if ($aData['deleted']) {
            # объявление было удалено (модератором?!)
            return $this->showForbidden($langActivateTitle, _t('bbs', 'Объявление было удалено'));
        }
        if ($aData['status'] == self::STATUS_BLOCKED) {
            # объявление было заблокировано (модератором?!)
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Объявление было заблокировано модератором, причина: [reason]', array(
                        'reason' => $aData['blocked_reason']
                    )
                )
            );
        }

        # 2. Получаем данные о пользователе:
        $aUserData = Users::model()->userData($aData['user_id'], array(
                'user_id',
                'email',
                'name',
                'password',
                'password_salt',
                'activated',
                'blocked',
                'blocked_reason'
            )
        );
        if (empty($aUserData)) {
            # попытка активации объявления при отсутствующем профиле пользователя (публиковавшего объявление)
            return $this->showForbidden($langActivateTitle, _t('bbs', 'Ошибка активации, обратитесь в службу поддержки.'));
        } else {
            $nUserID = $aUserData['user_id'];
            # аккаунт заблокирован
            if ($aUserData['blocked']) {
                return $this->showForbidden($langActivateTitle,
                    _t('bbs', 'Ваш аккаунт заблокирован. За детальной информацией обращайтесь в службу поддержки.')
                );
            }
            # активируем аккаунт
            if (!$aUserData['activated']) {
                $sPassword = func::generator(12); # генерируем новый пароль
                $aUserData['password'] = $this->security->getUserPasswordMD5($sPassword, $aUserData['password_salt']);
                $bSuccess = Users::model()->userSave($nUserID, array(
                        'activated'    => 1,
                        'activate_key' => '',
                        'password'     => $aUserData['password'],
                    )
                );
                if ($bSuccess) {
                    $bUserActivated = true;
                    # отправляем письмо об успешной автоматической регистрации
                    bff::sendMailTemplate(array(
                            'name'     => $aUserData['name'],
                            'email'    => $aUserData['email'],
                            'password' => $sPassword
                        ),
                        'users_register_auto', $aUserData['email']
                    );
                }
            }
            # авторизуем, если текущий пользователь неавторизован
            if (!User::id()) {
                Users::i()->userAuth($nUserID, 'user_id', $aUserData['password'], true);
            }
        }

        # 3. Публикуем объявление:
        $bSuccess = $this->model->itemSave($nItemID, array(
                'activate_key'     => '', # чистим ключ активации
                'publicated'       => $this->db->now(),
                'publicated_order' => $this->db->now(),
                'publicated_to'    => $this->getItemPublicationPeriod(),
                'status_prev'      => self::STATUS_NOTACTIVATED,
                'status'           => self::STATUS_PUBLICATED,
                'moderated'        => 0, # помечаем на модерацию
            )
        );

        if (isset($bUserActivated)) {
            # триггер активации аккаунта пользователя
            bff::i()->callModules('onUserActivated', array($nUserID));
        }

        if (!$bSuccess) {
            return $this->showForbidden($langActivateTitle,
                _t('bbs', 'Ошибка активации, обратитесь в службу поддержки.')
            );
        }

        # накручиваем счетчик кол-ва объявлений авторизованного пользователя
        $this->security->userCounter('items', 1, $nUserID); # +1
        # обновляем счетчик "на модерации"
        $this->moderationCounterUpdate();

        return $this->itemStatus('new', $nItemID, array('activated' => true));
    }

    /**
     * Кабинет пользователя: Импорт
     */
    public function my_import()
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }
        
        if (static::publisher(static::PUBLISHER_SHOP) && !User::shopID()) {
            return $this->showInlineMessage(_t('bbs.import', 'Для возможности импорта объявлений <a [open_link]>откройте магазин</a>.',
                    array('open_link' => 'href="' . Shops::url('my.open') . '"')
                )
            );
        }

        $aData = array(
            'list'   => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по 15 на странице'), 'c' => 15),
                25 => array('t' => _t('pgn', 'по 25 на странице'), 'c' => 25),
                50 => array('t' => _t('pgn', 'по 50 на странице'), 'c' => 50),
            ),
        );

        $import = $this->itemsImport();
        $sAction = $this->input->getpost('sAction');
        switch ($sAction) {
            case 'template':
                $aSettings = array();
                $aSettings['catId'] = $this->input->get('cat_id', TYPE_UINT);
                $aSettings['state'] = $this->input->get('status', TYPE_UINT);
                $aSettings['langKey'] = LNG;
                
                if (empty($aSettings['catId'])) {
                    $aData['errors'] = _t('bbs.import','Необходимо выбрать категорию');
                    break;
                }
                
                if (empty($aSettings['state'])) {
                    $aData['errors'] = _t('bbs.import', 'Необходимо выбрать статус объявлений');
                    break;
                }

                $import->importTemplate($aSettings);
                break;
            case 'import':
                if (Request::isPOST())
                {
                    $aResponse = array();
                    $aSettings = array(
                        'catId'  => $this->input->post('cat_id', TYPE_UINT),
                        'userId' => $nUserID,
                        'shop'   => User::shopID(),
                        'state'  => $this->input->post('status', TYPE_UINT),
                    );

                    if (empty($aSettings['catId'])) {
                        $this->errors->set(_t('bbs.import','Необходимо выбрать категорию'));
                    }

                    if (empty($aSettings['state'])) {
                        $this->errors->set(_t('bbs.import', 'Необходимо выбрать статус объявлений'));
                    }

                    if ($this->errors->no()) {
                        $aResponse['id'] = $import->importStart('file', $aSettings);
                    }
                    $this->iframeResponseForm($aResponse);
                }
                break;
        }
        
        $f = $this->input->postgetm(array(
                'page' => TYPE_UINT, # страница
                'pp'   => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (!isset($aData['pgn_pp'][$f_pp])) {
            $f_pp = 15;
        }
        
        $aFilter = array('user_id' => User::id());
        $sqlFields = array();
        $nTotal = $this->model->importListing($sqlFields, $aFilter, false, false, true);
        $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
        if ($nTotal > 0) {
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
            $aData['list'] = $this->model->importListing($sqlFields, $aFilter, $oPgn->getLimitOffset(), 'created DESC');
            if (!empty($aData['list'])) {
                foreach ($aData['list'] as &$v) {
                    $v['comment_text'] = '';
                    $comment = func::unserialize($v['status_comment']);
                    if ($comment) {
                        if ($v['status'] == BBSItemsImport::STATUS_FINISHED) {
                            $details = array();
                            if ($v['items_ignored'] > 0)     $details[] = _t('bbs.import','пропущено: [count]',array('count'=>$v['items_ignored']));
                            if (!empty($comment['success'])) $details[] = _t('bbs.import','добавлено: [count]',array('count'=>$comment['success']));
                            if (!empty($comment['updated'])) $details[] = _t('bbs.import','обновлено: [count]',array('count'=>$comment['updated']));
                            if (!empty($details)) $v['comment_text'] = implode(', ',$details);
                        } elseif (isset($comment['message'])) {
                            $v['comment_text'] = _t('bbs.import', 'Ошибка обработки файла импорта, обратитесь к администратору');
                        }
                    }
                    
                    $v['file_link'] = '&mdash;';
                    $file = func::unserialize($v['filename']);
                    if ($file) {
                        $v['file_link'] = BBSItemsImport::getImportPath(true, $file['filename']);
                    }
                } unset($v);
            }
        }

        $aData['list_total'] = $nTotal;
        $aData['status'] = $import->getStatusList();
        $aData['list'] = $this->viewPHP($aData, 'my.import.list');
        
        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'   => $aData['pgn'],
                    'list'  => $aData['list'],
                    'total' => $nTotal,
                )
            );
        }
        
        $aData['f'] = & $f;
        $aData['page'] = $oPgn->getCurrentPage();

        return $this->viewPHP($aData, 'my.import');
    }
    
    /**
     * Кабинет пользователя: Объявления
     */
    public function my_items($nShopID = 0)
    {
        $nUserID = User::id();
        if (!$nUserID) {
            return $this->showInlineMessage(_t('users', 'Для доступа в кабинет необходимо авторизоваться'), array('auth' => true));
        }
        if (static::publisher(static::PUBLISHER_SHOP) && !User::shopID()) {
            return $this->showInlineMessage(_t('bbs.my', 'Для возможности публикации объявлений <a [open_link]>откройте магазин</a>.', array('open_link' => 'href="' . Shops::url('my.open') . '"')
                            )
            );
        }

        $sAction = $this->input->postget('act', TYPE_STR);
        if (!empty($sAction)) {
            $aResponse = array();

            if (!$this->security->validateToken()) {
                $this->errors->reloadPage();
            } else {
                $aItemID = $this->input->post('i', TYPE_ARRAY_UINT);
                if (empty($aItemID)) {
                    $this->errors->set(_t('bbs.my', 'Необходимо отметить минимум одно из ваших объявлений'));
                }
            }

            if ($this->errors->no()) {
                switch ($sAction) {
                    case 'mass-publicate': # массовая публикация

                        $aResponse['cnt'] = $this->model->itemsPublicate(array(
                                'id'      => $aItemID,
                                'user_id' => $nUserID,
                                'status'  => self::STATUS_PUBLICATED_OUT,
                                'deleted' => 0
                            )
                        );

                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно опубликованы');
                        }
                    break;
                    case 'mass-unpublicate': # массовое снятие с публикации

                        $aResponse['cnt'] = $this->model->itemsUnpublicate(array(
                                'id'      => $aItemID,
                                'user_id' => $nUserID,
                                'status'  => self::STATUS_PUBLICATED,
                                # deleted не проверяем, поскольку они не могут быть в статусе STATUS_PUBLICATED
                            )
                        );
                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно сняты с публикации');
                        }
                    break;
                    case 'mass-refresh': # массовое продление
                        $aResponse['cnt'] = $this->model->itemsRefresh(array(
                                'id'      => $aItemID,
                                'user_id' => $nUserID,
                                'status'  => self::STATUS_PUBLICATED,
                                'deleted' => 0
                            )
                        );

                        if (!empty($aResponse['cnt'])) {
                            $aResponse['message'] = _t('bbs.my', 'Отмеченные объявления были успешно продлены');
                        }
                    break;
                    case 'mass-delete': # массовое удаление
                    {

                        $aItems = $this->model->itemsDataByFilter(
                            array(
                                'id'      => $aItemID,
                                'user_id' => $nUserID,
                                # для удаления доступны только снятые с публикации
                                'status'  => self::STATUS_PUBLICATED_OUT,
                                'deleted' => 0
                            ),
                            array('id')
                        );
                        if (!empty($aItems)) {
                            $aItems = array_keys($aItems);
                            $aResponse['cnt'] = $this->model->itemsSave($aItems, array(
                                    'deleted'       => 1, # помечаем как удаленные
                                    # снимаем с публикации
                                    'status_prev = status',
                                    'status'        => self::STATUS_PUBLICATED_OUT,
                                    'publicated_to' => $this->db->now(),
                                )
                            );
                            $aResponse['success_msg'] = _t('bbs', 'Отмеченные объявления были успешно удалены');
                            $aResponse['id'] = $aItems;
                        }
                    }
                    break;
                    default:
                    {
                        $this->errors->reloadPage();
                    }
                    break;
                }
            }

            $this->ajaxResponseForm($aResponse);
        }

        $aData = array(
            'items'  => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по 15 на странице'), 'c' => 15),
                25 => array('t' => _t('pgn', 'по 25 на странице'), 'c' => 25),
                50 => array('t' => _t('pgn', 'по 50 на странице'), 'c' => 50),
            )
        );

        $aFilter = array('user_id' => $nUserID, 'deleted' => 0);
        $f = $this->input->postgetm(array(
                'status' => TYPE_UINT, # статус
                'c'      => TYPE_UINT, # ID категории
                'qq'     => TYPE_NOTAGS, # строка поиска
                'page'   => TYPE_UINT, # страница
                'pp'     => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        if (empty($f_status) || !in_array($f_status, array(1, 2, 3)))
            $f_status = 1;
        if (!isset($aData['pgn_pp'][$f_pp]))
            $f_pp = 15;

        $fillFilter = function($status, array &$filter) {
            switch ($status) {
                case 1: # активные
                {
                    $filter['status'] = self::STATUS_PUBLICATED;
                    if (static::premoderation()) {
                        $filter[':mod'] = 'moderated > 0';
                    }
                }
                break;
                case 2: # на проверке
                {
                    if (static::premoderation()) {
                        $filter[':mod'] = array('(status = :status OR moderated = 0)', ':status' => self::STATUS_BLOCKED);
                    } else {
                        $filter['status'] = self::STATUS_BLOCKED;
                    }
                }
                break;
                case 3: # неактивные
                {
                    $filter['status'] = self::STATUS_PUBLICATED_OUT;
                }
                break;
            }
        };

        if ($f_c > 0) {
            $aFilter[':cat'] = array('(cat_id2 = :catID OR cat_id1 = :catID)', ':catID' => $f_c);
        }

        if (!empty($f_qq)) {
            $f_qq = $this->input->cleanSearchString($f_qq, 50);
            if (!empty($f_qq)) {
                $aFilter[':qq'] = array('(I.title LIKE :qq OR I.descr LIKE :qq)', ':qq' => '%' . $f_qq . '%');
            }
        }

        $aFilter[':shop'] = array('I.shop_id = :shop', ':shop' => $nShopID);

        $aFilterCounters = array();
        for ($i = 1; $i <= 3; $i++) {
            $aFilterCounters[$i] = $aFilter;
            $fillFilter($i, $aFilterCounters[$i]);
        }
        $fillFilter($f_status, $aFilter);

        $nTotal = $aData['total'] = $this->model->itemsListMy($aFilter, true);
        if ($nTotal > 0) {
            $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
            $aData['items'] = $this->model->itemsListMy($aFilter, false, $oPgn->getLimitOffset(), 'created DESC');
        }
        $aData['counters'] = array();
        foreach ($aFilterCounters as $k=>$v) {
            $aData['counters'][$k] = $this->model->itemsListMy($v, true);
        }

        # формируем список
        $aData['device'] = bff::device();
        $aData['img_default'] = $this->itemImages()->urlDefault(BBSItemImages::szSmall);
        $aData['list'] = $this->viewPHP($aData, 'my.items.list');
        unset($aData['items']);

        $aCats = array(0 => array('id' => 0, 'title' => _t('bbs.my', 'Все категории')));
        $aData['cat_active'] = $aCats[0];
        unset($aFilter[':cat']);
        $aCats += $this->model->itemsListCategories($aFilter, 1);
        $aCatsSub = $this->model->itemsListCategories($aFilter, 2);
        if (sizeof($aCats) + sizeof($aCatsSub) < 11) {
            foreach ($aCatsSub as $v) {
                if (isset($aCats[$v['pid']])) {
                    $aCats[$v['pid']]['sub'][$v['id']] = $v;
                    if ($v['id'] == $f_c) {
                        $aData['cat_active'] = $v;
                    }
                }
            }
            unset($aCatsSub);
        }
        $aData['cats'] = $this->viewPHP($aCats, 'my.items.cats');

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'pgn'   => $aData['pgn'],
                    'list'  => $aData['list'],
                    'cats'  => $aData['cats'],
                    'total' => $aData['total'],
                    'counters' => $aData['counters'],
                )
            );
        }

        $aData['shop_id'] = User::shopID();
        $aData['f'] = & $f;
        $aData['empty'] = !$nTotal;
        $aData['status'] = array(
            1 => array('title' => _t('bbs.my', 'Активные'), 'left' => false, 'right' => 2),
            2 => array('title' => _t('bbs.my', 'На проверке'), 'left' => 1, 'right' => 3),
            3 => array('title' => _t('bbs.my', 'Неактивные'), 'left' => 2, 'right' => false),
        );

        return $this->viewPHP($aData, 'my.items');
    }

    /**
     * Кабинет пользователя: Избранные объявления
     */
    public function my_favs()
    {
        $nUserID = User::id();
        $sAction = $this->input->post('act', TYPE_STR);
        if (!empty($sAction)) {
            switch ($sAction) {
                # удаление всех избранных объявлений
                case 'cleanup':
                {
                    if ($nUserID) {
                        if (!$this->security->validateToken()) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $this->model->itemsFavDelete($nUserID);
                        # актулизируем счетчик избранных пользователя
                        User::counterSave('items_fav', 0);
                    } else {
                        Request::deleteCOOKIE(BBS_FAV_COOKIE);
                    }
                }
                break;
            }
            $this->ajaxResponseForm();
        }

        $aData = array(
            'items'  => array(),
            'pgn'    => '',
            'pgn_pp' => array(
                -1 => array('t' => _t('pgn', 'показать все'), 'c' => 100),
                15 => array('t' => _t('pgn', 'по 15 на странице'), 'c' => 15),
                25 => array('t' => _t('pgn', 'по 25 на странице'), 'c' => 25),
                50 => array('t' => _t('pgn', 'по 50 на странице'), 'c' => 50),
            ),
            'device' => bff::device(),
        );
        $f = $this->input->postgetm(array(
                'c'    => TYPE_UINT, # ID категории
                'lt'   => TYPE_UINT, # тип списка
                'page' => TYPE_UINT, # страница
                'pp'   => TYPE_INT, # кол-во на страницу
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f_lt = self::LIST_TYPE_LIST;
        if (!isset($aData['pgn_pp'][$f_pp]))
            $f_pp = 15;

        $aFavoritesID = $this->getFavorites($nUserID);
        $aFilter = array('id' => $aFavoritesID, 'status' => self::STATUS_PUBLICATED);
        if (static::premoderation()) {
            $aFilter[':mod'] = 'moderated > 0';
        }
        # корректируем счетчик избранных ОБ пользователя
        $aFavoritesExists = $this->model->itemsDataByFilter($aFilter, array('id'));
        if (sizeof($aFavoritesID) != sizeof($aFavoritesExists)) {
            if (User::id()) {
                $aDeleteID = array();
                foreach ($aFavoritesID as $v) {
                    if (!array_key_exists($v, $aFavoritesExists)) $aDeleteID[] = $v;
                }
                if ( ! empty($aDeleteID)) {
                    $this->model->itemsFavDelete(User::id(), $aDeleteID);
                    User::counterSave('items_fav', sizeof($aFavoritesExists));
                }
            } else {
                Request::setCOOKIE(BBS_FAV_COOKIE, join('.', array_keys($aFavoritesExists)), 2);
            }
        }

        if ($f_c > 0) {
            $aFilter[':cat'] = array('(cat_id2 = :catID OR cat_id1 = :catID)', ':catID' => $f_c);
        }

        $nTotal = $this->model->itemsList($aFilter, true);
        if ($nTotal > 0) {
            $oPgn = new Pagination($nTotal, $aData['pgn_pp'][$f_pp]['c'], '?' . Pagination::PAGE_PARAM);
            $aData['items'] = $this->model->itemsList($aFilter, false, $oPgn->getLimitOffset(), 'publicated_order DESC');
            $aData['pgn'] = $oPgn->view(array(), tpl::PGN_COMPACT);
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $this->searchList($aData['device'], $f_lt, $aData['items']),
                    'pgn'  => $aData['pgn']
                )
            );
        }

        $aCats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        $aData['cat_active'] = $aCats[0];
        if ($nTotal > 0) {
            unset($aFilter[':cat']);
            $aCats += $this->model->itemsListCategories($aFilter, 1);
            $aCatsSub = $this->model->itemsListCategories($aFilter, 2);
            if (sizeof($aCats) + sizeof($aCatsSub) < 11) {
                foreach ($aCatsSub as $v) {
                    if (isset($aCats[$v['pid']])) {
                        $aCats[$v['pid']]['sub'][$v['id']] = $v;
                        if ($v['id'] == $f_c) {
                            $aData['cat_active'] = $v;
                        }
                    }
                }
                unset($aCatsSub);
            }
        }

        $aData['f'] = & $f;
        $aData['cats'] = & $aCats;
        $aData['empty'] = !$nTotal;
        $aData['total'] = $nTotal;

        return $this->viewPHP($aData, 'my.favs');
    }

    /**
     * Профиль пользователя: Объявления
     * @param integer $userID ID пользователя
     * @param array $userData данные пользователя
     */
    public function user_items($userID, $userData)
    {
        $pageSize = config::sys('bbs.user.items.pagesize', 10);
        $data = array('items' => array(), 'pgn' => '', 'device' => bff::device());

        $f = $this->input->postgetm(array(
                'c'    => TYPE_UINT, # ID категории
                'page' => TYPE_UINT, # страница
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f['lt'] = self::LIST_TYPE_LIST;

        $filter = array('user_id' => $userID, 'status' => self::STATUS_PUBLICATED);
        if (static::premoderation()) $filter[':mod'] = 'moderated > 0';
        if (bff::shopsEnabled()) $filter[':shop'] = 'shop_id = 0';

        if ($f_c > 0) {
            $filter[':cat'] = array('(cat_id2 = :catID OR cat_id1 = :catID)', ':catID' => $f_c);
        }

        $total = $this->model->itemsList($filter, true);
        if ($total > 0) {
            $pgn = new Pagination($total, $pageSize, array(
                'link'  => $userData['profile_link'],
                'query' => array('page' => $f['page'], 'c' => $f['c']),
            ));
            $f['page'] = $pgn->getCurrentPage();
            $data['items'] = $this->model->itemsList($filter, false, $pgn->getLimitOffset(), 'publicated_order DESC');
            $data['pgn'] = $pgn->view();
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $this->searchList($data['device'], $f['lt'], $data['items']),
                    'pgn'  => $data['pgn']
                )
            );
        }

        # SEO: Объявления пользователя
        $this->urlCorrection($userData['profile_link']);
        $this->seo()->robotsIndex(!$f_c);
        $this->seo()->canonicalUrl($userData['profile_link_dynamic'], array('page' => $f['page']));
        $this->setMeta('user-items', array(
                'name'   => $userData['name'],
                'region' => ($userData['region_id'] ? $userData['region_title'] : ''),
                'country' => ($userData['reg1_country'] ? $userData['country_title'] : ''),
                'page'   => $f['page'],
            )
        );

        # категории
        $cats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        $data['cat_active'] = $cats[0];
        if ($total > 0) {
            unset($filter[':cat']);
            $cats += $this->model->itemsListCategories($filter, 1);
            $catsSub = $this->model->itemsListCategories($filter, 2);
            if (sizeof($cats) + sizeof($catsSub) < 11) {
                foreach ($catsSub as $v) {
                    if (isset($cats[$v['pid']])) {
                        $cats[$v['pid']]['sub'][$v['id']] = $v;
                        if ($v['id'] == $f_c) {
                            $data['cat_active'] = $v;
                        }
                    }
                }
                unset($catsSub);
            }
        }

        $data['f'] = & $f;
        $data['cats'] = & $cats;
        $data['empty'] = !$total;

        return $this->viewPHP($data, 'user.items');
    }

    /**
     * Страница магазина: Объявления
     * @param integer $shopID ID магазина
     * @param array $shopData данные магазина
     */
    public function shop_items($shopID, $shopData)
    {
        $pageSize = config::sys('bbs.shop.items.pagesize', 10);
        $data = array('items' => array(), 'pgn' => '', 'device' => bff::device());

        $f = $this->input->postgetm(array(
                'c'    => TYPE_UINT, # ID категории
                'page' => TYPE_UINT, # страница
            )
        );
        extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');
        $f['lt'] = self::LIST_TYPE_LIST;

        $filter = array('shop_id' => $shopID, 'status' => self::STATUS_PUBLICATED);
        if (static::premoderation()) $filter[':mod'] = 'moderated > 0';

        if ($f_c > 0) {
            $filter[':cat'] = array('(cat_id2 = :catID OR cat_id1 = :catID)', ':catID' => $f_c);
        }

        $total = $this->model->itemsList($filter, true);
        if ($total > 0) {
            $pgn = new Pagination($total, $pageSize, array(
                'link'  => $shopData['link'],
                'query' => array('page' => $f['page'], 'c' => $f['c']),
            ));
            $f['page'] = $pgn->getCurrentPage();
            $data['items'] = $this->model->itemsList($filter, false, $pgn->getLimitOffset(), 'publicated_order DESC');
            $data['pgn'] = $pgn->view();
        }

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $this->searchList($data['device'], $f['lt'], $data['items']),
                    'pgn'  => $data['pgn']
                )
            );
        }

        # Категории
        $cats = array(0 => array('id' => 0, 'title' => _t('bbs', 'Все категории')));
        $data['cat_active'] = $cats[0];
        if ($total > 0) {
            unset($filter[':cat']);
            $cats += $this->model->itemsListCategories($filter, 1);
            $catsSub = $this->model->itemsListCategories($filter, 2);
            if (sizeof($cats) + sizeof($catsSub) < 11) {
                foreach ($catsSub as $v) {
                    if (isset($cats[$v['pid']])) {
                        $cats[$v['pid']]['sub'][$v['id']] = $v;
                        if ($v['id'] == $f_c) {
                            $data['cat_active'] = $v;
                        }
                    }
                }
                unset($catsSub);
            }
        }

        # SEO: Страница магазина (с владельцем)
        $this->urlCorrection($shopData['link']);
        $this->seo()->robotsIndex(!$f_c);
        $this->seo()->canonicalUrl($shopData['link_dynamic'], array('page' => $f['page']));
        $this->seo()->setPageMeta('shops', 'shop-view', array(
                'title'       => $shopData['title'],
                'description' => tpl::truncate($shopData['descr'], 150),
                'region'      => ($shopData['region_id'] ? $shopData['region_title'] : ''),
                'country'     => (!empty($shopData['country']['title']) ? $shopData['country']['title'] : ''),
                'page'        => $f['page'],
            ), $shopData
        );
        $this->seo()->setSocialMetaOG($shopData['share_title'], $shopData['share_description'], $shopData['logo'], $shopData['link'], $shopData['share_sitename']);

        $data['f'] = & $f;
        $data['cats'] = & $cats;
        $data['empty'] = !$total;

        return $this->viewPHP($data, 'shop.items');
    }

    /**
     * Список категорий для страницы "Карта сайта"
     */
    public function catsListSitemap()
    {
        $iconSize = BBSCategoryIcon::SMALL;
        $aData = $this->model->catsListSitemap($iconSize);
        if (!empty($aData)) {
            foreach ($aData as &$v) {
                $v['link'] = static::url('items.search', array('keyword' => $v['keyword']));
            }
            unset($v);

            $aData = $this->db->transformRowsToTree($aData, 'id', 'pid', 'subs');

            $oIcon = $this->categoryIcon(0);
            foreach ($aData as &$v) {
                $v['icon'] = $oIcon->url($v['id'], $v['icon'], $iconSize);
            }
            unset($v);
        }

        return $aData;
    }

    public function ajax()
    {
        $nUserID = User::id();
        $aResponse = array();
        switch ($this->input->getpost('act', TYPE_STR)) {
            # форма добавления/редактирования (в зависимости от настроек категорий)
            case 'item-form-cat':
            {
                $nCategoryID = $this->input->post('id', TYPE_UINT);
                $aResponse['id'] = $nCategoryID;

                $aData = $this->itemFormByCategory($nCategoryID);
                if (empty($aData)) {
                    $this->errors->unknownRecord();
                    break;
                } else {
                    $aData['types'] = HTML::selectOptions($aData['types'], 0, false, 'id', 'title');
                }
                $aResponse = array_merge($aData, $aResponse);

            }
            break;
            # стоимость услуг для формы добавления/редактирования
            case 'item-form-svc-prices':
            {
                if ( ! bff::servicesEnabled()) {
                    $aResponse['prices'] = array();
                    break;
                }
                $nCategoryID = $this->input->post('cat', TYPE_UINT);
                $nCityID = $this->input->post('city', TYPE_UINT);

                $aSvcData = $this->model->svcData();
                $aSvcPrices = $this->model->svcPricesEx(array_keys($aSvcData), $nCategoryID, $nCityID);
                foreach ($aSvcData as $k => $v) {
                    if (empty($aSvcPrices[$k]) || $aSvcPrices[$k] <= 0) {
                        $aSvcPrices[$k] = $v['price'];
                    }
                }
                $aResponse['prices'] = $aSvcPrices;
            }
            break;
            # дин. свойства: child-свойства
            case 'dp-child':
            {
                $p = $this->input->postm(array(
                        'dp_id'       => TYPE_UINT, # ID parent-дин.свойства
                        'dp_value'    => TYPE_UINT, # ID выбранного значения parent-дин.свойства
                        'name_prefix' => TYPE_NOTAGS, # Префикс для name
                        'search'      => TYPE_BOOL, # true - форма поиска ОБ, false - форма доб/ред ОБ
                        'format'      => TYPE_STR, # требуемый формат: 'f-desktop', 'f-phone', ''
                    )
                );

                if (empty($p['dp_id']) && empty($p['dp_value'])) {
                    $this->errors->impossible();
                } else {
                    $bFilter = (!empty($p['format']));
                    $aData = $this->dp()->formChildByParentIDValue($p['dp_id'], $p['dp_value'], array('name' => $p['name_prefix']), $p['search'], ($bFilter ? false : 'form.child'));
                    if ($bFilter && !empty($aData)) {
                        switch ($p['format']) {
                            case 'f-desktop':
                            case 'f-phone':
                            {
                                $aResponse = array(
                                    'id'    => $aData['id'],
                                    'df'    => $aData['data_field'],
                                    'multi' => $aData['multi']
                                );
                            }
                            break;
                        }
                    } else {
                        $aResponse['form'] = $aData;
                    }
                }
            }
            break;
            # просмотр контактов объявления
            case 'item-contacts':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);

                if (!$nItemID || !$this->security->validateToken(true, false)) {
                    $this->errors->reloadPage();
                    break;
                }

                $aData = $this->model->itemData($nItemID,
                    array('user_id', 'shop_id', 'status', 'moderated', 'deleted', 'views_today', 'phones', 'skype', 'icq')
                );
                # неверно указан ID объявления
                if (empty($aData)) {
                    $this->errors->reloadPage();
                }
                # объявление не опубликовано / удалено
                $isOwner = User::isCurrent($aData['user_id']);
                $isModeration = static::moderationUrlKey($nItemID, $this->input->post('mod', TYPE_STR));
                if (($aData['status'] != self::STATUS_PUBLICATED && !($isModeration || $isOwner)) || $aData['deleted']) {
                    $this->errors->reloadPage();
                }
                # объявление непромодерировано (при включенной "премодерации")
                if (!$aData['moderated'] && static::premoderation() && !($isModeration || $isOwner)) {
                    $this->errors->reloadPage();
                }
                # накручиваем статистику для всех кроме владельца объявления и модератора
                if (!$isModeration && !$isOwner) {
                    $this->model->itemViewsIncrement($nItemID, 'contacts', $aData['views_today']);
                }
                # подставляем контактные данные из профиля
                if ($this->getItemContactsFromProfile())
                {
                    if ($aData['shop_id']) {
                        $contactsData = Shops::model()->shopData($aData['shop_id'], array('phones','skype','icq'));
                    } else {
                        $contactsData = Users::model()->userData($aData['user_id'], array('phones','phone_number','phone_number_verified','skype','icq'));
                        if (Users::registerPhoneContacts() && $contactsData['phone_number'] && $contactsData['phone_number_verified']) {
                            array_unshift($contactsData['phones'], array('v'=>$contactsData['phone_number'],'m'=>mb_substr($contactsData['phone_number'], 0, 2) . 'x xxx xxxx'));
                        }
                    }
                    if ( ! empty($contactsData)) {
                        foreach ($contactsData as $k=>$v) {
                            $aData[$k] = $v;
                        }
                    }
                }
                if (!empty($aData['phones'])) {
                    if (!bff::deviceDetector(bff::DEVICE_PHONE)) {
                        $aPhones = array();
                        foreach ($aData['phones'] as $v) $aPhones[] = $v['v'];
                        $aResponse['phones'] = '<span><img src="' . Users::contactAsImage($aPhones) . '" /></span>';
                    } else {
                        $htmlPhones = '<span>'; $i = 1;
                        foreach ($aData['phones'] as $v) {
                            $phone = HTML::obfuscate($v['v']);
                            $htmlPhones .= '<a href="tel:'.$phone.'">'.$phone.'</a>';
                            if ($i++ < sizeof($aData['phones'])) {
                                $htmlPhones .= ', ';
                            }
                        }
                        $htmlPhones .= '</span>';
                        $aResponse['phones'] = $htmlPhones;
                    }
                }
                if (!empty($aData['skype'])) {
                    $sSkype = HTML::obfuscate($aData['skype']);
                    $aResponse['skype'] = '<a href="skype:' . $sSkype . '?call">' . $sSkype . '</a>';
                }
                if (!empty($aData['icq'])) {
                    $aResponse['icq'] = HTML::obfuscate($aData['icq']);
                }
            }
            break;
            # избранные объявления (добавление / удаление) - для авторизованных
            # для неавторизованных - процесс выполняется средствами javascript+cookie
            case 'item-fav':
            {
                $nItemID = $this->input->post('id', TYPE_UINT);
                if (!$nItemID || !$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $aFavoritesID = $this->getFavorites($nUserID);
                if (in_array($nItemID, $aFavoritesID)) {
                    $this->model->itemsFavDelete($nUserID, $nItemID);
                    $aResponse['added'] = false;
                } else {
                    $this->model->itemsFavSave($nUserID, array($nItemID));
                    $aResponse['added'] = true;
                }
                $aResponse['cnt'] = $this->getFavorites($nUserID, true);

                # актулизируем счетчик избранных ОБ пользователя
                User::counterSave('items_fav', $aResponse['cnt']);
            }
            break;
            # смена статуса объявления
            case 'item-status':
            {
                $nItemID = $this->input->postget('id', TYPE_UINT);
                $bFrom = $this->input->postget('form', TYPE_BOOL);
                if ($bFrom) {
                    $this->security->setTokenPrefix('bbs-item-form');
                }
                if (!$nItemID || !$nUserID || !$this->security->validateToken()) {
                    $this->errors->reloadPage();
                    break;
                }

                $aData = $this->model->itemData($nItemID, array(
                        'user_id',
                        'status',
                        'deleted',
                        'publicated_to',
                        'publicated_order'
                    )
                );
                if (empty($aData)) {
                    $this->errors->reloadPage();
                    break;
                }
                if (!$this->isItemOwner($nItemID, $aData['user_id'])) {
                    $this->errors->set(_t('bbs', 'Вы не является владельцем данного объявления'));
                    break;
                }

                switch ($this->input->getpost('status', TYPE_STR)) {
                    case 'unpublicate':
                    { # снятие с публикации

                        if ($aData['status'] != self::STATUS_PUBLICATED) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $res = $this->model->itemSave($nItemID, array(
                                'status'        => self::STATUS_PUBLICATED_OUT,
                                'status_prev'   => $aData['status'],
                                'publicated_to' => $this->db->now(),
                            )
                        );
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Объявления было успешно снято с публикации');
                        }
                    }
                    break;
                    case 'publicate':
                    { # публикация
                        if ($aData['status'] != self::STATUS_PUBLICATED_OUT) {
                            $this->errors->reloadPage();
                            break;
                        }
                        $aUpdate = array(
                            'status'        => self::STATUS_PUBLICATED,
                            'status_prev'   => $aData['status'],
                            'publicated'    => $this->db->now(),
                            'publicated_to' => $this->getItemPublicationPeriod(), # от текущей даты
                        );
                        /**
                         * Обновляем порядок публикации (поднимаем наверх)
                         * только в случае если разница между датой publicated_order и текущей более 7 дней
                         * т.е. тем самым закрываем возможность бесплатного поднятия за счет
                         * процедуры снятия с публикации => возобновления публикации (продления)
                         */
                        if ((time() - strtotime($aData['publicated_order'])) >= (86400 * 7)) {
                            $aUpdate['publicated_order'] = $this->db->now();
                        }
                        $res = $this->model->itemSave($nItemID, $aUpdate);
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Объявления было успешно опубликовано');
                        }
                    }
                    break;
                    case 'refresh':
                    { # продление публикации
                        if ($aData['status'] != self::STATUS_PUBLICATED) {
                            $this->errors->reloadPage();
                            break;
                        }

                        # от даты завершения публикации
                        $res = $this->model->itemSave($nItemID, array(
                                'publicated_to' => $this->getItemRefreshPeriod($aData['publicated_to']),
                            )
                        );
                        if (empty($res)) $this->errors->reloadPage();
                        else {
                            $aResponse['message'] = _t('bbs', 'Срок публикации объявления был успешно продлен');
                        }
                    }
                    break;
                    case 'delete': # удаление
                    {

                        if ($aData['status'] == self::STATUS_PUBLICATED) {
                            $this->errors->set(_t('bbs', 'Для возможности удаления объявления, необходимо снять его с публикации'));
                            break;
                        }
                        $aResponse['message'] = _t('bbs', 'Объявления было успешно удалено');
                        $aResponse['redirect'] = static::url('my.items');
                        if ($aData['deleted']) break;
                        $res = $this->model->itemSave($nItemID, array(
                                # помечаем как удаленное
                                'deleted'       => 1,
                                # снимаем с публикации
                                'status'        => self::STATUS_PUBLICATED_OUT,
                                'status_prev'   => $aData['status'],
                                'publicated_to' => $this->db->now(),
                            )
                        );
                        if (empty($res)) {
                            $this->errors->set(_t('bbs', 'Неудалось удалить объявление, возможно данное объявление уже удалено'));
                        }
                    }
                    break;
                }
            }
            break;
            default:
            {
                $this->errors->impossible();
            }
            break;
        }

        $this->ajaxResponseForm($aResponse);
    }

    public function itemsCronStatus()
    {
        if (!bff::cron()) return;

        # 1. Актуализация статуса объявлений
        $this->model->itemsCronStatus();

        # 2. Уведомление о скором завершении публикации объявлений
        $this->itemsCronUnpublicateSoon();

        # 3. Полное удаление объявлений
        $this->model->itemsCronDelete();
    }

    public function itemsCronViews()
    {
        if (!bff::cron()) return;

        $this->model->itemsCronViews();
    }

    protected function itemsCronUnpublicateSoon()
    {
        if (!bff::cron())
            return;

        $days = func::unserialize(config::get('bbs_item_unpublicated_soon'));
        # уведомления были выключены в настройках
        if (empty($days)) {
            return;
        }
        # кол-во отправляемых объявлений за подход
        $limit = config::get('bbs_item_unpublicated_soon_messages', 0);
        if ($limit<=0) $limit = 100;
        if ($limit>300) $limit = 300;

        $now = date('Y-m-d');

        # очистка списка отправленных за предыдущие дни
        $last = config::get('bbs_item_unpublicated_soon_last_enotify');
        if ($last != $now) {
            config::save('bbs_item_unpublicated_soon_last_enotify', $now);
            $this->model->itemsCronUnpublicateClearLast($last);
        }

        # получаем объявления у которых завершается срок публикации,
        # до завершения осталось {$days} дней (варианты)
        $items = $this->model->itemsCronUnpublicateSoon($days, $limit, $now);
        if (empty($items))
            return;

        $mail = new CMail();
        $tpl = Sendmail::i()->getMailTemplate('bbs_item_unpublicated_soon', array());
        $tplTags = Sendmail::i()->getTags();

        foreach ($items as &$item) {
            # помечаем в таблице отправленных за сегодня (если еще нет)
            if ($this->model->itemsCronUnpublicateSended($item['item_id'], $now))
                continue;

            $item['days_in'] = tpl::declension($item['days'], _t('', 'день;дня;дней'));
            $item['item_link'] = static::urlDynamic($item['item_link']);
            $auth = $this->userAuthHash($item);
            $item['publicate_link'] = $item['item_link'].'?auth='.$auth;
            $item['svc_up']      = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_UP));
            $item['svc_quick']   = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_QUICK));
            $item['svc_fix']     = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_FIX));
            $item['svc_mark']    = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_MARK));
            $item['svc_press']   = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_PRESS));
            $item['svc_premium'] = static::url('item.promote', array('id' => $item['item_id'], 'auth' => $auth, 'svc' => static::SERVICE_PREMIUM));

            $replace = array();
            foreach ($item as $k => $v) {
                $replace[$tplTags[0] . $k . $tplTags[1]] = $v;
            }

            $tplData = $tpl;
            $tplData['body'] = strtr($tplData['body'], $replace);
            $tplData['subject'] = strtr($tplData['subject'], $replace);

            $mail->Subject = $tplData['subject'];
            $mail->AltBody = '';
            $mail->MsgHTML($tplData['body']);
            $mail->clearAddresses();
            $mail->AddAddress($item['email']);

            $mail->Send();
        }
        unset($item);
    }

    /**
     * Импорт объявлений (cron)
     * Рекомендуемый период: раз в 7 минут
     */
    public function itemsCronImport()
    {
        if (!bff::cron()) return;

        $this->itemsImport()->importCron();
    }

    /**
     * Комментарии ОБ
     * @param array $aData
     * @return mixed
     */
    public function comments(array $aData = array())
    {
        $nUserID = User::id();
        $oComments = $this->itemComments();

        if (Request::isAJAX())
        {
            $aResponse = array();
            switch ($this->input->getpost('act', TYPE_STR)) {
                case 'add': # комментарии: добавление
                {
                    if (!$this->security->validateToken(false)) {
                        $this->errors->reloadPage();
                        break;
                    }


                    $sMessage = $this->input->post('message', TYPE_NOTAGS);
                    $sMessage = $oComments->validateMessage($sMessage, false);
                    if (mb_strlen($sMessage) < 5) {
                        $this->errors->set(_t('comments', 'Комментарий не может быть короче 5 символов'), 'message');
                        break;
                    }

                    $nItemID = $this->input->post('item_id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aItemData = $this->model->itemData($nItemID, array('status', 'user_id'));
                    if (empty($aItemData)) {
                        $this->errors->reloadPage();
                    }
                    if ($aItemData['status'] != static::STATUS_PUBLICATED) {
                        $this->errors->impossible();
                        break;
                    }

                    # не чаще чем раз в {X} секунд с одного IP (для одного пользователя)
                    if (Site::i()->preventSpam('bbs-comment', 10)) {
                        break;
                    }

                    # ID комментария на который отвечаем или 0
                    $nParent = $this->input->post('parent', TYPE_UINT);
                    $aData = array(
                        'message' => $sMessage,
                        'name' => User::data('name'),
                    );
                    $nCommentID = $oComments->commentInsert($nItemID, $aData, $nParent);
                    if ($nCommentID) {
                        # оставили комментарий
                        $aResponse['premod'] = $oComments->isPreModeration();
                        if (!$aResponse['premod']) {
                            $aComment = $oComments->commentData($nItemID, $nCommentID);
                            $aComment['login']  = User::data('login');
                            $aComment['sex']    = User::data('sex');
                            $aComment['avatar'] = User::data('avatar');
                            $aResponse['html'] = $this->commentsList(array(
                                'comments'   => array($aComment),
                                'itemID'     => $nItemID,
                                'itemUserID' => $aItemData['user_id'],
                                'itemStatus' => $aItemData['status'],
                            ));
                        }
                    }
                }   break;
                case 'delete': # комментарии: удаление
                {
                    if (!$this->security->validateToken(false, true)) {
                        $this->errors->reloadPage();
                        break;
                    }


                    $nCommentID = $this->input->post('id', TYPE_UINT);
                    if (!$nCommentID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $nItemID = $this->input->post('item_id', TYPE_UINT);
                    if (!$nItemID) {
                        $this->errors->reloadPage();
                        break;
                    }

                    $aItemData = $this->model->itemData($nItemID, array('status', 'user_id'));
                    if (empty($aItemData)) {
                        $this->errors->reloadPage();
                        break;
                    }

                    if ($aItemData['status'] != static::STATUS_PUBLICATED) {
                        $this->errors->impossible();
                        break;
                    }

                    $aCommentData = $oComments->commentData($nItemID, $nCommentID);
                    if (empty($aCommentData)) {
                        $this->errors->reloadPage();
                        break;
                    }
                    if ($aCommentData['user_id'] == $nUserID) { # владелец комментария
                        $oComments->commentDelete($nItemID, $nCommentID, BBSItemComments::commentDeletedByCommentOwner);
                    }else{
                        $this->errors->reloadPage();
                        break;
                    }

                    $aCommentsData = $oComments->commentsDataFrontend($nItemID, $nCommentID);
                    if ($aCommentsData['total'] > 0) {
                        $aResponse['html'] = $this->commentsList(array(
                            'comments'   => $aCommentsData['comments'],
                            'itemID'     => $nItemID,
                            'itemUserID' => $aItemData['user_id'],
                            'itemStatus' => $aItemData['status'],
                        ));
                    } else {
                        $aResponse['html'] = '';
                    }
                }  break;
                default:
                    $this->errors->impossible();
            }
            $this->ajaxResponseForm($aResponse);
        }

        $aCommentsData = $oComments->commentsDataFrontend($aData['itemID']);
        $aData['comments'] = $this->commentsList(array(
            'comments'   => $aCommentsData['comments'],
            'itemID'     => $aData['itemID'],
            'itemUserID' => $aData['itemUserID'],
            'itemStatus' => $aData['itemStatus'],
        ));
        $aData['commentsTotal'] = $aCommentsData['total'];
        return $this->viewPHP($aData, 'item.comments');
    }

    /**
     * Вывод списка комментариев, рекурсивно
     * @param array $aData
     * @return string
     */
    public function commentsList($aData)
    {
        return $this->viewPHP($aData, 'item.comments.ajax');
    }

    /**
     * Авторизация пользователя по GET параметру "auth"
     * @param integer $userID ID предполагаемого пользователя
     * @return int $userID или 0
     */
    protected function userAuthGetParam($userID)
    {
        if (!$userID) return 0;

        $hash = $this->input->get('auth', TYPE_STR);
        if (!$hash) return 0;

        $userData = Users::model()->userData($userID, array('user_id', 'user_id_ex', 'last_login', 'password'));
        if (empty($userData)) return 0;

        if (mb_strtolower($hash) === $this->userAuthHash($userData)) {
            if (Users::i()->userAuth($userID, 'user_id', $userData['password']) === true) {
                return $userID;
            }
        }

        return 0;
    }

}