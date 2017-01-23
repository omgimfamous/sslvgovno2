<?php

/**
 * @var $this bff\db\Comments
 */

foreach($comments as $v):

    $commentID = $v['id'];
    ?>
    <div class="comment" id="comment_id_<?= $commentID ?>">
        <input type="checkbox" name="c[]" value="<?= $commentID ?>" class="check j-comm-check" />
        <a name="comment<?= $commentID ?>" ></a>
        <div id="comment_content_id_<?= $commentID ?>" class="ccontent<?php if($v['deleted'] || $v['ublocked']){ ?> del<?php } ?>">
            <div class="tb"><div class="tl"><div class="tr"></div></div></div>
            <div class="ctext"><?= $v['message'] ?></div>
            <div class="bl"><div class="bb"><div class="br"></div></div></div>
        </div>
        <div class="info">
            <ul>
                <li><p><?php if($v['user_id']>0){ ?><a href="#" onclick="return bff.userinfo(<?= $v['user_id'] ?>);" class="userlink author<?php if($v['ublocked']){ ?> blocked<?php } ?>"><?= $v['uname'] ?></a><?php } else { echo $v['name'].' ('.$v['user_ip'].')'; } ?></p></li>
                <li class="date"><?= tpl::date_format2($v['created'], true); ?></li>
                <li><a href="<?= $this->urlListing.$v['item_id'].'#comment'.$commentID ?>">Ссылка</a></li>
                <li><a href="#" class="delete ajax" onclick="jCommentsMod.act(<?= $commentID ?>, <?= $v['item_id'] ?>, 'd'); return false;">Удалить</a></li>
                <li class="mod"><a href="#" class="moderate ajax" onclick="jCommentsMod.act(<?= $commentID ?>, <?= $v['item_id'] ?>, 'm'); return false;">Одобрить</a></li>
            </ul>
            
        </div>
    </div>
<?php
endforeach;

if(empty($comments)): ?>
 <div class="alignCenter valignMiddle" id="comment-no" style="height:30px; padding-top:15px;">
    <span class="desc">нет комментариев на модерацию</span>
 </div><?php
endif;