<?php

# paths
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require 'paths.php';
require PATH_CORE . 'init.php';


class bff extends \bff\base\app
{
    static public $userSettings = 0;

    /**
     * Инициализация приложения
     * @return void
     */
    public function init()
    {
        # Инициализируем base\app

        parent::init();
        static::autoloadEx(array(
                'User' => array('app', 'app/user.php'),
            )
        );
        if (static::cron()) {
            return;
        }

        # Yandex Карты 2.1
        Geo::$ymapsCoordOrder = 'latlong';
        Geo::$ymapsDefaultCoords = '55.7481,37.6206';
        Geo::$ymapsJS = Request::scheme().'://api-maps.yandex.ru/2.1/?lang=ru_RU';

        # подключаем Javascript + CSS
        tpl::includeJS('jquery', true);
        tpl::includeJS('bff', true, 3);
        tpl::includeJS(array('bootstrap.min'), false);
        if (!static::adminPanel()) {
            # для фронтенда
            js::setDefaultPosition(js::POS_FOOT); # переносим все инициализируемые inline-скрипты в footer
            tpl::includeJS('app', false, 13);
            self::$userSettings = static::DI('input')->cookie(config::sys('cookie.prefix') . 'usett');
        } else {
            # для админки
            tpl::includeJS(array('admin/bff', 'fancybox'), true);
        }

        if (($userID = User::id())) {
            # актуализируем "Время последней активности" пользователя
            if ((BFF_NOW - func::SESSION('last_activity', 0)) >= config::sys('users.activity.timeout')) {
                Users::model()->userSave($userID, false, array('last_activity' => static::DI('database')->now()));
                func::setSESSION('last_activity', BFF_NOW);
            }
            # актуализируем счетчики пользователя
            static::DI('security')->userCounter(null);
        }
    }

    public static function isIndex()
    {
        return (self::$event == 'index' && self::$class == 'site');
    }



    /**
     * Устанавливаем активный пункт меню
     * @param string $sPath keyword пункта меню (sitemap)
     * @param bool $bUpdateMeta обновить meta-данные
     * @param mixed $mActiveStateData данные для активного пункта меню
     */
    public static function setActiveMenu($sPath, $bUpdateMeta = true, $mActiveStateData = 1)
    {
        if (Request::isAJAX()) {
            return;
        }
        $sPath = str_replace('//', '/main/', $sPath);
        Sitemap::i()->setActiveMenuByPath($sPath, $bUpdateMeta, $mActiveStateData);
    }

    /**
     * Устанавливаем / получаем данные о фильтре
     * @param string $sKey ключ фильтра
     * @param array|NULL $mData данные или NULL (получаем текущие)
     * @return mixed
     */
    public static function filter($sKey, $mData = null)
    {
        if (is_null($mData)) {
            return config::get('filter-' . $sKey, array());
        } else {
            config::set('filter-' . $sKey, $mData);
        }
    }

    /**
     * Проверка / сохранение типа текущего устройства:
     * > if( bff::device(bff::DEVICE_DESKTOP) ) - проверяем, является ли текущее устройство DESKTOP
     * > if( bff::device(array(bff::DEVICE_DESKTOP,bff::DEVICE_TABLET)) ) - проверяем, является ли текущее устройство DESKTOP или TABLET
     * > $deviceID = bff::device() - получаем текущий тип устройства
     * > bff::device(bff::DEVICE_DESKTOP, true) - сохраняем тип текущего устройства
     * @param string|array|bool $device ID устройства (self::DEVICE_), ID нескольких устройств или FALSE
     * @param bool $set true - сохраняем тип текущего устройства
     * @return bool|int
     */
    public static function device($device = 0, $set = false)
    {
        static $detected;
        $cookieKey = config::sys('cookie.prefix') . 'device';

        # получаем тип устройства
        if (!$set) {
            if (!isset($detected)) {
                $detected = static::input()->cookie($cookieKey, TYPE_STR);
                if (empty($detected)) {
                    $detected = static::deviceDetector();
                }
            }
            if (!empty($device)) {
                # для desktop загружаем весь контент (эмулируем все устройства)
                if (static::deviceDetector(self::DEVICE_DESKTOP)) {
                    return true;
                }

                return (is_string($device) ? $detected === $device :
                    (is_array($device) ? in_array($detected, $device, true) :
                        false));
            } else {
                return $detected;
            }
        } # устанавливаем тип устройства
        else {
            if (empty($device) || is_array($device) || !in_array($device, array(
                        self::DEVICE_DESKTOP,
                        self::DEVICE_TABLET,
                        self::DEVICE_PHONE
                    )
                )
            ) {
                $device = static::deviceDetector();
            }
            if ($device !== static::input()->cookie($cookieKey, TYPE_STR)) {
                unset($detected);
                setcookie($cookieKey, $device, time() + 604800, '/', '.' . SITEHOST);
                $_COOKIE[$cookieKey] = $device;
            }
        }
    }

    public static function shopsEnabled()
    {
        return !BBS::publisher(BBS::PUBLISHER_USER);
    }

    public static function servicesEnabled()
    {
        if (bff::adminPanel()) {
            return bff::moduleExists('svc', false);
        } else {
            return config::sys('services.enabled', false) && bff::moduleExists('svc', false);
        }
    }

    public static function urlAway($sURL)
    {
        $sURL = str_replace(array('http://', 'https://', 'ftp://'), '', $sURL);
        if (empty($sURL) || $sURL == '/') {
            return static::urlBase();
        }

        return static::urlBase(false) . '/away/?url=' . rawurlencode($sURL);
    }

    public static function routeEx($req)
    {
        if (Geo::urlType() == Geo::URL_SUBDIR &&
            $region = Geo::filterUrl('keyword')
        ) {
            $req = str_replace($region . '/', '', $req);
        }

        return $req;
    }

}

bff::i()->init();

# объявляем константы типа текущего устройства пользователя
define('DEVICE_DESKTOP', bff::device(bff::DEVICE_DESKTOP));
define('DEVICE_TABLET', bff::device(bff::DEVICE_TABLET));
define('DEVICE_PHONE', bff::device(bff::DEVICE_PHONE));
define('DEVICE_DESKTOP_OR_TABLET', DEVICE_DESKTOP || DEVICE_TABLET);
define('DEVICE_TABLET_OR_PHONE', DEVICE_TABLET || DEVICE_PHONE);