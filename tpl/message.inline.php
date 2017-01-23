<?php

?>
<br />
<p class="<?= ( ! empty($align) ? $align : 'align-center') ?>">
    <?= $message ?>
    <? if( ! empty($auth)) { ?><br /><?= _t('', '<a [link_login]>Войдите</a> или <a [link_register]>зарегистрируйтесь</a>',
                    array('link_login'    => 'href="'.Users::url('login').'"',
                          'link_register' => 'href="'.Users::url('register').'"',)) ?><? } ?>
</p>