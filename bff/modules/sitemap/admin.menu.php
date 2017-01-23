<?php

if ($security->haveAccessToModuleToMethod('sitemap', 'listing')) {
    $menu->assign('Карта сайта', 'Управление меню', 'sitemap', 'listing', true, 1);
    $menu->assign('Карта сайта', 'Добавить меню', 'sitemap', 'add', false, 2);
    $menu->assign('Карта сайта', 'Редактирование меню', 'sitemap', 'edit', false, 3);
}