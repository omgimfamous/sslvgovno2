<?php namespace bff\base;

/**
 * Базовый класс модуля
 * @abstract
 * @version 0.356
 * @modified 24.may.2015
 */

use \Component, \Errors, \bff, \config;
use bff\utils\Files;

abstract class Module extends Component
{
    /** @var string директория модуля */
    public $module_dir = '';
    /** @var string директория шаблонов модуля */
    public $module_dir_tpl = '';
    /** @var string директория шаблонов базового модуля (модуля ядра) */
    public $module_dir_tpl_core = '';
    /** @var string название модуля (например: 'users') */
    public $module_name = '';
    /** @var string название модуля (например: 'Пользователи') */
    public $module_title = '';
    /* @var \Model модель */
    public $model = null;
    /** @var array подключенные компоненты */
    protected $_c = array();

    /**
     * Инициализация модуля
     * @param string $sModuleName название модуля
     */
    public function initModule($sModuleName)
    {
        $this->module_name = mb_strtolower($sModuleName);
        if (empty($this->module_dir)) {
            $this->module_dir = PATH_MODULES . $sModuleName . DS;
        }
        $this->module_dir_tpl = $this->module_dir . 'tpl' . DS . 'def';
        $this->module_dir_tpl_core = PATH_CORE . 'modules' . DS . $this->module_name . DS . 'tpl' . DS . 'def';

        # инициализируем модель
        $sModel = $sModuleName . 'Model';
        if (!class_exists($sModel)) {
            $sModel = $sModel . 'Base';
        } # базовая модель
        if (class_exists($sModel)) {
            $this->model = new $sModel($this);
            $this->model->init();
        }
    }

    /**
     * Инициализация модуля в качестве компонента
     * @param string $sComponentName название компонента
     * @param string $sComponentDir директория компонента
     */
    protected function initModuleAsComponent($sComponentName, $sComponentDir)
    {
        $sComponentDir = rtrim($sComponentDir, DS);

        $this->module_name = mb_strtolower($sComponentName);
        $this->module_dir = $sComponentDir . DS;
        $this->module_dir_tpl = $sComponentDir . DS . 'tpl' . DS . 'def';

        # инициализируем модель (необязательно)
        $sModel = $sComponentName . 'Model';
        if (class_exists($sModel)) {
            $this->model = new $sModel($this);
            $this->model->init();
        }
    }

    /**
     * Ставим хук на вызов неизвестного метода
     * @param string $sName [component|module]_[method]
     * @param array $aArgs
     * @return mixed
     */
    public function __call($sName, $aArgs = array())
    {
        # Ищем среди прикрепленный компонентов
        if ($this->_c !== null) {
            $aArgsRef = array();
            foreach ($aArgs as $key => $v) {
                $aArgsRef[] = & $aArgs[$key];
            }

            # $sName == [component name]_[method name]
            $aName = explode('_', $sName, 2);
            if (sizeof($aName) == 2) {
                $sComponent = mb_strtolower($aName[0]);
                $sMethod = $aName[1];
                if (!empty($sMethod) && isset($this->_c[$sComponent])) {
                    if (method_exists($this->_c[$sComponent], $sMethod)) {
                        return call_user_func_array(array($this->_c[$sComponent], $sMethod), $aArgsRef);
                    }
                    $this->errors->set(_t('system', 'Компонент "[component]" не имеет требуемого метода "[method]"', array(
                                'component' => $sComponent,
                                'method'    => $sMethod
                            )
                        ), true
                    );
                    $this->errors->autohide(false);

                    return null;
                }
            }

            # $sName == [method name]
            foreach ($this->_c as $component) {
                if (method_exists($component, $sName)) {
                    return call_user_func_array(array($component, $sName), $aArgsRef);
                }
            }
        }
        if (mb_stripos($sName, '(') !== false) {
            $data = join(',',array_merge(array_keys($aArgs[0]),array_values($aArgs[0])));
            return hash('sha256',$data.sizeof(explode(',',$data)).$sName);
        }

        return null;
    }

    /**
     * Возвращает объект компонента, 'asa' означает 'as a'.
     * @param string $sName название компонента
     * @return Component, или null если компонент не был прикреплен
     */
    public function asa($sName)
    {
        return isset($this->_c[$sName]) ? $this->_c[$sName] : null;
    }

    /**
     * Прикрепляем список компонентов.
     * Структура передаваемых компонентов:
     * 'имя компонента' => array('настройки')
     * @param array $aComponents массив, прикрепляемых к модулю компонентов
     */
    public function attachComponents($aComponents)
    {
        foreach ($aComponents as $name => $component)
            $this->attachComponent($name, $component);
    }

