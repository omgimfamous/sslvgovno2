<?php
    if($this->manageNonSystemGroups || FORDEV) {
        tplAdmin::adminPageSettings(array('link'=>array('title'=>'+ создать группу', 'href'=>$this->adminLink('group_add'))));
    }
?>
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?php if(FORDEV){ ?><th width="50">ID</th><?php } ?>
        <th class="left">Название группы<div class="order-asc"></div></th>
        <?php if(FORDEV){ ?><th width="110">Keyword</th><?php } ?>
        <th>Доступ</th>
        <th width="80">Действие</th>
    </tr>
</thead>
<tbody>
<?php foreach($groups as $k=>$v) { $id = $v['group_id']; ?>
<tr class="row<?= $k%2 ?>">
	<?php if(FORDEV){ ?><td><?= $id ?></td><?php } ?>
	<td class="left"><span style="color:<?= $v['color'] ?>; font-weight:bold;"><?= $v['title'] ?></span></td>
    <?php if(FORDEV){ ?><td><?= $v['keyword'] ?></td><?php } ?>
	<td>
        <?php if( ! $v['adminpanel']) { ?>
            <span class="desc">нет</span>
        <?php } else { ?>
            <a href="<?= $this->adminLink('group_permission_listing&rec='.$id) ?>">доступ</a>
        <?php } ?>
    </td>
    <td>
    <?php if( (! $v['issystem'] && $this->manageNonSystemGroups) || FORDEV ) { ?>
        <a class="but edit" title="редактировать" href="<?= $this->adminLink('group_edit&rec='.$id) ?>"></a>
        <a class="but del" title="удалить" href="#" onclick="bff.confirm('sure', {r:'<?= $this->adminLink('group_delete&rec='.$id) ?>'}); return false;" ></a>
    <?php } else { ?>
        <span class="desc">нет</span>
    <?php } ?>
    </td> 
</tr>
<?php } ?>
</tbody>
</table>