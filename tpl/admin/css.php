<?php
    $cssUrl = SITEURL_STATIC;
?>
<link href="<?= $cssUrl ?>/css/admin-bootstrap.css" rel="stylesheet" media="screen" />
<link href="<?= $cssUrl ?>/css/admin-responsive.css" rel="stylesheet" media="screen" />
<link href="<?= $cssUrl ?>/css/admin.css" rel="stylesheet" media="screen" />
<?
foreach(tpl::$includesCSS as $v) {
    ?><link rel="stylesheet" href="<?= $v; ?>" type="text/css" /><?
}