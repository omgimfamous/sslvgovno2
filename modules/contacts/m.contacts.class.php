<?php

class M_Contacts
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('contacts', 'view')) {
            $menu->adminHeaderCounter('контакты', 'contacts_new', 'contacts', 'listing', 5, 'icon-envelope');
            $menu->assign('Контакты', 'Список сообщений', 'contacts', 'listing', true, 1);
        }
    }
}