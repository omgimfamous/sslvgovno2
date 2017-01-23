<?php
    /**
     * @var $this Blog
     */
    $aData = HTML::escape($aData, 'html', array('keyword'));
    $aTabs = array(
        'info' => 'Основные',
        'seo' => 'SEO',
    );
    $edit = ! empty($id);
?>
<form name="BlogCategoriesForm" id="BlogCategoriesForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<div class="tabsBar" id="BlogCategoriesFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<div class="j-tab j-tab-info">
    <table class="admtbl tbledit">
    <?= $this->locale->buildForm($aData, 'categories-item','
    <tr>
        <td class="row1" width="100"><span class="field-title">Название:</span></td>
        <td class="row2">
            <input class="stretch lang-field" type="text" id="category-title-<?= $key ?>" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" />
        </td>
    </tr>'); ?>
    <tr>
        <td class="row1"><span class="field-title">URL-Keyword<span class="required-mark">*</span>:</span><br /><a href="#" onclick="return bff.generateKeyword('#category-title-<?= LNG ?>', '#category-keyword');" class="ajax desc small">сгенерировать</a></td>
        <td class="row2">
            <input class="stretch" type="text" id="category-keyword" name="keyword" value="<?= $keyword ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1"><span class="field-title">Включена:</span></td>
        <td class="row2">
            <label class="checkbox"><input type="checkbox" name="enabled"<? if($enabled){ ?> checked="checked"<? } ?> /></label>
        </td>
    </tr>
    </table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'listing-category'); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" onclick="jBlogCategoriesForm.save(false);" />
    <? if($edit) { ?><input type="button" class="btn btn-success button submit" value="Сохранить и вернуться" onclick="jBlogCategoriesForm.save(true);" /><? } ?>
    <? if($edit) { ?><input type="button" onclick="bff.redirect('<?= $this->adminLink('categories_delete&id='.$id) ?>')" class="btn btn-danger button delete" value="Удалить" /><? } ?>
    <input type="button" class="btn button cancel" value="Отмена" onclick="jBlogCategoriesFormManager.action('cancel');" />
</div>
</form>

<script type="text/javascript">
var jBlogCategoriesForm =
(function(){
    var $progress, $form, formChk, id = parseInt(<?= $id ?>);
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $progress = $('#BlogCategoriesFormProgress');
        $form = $('#BlogCategoriesForm');

        // tabs
        $form.find('#BlogCategoriesFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });
    return {
        del: function()
        {
            if( id > 0 ) {
                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,
                    false, {progress: $progress, repaint: false, onComplete:function(){
                        bff.success('Категория успешно удалена');
                        jBlogCategoriesFormManager.action('cancel');
                        jBlogCategoriesList.refresh();
                    }});
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            bff.ajax(ajaxUrl, $form.serialize(), function(data,errors){
                if(data && data.success) {
                    bff.success('Данные успешно сохранены');
                    if(returnToList || ! id) {
                        jBlogCategoriesFormManager.action('cancel');
                        jBlogCategoriesList.refresh();
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