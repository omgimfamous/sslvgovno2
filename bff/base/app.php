<?php namespace bff\base;

/**
 * Базовый класс приложения
 * @abstract
 * @version 0.772
 * @modified 3.may.2015
 */

use \config;
use \Errors;
use bff\Singleton;
use bff\logs\File;
use bff\utils\Files;

class app extends Singleton
{
    /** @var \Pimple DI-контейнер */
    protected static $di;
    /** @var integer|bool ID текущего авторизованного пользователя или FALSE (0) */
    public static $userID = false;
    /** @var bool является ли текущий пользователь поисковым ботом */
    public static $isBot = false;
    /** @var string название требуемого модуля */
    public static $class = '';
    /** @var string название требуемого метода модуля */
    public static $event = '';

    /** @var array список модулей ядра (базовая функциональность которых реализована в ядре) */
    protected $_core_modules = array('bills', 'dev', 'geo', 'sendmail', 'seo', 'site', 'sitemap', 'svc', 'users');
    /** @var array список инициализированных объектов модулей @see \bff\base\app::getModule */
    protected $_m = array();

    # тип устройства:
    const DEVICE_DESKTOP = 'desktop';
    const DEVICE_TABLET = 'tablet';
    const DEVICE_PHONE = 'phone';

    /**
     * Singleton
     * @return \bff
     */
    public static function i()
    {
        return parent::i();
    }

    /**
     * Инициализация основного класса ядра
     */
    public function init()
    {
        parent::init();

        # Параметры запроса
        static::$isBot = (empty($_COOKIE) && preg_match("#(google|googlebot|yandex|rambler|msnbot|bingbot|yahoo! slurp|facebookexternalhit)#si", \Request::userAgent()));
        static::$class = static::DI('input')->getpost('s', TYPE_STR, array('len' => 30));
        static::$event = static::DI('input')->getpost('ev', TYPE_STR, array('len' => 30));

        # Локализация
        static::locale()->init(config::sys('locale.available'), config::sys('locale.default'), static::adminPanel());

        # Строим путь к основным шаблонам
        define('TPL_PATH', PATH_BASE . 'tpl' .
            (static::adminPanel() ? DIRECTORY_SEPARATOR . 'admin' :
                (static::isMobile() ? DIRECTORY_SEPARATOR . 'mobile' : '')));

        # Загружаем настройки сайта
        config::load();

        # Инициализируем работу с сессией
        static::DI('security')->init();
        static::DI('security')->checkExpired();

        # Настройки админ. панели
        if (static::adminPanel()) {
            config::set('tpl_custom_center_area', false);

            $oSm = \CSmarty::i();
            $oSm->force_compile = FORDEV;
            $oSm->compile_check = true;
            $oSm->debugging = false;
            $oSm->compile_dir = PATH_BASE . 'files/smarty';
            $oSm->config_dir = PATH_BASE . 'config';
            $oSm->plugins_dir = array('plugins', 'plugins/bff');
            $oSm->template_dir = TPL_PATH;
            $oSm->assign('site_url', SITEURL);
            $oSm->assign('fordev', FORDEV);
            $oSm->assign('class', static::$class);
            $oSm->assign('event', static::$event);
            $oSm->assign_by_ref('config', config::$data);
        }

        config::set('bot', static::$isBot);
    }

    /**
     * Dependency Injection Container
     * @param bool $key ключ требуемого "сервиса", false - объект \Pimple
     * @return mixed|\Pimple
     */
    public static function DI($key = false)
    {
        if (!isset(static::$di)) {
            static::$di = new \Pimple(array(
                'errors'         => function ($c) {
                        return $c['errors_class']::i();
                    },
                'errors_class'   => '\Errors',
                'security'       => function ($c) {
                        return new $c['security_class']();
                    },
                'security_class' => '\Security',
                'locale'         => function ($c) {
                        return new $c['locale_class']();
                    },
                'locale_class'   => '\bff\base\Locale',
                'input'          => function ($c) {
                        return new $c['input_class']();
                    },
                'input_class'    => '\bff\base\Input',
                'database'       => function ($c) {
                        $db = new $c['database_class']();
                        $db->connectionConfig('db');
                        $db->connect();

                        return $db;
                    },
                'database_class' => '\bff\db\Database',
            ));
            static::$di['database_factory'] = static::$di->factory(function ($c) {
                    return new $c['database_class']();
                }
            );
        }
        if (!empty($key)) {
            return static::$di[$key];
        }

        return static::$di;
    }

