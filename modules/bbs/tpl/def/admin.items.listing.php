<?php
    tpl::includeJS(array('datepicker'), true);

    $aTabs = array(
        0 => array('t'=>'Опубликованные'),
        2 => array('t'=>'Снятые с публикации'),
        3 => array('t'=>'На модерации', 'counter'=>config::get('bbs_items_moderating', 0)),
        4 => array('t'=>'Неактивированные','c'=>' class="disabled" '),
        5 => array('t'=>'Заблокированные'),
        6 => array('t'=>'Удаленные'),
        7 => array('t'=>'Все'),
    );

    tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>'+ добавить объявление', 'href'=>$this->adminLink('add')),
        'fordev'=>array(
            array('title'=>'обновление ссылок объявлений', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-links-rebuild')."'})", 'icon'=>'icon-check'),
            array('title'=>'опубликовать все снятые с публикации', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('listing&act=dev-items-publicate-all-unpublicated')."'})", 'icon'=>'icon-arrow-up'),
        ),
    ));
?>

<div class="tabsBar" id="items-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$f['status'] ? ' tab-active' : '' ?>"><a href="#" onclick="return jItems.onStatus(<?= $k ?>, this);"<?= (!empty($v['c']) ? $v['c'] : '') ?>><?= $v['t'] ?><? if(! empty($v['counter'])){ ?> (<?= $v['counter'] ?>)<? } ?></a></span>
    <? } ?>
    <span class="progress pull-right" style="display:none; margin: 8px 5px 0 0;" id="progress-items"></span>
</div>

<div class="actionBar">
    <form action="" method="get" name="filters" id="items-filters" class="form-inline" onsubmit="return false;">
        <input type="hidden" name="page" value="<?= $f['page'] ?>" />
        <input type="hidden" name="order" value="<?= $f['order'] ?>" />
        <input type="hidden" name="status" value="<?= $f['status'] ?>" />
        <div class="controls controls-row">
            <div class="left">
                <select name="cat" onchange="jItems.onCategory(intval(this.value));" style="width: 140px;"><?= $cats_select; ?></select>
                <input type="text" maxlength="150" name="title" id="items-title-or-id" placeholder="ID / Заголовок объявления" value="<?= HTML::escape($f['title']) ?>" style="width: 125px;" />
                <input type="text" maxlength="130" name="uid" id="items-uid" placeholder="ID / E-mail пользователя" value="<?= HTML::escape($f['uid']) ?>" style="width: 125px;" />
                <? if($shops_on) { ?><input type="text" maxlength="20" name="shopid" id="items-shop-id" placeholder="ID магазина" value="<?= HTML::escape($f['shopid']) ?>" style="width: 75px;" /><? } ?>
                <select name="moderate_list" class="input-small"<? if($f['status']!==3){ ?> style="display:none"<? } ?> id="items-moderate-list"><?= HTML::selectOptions(array(0 => 'все', 1 => 'отредактированные', 2 => 'импортированные'), $f['moderate_list']) ?></select>
            </div>
            <? if ( ! Geo::coveringType(Geo::COVERING_CITY)): ?>
                <div class="left" style="margin-left:4px;">
                    <?= Geo::i()->regionSelect($f['region'], 'region', array(
                        'on_change'=>'jItems.onRegionSelect', 'placeholder' => Geo::coveringType(Geo::COVERING_COUNTRIES) ? 'Страна / Регион' : 'Регион', 'width' => '110px',
                    )); ?>
                </div>
            <? endif; ?>
            <div class="left" style="margin:1px 0 0 4px;">
                <input type="submit" class="btn btn-small" onclick="jItems.submit(false);" value="найти" />
                <a class="ajax cancel" onclick="jItems.submit(true); return false;">сбросить</a>
            </div>
        </div>
        <div class="clearfix"></div>
    </form>
    <div id="j-massModerateInfo" class="well well-small hide">
        <span class="j-info"></span>&nbsp;
        <input type="submit" class="btn btn-mini btn-success success button" onclick="jItems.massModerate();" value="одобрить выбранные" />
    </div>
</div>
<table class="table table-condensed table-hover admtbl">
<thead>
    <tr>
        <th width="20"><label class="checkbox inline"><input type="checkbox" id="j-check-all" onclick="jItems.massModerate('check-all',this);" /></label></th>
        <th width="30">ID</th>
        <th class="left" style="padding-left: 10px;">Заголовок</th>
        <th width="30"></th>
        <th width="160">
            <a href="javascript: jItems.onOrder('created');" class="ajax">Создано</a>
            <div class="order-<?= $f['order_dir'] ?>" <? if($f['order_by']!='created') { ?>style="display:none;"<? } ?> id="items-order-created"></div>
        </th>
        <th width="140">Действие</th>
    </tr>
</thead>
<tbody id="items-list">
<?= $list ?>
</tbody>
</table>
<div id="items-pgn"><?= $pgn; ?></div>

