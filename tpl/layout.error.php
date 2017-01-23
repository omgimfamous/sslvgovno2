<!DOCTYPE html>
<html class="no-js">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= $title ?></title>
<link rel="shortcut icon" href="<?= SITEURL_STATIC ?>/favicon.ico" />
<? include 'css.php'; ?>
</head>
<body class="q<?= bff::database()->statQueryCnt(); ?>">
<? include 'alert.php'; ?>
<div id="wrap">
    <?
        if( ! defined('DEVICE_DESKTOP_OR_TABLET') ) {
            bff::log( 'DEVICE_DESKTOP_ constants not defined, file: layout.error.php' );
        }
    ?>
    <? include 'header.short.php'; ?>
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <div class="row-fluid">
                    <div class="l-page span12">
                        <?= $centerblock; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- END main content -->
    <div id="push"></div>
</div>
<? include 'footer.php'; ?>
</body>
</html>