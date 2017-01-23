<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= config::get('title_'.LNG) ?> | Панель управления</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<? include 'css.php'; ?>
<? include 'js.php'; ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row-fluid">
            <div class="login-logo">
                <img src="<?= SITEURL_STATIC ?>/img/do-logo-small.png" alt="" />
                <span><?= config::get('title_admin_'.LNG) ?></span>
            </div>
            <!--Start LOGIN block-->
            <div class="login-box">
                <? if( ! empty($errors) ) { ?>
                    <div class="alert alert-error">
                        <button data-dismiss="alert" class="close" type="button">×</button>
                        <? foreach($errors as $v) { echo $v, '<br />'; } ?>
                    </div>
                <? } ?>
                <div class="title">Панель управления</div>
                <div class="icons">
                    <a href="<?= Site::urlBase() ?>"><i class="icon-home"></i></a>
                </div>
                <div class="clearfix"></div>
                <form method="post" action="" class="form-horizontal">
                    <input type="hidden" name="s" value="users" />
                    <input type="hidden" name="ev" value="login" />
                    <input type="hidden" name="hh" value="" />
                    <fieldset>
                        <div title="Логин" class="input-prepend left" style="margin-left: 20px;">
                            <span class="add-on"><i class="icon-user"></i></span>
                            <input type="text" placeholder="логин" name="login" id="login" tabindex="1" class="input-large span10" />
                        </div>
                        <div title="Пароль" class="input-prepend right" style="margin-right: 16px;">
                            <span class="add-on"><i class="icon-lock"></i></span>
                            <input type="password" placeholder="пароль" name="password" tabindex="2" class="input-large span10" />
                        </div>
                        <div class="clearfix"></div>
                        <div class="progress left" style="display:none;" id="progress-login"></div>
                        <div class="btn-group button-login right">
                            <button class="btn btn-round btn-small" type="submit" onclick="document.getElementById('progress-login').style.display='inline-block';" tabindex="3"><img src="<?= SITEURL_STATIC ?>/img/admin/login.png" alt="" />&nbsp;&nbsp;Вход</button>
                        </div>
                        <div class="clearfix"></div>
                    </fieldset>
                </form>
                <script type="text/javascript">
                    document.getElementById('login').focus();
                </script>
            </div>
            <!--End LOGIN block-->
        </div>
    </div>    
</body>
</html>