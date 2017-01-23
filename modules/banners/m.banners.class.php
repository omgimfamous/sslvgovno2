<?php

class M_Banners
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if (!$security->haveAccessToModuleToMethod('banners', 'listing')) {
            return;
        }

        # баннеры
        $menu->assign('Баннеры', 'Список', 'banners', 'listing', true, 1, array(
                'rlink' => array('event' => 'add')
            )
        );
        $menu->assign('Баннеры', 'Добавление баннера', 'banners', 'add', false, 2);
        $menu->assign('Баннеры', 'Редактирование баннера', 'banners', 'edit', false, 3);

        # статистика
        $menu->assign('Баннеры', 'Статистика по баннеру', 'banners', 'statistic', false, 4);

        # позиции
        $menu->assign('Баннеры', 'Позиции', 'banners', 'positions', true, 5, (FORDEV ? array(
                'rlink' => array('event' => 'positions&act=add')
            ) : array())
        );
        $menu->assign('Баннеры', 'Удаление позиции', 'banners', 'position_delete', false, 6);
    }
}