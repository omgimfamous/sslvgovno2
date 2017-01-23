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
<?= tplAdmin::blockStart('Карта сайта и меню / Создание меню', false); ?>
<form method="post" action="" id="sitemap-form">
<div class="tabsBar<?php if(sizeof($aTabs) == 1){ ?> hidden<?php } ?>" id="sitemap-form-tabs">
    <?php foreach($aTabs as $k=>$v) { ?>
        <span class="tab<?php if($k == 'info') { ?> tab-active<?php } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <?php } ?>
</div>
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr>
    <td class="row1 field-title" width="100">Создать в:</td>
    <td class="row2">
        <?php if(FORDEV) { ?><select name="pid" style="width:300px;"><?= $pid_options ?></select>
        <?php } else { ?>
            <input name="pid" type="hidden" value="<?= $parent['id'] ?>" />
            <strong><?= $pid_options ?></strong>
        <?php } ?>
    </td>
</tr>
<tr>                    
    <td style="vertical-align:top;"><span class="field-title">Тип</span>:</td>
    <td class="row2">
        <label class="radio"><input value="3" type="radio" name="type" onclick="jSitemapItemAdd.onType(this);" id="type-link-external" /> Ссылка</label>
        <label class="radio"><input value="2" type="radio" name="type" onclick="jSitemapItemAdd.onType(this);" id="type-page" /> Ссылка на текстовую страницу</label>
        <?php if(FORDEV) { ?><label class="radio"><input value="4" type="radio" name="type" onclick="jSitemapItemAdd.onType(this);" id="type-link-modulemethod" /> Системная ссылка</label><?php } ?>
        <?php if(FORDEV || ($parent && $parent['allow_submenu']==1)) { ?>
        <label class="radio"><input value="1" type="radio" name="type" onclick="jSitemapItemAdd.onType(this);" id="type-menu" /> Новое меню</label>
        <?php } ?>
    </td>
</tr>

<?= $this->locale->buildForm($aData, 'sitemap-item',
'<tr class="required">
    <td class="row1 field-title" width="100">Название:</td>
    <td class="row2">
        <input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" class="stretch lang-field sitemap-title <?= $key ?>" />
    </td>
</tr>'); ?>

<tr id="sitemap-page-select" style="display:none;" class="required check-select">
    <td class="row1 field-title" width="100">Страница:</td>
    <td class="row2">
        <div style="display:block;" id="divexistpage" class="desc">
            <select name="page_id" id="page_id" style="width:300px;" onchange="if(this.selectedIndex>0){ $('.sitemap-title').val( this.options[this.selectedIndex].text ); }">
                <?= $pages_options ?>
            </select>
            [<a href="<?= $this->adminLink('pagesAdd','site') ?>" target="_blank">добавить страницу</a>]
        </div>
    </td>
</tr> 
<?php if(FORDEV){ ?>
<tr class="required">
    <td class="row1 field-title">Keyword:</td>
    <td class="row2"><input type="text" name="keyword" maxlength="15" value="<?= $keyword ?>" class="stretch" /></td>
</tr>    
<?php } ?>
<tr id="sitemap-link" style="display:none;">
    <td class="row1 field-title"><a href="javascript:void(0);" class="ajax" id="j-sitemap-link-title">Ссылка:</a><div id="j-sitemap-link-popover"></div></td>
    <td class="row2"><input type="text" name="link" id="j-sitemap-link" value="<?= $link ?>" class="stretch" /></td>
</tr> 

<tr id="sitemap-style" style="display:none;">
    <td class="row1 field-title">Style:</td>
    <td class="row2"><input type="text" name="style" value="<?= $style ?>" class="stretch" /></td>
</tr> 

<tbody <?php if( ! Sitemap::XML_EXPORT) { ?> style="display:none;"<?php } ?>>
    <tr>
        <td class="row1 field-title">Частота обновления:</td>
        <td class="row2"><input type="text" name="changefreq" value="<?= $changefreq ?>" class="stretch" /></td>
    </tr>
    <tr>
        <td class="row1 field-title">Приоритет:</td>
        <td class="row2"><input type="text" name="priority" value="<?= $priority ?>" class="stretch" /></td>
    </tr>
</tbody>
<tr id="sitemap-system" style="display:none;">
    <td class="row1 field-title">Системный:</td>
    <td class="row2"><label class="checkbox"><input type="checkbox" name="is_system" /></label></td>
</tr>   
<tr id="sitemap-allow-submenu" style="display:none;">
    <td class="row1 field-title">Разрешать вложенные меню:</td>
    <td class="row2"><label class="checkbox"><input type="checkbox" name="allow_submenu" /></label></td>
</tr>
</table>
</div>
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData); ?>
</div>
<div style="margin-top: 10px;">
    <span id="sitemap-submit" style="display:none;">
        <input type="submit" class="btn btn-success button submit" value="Создать" />
    </span>
    <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
</div>
</form>

<script type="text/javascript">
var jSitemapItemAdd = (function(){
    var $form, formChecker;
    var fordev = <?= (FORDEV?'true':'false'); ?>;

    $(function(){
        $form = $('#sitemap-form');
        formChecker = new bff.formChecker($form);
        if( bff.bootstrapJS() ) {
            $('#j-sitemap-link-title').popover({trigger:'click',placement:'bottom',container:'#j-sitemap-link-popover',
                title:'Макросы для ссылки:',html:true,
                content:'<div><a href="#" class="ajax" onclick="return jSitemapItemAdd.onLinkMacros(this);"><?= Sitemap::MACROS_SITEURL ?></a> = "<?= SITEURL ?>"</div><div><a href="#" class="ajax" onclick="return jSitemapItemAdd.onLinkMacros(this);"><?= Sitemap::MACROS_SITEHOST ?></a> = "<?= SITEHOST ?>"</div>'});
        }

        // tabs
        $form.find('#sitemap-form-tabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });
    });

    function onType(element)
    {
        var type   = intval( $(element).val() );
        $('#sitemap-page-select, #sitemap-link, #sitemap-target, #sitemap-allow-submenu', $form).hide();
        switch(type)
        {
            case 1:{ // menu
                if(fordev) { $('#sitemap-allow-submenu', $form).show(); }
                $('#sitemap-link, #sitemap-target', $form).show();
            }break;
            case 2:{ //page
                 $('#sitemap-page-select, #sitemap-target', $form).show();
                 var $title = $('.sitemap-title');
                 if($.trim($title.val())=='') {
                    var elem = $('#page_id').get(0);
                    if(elem && elem.options[0] && elem.value!=0) {
                        $title.val( elem.options[0].text );
                    }
                 }
            } break;
            case 3: //link - external
            {
                 $('#sitemap-link, #sitemap-target', $form).show();
            } break;
            case 4: //link - modulemethod
            {
                 $('#sitemap-link, #sitemap-target', $form).show();
            }break;
        }
        $('#sitemap-submit', $form).show(); //common
        if(fordev) { $('#sitemap-style, #sitemap-system', $form).show(); }
        formChecker.check();
    }

    return {
        onType:onType,
        onLinkMacros: function(link){
            bff.textInsert('#j-sitemap-link', $(link).text());
            return false;
        }
    };
}());
</script>