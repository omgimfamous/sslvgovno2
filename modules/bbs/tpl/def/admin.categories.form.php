<?php
    /**
     * @var $this BBS
     */
    tpl::includeJS(array('tablednd'));
    $aData = HTML::escape($aData, 'html', array('keyword_edit'));
    $aTabs = array(
        'info' => 'Основные',
        'seo' => 'SEO',
    );
    $edit = $aData['edit'] = ! empty($id);

    if( ! $edit ) {
        $price_sett['ranges'] = array();
        $price_sett['ex'] = 0;
        $photos = BBS::CATS_PHOTOS_MIN;
    }

echo tplAdmin::blockStart( 'Объявления / Категории / '. ( $edit ? 'Редактирование': 'Добавление'), false);
?>
<form method="post" action="" name="bbsCategoryForm" id="bbsCategoryForm" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= $id ?>" />
<input type="hidden" name="seek" value="<?= $seek ?>" id="bbs-cat-seek" />
<input type="hidden" name="copy_to_subs" value="0" id="bbs-cat-copy-to-subs" />
<div class="tabsBar" id="bbsCategoryFormTabs">
    <? foreach($aTabs as $k=>$v) { ?>
        <span class="tab<? if($k == 'info') { ?> tab-active<? } ?>"><a href="#" class="j-tab-toggler" data-key="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>
<!-- таб: Основные -->
<div class="j-tab j-tab-info">
<table class="admtbl tbledit">
<tr>
    <td class="row1 field-title" width="120">Основная категория:</td>
    <td class="row2">
        <? if( ! $edit || ! empty($pid_editable)) { ?>
            <select name="pid"><?= $pid_options ?></select>
        <? } else { ?>
            <?
                $pid_title = array();
                if( ! empty($pid_options) ) foreach($pid_options as $v) $pid_title[] = $v['title'];
            ?>
            <p class="bold"><?= join('&nbsp;&nbsp;&gt;&nbsp;&nbsp;', $pid_title); ?></p>
            <input type="hidden" name="pid" value="<?= $pid ?>" />
        <? } ?>
    </td>
</tr>
<?= $this->locale->buildForm($aData, 'bbs-category', ''.'
<tr class="required">
    <td class="row1 field-title">Название:</td>
    <td class="row2"><input class="stretch lang-field" type="text" name="title[<?= $key ?>]" id="bbs-cat-title-<?= $key ?>" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" maxlength="200" /></td>
</tr>
<tr <? if(!$aData[\'edit\'] || $aData[\'numlevel\'] < BBS::catsFilterLevel()){ ?>class="displaynone"<? } ?>>
    <td class="row1 field-title">Заголовок подкатегорий:</td>
    <td class="row2">
        <input class="stretch lang-field" type="text" name="subs_filter_title[<?= $key ?>]" value="<?= HTML::escape($aData[\'subs_filter_title\'][$key]); ?>" />
        <span class="desc">заголовок для группы подкатегорий в фильтре</span>
    </td>
</tr>
<tr<? if(BBS::CATS_TYPES_EX) { ?> class="hidden"<? } ?>>
    <td class="row1 field-title">Тип размещения:</td>
    <td class="row2">
        <div class="well well-small">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1" width="75"><span class="">Предлагаю</span>:</td>
                    <td class="row2">
                        <input class="input-medium lang-field" type="text" placeholder="Предлагаю" title="Название в форме" name="type_offer_form[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_offer_form\'][$key]); ?>" />
                        <input class="input-medium lang-field" type="text" placeholder="Объявления" title="Название в списке" name="type_offer_search[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_offer_search\'][$key]); ?>" /><span class="help-inline">примеры: Предлагаю, Продам, Сдам, Предложение, Предлагаю работу, ...</span>
                    </td>
                </tr>
                <tr class="j-bbs-cat-seek-param<? if( ! $aData[\'seek\']) { ?> disabled<? } ?>">
                    <td class="row1"><span class="field-title">Ищу</span>:</td>
                    <td class="row2">
                        <input class="input-medium lang-field" type="text" placeholder="Ищу" title="Название в форме" name="type_seek_form[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_seek_form\'][$key]); ?>" />
                        <input class="input-medium lang-field" type="text" placeholder="Объявления" title="Название в списке" name="type_seek_search[<?= $key ?>]" value="<?= HTML::escape($aData[\'type_seek_search\'][$key]); ?>" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-bbs-cat-seek-toggler" <? if( $aData[\'seek\']) { ?>checked="checked"<? } ?> onclick="jCategory.onSeek(this);" />задействовать</label>
                        <span class="help-inline">примеры: Ищу, Куплю, Сниму, Ищу работу, ...</span>
                    </td>
                </tr>
            </table>
        </div>
    </td>
</tr>
'); ?>
<tr>
    <td class="row1 field-title">Цена:</td>
    <td class="row2">
        <label class="radio inline"><input type="radio" name="price" value="1" onclick="$('#j-price-sett').show();" <? if($price) { ?> checked="checked" <? } ?> />есть</label>
        <label class="radio inline"><input type="radio" name="price" value="0" onclick="$('#j-price-sett').hide();" <? if( ! $price) { ?> checked="checked" <? } ?> />нет</label>
    </td>
</tr>
<tr id="j-price-sett" style="<? if( ! $price) { ?> display:none;<? } ?>">
    <td class="row1"></td>
    <td class="row2">
        <?
            $price_curr = ( ! empty($price_sett['curr']) ? $price_sett['curr'] : Site::currencyDefault('id') );
            $price_curr_title = Site::currencyData($price_curr, 'title_short');
        ?>
        <div class="well well-small">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1" width="140">Заголовок цены:</td>
                    <td id="j-price-title">
                        <?= $this->locale->formField('price_sett[title]', $price_sett['title'], 'text', array('placeholder'=>'Цена')); ?>
                        <span class="help-inline">примеры: Цена, Стоимость, Зарплата от, ...</span>
                    </td>
                </tr>
                <tr>
                    <td class="row1">Валюта по-умолчанию:</td>
                    <td id="j-price-curr">
                        <select name="price_sett[curr]" style="width:70px;" onchange="jCategoryPrice.onCurr(this);"><?= Site::currencyOptions($price_curr); ?></select>
                    </td>
                </tr>
                <tr>
                    <td class="row1">Диапазоны цен:<br /><span class="desc">(для поиска)</span></td>
                    <td>
                        <table id="j-price-ranges">
                            <?
                            $i = 1;
                            if( ! empty($price_sett['ranges']) ) {
                                foreach($price_sett['ranges'] as $v) {
                                    ?><tr class="range-<?= $i; ?>"><td>от <input name="price_sett[ranges][<?= $i ?>][from]" value="<?= ($v['from'] > 0 ? $v['from'] : '' ); ?>" type="text" class="input-mini" />&nbsp;&nbsp; до <input name="price_sett[ranges][<?= $i ?>][to]" type="text" value="<?= ($v['to'] > 0 ? $v['to'] : ''); ?>" class="input-mini" /><span class="help-inline j-price-ranges-curr-help"><?= $price_curr_title ?></span><a class="but cross j-price-ranges-del" href="#" style="margin-left:7px;"></a></td></tr><?
                                    $i++;
                                }
                            } ?>
                        </table>
                        <a href="#" class="ajax" id="j-price-ranges-add">добавить диапазон цен</a><span class="desc">&nbsp;&nbsp;&uarr;&darr;</span>
                    </td>
                </tr>
                <tr>
                    <td class="row1">Модификатор:</td>
                    <td>
                        <label class="checkbox inline"><input type="checkbox" name="price_sett[ex][0]" value="<?= BBS::PRICE_EX_MOD ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_MOD) { ?> checked="checked" <? } ?> onclick="$('#j-price-sett-mod-title').toggle()" /></label>
                        <span id="j-price-sett-mod-title"<? if( ! ($price_sett['ex'] & BBS::PRICE_EX_MOD) ) { ?> style="display: none;" <? } ?>>
                            <?= $this->locale->formField('price_sett[mod_title]', $price_sett['mod_title'], 'text', array('placeholder'=>'Торг возможен')); ?>
                            <span class="help-inline">примеры: Торг возможен, По результатам собеседования, ...</span>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td class="row1">Обмен:</td>
                    <td><label class="checkbox inline"><input type="checkbox" name="price_sett[ex][1]" value="<?= BBS::PRICE_EX_EXCHANGE ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_EXCHANGE) { ?> checked="checked" <? } ?> /></label></td>
                </tr>
                <tr>
                    <td class="row1">Бесплатно:</td>
                    <td><label class="checkbox inline"><input type="checkbox" name="price_sett[ex][2]" value="<?= BBS::PRICE_EX_FREE ?>" <? if($price_sett['ex'] & BBS::PRICE_EX_FREE) { ?> checked="checked" <? } ?> /></label></td>
                </tr>
            </table>
        </div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Фотографии:</td>
    <td class="row2">
        <label><input class="input-mini" type="number" min="<?= BBS::CATS_PHOTOS_MIN ?>" max="<?= BBS::CATS_PHOTOS_MAX ?>" maxlength="2" name="photos" value="<?= $photos ?>" /><span class="help-inline"> &mdash; максимально доступное кол-во фотографий в объявлении (<?= BBS::CATS_PHOTOS_MIN ?> - <?= BBS::CATS_PHOTOS_MAX ?>)</span></label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Представитель:</td>
    <td class="row2">
        <label class="radio inline"><input type="radio" name="owner_business" value="1" onclick="$('#j-ownertype-sett').show();" <? if($owner_business) { ?> checked="checked" <? } ?> />есть</label><label class="radio inline"><input type="radio" name="owner_business" value="0" onclick="$('#j-ownertype-sett').hide();" <? if( ! $owner_business) { ?> checked="checked" <? } ?> />нет</label>
        <div class="well well-small" id="j-ownertype-sett" style="margin-top:5px;<? if( ! $owner_business) { ?> display:none;<? } ?>">
            <input type="hidden" name="owner_search[1]" class="j-ownertype-search-val-private" value="<?= ($owner_search & BBS::OWNER_PRIVATE ? BBS::OWNER_PRIVATE : 0) ?>" />
            <input type="hidden" name="owner_search[2]" class="j-ownertype-search-val-business" value="<?= ($owner_search & BBS::OWNER_BUSINESS ? BBS::OWNER_BUSINESS : 0) ?>" />
            <? $i=0; foreach($this->locale->getLanguages() as $lng) { ?>
            <table class="admtbl tbledit j-lang-form j-lang-form-<?= $lng ?><?= ($i++ ? ' displaynone':'') ?>">
                <tr>
                    <td class="row1" width="95"><span class="">Частное лицо</span>:</td>
                    <td class="row2">
                        <input class="input-large lang-field" type="text" placeholder="Частное лицо" title="Название в форме" name="owner_private_form[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_private_form'][$lng]); ?>" maxlength="50" />
                        <input class="input-large lang-field" type="text" placeholder="От частных лиц" title="Название в списке" name="owner_private_search[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_private_search'][$lng]); ?>" maxlength="50" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-ownertype-search-toggler-private" data="{id:<?= BBS::OWNER_PRIVATE ?>, key:'private'}" <? if($owner_search & BBS::OWNER_PRIVATE) { ?>checked="checked"<? } ?> onclick="jCategory.onOwnertypeSearch(this);" />поиск</label>
                    </td>
                </tr>
                <tr>
                    <td class="row1"><span class="field-title">Бизнес</span>:</td>
                    <td class="row2">
                        <input class="input-large lang-field" type="text" placeholder="Бизнес" title="Название в форме" name="owner_business_form[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_business_form'][$lng]); ?>" maxlength="50" />
                        <input class="input-large lang-field" type="text" placeholder="Только бизнес объявления" title="Название в списке" name="owner_business_search[<?= $lng ?>]" value="<?= HTML::escape($aData['owner_business_search'][$lng]); ?>" maxlength="50" />
                        <label class="checkbox inline desc" style="margin-left:7px;"><input type="checkbox" class="j-ownertype-search-toggler-business" data="{id:<?= BBS::OWNER_BUSINESS ?>, key:'business'}" <? if($owner_search & BBS::OWNER_BUSINESS) { ?>checked="checked"<? } ?> onclick="jCategory.onOwnertypeSearch(this);" />поиск</label>
                    </td>
                </tr>
            </table>
            <? } ?>
        </div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Адрес:</td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" name="addr" <? if($addr) { ?> checked="checked"<? } ?> />подробный адрес и карта</label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Метро:</td>
    <td class="row2">
        <label class="checkbox inline"><input type="checkbox" name="addr_metro" <? if($addr_metro) { ?> checked="checked"<? } ?> />поиск по станции метро</label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Вид списка<br /> по-умолчанию:</td>
    <td class="row2">
        <select name="list_type" style="width: auto;">
            <?= HTML::selectOptions(array(
                0 => 'Не указан',
                BBS::LIST_TYPE_LIST    => 'Список',
                BBS::LIST_TYPE_GALLERY => 'Галерея',
                BBS::LIST_TYPE_MAP     => 'Карта',
            ), $list_type); ?>
        </select>
    </td>
</tr>
<tr>
    <td class="row1">
        <span class="field-title">URL Keyword</span>:<br />
        <a href="#" onclick="return bff.generateKeyword('#bbs-cat-title-<?= LNG ?>', '#bbs-cat-keyword');" class="ajax desc small">сгенерировать</a>
    </td>
    <td class="row2">
        <input class="stretch" type="text" maxlength="100" name="keyword_edit" id="bbs-cat-keyword" value="<?= $keyword_edit ?>" />
    </td>
</tr>
<? if($edit && $this->model->catIsMain($id, $pid))
{
    $oIcon = BBS::categoryIcon($id);
    foreach($oIcon->getVariants() as $iconField=>$v) {
        $oIcon->setVariant($iconField);
        $icon = $v;
        $icon['uploaded'] = ! empty($aData[$iconField]);
    ?>
    <tr>
        <td class="row1">
            <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($v['sizes']) == 1) { $sz = current($v['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
        </td>
        <td class="row2">
            <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
            <? if($icon['uploaded']) { ?>
                <div style="margin:5px 0;">
                    <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                    <img src="<?= $oIcon->url($id, $aData[$iconField], $icon['key']) ?>" alt="" /><br />
                    <a href="#" class="ajax desc cross but-text" onclick="return jCategory.iconDelete(this);">удалить</a>
                </div>
            <? } ?>
        </td>
    </tr>
    <? }
} ?>
</table>
</div>
<!-- таб: SEO -->
<div class="j-tab j-tab-seo hidden">
    <?= SEO::i()->form($this, $aData, 'search-category'); ?>
</div>
<div style="margin-top: 10px;">
    <input type="submit" class="btn btn-success button submit" value="Сохранить" />
    <input type="button" class="btn button cancel" value="Отмена" onclick="bff.redirect('<?= $this->adminLink('categories_listing') ?>')" />
    <? if(FORDEV){ ?><input type="submit" class="btn btn-small button pull-right" value="Скопировать настройки в подкатегории" onclick="return jCategory.copySettingsToSubs();" /><? } ?>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
var jCategory = (function(){
    var $seekData, $form;
    $(function(){
        $form = $('#bbsCategoryForm');
        new bff.formChecker( document.forms.bbsCategoryForm );
        $seekData = $('#bbs-cat-seek');

        // tabs
        $form.find('#bbsCategoryFormTabs .j-tab-toggler').on('click', function(e){ nothing(e);
            var key = $(this).data('key');
            $form.find('.j-tab').addClass('hidden');
            $form.find('.j-tab-'+key).removeClass('hidden');
            $(this).parent().addClass('tab-active').siblings().removeClass('tab-active');
        });

        $form.find('[name="addr"]').change(function(){
            var ch = $(this).is(':checked');
            var $lt = $form.find('[name="list_type"]');
            var $lt_map = $lt.find('[value="<?= BBS::LIST_TYPE_MAP ?>"]');
            if(! ch && intval($lt.val()) == <?= BBS::LIST_TYPE_MAP ?>){
                $lt.val(0);
            }
            $lt_map.attr('disabled', ! ch);
        });
    });

    return {
        iconDelete: function(link){
            var $block = $(link).parent();
            $block.hide().find('input.del-icon').val(1);
            $block.prev().show();
            return false;
        },
        onSeek: function(check){
            var enabled = $(check).is(':checked');
            $seekData.val( ( enabled ? 1 : 0 ) );
            $('.j-bbs-cat-seek-param').toggleClass('disabled', !enabled);
            $('.j-bbs-cat-seek-toggler').not(check).prop('checked', enabled);
        },
        onOwnertypeSearch: function(check){
            var checked = $(check).is(':checked');
            var meta = $(check).metadata();
            $('.j-ownertype-search-val-'+meta.key).not(check).val((checked ? meta.id : 0));
            $('.j-ownertype-search-toggler-'+meta.key).not(check).prop({checked:checked});
        },
        copySettingsToSubs: function(){
            if ( ! bff.confirm('sure') ) return false;
            $('#bbs-cat-copy-to-subs').val(1);
        }
    };
}());
var jCategoryPrice = (function(){

    function getCurrTitle() {
        var sel = $('#j-price-curr select').get(0);
        return sel.options[sel.selectedIndex].text;
    }

    var ranges = (function(){
        var $block, iterator = <?= ( ! empty($price_sett) ? count($price_sett['ranges']) : 0 ); ?>;
        $(function(){
            $block = $('#j-price-ranges');

            $('#j-price-ranges-add').on('click', function(e){ nothing(e);
                addRange(++iterator);
                initRotate(true);
            });
            $block.on('click', '.j-price-ranges-del', function(e){ nothing(e);
                $(this).parent().remove();
            });
            initRotate(false);
        });

        function initRotate(update)
        {
            if(update === true) {
                $block.tableDnDUpdate();
            } else {
                $block.tableDnD({onDragClass: 'rotate'});
            }
        }

        function addRange(i)
        {
            $block.append('<tr class="range-'+i+'"><td>от <input name="price_sett[ranges]['+i+'][from]" type="text" class="input-mini" />&nbsp;&nbsp; до <input name="price_sett[ranges]['+i+'][to]" type="text" class="input-mini" /><span class="help-inline j-price-ranges-curr-help">'+getCurrTitle()+'</span><a class="but cross j-price-ranges-del" href="#" style="margin-left:7px;"></a></td></tr>');
            $('.range-'+i+' > td > input:first', $block).focus();
        }
    }());
    return {
        onCurr: function() {
            $('.j-price-ranges-curr-help').text( getCurrTitle() );
        }
    };
}());
</script>
<?= tplAdmin::blockStop(); ?>
<? if(BBS::CATS_TYPES_EX && $edit) {
    echo $this->types_listing($id);
} ?>