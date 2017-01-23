<?php
    /**
     * Форма магазина: основные настройки
     * @var $this Shops
     */
    $aData = HTML::escape($aData, 'html', array('title','descr','site','addr','region_title'));
    Geo::mapsAPI(true);
    tpl::includeJS('autocomplete');
    $edit = ! empty($aData['id']);
?>
<tbody id="j-shop-form-info-content">
<tr>
    <td class="row1 field-title" style="width:110px;">Название<span class="required-mark">*</span>:</td>
    <td class="row2">
        <input maxlength="50" type="text" name="shop_title" value="<?= $title ?>" id="shop_title" class="input-xlarge" />
        <? if($edit && bff::$class=='users' && BBS::publisher(BBS::PUBLISHER_USER_TO_SHOP)) { ?>
            <a href="#" class="btn btn-small pull-right j-user-shop-delete">удалить магазин</a>
            <div class="clearfix"></div>
        <? } ?>
    </td>
</tr>
<? if ($cats_on): ?>
<tr>
    <td class="row1 field-title"><span class="field-title">Категория</span><span class="required-mark">*</span>:</td>
    <td class="row2">
        <select class="input-large j-cats-select<? if($edit){ ?> hide<? } ?>"><?= $cats ?></select>
        <div>
            <div class="hide left j-cats-selected"></div>
            <? if($edit){ ?><a href="#" class="ajax desc j-cats-plus" style="margin-left: 5px;">+ добавить</a><? } ?>
            <div class="clearfix"></div>
        </div>
    </td>
</tr>
<? endif; # $cats_on ?>
<tr>
    <td class="row1 field-title">Чем занимается<span class="required-mark">*</span>:</td>
    <td class="row2">
        <textarea class="stretch" onkeyup="checkTextLength(600, this.value, $('#shop_descr_warn').get(0));" style="height: 100px;" name="shop_descr"><?= $descr ?></textarea>
        <div id="shop_descr_warn" class="clr-error"></div>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Телефоны</span>:</td>
    <td class="row2">
        <div id="j-shop-phones"></div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Skype:</td>
    <td class="row2">
        <input type="text" name="shop_skype" value="<?= $skype ?>" maxlength="32" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">ICQ:</td>
    <td class="row2">
        <input type="text" name="shop_icq" value="<?= $icq ?>" maxlength="20" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Ссылка на сайт:</td>
    <td class="row2">
        <input type="text" name="shop_site" value="<?= $site ?>" maxlength="200" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Социальные сети</span>:</td>
    <td class="row2">
        <div id="shop-social-block">
            <? if( ! empty($social)) { foreach($social as $k=>$v) { ?>
            <div>
                <select name="shop_social[<?= $k+1 ?>][t]" class="left" style="margin:3px 5px 0px 0px; width:135px;"><?= Shops::socialLinksTypes(true, $v['t']) ?></select>
                <input type="text" name="shop_social[<?= $k+1 ?>][v]" value="<?= HTML::escape($v['v']) ?>" class="left input-xxlarge" placeholder="ссылка" maxlength="300" />
                <div class="left" style="margin: 3px 0 0 4px;"><a href="#" class="but cross"></a></div>
                <div class="clear"></div>
            </div>
            <? } } ?>
        </div>
        <a class="ajax desc" id="shop-social-add" href="#">+ добавить ссылку</a>
    </td>
</tr>
<tr>
    <td colspan="2"><hr class="cut" /></td>
</tr>
<tr>
    <td class="row1 field-title">Регион<span class="required-mark">*</span>:</td>
    <td class="row2">
        <?= Geo::i()->citySelect($region_id, true, 'shop_region_id', array(
            'on_change' => 'jShopInfo.onCitySelect',
            'form' => 'shops-'.($edit ? 'settings' : 'form'),
#            'country_value' => ($edit ? $reg1_country : 0),
        )); ?>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Адрес:<br /><br /><div class="progress" id="shop-addr-progress" style="display: none;"></div></td>
    <td class="row2">
        <div style="margin-bottom: 6px;">
            <input type="hidden" name="shop_addr_lat" id="shop-addr-lat" value="<?= $addr_lat ?>" />
            <input type="hidden" name="shop_addr_lon" id="shop-addr-lon" value="<?= $addr_lon ?>" />
            <textarea class="stretch" rows="1" name="shop_addr_addr" id="shop-addr-addr" placeholder="Точный адрес"><?= $addr_addr ?></textarea>
            <input type="button" class="btn btn-mini" onclick="jShopInfo.onMapSearch();" value="найти на карте" />
            <span class="desc"> - переместите маркер на карте чтобы указать точное местоположение</span>
        </div>
        <div id="shop-addr-map-block">
            <div id="shop-addr-map" class="map-google" style="width:100%; height: 260px;"></div>
        </div>
    </td>
