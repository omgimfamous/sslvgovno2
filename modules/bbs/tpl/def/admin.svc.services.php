<?php

    /**
     * @var $this BBS
     */
    tpl::includeJS(array('wysiwyg','autocomplete','ui.sortable'), true);
    tplAdmin::adminPageSettings(array('icon'=>false));
    $saveUrl = $this->adminLink('svc_services&act=');
    $pricePrefix = '&nbsp;<span class="desc">'.Site::currencyDefault().'</span>';
    $bSelectCountry = Geo::coveringType(Geo::COVERING_COUNTRIES);
?>

<div class="tabsBar">
    <form id="j-services-tabs" action="">
    <? $tabActiveKey = $this->input->get('tab', TYPE_STR);
       $i = 0; foreach($svc as $k=>&$v)
       {
          $v['active'] = ( ! empty($tabActiveKey) ? $k == $tabActiveKey : !$i++ );
    ?>
        <div class="left">
            <span style="margin: 0 2px;" class="tab<? if( ! $v['on'] ) { ?> desc<? } if( $v['active'] ){ ?> tab-active<? } ?>"<? if(FORDEV){ ?> title="<?= $v['id'].':'.$k ?>"<? } ?> onclick="return jSvcServices.onTab('<?= $k ?>', this);"><?= $v['title'] ?></span>
            <input type="hidden" name="svc[<?= $v['id'] ?>]" value="<?= $v['id'] ?>" />
        </div>
    <? } unset($v); ?>
    </form>
    <div class="progress" style="display:none; margin-left:5px;" id="j-services-progress"></div>
</div>

