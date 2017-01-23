<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

<?

?>
<link rel="stylesheet" media="all" type="text/css" href="<?= SITEURL_STATIC ?>/css/custom-bootstrap.css" />
<link rel="stylesheet" media="all" type="text/css" href="<?= SITEURL_STATIC ?>/css/main.css" />
<?
 ?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css" media="all" type="text/css" />
<?
foreach(tpl::$includesCSS as $v) {
    ?><link rel="stylesheet" href="<?= $v; ?>" type="text/css" /><?
}