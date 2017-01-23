<?php

# Посадочные страницы
if (SEO::landingPagesEnabled() && $security->haveAccessToModuleToMethod('seo','landingpages')) {
    $menu->assign('SEO', 'Посадочные страницы', 'seo', 'landingpages', true, 100,
            array('rlink'=>array('event'=>'landingpages&act=add') ));

}