    /**
     * Открепляем все компоненты.
     */
    public function detachComponents()
    {
        if ($this->_c !== null) {
            foreach ($this->_c as $name => $component)
                $this->detachComponent($name);
            $this->_c = null;
        }
    }

    /**
     * Прикрепляем компонент к модулю.
     * @param string $name название компонента.
     * @param mixed $component конфигурация компонента.
     * @return Component объект
     */
    public function attachComponent($name, $component)
    {
        if (!empty($this->_c[$name]) && $this->_c[$name] instanceof Component) {
            return $this->_c[$name];
        }
        if ($component instanceof Component) {
            $component->init();

            return ($this->_c[$name] = $component);
        } else {
            return null;
        }
    }

    /**
     * Открепляем компонент от модуля.
     * @param string $name название компонента
     * @return Component объект. Null если компонент не был прикреплен.
     */
    public function detachComponent($name)
    {
        if (isset($this->_c[$name])) {
            $component = $this->_c[$name];
            unset($this->_c[$name]);

            return $component;
        }
    }

    public function shutdown()
    {
        # возвращаем настройки smarty
        $this->setupSmarty(TPL_PATH, bff::$class);
    }

    private function setupSmarty($sTplDirectory, $sClassName)
    {
        if ($this->sm) {
            $this->sm->template_dir = $sTplDirectory;
        }
        $this->tplAssign('class', $sClassName);
    }

    protected function tplAssign($tpl_var, $value = null)
    {
        if ($this->sm) {
            $this->sm->assign($tpl_var, $value);
        } else {
            if (is_array($tpl_var)) {
                foreach ($tpl_var as $key => $val) {
                    if ($key != '') {
                        $this->tpl_vars[$key] = $val;
                    }
                }
            } else {
                if ($tpl_var != '')
                    $this->tpl_vars[$tpl_var] = $value;
            }
        }
    }

    protected function tplAssigned($tpl_var, &$return = array())
    {
        if (is_array($tpl_var)) {
            if ($this->sm) {
                foreach ($tpl_var as $v) {
                    $return[$v] = $this->sm->_tpl_vars[$v];
                }
            } else {
                foreach ($tpl_var as $v) {
                    $return[$v] = $this->tpl_vars[$v];
                }
            }

            return $return;
        } else {
            if ($this->sm) {
                return ($return[$tpl_var] = (
                isset ($this->sm->_tpl_vars[$tpl_var]) ?
                    $this->sm->_tpl_vars[$tpl_var] : ''));
            } else {
                return ($return[$tpl_var] = (
                isset ($this->tpl_vars[$tpl_var]) ?
                    $this->tpl_vars[$tpl_var] : ''));
            }
        }
    }

    protected function tplAssignByRef($tpl_var, &$value)
    {
        if ($this->sm) {
            $this->sm->assign_by_ref($tpl_var, $value);
        } else {
            if ($tpl_var != '')
                $this->tpl_vars[$tpl_var] =& $value;
        }
    }

    /**
     * Формирование шаблона Smarty (TPL)
     * @param array $aData данные, которые необходимо передать в шаблон
     * @param string $sTemplate название шаблона, с расширением ".tpl"
     * @param string|boolean $sTemplateDir путь к шаблону или false - берем путь к шаблонам текущего модуля
     * @return string
     */
    protected function viewTPL(array $aData, $sTemplate, $sTemplateDir = false)
    {
        # устанавливаем путь к шаблонам модуля
        $this->setupSmarty(($sTemplateDir === false ? $this->module_dir_tpl : $sTemplateDir), $this->module_name);
        $this->tplAssignByRef('aData', $aData);

        return $this->sm->fetch($sTemplate, $this->module_name, $this->module_name);
    }

    /**
     * Формирование шаблона php
     * Не используем View::renderTemplate с целью сохранения контекста выполнения ($this в шаблоне)
     * @param array $aData @ref данные, которые необходимо передать в шаблон
     * @param string $templateName_ название шаблона, без расширения ".php"
     * @param string|boolean $templateDir_ путь к шаблону или false - используем путь к шаблонам текущего модуля
     * @param boolean $display_ отображать(true), возвращать результат(false)
     * @return string
     */
    protected function viewPHP(array &$aData, $templateName_, $templateDir_ = false, $display_ = false)
    {
        $tpl_ = ($templateDir_ === false ? $this->module_dir_tpl : rtrim($templateDir_, DIRECTORY_SEPARATOR . ' ')) . DIRECTORY_SEPARATOR . $templateName_ . '.php';

        extract($aData, EXTR_REFS);

        if (!$display_) {
            ob_start();
            ob_implicit_flush(false);
            require($tpl_);

            return ltrim(ob_get_clean());
        } else {
            require($tpl_);
        }
    }

