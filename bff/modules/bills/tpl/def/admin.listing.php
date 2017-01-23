<?php 

?>
<?= tplAdmin::blockStart('Счета / Список') ?>
    <div class="actionBar">
    <form action="" method="get" name="filters" id="j-bills-filters" onsubmit="return false;" class="form-inline">
        <input type="hidden" name="offset" value="<?= $f['offset'] ?>" />
        <input type="hidden" name="order" value="<?= $f['order'] ?>" />
        <input type="hidden" name="status" value="<?= $f['status'] ?>" />

        <div class="tabsBar j-status-tabs">
            <span class="tab j-tab<?php if( ! $f['status']) { ?> tab-active<?php } ?>" data-status="0">Все</span>
            <?php foreach($status_data as $k=>$v): ?>
                <span class="tab j-tab<?php if($k == $f['status']) { ?> tab-active<?php } ?>" data-status="<?= $k ?>"><?= tpl::ucfirst($v) ?></span>
            <?php endforeach; ?>
            <span class="progress" style="display:none; margin-left:2px;" id="j-bills-progress"></span>
        </div>

        <label>№ счета: <input type="text" name="id" value="<?= ($f['id']>0?$f['id']:'') ?>" style="width: 85px;" /></label>
        &nbsp;<label>ID объекта: <input type="text" name="item" value="<?= ($f['item']>0?$f['item']:'') ?>" style="width: 85px;" /></label>
        &nbsp;<label>Дата: <input type="text" name="p_from" value="<?= $f['p_from'] ?>" placeholder="от" style="width: 70px;" id="j-bills-period-from" /></label>
        <label><input type="text" name="p_to" value="<?= $f['p_to'] ?>" placeholder="до" style="width: 70px;" id="j-bills-period-to" /></label>
        &nbsp;<label class="relative">
           <input type="hidden" name="uid" id="j-bills-user-id" value="<?= $f['uid'] ?>" />
           <input type="text" name="uemail" class="autocomplete" id="j-bills-user-login" style="width: 140px;" placeholder="E-mail пользователя" value="<?= ($f['uid']>0 ? $user['email'] : '') ?>" />
        </label>
        <input type="button" class="btn btn-small button cancel" onclick="jBills.submit(true);" value="найти" />
        <a class="ajax cancel" onclick="jBills.submit(true,true); return false;">сбросить</a>
        <label>
            Тип операции: <select onchange="jBills.submit(true);" style="width: 140px;" name="type">
                <option class="bold" value="0">- все -</option>
                <?= $type_options ?>
            </select>
        </label>
        &nbsp;<label>
            Услуга: <select onchange="jBills.submit(true);" style="width: 140px;" name="svc">
                <option class="bold" value="0">- все -</option>
                <?= $svc_options ?>
            </select>
        </label>
    </form>
    </div>

    <table class="table table-condensed table-hover admtbl tblhover">
    <thead>
        <tr class="header">
            <th width="50">
                <a href="javascript: jBills.onOrder('id');" class="ajax">ID</a>
                <div class="order-<?= $f['order_dir'] ?>" <?php if($f['order_by']!='id') { ?>style="display:none;"<?php } ?> id="j-order-id"></div>
            </th>
            <th width="95">Дата</th>
            <th width="135">Пользователь</th>
            <th width="60">Баланс</th>
            <th width="60">Сумма</th>
            <th>Описание</th>
            <th width="125">Статус</th>
        </tr>
    </thead>
    <tbody id="j-bills-list">
    <?= $list; ?>
    </tbody>
    </table>
    <div id="j-bills-pgn" align="center" style="margin-top: 5px;"><?= $pgn; ?></div>
<?= tplAdmin::blockStop() ?>

<?php
    $aFormAttr = array('id'=>'j-ubalance-block');
    if( ! $f['uid'] || ! $access_edit ) $aFormAttr['style'] = 'display: none;';
    echo tplAdmin::blockStart('Операции со счетом пользователя', false, $aFormAttr);