<script type="text/javascript">
var jSvcServices = (function(){
    var urlAjax = '<?= $saveUrl; ?>';
    var $tabs, $progress, priceEx = {};
    <? if($bSelectCountry): ?>
    var countries = <?= func::php2js(Geo::countriesList()) ?>;
    <? endif; ?>

    $(function(){
        $tabs = $('#j-services-tabs-content').find('.j-tab-content');
        $progress = $('#j-services-progress');
        $('textarea.wy').bffWysiwyg({autogrow: false});

        $tabs.on('click', '.j-price-ex-plus', function(e){ nothing(e);
            var svc = $(this).data('svc');
            if( ! priceEx.hasOwnProperty(svc) ) {
                priceEx[svc] = priceExInit(svc);
            }
            priceEx[svc].plus();
        });

        var $svcOrder = $('#j-services-tabs').sortable({
            update: function( event, ui ) {
                bff.ajax(urlAjax+'reorder', $svcOrder.serialize(), function(data,errors) {
                    if(data && data.success) {
                        bff.success('Порядок услуг был успешно изменен');
                    }
                }, $progress);
            }
        });
        $svcOrder.sortable('refresh');

        $('.j-svc-service-form').each(function(){
            var $form = $(this);
            bff.iframeSubmit($form, function(data){
                if(data && data.success) {
                    bff.success('Настройки успешно сохранены');
                    setTimeout(function(){ location.reload(); }, 1000);
                }
            });
        });
    });

    function priceExInit(key)
    {
        var block_class = 'j-price-ex-block';
        var $cats = $('#j-price-ex-cats');
        var $block = $tabs.find('#j-price-ex-block-'+key);
        var iterator = intval($block.find('.'+block_class).length) + 1;

        $block.on('click', '.j-del', function(e){ nothing(e);
            var $i = $(this).closest('.'+block_class);
            $i.remove();
        });

        function add(i, data)
        {
            data = $.extend({price:1,cats:[],regions:[]}, data||{});
            var namePrefix = 'price_ex['+i+']';
            $block.append('<div class="well well-small relative '+block_class+' '+block_class+'-'+i+'" style="margin-bottom:5px;">'+
                            '<table class="admtbl tbledit">'+
                            '<tr><td width="73">Стоимость<span class="required-mark">*</span>:</td><td style="padding-bottom: 3px;"><input type="text" name="'+namePrefix+'[price]" value="'+data.price+'" class="input-mini" pattern="[0-9\.\,]*" /><?= $pricePrefix ?></td></tr>'+
                            '<tr><td class="row1">Категории<span class="required-mark">*</span>:</td><td class="row2" style="padding-bottom: 3px;">'+
                                '<select class="j-cat-select" style="margin-bottom: 3px;"></select>'+
                                '<div class="j-cats-selected hide"></div></td></tr>'+
                            '<tr><td class="row1">Регионы:</td><td class="row2">'+
                                '<input type="hidden" class="j-region-id" />'+
                                '<div class="relative" style="margin-bottom: 3px;"><input type="text" class="j-region autocomplete" placeholder="Введите название региона" /></div>'+
                                '<div class="j-regions-selected hide"></div></td></tr>'+
                            '</table>'+
                            '<button type="button" class="close j-del" style="position:absolute; right:5px; top:0px;">&times;</button>'+
                          '</div>');
            var $i = $block.find('.'+block_class+'-'+i);
            // -------------------------------
            // cats
            var $catsSelected = $i.find('.j-cats-selected');
            $catsSelected.on('click', '.j-cat-del', function(e){ nothing(e);
                $(this).parent().remove();
                if( ! $catsSelected.find('.j-selected').length ) {
                    $catsSelected.addClass('hide');
                }
            });
            $i.find('.j-cat-select').html($cats.html()).on('change', function(){
                var $sel = $(this);
                var $opt = $sel.find('option:selected');
                setTimeout(function(){
                    addCat( intval($opt.attr('value')) );
                },1);
                $opt.prop('selected', false);
            });
            function addCat(id)
            {
                if( id > 0 && ! $catsSelected.find('.j-selected-id[value="'+id+'"]').length )
                {
                    var $option = $cats.find('option[value="'+id+'"]');
                    var title = $option.text().trim();
                    var $optionParent = $cats.find('option[value="'+$option.data('pid')+'"]');
                    if( $optionParent.length ) {
                        title = $optionParent.text().trim() + ' / ' + title;
                    }
                    $catsSelected.append(
                        '<span class="label j-selected" style="margin:0 2px 2px 2px;">'+title+'<a href="#" class="j-cat-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><input type="hidden" name="'+namePrefix+'[cats][]" class="j-selected-id" value="'+id+'" /></span>'
                    ).removeClass('hide');
                }
            }
            if(data.cats) {
                for(var c in data.cats) {
                    addCat(intval(data.cats[c]));
                }
            }
            // -------------------------------
            // regions
            var $regionsSelected = $i.find('.j-regions-selected');
            $regionsSelected.on('click', '.j-region-del', function(e){ nothing(e);
                $(this).parent().remove();
                if( ! $regionsSelected.find('.j-selected').length ) {
                    $regionsSelected.addClass('hide');
                }
            });
            var ac = $.autocomplete($i.find('.j-region'), '<?= $this->adminLink('regionSuggest', 'geo') ?>',
                {valueInput: $i.find('.j-region-id') <?= ! $bSelectCountry ? ', suggest: '.Geo::regionPreSuggest() : '' ?>, params:{reg:1<?= $bSelectCountry ? ', country:1' : ''?>},
                 onSelect: function(regionID, regionTitle, ex){
                    if( ! ex.changed ) return;
                    <? if($bSelectCountry): ?>
                    var c = intval(ex.data[4]);
                    if(c && countries.hasOwnProperty(c)){
                        regionTitle = countries[c].title + ' / ' + regionTitle;
                    }
                    <? endif; ?>
                    addRegion(regionID, regionTitle);
                    ac.reset();
            }});
            function addRegion(id, title)
            {
                if( id > 0 && ! $regionsSelected.find('.j-selected-id[value="'+id+'"]').length ) {
                    $regionsSelected.append(
                        '<span class="label j-selected" style="margin:0 2px 2px 2px;">'+title+'<a href="#" class="j-region-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><input type="hidden" name="'+namePrefix+'[regions][]" class="j-selected-id" value="'+id+'" /></span>'
                    ).removeClass('hide');
                }
            }
            if(data.regions) {
                for(var r in data.regions) {
                    addRegion(data.regions[r]['id'], data.regions[r]['t']);
                }
            }
        }

        return {
            plus: function(data){
                add(iterator++, data);
            }
        };
    }

    return {
        onTab: function(key,link){
            $tabs.addClass('hidden');
            $tabs.filter('#j-services-'+key).removeClass('hidden');
            $(link).addClass('tab-active').parent().siblings().find('.tab').removeClass('tab-active');
            if( bff.h ) {
                window.history.pushState({}, document.title, '<?= $this->adminLink('svc_services&tab=') ?>'+key);
            }
            return false;
        },
        priceExEdit: function(svc, data)
        {
            if( ! priceEx.hasOwnProperty(svc) ) {
                priceEx[svc] = priceExInit(svc);
            }
            data = data || {};
            for(var j in data) {
                priceEx[svc].plus(data[j]);
            }
        },
        iconDelete: function(link){
            var $block = $(link).parent();
            $block.hide().find('input.del-icon').val(1);
            $block.prev().show();
            return false;
        }
    };
}());
</script>

