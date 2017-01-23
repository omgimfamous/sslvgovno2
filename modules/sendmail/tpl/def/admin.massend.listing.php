<?php

?>
<script type="text/javascript">
    function delMassend(id, link)
    {
        bff.ajaxDelete('Удалить рассылку?', id, '<?= $this->adminLink('ajax&act=massend-delete') ?>', link);
        return false;
    }
    function infoMassend(id)
    {
        $.fancybox('', {ajax:true, href:'<?= $this->adminLink('ajax&act=massend-info&id=') ?>'+id});
        return false;
    }
</script>

<table class="table table-condensed table-hover admtbl tblhover">
    <thead>
        <tr class="header">
            <th width="60">ID</th>
            <th>Получателей</th>
            <th>Отправлено</th>
            <th>Начало</th>
            <th>Окончание</th>
            <th width="70"></th>
        </tr>
    </thead>
    <? foreach($items as $k=>$v) { $ID = $v['id']; ?>
    <tr class="row<?= $k%2 ?>" id="ms<?= $ID ?>">
        <td><?= $ID ?></td>
        <td><span><?= $v['total'] ?></span></td>
        <td><span class="clr-success"><?= $v['success'] ?></span><span class="desc"> / </span><span class="clr-error"><?= $v['fail'] ?></span></td>
        <td><?= tpl::date_format2($v['started'], true) ?></td>
        <td><? if(!$v['status']) { ?>незавершена<? } else { echo tpl::date_format2($v['finished'], true); } ?></td>
        <td>
            <a class="but edit" title="Подробности" href="#" onclick="return infoMassend(<?= $ID ?>);"></a>
            <a class="but del" title="Удалить" href="#" onclick="return delMassend(<?= $ID ?>, this);"></a>
        </td>
    </tr>
    <? } if( empty($items) ) { ?>
    <tr class="norecords">
        <td colspan="6">нет рассылок</td>
    </tr>
    <? } ?>
</table>