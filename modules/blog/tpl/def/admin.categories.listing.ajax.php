<?php
    /**
     * @var $this Blog
     */
    $urlPosts = $this->adminLink('posts&cat=');
    foreach($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>" data-numlevel="<?= $v['numlevel'] ?>" data-pid="<?= $v['pid'] ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><?= $v['title'] ?></td>
        <td><a href="<?= $urlPosts.$id ?>"><?= $v['posts'] ?></a></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a class="but <?= ($v['enabled']?'un':'') ?>block category-toggle" title="Включена" href="#" data-type="enabled" data-id="<?= $id ?>"></a>
            <a class="but edit category-edit" title="Редактировать" href="#" data-id="<?= $id ?>"></a>
            <a class="but del category-del" title="Удалить" href="#" data-id="<?= $id ?>"></a>
        </td>
    </tr>
<? endforeach; unset($v);

if( empty($list) && !isset($skip_norecords) ): ?>
    <tr class="norecords">
        <td colspan="5">
            ничего не найдено
        </td>
    </tr>
<? endif;