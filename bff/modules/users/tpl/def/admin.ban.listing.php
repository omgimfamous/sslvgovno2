<?php

?>
<?= tplAdmin::blockStart('Разблокировать доступ или удалить из белого списка') ?>
<?php if( ! empty($bans) ) { ?>
<div class="actionBar">
    <span class="text-info"><strong>жирным</strong> - белый список</span>
</div>
<form action="" method="post">
<input type="hidden" name="act" value="massdel" />
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?php if(FORDEV) { ?><th width="40">ID</th><?php } ?>
        <th width="40"><input type="checkbox" id="сheckAll" value="0" onclick="if(this.value == '1') {onCheck(0); this.value = '0';} else {onCheck(1); this.value = '1';}" /></th>
        <th class="left" width="200">Блокировка</th>
        <th width="100">Тип</th>
        <th class="left">Описание</th>
        <th width="90">Период</th>
        <th width="35"></th>
    </tr>
</thead>
<?php foreach($bans as $k=>$v) { $id = $v['id']; ?>
<tr class="row<?= $k%2 ?>" id="ban<?= $id ?>">
    <?php if(FORDEV) { ?><td class="small"><?= $id ?></td><?php } ?>
    <td><input type="checkbox" class="сheckBan" value="<?= $id ?>" title="блокировка администратора" name="banid[]" /></td>
    <td class="left"><span class="<?php if($v['exclude']){ ?> bold<?php } ?>"><?= ( ! empty($v['ip']) ? $v['ip'] : $v['email'] ) ?></span></td>
    <td class="desc"><?= ( ! empty($v['ip']) ? 'IP' : 'e-mail' ) ?></td>
    <td><a class="desc" href="javascript:void(0);" title="для пользователя: <?= HTML::escape( tpl::truncate($v['reason'], 50) ) ?>&shy;"><?= tpl::truncate($v['description'], 50) ?></a></td>
    <td class="small"><?= $v['till'] ?><br /><?php if($v['finished']) { ?> до <?php echo tpl::date_format3($v['finished_formated'], 'd.m.Y H:i'); } ?></td>
    <td>
        <a class="but del" title="Удалить" href="javascript:void(0);" onclick="bff.ajaxDelete('Удалить блокировку?', <?= $id ?>, '<?= $this->adminLink(bff::$event.'&act=delete') ?>', this); return false;" ></a>
    </td>
</tr>
<?php } ?>
<tfoot>
    <tr class="footer">
        <td colspan="7">
            <input type="submit" class="btn btn-danger btn-small button delete" onclick="if( ! bff.confirm('sure')) return false;" value="Удалить выделенные" />
        </td>
    </tr>
</tfoot>
</table>
</form>
<?php } else { ?>
<table class="admtbl">
<tr class="norecords">
    <td>нет блокировок</td>
</tr>
</table>
<?php } ?>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart('Заблокировать доступ', false) ?>
<form method="post" action="" name="banlistForm">
<input type="hidden" name="banmode" id="banmode" value="ip" />
<table class="admtbl tbledit">
<tr>
    <td class="row1 actionBar" colspan="2">
        <span class="bold field-title">Заблокировать:</span>&nbsp;&nbsp;
        <a onclick="return banType('ip');" id="typelnk_ip" class="clr-error ajax bold" href="#">доступ с IP-адресов</a>,
        <a onclick="return banType('email');" id="typelnk_email" class="ajax hidden" href="#">email адреса</a>
        <br />
        <span class="desc" id="type_ip_desc">
            Вводите каждый IP-адрес или имя узла на новой строке. Для указания диапазона IP-адресов отделите его начало и конец дефисом (-), или используйте звёздочку (*) в качестве подстановочного знака.
            <br /><u>Проверка IP-адреса производится</u>:<br />
                <div>- при регистрации пользователей</div>
                <div>- при авторизации администраторов в админ. панель</div>
                <div>- при авторизации пользователей</div>
        </span>
        <span class="desc" id="type_email_desc" style="display:none;">
            Вводите каждый адрес на новой строке. Используйте звёздочку (*) в качестве подстановочного знака для блокировки группы однотипных адресов. Например, *@gmail.com, *@*.example.com и т.д.
            <br /><u>Проверка email-адреса производится</u>:<br />
                <div>- при регистрации пользователей</div>
        </span>
    </td>
