<?php
    /**
     * Необходимыые данные ОБ:
     * id, status, status_changed, moderated, publicated, publicated_to, publicated_order
     * svc, blocked_reason
     *
     * Необходимыые данные пользователя:
     * user['blocked']
     */

    /**
     * @var $this BBS
     */

    $blocked = ($status == BBS::STATUS_BLOCKED);
    $is_popup = ! empty($is_popup);
?>

<script type="text/javascript">
var jItemStatus = (function(){
    var $block, $buttons, $progress, url = '<?= $this->adminLink('ajax&act=', 'bbs'); ?>';
    var $blocked_reason;
    var data = {id: <?= $id ?>, blocked: <?= ($blocked ? 1 : 0) ?>};
    $(function(){
        $block = $('#j-i-status-block');
        $progress = $block.find('.j-i-status-progress');
        $blocked_reason = $block.find('.j-i-blocked-reason');
        $buttons = $block.find('.j-i-status-buttons');
    });

    return {
        activate: function(){
            bff.ajax(url+'item-activate', data, function(resp){
                if(resp && resp.success) {
                   location.reload();
                }
            });
        },
        approve: function(){
            bff.ajax(url+'item-approve', data, function(resp){
                if(resp && resp.success) {
                   location.reload();
                }
            }, $progress);
        },
        unpublicate: function(){
            if( ! bff.confirm('sure')) return;
            bff.ajax(url+'item-unpublicate', data, function(resp){
                if(resp && resp.success) {
                    location.reload();
                }
            }, $progress);
        },
        refresh: function(step){
            var $blockRefresh = $block.find('#i_refresh');
            switch(step)
            {
                case 0: { // показываем форму продления
                    $blockRefresh.show();
                    $buttons.hide();
                } break;
                case 1: { // сохранить
                    bff.ajax(url+'item-refresh', data, function(resp){
                        if(resp && resp.success) {
                            location.reload();
                        }
                    }, $progress);
                } break;
                case 2: { // отмена
                    $blockRefresh.hide();
                    $buttons.show();
                } break;
            }
            return false;
        },
        changeBlocked: function(step, block)
        {
            switch(step)
            {
                case 1: { // заблокировать / изменить блокировку
                    $block.find('#i_blocked').hide();
                    $block.find('#i_blocked_error, #i_blocking').show(0, function(){
                        $blocked_reason.focus();
                        $buttons.hide();
                    });
                } break;
                case 2: { // отменить
                    if(data.blocked == 1) {
                        $block.find('#i_blocking').hide();
                        $block.find('#i_blocked').show();
                    } else {
                        $block.find('#i_blocked_error').hide();
                    }
                    $buttons.show();
                } break;
                case 3: { // сохранить
                    data.blocked_reason = $blocked_reason.val();
                    bff.ajax(url+'item-block', data, function(resp){
                        if(resp && resp.success)
                        {
                            data.blocked = resp.blocked;
                            if( ! block) {
                                $block.find('#i_blocked_error').hide();
                            } else {
                                data.blocked_reason = resp.reason;
                                $blocked_reason.val( resp.reason );
                                $block.find('#i_blocked_text').html( resp.reason );
                                $block.find('#i_blocked_error').show();
                                jItemStatus.changeBlocked(2);
                            }
                        }
                    }, $progress);
                } break;
                case 4: { // разблокировать
                    if( ! bff.confirm('sure')) break;
                    bff.ajax(url+'item-approve', data, function(resp){
                        if(resp && resp.success) {
                            location.reload();
                        }
                    }, $progress);
                } break;
            }
            return false;
        }
    };
}());
</script>

