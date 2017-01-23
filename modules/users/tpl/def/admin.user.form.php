<?php
    $enotify = Users::i()->getEnotifyTypes($enotify); # уведомления
    $aData = HTML::escape($aData, 'html', array('login', 'name', 'email', 'phone_number', 'surname', 'skype', 'icq', 'site', 'addr_addr'));
    $edit = $user_id > 0;
    $bNoShop = ( ! $shop_id );
?>

<? if($edit) {
    $aData['popup'] = false;
    echo $this->viewPHP($aData, 'admin.user.status');
} ?>

<div class="tabsBar">
    <script type="text/javascript">
        function jUserTab(key, link)
        {
            $('.tab-form').hide(); 
            $('#tab-'+key).show();
            if(key == 'shop' && typeof jShopInfo != 'undefined') {
                jShopInfo.onShow();
            }
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        }
    </script>
    <span class="tab tab-active"><a href="#" onclick="return jUserTab('profile', this);">Профиль</a></span>
    <? if($edit) { ?>
    <? if($shops_on) { ?><span class="tab"><a href="#" <? if($bNoShop){ ?> class="disabled" <? } ?> id="shop-form-tab" onclick="return jUserTab('shop', this);">Магазин</a></span><? } ?>
    <span class="tab"><a href="<?= $this->adminLink('listing&uid='.$user_id, 'bills'); ?>">Баланс <span class="desc">(<?= $balance ?>)</span></a></span>
    <div class="right">
        <div style="margin:0 0 0 10px;<? if( ! $activated) { ?> display: none;<? } ?>" class="left u_block_links">
            <a href="#" onclick="return jUserStatus.unblock(this);" class="u_unblock_lnk ajax clr-success <? if(!$blocked){ ?>hidden<? } ?>">разблокировать</a>
            <a href="#" onclick="return jUserStatus.block(this);" class="u_block_lnk ajax clr-error <? if($blocked){ ?>hidden<? } ?>">заблокировать</a>
        </div>
    </div>
    <? } ?>
    <div class="clear"></div>
</div>

<form action="" name="modifyUserForm" id="modifyUserForm" method="post" enctype="multipart/form-data">
<input type="hidden" name="shop_id" value="<?= $shop_id ?>" />
<table class="admtbl tbledit relative">
<tbody id="tab-profile" class="tab-form relative">
<tr>
    <td class="row1 field-title" style="width:150px;">ФИО:</td>
    <td class="row2">
        <input maxlength="50" type="text" name="name" id="user_name" value="<?= $name ?>" />
        <? if($edit && !empty($social)) { ?>
        <div style="display: inline-block; vertical-align: middle; margin: 2px 0 0 5px;">
            <? foreach ($social as $v):
                if (empty($v['profile_url'])) continue;
                ?><a href="<?= $v['profile_url'] ?>" class="social <?= $v['provider_key'] ?>" target="_blank"></a>&nbsp;<?
               endforeach; ?>
        </div>
        <? } ?>
        <div style="position: absolute; right: 5px; top: 5px; text-align: center;">
            <div style="margin-bottom: 5px;">
                <img id="avatar" src="<?= UsersAvatar::url($user_id, $avatar, UsersAvatar::szNormal); ?>" class="img-polaroid" alt="" />
            </div>
            <input type="hidden" name="avatar_del" id="avatar_delete_flag" value="0" />
            <? if($avatar && $edit) {?><a href="javascript:void(0);" id="avatar_delete_link" title="удалить текущий аватар" class="desc ajax" onclick="jUser.deleteAvatar('<?= UsersAvatar::url($user_id, '', UsersAvatar::szNormal) ?>');">удалить</a><? } ?>
        </div>
    </td>
</tr>
<tr style="display: none;">
    <td class="row1 field-title">Фамилия:</td>
    <td class="row2">
        <input maxlength="35" type="text" name="surname" id="user_surname" value="<?= $surname ?>" />
    </td>