    /**
     * Обработка вызова метода модуля
     * @param string $name имя модуля
     * @param array $aArgs аргументы
     * @return mixed
     */
    public function __call($name, $aArgs)
    {
        return $this->callModule($name, $aArgs);
    }

    /**
     * Вызываем метод требуемого модуля
     * @param string $name имя модуля
     * @param array $aArgs аргументы
     * @return mixed
     */
    public function callModule($name, $aArgs = array())
    {
        list($oModule, $moduleName, $sMethod, $firstRun) = $this->getModule($name, false);

        $aArgsRef = array();
        foreach ($aArgs as $key => $v) {
            $aArgsRef[] = & $aArgs[$key];
        }

        if (!method_exists($oModule, $sMethod)) {
            $result = $oModule->$sMethod($aArgsRef); //пытаемся вызвать метод прикрепленного компонента
            if ($result === null) {
                if (!static::adminPanel()) {
                    static::DI('errors')->error404();
                }
                static::DI('errors')->set(_t('system', 'Модуль [module] не имеет метода [method]', array(
                            'module' => $moduleName,
                            'method' => $sMethod
                        )
                    ), true
                );
                static::DI('errors')->autohide(false);
                $result = '';
            }
        } else {
            $result = call_user_func_array(array($oModule, $sMethod), $aArgsRef);
        }
        $oModule->shutdown();

        return $result;
    }

    /**
     * Вызываем требуемый метод во всех модулях приложения (в которых он реализован)
     * @param string $sMethod имя метода
     * @param array $aArgs аргументы
     * @return mixed
     */
    public function callModules($sMethod, array $aArgs = array())
    {
        $aArgsRef = array();
        foreach ($aArgs as $key => $v) {
            $aArgsRef[] = & $aArgs[$key];
        }

        $aModules = $this->getModulesList();
        foreach ($aModules as $v) {
            $oModule = $this->getModule($v);
            if (method_exists($oModule, $sMethod)) {
                call_user_func_array(array($oModule, $sMethod), $aArgsRef);
            }
        }
    }

    /**
     * Возвращаем объект модуля, имя модуля и имя вызванного метода
     * @param string $name название модуля
     * @param bool $onlyObject true - возвращать только объект модуля; false - array(объект, название, название метода, bool-первое обращение к модулю)
     * @return Module|array
     */
    public function getModule($name, $onlyObject = true)
    {
        $aName = explode('_', $name, 2);
        if (sizeof($aName) == 2) {
            $moduleName = mb_strtolower($aName[0]);
            $method = $aName[1];
        } else {
            $moduleName = mb_strtolower($name);
            $method = '';
        }
        $moduleName = str_replace(array('.',DIRECTORY_SEPARATOR), '', $moduleName);

        $firstRun = true;
        if (isset($this->_m[$moduleName])) {
            $moduleObject = $this->_m[$moduleName];
            $firstRun = false;
        } else {
            $adm = static::adminPanel();
            $core = in_array($moduleName, $this->_core_modules);
            if ($core) {
                # подключаем [ModuleName]ModuleBase
                require PATH_CORE . 'modules' . DS . $moduleName . DS . 'base.php';
                # подключаем [ModuleName]Module
                require PATH_CORE . 'modules' . DS . $moduleName . DS . ($adm ? 'admin' : 'frontend') . '.php';
            }

            $path = PATH_MODULES . $moduleName . DS; # ищем в модулях приложения (/modules)
            if (file_exists($path . $moduleName . ($adm ? '.adm' : '') . '.class.php')) {
                # подключаем [ModuleName]Base
                require $path . $moduleName . '.bl.class.php';
                # подключаем [ModuleName]Model
                require $path . $moduleName . '.model.php';
                # подключаем [ModuleName]
                require $path . $moduleName . ($adm ? '.adm' : '') . '.class.php';

                # cоздаем объект модуля приложения
                $moduleObject = $this->_m[$moduleName] = new $moduleName();
                $moduleObject->initModule($moduleName);
                $moduleObject->init();
            } else {
                if ($core) {
                    # cоздаем объект модуля ядра
                    $moduleCore = $moduleName . 'Module';
                    $moduleObject = $this->_m[$moduleName] = new $moduleCore();
                    $moduleObject->initModule($moduleName);
                    $moduleObject->init();
                } else {
                    throw new \Exception(_t('system', 'Неудалось найти модуль "[module]"', array('module' => $moduleName)));
                }
            }
        }

        return ($onlyObject ? $moduleObject : array($moduleObject, $moduleName, $method, $firstRun));
    }

