<?php

    tplAdmin::adminPageSettings(array('link'=>array('title'=>'+ добавить', 'href'=>$this->adminLink('mm_add'))));
?>
<table class="table table-condensed table-hover admtbl tblhover" id="mm-listing">
<thead>
    <tr class="header nodrag nodrop">
        <th class="left" width="150">ММ</th>
        <th class="left">Название</th>
        <th width="90">Действия</th>
    </tr>
</thead>
<?php foreach($mm as $k=>$v) { $id = $v['id']; ?>
<tr class="row0" id="dnd-<?= $id ?>" data-pid="0" data-numlevel="1">
	<td class="left bold"><?= $v['module'] ?></td>
	<td class="left"><?= $v['title'] ?></td>
	<td>
        <a class="but del" title="удалить" href="#" onclick="mmDelete(<?= $id ?>); return false;"></a>
    </td>
</tr>
    <?php foreach($v['subitems'] as $vv) { $id2 = $vv['id']; ?>
        <tr class="row1" id="dnd-<?= $id2 ?>" data-pid="<?= $id ?>" data-numlevel="2">
	        <td class="left"><?= $vv['method'] ?></td>
	        <td class="left"><?= $vv['title'] ?></td>
	        <td>
                <a class="but del" title="удалить" href="#" onclick="mmDelete(<?= $id2 ?>); return false;"></a>
            </td>
        </tr>
    <?php } ?>
<?php } ?>
</table>
<div>
    <input type="button" class="btn btn-small button submit" value="+ добавить модуль/метод" onclick="bff.redirect('<?= $this->adminLink('mm_add') ?>');" />
    <div class="progress" style="display:none;" id="progress-mm"></div>
</div>

<script type="text/javascript">
$(function(){
    bff.rotateTable('#mm-listing', '<?= $this->adminLink('mm_listing&act=rotate') ?>', '#progress-mm');
}); 

function mmDelete(id)
{
    bff.ajaxDelete('sure', id, '<?= $this->adminLink('mm_listing&act=delete') ?>', false, {progress: '#progress-mm', onComplete: function(data){
        if(data){
            location.reload();
        }
    }});
}
</script>