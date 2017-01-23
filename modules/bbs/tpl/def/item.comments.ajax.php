<?php
/**
 * Комментарии объявления: список
 * @var $this BBS
 * @var $comments array список комментариев (данные)
 * @var $commentsAnswers boolean список ответов
 * @var $itemID integer ID объявления
 * @var $itemUserID integer ID автора объявления
 * @var $itemStatus integer статус объявления
 */
$nPerPage = config::sys('bbs.comments.collapse'.(!empty($commentsAnswers)?'.answers':''), 10);
$nUserID = User::id();
$bAllowAdd = ($itemStatus == BBS::STATUS_PUBLICATED && $nUserID);
$aHideReasons = $this->itemComments()->getHideReasons();

$lang_you = '';//_t('comments', '(Это Вы)');
$lang_you_delete = _t('comments', 'Вы удалили этот комментарий');
$lang_answer = _t('comments', 'Ответить');
$lang_delete = _t('comments', 'Удалить');
$lang_cancel = _t('', 'Отмена');
$lang_date = _t('comments', 'd.m.Y в H:i');

$nCnt = 0;
foreach ($comments as $v):
    $nCnt++;
    $bLevelOne = ($v['numlevel'] <= 1);
    $bCommentOwner = ($nUserID == $v['user_id']);
    $bAllowDelete = ($bAllowAdd && $bCommentOwner);
    $bAllowAnswer = ($bAllowAdd && $bLevelOne);
    ?>

    <? if ($bLevelOne): ?><li class="l-commentsList-item media j-comment-block<?= ($nCnt > $nPerPage ? ' hide' : '') ?>">
    <? else: ?><div class="l-commentsList-item-answer j-comment-block j-comment-block-answer<?= ($nCnt > $nPerPage ? ' hide' : '') ?>">
    <? endif; ?>

        <a href="<?= Users::url('user.profile', array('login' => $v['login'])) ?>" class="l-commentsList-item-avatar">
            <img src="<?= UsersAvatar::url($v['user_id'], $v['avatar'], UsersAvatar::szNormal, $v['sex']) ?>" alt="" />
        </a>
        <div class="media-body">
            <div class="l-commentsList-l-author">
                <strong><a href="<?= Users::url('user.profile', array('login' => $v['login'])) ?>"><?= $v['name'] ?></a><?= ($bCommentOwner ? ' '.$lang_you: '') ?></strong>
                <span class="l-commentsList-item-date"><?= tpl::date_format_pub($v['created'], $lang_date) ?></span>
            </div>

            <? if ($v['deleted']): ?>
            <div class="alert alert-default mrgb0">
                <? switch ($v['deleted']):
                    case BBSItemComments::commentDeletedByItemOwner:
                        echo ( $itemUserID == $nUserID ? $lang_you_delete : $aHideReasons[$v['deleted']] );
                        break;
                    case BBSItemComments::commentDeletedByCommentOwner:
                        echo ( $bCommentOwner ? $lang_you_delete : $aHideReasons[$v['deleted']] );
                        break;
                    default:
                        echo $aHideReasons[$v['deleted']];
                   endswitch; ?>
            </div>
            <? else: ?>
            <div class="j-comment">
                <div class="l-commentsList-item-text">
                    <?= $v['message'] ?>
                </div>

                <div class="l-commentsList-item-controls j-comment-actions">
                    <? if($bAllowAnswer){ ?><a href="#" class="ajax j-comment-add"><?= $lang_answer ?></a><? } ?>
                    <? if($bAllowDelete){ ?><a href="#" class="ajax ico red j-comment-delete" data-id="<?= $v['id'] ?>"><i class="fa fa-times"></i> <span><?= $lang_delete ?></span></a><? } ?>
                </div>

                <? if ($bAllowAnswer): ?>
                <div class="l-commentsList-item-answerForm hide">
                    <form role="form" class="form j-comment-add-form" method="post" action="">
                        <input type="hidden" name="item_id" value="<?= $itemID ?>" />
                        <input type="hidden" name="parent" value="<?= $v['id'] ?>" />
                        <div class="controls j-required">
                            <textarea rows="3" name="message" class="span12"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm j-submit"><?= $lang_answer ?></button>
                        <a href="#" class="btn btn-default btn-sm j-comment-cancel"><?= $lang_cancel ?></a>
                    </form>
                </div>
                <? endif; ?>
            </div>
            <? endif; ?>
        </div>

        <? if (!empty($v['sub'])):
            # Ответы на комментарий:
            echo $this->commentsList(array(
                'comments'   => $v['sub'],
                'commentsAnswers' => true,
                'itemID'     => $itemID,
                'itemUserID' => $itemUserID,
                'itemStatus' => $itemStatus,
            ));
        endif; ?>

    <? if($bLevelOne): ?></li>
    <? else: ?></div>
    <? endif; ?>

<? endforeach;

if ($nCnt > $nPerPage): ?>
    <? if (!empty($commentsAnswers)): ?>
    <div class="l-commentsList-item-showall j-comments-more-block">
        <a href="#" class="ajax j-comments-more" data-answers="1"><?=  _t('comments', 'Показать все ответы') ?></a>
    </div>
    <? else: ?>
    <li class="l-commentsList-item-more j-comments-more-block">
        <a href="#" class="ajax j-comments-more" data-answers="0"><?= _t('comments', 'Еще комментарии ([num])', array('num'=>(count($comments)-$nPerPage))); ?></a>
        <div class="c-spacer10 visible-xs"></div>
    </li>
    <? endif; ?>
<? endif;