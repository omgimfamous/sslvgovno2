<?php
    /**
     * Кабинет пользователя: Настройки
     * @var $this Users
     */
    Geo::mapsAPI(true);
    tpl::includeJS(array('qquploader'), true);
    tpl::includeJS('users.my', false, 2);
    $aData = HTML::escape($aData, 'html', array('email','phone_number','name','skype','icq','addr_addr','region_title'));
    $shopID = ( User::shopID() && ! BBS::publisher(BBS::PUBLISHER_USER) );
?>

<div class="u-cabinet__settings" id="j-my-settings">
    <? if($shopID): ?>
        <div class="u-cabinet__settings__block">
            <div class="u-cabinet__settings__block__title">
                <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="shop"><span><?= _t('users', 'Настройки магазина') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
            </div>
            <div class="u-cabinet__settings__block__content hide j-block j-block-shop">
                <?= Shops::i()->my_settings() ?>
            </div>
        </div>
    <? endif; # shop settings ?>
    <? if($on['contacts']) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="contacts"><span><?= _t('users', 'Контактные данные') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-contacts">
            <form class="form-horizontal j-form-contacts" action="">
            <input type="hidden" name="act" value="contacts" />
            <div class="control-group">
                <label class="control-label"><?= _t('users', 'Контактное лицо') ?><span class="required-mark">*</span></label>
                <div class="controls">
                    <input type="text" name="name" value="<?= $name ?>" class="input-block-level j-required" maxlength="50" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?= _t('users', 'Фотография') ?><br /><small><?= _t('users', 'Профили с личной фотографией пользуются большим доверием') ?></small></label>
                <div class="controls">
                    <div class="u-cabinet__settings__photo pull-left">
                        <a class="v-author__avatar" href="#" onclick="return false;">
                            <img alt="" class="img-circle" src="<?= $avatar_normal ?>" id="j-my-avatar-img" />
                        </a>
                    </div>
                    <div class="u-cabinet__settings__photo__download pull-left"><a class="ajax" id="j-my-avatar-upload" href="javascript:void(0);"><?= _t('users', 'загрузить фото') ?></a></div>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?= _t('users', 'Контакты') ?></label>
                <div class="controls">
                    <div class="i-formpage__contacts">
                        <div id="j-my-phones"></div>
                        <div class="i-formpage__contacts__item">
                            <div class="input-prepend">
                                <span class="add-on"><i class="ico ico__skype-dark"></i></span>
                                <input type="text" class="input-large" name="skype" value="<?= $skype ?>" placeholder="<?= _t('', 'Skype') ?>" maxlength="32" />
                            </div>
                        </div>
                        <div class="i-formpage__contacts__item i-formpage__contacts__item_last">
                            <div class="input-prepend">
                                <span class="add-on"><i class="ico ico__icq-dark"></i></span>
                                <input type="text" class="input-large" name="icq" value="<?= $icq ?>" placeholder="<?= _t('', 'ICQ') ?>" maxlength="20" pattern="[0-9]*" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="j-my-geo">
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Город') ?></label>
                    <div class="controls rel">
                        <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
                            'on_change' => 'jMySettings.onCitySelect',
                            'form' => 'users-settings',
                        )); ?>
                    </div>
                </div>
                <div id="j-my-geo-addr">
                    <div class="control-group">
                        <label class="control-label"><?= _t('users', 'Точный адрес') ?></label>
                        <div class="controls">
                            <input type="hidden" name="addr_lat" id="j-my-geo-addr-lat" value="<?= $addr_lat ?>" />
                            <input type="hidden" name="addr_lon" id="j-my-geo-addr-lon" value="<?= $addr_lon ?>" />
                            <input type="text" class="input-block-level" name="addr_addr" id="j-my-geo-addr-addr" value="<?= $addr_addr ?>" />
                        </div>
                    </div>
                    <div class="control-group i-formpage__map">
                        <div class="controls">
                            <div id="j-my-geo-addr-map" class="i-formpage__map_desktop" style="height: 250px; width: 100%; max-width: 470px;"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <input type="submit" class="btn btn-success" value="<?= _t('users', 'Сохранить') ?>" />
                </div>
            </div>
            </form>
        </div>
    </div>
    <? } # on['contacts'] ?>
    <? if( ! empty($social) ) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="social"><span><?= _t('users', 'Связь с социальными сетями') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-social">
            <form action="">
                <div class="u-sc">
                    <?= _t('users', 'Для ускорение процесса авторизации вы можете использовать <br />свои аккаунты в социальных сетях') ?>:
                    <br /><br />
                    <? foreach($social as $k=>$v) {

                        ?>
                            <a href="#" class="btn u-sc_<?= $v['class'] ?><? if( isset($v['user']) ){ ?> active<? } ?> j-my-social-btn" data="{provider:'<?= $v['key'] ?>',w:<?= $v['w'] ?>,h:<?= $v['h'] ?>}"><?= $v['title'] ?></a>
                        <?

                    } ?>
                </div>
            </form>
        </div>
    </div>
    <? } ?>
    <? if( ! empty($enotify) ) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="enotify"><span><?= _t('users', 'Настройка уведомлений') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-enotify">
            <form class="form-horizontal j-form-enotify" action="">
            <input type="hidden" name="act" value="enotify" />
            <div class="u-cabinet__settings__delivery">
                <? foreach($enotify as $k=>$v) { ?>
                    <label class="checkbox"><input type="checkbox" name="enotify[]" value="<?= $k ?>" <? if($v['a']) { ?> checked="checked"<? } ?> class="j-my-enotify-check" /><?= $v['title'] ?></label>
                <? } ?>
            </div>
            </form>
        </div>
    </div>
    <? } ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="pass"><span><?= _t('users', 'Изменить пароль') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-pass">
            <form class="form-horizontal j-form-pass" action="">
                <input type="hidden" name="act" value="pass" />
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Текущий пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" class="input j-required" name="pass0" maxlength="100" autocomplete="off" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Новый пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" class="input j-required" name="pass1" maxlength="100" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Новый пароль еще раз') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" class="input j-required" name="pass2" maxlength="100" />
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <input type="submit" class="btn btn-success" value="<?= _t('users', 'Изменить пароль') ?>" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    <? if($on['phone']) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="phone"><span><?= _t('users', 'Изменить номер телефона') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-phone">
            <form class="form-horizontal j-form-phone" action="">
                <input type="hidden" name="act" value="phone" />
                <input type="hidden" name="save" value="" />
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Ваш номер') ?>:</label>
                    <div class="controls">
                        <input type="text" class="input" name="phone0" value="<?= (!empty($phone_number) ? ''.$phone_number : _t('users', 'Не указан')) ?>" disabled="disabled" />
                        <? if($phone_number_verified && !empty($phone_number)) { ?>
                            <i class="fa fa-check text-success hidden-phone"></i>
                        <? } ?>
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Новый номер') ?><span class="required-mark">*</span></label>
                    <div class="controls j-phone-change-step1">
                        <div class="u-control-phone">
                            <?= $this->registerPhoneInput(array('name'=>'phone','autocomplete'=>'off')) ?>
                        </div>
                    </div>
                    <div class="controls j-phone-change-step2 hide">
                        <input type="text" name="code" class="input j-phone-change-code" placeholder="<?= HTML::escape(_t('users', 'Введите код из sms')) ?>" />
                        <div class="i-control-links">
                            <a href="#" class="ajax j-phone-change-repeate"><?= _t('users', 'Выслать код повторно') ?></a>
                            <a href="#" class="ajax j-phone-change-back"><?= _t('users', 'Изменить номер') ?></a>
                        </div>
                        <div class="alert alert-warning mrgb0 mrgt10 hide"></div>
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <input type="submit" class="btn btn-success" value="<?= _t('users', 'Изменить') ?>" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    <? } ?>
    <? if($on['email']) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="email"><span><?= _t('users', 'Изменить email-адрес') ?></span> <i class="fa fa-chevron-down j-icon"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-email">
            <form class="form-horizontal j-form-email" action="">
                <input type="hidden" name="act" value="email" />
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Ваш текущий email') ?>:</label>
                    <div class="controls">
                        <input type="email" class="input" name="email0" value="<?= $email ?>" disabled="disabled" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Новый email') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="email" class="input j-required" name="email" maxlength="100" />
                    </div>
                </div>
                <div class="control-group">
                    <label class="control-label"><?= _t('users', 'Текущий пароль') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="password" class="input j-required" name="pass" maxlength="100" autocomplete="off" />
                    </div>
                </div>
                <div class="control-group">
                    <div class="controls">
                        <input type="submit" class="btn btn-success" value="<?= _t('users', 'Изменить') ?>" />
                    </div>
                </div>
            </form>
        </div>
    </div>
    <? } ?>
    <? if($on['destroy']) { ?>
    <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title">
            <a href="javascript:void(0);" class="ajax ajax-ico j-block-toggler" data-block="destroy"><span><?= _t('users', 'Удалить учетную запись') ?></span> <i class="fa fa-chevron-down"></i></a>
        </div>
        <div class="u-cabinet__settings__block__content hide j-block j-block-destroy">
            <div class="u-cabinet__settings__delacc">
                <form class="form-horizontal j-form-destroy" action="">
                    <input type="hidden" name="act" value="destroy" />
                    <div class="control-group">
                        <div class="controls">
                            <?= _t('users', 'Вы можете удалить свою учетную запись, если больше не планируете пользоваться сайтом') ?>
                            <br /><br />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label"><?= _t('users', 'Текущий пароль') ?><span class="required-mark">*</span></label>
                        <div class="controls">
                            <input type="password" class="input j-required" name="pass" maxlength="100" autocomplete="off" />
                        </div>
                    </div>
                    <div class="control-group">
                        <div class="controls">
                            <input type="submit" class="btn btn-danger" value="<?= _t('users', 'Удалить учетную запись') ?>" />
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <? } ?>
</div>

