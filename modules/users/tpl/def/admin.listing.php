<?php
    tpl::includeJS(array('datepicker'), true);
    $f = HTML::escape($f, 'html', array('r_from','r_to','a_from','a_to','q'));
    tplAdmin::adminPageSettings(array('link'=>array(
        'title'=>'+ добавить пользователя','href'=>$this->adminLink('user_add')
    )));

    $aTabs = array(
        0 => array('t'=>'Все'),
        1 => array('t'=>'Активированные'),
        2 => array('t'=>'Неактивированные'),
        3 => array('t'=>'Заблокированные'),
        4 => array('t'=>'Подписавшиеся'),
    );
    $statusNotActivated = ($f['status'] == 2);
?>
<script type="text/javascript">
$(function(){
    var url = '<?= $this->adminLink('ajax&act='); ?>';

    var $filter = $('#j-users-listing-filter-form');
    bff.datepicker('.bff-datepicker', {yearRange: '-3:+3'});

    $('#j-users-listing-filter-cancel').on('click', function(e){ nothing(e);
        var filter = $filter.get(0);
        filter.elements.page.value = 1;
        filter.elements.r_from.value = '';
        filter.elements.r_to.value = '';
        filter.elements.a_from.value = '';
        filter.elements.a_to.value = '';
        filter.elements.q.value = '';
        $filter.find('.j-geo-region-select-id').val(0);
        $filter.find('.j-geo-region-select-ac').val('');
        filter.submit();
    });

    $('.j-users-listing-filter-status-tab').on('click', function(e){ nothing(e);
        var statusID = $(this).data('id');
        $filter.find('.j-users-listing-filter-status').val(statusID);
        $filter.get(0).elements.page.value = 1;
        $filter.submit();
    });

    var $list = $('#j-list');
    $('#j-check-all').change(function(){
        $list.find('.j-user-check').prop('checked', $(this).is(':checked'));
        showMassPanel();
    });
    $list.on('change', '.j-user-check', function(){
        showMassPanel();
    });

    var $mass = $('#j-mass-unactivated');
    var $info = $mass.find('.j-info');
    function showMassPanel()
    {
        var $ch = $list.find('.j-user-check:checked');
        $info.html('Выбрано <strong>' + $ch.length + '</strong> ' + bff.declension($ch.length,['пользователь','пользователя','пользователей'], false));
        $mass.toggleClass('hide', ! $ch.length);
    }
    $mass.find('.j-remove-checked').click(function(){
        var $ch = $list.find('.j-user-check:checked');
        if(confirm('Удалить ' + $ch.length + ' ' + bff.declension($ch.length,['пользователь','пользователя','пользователей'], false) + '?')){
            deleteUnactivated('checked');
        }
    });
    $mass.find('.j-remove-all').click(function(){
        if(confirm('Удалить всех пользователей?')){
            deleteUnactivated('all');
        }
    });

    function deleteUnactivated(mode)
    {
        var $ch = $list.find('.j-user-check:checked');
        bff.ajax(url+'delete-unactivated', $ch.serialize()+'&mode='+mode+'&id=1' , function(resp){
            if (resp && resp.success) {
                bff.success(resp.msg);
                if (resp.hasOwnProperty('deleted')) {
                    $list.find('.j-user-check').each(function(){
                        var $el = $(this);
                        if (resp.deleted.indexOf($el.val()) >= 0) {
                            $el.closest('tr').remove();
                        }
                    });
                }
                showMassPanel();
            }
        });
    }

});
</script>

<div class="tabsBar" id="items-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$f['status'] ? ' tab-active' : '' ?>"><a href="#" data-id="<?= $k ?>" class="j-users-listing-filter-status-tab"><?= $v['t'] ?></a></span>
    <? } ?>
</div>

<div class="actionBar">
    <form action="" method="get" name="filters" id="j-users-listing-filter-form" class="form-inline">
        <div class="left">
            <input type="hidden" name="s" value="users" />
            <input type="hidden" name="ev" value="listing" />
            <input type="hidden" name="status" value="<?= $f['status'] ?>" class="j-users-listing-filter-status" />
            <input type="hidden" name="order" value="<?= $order_by.tpl::ORDER_SEPARATOR.$order_dir ?>" />
            <input type="hidden" name="page" value="1" />
            <label>регистрация: <input type="text" name="r_from" value="<?= $f['r_from'] ?>" placeholder="от" style="width: 65px;" class="bff-datepicker" /></label><label>&nbsp;<input type="text" name="r_to" value="<?= $f['r_to'] ?>" placeholder="до" style="width: 65px;" class="bff-datepicker" />&nbsp;</label>
            <label>был: <input type="text" name="a_from" value="<?= $f['a_from'] ?>" placeholder="от" style="width: 65px;" class="bff-datepicker" /></label><label>&nbsp;<input type="text" name="a_to" value="<?= $f['a_to'] ?>" placeholder="до" style="width: 65px;" class="bff-datepicker" />&nbsp;</label>
            <label><input type="text" placeholder="ID / логин / e-mail" name="q" value="<?= $f['q'] ?>" style="width: 140px;" /></label>
        </div>
        <? if ( ! Geo::coveringType(Geo::COVERING_CITY)): ?>
        <div class="left" style="margin-left: 4px;">
            <?= Geo::i()->regionSelect($f['region'], 'region', array(
                'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? 'Страна / Регион' : 'Регион', 'width' => '120px',
            )); ?>
        </div>
        <? endif; ?>
        <div class="left" style="margin: 1px 0 0 4px;">
            <input type="submit" value="найти" class="btn btn-small button submit" />
            <a class="cancel" id="j-users-listing-filter-cancel">сбросить</a>
        </div>
        <div class="clear"></div>
    </form>
    <div id="j-mass-unactivated" class="well well-small hide">
        <span class="j-info"></span>&nbsp;&nbsp;
        <input type="button" class="btn btn-mini btn-danger button j-remove-checked" value="удалить выбранных" />
        <input type="button" class="btn btn-mini btn-danger button j-remove-all" value="удалить все" />
    </div>
