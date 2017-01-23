<?php
    /**
     * @var $this Site
     */
    $aTabs = array(
        'info' => 'Основные',
        'seo' => 'SEO',
    );
    $edit = ! empty($id);
?>
<?= tplAdmin::blockStart('Страницы / '.($edit ? 'Редактирование cтраницы' : 'Создание cтраницы'), false); ?>
<form method="post" action="" name="pagesForm" id="pagesForm">
<div class="tabsBar" id="pagesFormTabs">
    <?php foreach($aTabs as $k=>$v) { ?>
        <span class="tab<?php if($k == 'info') { ?> tab-active<?php } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <?php } ?>
</div>
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr class="required">
	<td class="row1" width="100"><span class="field-title">Имя файла</span>:</td>
	<td class="row2">
    <?php if($edit){ ?>
        <div class="bold left" style="height:25px;"><?= $filename . Site::$pagesExtension ?></div>
        <div class="right desc"><?= tpl::date_format2(($modified ? $modified : $created), true); ?>, <a href="#" class="desc ajax" onclick="return bff.userinfo(<?= $modified_uid ?>);"><?= $modified_login ?></a></div>
        <div class="clear"></div>
    <?php } else { ?>
        <input type="text" name="filename" value="<?= ( ! empty($filename) ? $filename : '') ?>" maxlength="30" class="text-field" />&nbsp;<strong><?= Site::$pagesExtension ?></strong>
    <?php } ?>
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'pages-item',
'
<tr class="required">
    <td class="row1"><span class="field-title">Заголовок</span>:</td>
    <td class="row2"><input class="stretch lang-field" type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" /></td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Содержание</span>:</td>
    <td class="row2"><?= tpl::wysiwyg($aData[\'content\'][$key],\'content[\'.$key.\']\',\'100%\',300) ?></td>
</tr>'); ?>
<?php if(FORDEV){ ?>
<tr>
	<td class="row1"><span class="field-title">Системная страница</span>:</td>
	<td class="row2">
        <label class="checkbox"><input type="checkbox" name="issystem" <?= ($issystem ? 'checked="checked"' : '') ?>" /></label>
    </td>
</tr>
<?php } ?>
</table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'page-view'); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" />
    <?php if($edit && ! $issystem) { ?>
    <input type="button" class="btn btn-danger button delete" value="Удалить" onclick="bff.confirm('sure', {r: '<?= $this->adminLink('pagesListing&act=delete&id='.$id) ?>'});" />
    <?php } ?>
    <input type="button" class="btn button cancel" value="К списку страниц" onclick="bff.redirect('<?= $this->adminLink('pagesListing') ?>');" />
</div>
</form>

<script type="text/javascript">
$(function(){
    var helper = new bff.formChecker( document.forms.pagesForm );
    var $form = $('#pagesForm');

    // tabs
    $form.find('#pagesFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
        var key = $(this).data('key');
        $form.find('.j-tab').addClass('hidden');
        $form.find('.j-tab-'+key).removeClass('hidden');
        $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
    });
});
</script>