<script type="text/javascript">
<? js::start() ?>
jMySettings.init(<?= func::php2js(array(
        'url_settings' => Users::url('my.settings'),
        'url_social' => Users::url('login.social'),
        'lang' => array(
            'saved_success' => _t('users', 'Настройки успешно сохранены'),
            'ava_upload_messages' => array(
                'typeError' => _t('users', 'Допустимы только следующие типы файлов: {extensions}'),
                'sizeError' => _t('users', 'Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
                'minSizeError' => _t('users', 'Файл {file} имеет некорректный размер'),
                'emptyError' => _t('users', 'Файл {file} имеет некорректный размер'),
                'onLeave' => _t('users', 'Происходит загрузка изоражения, если вы покинете эту страницу, загрузка будет прекращена'),
            ),
            'ava_upload' => _t('users', 'Загрузка фотографии'),
            'phones_tip' => _t('item-form', 'Номер телефона'),
            'phones_plus' => _t('item-form', '+ ещё<span [attr]> телефон</span>', array('attr'=>'class="hidden-phone"')),
            'pass_changed' => _t('users', 'Пароль был успешно изменен'),
            'pass_confirm' => _t('users', 'Ошибка подтверждения пароля'),
            'phone_changed' => _t('users', 'Номер телефона был успешно изменен'),
            'phone_code_sended' => _t('users', 'Код подтверждения был отправлен на указанный номер телефона'),
            'email_wrong' => _t('users', 'E-mail адрес указан некорректно'),
            'email_diff' => _t('users', 'E-mail адрес не должен совпадать с текущим'),
            'email_changed' => _t('users', 'E-mail адрес был успешно изменен'),
            'account_destoyed' => _t('users', 'Ваш аккаунт был успешно удален'),
        ),
        # avatar
        'avatarMaxsize' => $avatar_maxsize,
        'avatarSzSmall' => UsersAvatar::szSmall,
        'avatarSzNormal' => UsersAvatar::szNormal,
        # phones
        'phonesLimit' => Users::i()->profilePhonesLimit,
        'phonesData' => $phones,
        # tab
        'tab' => $tab,
        'uploadProgress'  => '<div class="align-center j-progress"   style="width: 64px;  height: 64px;  float: left;  line-height: 64px;"> <img alt="" src="'.SITEURL_STATIC.'/img/loading.gif"> </div>',
    )) ?>);
<? js::stop() ?>
</script>