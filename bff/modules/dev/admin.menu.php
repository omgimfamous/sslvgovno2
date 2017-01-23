<?php

if (FORDEV && !bff::demo()) {
    $menu->assign('Development', 'Настройки системы', 'dev', 'sys', true, 10);
    $menu->assign('Development', 'Доступ к папкам', 'dev', 'dirs_listing', true, 20);
    $menu->assign('Development', 'Создать модуль', 'dev', 'module_create', true, 30);
    $menu->assign('Development', 'PHPInfo', 'dev', 'phpinfo1', true, 40);
    $menu->assign('Development', 'Дополнительно', 'dev', 'utils', true, 50);
    $menu->assign('Development', 'Права: модули/методы', 'dev', 'mm_listing', true, 60,
        array('rlink' => array('event' => 'mm_add', 'title' => '+'))
    );
    $menu->assign('Development', 'Права: добавить модуль/метод', 'dev', 'mm_add', false);

    $menu->assign('Development', 'Локализация', 'dev', 'locale_data', true, 70);
}