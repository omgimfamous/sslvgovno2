<?php

/**
 * Класс меню в admin-панели
 * @version 0.286
 * @modified 7.jun.2015
 */
class CMenu extends bff\Singleton
{
    protected $items = array();
    protected $adminHeaderCounters = array();

    protected $curMainTab = false;
    protected $curMainTabTitle = false;
    protected $curSubTab = false;
    protected $curSubTabTitle = false;

    const TYPE_MODULE_METHOD = 0;
    const TYPE_LINK          = 1;
    const TYPE_SEPARATOR     = 2;

    /**
     * @return CMenu
     */
    public function init()
    {
        parent::init();

        return $this;
    }

    /**
     * @return CMenu
     */
    public static function i()
    {
        return parent::i();
    }

    /**
     * Закрепляем необходимые данные за основным разделом
     * @param string $mainTitle название основного раздела
     * @param string $paramKey ключ
     * @param mixed $paramValue данные
     */
    public function setMainTabParam($mainTitle, $paramKey, $paramValue)
    {
        $this->items[$mainTitle][$paramKey] = $paramValue;
    }

    /**
     * Счетчики в шапке админ. панели
     * @param string $title название счетчика
     * @param string $counterKey ключ счетчика в настройках сайта (TABLE_CONFIG) или пользовательский ($options['userCounter'] = true)
     * @param string $module модуль
     * @param string $method метод
     * @param integer $priority приоритет, определяет порядок счетчиков
     * @param string $icon иконка
     * @param array $options доп. параметры
     */
    public function adminHeaderCounter($title, $counterKey, $module, $method, $priority, $icon = '', array $options = array())
    {
        $this->adminHeaderCounters[] = array(
            't'      => $title,
            'url'    => tplAdmin::adminLink(strtolower($method), strtolower($module)),
            's'      => strtolower($module),
            'ev'     => strtolower($method),
            'evp'    => (isset($options['params']) ? $options['params'] : ''),
            'cnt'    => (!empty($options['userCounter']) ? bff::security()->userCounter($counterKey) : config::get($counterKey, 0)),
            'num'    => $priority,
            'i'      => $icon,
            'danger' => !empty($options['danger']),
            'o'      => $options,
        );
    }

    /**
     * Формирование счетчиков в шапке админ. панели
     * @return array
     */
    public function adminHeaderCounters()
    {
        # сортируем в порядке priority
        $priorityOrder = array();
        foreach ($this->adminHeaderCounters as $k => $v) {
            $priorityOrder[$k] = $v['num'];
        }
        array_multisort($priorityOrder, SORT_ASC, $this->adminHeaderCounters);

        return $this->adminHeaderCounters;
    }

    /**
     * Добавление пункта меню
     * @param string $mainTitle название основного раздела меню
     * @param string $subTitle название подраздела меню
     * @param string $module название модуля, или ссылка (@example http://example.com/page1.html)
     * @param string $method название метода модуля
     * @param boolean $isVisible показывать пункт меню по-умолчанию
     * @param integer $priority приоритет, определяет порядок подразделов в пределах раздела
     * @param array $options дополнительные настройки:
     *    1. rlink => array(title=>'+', event=>'', type=>self::TYPE_MODULE_METHOD, link=>'')
     *    2. counter => array()
     *    3. params => string дополнительные параметры ссылки типа TYPE_MODULE_METHOD
     *    4. access => array(module,method) или 'method' -  проверка прав доступа
     * @param integer $type тип раздела/подраздела ( self::TYPE_ )
     * @return CMenu
     */
    public function assign($mainTitle, $subTitle, $module, $method, $isVisible = true, $priority = 999, array $options = array(), $type = self::TYPE_MODULE_METHOD)
    {
        $rlink = false;
        # access:
        if (!empty($options['access'])) {
            if (is_string($options['access'])) {
                if (!bff::security()->haveAccessToModuleToMethod($module, $options['access'])) {
                    return;
                }
            } else if (is_array($options['access']) && sizeof($options['access']) == 2) {
                if (!bff::security()->haveAccessToModuleToMethod($options['access'][0], $options['access'][1])) {
                    return;
                }
            }
        }
        # rlink:
        if (isset($options['rlink'])) {
            $rlink = $options['rlink'];

            if (!isset($rlink['title'])) {
                $rlink['title'] = '+';
            }

            $rlink['url'] = (isset($rlink['type']) && $rlink['type'] == self::TYPE_LINK ? $rlink['link'] :
                ($type == self::TYPE_LINK ? '#' : tplAdmin::adminLink($rlink['event'], $module)));
        }
        # counter:
        if (!empty($options['counter'])) {
            $cnt = (!empty($options['userCounter']) ? bff::security()->userCounter($options['counter']) : config::get($options['counter'], 0));
            $options['counter'] = ($cnt > 0 ? '<span class="num" id="j-' . $module . '-' . $method . '-counter">' . $cnt . '</span>' : false);
        }

        $this->items[$mainTitle]['sub'][] = array(
            'title'       => $subTitle,
            'class'       => strtolower($module),
            'event'       => strtolower($method),
            'eventParams' => (isset($options['params']) ? $options['params'] : ''),
            'visible'     => $isVisible,
            'priority'    => $priority,
            'type'        => $type,
            'counter'     => (isset($options['counter']) ? $options['counter'] : false),
            'rlink'       => $rlink,
        );

        return $this;
    }

