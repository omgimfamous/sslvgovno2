<?php

define('BFF_ADMINPANEL', 1);
require __DIR__.'/../../bff.php';



$aTemplateData = array();
if (bff::$class) {
    try {
        $aTemplateData['centerblock'] = bff::i()->callModule(bff::$class.'_'.bff::$event);
    } catch (\Exception $e) {
        Errors::i()->set($e->getMessage().', '.$e->getFile().' ['.$e->getCode().']', true);
        if ( ! bff::security()->haveAccessToAdminPanel() ) {
            echo '<pre>', print_r(Errors::i()->get(0,0), true), '</pre>';
            exit;
        }
    }
}

if ( ! bff::security()->haveAccessToAdminPanel()) {
    Request::redirect( tplAdmin::adminLink('login','users') );
}

# Формируем меню
$aTemplateData['menu'] = CMenu::i()->init()->buildAdminMenu(array('Панель управления',
                         'Объявления', 'Магазины', 'Пользователи', 'Счета', 'Баннеры', 'Сообщения',
                         'Блог', 'Помощь', 'Страницы', 'Контакты', 'Работа с почтой',
                         'Карта сайта и меню', 'SEO', 'Настройка сайта'),
                         array('dev','users','geo','svc','seo')); // - sendmail, bills, site, sitemap

if ( ! bff::$class) {
    Request::redirect($aTemplateData['menu']['url']);
}

$aTemplateData['user_login']   = bff::security()->getUserLogin();
$aTemplateData['db_querycnt']  = bff::database()->statQueryCnt();
$aTemplateData['err_success']  = Errors::i()->isSuccess();
$aTemplateData['err_errors']   = Errors::i()->get(false, !FORDEV);
$aTemplateData['err_autohide'] = Errors::i()->autohide();
$aTemplateData['page']         = tplAdmin::adminPageSettings();

echo View::renderLayout($aTemplateData);