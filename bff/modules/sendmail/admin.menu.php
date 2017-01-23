<?php

if ($security->haveAccessToModuleToMethod('sendmail', 'templates-listing')) {
    $menu->assign('Работа с почтой', 'Шаблоны писем', 'sendmail', 'template_listing', true);
    $menu->assign('Работа с почтой', 'Редактирование шаблона', 'sendmail', 'template_edit', false);
}