</tr>
<tr>
    <td colspan="2"><hr class="cut" /></td>
</tr>
<tr>
    <td class="row1 field-title">Лого:</td>
    <td class="row2">
        <input type="file" name="shop_logo" size="17" <? if( ! empty($logo)){ ?>style="display:none;" <? } ?> />
        <? if( ! empty($logo)) { ?>
        <div style="margin: 5px 0;">
            <input type="hidden" name="shop_logo_del" id="shop_logo_delete_flag" value="0" />
            <a href="<?= $logo_view ?>" rel="shop-logo-fancybox"><img id="shop_logo" src="<?= $logo_list ?>" alt="" /></a><br />
            <a href="#" title="удалить логотип" class="ajax desc cross but-text" onclick="return jShopInfo.deleteLogo(this);">удалить логотип</a>
        </div>
        <? } ?>
    </td>
</tr>
<tr <?php if($import_access != BBS::IMPORT_ACCESS_CHOSEN): ?>class="displaynone"<?php endif; ?>>
    <td class="row1 field-title">Импорт объявлений:</td>
    <td class="row2">
        <label class="checkbox">
            <input type="checkbox" name="shop_import" <? if($import){ ?> checked="checked"<? } ?> />
            <span class="desc">доступна возможность пакетной публикации объявлений</span>
        </label>
    </td>
