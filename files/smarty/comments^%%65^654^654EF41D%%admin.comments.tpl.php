<?php /* Smarty version 2.6.7, created on 2017-01-15 22:16:52
         compiled from admin.comments.tpl */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'date_format2', 'admin.comments.tpl', 58, false),)), $this); ?>
<script type="text/javascript">
<?php echo '
var jComments = null;
$(function() {      
    jComments = new bffAdmComments({
        url_ajax: \'';  echo $this->_tpl_vars['aData']['url_ajax'];  echo '\',
        group_id: \'';  echo $this->_tpl_vars['aData']['group_id'];  echo '\'
    },
    { total: $(\'#j-item-comments-total\') } );
    jComments.setIdCommentLast( ';  echo $this->_tpl_vars['aData']['comments']['nMaxIdComment'];  echo ' );
});
'; ?>

</script>              

<div class="actionBar" style="height:12px;">
    <div class="left">
        <span id="progress-comments" style="display:none;" class="progress"></span>
    </div>
    <div class="right desc">
        <a href="#" onclick="jComments.toggleCommentForm(0, true); $.scrollTo($('.reply-title'), {duration: 800, offset: -250}); return false;" style="margin-right:20px;" class="ajax">комментировать&darr;</a>
        <?php if ($this->_tpl_vars['aData']['tree']): ?><a onclick="jComments.collapseNodeAll(); return false;" class="ajax" href="#">свернуть</a> /
        <a onclick="jComments.expandNodeAll(); return false;" class="ajax" href="#">развернуть</a><?php endif; ?>
        &nbsp;&nbsp;&nbsp;
        кол-во: <span class="bold" id="j-item-comments-total"><?php echo $this->_tpl_vars['aData']['comments']['total']; ?>
</span></div>
</div>

<div class="comments">
    <div class="update" id="update">
        <div class="tl"></div>
        <div class="wrapper">
            <div class="refresh">
                <img class="update-comments" id="update-comments" alt="" src="/img/admin/comment-update.gif" onclick="jComments.responseNewComment(this); return false;"/>
            </div>
            <div class="new-comments" id="new-comments" style="display: none;" onclick="jComments.goNextComment();"></div>
        </div>
        <div class="bl"></div>
    </div>
<?php $this->assign('nesting', "-1"); ?>
<?php $this->_foreach['rublist'] = array('total' => count($_from = (array)$this->_tpl_vars['aData']['comments']['aComments']), 'iteration' => 0);
if ($this->_foreach['rublist']['total'] > 0):
    foreach ($_from as $this->_tpl_vars['v']):
        $this->_foreach['rublist']['iteration']++;
?>
    <?php $this->assign('cmtlevel', $this->_tpl_vars['v']['level']); ?>
    <?php if ($this->_tpl_vars['nesting'] < $this->_tpl_vars['cmtlevel']): ?>
    <?php elseif ($this->_tpl_vars['nesting'] > $this->_tpl_vars['cmtlevel']): ?>
        <?php unset($this->_sections['closelist1']);
$this->_sections['closelist1']['name'] = 'closelist1';
$this->_sections['closelist1']['loop'] = is_array($_loop=($this->_tpl_vars['nesting']-$this->_tpl_vars['cmtlevel']+1)) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['closelist1']['show'] = true;
$this->_sections['closelist1']['max'] = $this->_sections['closelist1']['loop'];
$this->_sections['closelist1']['step'] = 1;
$this->_sections['closelist1']['start'] = $this->_sections['closelist1']['step'] > 0 ? 0 : $this->_sections['closelist1']['loop']-1;
if ($this->_sections['closelist1']['show']) {
    $this->_sections['closelist1']['total'] = $this->_sections['closelist1']['loop'];
    if ($this->_sections['closelist1']['total'] == 0)
        $this->_sections['closelist1']['show'] = false;
} else
    $this->_sections['closelist1']['total'] = 0;
if ($this->_sections['closelist1']['show']):

            for ($this->_sections['closelist1']['index'] = $this->_sections['closelist1']['start'], $this->_sections['closelist1']['iteration'] = 1;
                 $this->_sections['closelist1']['iteration'] <= $this->_sections['closelist1']['total'];
                 $this->_sections['closelist1']['index'] += $this->_sections['closelist1']['step'], $this->_sections['closelist1']['iteration']++):
$this->_sections['closelist1']['rownum'] = $this->_sections['closelist1']['iteration'];
$this->_sections['closelist1']['index_prev'] = $this->_sections['closelist1']['index'] - $this->_sections['closelist1']['step'];
$this->_sections['closelist1']['index_next'] = $this->_sections['closelist1']['index'] + $this->_sections['closelist1']['step'];
$this->_sections['closelist1']['first']      = ($this->_sections['closelist1']['iteration'] == 1);
$this->_sections['closelist1']['last']       = ($this->_sections['closelist1']['iteration'] == $this->_sections['closelist1']['total']);
?></div></div><?php endfor; endif; ?>
    <?php elseif (! ($this->_foreach['rublist']['iteration'] <= 1)): ?>
        </div></div>
    <?php endif; ?>
    <div class="comment" id="comment_id_<?php echo $this->_tpl_vars['v']['id']; ?>
">
            <?php if ($this->_tpl_vars['aData']['tree']): ?><img src="/img/admin/comment-close.gif" alt="+" title="свернуть/развернуть" class="folding" /><?php endif; ?>
            <a name="comment<?php echo $this->_tpl_vars['v']['id']; ?>
" ></a>
            <div id="comment_content_id_<?php echo $this->_tpl_vars['v']['id']; ?>
" class="ccontent<?php if ($this->_tpl_vars['v']['deleted'] || $this->_tpl_vars['v']['ublocked']): ?> del<?php endif; ?>">
                <div class="tb"><div class="tl"><div class="tr"></div></div></div>
                <div class="ctext"><?php echo $this->_tpl_vars['v']['message']; ?>
</div>
                <div class="bl"><div class="bb"><div class="br"></div></div></div>
            </div>
            <div class="info">
                <ul>
                    <li><p><?php if ($this->_tpl_vars['v']['user_id'] > 0): ?><a href="#" onclick="return bff.userinfo(<?php echo $this->_tpl_vars['v']['user_id']; ?>
);" class="userlink author<?php if ($this->_tpl_vars['v']['ublocked']): ?> blocked<?php endif; ?>"><?php echo $this->_tpl_vars['v']['uname']; ?>
</a><?php else:  echo $this->_tpl_vars['v']['name']; ?>
 (<?php echo $this->_tpl_vars['v']['user_ip']; ?>
)<?php endif; ?></p></li>
                    <li class="date"><?php echo ((is_array($_tmp=$this->_tpl_vars['v']['created'])) ? $this->_run_mod_handler('date_format2', true, $_tmp, true) : smarty_modifier_date_format2($_tmp, true)); ?>
</li>
                    <?php if ($this->_tpl_vars['aData']['tree'] && ( ! $this->_tpl_vars['aData']['tree2lvl'] || ( $this->_tpl_vars['aData']['tree2lvl'] && ! $this->_tpl_vars['v']['pid'] ) )): ?><li><a href="javascript: jComments.toggleCommentForm(<?php echo $this->_tpl_vars['v']['id']; ?>
);" class="reply-link ajax">Ответить</a></li><?php endif; ?>
                    <li><a href="#comment<?php echo $this->_tpl_vars['v']['id']; ?>
">Ссылка</a></li>
                    <?php if ($this->_tpl_vars['v']['pid']): ?>
                        <li class="goto-comment-parent"><a href="#comment<?php echo $this->_tpl_vars['v']['pid']; ?>
" title="Ответ на">&uarr;</a></li>
                    <?php endif; ?>
                    <li>
                        <?php if ($this->_tpl_vars['v']['deleted']): ?><span class="desc"><?php echo $this->_tpl_vars['aData']['deltxt'][$this->_tpl_vars['v']['deleted']]; ?>
</span>
                        <?php else: ?><a href="#" class="delete ajax" onclick="jComments.deleteComment(this,<?php echo $this->_tpl_vars['v']['id']; ?>
); return false;">Удалить</a><?php endif; ?>
                    </li>
                    <?php if (! $this->_tpl_vars['v']['moderated']): ?><li class="mod"><a href="#" class="moderate ajax" onclick="jComments.moderateComment(this,<?php echo $this->_tpl_vars['v']['id']; ?>
); return false;">Одобрить</a></li><?php endif; ?>
                </ul>
            </div>
            <div class="reply" id="reply_<?php echo $this->_tpl_vars['v']['id']; ?>
