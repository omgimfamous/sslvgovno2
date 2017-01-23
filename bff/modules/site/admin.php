<?php

/**
 * Права доступа группы:
 *  - site-pages: Страницы
 *      - listing: Просмотр списка
 *      - manage: Управление страницами
 *  - site: Настройки сайта
 *      - settings: Общие настройки
 *      - counters: Счетчики
 *      - currencies: Валюты
 *      - instructions: Инструкции
 *      - seo: SEO
 */

class SiteModule extends SiteModuleBase
{
    # -------------------------------------------------------------------------
    # Страницы

    function pagesListing()
    {
        if (!$this->haveAccessTo('listing', 'site-pages')) {
            return $this->showAccessDenied();
        }

        switch ($this->input->postget('act', TYPE_STR)) {
            case 'delete':
            {
                if (!$this->haveAccessTo('manage', 'site-pages')) {
                    return $this->showAccessDenied();
                }

                $nPageID = $this->input->get('id', TYPE_UINT);
                if (!$nPageID) {
                    $this->adminRedirect(Errors::IMPOSSIBLE, 'pagesListing');
                }

                $mResult = $this->model->pageDelete($nPageID);
                $this->adminRedirect($mResult, 'pagesListing');
            }
            break;
        }

        $aData['pages'] = $this->model->pagesListing();

        return $this->viewPHP($aData, 'admin.pages.listing', $this->module_dir_tpl_core);
    }

    function pagesAdd()
    {
        if (!$this->haveAccessTo('manage', 'site-pages')) {
            return $this->showAccessDenied();
        }

        $this->validatePageData(0, $aData);

        if (Request::isPOST()) {
            if ($this->errors->no()) {
                $nPageID = $this->model->pageSave(0, $aData);
                if ($nPageID > 0) {
                    $this->adminRedirect(Errors::SUCCESS, 'pagesListing');
                }
            }
        }

        return $this->viewPHP($aData, 'admin.pages.form', $this->module_dir_tpl_core);
    }

    function pagesEdit()
    {
        if (!$this->haveAccessTo('manage', 'site-pages')) {
            return $this->showAccessDenied();
        }

        $nPageID = $this->input->get('id', TYPE_UINT);
        if (!$nPageID) {
            $this->adminRedirect(Errors::IMPOSSIBLE, 'pagesListing');
        }

        if (Request::isPOST()) {
            $this->validatePageData($nPageID, $aData);

            if ($this->errors->no()) {
                $this->model->pageSave($nPageID, $aData);

                $this->adminRedirect(Errors::SUCCESS, 'pagesListing');
            }
        } else {
            $aData = $this->model->pageData($nPageID);
        }

        return $this->viewPHP($aData, 'admin.pages.form', $this->module_dir_tpl_core);
    }

    function validatePageData($nPageID = 0, &$aData = array())
    {
        $aData['mtemplate'] = $this->input->post('mtemplate', TYPE_BOOL);

        $this->input->postm_lang($this->model->langPage, $aData);

        if (Request::isPOST()) {
            if (!$nPageID) {
                $sFilename = $this->input->post('filename', TYPE_NOTAGS);
                $sFilename = str_replace(array('.html', '.htm', '.php'), '', $sFilename);
                $sFilename = bff\utils\Files::cleanFilename($sFilename);

                if (!empty($sFilename) && $this->model->pageFilenameExists($sFilename)) {
                    $this->errors->set(_t('site', 'Указанное имя файла уже используется. Пожалуйста, укажите другое.'));
                }
                if (empty($sFilename)) {
                    $this->errors->set(_t('site', 'Укажите имя файла'));
                }

                $aData['filename'] = $sFilename;
            }
        } else {
            if (!$nPageID) {
                $aData['mtemplate'] = 1;
            }
        }

        if (FORDEV) {
            $aData['issystem'] = $this->input->post('issystem', TYPE_BOOL);
        }

        return $aData;
    }

    # -------------------------------------------------------------------------
    # Счетчики