</div>

<table class="table table-condensed table-hover admtbl" id="j-list">
<thead>
    <tr class="">
        <? if($statusNotActivated): ?><th width="20"><label class="checkbox inline"><input type="checkbox" id="j-check-all" /></label></th><? endif; ?>
        <?
            $aHeaderCols = array(
                'user_id' => array('t'=>'ID','w'=>60,'order'=>'desc'),
                'email' => array('t'=>'E-mail','w'=>165,'order'=>'asc','align'=>'left'),
                'name' => array('t'=>'Имя','w'=>150,'align'=>'left'),
            );
            if ($shops_on) {
                $aHeaderCols['shop'] = array('t'=>'Магазин','align'=>'left');
            } else {
                unset($aHeaderCols['name']['w']);
            }
            $aHeaderCols += array(
                'last_login' => array('t'=>'Был','w'=>125,'order'=>'desc'),
                'action' => array('t'=>'Действие','w'=>85)
            );
            $urlOrderBy = $this->adminLink('listing'.$filter.'&page=1&order=');
            foreach($aHeaderCols as $k=>$v) {
                ?><th<? if( ! empty($v['w']) ) { ?> width="<?= $v['w'] ?>"<? } if( ! empty($v['align']) ) { ?>  class="<?= $v['align'] ?>"<? } ?>><?
                if( ! empty($v['order'])) {
                    if( $order_by == $k ) {
                        ?><a href="<?= $urlOrderBy.$k.tpl::ORDER_SEPARATOR.$order_dir_needed ?>"><?= $v['t'] ?><div class="order-<?= $order_dir ?>"></div></a><?
                    } else {
                        ?><a href="<?= $urlOrderBy.$k.tpl::ORDER_SEPARATOR.$v['order'] ?>"><?= $v['t'] ?></a><?
                    }
                } else {
                    echo $v['t'];
                }
                ?></th><?
            }
        ?>
    </tr>
</thead>
<? foreach($users as $k=>$v) { $id = $v['user_id']; ?>
<tr class="row<?= $k%2 ?>">
    <? if($statusNotActivated): ?><td><label class="checkbox inline"><input type="checkbox" name="i[]" value="<?= $id ?>" class="check j-user-check" /></label></td><? endif; ?>
    <td><?= $id ?></td>
    <td class="left"><a href="#" onclick="return bff.userinfo(<?= $id ?>);" class="nolink<?= ! $v['activated'] ? ' disabled' : '' ?>"><?= $v['email'] ?></a></td>
    <td class="left"><span class="<?= ! $v['activated'] ? 'disabled' : '' ?>"><?= tpl::truncate($v['name'], 20) ?></span></td>
    <? if($shops_on) { ?><td class="left"><? if($v['shop_id'] > 0) { ?><a href="<?= Shops::urlDynamic($v['shop']['link']) ?>" target="_blank" class="but linkout"></a><a href="#" onclick="return bff.shopInfo(<?= $v['shop_id'] ?>);"><?= tpl::truncate($v['shop']['title'], 40) ?></a><? } else { ?><span class="desc" style="padding-left: 20px;">-</span><? } ?></td><? } ?>
    <td><?= ( $v['last_login'] != '0000-00-00 00:00:00' ? tpl::date_format3($v['last_login'], 'd.m.Y H:i') : '&mdash;') ?></td>
    <td>
        <a class="but <? if( ! $v['blocked'] ) { ?>un<? } ?>block" href="#" onclick="return bff.userinfo(<?= $id ?>);" id="u<?= $id ?>"></a>
        <a class="but edit" href="<?= $this->adminLink('user_edit&members=1&tuid='.$v['tuid'].'&rec='.$id) ?>"></a>
    </td>
</tr>
<? } if( empty($users) ) { ?>
<tr class="norecords">
    <td colspan="<?= sizeof($aHeaderCols) + intval($statusNotActivated); ?>">нет пользователей</td>
</tr>
<? } ?>
</table>

<?= $pgn; ?>