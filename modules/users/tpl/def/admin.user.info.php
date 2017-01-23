<?php
/**
 * Просмотр краткой информации о пользователе (popup)
 * @var $this Users
 */
?>
<div id="j-users-userinfo-popup" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Информация о пользователе</div>
        <div class="ipopup-content" style="width:643px;">
                    <?
                        $aData['popup'] = true;
                        echo $this->viewPHP($aData, 'admin.user.status');
                    ?>
                    <div style="min-height:275px;">
                    
                    <table class="admtbl tbledit">
                        <tr>
                            <th width="115" style="height: 1px;"></th>
                            <th width="20" style="height: 1px;"></th>
                            <th style="height: 1px;"></th>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">Пользователь:</td>
                            <td></td>
                            <td class="relative">
                                <?= ( ! empty($name) ? $name . ( ! empty($login) ? ' ['.$login.']' : '') : $login) ?>
                                <div style="display: inline-block; vertical-align: middle; margin-left: 5px;">
                                <? if( ! empty($social) ) {
                                    foreach ($social as $v) {
                                        if (empty($v['profile_url'])) continue;
                                        ?><a href="<?= $v['profile_url'] ?>" class="social <?= $v['provider_key'] ?>" target="_blank"></a>&nbsp;<?
                                    }
                                } ?>
                                </div>
                                <div style="text-align: center; position: absolute; right: 5px; top: 5px;">
                                    <div style="margin-bottom: 5px;"><img src="<?= UsersAvatar::url($user_id, $avatar, UsersAvatar::szNormal, $sex) ?>" class="img-polaroid" alt="" /></div>
                                    <a href="#" class="text-error j-act-block<? if($blocked) { ?> hidden<? } ?>" onclick="jUserStatusPopup.block(); return false;">заблокировать</a>
                                    <a href="#" class="text-success j-act-unblock<? if(!$blocked) { ?> hidden<? } ?>" onclick="jUserStatusPopup.unblock(); return false;">разблокировать</a>
                                </div>
                            </td>
                        </tr>
                        <? if($shops_on) { ?>
                        <tr>
                            <td class="row1 field-title right">Магазин:</td>
                            <td></td>
                            <td>
                                <? if($shop_id > 0) { ?>
                                    <a href="<?= $shop['link'] ?>" target="_blank" class="but linkout"></a><a href="#" onclick="return bff.shopInfo(<?= $shop_id ?>);" class="ajax"><?= $shop['title'] ?></a>
                                <? } else { ?>
                                    <span class="desc">нет,&nbsp;</span><a href="<?= $this->adminLink('add&user='.$user_id, 'shops') ?>" class="desc">(открыть магазин)</a>
                                <? } ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr>
                            <td class="row1 field-title right">Счет:</td>
                            <td></td>
                            <td><a class="bold" href="<?= $this->adminLink('listing&uid='.$user_id, 'bills'); ?>"><?= $balance ?></a></td>
                        </tr>
                        <? if(Users::registerPhone()){ ?>
                        <tr>
                            <td class="row1 field-title right">Телефон:</td>
                            <td></td>
                            <td>
                                <? if(!empty($phone_number)) { ?>
                                    <strong>+<?= $phone_number ?></strong>
                                    <? if(!empty($phone_number_verified)) { ?>
                                        <i class="icon-ok disabled" style="margin-top:-2px; opacity: 0.2;" title="подтвержден"></i>
                                    <? } ?>
                                <? } else { ?>
                                    <span class="desc">Не указан</span>
                                <? } ?>
                            </td>
                        </tr>
                        <? } ?>
                        <tr>
                            <td class="row1 field-title right">Email:</td>
                            <td></td>
                            <td><a href="mailto:<?= $email ?>"><?= $email ?></a></td>
                        </tr> 
                        <tr>
                            <td class="row1 field-title right">Регистрация:</td>
                            <td></td>
                            <td><?= tpl::date_format2($created, true) ?>, <a class="desc" href="<?= $this->adminLink('ban','users'); ?>"><?= long2ip($created_ip) ?></a></td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">Авторизация:</td>
                            <td></td>
                            <td>
                                <div>
                                <? if( $last_login == '0000-00-00 00:00:00' ) { ?>-<? } else { ?>
                                <?= tpl::date_format2($last_login, true) ?><span class="desc"> - последнее, <a class="bold desc" href="<?= $this->adminLink('ban', 'users'); ?>"><?= long2ip($last_login_ip) ?></a></span>
                                <? if($last_login2){ ?><br /><?= tpl::date_format2($last_login2, true); ?><span class="desc"> - предпоследнее</span><? } ?>
                                <? } ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">Город:</td>
                            <td></td>
                            <td>
                                <? if($region_id>0){ ?><a href="<?= $this->adminLink('listing&region='.$region_id); ?>"><?= $region_title ?></a><? } else { ?><span class="desc">не указан</span><? } ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">ICQ:</td>
                            <td></td>
                            <td><?= ( ! empty($icq) ? $icq : '-' ) ?></td>
                        </tr> 
                        <tr>
                            <td class="row1 field-title right">Skype:</td>
                            <td></td>
                            <td><? if($skype){ ?><a href="skype:<?= $skype ?>?chat"><?= $skype ?></a><? } else { ?>-<? } ?></td>
                        </tr>
                        <tr>
                            <td class="row1 field-title right">Телефоны:</td>
                            <td></td>
                            <td><?
                                if( ! empty($phones) ) {
                                    $phonesView = array();
                                    foreach($phones as $ph){ $phonesView[] = $ph['v']; }
                                    echo join(', ', $phonesView);
                                } else {
                                    echo '-';
                                }
                                ?></td>
                        </tr>
                    </table>

                    <? if($im_form) { ?>
                    <script type="text/javascript"> 
                    //<![CDATA[
                    $(function(){
                        var $popup = $('#j-users-userinfo-popup'), _process = false;
                        var $block = $popup.find('#j-users-userinfo-im-block'),
                            $success = $popup.find('#j-users-userinfo-im-success'),
                            $form = $block.find('.j-form'),
                            $message = $block.find('.j-message');

                        $popup.on('click', '#j-users-userinfo-im-toggle', function(e){ nothing(e);
                            $block.show(0, function(){
                                $.fancybox.resize();
                                $message.focus();
                            });
                        });
                        $form.on('click', '.j-btn-send', function(e){ nothing(e);
                            if(_process) return;
                            if( $message.val().trim() == '' ){
                                $message.focus();
                                return;
                            }
                            bff.ajax('<?= $this->adminLink('listing','internalmail') ?>', $form.serialize(), function(data){
                                if(data) {
                                    $form.get(0).reset();
                                    $block.slideUp();
                                    $success.slideDown();
                                    setTimeout(function(){ $success.slideUp('fast'); }, 5000);
                                }
                            }, function(p){ _process = p; });
                        });
                        $form.on('click', '.j-btn-cancel', function(e){ nothing(e);
                            $block.hide(0, function(){ $.fancybox.resize(); });
                        });
                    });
                    //]]> 
                    </script>   
                    
                    <div class="well well-small" id="j-users-userinfo-im-block" style="display:none;">
                        <form action="" method="post" class="j-form">
                            <input type="hidden" name="act" value="send" />
                            <input type="hidden" name="recipient" value="<?= $user_id ?>" />
                            <div><b>Отправить сообщение:</b></div>
                            <textarea name="message" class="autogrow j-message" style="height:90px; min-height:90px;"></textarea>
                            <a class="btn btn-mini btn-success j-btn-send" href="#">отправить</a>
                            <a class="btn btn-mini j-btn-cancel" href="#">отмена</a>
                        </form>
                    </div> 
                    <div class="alert alert-success" id="j-users-userinfo-im-success" style="display:none;">
                        Сообщение было успешно <a href="<?= $this->adminLink('conv&i='.$user_id, 'internalmail'); ?>">отправлено</a>
                    </div>
                    <? } ?> 
                    
                    </div>

                <div class="ipopup-content-bottom">
                    <ul class="right">
                        <? if($im_form){ ?><li><a href="#" class="edit_s ajax" id="j-users-userinfo-im-toggle">написать сообщение</a></li><? } ?>
                        <li><span class="post-date" title="дата регистрации"><?= tpl::date_format2($created, true) ?></span></li>
                        <li><a href="<?= $this->adminLink('listing&status=7&uid='.$user_id, 'bbs') ?>"> объявления </a></li>
                        <li><a href="<?= $this->adminLink('user_edit&rec='.$user_id.'&tuid='.$tuid) ?>"> редактировать <span style="display:inline;" class="desc">#<?= $user_id ?></span></a></li>
                    </ul> 
                </div>
        
        </div>
    </div>
</div>