</tr>
<? if($edit) { ?>
<tr>
    <td colspan="2">
        <?= $this->viewPHP($aData, 'admin.shops.form.status'); ?>
    </td>
</tr>
<? } ?>
<tr style="display: none;">
    <td colspan="2">
        <script type="text/javascript">
            //<![CDATA[
            var jShopInfo = (function(){
                var $container, inited = false, id = <?= $id ?>;
                var addr = {city:0,addr:0,progress:0,lat:0,lon:0,map:0};
                var $cats, $catsSelected;

                $(function(){
                    $container = $('#j-shop-form-info-content');

                    $('a[rel=shop-logo-fancybox]', $container).fancybox();

                    initPhones(<?= Shops::phonesLimit() ?>, <?= func::php2js($phones) ?>);

                    initSocial();

                    <? if($cats_on): ?>
                    initCats();
                    <? endif; # $cats_on ?>

                    $container.find('.j-user-shop-delete').on('click', function(e){ nothing(e);
                        if( ! bff.confirm('sure')) return;
                        bff.ajax('<?= $this->adminLink('ajax&act=shop-delete') ?>', {id: id}, function(data){
                            if(data && data.success) {
                                bff.success('Магазин был успешно удален');
                                setTimeout(function(){ location.reload(); }, 1000);
                            }
                        });
                    });

                    inited = true;
                });

                <? if($cats_on): ?>
                function initCats()
                {
                    $catsSelected = $container.find('.j-cats-selected');
                    $cats = $container.find('.j-cats-select').on('change', function(){
                        var $opt = $cats.find('option:selected');
                        setTimeout(function(){
                            catAdd( intval($opt.attr('value')) );
                        }, 1);
                        $opt.prop('selected', false);
                    });
                    $container.on('click','.j-cats-plus', function(e){ nothing(e);
                        $(this).hide();
                        $cats.show();
                    });
                    $catsSelected.on('click', '.j-cat-del', function(e){ nothing(e);
                        $(this).parent().remove();
                        if( ! $catsSelected.find('.j-selected').length ) {
                            $catsSelected.addClass('hide');
                        }
                    });
                    var catsIn = <?= func::php2js($cats_in); ?>;
                    if(catsIn) {
                        for(var i in catsIn) {
                            catAdd(intval(catsIn[i]['id']));
                        }
                    }
                }

                function catAdd(id)
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
                            '<span class="label j-selected" style="margin:0 2px 2px 2px;">'+title+'<a href="#" class="j-cat-del" style="margin-left: 3px;"><i class="icon-remove icon-white" style="margin-top: 0px;"></i></a><input type="hidden" name="shop_cats[]" class="j-selected-id" value="'+id+'" /></span>'
                        ).removeClass('hide');
                    }
                }
                <? endif; # $cats_on ?>

                function initPhones(limit, phones)
                {
                    var index  = 0, total = 0;
                    var $block = $container.find('#j-shop-phones');

                    function add(value)
                    {
                        if(limit>0 && total>=limit) return;
                        index++; total++;
                        $block.append('<div class="j-phone">\
                                            <input type="text" maxlength="40" name="shop_phones['+index+']" value="'+(value?value.replace(/"/g, "&quot;"):'')+'" class="left j-value" placeholder="Номер телефона" />\
                                            <div class="left" style="margin: 3px 0 0 4px;">'+(total==1 ? '<a class="ajax desc j-plus" href="#">+ еще телефон</a>' : '<a href="#" class="but cross j-remove"></a>')+'</div>\
                                            <div class="clear"></div>\
                                        </div>');
                    }

                    $block.on('click', 'a.j-plus', function(e){ nothing(e);
                        add('');
                    });

                    $block.on('click', 'a.j-remove', function(e){ nothing(e);
                        var $ph = $(this).closest('.j-phone');
                        if( $ph.find('.j-value').val() != '' ) {
                            if(confirm('Удалить телефон?')) {
                                $ph.remove(); total--;
                            }
                        } else {
                            $ph.remove(); total--;
                        }
                    });

                    phones = phones || {};
                    for(var i in phones) {
                        if( phones.hasOwnProperty(i) ) {
                            add(phones[i].v);
                        }
                    }
                    if( ! total ) {
                        add('');
                    }
                }

                function initSocial()
                {
                    var $block = $("#shop-social-block");
                    var $plus  = $('#shop-social-add', $block.parent());
                    var index = <?= sizeof($social) ?>, lmt = <?= Shops::socialLinksLimit() ?>;
                    var total = index;

                    $plus.click(function(){
                        if(lmt>0 && total>=lmt) return false;
                        index++; total++;
                        $block.append('<div>\
                                            <select name="shop_social['+index+'][t]" class="left" style="margin:3px 5px 0px 0px; width:135px;"><?= Shops::socialLinksTypes(true) ?></select>\
                                            <input type="text" name="shop_social['+index+'][v]" value="" class="left input-xxlarge" placeholder="ссылка" maxlength="300" />\
                                            <div class="left" style="margin: 3px 0 0 4px;"><a href="#" class="but cross"></a></div>\
                                            <div class="clear"></div>\
                                        </div>');
                        if(total === lmt) {
                            $plus.hide();
                        }
                        return false;
                    });

                    $block.on('click', 'a.cross', function(){
                        var p = $(this).parent().parent();
                        if(p.find('input:first').val()!='') {
                            if(confirm('Удалить ссылку?')) {
                                p.remove(); total--;
                            }
                        } else {
                            p.remove(); total--;
                        }
                        $plus.show();
                        return false;
                    });
                    if(total === lmt) {
                        $plus.hide();
                    }
                }

                function initAddr()
                {
                    addr.addr = $('#shop-addr-addr', $container);
                    addr.lat  = $('#shop-addr-lat', $container);
                    addr.lon  = $('#shop-addr-lon', $container);
                    addr.progress = $('#shop-addr-progress', $container);
                    addr.progress.show();

                    addr.map = bff.map.init('shop-addr-map', [addr.lat.val(), addr.lon.val()], function(map){
                        if (this.isYandex()) {
                            map.controls.add('zoomControl', {top:5,left:5});
                        }

                        addr.mapEditor = bff.map.editor();
                        addr.mapEditor.init({
                            map: map, version: '2.1',
                            coords: [addr.lat, addr.lon],
                            address: addr.addr,
                            addressKind: 'house',
                            updateAddressIgnoreClass: 'typed'
                        });

                        addr.addr.bind('change keyup input', $.debounce(function(){
                            if( ! $.trim(addr.addr.val()).length ) {
                                addr.addr.removeClass('typed');
                            } else {
                                addr.addr.addClass('typed');
                                jShopInfo.onMapSearch();
                            }
                        }, 700));
                        jShopInfo.onMapSearch();
                        addr.progress.hide();
                    }, {zoom:12});
                }

                return {
                    deleteLogo: function(link)
                    {
                        if (confirm('Удалить текущий логотип?')) {
                            var $block = $(link).parent();
                            $block.hide().find('#shop_logo_delete_flag').val(1);
                            $block.prev().show();
                            return false;
                        }
                    },
                    onMapSearch: function()
                    {
                        var $country = $container.find('.j-geo-city-select-country');
                        var country = ($country.is('select') ? $country.find('option:selected').text() :
                                       $country.val());
                        var q = [country];
                        var q_city = $.trim($container.find('.j-geo-city-select-ac').val());
                        if(q_city.length) q.push('г. '+q_city);
                        q.push( $.trim(addr.addr.val()) );
                        q = q.join(', ');
                        addr.mapEditor.search(q, 1);
                    },
                    onCitySelect: function(cityID, cityTitle, ex)
                    {
                        if(ex && ex.changed) {
                            jShopInfo.onMapSearch();
                        }
                    },
                    onShow: function()
                    {
                        if(inited) {
                            if( ! addr.map) initAddr();
                        }
                    }
                };
            }());
            //]]>
        </script>
    </td>
</tr>
</tbody>