<div class="<? if( ! $is_popup ) { ?>well well-small<? } ?>" id="j-i-status-block">
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title<? if($is_popup) { ?> right<? } ?>" style="width:<?= ( $is_popup ? 133 : 105 ) ?>px;">Статус:</td>
            <td class="row2"><strong><?
                if( $user['blocked'] ) {
                    ?>Аккаунт пользователя был заблокирован<?
                } else {
                    switch($status) {
                        case BBS::STATUS_NOTACTIVATED: { echo 'Неактивировано'; } break;
                        case BBS::STATUS_PUBLICATED: { echo 'Публикуется'; } break;
                        case BBS::STATUS_PUBLICATED_OUT: { echo 'Период публикации завершился'; } break;
                        case BBS::STATUS_BLOCKED: { echo ($moderated == 0?'Ожидает проверки (было заблокировано)':'Заблокировано'); } break;
                    }
                }
            ?></strong><? if($status_changed != '0000-00-00 00:00:00'){ ?>&nbsp;&nbsp;<span class="desc">(<?= tpl::date_format2($status_changed, true, true); ?>)</span><? } ?></td>
        </tr>
        <? if ($status == BBS::STATUS_NOTACTIVATED) { ?>
        <tr>
            <td class="row1"></td>
            <td class="row2">
                <input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.activate();" value="активировать" />
            </td>
        </tr>
        <? } ?>
        <? if ($status != BBS::STATUS_NOTACTIVATED && ! $user['blocked'] ) { ?>
        <tr>
            <td class="row1 field-title<? if($is_popup) { ?> right<? } ?>">Период:</td>
            <td class="row2"<? if($status == BBS::STATUS_PUBLICATED_OUT){ ?> class="desc" <? } ?>><b><?= tpl::date_format3($publicated, 'd.m.Y').' - '.tpl::date_format3($publicated_to, 'd.m.Y'); ?></b></td>
        </tr>
        <tr>
            <td class="row1" colspan="2">
                <div class="alert alert-danger <?= (!$blocked ? 'hidden':'') ?>" id="i_blocked_error">
                    <div>Причина блокировки: <div class="right desc" id="i_blocked_reason_warn" style="display:none;"></div></div>
                    <div class="clear"></div>
                    <div id="i_blocked">
                        <span id="i_blocked_text"><?= (!empty($blocked_reason) ? $blocked_reason :'?') ?></span> - <a href="#" onclick="jItemStatus.changeBlocked(1,0); return false;" class="ajax desc">изменить</a>
                    </div>
                    <div id="i_blocking" style="display: none;">
                        <textarea name="blocked_reason" class="autogrow j-i-blocked-reason" style="height:60px; min-height:60px;"><?= $blocked_reason; ?></textarea>
                        <a onclick="return jItemStatus.changeBlocked(3, 1);" class="btn btn-mini btn-success" href="#"><?= (!$blocked ? 'продолжить':'изменить причину') ?></a>
                        <? if($blocked) { ?><a onclick="return jItemStatus.changeBlocked(4);" class="btn btn-mini btn-success" href="#"><?= 'разблокировать' ?></a><? } ?>
                        <a onclick="return jItemStatus.changeBlocked(2);" class="btn btn-mini" href="#">отмена</a>
                    </div>
                </div>
                <div class="hidden alert alert-info" id="i_refresh">
                    <table class="admtbl tbledit">
                        <tr class="row1">
                            <td>
                                Выполнить продление публикации объявления до:<br /> <b>
                                <?
                                    $nRefreshPeriod = $this->getItemRefreshPeriod(
                                        ( $status === BBS::STATUS_PUBLICATED ? $publicated_to : BFF_NOW )
                                    );
                                    echo tpl::date_format2($nRefreshPeriod, true);
                                ?></b>
                            </td>
                        </tr>
                        <tr class="row1">
                            <td>
                                <a onclick="return jItemStatus.refresh(1);" class="btn btn-mini btn-success" href="#">продлить</a>
                                <a onclick="return jItemStatus.refresh(2);" class="btn btn-mini" href="#">отменить</a>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
        <? if( $deleted ) { ?>
        <tr>
            <td class="row1"></td>
            <td class="row2">
               <span class="clr-error bold">Удалено пользователем</span>
            </td>
        </tr>
        <? } elseif( ! ($moderated==1 && $blocked) ) { ?>
        <tr class="j-i-status-buttons">
            <td class="row1" colspan="2" <? if( $is_popup ) { ?> style="padding-left: 20px;" <? } ?>>
               <?
               if ($moderated == 0) { ?>
                    <input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.approve();" value="<?= ($blocked ? 'проверено, все впорядке' : 'проверено') ?>" />
               <? } else {
                   if ( $moderated == 2 ) {
                        ?><input class="btn btn-mini btn-success success button" type="button" onclick="jItemStatus.approve();" value="<?= ($blocked ? 'проверено, все впорядке' : 'проверено') ?>" /> <?
                   }
                   if ($status == BBS::STATUS_PUBLICATED_OUT) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jItemStatus.refresh(0);" value="опубликовать" /> <?
                   } else if ($status == BBS::STATUS_PUBLICATED) {
                        ?><input class="btn btn-mini submit button" type="button" onclick="jItemStatus.unpublicate();" value="снять с публикации" /> <?
                   }
               }
               if ( ! $blocked) { ?>
                   <a class="btn btn-mini text-error" onclick="jItemStatus.changeBlocked(1); return false;" id="i_block_lnk">заблокировать</a>
               <? } ?>
               <div class="progress j-i-status-progress" style="margin: 8px 8px 0; display: none;"></div>
            </td>
        </tr>
        <? }
        } # endif: ($status != BBS::STATUS_NOTACTIVATED  && ! $user['blocked']) ?>
    </table>
</div>