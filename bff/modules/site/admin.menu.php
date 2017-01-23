<?php

# страницы
if ($security->haveAccessToModuleToMethod('site-pages', 'listing')) {
    $menu->assign('Страницы', 'Список страниц', 'site', 'pagesListing', true, 1,
        array('rlink' => array('event' => 'pagesAdd'))
    );
    $menu->assign('Страницы', 'Добавить cтраницу', 'site', 'pagesAdd', false, 2);
    $menu->assign('Страницы', 'Редактирование cтраницы', 'site', 'pagesEdit', false, 3);
}

# счетчики
if ($security->haveAccessToModuleToMethod('site', 'counters')) {
    $menu->assign('Настройки сайта', 'Счетчики', 'site', 'counters', true, 10,
        array('rlink' => array('event' => 'counters&act=add'))
    );
}

# валюты
if ($security->haveAccessToModuleToMethod('site', 'currencies')) {
    $menu->assign('Настройки сайта', 'Валюты', 'site', 'currencies', true, 7);
}