</tr>
<tr id="type_ip_ban" class="required">
    <td class="row1 field-title" width="140">IP-адреса или хосты:</td>
    <td class="row2"><textarea name="ban_ip" style="height: 85px;"></textarea></td>
</tr>
<tr id="type_email_ban" class="required" style="display:none;">
    <td class="row1 field-title" width="140">Email адрес:</td>
    <td class="row2"><textarea name="ban_email" style="height: 85px;"></textarea></td>
</tr>
<tr>
    <td class="row1 field-title">Продолжительность блокировки:</td>
    <td class="row2">
        <select name="banlength" onchange="if(this.value==-1){ $('#till').show(); }else{ $('#till').hide();}" style="width:100px;">
            <option value="0">Бессрочно</option>
            <option value="30">30 минут</option>
            <option value="60">1 час</option>
            <option value="360">6 часов</option>
            <option value="1440">1 день</option>
            <option value="10080">7 дней</option>
            <option value="20160">2 недели</option>
            <option value="40320">1 месяц</option>
            <option value="-1">До даты ... </option>
        </select>
    </td>
</tr>
<tr id="till" style="display:none;">
    <td class="row1 field-title">До даты:</td>
    <td class="row2"><input style="width:94px;" type="text" name="bandate" value="" />&nbsp;&nbsp;&nbsp; <span class="desc">ДД-ММ-ГГГГ</span>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Добавить в белый список:</td>
    <td class="row2">
        <label class="radio inline"><input type="radio" class="radio" value="1" name="exclude"/> Да</label><br />
        <label class="radio inline"><input type="radio" class="radio" checked="checked" value="0" name="exclude"/> Нет
            <span class="desc" id="type_ip_exclude">(исключить введённые IP-адреса из чёрного списка.)</span>
            <span class="desc" id="type_email_exclude" style="display:none;">(исключить введённые email адреса из чёрного списка.)</span>
        </label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Причина блокировки доступа:</td>
    <td class="row2"><textarea name="description" id="description" onkeyup="bff.textLimit('description', 255);" onkeydown="bff.textLimit('description', 255);" style="height: 85px;"></textarea></td>
</tr>
<tr>
    <td class="row1 field-title">Причина, показываемая пользователю:</td>
    <td class="row2"><textarea name="reason" id="reason" onkeyup="bff.textLimit('reason', 255);" onkeydown="bff.textLimit('reason', 255);" style="height: 85px;"></textarea></td>
</tr>
<tr class="footer">
    <td colspan="2">
        <input type="submit" class="btn btn-danger button delete" value="Заблокировать" />
    </td>
</tr>
</table>
</form>
<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
//<![CDATA[

function banType(type)
{   
    type = type || 'ip';
    
    //links
    $('a[id^="typelnk"]').removeClass('clr-error bold');
    $('a[id^="typelnk_'+type+'"]').addClass('clr-error bold');
    
    $('*[id^="type_"]').hide();
    $('*[id^="type_'+type+'"]').show();
    
    $('#banmode').val(type);
    
    return false;
}

function onCheck(check)
{
    var cAll = $('#сheckAll');
    cAll.checked = false; cAll.value = 0;
    
    if(check)
    {
        cAll.checked = true; cAll.value = 1; 
        $('input.сheckBan').prop('checked', true);
    } 
    else 
    { 
        $('input.сheckBan').prop('checked', false);
    }
    return false;
}

var fchecker = new bff.formChecker( document.forms.banlistForm );

//]]>
</script>