</tr>
<? if(Users::registerPhone()){ ?>
<tr class="required">
	<td class="row1 field-title">Телефон<span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="text" name="phone_number" maxlength="50" pattern="[0-9+]*" value="<?= (!empty($phone_number) ? '+'.$phone_number : '') ?>" <? if(empty($phone_number)) { ?> placeholder="Не указан" <? } ?> autocomplete="off" />
    </td>
</tr>
<? } ?>
<tr class="required check-email">
	<td class="row1 field-title">E-mail<span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="text" id="email" name="email" maxlength="100" value="<?= $email ?>" autocomplete="off" />
    </td>
</tr>
<tr class="required">
    <td class="row1 field-title">Логин<span class="required-mark">*</span>:</td>
    <td class="row2">
        <input maxlength="35" type="text" name="login" id="user_login" value="<?= $login ?>" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="left field-title">Аватар:</span></td>
    <td class="row2"><input type="file" name="avatar" size="17" /></td>
</tr> 
<? if(!$edit){ ?>
<tr class="required check-password">
	<td class="row1 field-title">Пароль<span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="password" id="password" name="password" autocomplete="off" value="<?= $password ?>" maxlength="100" />
    </td>
</tr> 
<tr class="required check-password">
	<td class="row1 field-title">Подтверждение пароля<span class="required-mark">*</span>:</td>
	<td class="row2">
        <input type="password" id="password2" name="password2" class="check-password2" maxlength="100" autocomplete="off" value="<?= $password2 ?>" />
    </td>
</tr>
<? } else { ?>
<tr class="required check-password">
    <td class="row1">
        <span class="field-title">Пароль<span class="required-mark">*</span></span>:
        <input type="hidden" name="changepass" id="changepass" value="0" />
    </td>
    <td class="row2">
        <div id="passwordCurrent" style="height:17px; padding-top:5px;">
            <a href="#" class="ajax" onclick="jUser.doChangePassword(1); return false;">изменить пароль</a>
        </div>
        <div id="passwordChange" style="display:none; height:22px;">
            <input type="text" id="password" name="password" value="" maxlength="100" />
            &nbsp;&nbsp;<a href="#" class="ajax desc" onclick="jUser.doChangePassword(0); return false;">отмена</a>
        </div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Регистрация:</td>
    <td class="row2"><?= tpl::date_format2($created, true) ?>, <a class="desc" href="<?= $this->adminLink('ban') ?>"><?= long2ip($created_ip) ?></a></td>
</tr> 
<tr>
    <td class="row1 field-title">Авторизация:</td>
    <td class="row2">
        <?= tpl::date_format2($last_login,true); ?><span class="desc"> - последняя, <a class="bold desc" href="<?= $this->adminLink('ban') ?>"><?= long2ip($last_login_ip); ?></a></span>
        <? if($last_login2){ ?><br /><?= tpl::date_format2($last_login2,true); ?><span class="desc"> - предпоследняя</span><? } ?>
    </td>
</tr>
<? } ?>
<tr>
    <td class="row1 field-title">Город:</td>
    <td class="row2">
        <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
            'form' => 'users-settings'
        )); ?>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Точный адрес:</td>
    <td class="row2">
        <input type="text" name="addr_addr" value="<?= $addr_addr ?>" style="width: 300px;" maxlength="400" />
    </td>
</tr>
<?
if($this->profileBirthdate)
{
    $aData['birthdate'] = $this->getBirthdateOptions($aData['birthdate']); # дата рождения
?>
<tr>
    <td class="row1 field-title">Дата рождения:</td>
    <td class="row2">
        <select name="birthdate[day]" style="width:45px;"><?= $birthdate['days'] ?></select>
        <select name="birthdate[month]" style="width:90px;"><?= $birthdate['months'] ?></select>
        <select name="birthdate[year]" style="width:57px;"><?= $birthdate['years'] ?></select>
    </td>
</tr>
<? } ?>
<tr<? if( ! $this->profileSex) { ?> style="display: none;"<? } ?>>
    <td class="row1 field-title">Пол:</td>
    <td class="row2">
        <?
            $aSex = array(
                Users::SEX_FEMALE => 'Женщина',
                Users::SEX_MALE   => 'Мужчина',
            );
            foreach($aSex as $k=>$v) { ?><label class="radio inline"><input type="radio" name="sex" value="<?= $k ?>" <? if($sex == $k){ ?>checked="checked"<? } ?> /><?= $v ?></label><? } ?>
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Телефоны (контактные)</span>:</td>
    <td class="row2">
        <div id="j-user-phones"></div>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Skype:</td>
    <td class="row2">
        <input type="text" name="skype" value="<?= $skype ?>" maxlength="30" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">ICQ:</td>
    <td class="row2">
        <input type="text" name="icq" value="<?= $icq ?>" maxlength="20" />
    </td>
