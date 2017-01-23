<?php
    /**
     * @var $this Bbs
     */
    $statusList = $this->itemsImport()->getStatusList();

    foreach ($list as $k=>&$v):
    $id = $v['id']; ?>
    <tr class="row<?= ($k%2) ?>">
        <td class="small"><?= $id ?></td>
        <td class="left"><?= $v['cat_title'] ?></td>
        <td><span title="обработано"><?= $v['items_processed'] ?></span> / <span title="всего объявлений"><?= $v['items_total'] ?></span></td>
        <td class="left"><?= $v['comment_text'] ?></td>
        <td><span title="<?= tpl::date_format2($v['status_changed'], true, true) ?>"><?= $statusList[$v['status']] ?></span></td>
        <td><?= tpl::date_format2($v['created'], true, true) ?></td>
        <td>
            <a href="<?= $v['filename'] ?>" target="_blank" title="Скачать" class="but icon icon-download"></a>
            <a href="#" onclick="return jBbsImportsList.importInfo(<?= $id ?>);" class="but sett" title="Информация"></a>
            <a href="#" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="but userlink" title="Пользователь" style="padding:0px;"></a>
            <?php
               if(in_array($v['status'], array(BBSItemsImport::STATUS_WAITING,BBSItemsImport::STATUS_PROCESSING)) && $v['is_admin'] > 0){ ?>
                <a class="but del item-del" href="#" title="Отменить" rel="<?= $id ?>"></a>
            <? } else { ?>
                <a class="but" href="javascript:void(0);"></a>
            <? } ?>
        </td>
    </tr>
<? endforeach; unset($v);

if (empty($list) && ! isset($skip_norecords)): ?>
    <tr class="norecords">
        <td colspan="7">
            ничего не найдено
        </td>
    </tr>
<? endif;