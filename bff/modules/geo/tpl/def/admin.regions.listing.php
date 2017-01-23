<?php
    tpl::includeJS(array('tablednd'), true);
    $aFormTitles = array(Geo::lvlCountry=>'страну', Geo::lvlRegion=>'область', Geo::lvlCity=>'город');

    if (FORDEV) {
         tplAdmin::adminPageSettings(array(
            'fordev'=>array(
                array('title'=>'синхронизация ipgeobase', 'onclick'=>"bff.redirect('".$this->adminLink('dev_regions_ipgeobase')."'); return false;", 'icon'=>'icon-download'),
                array('title'=>'корректировка уникальности URL-keyword', 'onclick'=>"bff.redirect('".$this->adminLink('dev_keywords_uniqueness')."'); return false;", 'icon'=>'icon-magnet'),
            ),
         ));
    }
?>

<?= $this->viewPHP($aData, 'admin.regions.listing.tabs', $this->module_dir_tpl_core); ?>

<?= $content ?>

<?php if( ! $rotate){ echo $this->tplAssigned('pagenation_template'); } ?>

<div style="margin-top:7px;">
    <div class="left"></div>
    <div class="right desc" style="width:80px; text-align:right;">
        <?php if($rotate){ ?>&nbsp;&nbsp; &darr; &uarr;<?php } ?>
    </div>
    <div class="clear"></div>
</div> 

<div style="display:none;">
    <div class="ipopup" id="geo-regions-add-popup" style="width:450px;">
        <div class="ipopup-wrapper">
            <div class="ipopup-title">Добавить <?= $aFormTitles[$numlevel] ?></div>
            <div class="ipopup-content">
                <form action="" method="post" id="geo-regions-add-form">
                    <input type="hidden" name="id" value="0" />
                    <input type="hidden" name="pid" value="<?= $pid ?>" />
                    <input type="hidden" name="country" value="<?= ( $numlevel == Geo::lvlCountry ? 0 : $country ) ?>" />
                    <input type="hidden" name="numlevel" value="<?= $numlevel ?>" />
                    <input type="hidden" name="main" value="<?= $main ?>" />
                    <table class="admtbl tbledit">
                    <?= $this->locale->buildForm($aData, 'region-item',
                   '<tr class="required">
                        <td class="row1" style="width:100px;"><span class="field-title">Название</span>:</td>
                        <td class="row2"><input type="text" name="title[<?= $key ?>]" value="" class="stretch lang-field" rel="title-<?= $key ?>" /></td>
                    </tr>', array('popup'=>true)); ?>
                    <?php if($numlevel == Geo::lvlRegion || $numlevel == Geo::lvlCity): ?>
                    <tr>
                        <td><span class="field-title">URL-keyword</span>:</td>
                        <td><input type="text" name="keyword" value="" class="stretch" maxlength="30" /></td>
                    </tr>
                    <?php endif; ?>
                    <?php if($numlevel == Geo::lvlCity && Geo::manageRegions(Geo::lvlMetro) ): ?>
                    <tr>
                        <td><span class="field-title">Есть метро</span>:</td>
                        <td><label class="checkbox"><input type="checkbox" name="metro" /></label></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="footer">
                        <td colspan="2" class="center">
                            <input type="button" class="btn btn-success button submit j-submit" value="Добавить" />
                            <input type="button" class="btn button cancel" value="Отмена" onclick="$.fancybox.close();" />
                        </td>
                    </tr>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var jGeoRegionsParams = {main: <?= $main ?>, lvl: <?= $numlevel ?>, country: <?= $country ?>};
var jGeoRegions = (function(){
    var $progress, $list, $formAdd, $formEdit;
    var ajaxUrl = '<?= $this->adminLink('regions_ajax&act=') ?>';

    $(function(){

        $list = $('#geo-regions-listing');
        $progress = $('#geo-regions-progress');
        <?php if($rotate){ ?>
        bff.rotateTable($list, ajaxUrl+'region-rotate&main='+jGeoRegionsParams.main+'&lvl='+jGeoRegionsParams.lvl+'&country='+jGeoRegionsParams.country, $progress);
        <?php } ?>
        // добавление
        var $popupAdd = $('#geo-regions-add-popup');
        $('#geo-regions-add-link').click(function(e){ nothing(e);
            $.fancybox({type: 'html', content: $popupAdd});
        });
        $formAdd = $('#geo-regions-add-form');
        $formAdd.on('click', '.j-submit', function(){
            bff.ajax(ajaxUrl+'region-save', $formAdd.serialize(), function(data, errors){
                if(data && !errors.length) {
                    $.fancybox.close();
                    location.reload();
                }
            }, $progress);
        });
        new bff.formChecker($formAdd);
    });

    function editStart(id)
    {
        // редактирование: init
        $.fancybox('', {ajax:true, href:ajaxUrl+'region-edit&id='+id});
        return false;
    }

    function editFinish(btn)
    {
        // редактирование: submit
        var $form = $(btn.form);
        bff.ajax(ajaxUrl+'region-save', $form.serialize(), function(data, errors) {
            if(data && !errors.length) {
                location.reload();
            }
        });
        return false;
    }

    function toggle(id, $link, main)
    {
        var url = ajaxUrl+'region-toggle&main='+(main?1:0)+'&lvl='+jGeoRegionsParams.lvl+'&country='+jGeoRegionsParams.country+'&rec='+id;
        if(main) {
            bff.ajaxToggle(id, url, {link: $link, progress: $progress, block: 'fav', unblock: 'unfav', complete: function(){
                if(jGeoRegionsParams.main) {
                    $($link).parent().parent().remove();
                    $list.tableDnDUpdate();
                }
            }});
        } else {
            bff.ajaxToggle(id, url, {link: $link, progress: $progress});
        }
        return false;
    }
    
    function del(id, $link) {
        bff.ajaxDelete('sure', id, ajaxUrl+'region-delete&lvl='+jGeoRegionsParams.lvl+'&country='+jGeoRegionsParams.country, $link,
            {progress: $progress});
        return false;
    }

    function resetCache()
    {
        bff.ajax(ajaxUrl+'reset-cache', {}, function (data) {
            if(data && data.success) {
                bff.success('Кеш регионов был успешно сброшен');
            }
        });
        return false;
    }

    return {edit:editStart, editFinish:editFinish, toggle:toggle, del:del, resetCache:resetCache};
})();
</script>