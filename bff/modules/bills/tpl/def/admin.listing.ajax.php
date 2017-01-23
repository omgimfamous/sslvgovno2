<?php

$aStatusText = array(
    Bills::STATUS_WAITING    => '<span class="clr-error">незавершен</span>',
    Bills::STATUS_COMPLETED  => '<span style="color:green;">завершен',
    Bills::STATUS_PROCESSING => '<span style="color:darkorange;">обрабатывается</span>',
    Bills::STATUS_CANCELED   => '<span style="color:#666;">отменен</span>',
    'activated'              => '<span style="color:green;">завершен</span>',
);

foreach($bills as $k=>$v) 
{
    $k = $k%2;
    $status = $v['status'];
    ?>
<tr class="row<?= $k ?>" style="color: #666666;" id="tr<?= $v['id'] ?>">
    <td><?= $v['id'] ?></td>
    <td><?= tpl::date_format2($v['created'], true) ?></td>
    <td><?php if($v['user_id'] > 0){ ?><a href="#" onclick="return bff.userinfo(<?= $v['user_id'] ?>);"><?= $v['email'] ?></a><?php } else { ?><span class="decs"><?= $v['ip'] ?></span><?php } ?></td>
    <td><?= $v['user_balance'] ?></td>
    <td><?= ($v['type'] == Bills::TYPE_OUT_SERVICE ? ( !$v['amount'] ? '0' : '<span class="clr-error">–&nbsp;'.$v['amount'].'</span>' ) : '<span class="bold green">+&nbsp;'.$v['amount'].'</span>') ?></td>
    <td><span class="bill"><?php if(false && !empty($v['details'])){ ?><a href="#" class="ajax" onclick="$(this).next().toggleClass('displaynone'); return false;"><?= $v['description'] ?></a><span class="desc displaynone"><br /><?= $v['details'] ?></span><?php } else { echo $v['description']; } ?></span></td>
    <td id="tr<?= $v['id'] ?>_status">
        <?php if($v['status'] == Bills::STATUS_WAITING) { ?>
            <a href="#" class="ajax" onclick="jBills.changeStatusShow(<?= $k ?>, '<?= $v['amount'] ?>', <?= $v['id'] ?>, <?= $v['user_id'] ?>, '<?= $v['email'] ?>'); return false;"><?= $aStatusText[$status]; ?></a>
        <?php } elseif($v['status'] == Bills::STATUS_COMPLETED) { ?>
            <?php if( $v['type'] == Bills::TYPE_OUT_SERVICE ) { echo $aStatusText['activated']; } else { ?>
            <?= $aStatusText[$status]; ?><br /><?= tpl::date_format2($v['payed'], true) ?>
        <?php } } elseif($v['status'] == Bills::STATUS_PROCESSING) { ?>
            <a href="#" class="ajax" onclick="jBills.changeStatusShow(<?= $k ?>, '<?= $v['amount'] ?>', <?= $v['id'] ?>, <?= $v['user_id'] ?>, '<?= $v['email'] ?>'); return false;"><?= $aStatusText[$status]; ?> </a>
        <?php } elseif($v['status'] == Bills::STATUS_CANCELED) { echo $aStatusText[$status]; } ?>
    </td>
</tr>
<?php
}
if(empty($bills)) 
{ ?>
<tr class="norecords">
    <td colspan="7"><div style="margin:15px 0;">ничего не найдено</div></td>
</tr>
<?php }