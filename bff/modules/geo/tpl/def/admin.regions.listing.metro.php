<?php
    tpl::includeJS(array('tablednd'), true);
?>

<?= tplAdmin::blockStart('Добавить метро', false, array('id'=>'GeoRegionsMetroFormBlock','style'=>'display:none;')) ?>
<form action="" method="post" name="GeoRegionsMetroFormNew" enctype="multipart/form-data">
    <input type="hidden" name="act" value="add-finish" />
    <input type="hidden" name="city_id" value="<?= $city ?>" />
    <table class="admtbl tbledit form_params">
        <?= $form ?>
    </table>
    <table class="admtbl tbledit">
        <tr class="footer">
            <td colspan="2">
                <input type="submit" class="btn btn-success button submit" value="Сохранить" />
                <input type="button" class="btn button submit" value="Отмена" onclick="jGeoRegionsMetro.toggle('cancel');" />
            </td>
        </tr>
    </table>
</form>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart('Регионы / Метро') ?>

<?= $this->viewPHP($aData, 'admin.regions.listing.tabs', $this->module_dir_tpl_core); ?>

<div class="actionBar">
    <form action="<?= $this->adminLink(NULL) ?>" method="get" id="GeoRegionsMetroListForm" class="form-inline">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <label style="margin-right: 10px;"><a href="<?= $this->adminLink('regions_country') ?>"><?= _t('geo', 'Страна') ?></a>:&nbsp;<select name="country" style="width:120px;" onchange="jGeoRegionsMetro.onFilter('country');"><?= $country_options ?></select></label>
        <label>Город:&nbsp;&nbsp;<select name="city" onchange="jGeoRegionsMetro.onFilter('city');" style="margin-right: 5px;"><?= $city_options ?></select></label>
        <a href="#" class="ajax" onclick="return jGeoRegionsMetro.toggle('add');">+ <?= _t('', 'добавить') ?></a>
    </form>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="geo-regions-metro-table">
<thead>
    <tr class="header nodrag nodrop">
        <th width="70"></th>
        <th class="left"><?= _t('','Название') ?></th>
        <th width="120"><?= _t('','Действие') ?></th>
    </tr>
</thead>
<?php foreach($items as $k=>$v):
   $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>" data-numlevel="1" data-pid="0">
        <td><?php if( ! empty($v['color'])){ ?><div style="background-color:<?= $v['color'] ?>; float: right; width:20px; height:20px;">&nbsp;</div><?php } ?></td>
        <td class="left<?php if($v['branch']){ ?> bold<?php } ?>"><?= $v['title'] ?></td>
        <td>
            <a class="but edit metro-edit" href="#" rel="<?= $id ?>"></a>
            <a class="but del metro-del" href="#" rel="<?= $id ?>"></a>
        </td>
    </tr>
    <?php if( ! empty($v['sub'])): foreach($v['sub'] as $kk=>$vv): $metroID = $vv['id']; ?>
    <tr class="row<?= ($kk%2) ?>" id="dnd-<?= $metroID ?>" data-numlevel="2" data-pid="<?= $id ?>">
        <td></td>
        <td class="left" style="padding-left: 6px;"><?= $vv['title'] ?></td>
        <td>
            <a class="but edit metro-edit" href="#" rel="<?= $metroID ?>"></a>
            <a class="but del metro-del" href="#" rel="<?= $metroID ?>"></a>
        </td>
    </tr>
    <?php endforeach; endif; ?>
<?php endforeach; if(empty($items)): ?>
<tr class="norecords">
    <td colspan="3">
        <?php if( ! $city): ?>
            укажите город
        <?php else: ?>
            в выбранном городе нет станций метро
        <?php endif; ?>
    </td>
</tr>
<?php endif; ?>
</table>
<div>
    <div class="left">

    </div>
    <div class="right desc" style="width:60px; text-align:right;">
        <div class="progress" id="progress-geo-regions-metro" style="display:none;"></div>
        &darr; &uarr;
    </div>
    <div class="clear clearfix"></div>
</div>
<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
var jGeoRegionsMetro = (function(){
    var $progress, $block, $blockCaption, form, formClean, $list, fChecker;
    var ajax_url = '<?= $this->adminLink('regions_metro&act='); ?>';
    
    $(function(){
        form = document.forms.GeoRegionsMetroFormNew;
        
        fChecker = new bff.formChecker( form );
        formClean = $(form).html();
        
        $progress = $('#progress-geo-regions-metro');
        $block = $('#GeoRegionsMetroFormBlock');
        $blockCaption = $block.find('span.caption'); 
        $list = $('#geo-regions-metro-table');
        
        $list.on('click', 'a.metro-edit', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) edit( id );
            return false;
        });
        $list.on('click', 'a.metro-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del( id, this );
            return false;
        });

        bff.rotateTable('#geo-regions-metro-table', ajax_url+'rotate', $progress);
    });

    function toggle(type, editData)
    {                                           
        switch(type) {
            case 'add': {
                if($block.is(':hidden')) {
                    $block.show();
                    $(form).html(formClean);
                    $blockCaption.html('Добавить станцию метро');
                    $.scrollTo( $blockCaption, { duration:500, offset:-300 } );
                } else {
                    $block.hide();   
                }
            } break; 
            case 'cancel': {
                $block.hide();
            } break;
            case 'edit': {                           
                $blockCaption.html(editData.caption);
                $(form).find('.form_params').html(editData.form);
                $block.show();
                $.scrollTo( $blockCaption, { duration:500, offset:-300 } );
                form.elements['act'].value = 'edit-finish';
            } break;
        }
        fChecker.check(true, true);
        return false;
    }
    
    function del(id, link)
    {
        bff.ajaxDelete('sure', id,
            ajax_url+'delete&id='+id,
            link, {progress: $progress, repaint: false});
        return false;
    }

    function edit(id)
    {
        bff.ajax(ajax_url+'edit&id='+id,{},function(data){
            if(data) {
                toggle('edit', $.extend({caption: 'Редактирование станции метро'}, data) );
            }                  
        }, $progress);
        return false;
    }
    
    return {
        toggle: toggle,
        onFilter: function(type) {
            var $filter = $('#GeoRegionsMetroListForm');
            if(type == 'country') {
                $filter.find('[name="city"]').val(0);
            }
            $filter.submit();
        }
    };
}());
</script>