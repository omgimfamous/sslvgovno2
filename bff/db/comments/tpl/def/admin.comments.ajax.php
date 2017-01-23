<?php
    /**
     * @var $this bff\db\Comments
     */
?>
<?php if($this->commentsTree){ ?><img src="/img/admin/comment-close.gif" alt="+" title="свернуть/развернуть" class="folding" style="display: none;"/><?php } ?>
<a name="comment<?= $id ?>" ></a>
<div id="comment_content_id_<?= $id ?>" class="ccontent <?php if($deleted || $ublocked){ ?> del<?php } if($this->security->isCurrentUser($user_id)){ ?> self<?php } ?>">
    <div class="tb"><div class="tl"><div class="tr"></div></div></div>
    <div class="ctext"><?= $message ?></div>
    <div class="bl"><div class="bb"><div class="br"></div></div></div>
</div>
<div class="info">
    <ul>
        <li><p><a href="#" onclick="return bff.userinfo(<?= $user_id ?>);" class="userlink author<?php if($ublocked){ ?> blocked<?php } ?>"><?= $name ?></a></p></li>
        <li class="date"><?= tpl::date_format2($created, true); ?></li>
        <?php if($this->commentsTree && ( ! $this->commentsTree2Levels || ($this->commentsTree2Levels && ! $pid))){ ?><li><a href="#" onclick="jComments.toggleCommentForm(<?= $id ?>); return false;" class="reply-link ajax">Ответить</a></li><?php } ?>
        <li><a href="#comment<?= $id ?>">Ссылка</a></li>
        <?php if($pid) { ?>
            <li class="goto-comment-parent"><a href="#comment<?= $pid ?>" title="Ответ на">&uarr;</a></li>
        <?php } ?>
        <li>
            <?php if($deleted){ ?><span class="desc"><?= $this->commentHideReasons[$deleted] ?></span>
            <?php } else { ?><a href="#" class="delete ajax" onclick="jComments.deleteComment(this,<?= $id ?>); return false;">Удалить</a><?php } ?>
        </li>
        <?php if( ! $moderated) { ?><li class="mod"><a href="#" class="moderate ajax" onclick="jComments.moderateComment(this,<?= $id ?>); return false;">Одобрить</a></li><?php } ?>
    </ul>
</div>
<div class="reply" id="reply_<?= $id ?>" style="display: none;"></div>
<div class="comment-children" id="comment-children-<?= $id ?>"></div>