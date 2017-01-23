<?php

$urlCatAdd = $this->adminLink('categories_add&pid=');
$urlCatEdit = $this->adminLink('categories_edit&id=');
$urlItemsListing = $this->adminLink('listing&status=0&cat=');

foreach($cats as $v)
{
    $id = $v['id']; $isNode = $v['node'];
?>
<tr id="dnd-<?= $id ?>" data-numlevel="<?= $v['numlevel'] ?>" data-pid="<?= $v['pid'] ?>">
    <td style="padding-left:<?= ($v['numlevel']*15-10) ?>px;" class="left">
        <a onclick="return bbsCatAct(<?= $id ?>,'c');" class="but folder<? if(!$isNode) { ?>_ua<? } ?> but-text"><?= $v['title'] ?></a>
    </td>
    <td><a href="<?= $urlItemsListing.$id ?>"><?= $v['items'] ?></a></td>
    <td><? if( $v['addr'] ) { ?> <i class="icon-ok disabled"></i><? } ?></td>
    <td><? if( $v['price'] ) { ?> <i class="icon-ok disabled"></i><? } ?></td>
    <td><a class="but <? if( $v['enabled']) { ?>un<? } ?>block" href="#" onclick="return bbsCatAct(<?= $id ?>, 'toggle', this);" title="Вкл/выкл"></a>
        <a class="but sett" onclick="return bbsCatAct(<?= $id ?>, 'dyn');" href="#" title="Дин. свойства"></a>
        <a class="but edit" href="<?= $urlCatEdit.$id ?>" title="Редактировать"></a>
        <? if($v['numlevel'] >= $deep) { ?><a href="javascript:void(0);" class="but"></a><? } elseif(!$isNode && $v['items']) { ?><a href="javascript:void(0);" class="but add disabled"></a><? } else { ?>
            <a class="but add" href="<?= $urlCatAdd.$id ?>" title="Добавить"></a>
        <? } ?>
        <? if($isNode && ! FORDEV) { ?><a class="but" href="#"></a><? } else { ?>
            <a class="but del" href="#" onclick="return bbsCatAct(<?= $id ?>, 'del', this);" title="Удалить"></a>
        <? } ?>
    </td>
</tr>
<? }