<?php
    foreach($list as $k=>$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <?php if(FORDEV) { ?>
            <td><?= $id ?></td>
            <td class="left"><?= $v['keyword'] ?></td>
        <?php } ?>
        <td class="left"><?= $v['title'] ?></td>
        <td><?= $v['sizes'] ?></td>
        <td><a class="but <?= (!$v['rotation']?'un':'') ?>checked check position-toggle" title="Ротация" href="#" data-type="rotation" data-toggle-type="check" rel="<?= $id ?>"></a></td>
        <td><a href="<?= $this->adminLink('listing&pos='.$v['id']) ?>" ><?= $v['banners'] ?></a></td>
        <td>
            <a class="but edit position-edit" title="Редактировать" href="#" rel="<?= $id ?>"></a>
            <a class="but <?= ($v['enabled']?'un':'') ?>block position-toggle" title="Включен" href="#" data-type="enabled" rel="<?= $id ?>"></a>
            <?php if(FORDEV) { ?><a class="but del position-del" title="Удалить" href="#" rel="<?= $id ?>"></a><?php } ?>
        </td>
    </tr>
<?php endforeach;

if( empty($list) && ! isset($skip_norecords) ): ?>
    <tr class="norecords">
        <td colspan="<?= (FORDEV ? 7 : 5) ?>">
            ничего не найдено
        </td>
    </tr>
<?php endif;