<?php
/**
 * Cписок директорий/файлов требующих проверки на наличие прав записи
 * @var $list array
 * @var $accessTypes array
 */
?>
<table class="table table-condensed table-hover admtbl tblhover">
    <thead>
        <tr class="header">
            <th class="left">Путь</th>
            <th width="200">Доступ</th>
        </tr>
    </thead>
    <?php $i=1; foreach($dirs as $v) { ?>
        <tr class="row<?= ($i++%2) ?>">
            <td class="left"><?= $v['path'] ?><?php if(!empty($v['title'])) { ?><span class="desc"> - <?= $v['title'] ?></span><?php } ?></td>
            <td><span class="<?= $accessTypes[$v['access']]['class'] ?>"><?= $accessTypes[$v['access']]['t'] ?></span></td>
        </tr>
    <?php } ?>
</table>