<script type="text/javascript">
var jItems = (function()
{
    var $progress, $list, $listPgn, filters, $moderateList;
    var url = '<?= $this->adminLink('listing'); ?>';
    var orders = <?= func::php2js($orders) ?>;
    var orderby = '<?= $f['order_by'] ?>';
    var status = intval(<?= $f['status'] ?>);
    var cat = intval(<?= $f['cat'] ?>);
    var $checkAll, $checkAllTh, $moderate, $moderateInfo;
    var _processing = false; 
    
    $(function(){
        $progress = $('#progress-items');
        $list     = $('#items-list');
        $checkAll = $('#j-check-all');
        $checkAllTh = $checkAll.parents('th:eq(0)');
        $moderate = $('#j-massModerateInfo');
        $moderateInfo = $moderate.find('.j-info');
        $listPgn  = $('#items-pgn');
        filters   = $('#items-filters').get(0);
        $moderateList = $('#items-moderate-list');

        $list.on('click', 'a.item-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del( id, this );
            return false;
        });
        $list.on('click', '.j-item-import-info', function(){
            var importID = intval($(this).data('import-id'));
            if (importID > 0) {
                $.fancybox('', {ajax: true, href: '<?= $this->adminLink('ajax&act=import-info&id=') ?>' + importID});
            }
        });
        $moderateList.change(function(){
            updateList();
        });
        
        bff.datepicker('#items-period-from', {yearRange: '-3:+3'});
        bff.datepicker('#items-period-to', {yearRange: '-3:+3'});

        massModerateActions();
    });
    
    function isProcessing()
    {
        return _processing;
    }
    
    function del(id, link)
    {
        bff.ajaxDelete('Удалить объявление?', id, url+'&act=delete&id='+id,
            link, {progress: $progress, repaint: false});
        return false;
    } 

    function updateList()
    {
        if(isProcessing()) return;
        _processing = true;
        $list.addClass('disabled');
        var f = $(filters).serialize();
        bff.ajax(url, f, function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                
                if(bff.h) {
                    window.history.pushState({}, document.title, url + '&' + f);
                }
            }
            $list.removeClass('disabled');
            
            massModerateActions();
            
            _processing = false;
        }, $progress);
    }
    
    function massModerateActions()
    {
        $checkAllTh.toggle(status === 3);
    }
    
    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            setPage(1);
            if(resetForm) {
                filters.cat.value = 0;
                filters.title.value = '';
                filters.uid.value = '';
                filters.shopid.value = '';
                $(filters).find('.j-geo-region-select-id').val(0);
                $(filters).find('.j-geo-region-select-ac').val('');
            }
            updateList();
            return true;
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
            return true;
        }, 
        onOrder: function(by)
        {
            if(isProcessing() || !orders[by])
                return;

            orders[by] = (orders[by] == 'asc' ? 'desc' : 'asc');
            //hide prev order direction
            $('#items-order-'+orderby).hide();
            //show current order direction
            orderby = by;
            $('#items-order-'+orderby).removeClass('order-asc order-desc').addClass('order-'+orders[by]).show();

            filters.order.value = orderby+'<?= tpl::ORDER_SEPARATOR ?>'+orders[by];
            setPage(1);
            
            updateList();
        },
        onStatus: function(statusNew, link)
        {
            if(isProcessing() || status == statusNew) return false;
            status = statusNew;
            setPage(1);
            filters.status.value = statusNew;
            updateList();
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            $moderateList.toggle(status === 3);
            return false;
        },
        onCategory: function(catNew)
        {
            if(isProcessing() || cat == catNew) return false;
            cat = catNew;
            setPage(1);
            filters.cat.value = catNew;
            updateList();
            return false;
        },
        massModerate: function(act, extra)
        {
            var $c = $('input.j-item-check:visible:checked:not(:disabled)');
            var $all = $('input.j-item-check:visible:not(:disabled)');

            switch(act) {
                case 'check-all': {
                    if(!$all.length) return false;
                    if(!$c.length || $c.length < $all.length) {
                        $all.prop('checked', true); // доотмечаем неотмеченные
                        $(extra).prop('checked', true);
                        $moderateInfo.html('Выбрано <strong>' + $all.length + '</strong> ' + bff.declension($all.length,['объявление','объявления','объявлений'], false));
                        $moderate.show();
                    } else {
                        $all.prop('checked',false); // снимаем все
                        $(extra).prop('checked',false);
                        $moderate.hide();
                    }
                    return false;
                } break;
                case 'check': {
                    if(!$c.length || $c.length <= 0) {
                        $moderate.hide();
                    } else {
                        $moderateInfo.html('Выбрано <strong>' + $c.length + '</strong> ' + bff.declension($c.length,['объявление','объявления','объявлений'], false));
                        $moderate.show();
                    }
                    
                    if(!$c.length || $c.length < $all.length) $checkAll.prop('checked',false);
                    else $checkAll.prop('checked',true);
                    
                    return false;
                } break;
            }

            if(intval($c.length)<=0){ bff.error('Нет отмеченных объявлений'); return; }
            if(!bff.confirm('sure')) return;

            if(_processing) return false;
            _processing = true;
            bff.ajax('<?= $this->adminLink('ajax'); ?>'+'&act=items-approve', $c.serialize(), function(resp){
                _processing = false;
                if(resp && resp.success) {
                    bff.success('Успешно одобрено: '+resp.updated);
                    setTimeout(function(){ location.reload(); }, 1000);
                }
            }, $progress);
        },
        onRegionSelect: function(cityID, cityTitle, ex)
        {
            setPage(1);
            updateList();
        }
    };
}());
</script>