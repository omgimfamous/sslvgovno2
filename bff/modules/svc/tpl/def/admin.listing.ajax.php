<?php
    $i = 0;
    foreach($list as $k=>$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($i++%2) ?>" id="dnd-<?= $id ?>">
        <td><?= $id ?></td>
        <td class="left"><?= $v['title'] ?></td>
        <td class="left"><?= $v['module_title'] ?><span class="desc"> (<?= $v['module'] ?>)</span></td>
        <td>
            <a class="but edit svc-edit" title="Редактировать" href="#" rel="<?= $id ?>"></a>
            <a class="but del svc-del" title="Удалить" href="#" rel="<?= $id ?>"></a>
        </td>
    </tr>
<?php endforeach;

if( empty($list) && !isset($skip_norecords) ): ?>
    <tr class="norecords">
        <td colspan="3">
            ничего не найдено
        </td>
    </tr>
<?php endif;