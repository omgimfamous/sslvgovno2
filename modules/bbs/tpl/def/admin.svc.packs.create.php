<?php

?>
<form method="post" action="" name="jSvcForm" id="j-bbs-svc-pack-form">
    <table class="admtbl tbledit">
    <tr class="required">
        <td class="row1" width="70"><span class="field-title">Название:</span></td>
        <td class="row2">
             <input type="text" name="title" class="stretch" value="<?= $title; ?>" />
        </td>
    </tr>
    <tr class="required">
        <td class="row1"><span class="field-title">Keyword:</span></td>
        <td class="row2">
             <input type="text" name="keyword" class="stretch" maxlength="45" value="<?= $keyword; ?>" />
        </td>
    </tr>
    <tr class="footer">
        <td colspan="2" class="row1">
            <input type="submit" class="btn btn-success button submit" value="Сохранить" />
            <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
        </td>
    </tr>
    </table>
</form>

<script type="text/javascript">
$(function(){
    new bff.formChecker( $('#j-bbs-svc-pack-form') );
});
</script>