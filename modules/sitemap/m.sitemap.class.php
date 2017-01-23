<?php

class M_Sitemap
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('sitemap', 'listing')) {
            $menu->assign('Карта сайта и меню', 'Управление меню', 'sitemap', 'listing', true, 1);
        }
    }
}