    function counters()
    {
        if (!$this->haveAccessTo('counters')) {
            return $this->showAccessDenied();
        }

        $sAct = $this->input->postget('act', TYPE_STR);
        if (!empty($sAct) || Request::isPOST()) {
            $aResponse = array();
            switch ($sAct) {
                case 'add':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $aData = $this->validateCounterData(0, $bSubmit);
                    if ($bSubmit) {
                        if ($this->errors->no()) {
                            $nCounterID = $this->model->counterSave(0, $aData);
                            if ($nCounterID > 0) {
                            }
                        }
                    }

                    $aData['id'] = 0;

                    $aResponse['form'] = $this->viewPHP($aData, 'admin.counters.form', $this->module_dir_tpl_core);
                }
                break;
                case 'edit':
                {
                    $bSubmit = $this->input->post('save', TYPE_BOOL);
                    $nCounterID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCounterID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    if ($bSubmit) {
                        $aData = $this->validateCounterData($nCounterID, $bSubmit);
                        if ($this->errors->no()) {
                            $this->model->counterSave($nCounterID, $aData);
                        }
                        $aData['id'] = $nCounterID;
                    } else {
                        $aData = $this->model->counterData($nCounterID, true);
                        if (empty($aData)) {
                            $this->errors->unknownRecord();
                            break;
                        }
                    }

                    $aResponse['form'] = $this->viewPHP($aData, 'admin.counters.form', $this->module_dir_tpl_core);
                }
                break;
                case 'toggle':
                {
                    $nCounterID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCounterID) {
                        $this->errors->unknownRecord();
                        break;
                    }

                    $sToggleType = $this->input->get('type', TYPE_STR);

                    $this->model->counterToggle($nCounterID, $sToggleType);
                }
                break;
                case 'rotate':
                {
                    $nTab = $this->input->post('tab', TYPE_INT);
                    switch ($nTab) {
                        case 0:
                        {
                            # сортировка "Все"
                            $this->model->countersRotate('num', '');
                        }
                        break;
                    }
                }
                break;
                case 'delete':
                {
                    $nCounterID = $this->input->postget('id', TYPE_UINT);
                    if (!$nCounterID) {
                        $this->errors->impossible();
                        break;
                    }

                    $aData = $this->model->counterData($nCounterID, true);
                    if (empty($aData)) {
                        $this->errors->impossible();
                        break;
                    }

                    $res = $this->model->counterDelete($nCounterID);
                    if (!$res) {
                        $this->errors->impossible();
                        break;
                    } else {
                    }
                }
                break;
                default:
                    $aResponse = false;
            }

