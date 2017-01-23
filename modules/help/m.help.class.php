<?php

class M_Help
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        # Вопросы
        if ($security->haveAccessToModuleToMethod('help', 'questions')) {
            $menu->assign('Помощь', 'Вопросы', 'help', 'questions', true, 1,
                array('rlink' => array('event' => 'questions&act=add'))
            );
        }
        # Категории
        if ($security->haveAccessToModuleToMethod('help', 'categories')) {
            $menu->assign('Помощь', 'Категории', 'help', 'categories', true, 5,
                array('rlink' => array('event' => 'categories&act=add'))
            );
        }
        # SEO
        if ($security->haveAccessToModuleToMethod('help', 'seo')) {
            $menu->assign('SEO', 'Помощь', 'help', 'seo_templates_edit', true, 40);
        }
    }
}