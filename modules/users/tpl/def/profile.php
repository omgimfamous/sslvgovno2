<?php
    /**
     * Профиль пользователя (layout)
     * @var $this Users
     * @var $user array
     */
?>
<div class="row-fluid">
    <div class="l-page l-page_full l-page_full-left v-page span12">
        <div class="v-page__content">

            <? if(DEVICE_PHONE): ?>
            <div class="visible-phone">
                <div class="v-author">
                    <a href="<?= $user['profile_link'] ?>" class="v-author__avatar">
                        <img src="<?= $user['avatar'] ?>" class="img-circle" alt="" />
                    </a>
                    <div class="v-author__info">
                        <span><?= $user['name'] ?></span><br />
                        <? if($user['region_id']){ ?><small><?= $user['region_title'] ?></small><br /><? } ?>
                        <small><?= _t('users', 'на сайте с [date]', array('date'=>tpl::date_format2($user['created']))) ?></small>
                    </div>
                    <div class="clearfix"></div>
                    <? if($user['has_contacts']): ?>
                    <div class="v-author__contact">
                        <div class="v-author__contact__title"><span><?= _t('users', 'Контакты') ?>:</span> <a href="#" class="ajax j-user-profile-c-toggler"><?= _t('users', 'показать контакты') ?></a></div>
                        <? if( ! empty($user['phones']) ): ?>
                        <div class="v-author__contact_items">
                            <div class="v-author__contact_title"><?= _t('users', 'Тел.') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-phones">
                                <? foreach($user['phones'] as $v): ?>
                                    <span class="hide-tail"><?= $v['m'] ?></span>
                                <? endforeach; ?>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # phones ?>
                        <? if( ! empty($user['skype']) || ! empty($user['icq']) ): ?>
                        <div class="v-author__contact_items">
                            <? if( ! empty($user['skype']) ): ?>
                            <div class="v-author__contact_title"><?= _t('', 'Skype') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-skype">
                                <span class="hide-tail"><?= $user['skype'] ?></span>
                            </div>
                            <? endif; ?>
                            <? if( ! empty($user['icq']) ): ?>
                            <div class="v-author__contact_title"><?= _t('', 'ICQ') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-icq">
                                <span class="hide-tail"><?= $user['icq'] ?></span>
                            </div>
                            <? endif; ?>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; ?>
                    </div>
                    <? endif; ?>
                    <? if($is_owner): ?>
                    <div class="v-author__contact_write">
                        <a href="<?= Users::url('my.settings', array('t'=>'contacts')) ?>" class="ico"><i class="fa fa-edit"></i> <span><?= _t('users', 'Редактировать') ?></span></a>
                    </div>
                    <? endif; # is_owner ?>
                </div>
            </div>
            <div class="clearfix"></div>
            <? endif; # DEVICE_PHONE ?>

            <div class="l-main l-main_maxtablet">
                <div class="l-main__content">
                    <?= $content ?>
                </div>
            </div>

            <? if(DEVICE_DESKTOP_OR_TABLET): ?>
            <div class="l-right hidden-phone">
                <div class="v-author">
                    <a href="<?= $user['profile_link'] ?>" class="v-author__avatar">
                        <img src="<?= $user['avatar'] ?>" class="img-circle" alt="" />
                    </a>
                    <div class="v-author__info">
                        <span><?= $user['name'] ?></span><br />
                        <? if($user['region_id']){ ?><small><?= $user['region_title'] ?></small><br /><? } ?>
                        <small><?= _t('users', 'на сайте с [date]', array('date'=>tpl::date_format2($user['created']))) ?></small>
                    </div>
                    <div class="clearfix"></div>
                    <? if($user['has_contacts']): ?>
                    <div class="v-author__contact">
                        <div class="v-author__contact__title"><span><?= _t('users', 'Контакты') ?>:</span> <a href="#" class="ajax j-user-profile-c-toggler"><?= _t('users', 'показать контакты') ?></a></div>
                        <? if( ! empty($user['phones']) ): ?>
                        <div class="v-author__contact_items">
                            <div class="v-author__contact_title"><?= _t('users', 'Тел.') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-phones">
                                <? foreach($user['phones'] as $v): ?>
                                    <span class="hide-tail"><?= $v['m'] ?></span>
                                <? endforeach; ?>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # phones ?>
                        <? if( ! empty($user['skype']) || ! empty($user['icq']) ): ?>
                        <div class="v-author__contact_items">
                            <? if( ! empty($user['skype']) ): ?>
                            <div class="v-author__contact_title"><?= _t('', 'Skype') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-skype">
                                <span class="hide-tail"><?= $user['skype'] ?></span>
                            </div>
                            <? endif; # skype ?>
                            <? if( ! empty($user['icq']) ): ?>
                            <div class="v-author__contact_title"><?= _t('', 'ICQ') ?></div>
                            <div class="v-author__contact_content j-user-profile-c-icq">
                                <span class="hide-tail"><?= $user['icq'] ?></span>
                            </div>
                            <? endif; # icq ?>
                            <div class="clearfix"></div>
                        </div>
                        <? endif; # skype, icq ?>
                    </div>
                    <? endif; # $user['has_contacts'] ?>
                    <? if($is_owner): ?>
                    <div class="v-author__contact_write">
                        <a href="<?= Users::url('my.settings', array('t'=>'contacts')) ?>" class="ico"><i class="fa fa-edit"></i> <span><?= _t('users', 'Редактировать') ?></span></a>
                    </div>
                    <? endif; # is_owner ?>
                </div>
                <? # Баннер: справа - пользователь (right_user) ?>
                <? if ($bannerRight = Banners::view('users_profile_right')): ?>
                <div class="l-banner banner-right">
                    <div class="l-banner__content">
                        <?= $bannerRight ?>
                    </div>
                </div>
                <? endif; ?>
            </div>
            <div class="clearfix"></div>
            <? endif; # DEVICE_DESKTOP_OR_TABLET ?>

        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        var _process = false;
        $('.j-user-profile-c-toggler').on('click touchstart', function(e){
            nothing(e); if(_process) return;
            var $link = $(this);
            bff.ajax(bff.ajaxURL('users','user-contacts'), {hash:app.csrf_token, ex:'<?= $user['user_id_ex'] ?>-<?= $user['user_id'] ?>'},
                function(data, errors) {
                    if(data && data.success) {
                        var keys = ['phones','skype','icq'];
                        for(var i in keys) { var k = keys[i];
                            if(data.hasOwnProperty(k)) $('.j-user-profile-c-'+k).html(data[k]);
                        }
                        $link.remove();
                    } else {
                        app.alert.error(errors);
                    }
                }, function(p){ _process = p; }
            );
        });
    });
<? js::stop(); ?>
</script>