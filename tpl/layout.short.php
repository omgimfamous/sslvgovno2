<!DOCTYPE html>
<html class="no-js">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?= SEO::i()->metaRender() ?>
<? if( User::id() ){ ?><meta name="csrf_token" content="<?= bff::security()->getToken() ?>"  /><? } ?>
<link rel="shortcut icon" href="<?= SITEURL_STATIC ?>/favicon.ico" />
<? include 'css.php'; ?>
</head>
<body class="q<?= bff::database()->statQueryCnt(); ?>">
<? include 'alert.php'; ?>
<div id="wrap">
    <? include 'header.short.php'; ?>
    <!-- BEGIN main content -->
    <div id="main">
        <div class="content">
            <div class="container-fluid">
                <?= $centerblock; ?>
            </div>
        </div>
    </div>
    <!-- END main content -->
    <div id="push"></div>
</div>
<? include 'footer.php'; ?>
</body>
</html>