<?php

# Время запроса
define('BFF_NOW', $_SERVER['REQUEST_TIME']);
define('BFF_VERSION', 3.0);
define('BFF_SUPPORT', 'pjotrs@eptron.eu');

# Подключение файла лицензии (BFF-LICENSE)


# Основные компоненты
require PATH_CORE . 'common.php';
require PATH_CORE . 'singleton.php';
require PATH_CORE . 'base/app.php';

# Autoload
spl_autoload_register(array('bff\base\app', 'autoload'));

# Загрузка системных настроек
config::sys(false);

# Общие константы
define('BFF_DEBUG', config::sys('debug'));
define('BFF_LOCALHOST', config::sys('localhost'));
error_reporting(config::sys('php.errors.reporting'));
ini_set('display_errors', config::sys('php.errors.display'));
header('Content-type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');
date_default_timezone_set(config::sys('date.timezone'));
define('SITEHOST', config::sys('site.host'));
define('SITEURL', Request::scheme() . '://' . SITEHOST);
define('SITEURL_STATIC', rtrim(config::sys('site.static'), '/ '));
define('DB_PREFIX', config::sys('db.prefix'));
if (!defined('BFF_SESSION_START')) {
    define('BFF_SESSION_START', 1);
}

# images
define('BFF_IMAGES_DEFAULT', 'default.gif');
# users
define('USERS_GROUPS_SUPERADMIN', 'x71');
define('USERS_GROUPS_MODERATOR', 'c60');
define('USERS_GROUPS_MEMBER', 'z24');

# Константы таблиц базы данных
config::file('db.tables');

if (BFF_DEBUG) {
    ini_set('display_startup_errors', 1);
    include(PATH_CORE . 'utils' . DIRECTORY_SEPARATOR . 'vardump.php');
} else {
    if (!function_exists('debug')) {
        function debug()
        {
        }
    }
}