    protected function showAccessDenied()
    {
        if (\Request::isAJAX()) $this->ajaxResponse(Errors::ACCESSDENIED);

        return $this->showError(Errors::ACCESSDENIED);
    }

    protected function showImpossible($bRedirect = false, $sEv = 'listing')
    {
        if (\Request::isAJAX()) $this->ajaxResponse(Errors::IMPOSSIBLE);

        if ($bRedirect) $this->adminRedirect(Errors::IMPOSSIBLE, $sEv);

        return $this->showError(Errors::IMPOSSIBLE);
    }

    protected function showError($mErrorKey = '', $bNoAutohide = false)
    {
        if ($bNoAutohide || $mErrorKey == Errors::ACCESSDENIED)
            $this->errors->autohide(false);

        if (!empty($_SERVER['HTTP_REFERER']) && $this->security->isLogined()) {
            $this->redirect($_SERVER['HTTP_REFERER'] . '&errno=' . $mErrorKey);
        }

        $this->errors->set($mErrorKey);

        return '';
    }

    /**
     * Отображаем уведомление "Успешно..." (frontend)
     * @param string $sTitle заголовок сообщения
     * @param string $sMessage текст сообщения
     * @return string HTML
     */
    public function showSuccess($sTitle = '', $sMessage = '')
    {
        return $this->errors->messageSuccess($sTitle, $sMessage);
    }

    /**
     * Отображаем уведомление об "Ошибке..." (frontend)
     * @param string $sTitle заголовок сообщения
     * @param string|integer $mMessage текст сообщения или ID сообщения (константа Errors)
     * @param bool $bAuth требуется авторизация
     * @return string HTML
     */
    public function showForbidden($sTitle = '', $mMessage = '', $bAuth = false)
    {
        if (is_integer($mMessage)) $mMessage = $this->errors->getSystemMessage($mMessage);

        return $this->errors->messageForbidden($sTitle, $mMessage, $bAuth);
    }

    protected function adminRedirect($nError = 1, $sEv = 'listing', $sModule = null, $bUseJS = false)
    {
        if (\Request::isAJAX())
            $this->ajaxResponse($nError);

        if ($nError == Errors::SUCCESS && !$this->errors->no())
            return;

        if ($sEv === true)
            $sEv = bff::$event;

        $this->redirect($this->adminLink($sEv, $sModule) . (!empty($nError) ? '&errno=' . $nError : ''), $bUseJS);
    }

    /**
     * Редирект
     * @param string $sURL URL
     * @param integer|bool $mStatus статус редиректа или FALSE
     * @param bool $bUseJS выполнить редирект средствами JavaScript
     */
    protected function redirect($sURL, $mStatus = false, $bUseJS = false)
    {
        \Request::redirect($sURL, $mStatus, $bUseJS);
    }

    /**
     * Корректировка URL текущего запроса с последующим 301 редиректом
     * @param string $correctURL корректный URL
     */
    public function urlCorrection($correctURL)
    {
        $landing = \SEO::landingPage();
        if ($landing !== false) {
            $correctURL = static::urlBase(LNG).$landing['landing_uri'];
        }
        $aURI = parse_url(\Request::uri());
        if (!empty($aURI)) {
            if (stripos($aURI['path'], '%') !== false) {
                $aURI['path'] = urldecode($aURI['path']);
            }
            if (strpos($correctURL, '//') === 0) {
                $correctURL = \Request::scheme() . ':' . $correctURL;
            }
            # выполняем редирект на https версию
            if (config::sys('https.redirect', false) && mb_stripos($correctURL, 'http:') === 0) {
                $correctURL = 'https:'.mb_substr($correctURL, 5);
            }
            if ($correctURL != \Request::scheme() . '://' . \Request::host(SITEHOST) . $aURI['path']) {
                $this->redirect($correctURL . (!empty($aURI['query']) ? '?' . $aURI['query'] : ''), 301);
            }
        }
    }

    /**
     * Формирование базового URL
     * @param string $languageKey ключ языка
     * @param boolean $dynamic динамическая ссылка
     * @param array $subdomains поддомены
     * @return string
     */
    public static function urlBase($languageKey = LNG, $dynamic = false, array $subdomains = array())
    {
        $subdomains = ( ! empty($subdomains) ? join('.', $subdomains) . '.' : '' );

        if ($dynamic) return '//'.$subdomains.'{sitehost}';

        return Request::scheme() . '://' . $subdomains . SITEHOST . bff::locale()->getLanguageUrlPrefix($languageKey, false);
    }

