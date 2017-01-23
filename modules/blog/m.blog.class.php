<?php

class M_Blog
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        # Посты
        if ($security->haveAccessToModuleToMethod('blog', 'posts')) {
            $menu->assign('Блог', 'Посты', 'blog', 'posts', true, 1,
                array('rlink' => array('event' => 'posts&act=add'))
            );
        }

        # Категории
        if ($security->haveAccessToModuleToMethod('blog', 'categories') && Blog::categoriesEnabled()) {
            $menu->assign('Блог', 'Категории', 'blog', 'categories', true, 2,
                array('rlink' => array('event' => 'categories&act=add'))
            );
        }

        # Теги
        if ($security->haveAccessToModuleToMethod('blog', 'tags') && Blog::tagsEnabled()) {
            $menu->assign('Блог', 'Теги', 'blog', 'tags', true, 3);
        }

        # SEO
        if ($security->haveAccessToModuleToMethod('blog', 'seo')) {
            $menu->assign('SEO', 'Блог', 'blog', 'seo_templates_edit', true, 30);
        }

        # Настройки
        if ($security->haveAccessToModuleToMethod('blog', 'settings')) {
            $menu->assign('Блог', 'Настройки', 'blog', 'settings', true, 40);
        }
    }
}