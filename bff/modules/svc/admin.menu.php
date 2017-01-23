<?php

if (FORDEV && !bff::demo() && bff::moduleExists('svc', false)) {
    $menu->assign('Development', 'Услуги', 'svc', 'manage', true, 31,
        array('rlink' => array('event' => 'manage&act=add'))
    );
}