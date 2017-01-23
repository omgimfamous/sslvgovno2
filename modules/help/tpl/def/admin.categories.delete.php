<?php
    /**
     * @var $this Help
     */
    tplAdmin::adminPageSettings(array('title'=>'Категории / Удаление'));
    $questions = intval($questions);
?>
<script type="text/javascript">
var cntQuestions = <?= $questions ?>;
function helpCategoryDelete()
{
    if ( ! cntQuestions)
        return true;

    if (intval($('#category').val()) <= 0) {
        bff.error('Выберите категорию');
        return false;
    }
    return true;
}
</script>

<form action="" method="post" onsubmit="return helpCategoryDelete();" >
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <? if($questions > 0){ ?>
            <p class="text-info">Прежде чем удалить категорию "<strong><?= $title[LNG] ?></strong>", укажите категорию к которой будут <br />
            относиться <? if($questions > 0){ ?> вопросы (<a href="<?= $this->adminLink('questions&cat='.$id); ?>"><?= $questions ?></a>)<? } ?> относившиеся к удаляемой категории: <select name="next" id="category"><?= $categories ?></select></p>
        <? } else { ?>
            <p class="text-info">Вы уверены, что хотите удалить категорию "<strong><?= $title[LNG] ?></strong>"?</p>
        <? } ?>
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger button delete" value="Удалить" />
        <input type="button" class="btn button cancel" value="Отмена" onclick="return history.back();" />
    </td>
</tr>
</table>
</form>