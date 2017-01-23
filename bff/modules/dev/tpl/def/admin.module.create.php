<?php
    $aData = HTML::escape($aData, 'html', array('title', 'name'));
    tplAdmin::adminPageSettings(array('icon'=>false));
?>
<form method="post" action="" name="moduleForm">
<table class="admtbl tbledit">
<tr>
	<td class="row1 field-title" style="width:150px;">Название модуля:<br /><span class="desc">(на английском)</span></td>
	<td class="row2"><input type="text" name="title" id="j-dev-module-title" size="50" maxlength="40" value="<?= $title ?>" /></td>
</tr>
<tr>
    <td class="row1 field-title">Название модуля:<br /><span class="desc">(на русском)</span></td>
    <td class="row2"><input type="text" name="name" size="50" value="<?= $name ?>" /></td>
</tr>
<tr class="footer">
	<td colspan="2" class="row1">
        <input type="submit" class="btn btn-success button submit" value="Создать" />
	</td>
</tr>
</table>
</form>

<script type="text/javascript">
//<![CDATA[
$(function(){

    var aModules = <?= func::php2js($modules) ?>;
    var $title = $('#j-dev-module-title');
    $title.bind('keyup', $.debounce(function(){
        bff.errors.hide();
        var title = $.trim($title.val());
        for(var i in aModules) {
            if( aModules.hasOwnProperty(i) && aModules[i] == title ) {
                bff.error('Указанный модуль уже существует');
            }
        }
    }, 700)).focus();
});
//]]>
</script>