    /**
     * Получаем список модулей приложения
     * @param bool|mixed $mCoreModules :
     *  true  - список модулей ядра
     *  false - список модулей приложения
     *  'all' - список всех модулей (ядра + приложения)
     * @return array
     */
    public function getModulesList($mCoreModules = false)
    {
        if ($mCoreModules === true) {
            return $this->_core_modules;
        }
        static $cache;
        if (!isset($cache)) {
            $aModules = Files::getDirs(PATH_MODULES);
            foreach ($aModules as $k => $v) {
                if ($v{0} != '.' && $v{0} != '_' && $v != 'test') {
                    $aModules[$v] = $v;
                }
                unset($aModules[$k]);
            }
            $cache = $aModules;
        }
        if ($mCoreModules === 'all') {
            foreach ($this->_core_modules as $v) {
                if (!isset($cache[$v])) {
                    $cache[$v] = $v;
                }
            }
        }

        return $cache;
    }

    /**
     * Возвращаем объект модуля, имя модуля и имя вызванного метода
     * Сокращение для static::i()->getModule()
     * @param string $moduleName название модуля
     * @param bool $onlyObject возвращать только объект модуля - true, либо array(объект, название, ...) - false
     * @return @see bff::getModule
     */
    public static function module($moduleName, $onlyObject = true)
    {
        return static::i()->getModule($moduleName, $onlyObject);
    }

    /**
     * Проверяем существование модуля
     * @param string $moduleName название модуля
     * @param bool $bCheckCore выполнять поиск также среди модулей ядра или только среди модулей приложения
     */
    public static function moduleExists($moduleName, $bCheckCore = true)
    {
        if (empty($moduleName)) return false;
        $moduleName = mb_strtolower($moduleName);

        if ($bCheckCore) {
            if (in_array($moduleName, static::i()->getModulesList(true))) {
                return true;
            }
        }

        $aModules = static::i()->getModulesList(false);

        return in_array($moduleName, $aModules);
    }

    /**
     * Возвращаем объект модели указанного модуля
     * @param string $moduleName название модуля
     * @return \Model
     */
    public static function model($moduleName)
    {
        return static::i()->getModule($moduleName, true)->model;
    }

    /**
     * Выполняется ли запрос из admin-панели
     * @return bool
     */
    public static function adminPanel()
    {
        return defined('BFF_ADMINPANEL');
    }

    /**
     * Корректно ли выполнено обращение к cron-методу модуля
     * @return bool
     */
    public static function cron()
    {
        return defined('BFF_CRON');
    }

    /**
     * Демо версия
     * @return bool
     */
    public static function demo()
    {
        return defined('BFF_DEMO') || config::sys('demo');
    }

    /**
     * @return \Errors
     */
    public static function errors()
    {
        return static::DI('errors');
    }

    /**
     * @return \bff\base\Input
     */
    public static function input()
    {
        return static::DI('input');
    }

    /**
     * @return \bff\base\Locale
     */
    public static function locale()
    {
        return static::DI('locale');
    }

    /**
     * @return \Security
     */
    public static function security()
    {
        return static::DI('security');
    }

    /**
     * @return \bff\db\Database
     */
    public static function database()
    {
        return static::DI('database');
    }

    /**
     * Проверка на index-страницу
     * @return bool
     */
    public static function isIndex()
    {
        return empty(static::$class) || (static::$class == 1);
    }