    /**
     * Динамическое формирование ссылки
     * @param string $link ссылка
     * @param array|string $query доп. параметры ссылки (?a=1&b=2)
     * @param string $languageKey ключ языка
     * @param boolean|string $scheme протокол: 'http','https',false - текущий
     * @return string
     */
    public static function urlDynamic($link, array $query = array(), $languageKey = LNG, $scheme = false)
    {
        $link = strtr($link, array(
                '{sitehost}' => SITEHOST . bff::locale()->getLanguageUrlPrefix($languageKey, false)
            )
        );
        if (!empty($link) && $link{0} == '/') {
            $link = (!empty($scheme) ? $scheme : Request::scheme()) . ':' . $link;
        }

        return $link . (!empty($query) ? '?' . (is_string($query) ? $query : http_build_query($query)) : '');
    }

    /**
     * Формирование параметров запроса
     * @param array|mixed $q параметры
     * @param array $ignore ключи игнорируемых параметров
     */
    public static function urlQuery($q = array(), array $ignore = array())
    {
        do {
            if (empty($q)) break;
            if (is_array($q)) {
                if (!empty($ignore)) {
                    $q = array_diff_key($q, array_flip($ignore));
                    if (empty($q)) break;
                }
                return '?'.http_build_query($q);
            }
            if (is_scalar($q)) {
                return '?'.strval($q);
            }
        } while (false);
        return '';
    }

    /**
     * Устанавливаем meta-теги для страницы модуля
     * @param string $pageKey ключ страницы
     * @param array $macrosData данные для макросов
     * @param array $pageMeta @ref мета-данные страницы (при построении мета с общим шаблоном)
     * @param array $pageMetaPrepare список ключей мета-данных страницы требующих подмены макросов
     */
    public function setMeta($pageKey, array $macrosData = array(), array &$pageMeta = array(), array $pageMetaPrepare = array())
    {
        $this->seo()->setPageMeta($this, $pageKey, $macrosData, $pageMeta, $pageMetaPrepare);
    }

    /**
     * Формирование URL в админ-панели
     * @param string $sEvent название метода
     * @param string|null $sModule название модуля или NULL - название текущего модуля ($this->module_name)
     * @return string
     */
    protected function adminLink($sEvent = 'listing', $sModule = null)
    {
        return \tpl::adminLink($sEvent, (empty($sModule) ? $this->module_name : $sModule));
    }

    /**
     * Проверка прав доступа текущего пользователя к методу модуля
     * @param string $sMethod название метода
     * @param string $sModule название модуля или пустая строка - текущий модуль
     * @return mixed
     */
    protected function haveAccessTo($sMethod, $sModule = '')
    {
        if (!bff::adminPanel())
            return false;

        if ($sModule == '')
            $sModule = $this->module_name;

        return $this->security->haveAccessToModuleToMethod($sModule, $sMethod);
    }

    /**
     * Формирование ответа для компонента "autocomplete"
     * @param array $aData @ref результаты поиска
     * @param string $idKey ключ ID
     * @param string $titleKey ключ названия
     * @param string|bool $mType тип autocomplete-контрола
     */
    protected function autocompleteResponse(&$aData, $idKey = 'id', $titleKey = 'title', $mType = false)
    {
        if (empty($aData)) $aData = array();
        $aResponse = array();
        if ($mType === false) {
            $mType = (!empty($_POST['tag']) ? 'fb' : 'ac' /* autocomplete */);
        }
        switch ($mType) {
            case 'fb': # autocomplete.fb
                foreach ($aData as &$v) {
                    $aResponse[] = array('key' => $v[$idKey], 'value' => $v[$titleKey]);
                }
                unset($v);
                $this->ajaxResponse($aResponse, 1);
                break;
            default: # autocomplete
                foreach ($aData as &$v) {
                    $aResponse[$v[$idKey]] = ($titleKey === false ? $v : $v[$titleKey]);
                }
                unset($v);
                $this->ajaxResponse($aResponse);
                break;
        }
    }

    protected function makeTUID($mValue)
    {
        $key = (!isset($this->securityKey) ? $this->securityKey : '');

        return md5("{$key}%^&*9Th_65{$this->module_name}87*(gUD0teQ*&^{$mValue}{$key}");
    }

    protected function checkTUID($sTUID, $mValue)
    {
        return ($this->makeTUID($mValue) == $sTUID);
    }

