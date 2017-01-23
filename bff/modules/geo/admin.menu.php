<?php

# регионы
if ($security->haveAccessToModuleToMethod('site', 'regions')) {
    if (Geo::$useRegions !== false) {
        $menu->assign('Настройки сайта', 'Регионы', 'geo', 'regions', true, 26);
    }
}