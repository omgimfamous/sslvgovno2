<?php
    tpl::includeJS(array('datepicker','autocomplete'), true);
    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>'+ добавить баннер', 'href'=>$this->adminLink('add')),
        'fordev'=>array(
            array('title'=>'сбросить кеш', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('ajax&act=dev-reset-cache')."'})", 'icon'=>'icon-refresh'),
        ),
    ));
    $locales = bff::locale()->getLanguages(false);
    $localeFilter = (Banners::FILTER_LOCALE && sizeof($locales) > 1);
?>
<div class="actionBar">
    <form action="<?= $this->adminLink(NULL) ?>" method="get" name="bannersForm" id="j-banners-form" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="order" value="<?= $order_by.tpl::ORDER_SEPARATOR.$order_dir ?>" />
        <div class="controls controls-row">
            <div class="left">
            <select name="pos" class="input-medium" style="width: 130px;" onchange="jBannersList.submit();">
                 <option value="">Все позиции</option>
                 <?php foreach($positions as $k=>$v) { ?>
                    <option value="<?= $v['id'] ?>" <?php if($f['pos'] == $v['id']){ ?>selected="selected"<?php } ?>><?= $v['title'] ?>&nbsp;(<?= $v['sizes'] ?>)</option>
                 <?php } ?>
            </select>&nbsp;
            <? if($localeFilter) { ?>
            <select name="locale" onchange="jBannersList.submit();" style="width: 120px;">
                <option value=""<? if(empty($f['locale'])){ ?> selected="selected"<? } ?>>Локализация</option>
                <option value="<?= Banners::LOCALE_ALL ?>"<? if($f['locale'] == Banners::LOCALE_ALL){ ?> selected="selected"<? } ?>>Все локализации</option>
                <? foreach ($locales as $k=>$v) { ?>
                    <option value="<?= $k ?>"<? if($f['locale'] == $k){ ?> selected="selected"<? } ?>><?= $v['title'] ?></option>
                <? } ?>
            </select>&nbsp;
            <? } ?>
            </div>
            <div class="left">
                <?= Geo::i()->regionSelect($f['region'], 'region', array(
                    'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? 'Страна / Регион' : 'Регион', 'width' => '130px',
                )); ?>
            </div>
            <div class="left" style="margin-left: 4px;">
            &nbsp;Показ: <input type="text" name="show_start" value="<?= HTML::escape($f['show_start']) ?>" placeholder="с" style="width:65px;" />
            <input type="text" name="show_finish" value="<?= HTML::escape($f['show_finish']) ?>" placeholder="по" style="width:65px;" />
            &nbsp;<select name="status" onchange="jBannersList.submit();" style="width:100px;"><?= HTML::selectOptions(array(0=>'все',1=>'выключенные',2=>'включенные'), $f['status']) ?></select>
            &nbsp;<input class="btn btn-small button submit" type="submit" value="найти" />
            <a class="cancel" onclick="jBannersList.reset(); return false;">сбросить</a>
            </div>
            <div class="clear"><div
        </div>
    </form>
</div> 
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?
            $aCols = array(
                'id'          => array('t'=>'ID',       'w'=>40,   'order'=>'desc'),
                'title'       => array('t'=>'Баннер',   'w'=>false,'order'=>false),
                'limit'       => array('t'=>'Лимит',    'w'=>60,   'order'=>false),
                'show_start'  => array('t'=>'Начало показа', 'w'=>75, 'order'=>'desc'),
                'show_finish' => array('t'=>'Конец показа',  'w'=>75, 'order'=>'desc'),
                'shows'       => array('t'=>'Показов',  'w'=>66,   'order'=>'desc'),
                'clicks'      => array('t'=>'Кликов',   'w'=>60,   'order'=>'desc'),
                'ctr'         => array('t'=>'CTR(%)',   'w'=>61,   'order'=>'desc'),
                'action'      => array('t'=>'Действие', 'w'=>104,  'order'=>false),
            );
            foreach($aCols as $k=>$v) {
                if( empty($v['order']) ) {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>><?= $v['t'] ?></th><?
                } else {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>>
                     <?php if( $order_by == $k ) { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $order_dir_needed ?>');"><?= $v['t'] ?>
                        <div class="order-<?= $order_dir ?>"></div></a>
                     <?php } else { ?>
                        <a href="javascript:void(0);" onclick="jBannersList.order('<?= $k ?>-<?= $v['order'] ?>');"><?= $v['t'] ?></a>
                     <?php } ?>
                     </th><?
                }
            }
        ?>
    </tr>