<div id="j-services-tabs-content">
    <? foreach($svc as $k=>$v)
    {
        $ID = $v['id'];
    ?>
    <div id="j-services-<?= $k ?>" class="j-tab-content<? if( ! $v['active'] ){ ?> hidden"<? } ?>">
        <form action="<?= $saveUrl.'update' ?>" class="j-svc-service-form" id="j-services-form-<?= $k ?>">
            <input type="hidden" name="id" value="<?= $ID ?>" />
            <table class="admtbl tbledit">
                <?= $this->locale->buildForm($v, 'bbs-svc-'.$ID,'
                <tr>
                    <td class="row1"><span class="field-title">Название</span>:</td>
                    <td class="row2">
                        <input type="text" name="title_view[<?= $key ?>]" value="<?= ( isset($aData[\'title_view\'][$key]) ? HTML::escape($aData[\'title_view\'][$key]) : \'\') ?>" class="stretch lang-field" />
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title">Описание<br />(краткое)</span>:</td>
                    <td class="row2">
                        <textarea name="description[<?= $key ?>]" class="lang-field" rows="4"><?= ( isset($aData[\'description\'][$key]) ? $aData[\'description\'][$key] : \'\'); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title">Описание<br />(подробное)</span>:</td>
                    <td class="row2">
                        <?= tpl::jwysiwyg((isset($aData[\'description_full\'][$key]) ? $aData[\'description_full\'][$key] : \'\'), \'description_full-\'.$key.$aData[\'id\'].\',description_full[\'.$key.\']\', 0, 130); ?>
                    </td>
                </tr>
                '); ?>
                <tr>
                    <td class="row1" width="110"><span class="field-title">Стоимость</span><span class="required-mark">*</span>:</td>
                    <td class="row2">
                        <input type="text" name="price" value="<?= $v['price'] ?>" class="input-mini" pattern="[0-9\.\,]*" /><?= $pricePrefix; ?>
                        <a href="#" class="btn btn-mini j-price-ex-plus" data-svc="<?= $v['keyword'] ?>" style="margin-left: 5px;">добавить региональную стоимость</a>
                        <div id="j-price-ex-block-<?= $v['keyword'] ?>" style="margin: 5px 0;"></div>
                    </td>
                </tr>
                <? if( ! in_array($ID, array(BBS::SERVICE_UP, BBS::SERVICE_PRESS)) ) { ?>
                <tr>
                    <td class="row1"><span class="field-title">Период действия услуги</span><span class="required-mark">*</span>:</td>
                    <td class="row2">
                        <input type="number" min="1" name="period" value="<?= $v['period'] ?>" class="input-mini" /><div class="help-inline">дней</div>
                    </td>
                </tr>
                <? } ?>
                <?  $oIcon = BBS::svcIcon($ID);
                    foreach($oIcon->getVariants() as $iconField=>$icon) {
                        $oIcon->setVariant($iconField);
                        $icon['uploaded'] = ! empty($v[$iconField]);
                    ?>
                    <tr>
                        <td class="row1">
                            <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($icon['sizes']) == 1) { $sz = current($icon['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
                        </td>
                        <td class="row2">
                            <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
                            <? if($icon['uploaded']) { ?>
                                <div style="margin:5px 0;">
                                    <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                                    <img src="<?= $oIcon->url($ID, $v[$iconField], $icon['key']) ?>" alt="" /><br />
                                    <a href="#" class="ajax desc cross but-text" onclick="return jSvcServices.iconDelete(this);">удалить</a>
                                </div>
                            <? } ?>
                        </td>
                    </tr>
                    <? }
                ?>
                <tr>
                    <td class="row1"><span class="field-title">Цвет</span>:</td>
                    <td class="row2">
                        <input type="text" name="color" value="<?= $v['color'] ?>" class="input-mini" />
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title">В форме добавления</span>:</td>
                    <td class="row2">
                        <input type="checkbox" name="add_form" <? if($v['add_form']){ ?>checked="checked"<? } ?> />
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title">Включена</span>:</td>
                    <td class="row2">
                        <input type="checkbox" name="on" <? if($v['on']){ ?>checked="checked"<? } ?> />
                    </td>
                </tr>
                <tr><td colspan="2"><hr class="cut" /></td></tr>
                <tr>
                    <td class="footer" colspan="2">
                        <div class="left"><input type="submit" class="btn btn-success button submit" value="Сохранить" /></div>
                        <div class="right desc">
                            последние изменения: <span class="j-last-modified"><?= tpl::date_format2($v['modified'], true); ?>, <a class="bold desc ajax" href="#" onclick="return bff.userinfo(<?= $v['modified_uid'] ?>);"><?= $v['modified_login'] ?></a></span>
                        </div>
                        <div class="clear"></div>
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <? if( isset($price_ex[$ID]) ) { ?>
        <script type="text/javascript">
            $(function(){
                jSvcServices.priceExEdit('<?= HTML::escape($k, 'js') ?>', <?= func::php2js($price_ex[$ID]) ?>);
            });
        </script>
    <? } ?>
    <? } ?>
</div>

<div>
    <select id="j-price-ex-cats" class="hidden"><?= $cats ?></select>
</div>
<?