    /**
     * Определение необходимости отображения mobile-версии на отдельном домене
     * @return bool
     */
    public static function isMobile()
    {
        static $isMobile;
        if (isset($isMobile)) return $isMobile;

        $sMobileHost = config::sys('site.mobile.host');
        if (empty($sMobileHost)) {
            # мобильная версия незадействована
            return ($isMobile = false);
        }
        $sHttpHost = \Request::host(SITEHOST);
        $sForceCookieName = config::sys('cookie.prefix') . 'full';

        # находимся на поддомене m. => значит показываем мобильную версию, независимо от типа устройства (и куков)
        if (stripos($sHttpHost, $sMobileHost) === 0 || stripos($sHttpHost, '.'.$sMobileHost) !== false) {
            return ($isMobile = true);
        }

        # переход с мобильной версии на полную, ставим куку
        # m.host.com => host.com?full=1
        if (!empty($_GET['full']) && strpos(\Request::referer(), $sMobileHost) !== false) {
            \Request::setCOOKIE($sForceCookieName, 1);

            return ($isMobile = false);
        }

        # проверяем наличие куки для принудительного отображения полной версии
        $bForceFull = static::DI('input')->cookie($sForceCookieName, TYPE_BOOL);
        if (!empty($bForceFull)) {
            # кука есть => показываем полную версию
            return ($isMobile = false);
        }

        # определяем зашел ли пользователь с мобильного устройства, если да, тогда выполняем редирект на поддомен
        $sMobileRedirect = 'http://' . $sMobileHost;
        if (!empty($_SERVER['REQUEST_URI'])) {
            if (strpos($sHttpHost, '.' . SITEHOST)) {
                # находимся на поддомене, значит нет такой страницы в мобильной версии
                $sMobileRedirect .= '';
            } else {
                $sMobileRedirect .= $_SERVER['REQUEST_URI'];
            }
        }

        $isMobile = static::deviceDetector(self::DEVICE_PHONE);
        if ($isMobile) {
            \Request::redirect($sMobileRedirect);
        }

        return $isMobile;
    }

    /**
     * Определение типа устройства
     * @param null|string|array $check тип определяемого устройства или NULL
     * @return bool
     */
    public static function deviceDetector($check = null)
    {
        static $device;
        if (!isset($device)) {
            $detector = new \Mobile_Detect();
            $device = (!$detector->isMobile() ? self::DEVICE_DESKTOP :
                ($detector->isTablet() ? self::DEVICE_TABLET :
                    self::DEVICE_PHONE));
        }
        if (empty($check)) {
            return $device;
        } else if (is_array($check)) {
            return in_array($device, $check);
        } else {
            return ($device === $check);
        }
    }

    /**
     * Отправка письма на основе шаблона
     * @param array $tplVars данные подставляемые в шаблон
     * @param string $tplName ключ шаблона письма
     * @param string $to email получателя
     * @param string|bool $subject заголовок письма или FALSE (берем из шаблона письма)
     * @param string $from email отправителя
     * @param string $fromName имя отправителя
     * @param string $lng ключ языка шаблона
     * @return bool
     */
    public static function sendMailTemplate($tplVars, $tplName, $to, $subject = false, $from = '', $fromName = '', $lng = LNG)
    {
        try {
            $aTplData = \Sendmail::i()->getMailTemplate($tplName, $tplVars, $lng);
            if (BFF_LOCALHOST) {
                static::log(array('tpl' => $tplName, 'data' => $aTplData, 'vars' => $tplVars, 'to' => $to));
                return true;
            }
            return \Sendmail::i()->sendMail($to, ($subject !== false ? $subject : $aTplData['subject']), $aTplData['body'], $from, $fromName);
        } catch (\Exception $e) {
            static::DI('errors')->set($e->getMessage(), true);
        }
    }

    /**
     * Отправка письма
     * @param string $to email получателя
     * @param string $subject заголовок письма
     * @param string $from email отправителя
     * @param string $fromName имя отправителя
     * @return bool
     */
    public static function sendMail($to, $subject, $body, $from = '', $fromName = '')
    {
        return \Sendmail::i()->sendMail($to, $subject, $body, $from, $fromName);
    }

