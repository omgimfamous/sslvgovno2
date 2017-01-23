
<?= tplAdmin::blockStart('Добавить валюту', false, array('id'=>'commonCurrFormBlock','style'=>'display:none;')) ?>
<form action="" method="post" name="commonCurrFormNew">
    <input type="hidden" name="act" value="add-finish" />
    <table class="admtbl tbledit form_params">
        <?= $form ?>
    </table>
    <table class="admtbl tbledit">
        <tr class="footer">
            <td colspan="2">
                <input type="submit" class="btn btn-success button submit" value="Сохранить" />
                <input type="button" class="btn button cancel" value="Отмена" onclick="commonCurrencies.toggle('cancel');" />
            </td>
        </tr>
    </table>
</form>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart('Список валют', true, array(),
        array('title'=>'+ добавить валюту', 'class'=>'ajax', 'onclick'=>'return commonCurrencies.toggle(\'add\');')) ?>

<table class="table table-condensed table-hover admtbl tblhover" id="common-currencies-table">
<thead>
    <tr class="header nodrag nodrop">
        <th width="50">ID</th>
        <th class="left">Название</th>
        <th width="135">Действие</th>
    </tr>
</thead>
<?php foreach($currencies as $k=>$v) { $id = $v['id']; ?>
<tr class="row<?= $k%2 ?>" id="dnd-<?= $id ?>">
    <td><?= $id ?></td>
    <td class="left"><?= $v['title'] ?></td>
    <td>
        <a class="but <?php if($v['enabled']) { ?>un<?php } ?>block curr-toggle" title="Вкл/Выкл" href="#" rel="<?= $id ?>"></a>
        <a class="but edit curr-edit" title="Редактировать" href="#" rel="<?= $id ?>"></a>
        <?php if(FORDEV) { ?><a class="but del curr-del" title="Удалить" href="#" rel="<?= $id ?>"></a><?php } ?>
    </td>
</tr>
<?php } if( empty($currencies) ) { ?>
<tr class="norecords">
    <td colspan="3">нет валют (<a href="#" class="ajax" onclick="return commonCurrencies.toggle('add');">добавить</a>)</td>
</tr>
<?php } ?>
</table>
<?= tplAdmin::blockStop() ?>

<div>
    <div class="left">    
        
    </div>
    <div class="right desc" style="width:60px; text-align:right;">
        <div class="progress" id="progress-common-currencies" style="display:none;"></div>
        &darr; &uarr;
    </div> 
</div> 

<script type="text/javascript">
var commonCurrencies = (function(){
    var $progress, $block, $blockCaption, form, formClean, $list, fChecker;
    var ajax_url = '<?= $this->adminLink('currencies&act=') ?>';
    
    $(function(){
        form = document.forms.commonCurrFormNew;
        
        fChecker = new bff.formChecker( form );
        formClean = $(form).html();
        
        $progress = $('#progress-common-currencies');
        $block = $('#commonCurrFormBlock');
        $blockCaption = $block.find('span.caption'); 
        $list = $('#common-currencies-table');
        
        $list.on('click', 'a.curr-edit', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) edit( id );
            return false;
        });
        $list.on('click', 'a.curr-toggle', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) {
                bff.ajaxToggle(id, ajax_url+'toggle', {progress: $progress, link: this});
            }
            return false;
        });        
        $list.on('click', 'a.curr-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del( id, this );
            return false;
        });
        
        bff.rotateTable('#common-currencies-table', ajax_url+'rotate', $progress);
    });

    function toggle(type, editData)
    {                                           
        switch(type) {
            case 'add': {
                if($block.is(':hidden')) {
                    $block.show();
                    $(form).html(formClean);
                    $blockCaption.html('Добавить валюту');
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
            ajax_url+'delete&curr_id='+id, 
            link, {progress: $progress, repaint: false});
        return false;
    }
    
    function edit(id)
    {                          
        bff.ajax(ajax_url+'edit&curr_id='+id,{},function(data){
            if(data) {
                toggle('edit', $.extend({caption: 'Редактирование валюты'}, data) );
            }                  
        }, $progress);
        return false;
    }
    
    return {toggle: toggle};
}());
</script>