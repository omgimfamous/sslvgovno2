<?php

$langNumber = _t('bills', '№ операции');
$langDescription = array(
    Bills::TYPE_IN_PAY => _t('bills', 'Пополнение счета'),
    Bills::TYPE_IN_GIFT => _t('bills', 'Пополнение счета'),
    Bills::TYPE_OUT_SERVICE => _t('bills', 'Оплата усуги'),
);

$dateLast = 0;
foreach($list as $v)
{
    $in = ($v['type'] != Bills::TYPE_OUT_SERVICE);
?>
    <? if( $dateLast !== $v['created_date']) { ?>
        <tr>
            <td class="u-bill_list__date" colspan="3"><?= tpl::datePublicated($v['created_date'], 'Y-m-d', false, ' ') ?></td>
        </tr>
    <? } ?>
    <? $dateLast = $v['created_date']; ?>
    <tr>
        <td class="u-bill__list__descr"><?= $v['description'] ?><div class="visible-phone"><small class="grey"><?= $langNumber.' '.$v['id'] ?></small></div></td>
        <td class="align-center hidden-phone"><?= $v['id'] ?></td>
        <td class="u-bill__list__summ align-right"><?= ( ! $v['amount'] ? '&mdash;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : ( ! $in ? '- ' : '').$v['amount'].' '.$curr ) ?></td>
    </tr>
<?
}

if(empty($list))
{ ?>
<tr>
    <td colspan="3" class="text-center" style="padding:30px;"><?= _t('bills', 'Список операций по счету пустой') ?></td>
</tr>
<? }