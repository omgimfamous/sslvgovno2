<?php
/**
 * @var $this Shops
 */
?>
<div id="j-s-info-popup" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Информация о магазине №<?= $id ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title right" width="134">Владелец:</td>
                    <td class="row2">
                        <?= ($user_id ? '<a href="#"  class="ajax'.($user['blocked'] ? ' text-error':'').'" onclick="return bff.userinfo('.$user_id.');">'.$user['email'].'</a>' : '<i>не указан</i>' ); ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title right" width="134">Название:</td>
                    <td class="row2">
                        <a class="linkout but" href="<?= $link ?>" target="_blank"></a><?= $title ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?
                            $aData['is_popup'] = true;
                            echo $this->viewPHP($aData, 'admin.shops.form.status');
                        ?>
                    </td>
                </tr>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <li><span class="post-date" title="дата создания магазина"><?= tpl::date_format2($created, true); ?></span></li>
                    <? if($claims_cnt){ ?><li><a href="<?= $this->adminLink('edit&tab=claims&id='.$id); ?>" class="text-error">жалобы (<?= $claims_cnt ?>)</a></li><? } ?>
                    <li><a href="<?= $this->adminLink('listing&status=7&shopid='.$id, 'bbs'); ?>"> объявления (<?= $items ?>)</a></li>
                    <li><a href="<?= $this->adminLink('edit&id='.$id); ?>" class="edit_s"> редактировать <span style="display:inline;" class="desc">#<?= $id ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>