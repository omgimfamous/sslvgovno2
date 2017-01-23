<?php
    $aData = HTML::escape($aData, 'html', array('noreply'));
?>
<script type="text/javascript">
var sendmailMassend = (function(){
    var $form = false, $testReceivers, processing = false;
    var url = '<?= $this->adminLink('ajax&act=massend-init') ?>';

    $(function(){
        $form = $('#ms-form');
        $testReceivers = $form.find('#ms-receivers-test');
        $form.on('click', 'a.ms-receivers-test-example', function(e){ nothing(e);
            var cur = $testReceivers.val();
            if( cur.length > 0 ) cur += ', ';
            $testReceivers.val( cur + $(this).text() );
        });
    });

    function init(btn)
    {
        if(processing)
            return false;

        var testing = $form.find('#ms-test').is(':checked');
        //есть ли получатели
        if(testing)
        {
            var res = $.trim( $testReceivers.val() );
            if( ! res.length) {
                bff.error('Укажите получателей для тестирования');
                $testReceivers.focus();
                return false;
            }
        }

        //указан ли отправитель
        var f = $form.find('#ms-msg-from');
        if(f.val()=='') {
            f.focus();
            return false;
        }

        //проверяем тему сообщения
        var s = $form.find('#ms-msg-subject');
        if(s.val()=='') {
            s.focus();
            return false;
        }

        //указан текст сообщения
        var b = $form.find('#ms-msg-body');
        if(b.val()=='') {
            b.focus();
            return false;
        }

        bff.ajax(url, $form.serialize(), function(data){
            if(data && data.success)
            {
                if(testing)
                {
                    $form.find('#ms-result').html('<div class="alert alert-info"><table class="admtbl tdbledit">\
                            <tr><td><b>Результат тестовой рассылки писем:</b></td><td width="150">&nbsp;</td></tr>\
                            <tr><td><span class="clr-success">Отправлено: </span></td><td><strong>'+data.success+'</strong></td></tr>\
                            <tr><td><span class="clr-error">Не отправлено:</span></td><td><strong>'+data.failed+'</strong></td></tr>\
                            <tr><td colspan="2"><hr/></td></tr>\
                            <tr><td>Среднее время отправки письма:</td><td><strong>'+data.time_avg+'сек.</strong></td></tr>\
                            <tr><td>Общее время отправки:</td><td><strong>'+data.time_total+'сек.</strong></td></tr>\
                        </table></div>');
                } else {
                   bff.success('Рассылка была успешно иницирована');
                }
            } else {
                if( ! testing)
                    bff.error('Возникла ошибка при формировании рассылки');
            }
        }, function(p){ $(btn).button((p?'loading':'reset')); processing = p; });

        return true;
    }

    function testMode(inp)
    {
        if(inp.checked) {
            $('#ms-test-settings').show();
            $testReceivers.focus();
        } else {
            $('#ms-test-settings').hide();
        }
    }

    return {
        init:init,
        testMode:testMode
    };
}());
</script>

<form method="post" action="" id="ms-form">
<table class="admtbl tbledit">    
<tr>
    <td class="row1" width="80"><span class="field-title">От</span>:</td>
    <td class="row2">
        <input type="text" name="from" id="ms-msg-from" style="width:250px;" value="<?= $noreply ?>" tabindex="1" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Тема</span>:</td>
	<td class="row2">
	    <input type="text" name="subject" id="ms-msg-subject" class="stretch" value="" tabindex="2" />
	</td>
</tr>
<tr>
	<td class="row1 field-title">Сообщение:
        <div class="desc">
            <hr />
            <a href="#" onclick="bff.textInsert($('#ms-msg-body').get(0), '{fio}'); return false;">{fio}</a> - ФИО
        </div>
    </td>
	<td class="row2"><textarea name="body" id="ms-msg-body" class="stretch" style="min-height: 150px; height:150px" tabindex="3"></textarea></td>
</tr>
<tr>
    <td class="row1"></td>
    <td class="row2">
        <label class="checkbox"><input type="checkbox" name="is_html" tabindex="4" />Текст сообщения содержит HTML теги: <span class="desc"><?= HTML::escape('<div>, <br>, <table>, <body>, <html>') ?></span></label>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Шаблон:</td>
    <td class="row2">
        <select name="wrapper_id" style="width: auto; min-width: 150px;"><?= $wrappers ?></select>
    </td>
</tr>

<tr>
    <td class="row1"></td>
    <td class="row2">
        <hr class="cut" />
        <label class="checkbox"><input type="checkbox" name="test" value="1" tabindex="5" id="ms-test" onclick="sendmailMassend.testMode(this);" />Тестовая рассылка</label>
        <div id="ms-test-settings" style="display:none;">
            <label>
                Укажите получателей для тестирования:<br />
                <input type="text" class="stretch" name="receivers_test" id="ms-receivers-test" />
            </label>
            <span class="desc">например: <a class="ajax desc ms-receivers-test-example" href="#">test@gmail.com</a>, <a class="ajax desc ms-receivers-test-example" href="#">123@yandex.ru</a></span>
        </div>
    </td>
</tr>
<tr>
    <td class="row1"></td>
    <td class="row2" id="ms-result"></td>
</tr>
<tr>
    <td class="row1"></td>
	<td class="row2">
        <input type="button" class="btn btn-success button submit" data-loading-text="Подождите..." value="Отправить" tabindex="5" onclick="return sendmailMassend.init(this);" />
	</td>                                                                                                             
</tr> 
</table>
</form>