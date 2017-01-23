<?php
    $aData = HTML::escape($aData, 'html', array('method','title'));
?>
<form method="post" action="">
<table class="admtbl tbledit">
<tr>
	<td class="row1 field-title" width="120">Модуль</td>
	<td class="row2">
	    <select name="module" id="module"><?= $modules_options ?></select>
    </td>
</tr>
<tr>
	<td class="row1 field-title">Метод</td>
	<td class="row2"><input type="text" class="text-field" name="method" id="method" size="25" value="<?= $method ?>" /></td>
</tr>
<tr>
	<td class="row1 field-title">Название</td>
	<td class="row2"><input type="text" class="text-field" name="title" size="25" value="<?= $title ?>" /></td>
</tr>
<tr>
    <td class="row1 field-title">Генератор кода:</td>
    <td class="row2 desc" id="msg"></td>
</tr>
<tr class="footer">
	<td colspan="2">
        <input type="submit" class="btn btn-success button submit" value="Добавить" />
        <input type="button" class="btn button cancel" value="Отмена" onclick="bff.redirect('<?= $this->adminLink('mm_listing') ?>');" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
$(function(){ 
    var $module = $('#module'),
        $method = $('#method'),
        $msg = $('#msg');
    
    function checkMethod()
    {
        $msg.html( $method.val().length > 0 ? '$this->haveAccessTo(\'<strong>'+($method.val())+'</strong>\')' : '<strong>укажите метод</strong>' );
    }

    $method.keyup(function(){
        checkMethod();
    });                                                                                                                        
    $module.focus(); 
    checkMethod();
}); 

//]]>
</script>