</thead>
<?php foreach($banners as $k=>$v) { ?>
<tr class="row<?= $k%2 ?><?php if( ! $v['enabled']) { ?> desc<?php } ?>">
        <td class="small"><?= $v['id'] ?></td>
        <td width="200">
            <a href="<?= $v['click_url'] ?>" class="but linkout" target="_blank"></a><a href="javascript:void(0)" onclick="return jBannersList.preview(<?= $v['id'] ?>);"><?= $v['pos']['title'] ?></a><br />
            <a href="#" onclick="jBannersList.region(<?= $v['region_id'] ?>); return false;" class="desc"><?= $v['region_title'] ?></a>
            <? if($localeFilter && ! empty($v['locale']) && ! in_array(Banners::LOCALE_ALL, $v['locale'])) { ?>
               <span class="desc">/ <? foreach ($v['locale'] as $l) { ?><a href="javascript:void(0);" class="but lng-<?= $l ?>" style="margin-right: 3px;"></a><? } ?></span>
            <? } ?>
        </td>
        <td><?= ( ! empty($v['show_limit']) ? $v['show_limit'] : 'нет') ?></td>
        <td><?= tpl::date_format3($v['show_start'], 'd.m.Y') ?></td>
        <td><?= tpl::date_format3($v['show_finish'], 'd.m.Y') ?></td>
        <td><?= intval($v['shows']) ?></td>
        <td><?= intval($v['clicks']) ?></td>
        <td><?= $v['ctr'] ?></td>
        <td>
            <a class="but sett" title="Статистика" href="<?= $this->adminLink('statistic&id='.$v['id']) ?>" ></a>
            <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" onclick="return jBannersList.toggle(<?= $v['id'] ?>, this);"></a>
            <a class="but edit" href="<?= $this->adminLink('edit&id='.$v['id']) ?>"></a>
            <a class="but del" href="#" onclick="bff.confirm('sure',{r: '<?= $this->adminLink('delete&id='.$v['id']) ?>'}); return false;"></a>
        </td>
</tr>
<?php } if(empty($banners)) { ?>
<tr class="norecords">
    <td colspan="9">нет баннеров</td>
</tr>
<?php } ?>
</table>
<script type="text/javascript">
var jBannersList = (function(){
    var $form;

    $(function(){
        $form = $('#j-banners-form');
        bff.datepicker('input[name^=show_]', {yearRange: '-5:+5'});
    });

    function formSubmit()
    {
        $form.submit();
    }

    return {
        toggle: function(id, link)
        {
            bff.ajaxToggle(id, '<?= $this->adminLink('ajax&act=banner-toggle') ?>', {link: link, complete: function(data){
                $(link).closest('tr').toggleClass('desc');
            }});
            return false;
        },
        order: function(order)
        {
           $('[name=order]', $form).val(order);
           formSubmit();
        },
        preview: function(id)
        {
            bff.ajax('<?= $this->adminLink('preview') ?>', {id:id}, function(data){
                if(data) { $.fancybox(data); }
            });
            return false;
        },
        region: function(regionID)
        {
            $('.j-geo-region-select-id', $form).val(regionID);
            formSubmit();
        },
        reset: function()
        {
            bff.redirect('<?= $this->adminLink(bff::$event) ?>');
        },
        submit: function()
        {
            formSubmit();
        }
    };
}());
</script>