    /**
     * Формирование admin-меню
     * @param array $aMainTabs список названий основных пунктов меню
     * @param array|bool $aCoreModules список модулей ядра
     * @param string $sCollectFunctionName название вызываемого метода для формирования пунктов меню
     * @return array
     */
    public function buildAdminMenu(array $aMainTabs, $aCoreModules = false, $sCollectFunctionName = 'declareAdminMenu')
    {
        $security = bff::security();
        $menu = $this;

        foreach ($aMainTabs as $title) {
            $this->items[$title] = array(
                'title'   => $title,
                'counter' => false,
                'sub'     => array()
            );
        }

        # пункты меню
        if (true) // cache, TODO
        {
            # main modules
            $sPath = PATH_MODULES;
            $aDirectories = bff::i()->getModulesList();
            foreach ($aDirectories as $module) {
                if (file_exists($sPath . "$module/m.$module.class.php")) {
                    require_once($sPath . "$module/m.$module.class.php");
                    if (class_exists('M_' . $module) && method_exists('M_' . $module, $sCollectFunctionName)) {
                        if (!$security->haveAccessToModuleToMethod($module) && !in_array($module, array('site'))) {
                            continue;
                        }
                        call_user_func(array('M_' . $module, $sCollectFunctionName), $menu, $security);
                    }
                }
            }

            # core modules menu
            $sPath = PATH_CORE . 'modules' . DS;
            $aDirectories = bff::i()->getModulesList(true);
            if ($aCoreModules !== false) {
                # оставляем только требуемые модули
                if (empty($aCoreModules)) {
                    $aDirectories = array();
                } else {
                    $aDirectories = array_intersect($aDirectories, $aCoreModules);
                }
            }
            foreach ($aDirectories as $module) {
                include($sPath . "$module/admin.menu.php");
            }
        }

        # активируем пункт меню
        $this->activate();

        # формируем пункты меню
        $aTabs = $this->buildTabs();
        $firstTab = current($aTabs);

        # формируем название страницы (на основе активного пункта меню)
        $sPageTitle = $this->curMainTabTitle . (!empty($this->curSubTabTitle) ? ' / ' . $this->curSubTabTitle : '');
        tplAdmin::adminPageSettings(array('title' => $sPageTitle), false);

        return array(
            'tabs' => $aTabs,
            'url'  => $firstTab['url']
        );
    }

    /**
     * Формируем меню
     * @param array|bool $aMainTabs список названий основных пунктов меню
     * @param string $sCollectFunctionName
     * @return mixed
     */
    public function buildMenu(array $aMainTabs, $sCollectFunctionName)
    {
        $security = bff::security();

        if (!empty($aMainTabs)) {
            foreach ($aMainTabs as $title) {
                $this->items[$title] = array(
                    'title'   => $title,
                    'counter' => false,
                    'sub'     => array()
                );
            }
        }

        # пункты меню
        if (true) // cache check, later - todo
        {
            $sPath = PATH_MODULES;
            $aDirectories = bff::i()->getModulesList();
            foreach ($aDirectories as $module) {
                if (file_exists($sPath . "$module/m.$module.class.php")) {
                    require_once($sPath . "$module/m.$module.class.php");
                    if (class_exists('M_' . $module) && method_exists('M_' . $module, $sCollectFunctionName)) {
                        if (!$security->haveAccessToModuleToMethod($module)) {
                            continue;
                        }
                        call_user_func(array('M_' . $module, $sCollectFunctionName), $this, $security);
                    }
                }
            }
        }

        # счетчики
        # ?

        # активируем пункт меню
        $this->activate();

        # формируем пункты меню
        $aTabs = $this->buildTabs();
        $firstTab = current($aTabs);

        # формируем название страницы (на основе активного пункта меню)
        $sPageTitle = $this->curMainTabTitle . (!empty($this->curSubTabTitle) ? ' / ' . $this->curSubTabTitle : '');
        tplAdmin::adminPageSettings(array('title' => $sPageTitle), false);

        return array(
            'tabs' => $aTabs,
            'url'  => $firstTab['url'],
        );
    }