    /**
     * Формирование пути
     * @param string $sFolder вложенная директория
     * @param string|bool $sType тип пути(доступные: 'images') или FALSE
     * @return string
     */
    public static function path($sFolder, $sType = false)
    {
        $sep = DIRECTORY_SEPARATOR;
        if ($sType === false) {
            return PATH_PUBLIC . 'files' . $sep . (!empty($sFolder) ? $sFolder . $sep : '');
        }
        switch ($sType) {
            case 'images':
                return PATH_PUBLIC . 'files' . $sep . 'images' . $sep . $sFolder . $sep;
                break;
        }
    }

    /**
     * Формирование static URL
     * @param string $sPart часть URL
     * @param string|bool $sType тип (доступные: 'images') или FALSE
     * @return string
     */
    public static function url($sPart, $sType = false)
    {
        switch ($sType) {
            case 'images':
                return SITEURL_STATIC . '/files/images/' . $sPart . '/';
                break;
        }
        return SITEURL_STATIC . '/files/' . (!empty($sPart) ? $sPart . '/' : '');
    }

    /**
     * Формирование базового URL
     * @param boolean $trailingSlash
     * @param string $languageKey ключ языка
     * @param array $subdomains поддомены
     * @param string
     */
    public static function urlBase($trailingSlash = true, $languageKey = LNG, array $subdomains = array())
    {
        $subdomains = ( ! empty($subdomains) ? join('.', $subdomains) . '.' : '' );
        return Request::scheme() . '://' . $subdomains . SITEHOST . static::locale()->getLanguageUrlPrefix($languageKey, $trailingSlash);
    }

    /**
     * Формирование URL для переключения языка
     * @param string $languageKey ключ языка
     * @param boolean $addQuery добавлять в URL строку запроса
     * @return string
     */
    public static function urlLocaleChange($languageKey = LNG, $addQuery = true)
    {
        $url = Request::scheme() . '://' . Request::getSERVER('HTTP_HOST'); # proto + host
        $url.= static::locale()->getLanguageUrlPrefix($languageKey, true); # locale
        $url.= static::route(array(), array('return-request-uri'=>true)); # uri
        if ($addQuery) {
            $query = \Request::getSERVER('QUERY_STRING'); # query
            if (!empty($query)) {
                $url .= '?'.$query;
            }
        }
        return $url;
    }

    /**
     * Формирование ajax URL
     * @param string $moduleName название модуля
     * @param string $sActionQuery доп. параметры запроса
     * @return string
     */
    public static function ajaxURL($moduleName, $sActionQuery)
    {
        return '/index.php?bff=ajax&s=' . $moduleName . '&act=' . $sActionQuery;
    }

