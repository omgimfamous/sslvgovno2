<?php
    /**
     * @var $this Shops
     */
     tpl::includeJS(array('tablednd'), true);
     $urlListingAct = $this->adminLink('categories_listing&act=');
     tplAdmin::adminPageSettings(array(
        'link'=>array('title'=>'+ добавить категорию', 'href'=>$this->adminLink('categories_add')),
        'fordev'=>array(
            array('title'=>'валидация nested-sets', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('categories_listing&act=dev-treevalidate')."'})", 'icon'=>'icon-indent-left'),
            array('title'=>'удалить все категории', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('categories_listing&act=dev-delete-all')."'})", 'icon'=>'icon-remove'),
        ),
     ));
?>
<script type="text/javascript">
$(function(){
   bff.rotateTable($('#j-shops-cats-listing'), '<?= $urlListingAct.'rotate' ?>', '#j-shops-cats-progress');
});
function shopsCatAct(id, act, extra)
{
    switch(act)
    {
        case 'c': {
            bff.expandNS(id, '<?= $urlListingAct.'subs-list&category=' ?>',
                         {progress:'#j-shops-cats-progress', cookie: app.cookiePrefix+'shops_cats_state'});
        } break;
        case 'edit':     { bff.redirect( '<?= $this->adminLink('categories_edit&id=') ?>'+id); } break;
        case 'del':      {
            bff.ajaxDelete('sure', id, '<?= $urlListingAct.'delete' ?>', extra,
                {progress: '#j-shops-cats-progress'});
                return false;    
            } break;
        case 'toggle':   {
            bff.ajaxToggle(id, '<?= $urlListingAct.'toggle&rec=' ?>'+id,
                   {link: extra, progress: '#j-shops-cats-progress', complete: function(){
                        location.reload();
                   }});
                   return false;
            } break;
    }
    return false;
}
</script>

<table id="j-shops-cats-listing" class="table table-condensed table-hover admtbl">
<thead>
    <tr class="header nodrag nodrop">
        <th class="left">Название<span id="j-shops-cats-progress" style="display:none;" class="progress right"></span></th>
        <th width="120">Магазины</th>
        <th class="left" width="130">Действие</th>
    </tr>
</thead>
<? if( ! empty($cats) ) {
    echo $cats;
} else { ?>
    <tr class="norecords">
        <td colspan="3">нет категорий</td>
    </tr>
<? } ?>
</table>
<? if( ! empty($cats) ) { ?>
<div>
    <div class="left"></div>
    <div style="width:80px; text-align:right;" class="right desc">&nbsp;&nbsp; &darr; &uarr;</div>
    <br/>
</div>
<? } ?>