            if ($aResponse !== false && Request::isAJAX()) {
                $this->ajaxResponseForm($aResponse);
            }
        }

        $f = array();
        $this->input->postgetm(array(
                'page' => TYPE_UINT,
                'tab'  => TYPE_INT,
            ), $f
        );

        # формируем фильтр списка счетчиков
        $sql = array();
        $sqlBind = array();
        $sqlOrder = '';
        $aData['pgn'] = '';

        switch ($f['tab']) {
            case 0: # Все
                $sqlOrder = 'num';
                break;
        }

        $aData['list'] = $this->model->countersListing($sql, false, $sqlBind, '', $sqlOrder);

        $aData['list'] = $this->viewPHP($aData, 'admin.counters.listing.ajax', $this->module_dir_tpl_core);

        if (Request::isAJAX()) {
            $this->ajaxResponseForm(array(
                    'list' => $aData['list'],
                    'pgn'  => $aData['pgn'],
                )
            );
        }

        $aData['f'] = $f;
        $aData['id'] = $this->input->get('id', TYPE_UINT);
        $aData['act'] = $sAct;

        tpl::includeJS(array('tablednd'), true);

        return $this->viewPHP($aData, 'admin.counters.listing', $this->module_dir_tpl_core);
    }

    /**
     * Обрабатываем параметры запроса
     * @param int $nCounterID ID счетчика или 0
     * @param bool $bSubmit выполняем сохранение/редактирование
     * @return array параметры
     */
    function validateCounterData($nCounterID, $bSubmit)
    {
        $aData = array();
        $this->input->postm(array(
                'title'   => TYPE_STR, # Название
                'code'    => TYPE_STR, # Код счетчика
                'enabled' => TYPE_BOOL, # Включен
            ), $aData
        );

        if ($bSubmit) {

        }

        return $aData;
    }

    # -------------------------------------------------------------------------
    # Валюты

    function currencies()
    {
        if (!$this->haveAccessTo('currencies')) {
            return $this->showAccessDenied();
        }

        if (Request::isAJAX()) {
            switch ($this->input->get('act')) {
                case 'edit':
                {
                    $nCurrencyID = $this->input->get('curr_id', TYPE_UINT);
                    if (!$nCurrencyID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $aData = $this->model->currencyData($nCurrencyID, true);
                    if (empty($aData)) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    $aData = array('form' => $this->viewPHP($aData, 'admin.currencies.form', $this->module_dir_tpl_core));
                    $this->ajaxResponse($aData);
                }
                break;
                case 'toggle':
                {
                    $nCurrencyID = $this->input->postget('rec', TYPE_UINT);
                    if (!$nCurrencyID) {
                        $this->ajaxResponse(Errors::UNKNOWNRECORD);
                    }

                    $this->model->currencyToggle($nCurrencyID);

                    $this->ajaxResponse(Errors::SUCCESS);
                }
                break;
                case 'rotate':
                {
                    $this->db->rotateTablednd(TABLE_CURRENCIES, '', 'id', 'num');
                    $this->ajaxResponse(Errors::SUCCESS);
                }
                break;
                case 'delete':
                {
                    $nCurrencyID = $this->input->get('curr_id', TYPE_UINT);
                    if (!$nCurrencyID) {
                        $this->ajaxResponse(Errors::IMPOSSIBLE);
                    }

                    $res = $this->model->currencyDelete($nCurrencyID);

                    $this->ajaxResponse(($res ? Errors::SUCCESS : Errors::IMPOSSIBLE));
                }
                break;
            }
        } else {
            if (Request::isPOST()) {
                switch ($this->input->postget('act')) {
                    case 'add-finish':
                    {
                        $aData = $this->validateCurrencyData(0);
                        if ($this->errors->no()) {
                            $this->model->currencySave(0, $aData);
                        }
                    }
                    break;
                    case 'edit-finish':
                    {
                        $nCurrencyID = $this->input->post('curr_id', TYPE_UINT);
                        if (!$nCurrencyID) {
                            $this->errors->unknownRecord();
                        }

                        $aData = $this->validateCurrencyData($nCurrencyID);
                        if ($this->errors->no()) {
                            $this->model->currencySave($nCurrencyID, $aData);
                        }
                    }
                    break;
                }

                $this->adminRedirect(Errors::SUCCESS, bff::$event);
            }
        }

        $aData['currencies'] = $this->model->currencyListing();

        $aDataForm = $this->validateCurrencyData(0);
        $aDataForm['id'] = 0;
        $aData['form'] = $this->viewPHP($aDataForm, 'admin.currencies.form', $this->module_dir_tpl_core);
        unset($aDataForm);

        tpl::includeJS(array('tablednd'), true);

        return $this->viewPHP($aData, 'admin.currencies.listing', $this->module_dir_tpl_core);
    }


    /**
     * Обрабатываем параметры валюты
     * @param int $nCurrencyID ID валюты или 0
     * @return array параметры валюты
     */
    protected function validateCurrencyData($nCurrencyID)
    {
        $aData = array();

        $this->input->postm_lang($this->model->langCurrencies, $aData);

        $this->input->postm(array(
                'keyword' => TYPE_NOTAGS, # keyword
                'rate'    => TYPE_UNUM, # курс, по отношению к основной валюте
                'enabled' => TYPE_BOOL, # включена ли валюта
            ), $aData
        );

        return $aData;
    }

    #---------------------------------------------------------------------------------------
    # Инструкции

    /**
     * Форма редактирования инструкций
     * @param array $aTabs табы
     * @return string
     */
    protected function instructionsForm(array $aTabs)
    {
        if (!$this->haveAccessTo('instructions')) {
            return $this->showAccessDenied();
        }

        if (Request::isAJAX()) {
            $aData = $this->input->post('instr', TYPE_ARRAY);
            $aDataParams = array();
            foreach ($aTabs as $tabInstr) {
                if (!empty($tabInstr) && is_array($tabInstr)) {
                    foreach ($tabInstr as $k => $v) {
                        $aDataParams[$k] = TYPE_STR;
                    }
                }
            }
            $this->input->clean_array($aData, $aDataParams);
            config::instructionSave($aData);
            $this->ajaxResponse(Errors::SUCCESS);
        }

        $aData = array(
            'tabs' => $aTabs,
            'data' => config::instruction(false)
        );
        tpl::includeJS('wysiwyg', true);

        return $this->viewPHP($aData, 'admin.instructions', $this->module_dir_tpl_core);
    }

}