</tr>   
<tr style="display: none;">
    <td class="row1"><span class="field-title">Ссылка на сайт:</span><span class="right desc">http://</span></td>
    <td class="row2">
        <input type="text" class="stretch" name="site" value="<?= $site ?>" />
    </td>
</tr>
<tr style="display: none;">
    <td class="row1 field-title">О себе:</td>
    <td class="row2">
        <textarea class="stretch" name="about"><?= $about ?></textarea>
    </td>
</tr>
<tr>
    <td colspan="2"><hr class="cut" /></td>
</tr>
<? if($admin && bff::moduleExists('internalmail')) { ?>
<tr>
    <td class="row1"><span class="field-title">Сообщения:</span></td>
    <td class="row2"><label class="checkbox"><input type="checkbox" name="im_noreply"<? if($im_noreply){ ?> checked="checked"<? } ?> />пользователи не могут отвечать на его сообщения</label></td>
</tr>
<? } ?>
<tr>
    <td class="row1"><span class="field-title">Уведомления и подписка</span>:</td>
    <td class="row2">
        <? foreach($enotify as $k=>$v){ ?>
            <label class="checkbox"><input type="checkbox" name="enotify[]" value="<?= $k ?>" <? if($v['a']){ ?>checked="checked"<? } ?> /><?= $v['title'] ?></label>
        <? } ?>
    </td>
</tr>
<tr>
    <td class="row1 field-title">Принадлежность к группе:</td>
    <td class="row2">
        <table>
            <tr>
                <td width="240">
                    <strong>Группы пользователей:</strong><br />
                    <select multiple name="exists_values[]" id="exists_values" style="width:230px; height:100px;"><?= $exists_options ?></select>
                </td>
                <td width="35">
                     <div style="width:33px; height:12px;">&nbsp;</div>
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&gt;&gt;" onclick="bff.formSelects.MoveAll('exists_values', 'group_id');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&gt;" onclick="bff.formSelects.MoveSelect('exists_values', 'group_id');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&lt;" onclick="bff.formSelects.MoveSelect('group_id', 'exists_values');" />
                     <input type="button" class="btn btn-mini button" style="width: 25px; margin-bottom:2px;" value="&lt;&lt;" onclick="bff.formSelects.MoveAll('group_id', 'exists_values');" />
                </td>
                <td width="240">
                    <strong>Активные группы:</strong><br />
                    <select multiple name="group_id[]" id="group_id" style="width:230px; height:100px;"><?= $active_options ?></select>
                </td>
               	<td>&nbsp;</td>
            </tr>
        </table>
    </td>
</tr>
</tbody>

<? if($shops_on) { ?>
<tbody id="tab-shop" style="display: none;" class="tab-form">
<tr><td colspan="2">
<? if($bNoShop) { ?>
    <div class="desc" style="margin: 15px 3px;" id="shop-form-add">Магазин еще не создан, <a href="<?= $this->adminLink('add&user='.$user_id, 'shops') ?>" target="_blank">создать</a>.</div>
<? } else { ?>
    <table id="shop-form" class="admtbl tbledit">
        <?= $shop_form ?>
    </table>
<? } ?>
</td></tr>
</tbody>
<? } ?>

