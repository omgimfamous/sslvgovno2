<p>
<?= _t('users', 'На указанный Вами e-mail отправлено письмо.') ?><br />
<?= _t('users', 'Пожалуйста, перейдите по ссылке из письма для подтверждения указанного электронного адреса.') ?><br />
<?= _t('users', 'На этом регистрация будет завершена.') ?>
</p>
<? if($retry_allowed) { ?>
<p id="j-u-register-emailed-retry">
    <?= _t('users', 'Не получили письмо? <a [link_retry]>Отправить еще раз</a>', array('link_retry'=>'href="#" class="ajax"')) ?>
</p>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jUserAuth.registerEmailed(<?= func::php2js(array(
            'lang' => array(
                'success' => _t('users', 'Письмо было успешно отправлено повторно'),
            ),
        )) ?>);
    });
<? js::stop(); ?>
</script>
<? } ?>