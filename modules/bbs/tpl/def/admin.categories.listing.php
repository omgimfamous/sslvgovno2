<?php
    /**
     * @var $this BBS
     */
     tpl::includeJS(array('tablednd'), true);
     $urlListingAct = $this->adminLink('categories_listing&act=');
     tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>'+ добавить категорию', 'href'=>$this->adminLink('categories_add')),
        'fordev'=>array(
            array('title'=>'пакетные настройки', 'href'=>$this->adminLink('categories_packetActions'), 'icon'=>'icon-th-list'),
            array('title'=>'экспорт в формате txt', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('categories_listing&act=dev-export&type=txt')."'})", 'icon'=>'icon-download'),
            array('title'=>'валидация nested-sets', 'onclick'=>"return bff.confirm('Длительная операция, продолжить?', {r:'".$this->adminLink('categories_listing&act=dev-treevalidate')."'})", 'icon'=>'icon-indent-left'),
            array('title'=>'удалить все категории', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('categories_listing&act=dev-delete-all')."'})", 'icon'=>'icon-remove'),
        ),
     ));
?>
<script type="text/javascript">
$(function(){
   bff.rotateTable($('#bbs_cats_listing'), '<?= $urlListingAct.'rotate' ?>', '#progress-bbs-categories');
});

function bbsCatAct(id, act, extra)
{
    switch(act)
    {
        case 'c': {
            bff.expandNS(id, '<?= $urlListingAct.'subs-list&category=' ?>',
                         {progress:'#progress-bbs-categories', cookie: app.cookiePrefix+'bbs_cats_state'});
        } break;
        case 'dyn':      { bff.redirect( '<?= $this->adminLink('dynprops_listing&owner=') ?>'+id); } break;
        case 'edit':     { bff.redirect( '<?= $this->adminLink('categories_edit&id=') ?>'+id); } break;
        case 'del':      {
            bff.ajaxDelete('sure', id, '<?= $urlListingAct.'delete' ?>', extra, {
                    progress: '#progress-bbs-categories',
                    onComplete: function(data) {
                        location.reload();
                    }
                });
                return false;    
            } break;
        case 'toggle':   {
            bff.ajaxToggle(id, '<?= $urlListingAct.'toggle&rec=' ?>'+id,
                   {link: extra, progress: '#progress-bbs-categories', complete: function(r){
                        if( r.success && r.hasOwnProperty('reload') ) {
                            location.reload();
                        }
                   }});
                   return false;
            } break;
    }

    return false;
}
</script>

<table id="bbs_cats_listing" class="table table-condensed table-hover admtbl">
<thead>
    <tr class="header nodrag nodrop">
        <th class="left">Название</th>
        <th width="75">Объявления</th>
        <th width="70">Карта</th>
        <th width="45">Цена</th>
        <th width="155">Действие <span id="progress-bbs-categories" style="display:none;" class="progress right"></span></th>
    </tr>
</thead>
<? if( ! empty($cats) ) {
    echo $cats;
} else { ?>
    <tr class="norecords">
        <td colspan="5">нет категорий</td>
    </tr>
<? } ?>
</table>
<div>
    <br />
    <div class="left"> </div>
    <div style="width:80px; text-align:right;" class="right desc">&nbsp;&nbsp; &darr; &uarr;</div>
    <br/>
</div>