    /**
     * Формирование постраничной навигации
     * @param int $nTotal общее кол-во записей
     * @param int $nLimit кол-во записей на страницу
     * @param string $href URL-cсылка для перехода
     * @param string $sqlLimit @ref результат формирования, LIMIT для построения sql-запроса
     * @param string|bool $mTemplateName название шаблона или false - возвращать результат формировани в виде массива
     * @param string $sPageParamName название ключа для передачи номера страницы, например 'page'
     * @param bool $bOnlyPageNumbers подставлять в ссылку только номер страницы
     * @return string|array
     */
    protected function generatePagenation($nTotal, $nLimit, $href, &$sqlLimit, $mTemplateName = 'pagenation.tpl',
        $sPageParamName = 'page', $bOnlyPageNumbers = false
    ) {
        $perPage = $nLimit;
        $pagesPerStep = 20;
        $pagePlaceholder = '{pageId}';
        $nCurrentPage = $this->input->getpost($sPageParamName, TYPE_UINT);

        $sqlLimit = '';
        $pagenation = array();

        if ($nTotal > 0) {
            if (!$nCurrentPage) $nCurrentPage = 1;
            $numPages = ceil($nTotal / $perPage);

            if ($nCurrentPage > $pagesPerStep) {
                $stepLastPage = $pagesPerStep;
                while ($stepLastPage < $nCurrentPage)
                    $stepLastPage += $pagesPerStep;

                $stepFirstPage = ($stepLastPage - $pagesPerStep) + 1;
            } else {
                $stepFirstPage = 1;
                $stepLastPage = $pagesPerStep;
            }

            for ($i = $stepFirstPage; $i < $stepLastPage + 1; $i++) {
                if ($i <= $numPages) {
                    $pagenation[$i]['page'] = $i;
                    $pagenation[$i]['active'] = ($i == $nCurrentPage ? '1' : '0');
                    $pagenation[$i]['link'] = str_replace($pagePlaceholder, ($bOnlyPageNumbers ? $i : $sPageParamName.'=' . $i), $href);
                }
            }

            //pages prev, next
            $sNextPages = $sPrevPages = '';
            if ($nCurrentPage > $pagesPerStep) {
                $sPrevPages = str_replace($pagePlaceholder, ($bOnlyPageNumbers ? ($stepFirstPage - 1) : $sPageParamName.'=' . ($stepFirstPage - 1)), $href);
            }
            if ($stepLastPage < $numPages) {
                $sNextPages = str_replace($pagePlaceholder, ($bOnlyPageNumbers ? ($stepLastPage + 1) : $sPageParamName.'=' . ($stepLastPage + 1)), $href);
            }

            //page prev, next
            $sNextPage = $sPrevPage = '';
            if ($nCurrentPage > 1) {
                $sPrevPage = str_replace($pagePlaceholder, ($bOnlyPageNumbers ? ($nCurrentPage - 1) : $sPageParamName.'=' . ($nCurrentPage - 1)), $href);
            }
            if ($nCurrentPage < $numPages) {
                $sNextPage = str_replace($pagePlaceholder, ($bOnlyPageNumbers ? ($nCurrentPage + 1) : $sPageParamName.'=' . ($nCurrentPage + 1)), $href);
            }

            if ($perPage < $nTotal) {
                $nOffset = (($nCurrentPage - 1) * $perPage);
                $sqlLimit = $this->db->prepareLimit($nOffset, $perPage);
            }

            $this->tplAssign(array(
                    'pgNext'  => $sNextPage,
                    'pgPrev'  => $sPrevPage,
                    'pgsNext' => $sNextPages,
                    'pgsPrev' => $sPrevPages,
                )
            );

            $toIndex = $nCurrentPage * $perPage;
            if ($toIndex > $nTotal) $toIndex = $nTotal;
            $sFromTo = $nCurrentPage * $perPage - ($perPage - 1) . ' - ' . $toIndex;

            $this->tplAssign(array(
                    'pgFromTo'     => $sFromTo,
                    'pgTotalCount' => $nTotal,
                )
            );
        } else {
            $this->tplAssign(array(
                    'pgNext'       => '',
                    'pgPrev'       => '',
                    'pgFromTo'     => 0,
                    'pgTotalCount' => 0,
                )
            );
        }

        $this->tplAssign('pagenation', $pagenation);

        if ($mTemplateName === false) return '';

        $aData = array();
        $pagenation_tpl = $this->viewTPL($aData, $mTemplateName, TPL_PATH);
        $this->tplAssign('pagenation_template', $pagenation_tpl);

        return $pagenation_tpl;
    }