?>
<form action="" method="post" name="filters" id="j-ubalance-form">
    <input type="hidden" name="act" value="ubalance-gift" />
    <input type="hidden" name="uid" value="<?= $f['uid'] ?>" />
    <table class="admtbl tbledit">
        <tr>
            <td colspan="2">
                <p class="text-info">Пополнить счет <?php if($f['uid']>0){ ?><a href="#" id="j-ubalance-login" class="ajax" onclick="return bff.userinfo(<?= $f['uid'] ?>);"><?= $user['email'] ?></a><?php } ?> на указанную сумму:</p>
            </td>
        </tr>
        <tr class="required">
            <td class="row1" width="60"><span class="field-title">Сумма</span>:</td>
            <td class="row2"><input type="text" name="amount" value="0" maxlength="12" style="width:60px" />&nbsp;<span class="desc"><?= $curr['title_short'] ?></span></td>
        </tr>
        <tr class="required">
            <td class="row1"><span class="field-title">Описание</span>:</td>
            <td class="row2"><input type="text" name="description" maxlength="150" value="Подарок от администрации" style="width: 450px;" /></td>
        </tr>
        <tr class="footer">
            <td colspan="2">
                <input type="submit" class="btn btn-success button submit" value="Пополнить" />
                <div class="progress" style="display:none; margin-top: 10px;" id="j-ubalance-progress"></div>
            </td>
        </tr>
    </table>
