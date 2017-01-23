<?php

    $coveringCity = Geo::coveringType(Geo::COVERING_CITY);
    if ( ! $coveringCity)
    {
        tpl::includeJS('filter', false, 3);

        # фильтр: регион
        $regionID = 0;
        $regionData = Geo::filter(); # user
        if( ! empty($regionData['id']) ) {
            $regionID = $regionData['id'];
        }
    }
    $titleAll = _t('filter', 'Все регионы');
    if (!$coveringCity && Geo::coveringType(Geo::COVERING_COUNTRIES)) {
        $titleAll = _t('filter', 'Все страны');
    }
?>
<!-- BEGIN filter -->
<? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
<div class="f-navigation row-fluid rel">
    <!--for: desktop-->
    <div class="f-navigation__regions__title span5 hidden-phone">
        <? if ( ! $coveringCity) { ?>
            <span><?= _t('filter', 'Поиск в регионе:') ?> </span>
            <a href="#" class="ajax" id="j-f-region-desktop-link"><?= ( $regionID > 0 ? $regionData['title'] : $titleAll ) ?></a>
        <? } ?>
    </div>
    <div class="f-navigation__menu rel span7 hidden-phone">
        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
        <!--for: desktop-->
        <div class="f-navigation__menu_desktop visible-desktop">
            <ul class="nav nav-tabs">
                <li class="pull-right">&nbsp;&nbsp;&nbsp;&nbsp;</li>
                <?
                    $aMainMenu = array_reverse( Sitemap::view('main') );
                    foreach($aMainMenu as $k=>$v) {
                        ?><li class="pull-right <? if($v['a']) { ?>active<? } ?>"><a href="<?= $v['link'] ?>"<?= ($v['target'] === '_blank' ? ' target="_blank"' : '') ?>><?= $v['title'] ?></a></li><?
                    }
                ?>
            </ul>
        </div>
        <!--for: tablet-->
        <div class="f-navigation__menu_tablet rel visible-tablet">
            <ul class="nav nav-tabs pull-right">
                <li>
                    <div class="btn-group">
                        <?
                            $aMainMenu = Sitemap::view('main');
                            $mainMenuHTML = ''; $mainMenuActive = false;
                            foreach ($aMainMenu as $k=>$v) {
                                $mainMenuHTML .= '<li><a href="'.$v['link'].'"'.($v['a'] ? ' class="active"' : '') .''.($v['target'] === '_blank' ? ' target="_blank"' : '').'>'. $v['title'] .'</a></li>';
                                if ($v['a']) $mainMenuActive = $v;
                            }
                            if( empty($mainMenuActive) ) {
                                $mainMenuActive = reset($aMainMenu);
                            }
                        ?>
                        <button class="btn selected" onclick="bff.redirect('<?= $mainMenuActive['link'] ?>')"><?= $mainMenuActive['title'] ?></button>
                        <button class="btn dropdown-toggle" data-toggle="dropdown">
                            <i class="fa fa-bars"></i>
                        </button>
                        <ul class="dropdown-menu"><?= $mainMenuHTML ?></ul>
                    </div>
                </li>
            </ul>
        </div>
        <? } ?>
    </div>
</div>
<? } ?>
<? /* Форма поиска */ ?>
<?= Site::i()->filterForm() ?>