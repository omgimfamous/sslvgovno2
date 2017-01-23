<?php
    $urlEdit = $this->adminLink('template_edit&tpl=');
?>
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <th class="left">Доступные уведомления</th>
        <th width="50">HTML</th>
        <th width="150">Шаблон</th>
        <th width="30"></th>
    </tr>
</thead>
<?php $i=0; foreach($templates as $k=>$v): ?>
<tr class="row<?= $i++%2; ?>>">
	<td class="left">
        <span class="bold<?php if( ! $v['impl']){ ?> desc<?php } ?>"><?= $v['title'] ?></span><br /><span class="desc"><?= $v['description'] ?></span>
    </td>
    <td>
        <a class="but checked<?php if(empty($v['is_html'])) { ?> unchecked<?php } ?>" onclick="return false;"></a>
    </td>
    <td class="small">
        <?php if(!empty($v['wrapper'])) { echo $v['wrapper']; } else { ?>&mdash;<?php } ?>
    </td>
    <td>
        <a class="but edit" href="<?= $urlEdit.$k ?>"></a>
    </td>
</tr>
<?php
   endforeach;
   if (empty($templates)):
?>
    <tr class="norecords">
        <td colspan="4">нет доступных уведомлений</td>
    </tr>
<?php endif; ?>
</table>