<script type="text/javascript">
function usersGroupPermissions(id, show) {
    var $item = $('#subitem_'+id);
    if(show) $item.show(); else $item.hide();
}
</script>

<form method="post" action="">
<?php if ($group_id == Users::GROUPID_SUPERADMIN) { ?>
    <div class="alert alert-info">У группы <strong class="label"><?= $title ?></strong> полный доступ.</div>
<?php } elseif (empty($adminpanel)) { ?>
    <div class="alert alert-info">У группы <strong class="label"><?= $title ?></strong> нет доступа в админ. панель. Нет необходимости настраивать права.</div>
<?php } else { ?>
<p class="text-center alignCenter">Доступ группы <strong style="color:<?= $color ?>;"><?= $title ?></strong>:</p>
<table class="table table-condensed table-hover admtbl tblhover">
    <thead>
        <tr class="header">
            <th width="380" class="left">Модуль</th>
            <th colspan="2" class="left">Доступ</th>
        </tr>
    </thead>
    <?php foreach($permissions as $k=>$v) { $id = $v['id']; ?>
    <tr class="row0">
	    <td class="left"><strong><?= $v['title'] ?></strong></td>
	    <td class="left" width="140" <?php if($v['permissed']) { ?>class="text-success clr-success"<?php } ?>><label class="radio"><input type="radio" name="permission[<?= $id ?>]" value="<?= $id ?>" <?php if($v['permissed']) { ?>checked="checked"<?php } ?> <?php if($v['subitems']) { ?>onclick="usersGroupPermissions(<?= $k ?>, false)"<?php } ?> />Да (полный доступ)</label></td>
	    <td class="left" width="70"><label class="radio"><input type="radio" name="permission[<?= $id ?>]" value="0" <?php if( ! $v['permissed']) { ?>checked="checked"<?php } ?> <?php if($v['subitems']) { ?> onclick="usersGroupPermissions(<?= $k ?>, true);"<?php } ?> />Нет</label></td>
    </tr>
    <?php if($v['subitems']) { ?>
		<tbody id="subitem_<?= $k ?>" style="<?php if($v['permissed']) { ?>display:none;<?php } ?>">
		    <?php foreach($v['subitems'] as $vv) { $id2 = $vv['id']; ?>
			<tr class="row1">
				<td class="left" style="padding-left:20px;"><?= $vv['title'] ?></td>
				<td class="left"<?php if($vv['permissed']) { ?> class="text-success clr-success"<?php } ?>><label class="radio"><input type="radio" name="permission[<?= $id2 ?>]" value="<?= $id2 ?>" <?php if($vv['permissed']) { ?>checked="checked"<?php } ?> />Да</label></td>
				<td class="left"><label class="radio"><input type="radio" name="permission[<?= $id2 ?>]" value="0" <?php if(!$vv['permissed']) { ?>checked="checked"<?php } ?> />Нет</label></td>
			</tr>
			<?php } ?>
        </tbody>
    <?php } } ?>
    <tfoot>
        <tr class="footer">
            <td colspan="3">
                <?php if($group_id != Users::GROUPID_SUPERADMIN && ! empty($adminpanel)) { ?>
                <input type="submit" class="btn btn-success button submit" value="Сохранить" />
                <?php } ?>
                <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
            </td>
        </tr>
    </tfoot>
</table>
<?php } ?>
</form>