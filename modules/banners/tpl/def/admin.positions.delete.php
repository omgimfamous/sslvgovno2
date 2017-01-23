<?php ?>
<form method="post" action="">
<input type="hidden" name="del" id="banners-del-flag" value="0" />
<?php if( $banners > 0 ) { ?>
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        Прежде чем удалить позицию '<b><?=  $title ?></b>', укажите позицию к которой <br />
        будут относиться  все баннеры (<b><a href="<?= $this->adminLink('listing&pos='.$id); ?>" target="_blank"><?= $banners ?></a></b>) относившиеся к удаляемой позиции:
        <br />
        <select style="margin-top: 10px;" name="next">
            <option value="0">Выбрать</option>
            <?php foreach($positions as $v) { ?>
                <option value="<?= $v['id'] ?>"><?= $v['title'].'&nbsp;('.$v['sizes'].')' ?></option>
            <?php } ?>
        </select>
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger button delete" value="Удалить с заменой" />
        <?php if(FORDEV){ ?><input type="button" class="btn btn-danger button delete" value="Удалить позицию и баннеры" onclick="$('#banners-del-flag').val(1);" /><?php } ?>
        <input type="button" class="btn button cancel" onclick="history.back();" value="Отмена" />
    </td>
</tr>
</table>
<?php } else { ?>
<table class="admtbl tbledit">
<tr>
    <td class="row1">
        Вы действительно хотите удалить позицию '<b><?=  $title ?></b>'?
    </td>
</tr>
<tr class="footer">
    <td>
        <input type="submit" class="btn btn-danger button delete" value="Удалить" />
        <input type="button" class="btn button cancel" onclick="history.back();" value="Отмена" />
    </td>
</tr>
</table>
<?php } ?>
</form>