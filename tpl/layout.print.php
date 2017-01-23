<!DOCTYPE html>
<html class="no-js">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?= SEO::i()->metaRender() ?>
<link rel="shortcut icon" href="<?= SITEURL_STATIC ?>/favicon.ico" />
<? include 'css.php'; ?>
</head>
<body class="q<?= bff::database()->statQueryCnt(); ?>">
<div id="wrap">
    <!-- BEGIN main content -->
    <div id="main">
        <?= $centerblock; ?>
    </div>
    <!-- END main content -->
</div>
</body>
</html>