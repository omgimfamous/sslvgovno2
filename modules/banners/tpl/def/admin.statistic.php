<?php
    tpl::includeJS(array('datepicker'), true);
?>
<div class="actionBar">
    <form action="<?= $this->adminLink(null) ?>" method="get" name="statListingFilterForm" id="j-banner-statistic-form" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="id" value="<?= $banner['id'] ?>" />
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="order" value="<?= $f['order'] ?>" /> 
        <div class="controls controls-row">
            <strong>Статистика:</strong>
            <input type="text" name="date_start" value="<?= $f['date_start'] ?>" placeholder="c" style="width:70px;" />
            <input type="text" name="date_finish" value="<?= $f['date_finish'] ?>" placeholder="по" style="width:70px;" />
            &nbsp;<input type="button" onclick="jBannerStatistic.submit();" class="btn btn-small button submit" value="показать" />
            <a class="cancel" onclick="jBannerStatistic.reset(); return false;">сбросить</a>
        </div>
        <div class="controls controls-row">
            <div><strong>Баннер:</strong>&nbsp;&nbsp;<a href="<?= $banner['click_url'] ?>" class="but linkout" target="_blank"></a><a href="javascript:void(0)" onclick="return jBannerStatistic.preview(<?= $banner['id'] ?>);">просмотреть</a>&nbsp;<?php if( ! $banner['enabled']){ ?> - <span class="clr-error">выключен</span><?php } ?></div>
            <div><strong>Лимит показов:</strong>&nbsp;&nbsp;<?= ($banner['show_limit'] == 0 ? '<span class="desc">нет</span>' : tpl::declension($banner['show_limit'], 'показ;показа;показов')); ?></div>
            <div><strong>Позиция:</strong>&nbsp;&nbsp;<a href="<?= $this->adminLink('listing&pos='.$banner['pos']) ?>"><?= $banner['position']['title'] ?>&nbsp;(<?= $banner['position']['sizes'] ?>)</a></div>
            <div><strong>Период показа:</strong>&nbsp;&nbsp;<?= tpl::date_format2($banner['show_start']) ?> - <?= tpl::date_format2($banner['show_finish']) ?></div>
        </div>
    </form>
</div>
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?
            $aCols = array(
                'period' => array('t'=>'Дата',      'w'=>150,   'order'=>'asc'),
                'shows'  => array('t'=>'Показов',   'w'=>false, 'order'=>'asc'),
                'clicks' => array('t'=>'Переходов', 'w'=>false, 'order'=>'asc'),
                'ctr'    => array('t'=>'CTR(%)',    'w'=>65,    'order'=>'asc'),
            );
            foreach($aCols as $k=>$v) {
                if( empty($v['order']) ) {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>><?= $v['t'] ?></th><?
                } else {
                    ?><th<?php if(!empty($v['w'])) echo ' width="'.$v['w'].'"' ?>>
                     <?php if( $f['order_by'] == $k ) { ?>
                        <a href="javascript:void(0);" onclick="jBannerStatistic.order('<?= $k ?>-<?= $f['order_dir_needed'] ?>');"><?= $v['t'] ?>
                        <div class="order-<?= $f['order_dir'] ?>"></div></a>
                     <?php } else { ?>
                        <a href="javascript:void(0);" onclick="jBannerStatistic.order('<?= $k ?>-<?= $v['order'] ?>');"><?= $v['t'] ?></a>
                     <?php } ?>
                     </th><?
                }
            }
        ?>
    </tr>
</thead>
<?php foreach($stat as $k=>$v){ ?>
<tr class="row<?= ($k%2) ?>">
    <td><?= tpl::date_format2($v['period']) ?></td>
    <td><?= $v['shows'] ?></td>
    <td><?= $v['clicks'] ?></td>
    <td><?= $v['ctr'] ?></td>
</tr>
<?php } if(empty($stat)){ ?>
<tr class="norecords">
    <td colspan="4">нет данных (возможно баннер еще не просматривался)</td>
</tr>
<?php } ?>
</table>
<?= $pgn; ?>
<script type="text/javascript">
var jBannerStatistic = (function(){
    var $form;

    $(function(){
        $form = $('#j-banner-statistic-form');
        bff.datepicker($('input[name^=date_]', $form), {yearRange: '-5:+5'});
    });

    return {
        preview: function(id)
        {
            bff.ajax('<?= $this->adminLink('preview') ?>', {id:id}, function(data){
                if(data) { $.fancybox(data); }
            });
            return false;
        },
        order: function(order)
        {
           $('[name="order"]', $form).val(order);
           $form.submit();
        },
        page: function(pageID)
        {
            $('[name="page"]', $form).val(pageID);
           $form.submit();
        },
        submit: function() {
            jBannerStatistic.page(1);
        },
        reset: function() {
            bff.redirect('<?= $this->adminLink(bff::$event.'&id='.$banner['id']) ?>');
        }
    };
}());
</script>