    /**
     * Роутинг
     * @param array $rewrite массив правил роутинга: array(key=>value, ...)
     *   key: string регулярное выражение, определяющее некоторый URL @example ([\d]+)\.html, /users/shop
     *   value: string строка, определяющая, итоговый модуль-метод-параметры через "/"
     *      [module]/[method]/[param1=\\1&param2=\\2&test=www]
     * @param array|boolean $options настройки:
     *   'wrap' - обворачивать регулярное выражение,
     *   'return-request-uri' - вернуть текущий uri запроса
     *   'landing-pages' - задействовать посадочные страницы
     *   'init-class-event' - инициализировать bff::$class, bff::$event
     * @return array: array('class'=>'','event'=>'','params'=>array())
     */
    public static function route(array $rewrite = array(), $options = false)
    {
        static $req;
        # parse request URI
        if (!isset($req)) {
            $req = preg_replace('/\/+/', '/', \Request::uri()); # // => /
            $req = ltrim($req, '/'); # remove first / (left)
            $req = preg_replace("/^(.*)\?.*$/U", '$1', $req); # remove query "?xxx"
            $req = preg_replace('/^(' . join('|', static::locale()->getLanguages()) . ')\/(.*)$/U', '$2', $req);
            if (method_exists('bff', 'routeEx')) $req = static::routeEx($req);
        }

        # options
        $options = array_merge(array('wrap'=>true, 'landing-pages'=>false, 'init-class-event'=>true, 'return-request-uri'=>false),
            (!is_array($options) ? array('wrap' => !empty($options)) : $options));

        # return request uri
        if ($options['return-request-uri']) {
            return $req;
        }

        # wrap keys
        if ($options['wrap']) {
            $rewriteRes = array();
            foreach ($rewrite as $k => $v) $rewriteRes['#^' . $k . '$#i'] = $v;
            $rewrite = $rewriteRes;
            unset($rewriteRes);
        }

        # landing pages
        if ($options['landing-pages']) {
            $reqNew = \SEO::landingPage($req);
            if ($reqNew !== false && $req !== $reqNew) {
                $req = ltrim($reqNew, '/ ');
                $query = mb_stripos($req, '?');
                if ($query !== false) {
                    list($query, $req) = array(mb_substr($req, $query+1), mb_substr($req, 0, $query));
                    if (!empty($query)) {
                        parse_str($query, $query);
                        if (!empty($query)) {
                            foreach ($query as $k=>&$v) {
                                if (!isset($_GET[$k])) $_GET[$k] = $v;
                                if (!isset($_POST[$k])) $_POST[$k] = $v;
                            }
                        } unset($v);
                    }
                }
            }
        }

        # search route
        $reqResult = preg_replace(array_keys($rewrite), array_values($rewrite), $req, 1, $nCount);

        # more then one replacement > break on the first one
        if ($nCount > 1) {
            foreach ($rewrite as $k => $v) {
                $reqResult = preg_replace($k, $v, $req, 1, $nCount);
                if ($nCount) break;
            }
        }
        $reqResult = (!$nCount || $reqResult == '') ? array() : explode('/', $reqResult, 3);

        # init class-event-params
        $result = array('class' => false, 'event' => false, 'params' => array());
        if (!empty($reqResult)) {
            if (isset($reqResult[0])) $result['class'] = $reqResult[0];
            if (isset($reqResult[1])) $result['event'] = $reqResult[1];
            if (isset($reqResult[2])) {
                parse_str($reqResult[2], $result['params']);
                $_GET = array_merge($_GET, $result['params']);
            }
            if ($options['init-class-event']) {
                if (!empty($result['class'])) static::$class = $result['class'];
                if (!empty($result['event'])) static::$event = $result['event'];
            }
        }

        return $result;
    }

    /**
     * Выполняем логирование в файл
     * @param string|array $content данные
     * @param string $fileName название log-файла, например errors.log
     * @param string $filePath путь к log-файлу
     * @return mixed @see \bff\logs\File::log
     */
    public static function log($content, $fileName = 'errors.log', $filePath = '')
    {
        static $loggers;
        if (empty($filePath)) $filePath = PATH_BASE . 'files' . DS . 'logs';
        $key = $filePath . $fileName;
        if (!isset($loggers[$key])) {
            $loggers[$key] = new File($filePath, $fileName);
        }

        return $loggers[$key]->log((is_array($content) ? print_r($content, true) : $content));
    }

    /**
     * Установка meta тегов
     * @param string|bool $title заголовок страницы
     * @param string|bool $keywords ключевые слова
     * @param string|bool $description описание
     * @param array $macrosData данные для макросов
     * @param bool $last true - окончательный вариант, false - перекрываемый более поздним вызовом setMeta
     */
    public static function setMeta($title = false, $keywords = false, $description = false, array $macrosData = array(), $last = true)
    {
        static $set = false;
        if ($set === true && !$last) return;
        if ($last) $set = true;

        # заменяем макросы
        $data = array();
        if (!empty($title)) $data['mtitle'] = $title;
        if (!empty($keywords)) $data['mkeywords'] = $keywords;
        if (!empty($description)) $data['mdescription'] = $description;
        $data = \SEO::i()->metaTextPrepare($data, $macrosData);

        # устанавливаем meta теги
        foreach ($data as $k => &$v) {
            if (empty($v)) continue;
            \SEO::i()->metaSet($k, $v);
            config::set($k . '_' . LNG, trim($v, ' -|,')); # old version compability
        }
        unset($v);
    }

    /**
     * Метод, вызываемый перед завершением запроса
     */
    public static function shutdown()
    {
        exit;
    }

