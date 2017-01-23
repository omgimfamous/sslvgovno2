<?php
/**
 * @var $this BBS
 */
?>
<div id="popupBBSItemInfo" class="ipopup">
    <div class="ipopup-wrapper">
        <div class="ipopup-title">Информация об объявлении №<?= $id ?></div>
        <div class="ipopup-content" style="width:500px;">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title right" width="134">Пользователь:</td>
                    <td class="row2">
                        <?= ($user_id ? '<a href="#" onclick="return bff.userinfo('.$user_id.');" class="ajax">'.$user['email'].'</a>' : 'Аноним' ); ?>
                    </td>
                </tr>
                <? if( ! empty($shop)) { ?>
                <tr>
                    <td class="row1 field-title right">Магазин:</td>
                    <td>
                        <a href="<?= $shop['link'] ?>" target="_blank" class="but linkout"></a><a href="#" onclick="return bff.shopInfo(<?= $shop_id ?>);" class="ajax"><?= $shop['title'] ?></a>
                    </td>
                </tr>
                <? } ?>
                <tr>
                    <td class="row1 field-title right" style="vertical-align: top;">Категория:</td>
                    <td class="row2"><?
                        $i=0; $j=sizeof($cats_path);
                        foreach($cats_path as $v) {
                            echo '<a href="'.$this->adminLink('listing&cat='.$v['id']).'">'.$v['title'].'</a>'.(++$i < $j ? ' &raquo; ':'');
                        }
                    ?></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?
                            $aData['is_popup'] = true;
                            echo $this->viewPHP($aData, 'admin.form.status');
                        ?>
                    </td>
                </tr>
            </table>
            <div class="ipopup-content-bottom">
                <ul class="right">
                    <? if($blocked_num>1){ ?><li class="clr-error">блокировок: <?= $blocked_num; ?></li><? } ?>
                    <? if($claims_cnt){ ?><li><a href="<?= $this->adminLink('edit&tab=claims&id='.$id); ?>" class="text-error">жалобы (<?= $claims_cnt ?>)</a></li><? } ?>
                    <li><span class="post-date" title="дата создания"><?= tpl::date_format2($created); ?></span></li>
                    <li><a href="<?= $this->adminLink('edit&id='.$id); ?>" class="edit_s"> редактировать <span style="display:inline;" class="desc">#<?= $id ?></span></a></li>
                </ul>
            </div>
        </div>
    </div>
</div>