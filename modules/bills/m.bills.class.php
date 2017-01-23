<?php

class M_Bills
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if ($security->haveAccessToModuleToMethod('bills', 'listing')) {
            $menu->assign('Счета', 'Список счетов', 'bills', 'listing', true, 1);
        }
    }
}