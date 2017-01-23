<?php

class M_Shops
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        $sMenuTitle = 'Магазины';

        # Магазины
        if ($security->haveAccessToModuleToMethod('shops', 'manage')) {
            $menu->assign($sMenuTitle, 'Список', 'shops', 'listing', true, 1,
                array('rlink' => array('event' => 'add'))
            );
            $menu->assign($sMenuTitle, 'Добавление', 'shops', 'add', false, 2);
            $menu->assign($sMenuTitle, 'Редактирование', 'shops', 'edit', false, 3);
        }

        # Заявки на открытие / закрепление
        if ($security->haveAccessToModuleToMethod('shops', 'shops-requests') && (Shops::premoderation() || Shops::categoriesEnabled())) {
            $eventRequests = (Shops::premoderation() ? 'requests_open' : 'requests');
            $menu->adminHeaderCounter('заявки', 'shops_requests', 'shops', $eventRequests, 3, 'icon-shopping-cart');
            $menu->assign($sMenuTitle, 'Заявки', 'shops', $eventRequests, true, 10, array('counter' => 'shops_requests'));
        }

        # Жалобы
        if ($security->haveAccessToModuleToMethod('shops', 'claims-listing')) {
            $menu->assign($sMenuTitle, 'Жалобы', 'shops', 'claims', true, 15, array('counter' => 'shops_claims'));
        }

        # Категории
        if ($security->haveAccessToModuleToMethod('shops', 'categories') && Shops::categoriesEnabled()) {
            $menu->assign($sMenuTitle, 'Категории', 'shops', 'categories_listing', true, 20,
                array('rlink' => array('event' => 'categories_add'))
            );
            $menu->assign($sMenuTitle, 'Добавить категорию', 'shops', 'categories_add', false, 21);
            $menu->assign($sMenuTitle, 'Редактирование категории', 'shops', 'categories_edit', false, 22);
            $menu->assign($sMenuTitle, 'Типы категорий', 'shops', 'types', false, 23);
        }

        # Услуги
        if (bff::servicesEnabled() && $security->haveAccessToModuleToMethod('shops', 'svc')) {
            $menu->assign($sMenuTitle, 'Услуги', 'shops', 'svc_services', true, 30);
        }

        # SEO
        if ($security->haveAccessToModuleToMethod('users', 'seo')) {
            $menu->assign('SEO', $sMenuTitle, 'shops', 'seo_templates_edit', true, 20);
        }

        # Настройки
        if ($security->haveAccessToModuleToMethod('shops', 'settings')) {
            $menu->assign($sMenuTitle, 'Настройки', 'shops', 'settings', true, 50);
        }
    }
}