    /**
     * Активируем пункт меню исходя их bff::$class, bff::$event
     */
    protected function activate()
    {
        $class = strtolower(bff::$class);
        $event = strtolower(bff::$event);

        if (!empty($class)) {
            $n = 0;
            foreach ($this->items as $mainTitle => &$main) {
                if (!empty($main['sub'])) {
                    foreach ($main['sub'] as $i => &$sub) {
                        if ($sub['class'] == $class) {
                            $isLink = ($sub['type'] == self::TYPE_LINK);
                            if ($sub['event'] == $event) {
                                $this->curMainTab = $n;
                                $this->curMainTabTitle = $mainTitle;
                                $this->curSubTab = ($isLink ? -1 : $i);
                                $this->curSubTabTitle = $sub['title'];
                                break 2;
                            } else {
                                $this->curMainTab = $n;
                                $this->curMainTabTitle = $mainTitle;
                                $this->curSubTab = -1;
                            }
                        }
                    }
                }
                unset($sub);
                $n++;
            }
            unset($main);
        }

        if ($this->curMainTab === false) {
            $first = current($this->items);
            $this->curMainTab = 0;
            $this->curMainTabTitle = $first['title'];
        }

    }

    /**
     * Формируем разделы меню
     * @return array
     */
    protected function buildTabs()
    {
        $aTabs = array();
        $n = 0;

        foreach ($this->items as $title => $params) {
            if (empty($params['sub'])) {
                $n++;
                continue;
            }

            # основные разделы
            $paramsMain = & $params['sub'][0];

            $isActive = ($this->curMainTab == $n);

            $aTabs[$n] = array(
                'title'     => $title . (isset($params['counter']) && $params['counter'] !== false ? ' ' . $params['counter'] : ''),
                'active'    => $isActive,
                'class'     => $paramsMain['class'],
                'separator' => ($paramsMain['type'] == self::TYPE_SEPARATOR),
                'url'       => ($paramsMain['type'] == self::TYPE_MODULE_METHOD ?
                        tplAdmin::adminLink($paramsMain['event'] . $paramsMain['eventParams'], $paramsMain['class']) : //mm
                        $paramsMain['class']), // link
                'subtabs'   => array()
            );

            # подразделы
            if (count($params['sub']) > 1) {
                foreach ($params['sub'] as $i => &$sub) {
                    if (!$sub['visible']) {
                        continue;
                    }

                    $aTabs[$n]['subtabs'][] = array(
                        'title'     => $sub['title'] . (isset($sub['counter']) && $sub['counter'] !== false ? ' ' . $sub['counter'] : ''),
                        'class'     => $sub['class'],
                        'event'     => $sub['event'],
                        'params'    => $sub['eventParams'],
                        'active'    => ($isActive && $i == $this->curSubTab),
                        'separator' => ($sub['type'] == self::TYPE_SEPARATOR),
                        'rlink'     => $sub['rlink'],
                        'url'       => ($sub['type'] == self::TYPE_MODULE_METHOD ?
                                tplAdmin::adminLink($sub['event'] . $sub['eventParams'], $sub['class']) : //mm
                                $sub['class']), # link
                        'priority'  => $sub['priority'],
                        'id'        => ($sub['type'] == self::TYPE_MODULE_METHOD ? $sub['class'] . '-' . $sub['event'] : '')
                    );
                }
                unset($sub);

                # сортируем в порядке priority
                $priorityOrder = array();
                foreach ($aTabs[$n]['subtabs'] as $k => $v) {
                    $priorityOrder[$k] = $v['priority'];
                }
                array_multisort($priorityOrder, SORT_ASC, $aTabs[$n]['subtabs']);
            }

            $n++;
        }

        return $aTabs;
    }

}