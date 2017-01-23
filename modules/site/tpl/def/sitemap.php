<?php
    $lang_title = _t('', 'Карта сайта');
?>
<div class="row-fluid">
    <div class="l-page span12">
            <? # Хлебные крошки: ?>
            <? if(DEVICE_DESKTOP_OR_TABLET) {
                echo tpl::getBreadcrumbs(array(
                    array('title'=>$lang_title, 'link'=>'#', 'active'=>true)
                ));
            } ?>
            <h1><?= $lang_title ?></h1>
            <div class="l-sitemap row-fluid">

                <? foreach($cats as &$row) { ?>
                    <div class="span4">
                        <? foreach($row as &$c) { ?>
                            <ul>
                                <li>
                                    <img src="<?= $c['icon'] ?>" alt="<?= $c['title'] ?>" />
                                    <a href="<?= $c['link'] ?>"><?= $c['title'] ?></a>
                                    <ul>
                                        <? foreach($c['subs'] as &$cc) { ?>
                                            <li><a href="<?= $cc['link'] ?>"><?= $cc['title'] ?></a></li>
                                        <? } unset($cc); ?>
                                    </ul>
                                </li>
                            </ul>
                        <? } unset($c); ?>
                    </div>
                <? } unset($row); ?>
                <div class="clearfix"></div>
            </div>
        <div class="l-info">
            <?= $seotext ?>
        </div>
    </div>
</div>