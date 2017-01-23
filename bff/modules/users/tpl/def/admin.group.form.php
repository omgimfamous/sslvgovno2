<?php
    $aData = HTML::escape($aData, 'html', array('title','keyword','color'));
?>
<form method="post" action="" name="modifyGroupForm">
<table class="admtbl tbledit">
<tr class="required">
    <td class="row1 field-title" width="160">Название группы:</td>
    <td class="row2">
        <input type="text" name="title" value="<?= $title ?>" class="text-field" />
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title">Идентификатор группы:</td>
    <td class="row2">
        <input type="text" size="40" name="keyword" value="<?= $keyword ?>" class="text-field" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Доступ к админ. панели:</td>
    <td class="row2"><label class="checkbox"><input name="adminpanel" type="checkbox" <?php if($adminpanel) { ?>checked="checked"<?php } ?> /></label></td>
</tr>
<?php if(FORDEV) { ?>
<tr>
    <td class="row1 field-title">Системная группа:</td>
    <td class="row2"><label class="checkbox"><input name="issystem" type="checkbox" <?php if($issystem) { ?>checked="checked"<?php } ?> /></label></td>
</tr>
<?php } ?>
<tr>
    <td class="row1 field-title">Цвет:</td>
    <td class="row2">
        <input type="color" name="color" value="<?= $color ?>" class="input-small" />
        <?php if($edit || ! empty($color)) { ?><span style="background-color:<?= $color ?>; width:20px; height:20px; margin-left:5px;">&nbsp;&nbsp;&nbsp;&nbsp;</span><?php } ?>
        <span style="color:#666;">примеры: red, #f00, #ff0000</span>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input type="submit" class="btn btn-success button submit" value="<?= ($edit ? 'Сохранить' : 'Создать') ?>" <?php if($issystem){ ?> onclick="if( ! bff.confirm('sure')) return false;"<?php } ?> />
        <?php if(FORDEV && $deletable && $edit) { ?>
            <input type="button" class="btn btn-danger button delete" value="Удалить" onclick="bff.confirm('sure', {r:'<?= $this->adminLink('group_delete&rec='.$group_id) ?>'});" />
        <?php } ?>
        <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
$(function(){
    var fchecker = new bff.formChecker(document.forms.modifyGroupForm);
});
</script>