</form>
<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
<?php js::start(); ?>
var jBills = (function()
{
    var statusResult = <?= func::php2js(array(
        Bills::STATUS_WAITING   => '<span style="color:red;">незавершен</span>',
        Bills::STATUS_COMPLETED => '<span style="color:green;">завершен</span>',
        Bills::STATUS_CANCELED  => '<span style="color:#666;">отменен</span>',
    )); ?>;

    var $progress, $list, $listPgn, filters;
    var url = '<?= $this->adminLink('listing'); ?>';
    var urlAjax = '<?= $this->adminLink('ajax&act='); ?>';
    var orders = <?= func::php2js($orders) ?>;
    var orderby = '<?= $f['order_by'] ?>';
    var _processing = false;
    
    $(function(){
        $progress = $('#j-bills-progress');
        $list     = $('#j-bills-list');
        $listPgn  = $('#j-bills-pgn');
        filters   = $('#j-bills-filters').get(0);

        $('.j-status-tabs', filters).on('click', '.j-tab', function(e){
            $(this).addClass('tab-active').siblings().removeClass('tab-active');
            filters.status.value = $(this).data('status');
            resetOffset();
            updateList();
        });

//        $list.delegate('a.bill-item-link', 'click', function(){
//            var itemID = $(this).data('item');
//            bff.iteminfo(itemID);
//            return false;
//        });

        $('#j-bills-user-login').autocomplete('<?= $this->adminLink('ajax&act=user-autocomplete'); ?>',
            {valueInput: '#j-bills-user-id',
             onSelect: function(uid, login, extra){
                 uid = intval(uid);
                 resetOffset();
                 updateList(); 
                 jUserBalance.onUser(uid, login);
            }});

        bff.datepicker('#j-bills-period-from', {yearRange: '-3:+3'});
        bff.datepicker('#j-bills-period-to', {yearRange: '-3:+3'});
    });
    
    function isProcessing()
    {
        return _processing;
    }

    function updateList(scroll)
    {
        if(isProcessing()) return;
        _processing = true;
        $list.addClass('disabled');
        bff.ajax(url, $(filters).serializeArray(), function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                
                var f = $(filters).serialize();
                if(bff.h) {
                    window.history.pushState({}, document.title, url + '&' + f);
                }
                if(scroll && scroll === true) {
                    $.scrollTo($list, {duration:600, offset:-100});
                }
            }
            $list.removeClass('disabled');
            _processing = false;
        }, $progress);
    }
    
    function setOffset(id)
    {
        filters.offset.value = intval(id);
    }
    
    function resetOffset()
    {
        setOffset(0);
    }
    
    function showCheckResult(msg)
    {
        alert(msg);
    }

    return {
        submit: function(bResetOffset, resetForm)
        {
            if(isProcessing()) return false;
            if(bResetOffset === true) {
                resetOffset();
            }
            if(resetForm && resetForm===true) {
                filters.id.value = '';
                filters.item.value = '';
                filters.p_from.value = '';
                filters.p_to.value = '';
                filters.uid.value = 0;
                filters.uemail.value = '';
                filters.type.value = 0;
                filters.svc.value = 0;
                jUserBalance.onUser(0, '');
            }
            updateList();
        },
        prev: function (id)
        {
            if(isProcessing()) return; setOffset(id); updateList(true);
        },
        next: function (id)
        {
            if(isProcessing()) return; setOffset(id); updateList(true);
        },
        changeStatus: function(bid, cls)
        {
            bff.ajax(urlAjax+'status', $('#tr'+bid+'_form', $list).serialize(), function(data) {
                if(data){   
                    $('#tr'+bid+'_status', $list).html(statusResult[data.status]);
                    jBills.changeStatusCancel(bid, cls);
                }
            }, $progress);
        },
        changeStatusCancel: function (bid, cls)
        {
            $('#tr'+bid+'_chng', $list).remove();
            if(cls == 0) {
                $('#tr'+bid).css('borderBottom', '1px solid #F3EEE8');
            }            
        },
        changeStatusShow: function(cls, money, bid, user_id, login)
        {
            if($('#tr'+bid+'_chng', $list).length>0)
            {  
                jBills.changeStatusCancel(bid, cls);
            } else {
                if(cls == 0)
                    $('#tr'+bid, $list).css('borderBottom', '0');
                
                $('#tr'+bid, $list).after('<tr class="row'+cls+'" style="border-top: 0;" id="tr'+bid+'_chng"><td colspan="7"><div style="padding: 10px; text-align: left; border: 1px dotted #ccc;"><form action="" method="post" id="tr'+bid+'_form"><input type="hidden" name="bid" value='+bid+' /> \
                    <div class="warning" style="height:40px;"></div> \
                    Изменить статус счета #'+bid+' на: <select name="status" style="width:100px;"> <option value="2">завершен</option> <option value="3">отменен</option> </select>&nbsp;&nbsp;<a href="#" class="bold" onclick="jBills.changeStatus('+bid+', '+cls+'); return false;">изменить</a>&nbsp;|&nbsp;<span class="hidden"><a href="#" class="ajax" onclick="jBills.check('+bid+'); return false;">проверить</a>&nbsp;|&nbsp;</span><a href="#" class="ajax cancel" onclick="jBills.changeStatusCancel('+bid+', '+cls+'); return false;">отмена</a> <br /> \
                    <span class="description">После изменения статуса на <u>завершен</u> на счет пользователя <a href="#" onclick="return bff.userinfo('+user_id+');">'+login+'</a> будет зачислена сумма в размере '+money+' <?= $curr['title_short'] ?> </span><br /> \
                    </form></div></td></tr>');
            }
        },
        showExtra: function(bid)
        {
            bff.ajax(urlAjax+'extra', {bid: bid}, function(data)
            {
                if(data){   
                    if(!data.extra) data.extra = 'нет дополнительных данных';
                    alert(data.extra);
                }
            }, $progress);
        },
        check: function(bid)
        {
            bff.ajax(urlAjax+'check', {bid: bid}, function(data)
            {
                if(data){
                   setTimeout(function(){ showCheckResult(data); }, 1);
                }
            }, $progress);
        },
        onOrder: function(by)
        {
            if(isProcessing() || !orders[by])
                return;
                
            orders[by] = (orders[by] == 'asc' ? 'desc' : 'asc');
            //hide prev order direction
            $('#j-order-'+orderby, $list.parent()).hide();
            //show current order direction
            orderby = by;
            $('#j-order-'+orderby, $list.parent()).removeClass('order-asc order-desc').addClass('order-'+orders[by]).show();
                
            filters.order.value = orderby+'-'+orders[by];
            resetOffset();
            
            updateList();
        }
    };
}());

var pgn = {
    prev: jBills.prev,
    next: jBills.next
};

var jUserBalance = (function(){
    var $block, $form, $login;
    
    $(function(){
        $block = $('#j-ubalance-block');
        $form = $('#j-ubalance-form', $block); 
        $login = $('#j-ubalance-login', $block); 
    });
    
    return {
        onUser: function(uid, login)
        {
            <?php if(!$access_edit){ ?>return false;<?php } ?>
            
            if(uid>0) $block.show();
            else $block.hide();
            
            $login.html('<a href="#" onclick="bff.userinfo('+uid+'); return false;">'+login+'</a>');
            $form.get(0).uid.value = uid;
        }
    };
}());
<?php js::stop(); ?>
</script>