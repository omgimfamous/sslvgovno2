<!DOCTYPE html>
<html class="no-js">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?= SEO::i()->metaRender(); ?>
    <link rel="shortcut icon" href="<?= SITEURL_STATIC ?>/favicon.ico" />
    <? include 'css.php'; ?>
</head>
<body>
<div id="wrap">
    <!-- BEGIN header -->
    <div id="header">
        <div class="content">
            <div class="container-fluid">
                <div class="l-top row-fluid">
                    <div class="l-top__logo span12">
                        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
                            <!-- for: desktop & tablet -->
                            <div class="hidden-phone">
                                <img src="<?= SITEURL_STATIC ?>/img/do-logo.png" alt="" />
                            </div>
                        <? } if( DEVICE_PHONE ) { ?>
                            <!-- for: mobile -->
                            <div class="l-top__logo_mobile visible-phone">
                                <img src="<?= SITEURL_STATIC ?>/img/do-logo-small.png" alt="" />
                            </div>
                        <? } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END header -->
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="l-page span12">
                        <h1 class="align-center hidden-phone j-shortpage-title">
                            <?= _t('', 'Сайт временно отключен') ?>
                        </h1>
                        <div class="alert-inline visible-phone">
                            <div class="align-center alert j-shortpage-title">
                                <?= _t('', 'Сайт временно отключен') ?>
                            </div>
                        </div>
                        <div class="l-spacer hidden-phone"></div>
                        <div class="l-shortpage align-center">
                            <?= config::get('offline_reason_'.LNG); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END main content -->
</div>
</body>
</html>