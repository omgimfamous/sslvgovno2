<?php
    foreach($list as $k=>$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><?= $v['title'] ?></td>
        <td class="left"><?= $v['code'] ?></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a class="but edit counter-edit" title="Редактировать" href="#" rel="<?= $id ?>"></a>
            <a class="but <?= ($v['enabled']?'un':'') ?>block counter-toggle" title="Включен" href="#" data-type="enabled" rel="<?= $id ?>"></a>
            <a class="but del counter-del" title="Удалить" href="#" rel="<?= $id ?>"></a>
        </td>
    </tr>
<?php endforeach;

if( empty($list) && !isset($skip_norecords) ): ?>
    <tr class="norecords">
        <td colspan="5">
            ничего не найдено
        </td>
    </tr>
<?php endif;