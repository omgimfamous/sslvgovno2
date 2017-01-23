<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?= config::get('title_'.LNG) ?> | Панель управления</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<? include 'css.php'; ?>
<? include 'js.php'; ?>
<? if($err_errors && $err_autohide) { ?>
<script type="text/javascript">
$(function(){ bff.error(false, {init: true}); /* init err block */ });
</script>
<? } ?>
</head>
<body lang="<?= LNG ?>" class="q<?= $db_querycnt; ?>">
    <div class="warnblock warnblock-fixed" id="warning" style="<? if(empty($err_errors)) { ?>display:none;<? } ?>">
        <div class="warnblock-content alert alert-<?= ($err_success ? 'success' : 'danger') ?>">
            <a class="close j-close" href="#">&times;</a>
            <ul class="warns unstyled">
                <? foreach($err_errors as $v) { ?>
                <li><?= $v['msg'] ?><? if($v['errno'] == Errors::ACCESSDENIED) { ?> (<a href="#" onclick="history.back();">назад</a>)<? } ?></li>
                <? } ?>
            </ul>
        </div>
    </div>
    <div id="popupMsg" class="ipopup" style="display:none;">
        <div class="ipopup-wrapper">
            <div class="ipopup-title"></div>
            <div class="ipopup-content"></div>
            <div class="ipopup-footer-wrapper">
                <a href="javascript:void(null);" rel="close" class="ajax right">Закрыть</a>
                <div class="ipopup-footer"></div>
            </div>
        </div>
    </div>
    <div id="wrapper">
        <div id="main-side">
            <div class="navbar admintopmenu">
                <div class="navbar-inner">
                    <div class="container-fluid">
                        <a href="<?= Site::urlBase() ?>" class="brand"><img src="<?= SITEURL_STATIC ?>/img/do-logo-small.png" alt="" /> <span class="hidden-phone"><?= config::get('title_admin_'.LNG, 'Панель администратора') ?></span></a>
                        <!-- start: Header Menu -->
                        <div class="btn-group pull-right">
                            <?
                                $headerCounters = CMenu::i()->adminHeaderCounters();
                                foreach($headerCounters as $v) { ?>
                                <a href="<?= $v['url'] ?>" class="btn">
                                    <? if( ! empty($v['i']) ) { ?><i class="<?= $v['i'] ?>"></i><? } ?>
                                    <span class="hidden-phone hidden-tablet"> <?= $v['t'] ?></span><? if($v['cnt'] > 0) { ?> <span class="label <?= ($v['danger'] ? 'label-important' : 'label-success') ?>"><?= $v['cnt'] ?></span><? } ?>
                                </a>
                            <?  } ?>
                            <!-- start: User Dropdown -->
                            <a href="#" data-toggle="dropdown" class="btn<?= (FORDEV ? ' btn-info' : '') ?> dropdown-toggle">
                                <i class="<?= (!FORDEV ? 'icon-user' : 'icon-wrench') ?>"></i><span class="hidden-phone hidden-tablet"> <?= $user_login ?></span>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a href="<?= tplAdmin::adminLink('profile','users') ?>"><i class="icon-user"></i> настройка профиля</a></li>
                                <? if( bff::security()->isSuperAdmin() ) { ?><li><a href="<?= tplAdmin::adminLink(bff::$event.'&fordev='.(FORDEV?'off':'on'), bff::$class) ?>">
                                        <i class="icon-wrench"></i> <?= ( ! FORDEV ? 'режим разработчика' : 'выход из режима разработчика') ?>
                                    </a>
                                </li><? } ?>
                                <li><a href="<?= tplAdmin::adminLink('logout','users') ?>"><i class="icon-off"></i> выход</a></li>
                            </ul>
                            <!-- end: User Dropdown -->
                        </div>
                        <!-- end: Header Menu -->
                    </div>
                </div>
            </div>
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="span3">
                        <div id="adminmenu" class="adminmenu">
                        <ul class="nav nav-list">
                            <? foreach($menu['tabs'] as $k=>$v): ?>
                                <li<? if($v['active']) { ?> class="active"<? } ?>>
                                     <a href="<?= $v['url'] ?>" class="<? if($v['active']) { ?>active <? } ?>main">
                                        <?= $v['title'] ?>
                                     </a>
                                     <ul class="nav nav-list sub<? if( ! $v['subtabs']) { ?> empty<? } ?>"<? if( ! $v['active']) { ?> style="display: none;"<? } ?>>
                                        <? $i=1; $j = sizeof($v['subtabs']);
                                           foreach($v['subtabs'] as $kk=>$vv):
                                             $last = ($i++==$j);
                                        ?>
                                            <li>
                                                <? if( ! $vv['separator'] ) { ?>
                                                    <a href="<?= $vv['url'] ?>" class="<? if($vv['active']) { ?>active <? } if($last) { ?> last<? } ?>"><?= $vv['title'] ?></a>
                                                <? } else { ?>
                                                    <hr size="1" />
                                                <? }
                                                if( ! empty($vv['rlink']) ) { ?><a href="<?= $vv['rlink']['url'] ?>" class="rlink hidden-phone hidden-tablet"><i class="icon-plus"></i></a><? } ?>
                                            </li>
                                        <? endforeach; ?>
                                     </ul>
                                </li>
                            <? endforeach; ?>
                            <li class="divider"></li>
                            <li><a class="main logout" href="<?= tplAdmin::adminLink('logout','users') ?>">Выход &rarr; </a></li>
                        </ul>
                        </div>
                    </div>
                    <div class="span9">
                        <div id="content-side">
                        <? if($page['custom']) {
                             echo $centerblock;
                           } else {
                             echo tplAdmin::blockStart($page['title'], $page['icon'], $page['attr'], $page['link'], $page['fordev']);
                             echo $centerblock;
                             echo tplAdmin::blockStop();
                           } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div id="push"></div>
    </div>
    <div id="footer">
        <hr />
        <footer>
            <p class="pull-right">Сделано в <a href="#" target="_blank">Tamaranga</a></p>
        </footer>
    </div>
</body>
</html>