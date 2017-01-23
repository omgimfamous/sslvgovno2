<?php

# Site offline mode
if (!intval(config::get('enabled', 0)) && !Site::offlineIgnore()) {
    header(Request::getSERVER('SERVER_PROTOCOL', 'HTTP/1.1').' 503 Service Unavailable', true, 503);
    header('Retry-After: 3600');
    $aData = array();
    View::renderTemplate($aData, 'offline', false, true);
    bff::shutdown();
}



# Routing
if( ! bff::$class) {
bff::route(array(
    # index
    ''                                 => 'site/index/', # главная страница
    'help(.*)'                         => 'help/route/', # помощь
    # bbs
    'search/(.*)/(.*)\-([\d]+)\.html'  => 'bbs/view/item_view=1&cat=$1&id=$3', # просмотр объявления
    '(.*)/search/(.*)/(.*)\-([\d]+)\.html'  => 'bbs/view/item_view=1&cat=$2&id=$4', # просмотр объявления (при изменении формирования URL)
    'search/(.*)'                      => 'bbs/search/cat=$1', # поиск объявлений
    '(.*)/search/(.*)'                 => 'bbs/search/cat=$2', # поиск объявлений (при изменении формирования URL)
    'item/(.*)'                        => 'bbs/$1/', # объявления: добавление, редактировани, активация ...
    # users
    'user/loginsocial/(.*)'            => 'users/loginSocial/provider=$1', # авторизация через соц. сети
    'user/(.*)'                        => 'users/$1/', # авторизация, регистрация ...
    'users/([^/]+)/(.*)'               => 'users/profile/login=$1&tab=$2', # профиль пользователя
    'cabinet/(.*)'                     => 'users/my/tab=$1', # кабинет пользователя
    # shops
    'shops?(.*)'                       => 'shops/route/', # магазины
    '(.*)/shops?(.*)'                  => 'shops/route/', # магазины (при изменении формирования URL)
    # other
    'bn/(click|show)/(.*)'             => 'banners/$1/id=$2', # баннеры: отображение, переход
    '([a-z0-9\-]+)\.html'              => 'site/pageView/page=$1', # статические страницы
    'blog(.*)'                         => 'blog/route/', # блог
    'contact/?'                        => 'contacts/write/', # контакты
    'services(.*)'                     => 'site/services/', # услуги - список
    'away/(.*)'                        => 'site/away/', # внешние ссылки
    'sitemap(.*)'                      => 'site/sitemap/', # карта сайта
    # bill
    'bill/process/(.*)'                => 'bills/processPayRequest/psystem=$1', # процессинг оплаты услуг
    'bill/(success|fail)'              => 'bills/$1/', # результат процессинга
), array('landing-pages'=>true));
}

$aTemplateData = array();
try {
    $aTemplateData['centerblock'] = bff::i()->callModule(bff::$class.'_'.bff::$event);
} catch(\Exception $e){
    Errors::i()->set($e->getMessage().', '.$e->getFile().' ['.$e->getCode().']', true);
    Errors::i()->error404();
}

echo View::renderLayout($aTemplateData);
exit;