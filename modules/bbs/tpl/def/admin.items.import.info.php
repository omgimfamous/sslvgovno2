<?php
/**
 * @var $this BBS
 * @var $sett_user array
 * @var $sett_shop array
 */
 extract($settings, EXTR_PREFIX_ALL | EXTR_REFS, 'sett');
?>
<div id="popupBBSItemImportInfo" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Информация об импорте №<?= $id ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <th width="134" style="height: 1px;"></th>
                    <th width="12" style="height: 1px;"></th>
                    <th style="height: 1px;"></th>
                </tr>
                <tr>
                    <td class="row1 field-title right">Категория:</td>
                    <td></td>
                    <td class="row2">
                        <?= $settings['cat_title'] ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right">Пользователь:</td>
                    <td></td>
                    <td class="row2">
                        <a href="#" onclick="return bff.userinfo(<?= $user_id ?>);" class="ajax<? if($user['blocked']){ ?> clr-error<? } ?>"><?= $user['email'] ?></a>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right">Объявления:</td>
                    <td></td>
                    <td class="row2">
                        <span class="desc">всего:&nbsp;</span><span><?= $items_total ?></span>
                        <span class="desc">обработано:&nbsp;</span><span class="text-success"><?= $items_processed ?></span>
                        <? if($items_ignored > 0){ ?><span class="desc">пропущено:&nbsp;</span><span class="text-error"><?= $items_ignored ?></span><? } ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="3"><hr class="cut" /></td>
                </tr>
                <tr>
                    <td class="row1 field-title bold right">Настройки импорта:</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="row1 field-title right">Пользователь:</td>
                    <td></td>
                    <td class="row2">
                        <a href="#" onclick="return bff.userinfo(<?= $sett_user['user_id'] ?>);" class="ajax"><?= $sett_user['email'] ?></a>
                    </td>
                </tr>
                <? if ( ! empty($sett_shop)) { ?>
                <tr>
                    <td class="row1 field-title right">Магазин:</td>
                    <td></td>
                    <td class="row2">
                        <a href="#" onclick="return bff.shopInfo(<?= $sett_shop['id'] ?>);" class="ajax"><?= $sett_shop['title'] ?></a>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td class="row1 field-title right">Статус объявлений:</td>
                    <td></td>
                    <td class="row2">
                        <?php
                            if ($sett_state === BBS::STATUS_PUBLICATED) echo 'опубликованы';
                            elseif ($sett_state === BBS::STATUS_PUBLICATED_OUT) echo 'сняты с публикации';
                        ?>
                    </td>
                </tr>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <li><span class="desc"><?= $user_ip; ?></span></li>
                    <li>статус: <span class="bold"><?= $status_title; ?></span></li>
                    <li><span class="post-date" title="дата создания"><?= tpl::date_format2($created, true); ?></span></li>
                </ul>
            </div>
        </div>
    </div>
</div>