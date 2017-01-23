<?php

if ($security->haveAccessToModuleToMethod('users', 'groups-listing')) {
    $menu->assign('Пользователи', 'Группы', 'users', 'group_listing', true, 50);
    $menu->assign('Пользователи', 'Создание группы', 'users', 'group_add', false, 51);
    $menu->assign('Пользователи', 'Редактирование группы', 'users', 'group_edit', false, 52);
    $menu->assign('Пользователи', 'Доступ группы', 'users', 'group_permission_listing', false, 53);
}