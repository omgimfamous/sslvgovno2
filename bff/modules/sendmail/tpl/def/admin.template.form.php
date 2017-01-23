<?php

?>
<form method="post" action="" id="j-sendmail-template-form">
<table class="admtbl tbledit">
<tr class="row2">
    <td colspan="2">
        <p><strong><?= $aData['title'] ?></strong></p>
        <p class="text-info"><?= $aData['description'] ?></p>
    </td>
</tr>
<tr>
    <td style="vertical-align:top;">
        <table class="admtbl tbledit">
            <?= $this->locale->buildForm($aData, 'sendmail-tpl-item', ''.'
            <tr class="required">
                <td class="row1 field-title" width="80">Заголовок:</td>
                <td class="row2"><input class="stretch lang-field j-input <?= $key ?>" type="text" name="subject[<?= $key ?>]" value="<?= HTML::escape($aData[\'tpl\'][\'subject\'][$key]); ?>" /></td>
            </tr>
            <tr>
                <td class="row1 field-title">Текст:</td>
                <td class="row2"><textarea name="body[<?= $key ?>]" class="lang-field j-input stretch" style="height: 300px; min-height:235px;"><?= $aData[\'tpl\'][\'body\'][$key] ?></textarea></td>
            </tr>'); ?>
            <tr>
                <td class="row1"></td>
                <td class="row2">
                    <label class="checkbox">
                        <input type="checkbox" name="is_html"<?php if(!empty($tpl['is_html'])){ ?> checked="checked"<?php } ?> />Текст содержит HTML теги: <span class="desc"><?= HTML::escape('<div>, <br>, <table>, <body>, <html>') ?></span>
                    </label>
                </td>
            </tr>
            <? if (!empty($wrappers)) { ?>
            <tr>
                <td class="row1 field-title">Шаблон:</td>
                <td class="row2">
                    <select name="wrapper_id" style="width: auto; min-width: 150px;"><?= $wrappers ?></select>
                </td>
            </tr>
            <? } ?>
            <tr class="footer">
                <td></td>
                <td>
                    <input type="submit" class="btn btn-success submit button" value="Сохранить" />
                    <input type="button" class="btn cancel button" value="К списку уведомлений" onclick="bff.redirect('<?= $this->adminLink('template_listing') ?>');" />
                </td>
            </tr>
        </table>
    </td>
    <td style="vertical-align:top; width: 220px; min-width: 200px; padding-left: 20px;">
        <p class="text-info">Макросы для вывода данных:</p>
        <hr size="1" style="color:#ccc" />
        <?php foreach($aData['vars'] as $k=>$v) { ?>
        <div style="margin-bottom: 10px;">
            <a href="#" class="j-macros" data-key="<?= HTML::escape($k) ?>"><?= $k ?></a><br />
            <?= $v ?>
        </div>
        <?php } ?>
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
$(function(){
    var $form = $('#j-sendmail-template-form');
    var _input_focused = null;
    $form.on('focus', '.j-input', function(){
        _input_focused = this;
    });
    $form.on('click', '.j-macros', function(e){ nothing(e);
        bff.textInsert(_input_focused, $(this).data('key'));
    });
});
</script>