<?php

  require (__DIR__ . '/../paths.php');
  require PATH_CORE.'captcha/captcha.math.php';

/*
    <img src="<?= tpl::captchaURL() ?>" onclick="$(this).attr('src', '<?= tpl::captchaURL() ?>&r='+Math.random(1));" />
    
    для проверки:
    if( ! CCaptchaProtection::correct($this->input->cookie('c2'), $this->input->get('captcha2')) ) {
        $this->errors->set( _t('captcha', 'Результат с картинки указан некорректно') );
    }

*/