<?php   
    $banURL = $this->adminLink('ban', 'users');
?>
<? foreach($list as $k=>$v) { ?>
<tr class="row<?= $k%2 ?><? if($v['viewed']){ ?> desc<? } ?>">
    <td><?= $v['id'] ?></td>
    <td class="left"><?= tpl::truncate($v['message'], 80); ?></td>
    <td><?= tpl::date_format2($v['created'], true, true); ?><br /></td>
    <td>
        <a class="but edit" href="#" onclick="return jContacts.view(<?= $v['id'] ?>, this);"></a>
        <a class="but del contact-del" href="#" rel="<?= $v['id'] ?>"></a>
    </td>
</tr>
<? } if(empty($list)) { ?>
<tr class="norecords">
    <td colspan="4">ничего не найдено</td>
</tr>
<? }