<?php

class M_Sendmail
{
    static function declareAdminMenu(CMenu $menu, Security $security)
    {
        if (!$security->haveAccessToModuleToMethod('sendmail', 'templates-listing')) {
            return;
        }

        $menu->assign('Работа с почтой', 'Список рассылок', 'sendmail', 'massend_listing', true, 1, array(
                'rlink' => array('event' => 'massend_form')
            )
        );
        $menu->assign('Работа с почтой', 'Информация о рассылке', 'sendmail', 'massend_receivers_listing', false, 2);
        $menu->assign('Работа с почтой', 'Начать рассылку', 'sendmail', 'massend_form', false, 3);

        $menu->assign('Работа с почтой', 'Уведомления', 'sendmail', 'template_listing', true, 10);
        $menu->assign('Работа с почтой', 'Уведомления / Редактирование', 'sendmail', 'template_edit', false, 11);

        if ($security->haveAccessToModuleToMethod('sendmail','wrappers')) {
            $menu->assign('Работа с почтой', 'Шаблоны писем', 'sendmail', 'wrappers', true, 20);
        }
    }
}