<tr class="footer">
    <td colspan="2">
        <hr style="margin: 0 0 10px 0;" />
        <div class="left">
            <input type="hidden" name="back" class="j-back" value="0" />
            <div class="btn-group">
                <input type="submit" class="btn btn-success button submit j-submit" value="<?= ($edit ? 'Сохранить' : 'Создать') ?>" data-loading-text="<?= ($edit ? 'Сохранить' : 'Создать') ?>" />
                <? if($edit) { ?>
                    <input type="submit" class="btn btn-success button submit j-submit" value="и вернуться" data-loading-text="и вернуться" onclick="$('#modifyUserForm').find('.j-back').val(1);" />
                <? } ?>
            </div>
            <? if($edit && $session_id && ! $superadmin) { ?>
            <input type="button" class="btn clr-error button" value="Разлогинить" onclick="bff.confirm('sure', {r:'<?= $this->adminLink('user_action&type=logout&rec='.$user_id.'&tuid='.$tuid) ?>'});" />
            <? } ?>
            <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
        </div>
        <div class="right">
            <? if (!empty($admin_auth_url)): ?>
                <a href="<?= $admin_auth_url ?>" class="btn" target="_blank">Авторизоваться</a>
            <? endif; ?>
        </div>
        <div class="clear"></div>
    </td>
</tr>
</table>
</form>

<script type="text/javascript">
//<![CDATA[ 
var jUser = (function(){
    var $form;
    $(function(){
        $form = $('#modifyUserForm');
        bff.iframeSubmit($form, function(data){
            if(data && data.success) {
                if (data.hasOwnProperty('redirect')) {
                    bff.redirect('<?= tpl::adminLink('listing'); ?>');
                } else if(data.reload) {
                    setTimeout(function(){ location.reload(); }, 1000);
                } else if(data.back) {
                    history.back();
                } else {
                    bff.success('Данные успешно сохранены');
                }
            }
        },{
            beforeSubmit: function(){
                //check groups
                if( document.getElementById('group_id').options.length == 0 ) {
                    bff.error('укажите <strong>принадлежность к группе</strong>');
                    return false;
                }
                bff.formSelects.SelectAll('group_id');
                return true;
            },
            button: '.j-submit'
        });

        initPhones(<?= $this->profilePhonesLimit ?>, <?= func::php2js($phones) ?>);
    });

    function initPhones(limit, phones)
    {
        var index  = 0, total = 0;
        var $block = $('#j-user-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            $block.append('<div class="j-phone">\
                                <input type="text" maxlength="40" name="phones['+index+']" value="'+(value?value.replace(/"/g, "&quot;"):'')+'" class="left j-value" placeholder="Номер телефона" />\
                                <div class="left" style="margin: 3px 0 0 4px;">'+(total==1 ? '<a class="ajax desc j-plus" href="#">+ еще телефон</a>' : '<a href="#" class="but cross j-remove"></a>')+'</div>\
                                <div class="clear"></div>\
                            </div>');
        }

        $block.on('click', 'a.j-plus', function(e){ nothing(e);
            add('');
        });

        $block.on('click', 'a.j-remove', function(e){ nothing(e);
            var $ph = $(this).closest('.j-phone');
            if( $ph.find('.j-value').val() != '' ) {
                if(confirm('Удалить телефон?')) {
                    $ph.remove(); total--;
                }
            } else {
                $ph.remove(); total--;
            }
        });

        phones = phones || {};
        for(var i in phones) {
            if( phones.hasOwnProperty(i) ) {
                add(phones[i].v);
            }
        }
        if( ! total ) {
            add('');
        }
    }

    return {
        deleteAvatar: function(defaultAvatar)
        {
            if(confirm('Удалить текущий аватар?')) {
                $('#avatar').attr('src', defaultAvatar);
                $('#avatar_delete_flag').val(1);
                $('#avatar_delete_link').remove();
            }
            return false;
        },
        doChangePassword: function(change)
        {
            $('#passwordCurrent, #passwordChange').toggle();
            $('#changepass').val( change );

            if(change)
                $('#password').focus();
                
            return false;
        }
    };
}());
//]]>
</script>