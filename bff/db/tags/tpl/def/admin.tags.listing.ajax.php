<?php
    /**
     * Список тегов
     * @var $this bff\db\Tags
     */
    $itemsRow = ! empty($this->urlItemsListing);
?>
<?php foreach($list as $k=>$v) { $id = $v['id']; ?>
<tr class="row<?= $k%2 ?>">
    <?php if(FORDEV){ ?><td><?= $id ?></td><?php } ?>
    <td class="left"><?= $v['tag'] ?></td>
    <?php if($this->postModerationEnabled()) { ?><td><input type="checkbox" <?php if( ! empty($v['moderated']) ) { ?> checked="checked" disabled="disabled"<?php } ?> class="j-tag-moderate" data-id="<?= $id ?>" /></td><?php } ?>
    <?php if($itemsRow) { ?><td><a href="<?= $this->urlItemsListing.$id ?>"><?= $v['items'] ?></a></td><?php } ?>
    <td>
        <a class="but edit j-tag-edit" title="<?= _t('', 'Редактировать') ?>" href="#" data-id="<?= $id ?>"></a>
        <a class="but del j-tag-del" title="<?= _t('', 'Удалить') ?>" href="#" data-id="<?= $id ?>"></a>
    </td>
</tr>
<?php }

if( empty($list) ) { ?>
<tr class="norecords">
    <td colspan="<?php $nCols = 2; if(FORDEV) $nCols++; if($itemsRow) $nCols++; if($this->postModerationEnabled()) $nCols++; echo $nCols; ?>">список пуст</td>
</tr>
<?php }