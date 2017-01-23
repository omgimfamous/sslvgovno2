<?php

/**
 * Права доступа группы:
 *  - sitemap: Карта сайта
 *      - listing: Cписок разделов
 *      - edit: Управление разделами
 */

class SitemapModule extends SitemapModuleBase
{
    public function listing()
    {
        if (!$this->haveAccessTo('listing')) {
            return $this->showAccessDenied();
        }

        if (Request::isAJAX()) {
            if (!$this->haveAccessTo('edit')) {
                return $this->showAccessDenied();
            }

            switch ($this->input->get('act', TYPE_STR)) {
                case 'rotate':
                {
                    $res = $this->model->itemsRotate();
                    if ($res) {
                        $this->resetCache();
                        $this->ajaxResponse(Errors::SUCCESS);
                    }
                }
                break;
                case 'toggle':
                {
                    $nRecordID = $this->input->postget('rec', TYPE_UINT);
                    if (!$nRecordID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $this->model->itemToggle($nRecordID);

                    $this->resetCache();

                    $this->ajaxResponse(Errors::SUCCESS);
                }
                break;
            }
            $this->ajaxResponse(Errors::IMPOSSIBLE);
        }

        $aData = array('items' => array(), 'mid' => 0);

        $nMenuID = $this->input->get('mid', TYPE_UINT);

        $aMenu = $this->model->itemsListingMenu();

        if (!empty($aMenu)) {
            if (!isset($aMenu[$nMenuID])) {
                $nMenuID = key($aMenu);
            }
            $aMenu[$nMenuID]['active'] = 1;
            $aData['mid'] = $nMenuID;

            $aData['items'] = $this->model->itemsListing($aMenu[$nMenuID]['numleft'], $aMenu[$nMenuID]['numright']);
        }

        $aData['menu'] = $aMenu;
        tpl::includeJS(array('tablednd'), true);

        return $this->viewPHP($aData, 'admin.listing', $this->module_dir_tpl_core);
    }

    public function add()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $aData = $this->input->postm(array(
                'type'          => TYPE_UINT,
                'keyword'       => TYPE_NOTAGS,
                'target'        => TYPE_STR,
                'style'         => TYPE_STR,
                'link'          => TYPE_NOTAGS,
                'changefreq'    => TYPE_STR,
                'priority'      => TYPE_STR,
                'is_system'     => TYPE_BOOL,
                'allow_submenu' => TYPE_BOOL,
            )
        );
        $this->input->postm_lang($this->model->langItems, $aData);

        $sRedirect = '&mid=' . $this->input->get('mid', TYPE_UINT);

        $nParentID = $this->input->postget('pid', TYPE_UINT);
        $aParentData = array();
        if ($nParentID > 0) {
            $aParentData = $this->model->itemData($nParentID, false);
            if (!FORDEV && !$aParentData['pid']) {
                $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
            }
        } else {
            if (!FORDEV) {
                $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
            }
        }

        if (Request::isPOST()) {
            $this->checkType($aData['type']);
            $this->checkTarget($aData['target']);

            if (!FORDEV && $nParentID <= self::ROOT_ID) {
                $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
            }

            if (!FORDEV) {
                unset($aData['is_system'], $aData['allow_submenu']);
            }

            switch ($aData['type']) {
                case self::typeMenu:
                {
                    if (FORDEV && $nParentID == self::ROOT_ID) {
                        $aData['is_system'] = 1;
                    }

                    if (!$aParentData['allow_submenu']) {
                        $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
                    }
                }
                break;
                case self::typeLinkModuleMethod:
                {
                    $aData['is_system'] = 1;
                }
                break;
                case self::typePage:
                {
                    $nPageID = $this->input->post('page_id', TYPE_UINT);
                    $aPageData = bff::model('Site')->pageDataForSitemap($nPageID);
                    if (empty($aPageData)) {
                        $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
                    }

                    $aData['link'] = '/' . $aPageData['filename'] . Site::$pagesExtension;
                }
                break;
                case self::typeLink:
                {
                }
                break;
            }

            if (isset($aData['link'])) {
                $aData['link'] = $this->checkLink($aData['link'], $aData['type'] != self::typeMenu);
            }

            if (FORDEV) {
                if (empty($aData['keyword']) && $aData['type'] == self::typeMenu) {
                    $this->errors->set('Укажите keyword');
                } else {
                    if (array_key_exists($aData['keyword'], $aData) || in_array($aData['keyword'],
                            array(
                                'id',
                                'pid',
                                'keyword',
                                'link',
                                'type',
                                'style',
                                'title',
                                'mtitle',
                                'mkeywords',
                                'mdescription',
                                'a',
                                'sub'
                            )
                        )
                    ) {
                        $this->errors->set('Указанный keyword не может быть использован, укажите другой');
                    }
                }
            }

            if ($this->errors->no()) {
                $nItemID = $this->model->itemCreate($nParentID, $aParentData, $aData);
                if ($nItemID > 0) {
                    $this->resetCache();
                    $this->adminRedirect(Errors::SUCCESS . $sRedirect);
                }

                $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
            }
        }

        $aData['parent'] = $aParentData;

        if (FORDEV) {
            $aData['pid_options'] = $this->model->itemParentsOptions($nParentID);
        } else {
            $aData['pid_options'] = $this->model->itemParentsPath($nParentID);
        }

        $aPages = bff::model('Site')->pagesListing();
        $aData['pages_options'] = HTML::selectOptions($aPages, $this->input->postget('page_id', TYPE_UINT), 'выбрать страницу', 'id', 'title');

        $aData['target_options'] = $this->getTargets(true, $aData['target']);

        return $this->viewPHP($aData, 'admin.add', $this->module_dir_tpl_core);
    }

