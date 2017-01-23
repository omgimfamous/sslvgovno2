<script type="text/javascript">//<![CDATA[
var app = {adm: true, host:'<?= SITEHOST ?>', root: '<?= SITEHOST ?>', rootStatic: '<?= SITEURL_STATIC ?>', cookiePrefix: '<?= config::sys('cookie.prefix') ?>'};
//]]></script>
<?php foreach(tpl::$includesJS as $v) { ?>
<script src="<?= $v ?>" type="text/javascript" charset="utf-8"></script>
<?php } ?>
<script type="text/javascript">
$(function() {
    bff.map.setType('<?= Geo::mapsType() ?>');
    //init admin menu
    var colapse_process = false;
    $('#adminmenu a.main:not(a.logout)').click(function() {
        if($(this).next().is('.empty')) 
            return true;           
            
        if(colapse_process) return false; colapse_process = true;  

        if(! $(this).next().is(':visible') )
            $('#adminmenu ul.sub li:visible').parent().slideFadeToggle();
        
        $(this).next().slideFadeToggle(function(){ colapse_process = false; });
        return false;
    }).next().hide();
    $('#adminmenu a.active.main').next().show();
});

bff.extend(bff,
{
    iteminfo: function(itemID) {
        if(itemID) {
            $.fancybox('', {ajax:true, href:'<?= tpl::adminLink('ajax&act=item-info&id=','bbs') ?>'+itemID});
        }
        return false;
    },
    shopInfo: function(itemID) {
        if(itemID) {
            $.fancybox('', {ajax:true, href:'<?= tpl::adminLink('ajax&act=shop-info-popup&id=','shops') ?>'+itemID});
        }
        return false;
    }
});
</script>