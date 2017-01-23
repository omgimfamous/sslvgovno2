<?php
    $aData = HTML::escape($aData, 'html', array('title','module_title','keyword'));
    $edit = ! empty($id);
?>
<form name="SvcSvcForm" id="SvcSvcForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr class="required check-select<?= ( sizeof($types) === 1 ? ' hidden' : '') ?>">
    <td class="row1" style="width:110px;"><span class="field-title">Тип:</span></td>
    <td class="row2">
         <select name="type" id="j-svc-type"><?= $types_options ?></select>
    </td>
</tr>
<tr class="required">
    <td class="row1" style="width:110px;"><span class="field-title">Название:</span></td>
    <td class="row2">
         <input type="text" name="title" id="j-svc-title" class="stretch" value="<?= $title; ?>" />
    </td>
</tr>
<tr class="required check-select">
    <td class="row1"><span class="field-title">Модуль:</span></td>
    <td class="row2">
         <select name="module" id="j-svc-module"><?= $modules ?></select>
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title">Название модуля:</span></td>
    <td class="row2">
         <input type="text" name="module_title" id="j-svc-module-title" class="stretch" value="<?= $module_title; ?>" />
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title">Keyword:</span></td>
    <td class="row2">
         <input type="text" name="keyword" id="j-svc-keyword" class="stretch" maxlength="45" value="<?= $keyword; ?>" />
    </td>
</tr>
<tr class="footer">
    <td colspan="2" class="row1">
        <input type="submit" class="btn btn-success button submit" value="Сохранить" onclick="jSvcSvcForm.save(true);" />
        <input type="button" class="btn button cancel" value="Отмена" onclick="jSvcSvcFormManager.action('cancel');" />
    </td>
</tr>
</table>

</form>

<script type="text/javascript">
var jSvcSvcForm =
(function(){
    var $progress, $form, formChk, id = <?= $id ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#SvcSvcFormProgress');
        $form = $('#SvcSvcForm');
        
    });
    return {
        del: function()
        {
            if( id > 0 ) {
                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,
                    false, {progress: $progress, repaint: false, onComplete:function(){
                        bff.success('Услуга успешно сохранена');
                        jSvcSvcFormManager.action('cancel');
                        jSvcSvcList.refresh();
                    }});
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data,errors){
                if(data && data.success) {
                    bff.success('Услуга успешно сохранена');
                    if(returnToList || ! id) {
                        jSvcSvcFormManager.action('cancel');
                        jSvcSvcList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function ()
        {
            formChk = new bff.formChecker( $form );
        }
    };
}());
</script>