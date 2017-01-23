<?php
    /**
     * @var $this Blog
     */
    tplAdmin::adminPageSettings(array('title'=>'Категории / Удаление'));
    $posts = intval($posts);
?>
<script type="text/javascript">
var cntPosts = <?= $posts ?>;
function blogCategoryDelete()
{
    if ( ! cntPosts)
        return true;

    if (intval($('#category').val()) <= 0) {
        bff.error('Выберите категорию');
        return false;
    }
    return true;
}
</script>

<form action="" method="post" onsubmit="return blogCategoryDelete();" >
<input type="hidden" name="id" value="<?= $id ?>" />
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        <? if($posts > 0){ ?>
            <p class="text-info">Прежде чем удалить категорию "<strong><?= $title[LNG] ?></strong>", укажите категорию к которой будут <br />
            относиться <? if($posts > 0){ ?> посты (<a href="<?= $this->adminLink('posts&cat='.$id); ?>"><?= $posts ?></a>)<? } ?> относившиеся к удаляемой категории: <select name="next" id="category"><?= $categories ?></select></p>
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