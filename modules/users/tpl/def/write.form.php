<?php
    /**
     * Форма отправки сообщения
     * @var $this Users
     */
    tpl::includeJS('users.write.form', false, 1);
?>
<? if( ! User::id() ) { ?><input type="email" name="email" class="j-required j-email" value="" placeholder="<?= _t('users', 'Ваш email-адрес') ?>" maxlength="100" /><? } ?>
<textarea name="message" class="j-required j-message" placeholder="<?= _t('users', 'Текст вашего сообщения') ?>" autocapitalize="off"></textarea>
<? if( InternalMail::attachmentsEnabled() ) { ?>
<div class="v-descr_contact__form_file attach-file j-attach-block pull-left">
    <div class="upload-btn j-upload">
        <span class="upload-mask">
            <input type="file" name="attach" class="j-upload-file" />
        </span>
        <a href="#" onclick="return false;" class="ajax"><?= _t('users', 'Прикрепить файл') ?></a>
    </div>
    <div class="j-cancel hide">
        <span class="j-cancel-filename"></span>
        <a href="#" class="ajax pseudo-link-ajax ajax-ico j-cancel-link"><i class="fa fa-times"></i> <?= _t('users', 'Удалить') ?></a>
    </div>
</div>
<? } ?>
<button type="submit" class="btn btn-info j-submit"><i class="fa fa-envelope white"></i> <?= _t('users', 'Отправить') ?></button>
<div class="clearfix"></div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUsersWriteForm.init(<?= func::php2js(array(
            'lang'=>array(
                'email' => _t('','E-mail адрес указан некорректно'),
                'message' => _t('users','Сообщение слишком короткое'),
                'success' => _t('users','Сообщение было успешно отправлено'),
            ),
            'form_id' => '#'.$form_id,
        )) ?>);
    });
<? js::stop(); ?>
</script>