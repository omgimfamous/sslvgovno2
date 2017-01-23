<?php

class M_Users
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('users', 'members-listing')) {
            $menu->assign('Пользователи', 'Список пользователей', 'users', 'listing', true, 1,
                array('rlink' => array('event' => 'user_add'))
            );
            $menu->assign('Пользователи', 'Настройки профиля', 'users', 'profile', false, 10);
        }
        if ($security->haveAccessToModuleToMethod('users', 'admins-listing')) {
            $menu->assign('Пользователи', 'Список модераторов', 'users', 'listing_moderators', true, 2,
                array('rlink' => array('event' => 'user_add'))
            );
        }
        if ($security->haveAccessToModuleToMethod('users', 'users-edit')) {
            $menu->assign('Пользователи', 'Добавить пользователя', 'users', 'user_add', false, 3);
            $menu->assign('Пользователи', 'Редактирование пользователя', 'users', 'user_edit', false, 4);
            $menu->assign('Пользователи', 'Удаление пользователя', 'users', 'user_delete', false, 5);
            $menu->assign('Пользователи', 'Блокировка пользователей', 'users', 'ban', true, 6);
        }

        # SEO
        if ($security->haveAccessToModuleToMethod('users', 'seo')) {
            $menu->assign('SEO', 'Пользователи', 'users', 'seo_templates_edit', true, 25);
        }
    }
}