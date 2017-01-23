<?php
    /**
     * Форма добавления/редактирования тега
     * @var $this bff\db\Tags
     */
?>
<tr class="required">
    <?php if($add) { ?>
    <td colspan="2" class="row1">
        <p class="text-info"><?= $this->lang['add_text'] ?>:</p>
        <textarea class="stretch" name="tags" style="height:100px;"></textarea>
    </td>
    <?php } else { ?>
    <td class="row1" style="width:80px;"><span class="field-title"><?= _t('tags','Название') ?>:</span></td>
    <td class="row2">
        <input type="hidden" name="tag_id" value="<?= $id ?>" />
        <input class="stretch" type="text" name="tag" value="<?= HTML::escape($tag); ?>" maxlength="200" />
    </td>
    <?php } ?>
</tr>
<?php if (! $add) { ?>
<tr>
    <td class="row1"><span class="field-title"><?= _t('tags','Заменить на') ?>:</span></td>
    <td class="row2 relative">
        <input type="hidden" name="replace_tag_id" value="0" class="j-tag-replace-id" />
        <input type="text" value="" style="width: 50%;" maxlength="200" class="j-tag-replace-ac autocomplete" placeholder="<?= $this->lang['replace_text'] ?>" />
    </td>
</tr>
<?php } ?>