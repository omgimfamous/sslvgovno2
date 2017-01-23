<?php
$svc = intval($svc);
if( empty($svc_up_activate) && empty($svc) && empty($svc_press_status) ) {
    ?><div style="padding:5px 0;" class="desc">Нет активированных на текущий момент услуг</div><?
} else {
    if( ! empty($svc_up_activate) ) { ?>
        <div style="margin: 10px 0">Доступно поднятий: <b><?= $svc_up_activate ?></b></div>
    <? }
    if($svc & BBS::SERVICE_UP) { ?>
        <div style="margin: 10px 0">Поднято <b><?= tpl::date_format3($svc_up_date, 'd.m.Y') ?></b></div>
    <? }
    if($svc & BBS::SERVICE_MARK) { ?>
        <div style="margin: 10px 0">Выделено до <b><?= tpl::date_format3($svc_marked_to, 'd.m.Y H:i') ?></b></div>
    <? }
    if($svc & BBS::SERVICE_QUICK) { ?>
        <div style="margin: 10px 0">"Срочно" до <b><?= tpl::date_format3($svc_quick_to, 'd.m.Y H:i') ?></b></div>
    <? }
    if($svc & BBS::SERVICE_FIX) { ?>
        <div style="margin: 10px 0">Закреплено до <b><?= tpl::date_format3($svc_fixed_to, 'd.m.Y H:i') ?></b></div>
    <? }
    if($svc & BBS::SERVICE_PREMIUM) { ?>
        <div style="margin: 10px 0">Премиум до <b><?= tpl::date_format3($svc_premium_to, 'd.m.Y H:i') ?></b></div>
    <? }
    if( ! empty($svc_press_status) && BBS::PRESS_ON ) {
        if($svc_press_status == BBS::PRESS_STATUS_PAYED) { ?>
            <div style="margin: 10px 0">Пресса: <b>ожидает публикации</b></div>
        <? } else if($svc_press_status == BBS::PRESS_STATUS_PUBLICATED) { ?>
            <div style="margin: 10px 0">Пресса: <b>опубликовано в прессе <?= tpl::date_format2($svc_press_date) ?></b></div>
        <? }
    }
}
?>
<div style="margin: 10px 0"><a href="<?= $this->adminLink('listing&item='.$id, 'bills') ?>">История активации услуг объявления #<?= $id ?></a></div>
<?