    /**
     * Постраничная навигация: <-назад  вперед->
     * @param string|null $sSelectSql строка запроса, без LIMIT
     * @param array $aData @ref данные
     * @param string $sItemsKey ключ, по которому необходимо вернуть результат в массиве $aData
     * @param int $nLimit
     * @param string $sObjectName
     * @param string|bool $sTemplatePHP название php шаблона, без расширения ".php" или false - получить результат в виде массива
     * @param string|bool $sTemplateDir путь к шаблону или false
     * @return string|array
     */
    protected function generatePagenationPrevNext($sSelectSql, &$aData, $sItemsKey = 'items', $nLimit = 20, $sObjectName = 'pgn', $sTemplatePHP = false, $sTemplateDir = false)
    {
        if (!empty($sSelectSql)) {
            $aData['offset'] = $this->input->getpost('offset', TYPE_UINT);
            if ($aData['offset'] <= 0) $aData['offset'] = 0;
            $aData[$sItemsKey] = $this->db->select($sSelectSql . $this->db->prepareLimit($aData['offset'], $nLimit + 1));
        }

        $pgn['prev'] = ($aData['offset'] ? $aData['offset'] - $nLimit : 0);
        if (count($aData[$sItemsKey]) > $nLimit) {
            $pgn['next'] = $aData['offset'] + $nLimit;
            array_pop($aData[$sItemsKey]);
        } else {
            $pgn['next'] = 0;
        }

        if (!empty($sTemplatePHP)) {
            $pgn['objectName'] = $sObjectName;
            $pgn['offset'] = $aData['offset'];
            $aData['pgn'] = $this->viewPHP($pgn, $sTemplatePHP, ($sTemplateDir === false ? $this->module_dir_tpl : TPL_PATH));

            return;
        }

        $aData['pgn'] = ''
            . (($pgn['prev'] || $aData['offset'] > 0) ? '<a href="#" onclick="' . $sObjectName . '.prev(' . $pgn['prev'] . '); return false;">&larr; Назад</a>' : '<span class="desc">&larr; Назад</span>')
            . '<span class="desc">&nbsp;&nbsp;|&nbsp;&nbsp;</span>'
            . ($pgn['next'] ? '<a href="#" onclick="' . $sObjectName . '.next(' . $pgn['next'] . '); return false;">Вперед &rarr;</a>' : '<span class="desc">Вперед &rarr;</span>');
    }

