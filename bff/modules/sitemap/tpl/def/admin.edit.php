<?php
    /**
     * @var $this Sitemap
     */
    $aTabs = array(
        'info' => 'Основные',
    );
    if ($this->_useMetaSettings) {
        $aTabs['seo'] = 'SEO';
    }
?>
<?= tplAdmin::blockStart('Карта сайта и меню / Редактирование меню', false); ?>
<form method="post" action="" id="sitemap-form">
<input type="hidden" name="rec" value="<?= $id; ?>" />
<div class="tabsBar<?php if(sizeof($aTabs) == 1){ ?> hidden<?php } ?>" id="sitemap-form-tabs">
    <?php foreach($aTabs as $k=>$v) { ?>
        <span class="tab<?php if($k == 'info') { ?> tab-active<?php } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <?php } ?>
</div>
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr>
    <td class="row1 field-title" width="120">Принадлежит к:</td>
    <td class="row2 bold">
        <div class="left"><?= $pid_options; ?></div>
        <div class="right desc bold" style="padding-right:5px;">
            <?php  if($type == Sitemap::typeMenu ){ ?>Меню<?php }
            elseif($type == Sitemap::typePage){ ?>Ссылка на текстовую страницу<?php }
            elseif($type == Sitemap::typeLink){ ?>Ссылка<?php }
            elseif($type == Sitemap::typeLinkModuleMethod){ ?>Системная ссылка<?php } ?></div>
        <div class="clear"></div>
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'sitemap-item',
'<tr>
    <td class="row1 field-title">Название:</td>
    <td class="row2">
        <input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" class="stretch lang-field" />
    </td>
</tr>'); ?>

<?php if(FORDEV) { ?>
<tr>
    <td class="row1 field-title">Keyword:</td>
    <td class="row2"><input type="text" name="keyword" maxlength="15" value="<?= $keyword; ?>" class="stretch" /></td>
</tr>
<?php }
if( (FORDEV || $type != Sitemap::typeLinkModuleMethod)){ ?>
<tr>
    <td class="row1 field-title"><a href="javascript:void(0);" class="ajax" id="j-sitemap-link-title">Ссылка:</a><div id="j-sitemap-link-popover"></div></td>
    <td class="row2"><input type="text" name="link" id="j-sitemap-link" value="<?= $link; ?>" class="stretch" /></td>
</tr>
<?php }
if(FORDEV) { ?>
<tr>
    <td class="row1 field-title">Style:</td>
    <td class="row2"><input type="text" name="style" value="<?= $style; ?>" class="stretch" /></td>
</tr>
<?php }
if($type != Sitemap::typeMenu) { ?>
<tr>
    <td class="row1 field-title">Открывать:</td>
    <td class="row2"><select name="target" style="width:130px;"><?= $target_options ?></select></td>
</tr>
<?php } ?>
<tr<?php if( ! Sitemap::XML_EXPORT) { ?> style="display:none;"<?php } ?>>
    <td class="row1 field-title">Частота обновления:</td>
    <td class="row2"><input type="text" name="changefreq" value="<?= $changefreq; ?>" class="stretch" /></td>
</tr>
<tr<?php if( ! Sitemap::XML_EXPORT) { ?> style="display:none;"<?php } ?>>
    <td class="row1 field-title">Приоритет:</td>
    <td class="row2"><input type="text" name="priority" value="<?= $priority; ?>" class="stretch" /></td>
</tr>   
<?php if(FORDEV) { ?>
<tr>
    <td class="row1 field-title">Системный:</td>
    <td class="row2"><label class="checkbox"><input type="checkbox" name="is_system" <?php if($is_system) { ?>checked="checked"<?php } ?> /></label></td>
</tr>
    <?php if($type == Sitemap::typeMenu) { ?>
    <tr>
        <td class="row1 field-title">Разрешать вложенные меню:</td>
        <td class="row2"><label class="checkbox"><input type="checkbox" name="allow_submenu" <?php if($allow_submenu) { ?>checked="checked"<?php } ?> /></label></td>
    </tr>
    <?php }
} ?>
</table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" />
    <?php if( ! $is_system || FORDEV){ ?><input type="button" class="btn btn-danger button delete" onclick="bff.confirm('sure', {r:'<?= $this->adminLink('delete&id='.$id) ?>'});" value="Удалить" /><?php } ?>
    <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
</div>
</form>

<script type="text/javascript">
var jSitemapItemEdit = (function(){
    var $form;
    $(function(){
        $form = $('#sitemap-form');
        if( bff.bootstrapJS() ) {
            $('#j-sitemap-link-title').popover({trigger:'click',placement:'bottom',container:'#j-sitemap-link-popover',
                title:'Макросы для ссылки:',html:true,
                content:'<div><a href="#" class="ajax" onclick="return jSitemapItemEdit.onLinkMacros(this);">{siteurl}</a> = "<?= SITEURL ?>"</div><div><a href="#" class="ajax" onclick="return jSitemapItemEdit.onLinkMacros(this);">{sitehost}</a> = "<?= SITEHOST ?>"</div>'});
        }

        // tabs
        $form.find('#sitemap-form-tabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });

    return {
        onLinkMacros: function(link){
            bff.textInsert('#j-sitemap-link', $(link).text());
            return false;
        }
    };
}());
</script>