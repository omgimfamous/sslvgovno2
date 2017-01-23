<?php

$attach = $this->attach();

foreach($list as $v){ ?>
<tr>
    <td width="20" style="vertical-align:top;"><div class="im-conv-tri-<?= (!$v['my']?'from':'to') ?>"></div></td>
    <td style="padding-right: 20px; vertical-align:top;" <? if(!$v['my']){ ?>class="from"<? } ?>>
        <strong><?= ($v['my'] ? $name : $i['name']) ?></strong><span class="desc small"> <?= tpl::date_format2($v['created'],true); ?>:</span><br />
        <?= $v['message'] ?>
        <? if(InternalMail::attachmentsEnabled() && ! empty($v['attach'])) {
            echo '<br />'.$attach->getAttachLink($v['attach']);
        } ?>
    </td>
</tr>
<? }

if( empty($list) ) { ?>
<tr class="norecords">
    <td colspan="2">не найдено ни одного сообщения</td>
</tr>
<? }