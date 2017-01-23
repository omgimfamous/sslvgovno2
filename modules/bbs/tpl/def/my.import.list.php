<?php

$dateLast = 0;
foreach($list as $v){
    $thisDate = tpl::date_format2($v['created'], false, true);
    
?>
    <? if( $dateLast !== $thisDate) {?>
        <tr>
            <td class="u-bill_list__date" colspan="6"><?= $thisDate ?></td>
        </tr>
    <? } ?>
    <? $dateLast = $thisDate; ?>
    <tr>
        <td class="u-bill__list__descr"><?= $v['cat_title'] ?></td>
        <td class="align-center"><?= $v['items_total'] ?></td>
        <td class="align-center"><?= $v['items_processed'] ?></td>
        <td class="align-left" style="padding-left: 10px;"><?= $v['comment_text'] ?></td>
        <td class="align-center"><span title="<?= tpl::date_format2($v['status_changed'], true, true) ?>"><?= $status[$v['status']] ?></span></td>
        <td class="align-center"><a href="<?= $v['file_link'] ?>" target="_blank"><i class="icon-download"></i></a></td>
    </tr>
<?
}

if(empty($list))
{ ?>
<tr>
    <td colspan="6" class="text-center" style="padding:30px;"><?= _t('bbs.import', 'История импортов пустая') ?></td>
</tr>
<? }