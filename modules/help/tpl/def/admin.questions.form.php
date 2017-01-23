<?php
    /**
     * @var $this Help
     * @var $publicator \bff\db\Publicator
     */
    $aData = HTML::escape($aData, 'html', array('cat_id'));
    $aTabs = array(
        'info' => 'Основные',
        'seo' => 'SEO',
    );
    $edit = ! empty($id);
?>
<form name="HelpQuestionsForm" id="HelpQuestionsForm" action="<?= $this->adminLink(null) ?>" method="get" onsubmit="return false;">
<input type="hidden" name="act" value="<?= ($edit ? 'edit' : 'add') ?>" />
<input type="hidden" name="save" value="1" />
<input type="hidden" name="id" value="<?= $id ?>" />
<input type="hidden" name="cat_id" id="question-cat_id" value="<?= $cat_id ?>" />
<div class="tabsBar" id="HelpQuestionsFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<div class="j-tab j-tab-info">
    <table class="admtbl tbledit">
    <tr class="required check-select">
        <td class="row1 field-title" width="95">Категория<span class="required-mark">*</span>:</td>
        <td class="row2">
            <div class="left">
            <?
                foreach($cats as $lvl=>$v) {
                    ?><select class="cat-select" style="margin-right: 5px;<? if(empty($v['categories'])){ ?>display: none; <? } ?>" onchange="jHelpQuestionsForm.onCategory($(this))"><?= $v['categories'] ?></select><?
                }
            ?>
            </div>
            <div class="right desc">
                <? if($edit && $modified!='0000-00-00 00:00:00' ) { ?>
                    последние изменения: <?= tpl::date_format2($modified, true); ?>
                <? } ?>
            </div>
            <div class="clear clearfix"></div>
        </td>
    </tr>
    <?= $this->locale->buildForm($aData, 'questions-item','
    <tr>
        <td class="row1"><span class="field-title">Заголовок:</span></td>
        <td class="row2">
            <input class="stretch lang-field" type="text" id="question-title-<?= $key ?>" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1"><span class="field-title">Краткое описание:</span></td>
        <td class="row2">
            <? if($aData[\'big_wy\']) { ?>
                <?= tpl::wysiwyg($aData[\'textshort\'][$key], \'textshort[\'.$key.\']\', 0, 320); ?>
            <? } else { ?>
                <?= tpl::jwysiwyg($aData[\'textshort\'][$key], \'textshort[\'.$key.\']\', 0, 120); ?>
            <? } ?>
        </td>
    </tr>
    '); ?>
    <tr>
        <td class="row1" colspan="2">
            <?= $publicator->form($content, $id, 'content', 'jHelpQuestionsFormPublicator'); ?>
        </td>
    </tr>
    <tr>
        <td class="row1"><span class="field-title">Отображается:</span></td>
        <td class="row2">
            <label class="checkbox"><input type="checkbox" id="question-enabled" name="enabled"<? if($enabled){ ?> checked="checked"<? } ?> /></label>
        </td>
    </tr>
    </table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'view'); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" onclick="jHelpQuestionsForm.save(false);" />
    <? if ($edit) { ?><input type="button" class="btn btn-success button submit" value="Сохранить и вернуться" onclick="jHelpQuestionsForm.save(true);" /><? } ?>
    <? if ($edit) { ?><input type="button" onclick="jHelpQuestionsForm.del(); return false;" class="btn btn-danger button delete" value="Удалить" /><? } ?>
    <input type="button" class="btn button cancel" value="Отмена" onclick="jHelpQuestionsFormManager.action('cancel');" />
</div>
</form>

<script type="text/javascript">
var jHelpQuestionsForm =
(function(){
    var $progress, $form, formChk, id = parseInt(<?= $id ?>);
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';
    var catCache = {};

    $(function(){
        $progress = $('#HelpQuestionsFormProgress');
        $form = $('#HelpQuestionsForm');

        // tabs
        $form.find('#HelpQuestionsFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });

    function catView(data, $select)
    {
        if(data === 'empty') {
            return;
        }

        if(data.subs>0) {
            $select.after('<select class="cat-select" style="margin-right: 5px;" onchange="jHelpQuestionsForm.onCategory($(this))">'+data.cats+'</select>').show();
            return;
        }
        formChk.check(false, true);
    }

    return {
        del: function()
        {
            if( id > 0 ) {
                bff.ajaxDelete('sure', id, ajaxUrl+'&act=delete&id='+id,
                    false, {progress: $progress, repaint: false, onComplete:function(){
                        bff.success('Запись успешно удалена');
                        jHelpQuestionsFormManager.action('cancel');
                        jHelpQuestionsList.refresh();
                    }});
            }
        },
        save: function(returnToList)
        {
            if( ! formChk.check(true) ) return;
            // check selected cats
            var catsEmpty = $form.find('select.cat-select[value="0"]:visible');
            if(catsEmpty.length > 0) {
                bff.error('Выберите категорию');
                return false;
            }

            <? if($big_wy) { ?>
            for (var i in FCKeditorAPI.Instances)
                FCKeditorAPI.GetInstance(i).UpdateLinkedField();
            <? } ?>

            bff.ajax(ajaxUrl, $form.serialize(), function(data){
                if(data && data.success) {
                    bff.success('Данные успешно сохранены');
                    jHelpQuestionsFormPublicator.ajaxSave();
                    if(returnToList || ! id) {
                        jHelpQuestionsFormManager.action('cancel');
                        jHelpQuestionsList.refresh( ! id);
                    }
                }
            }, $progress);
        },
        onShow: function()
        {
            formChk = new bff.formChecker($form);
        },
        onCategory: function($select)
        {
            catView('empty');

            var catID = intval($select.val());
            $form.find('#question-cat_id').val(catID);
            $select.nextAll().remove();

            if( ! catID) return;

            if(catCache.hasOwnProperty(catID)) {
                catView( catCache[catID], $select );
            } else {
                bff.ajax('<?= $this->adminLink('questions&act=category-data'); ?>', {'cat_id': catID}, function(data){
                    if(data && data.success) {
                        catView( (catCache[catID] = data), $select );
                    }
                }, function(show){
                    $progress.toggle();
                });
            }
        },
        onLang: function(key)
        {
            jHelpQuestionsFormPublicator.setLang(key);
        }

    };
}());
</script>