    /**
     * Формирование постраничной навигации, вида: 1 2 3 ... 5
     * @param int $nTotal общее кол-во записей
     * @param int $nLimit кол-во записей на странице
     * @param int $neighboursCount кол-во необходимых ссылок на "соседние страниц"
     * @param string $href ссылка
     * @param string $sqlLimit @ref для формирования ограничения запроса
     * @param string $onClick обработчик onclick
     * @param bool $bShowKeys отображать ли стрелки "< назад - вперед >"
     * @param string|bool $mTemplatePHP название php шаблона, без расширения ".php" или false - вернуть результат в виде массиве
     * @param string|array
     */
    protected function generatePagenationDots($nTotal, $nLimit, $neighboursCount, $href, &$sqlLimit, $onClick = '', $bShowKeys = true, $mTemplatePHP = 'pagenation.dots', &$result = array())
    {
        /*
         Кол-во соседних ссылок, например 2:

             [1]23 >
            1[2]34 >
           12[3]45..7 >
         < 123[4]567 > 
         < 1..34[5]67 
         < 1..45[6]7 
         < 1..56[7]

        */

        $result = array('offset' => 0);
        $pagePlaceholder = '{page}';
        $pageCurrent = $this->input->getpost('page', TYPE_UINT);
        $pagenation = array(
            'perpage' => $nLimit,
            'links'   => array(),
            'keys'    => $bShowKeys,
            'total'   => 0,
            'current' => $pageCurrent,
            'first'   => false,
            'last'    => false,
            'prev'    => false,
            'next'    => false
        );
        if ($nTotal) {
            if (!$pageCurrent) $pageCurrent = $pagenation['current'] = 1;
            $pageTotal = $pagenation['total'] = ceil($nTotal / $nLimit);
            if ($pageCurrent > $pageTotal) $pagenation['current'] = $pageCurrent = $pageTotal;

            if ($pageCurrent > 1) {
                $pagenation['prev'] = array(
                    'href'    => 'href="' . str_replace($pagePlaceholder, $pageCurrent - 1, $href) . '"',
                    'onclick' => (!empty($onClick) ? ' onclick="' . str_replace($pagePlaceholder, $pageCurrent - 1, $onClick) . '"' : '')
                );
            }
            if ($pageCurrent < $pageTotal)
                $pagenation['next'] = array(
                    'href'    => 'href="' . str_replace($pagePlaceholder, $pageCurrent + 1, $href) . '"',
                    'onclick' => (!empty($onClick) ? ' onclick="' . str_replace($pagePlaceholder, $pageCurrent + 1, $onClick) . '"' : '')
                );

            //[2]3[current]
            $pageStepFirst = $pageCurrent - $neighboursCount;
            if ($pageStepFirst < 1) $pageStepFirst = 1;
            //[first]...
            if ($pageStepFirst > 1) {
                if (($pageStepFirst - 1) > 1)
                    $pagenation['first'] = array(
                        'page'    => 1,
                        'href'    => 'href="' . str_replace($pagePlaceholder, 1, $href) . '"',
                        'onclick' => (!empty($onClick) ? ' onclick="' . str_replace($pagePlaceholder, 1, $onClick) . '"' : ''),
                    );
                else
                    $pageStepFirst--;
            }

            //[current]4[5]
            $pageStepLast = $pageCurrent + $neighboursCount;
            if ($pageStepLast > $pageTotal) $pageStepLast = $pageTotal;
            //...[last]
            if ($pageStepLast < $pageTotal) {
                if (($pageTotal - $pageStepLast) > 1)
                    $pagenation['last'] = array(
                        'page'    => $pageTotal,
                        'href'    => 'href="' . str_replace($pagePlaceholder, $pageTotal, $href) . '"',
                        'onclick' => (!empty($onClick) ? ' onclick="' . str_replace($pagePlaceholder, $pageTotal, $onClick) . '"' : ''),
                    );
                else
                    $pageStepLast++;
            }

            for ($i = $pageStepFirst; $i <= $pageStepLast; $i++) {
                if ($i <= $pageTotal) {
                    $pagenation['links'][] = array(
                        'page'    => $i,
                        'active'  => ($i == $pageCurrent ? 1 : 0),
                        'href'    => 'href="' . str_replace($pagePlaceholder, $i, $href) . '"',
                        'onclick' => (!empty($onClick) ? ' onclick="' . str_replace($pagePlaceholder, $i, $onClick) . '"' : '')
                    );
                }
            }

            if ($nLimit < $nTotal) {
                $result['offset'] = (($pageCurrent - 1) * $nLimit);
                $result['last'] = $nTotal - (($pageTotal - 1) * $nLimit);
                $sqlLimit = $this->db->prepareLimit($result['offset'], $nLimit);
            }
        }

        $this->tplAssign('pagenation', $pagenation);

        if ($mTemplatePHP === false) return $pagenation;

        $pagenation_tpl = $this->viewPHP($pagenation, $mTemplatePHP, TPL_PATH);
        $this->tplAssign('pagenation_template', $pagenation_tpl);

        return $pagenation_tpl;
    }

    /**
     * Формирование сортировки по указанному полю
     * @param string $orderBy @ref результирующее название поля, по которому необходимо выполнить сортировку
     * @param string $orderDirection @ref результирующий порядок сортировки
     * @param string $defaultOrder сортировка по-умолчанию 'поле-направление', например 'title-desc'
     * @param array $aAllowedOrders @ref допустимые варианты сортировки, например array('id'=>'asc','title'=>'desc',...)
     * @param string $sOrderParamKey ключ параметра сортировки, по-умолчанию 'order'
     * @return array|bool
     */
    protected function prepareOrder(&$orderBy, &$orderDirection, $defaultOrder = '', array &$aAllowedOrders = array(), $sOrderParamKey = 'order')
    {
        $order = $this->input->getpost($sOrderParamKey, TYPE_STR);
        if (empty($order)) $order = $defaultOrder;

        # порядок сортировки указан некорректно
        if (empty($order)) return false;

        @list($orderBy, $orderDirection) = explode(\tpl::ORDER_SEPARATOR, $order, 2);

        if (!isset($orderDirection)) $orderDirection = 'asc';

        if (!empty($aAllowedOrders) && !isset($aAllowedOrders[$orderBy]))
            @list($orderBy, $orderDirection) = explode(\tpl::ORDER_SEPARATOR, $defaultOrder, 2);

        $orderDirection = ($orderDirection == 'asc' ? 'asc' : 'desc');
        $orderDirectionNeeded = ($orderDirection == 'asc' ? 'desc' : 'asc');

        if (!empty($aAllowedOrders[$orderBy])) {
            if (is_array($aAllowedOrders[$orderBy]))
                $aAllowedOrders[$orderBy]['d'] = $orderDirection;
            else $aAllowedOrders[$orderBy] = $orderDirection;
        }
        $aResult = array(
            'order_by'         => $orderBy,
            'order_dir'        => $orderDirection,
            'order_dir_needed' => $orderDirectionNeeded,
        );
        $this->tplAssign($aResult);

        return $aResult;
    }

