<?php

class M_Site
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        # страницы
        if ($security->haveAccessToModuleToMethod('site-pages', 'listing')) {
            $menu->assign('Страницы', 'Список страниц', 'site', 'pageslisting', true, 1,
                array('rlink' => array('event' => 'pagesAdd'))
            );
            $menu->assign('Страницы', 'Добавить cтраницу', 'site', 'pagesAdd', false, 2);
            $menu->assign('Страницы', 'Редактирование cтраницы', 'site', 'pagesEdit', false, 3);
        }

        # настройки сайта
        if ($security->haveAccessToModuleToMethod('site', 'siteconfig')) {
            $menu->assign('Настройки сайта', 'Общие настройки', 'site', 'siteconfig', true, 10);
        }

        # seo
        if ($security->haveAccessToModuleToMethod('site', 'seo')) {
            $menu->assign('SEO', 'Настройки сайта', 'site', 'seo_templates_edit', true, 50);
        }

        # инструкции
        //$menu->assign('Настройки сайта', 'Инструкции', 'site', 'instructions', true, 50);

        # счетчики
        if ($security->haveAccessToModuleToMethod('site', 'counters')) {
            $menu->assign('Настройки сайта', 'Счетчики', 'site', 'counters', true, 60,
                array('rlink' => array('event' => 'counters&act=add'))
            );
        }

        # валюты
        if ($security->haveAccessToModuleToMethod('site', 'currencies')) {
            $menu->assign('Настройки сайта', 'Валюты', 'site', 'currencies', true, 70);
        }
    }
}