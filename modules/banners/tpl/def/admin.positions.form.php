<?php
    $aData = HTML::escape($aData, 'html', array('title','keyword','width','height'));
    $edit = ! empty($id);
?>
<form name="BannersPositionsForm" id="BannersPositionsForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr class="required">
    <td class="row1" width="115"><span class="field-title">Название<span class="required-mark">*</span>:</span></td>
    <td class="row2">
        <input class="stretch" type="text" id="position-title" name="title" value="<?= $title ?>" />
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title">Keyword<span class="required-mark">*</span>:</span></td>
    <td class="row2">
        <input class="text-field" type="text" id="position-keyword" name="keyword" value="<?= $keyword ?>" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Ширина:</span></td>
    <td class="row2">
        <input class="short" type="text" id="position-width" name="width" value="<?= $width ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Высота:</span></td>
    <td class="row2">
        <input class="short" type="text" id="position-height" name="height" value="<?= $height ?>" maxlength="25" />
        <div class="help-inline">px</div>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Ротация:</span></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-rotation" name="rotation"<?php if($rotation){ ?> checked="checked"<?php } ?> /></label>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Фильтры:</span></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_sitemap" name="filter_sitemap"<?php if($filter_sitemap){ ?> checked="checked"<?php } ?> />раздел сайта</label>
        <label class="checkbox"><input type="checkbox" id="position-filter_region" name="filter_region"<?php if($filter_region){ ?> checked="checked"<?php } ?> />регион</label>
        <label class="checkbox"><input type="checkbox" id="position-filter_list_pos" name="filter_list_pos"<?php if($filter_list_pos){ ?> checked="checked"<?php } ?> />№ позиции в списке</label>
        <label class="checkbox"><input type="checkbox" id="position-filter_category" name="filter_category"<?php if($filter_category){ ?> checked="checked"<?php } ?> />категория</label>
    </td>
</tr>
<tr id="position-filter_category_module"<?php if(!$filter_category || sizeof($category_modules) == 1){ ?> class="hidden"<?php } ?>>
    <td class="row1"><span class="field-title">Модуль категорий:</span></td>
    <td class="row2">
        <select name="filter_category_module" class="input-medium"><?= HTML::selectOptions($category_modules, $filter_category_module) ?></select>
    </td>
</tr>
<tr<?php if( ! Banners::FILTER_AUTH_USERS ) { ?> class="hidden"<?php } ?>>
    <td class="row1"><span class="field-title">Пользователи:</span></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-filter_auth_users" name="filter_auth_users"<?php if($filter_auth_users){ ?> checked="checked"<?php } ?> />скрывать для авторизованных пользователей</label>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Включен:</span></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" id="position-enabled" name="enabled"<?php if($enabled){ ?> checked="checked"<?php } ?> /></label>
    </td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input type="submit" class="btn btn-success button submit" value="Сохранить" onclick="jBannersPositionsForm.save(false);" />
        <?php if($edit) { ?><input type="button" class="btn btn-success button submit" value="Сохранить и вернуться" onclick="jBannersPositionsForm.save(true);" /><?php } ?>
        <?php if($edit && FORDEV) { ?><input type="button" onclick="jBannersPositionsForm.del(); return false;" class="btn btn-danger button delete" value="Удалить" /><?php } ?>
        <input type="button" class="btn button cancel" value="Отмена" onclick="jBannersPositionsFormManager.action('cancel');" />
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
var jBannersPositionsForm =
(function(){
    var $progress, $form, formChk, id = <?= $id ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#BannersPositionsFormProgress');
        $form = $('#BannersPositionsForm');
        $form.on('click', '#position-filter_category', function(){
            $form.find('#position-filter_category_module').toggle($(this).is(':checked'));
        });
    });
    return {
        del: function()
        {
            if( id > 0 ) {
                bff.redirect('<?= $this->adminLink('position_delete&id=') ?>'+id);
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data,errors){
                if(data && data.success) {
                    bff.success('Данные успешно сохранены');
                    if(returnToList || ! id) {
                        jBannersPositionsFormManager.action('cancel');
                        jBannersPositionsList.refresh( ! id);
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