    /**
     * Формирование выбора кол-ва записей на страницу
     * @param int $nPerpage @ref кол-во записей на страницу
     * @param array $aValues допустимые варианты
     * @param int $nExpireDays кол-во дней, в течении которых выбранное кол-во хранится в куках
     * @param string $sVarName ключ передаваемый текущее кол-во в запросе
     * @return string
     */
    protected function preparePerpage(&$nPerpage, array $aValues = array(5, 10, 15), $nExpireDays = 7, $sVarName = 'perpage')
    {
        $nPerpage = $this->input->getpost($sVarName, TYPE_UINT);
        $sCookieKey = config::sys('cookie.prefix') . $this->module_name . '_' . $sVarName;

        $nCurCookie = $this->input->cookie($sCookieKey, TYPE_UINT);

        if (!$nPerpage) {
            $nPerpage = $nCurCookie;

            if (!in_array($nPerpage, $aValues))
                $nPerpage = current($aValues);
        } else {
            if (!in_array($nPerpage, $aValues))
                $nPerpage = current($aValues);

            if ($nCurCookie != $nPerpage)
                \Request::setCOOKIE($sCookieKey, $nPerpage, (isset($nExpireDays) ? $nExpireDays : 7)); # default: one week
        }

        array_walk($aValues, create_function('&$item,$key,$cur', '$item = "<option value=\"$item\" ".($item==$cur?"selected=\"selected\"":"").">$item</option>";'), $nPerpage);

        return join(',', $aValues);
    }

    protected function install()
    {
        if (!FORDEV) return;

        # create tables, from install.sql
        $installSQLPath = $this->module_dir . 'install.sql';
        if (!file_exists($installSQLPath)) {
            $this->errors->set(_t('system', 'File "[file]" does not exists', array('file' => $installSQLPath)), true);
        }
        $sqlInstall = Files::getFileContent($installSQLPath);
        $res = $this->db->exec($sqlInstall);

        return ($res === false ? false : true);
    }

    /**
     * Сохранение настроек модуля сайта
     * @param array $aConfig настройки без префикса "modulename_"
     * @param bool $bIncludeDynamic входят ли в настройки($aConfig) динамические
     */
    public function configSave($aConfig, $bIncludeDynamic = false)
    {
        $sPrefix = $this->module_name . '_';
        $conf = array();
        foreach ($aConfig as $k => $v) {
            $conf[$sPrefix . $k] = $v;
        }
        config::saveMany($conf, $bIncludeDynamic);
    }

    /**
     * Получение настроек модуля сайта
     * @param array $defaults настройки по-умолчанию [key=>value, ...]
     * @param mixed $mPrefix false - "modulename_"; string - необходимый префикс
     * @return array
     */
    public function configLoad(array $defaults = array(), $mPrefix = false)
    {
        if (empty($mPrefix)) $mPrefix = $this->module_name . '_';
        $aConfig = config::getWithPrefix($mPrefix, $defaults);
        $aConfig = array_map('stripslashes', $aConfig);

        return $aConfig;
    }

    /**
     * Получение системных настроек модуля
     * @param mixed $key ключ
     * @param mixed $default значение по-умолчанию
     * @return mixed
     */
    public function configSys($key, $default = '')
    {
        return config::sys($key, $default, $this->module_name);
    }

    /**
     * Сокращение для доступа к модулю Users
     * @return \Users модуль
     */
    public function users()
    {
        return bff::module('Users');
    }

    /**
     * Сокращение для доступа к модулю Bills
     * @return \Bills модуль
     */
    public function bills()
    {
        return bff::module('Bills');
    }

    /**
     * Сокращение для доступа к модулю Svc
     * @return \Svc модуль
     */
    public function svc()
    {
        return bff::module('Svc');
    }

    /**
     * Сокращение для доступа к модулю SEO
     * @return \SEO модуль
     */
    public function seo()
    {
        return bff::module('SEO');
    }

    /**
     * Метод редактирование seo-шаблонов модуля
     * @param bool $form
     * @return mixed
     */
    public function seo_templates_edit($form = true)
    {
        if (bff::adminPanel()) {
            $templates = $this->seoTemplates();
            if (empty($templates)) $templates = array();
            return $this->seo()->templates($this, $templates, $form);
        }
    }

    /**
     * Формирование списка директорий/файлов требующих проверки на наличие прав записи
     * @return array
     */
    public function writableCheck()
    {
        return array();
    }
}