" style="display: none;"></div>

            <div class="comment-children" id="comment-children-<?php echo $this->_tpl_vars['v']['id']; ?>
">
    <?php $this->assign('nesting', $this->_tpl_vars['cmtlevel']); ?>
    <?php if (($this->_foreach['rublist']['iteration'] == $this->_foreach['rublist']['total'])): ?>
        <?php unset($this->_sections['closelist2']);
$this->_sections['closelist2']['name'] = 'closelist2';
$this->_sections['closelist2']['loop'] = is_array($_loop=($this->_tpl_vars['nesting']+1)) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['closelist2']['show'] = true;
$this->_sections['closelist2']['max'] = $this->_sections['closelist2']['loop'];
$this->_sections['closelist2']['step'] = 1;
$this->_sections['closelist2']['start'] = $this->_sections['closelist2']['step'] > 0 ? 0 : $this->_sections['closelist2']['loop']-1;
if ($this->_sections['closelist2']['show']) {
    $this->_sections['closelist2']['total'] = $this->_sections['closelist2']['loop'];
    if ($this->_sections['closelist2']['total'] == 0)
        $this->_sections['closelist2']['show'] = false;
} else
    $this->_sections['closelist2']['total'] = 0;
if ($this->_sections['closelist2']['show']):

            for ($this->_sections['closelist2']['index'] = $this->_sections['closelist2']['start'], $this->_sections['closelist2']['iteration'] = 1;
                 $this->_sections['closelist2']['iteration'] <= $this->_sections['closelist2']['total'];
                 $this->_sections['closelist2']['index'] += $this->_sections['closelist2']['step'], $this->_sections['closelist2']['iteration']++):
