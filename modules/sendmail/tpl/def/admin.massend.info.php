<?php

?>
<div id="popupMassend" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Информация о рассылке</div>
        <div class="ipopup-content" style="width:643px;">

                <table class="admtbl tbledit">
                    <tr>
                        <td class="row1 field-title right" width="150">От:</td>
                        <td class="row2" style="padding-left:20px;"><?= $from ?></td>
                    </tr>            
                    <tr>
                        <td class="row1 field-title right">Тема:</td>
                        <td class="row2" style="padding-left:20px;"><?= $subject ?></td>
                    </tr>  
                    <tr>
                        <td class="row1 field-title right" style="vertical-align:top;">Сообщение:</td>
                        <td class="row2" style="padding-left:20px;"><?= $body ?></td>
                    </tr>                  
                    <tr>
                        <td class="row1 field-title right">Всего получателей:</td>
                        <td class="row2" style="padding-left:20px;"><?= $total ?></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right">Отправлено:</td>
                        <td class="row2" style="padding-left:20px;"><span class="clr-success"><?= $success ?></span><span class="desc"> / </span><span class="clr-error"><?= $fail ?></span></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right">Начало рассылки:</td>
                        <td class="row2" style="padding-left:20px;"><?= tpl::date_format3($started, 'd.m.Y H:i') ?></td>
                    </tr>
                    <tr>
                        <td class="row1 field-title right">Окончание рассылки:</td>
                        <td class="row2" style="padding-left:20px;"><strong><? if($status){ echo tpl::date_format3($finished, 'd.m.Y H:i'); } else{ ?>незавершена<? } ?></strong></td>
                    </tr> 
                     
                    <tr>
                        <td class="row1 field-title right">Время отправки:</td>
                        <td class="row2" style="padding-left:20px;"><?= $time_total ?> сек. <span class="desc">(<?= $time_avg ?> сек. - среднее)</span></td>
                    </tr>       
                </table>

        </div>
    </div>
</div>