    public function edit()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $sRedirect = '&mid=' . $this->input->get('mid', TYPE_UINT);

        $nRecordID = $this->input->postget('id', TYPE_UINT);
        if (!$nRecordID) {
            $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
        }

        $aData = $this->model->itemData($nRecordID, true);
        if (empty($aData)) {
            $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
        }

        if (!FORDEV && $aData['pid'] == self::ROOT_ID) {
            return $this->showAccessDenied();
        }

        if (Request::isPOST()) {
            $aInput = array(
                'target'        => TYPE_STR,
                'changefreq'    => TYPE_STR,
                'priority'      => TYPE_STR,
                'is_system'     => TYPE_BOOL,
                'allow_submenu' => TYPE_BOOL,
            );

            if (FORDEV) {
                $aInput['style'] = TYPE_STR;
                $aInput['keyword'] = TYPE_STR;
            } else {
                unset($aInput['is_system']);
            }

            switch ($aData['type']) {
                case self::typeMenu:
                {
                    $aInput['link'] = TYPE_STR;
                }
                break;
                case self::typeLinkModuleMethod:
                {
                    if (FORDEV) {
                        $aInput['link'] = TYPE_STR;
                    }
                }
                break;
                case self::typePage:
                case self::typeLink:
                {
                    $aInput['link'] = TYPE_STR;
                }
            }

            if (!FORDEV || $aData['type'] != self::typeMenu) {
                unset($aInput['allow_submenu']);
            }

            $p = $this->input->postm($aInput);
            $this->input->postm_lang($this->model->langItems, $p);

            if (isset($p['keyword']) && empty($p['keyword']) && $aData['type'] == self::typeMenu) {
                $this->errors->set('Укажите keyword');
            }

            $this->checkTarget($p['target']);

            if (isset($p['link'])) {
                $p['link'] = $this->checkLink($p['link'], $aData['type'] != self::typeMenu);
            }

            if ($this->errors->no()) {
                $this->model->itemUpdate($nRecordID, $p);

                $this->resetCache();
                $this->adminRedirect(Errors::SUCCESS . $sRedirect);
            }
            $aData = array_merge($aData, $p);
        }

        $aData['pid_options'] = $this->model->itemParentsPath($aData['pid']);

        $aData = HTML::escape($aData, 'html', array('link', 'keyword', 'style'));
        $aData['target_options'] = $this->getTargets(true, $aData['target']);

        return $this->viewPHP($aData, 'admin.edit', $this->module_dir_tpl_core);
    }

    public function delete()
    {
        if (!$this->haveAccessTo('edit')) {
            return $this->showAccessDenied();
        }

        $sRedirect = '&mid=' . $this->input->get('mid', TYPE_UINT);

        $nRecordID = $this->input->postget('id', TYPE_UINT);
        if (!$nRecordID) {
            $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
        }

        $bSuccess = $this->model->itemDelete($nRecordID);
        if ($bSuccess) {
            $this->resetCache();
            $this->adminRedirect(Errors::SUCCESS . $sRedirect);
        }

        $this->adminRedirect(Errors::IMPOSSIBLE . $sRedirect);
    }

    public function dev_reset_cache()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }
        $this->resetCache();

        $this->adminRedirect(Errors::SUCCESS);
    }

    public function dev_treevalidate()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        return $this->model->tree->validate(true);
    }

    public function dev_clear()
    {
        if (!FORDEV) {
            return $this->showAccessDenied();
        }

        $this->model->itemsClear();

        $this->resetCache();

        if ($this->errors->no()) {
            $this->errors->success();
        }
    }
}