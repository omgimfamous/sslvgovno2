<?php
    /**
     * @var $this Help
     */
    $aData = HTML::escape($aData, 'html', array('keyword'));
    $aTabs = array(
        'info' => 'Основные',
        'seo' => 'SEO',
    );
    $edit = ! empty($id);
?>
<form name="HelpCategoriesForm" id="HelpCategoriesForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<div class="tabsBar" id="HelpCategoriesFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<div class="j-tab j-tab-info">
    <table class="admtbl tbledit">
    <tr class="required check-select">
        <td class="row1 field-title" width="110">Основной раздел<span class="required-mark">*</span>:</td>
        <td class="row2">
            <? if ($edit) { ?>
                <input type="hidden" name="pid" id="category-pid" value="<?= $pid ?>" />
                <span class="bold"><?= $pid_path ?></span>
            <? } else { ?>
                <select name="pid" id="category-pid"><?= $this->model->categoriesOptions($pid, false, 1) ?></select>
            <? } ?>
        </td>
    </tr>
    <?= $this->locale->buildForm($aData, 'categories-item','
    <tr>
        <td class="row1"><span class="field-title">Название:</span></td>
        <td class="row2">
            <input class="stretch lang-field" type="text" id="category-title-<?= $key ?>" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" />
        </td>
    </tr>
    '); ?>
    <tr>
        <td class="row1"><span class="field-title">URL-Keyword<span class="required-mark">*</span>:</span><br /><a href="#" onclick="return bff.generateKeyword('#category-title-<?= LNG ?>', '#category-keyword');" class="ajax desc small">сгенерировать</a></td>
        <td class="row2">
            <input class="stretch" type="text" id="category-keyword" name="keyword_edit" value="<?= $keyword_edit ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1"><span class="field-title">Включена:</span></td>
        <td class="row2">
            <label class="checkbox"><input type="checkbox" id="category-enabled" name="enabled"<? if($enabled){ ?> checked="checked"<? } ?> /></label>
        </td>
    </tr>
    <tr class="footer">
        <td colspan="2">

        </td>
    </tr>
    </table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'listing-category'); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" onclick="jHelpCategoriesForm.save(false);" />
    <? if ($edit) { ?><input type="button" class="btn btn-success button submit" value="Сохранить и вернуться" onclick="jHelpCategoriesForm.save(true);" /><? } ?>
    <? if ($edit) { ?><input type="button" onclick="bff.redirect('<?= $this->adminLink('categories_delete&id='.$id) ?>');" class="btn btn-danger button delete" value="Удалить" /><? } ?>
    <input type="button" class="btn button cancel" value="Отмена" onclick="jHelpCategoriesFormManager.action('cancel');" />
</div>
</form>

<script type="text/javascript">
var jHelpCategoriesForm =
(function(){
    var $progress, $form, formChk, id = parseInt(<?= $id ?>);
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#HelpCategoriesFormProgress');
        $form = $('#HelpCategoriesForm');

        // tabs
        $form.find('#HelpCategoriesFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });
    return {
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data){
                if(data && data.success) {
                    bff.success('Данные успешно сохранены');
                    if(returnToList || ! id) {
                        jHelpCategoriesFormManager.action('cancel');
                        jHelpCategoriesList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function ()
        {
            formChk = new bff.formChecker($form);
        }
    };
}());
</script>