    /**
     * Autoload
     * @param string $className имя требуемого класса
     */
    public static function autoload($className)
    {
        $className = ltrim($className, '\\');
        if (isset(static::$autoloadMap[$className])) {
            list($group, $path) = static::$autoloadMap[$className];

            # ищем среди компонентов
            switch ($group) {
                case 'core': # ядра
                    if (file_exists(PATH_CORE . $path)) {
                        include(PATH_CORE . $path);

                        return class_exists($className, false) || interface_exists($className, false);
                    }
                    break;
                case 'app': # приложения
                    if (file_exists(PATH_BASE . $path)) {
                        include(PATH_BASE . $path);

                        return class_exists($className, false) || interface_exists($className, false);
                    }
                    break;
            }
        } else {
            # ищем среди компонентов ядра
            if (strpos($className, 'bff\\') === 0) {
                $path = mb_strtolower(str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 4))) . '.php';
                if (file_exists(PATH_CORE . $path)) {
                    include(PATH_CORE . $path);

                    return class_exists($className, false) || interface_exists($className, false);
                }
            } else {
                # ищем требуемый класс среди модулей (ядра/приложения)
                try {
                    \bff::i()->getModule($className);
                } catch (\Exception $e) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Расширяем autoload
     * @param array $classes :
     *  array(
     *      'имя класса' => array('ключ группы, варианты: [app, core]', 'путь к файлу, относительно директории группы')
     *  )
     */
    public static function autoloadEx(array $classes = array())
    {
        if (!empty($classes)) {
            foreach ($classes as $k => $v) {
                static::$autoloadMap[$k] = $v;
            }
        }
    }

    /**
     * @var array autoload карта
     *  array(
     *      'имя класса' => array('ключ группы: [app, core]', 'путь к файлу, относительно директории группы')
     *  )
     */
    protected static $autoloadMap = array(
        # app
        'tpl'                   => array('app', 'app/tpl.php'),
        'tplAdmin'              => array('app', 'app/tpl.admin.php'),
        'Module'                => array('app', 'app/module.php'),
        'Security'              => array('app', 'app/security.php'),
        'js'                    => array('core', 'js.php'),
        'User'                  => array('core', 'user.php'),
        'HTML'                  => array('core', 'html.php'),
        'View'                  => array('core', 'view.php'),
        'CMenu'                 => array('core', 'menu.php'),
        'Request'               => array('core', 'request.php'),
        'CSitemapXML'           => array('core', 'utils/sitemap.php'),
        # files
        'CUploader'             => array('core', 'files/uploader.php'),
        'CImageUploader'        => array('core', 'img/image.uploader.php'),
        'CImagesUploader'       => array('core', 'img/images.uploader.php'),
        'CImagesUploaderTable'  => array('core', 'img/images.uploader.table.php'),
        # captcha
        'CCaptchaProtection'    => array('core', 'captcha/captcha.protection.php'),
        # core modules
        'UsersAvatar'           => array('app', 'modules/users/users.avatar.php'),
        'UsersSocial'           => array('app', 'modules/users/users.social.php'),
        # database
        'bff\db\Dynprops'       => array('core', 'db/dynprops/dynprops.php'),
        'bff\db\Categories'     => array('core', 'db/categories/categories.php'),
        'bff\db\Comments'       => array('core', 'db/comments/comments.php'),
        'bff\db\Tags'           => array('core', 'db/tags/tags.php'),
        'bff\db\NestedSetsTree' => array('core', 'db/nestedsets/nestedsets.php'),
        'bff\db\Publicator'     => array('core', 'db/publicator/publicator.php'),
        # external
        'Pimple'                => array('core', 'external/pimple.php'),
        'Mobile_Detect'         => array('core', 'external/mobile.detect.php'),
        'CMail'                 => array('core', 'external/mail.php'),
        'CSmarty'               => array('core', 'external/smarty.php'),
        'CWysiwyg'              => array('core', 'external/wysiwyg.php'),
        'qqFileUploader'        => array('core', 'external/qquploader.php'),
        # core
        'Model'                 => array('core', 'model.php'),
        'Errors'                => array('core', 'errors.php'),
        'func'                  => array('core', 'utils/func.php'),
        'Pagination'            => array('core', 'utils/pagination.php'),
        'config'                => array('core', 'config.php'),
        'Cache'                 => array('core', 'cache/cache.php'),
        'Component'             => array('core', 'component.php'),
    );

}