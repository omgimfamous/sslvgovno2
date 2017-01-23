<?php

class M_InternalMail
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('internalmail', 'read')) {
            $menu->adminHeaderCounter('сообщения', 'internalmail_new', 'internalmail', 'listing', 4, 'icon-envelope',
                array('userCounter' => true)
            );

            $menu->assign('Сообщения', 'Личные сообщения', 'internalmail', 'listing', true, 1,
                array('counter' => 'internalmail_new', 'userCounter' => true)
            );
            $menu->assign('Сообщения', 'Личные переписка', 'internalmail', 'conv', false, 2);
        }

        if ($security->haveAccessToModuleToMethod('internalmail', 'spy')) {
            $menu->assign('Сообщения', 'Лента сообщений', 'internalmail', 'spy_lenta', true, 10);

            $menu->assign('Сообщения', 'Cообщения пользователя', 'internalmail', 'spy_listing', true, 20);
            $menu->assign('Сообщения', 'Переписка пользователя', 'internalmail', 'spy_conv', false, 21);
        }
    }
}