$this->_sections['closelist2']['rownum'] = $this->_sections['closelist2']['iteration'];
$this->_sections['closelist2']['index_prev'] = $this->_sections['closelist2']['index'] - $this->_sections['closelist2']['step'];
$this->_sections['closelist2']['index_next'] = $this->_sections['closelist2']['index'] + $this->_sections['closelist2']['step'];
$this->_sections['closelist2']['first']      = ($this->_sections['closelist2']['iteration'] == 1);
$this->_sections['closelist2']['last']       = ($this->_sections['closelist2']['iteration'] == $this->_sections['closelist2']['total']);
?></div></div><?php endfor; endif; ?>
    <?php endif; ?>
<?php endforeach; else: ?>
     <div class="alignCenter valignMiddle" id="comment-no" style="height:30px; padding-top:15px;">
        <span class="desc">нет комментариев</span>
     </div>
<?php endif; unset($_from); ?>

    <span id="comment-children-0"></span>
</div>

<div class="reply-title" style="margin-top: 5px;"><a href="#" onclick="jComments.toggleCommentForm(0); return false;" class="ajax desc">добавить комментарий</a></div>
<div style="display: none;" id="reply_0" class="reply">
    <form action="" method="post" id="form_comment" onsubmit="return false;">
        <input type="hidden" name="reply" value="0" id="form_comment_reply" />
        <input type="hidden" name="group_id" value="<?php echo $this->_tpl_vars['aData']['group_id']; ?>
" />
        <textarea name="message" id="form_comment_text" style="height: 100px; margin-bottom:3px;" class="stretch"></textarea>
        <input type="button" class="btn btn-success btn-small button submit" value="добавить" onclick="jComments.addComment('#form_comment'); return false;" name="submit_comment" />
        <a href="#" onclick="jComments.toggleCommentForm(0,false); return false;" class="ajax cancel" style="margin-left: 5px;">отмена</a>
    </form>
</div>