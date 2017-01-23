<script type="text/javascript">
{literal}
var jComments = null;
$(function() {      
    jComments = new bffAdmComments({
        url_ajax: '{/literal}{$aData.url_ajax}{literal}',
        group_id: '{/literal}{$aData.group_id}{literal}'
    },
    { total: $('#j-item-comments-total') } );
    jComments.setIdCommentLast( {/literal}{$aData.comments.nMaxIdComment}{literal} );
});
{/literal}
</script>              

<div class="actionBar" style="height:12px;">
    <div class="left">
        <span id="progress-comments" style="display:none;" class="progress"></span>
    </div>
    <div class="right desc">
        <a href="#" onclick="jComments.toggleCommentForm(0, true); $.scrollTo($('.reply-title'), {ldelim}duration: 800, offset: -250{rdelim}); return false;" style="margin-right:20px;" class="ajax">комментировать&darr;</a>
        {if $aData.tree}<a onclick="jComments.collapseNodeAll(); return false;" class="ajax" href="#">свернуть</a> /
        <a onclick="jComments.expandNodeAll(); return false;" class="ajax" href="#">развернуть</a>{/if}
        &nbsp;&nbsp;&nbsp;
        кол-во: <span class="bold" id="j-item-comments-total">{$aData.comments.total}</span></div>
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
{assign var="nesting" value="-1"}
{foreach from=$aData.comments.aComments item=v name=rublist}
    {assign var="cmtlevel" value=$v.level}
    {if $nesting < $cmtlevel}
    {elseif $nesting > $cmtlevel}
        {section name=closelist1 loop=`$nesting-$cmtlevel+1`}</div></div>{/section}
    {elseif not $smarty.foreach.rublist.first}
        </div></div>
    {/if}
    <div class="comment" id="comment_id_{$v.id}">
            {if $aData.tree}<img src="/img/admin/comment-close.gif" alt="+" title="свернуть/развернуть" class="folding" />{/if}
            <a name="comment{$v.id}" ></a>
            <div id="comment_content_id_{$v.id}" class="ccontent{if $v.deleted || $v.ublocked} del{/if}">
                <div class="tb"><div class="tl"><div class="tr"></div></div></div>
                <div class="ctext">{$v.message}</div>
                <div class="bl"><div class="bb"><div class="br"></div></div></div>
            </div>
            <div class="info">
                <ul>
                    <li><p>{if $v.user_id>0}<a href="#" onclick="return bff.userinfo({$v.user_id});" class="userlink author{if $v.ublocked} blocked{/if}">{$v.uname}</a>{else}{$v.name} ({$v.user_ip}){/if}</p></li>
                    <li class="date">{$v.created|date_format2:true}</li>
                    {if $aData.tree && (!$aData.tree2lvl || ($aData.tree2lvl && !$v.pid))}<li><a href="javascript: jComments.toggleCommentForm({$v.id});" class="reply-link ajax">Ответить</a></li>{/if}
                    <li><a href="#comment{$v.id}">Ссылка</a></li>
                    {if $v.pid}
                        <li class="goto-comment-parent"><a href="#comment{$v.pid}" title="Ответ на">&uarr;</a></li>
                    {/if}
                    <li>
                        {if $v.deleted}<span class="desc">{$aData.deltxt[$v.deleted]}</span>
                        {else}<a href="#" class="delete ajax" onclick="jComments.deleteComment(this,{$v.id}); return false;">Удалить</a>{/if}
                    </li>
                    {if !$v.moderated}<li class="mod"><a href="#" class="moderate ajax" onclick="jComments.moderateComment(this,{$v.id}); return false;">Одобрить</a></li>{/if}
                </ul>
            </div>
            <div class="reply" id="reply_{$v.id}" style="display: none;"></div>

            <div class="comment-children" id="comment-children-{$v.id}">
    {assign var="nesting" value=$cmtlevel}
    {if $smarty.foreach.rublist.last}
        {section name=closelist2 loop=`$nesting+1`}</div></div>{/section}
    {/if}
{foreachelse}
     <div class="alignCenter valignMiddle" id="comment-no" style="height:30px; padding-top:15px;">
        <span class="desc">нет комментариев</span>
     </div>
{/foreach}

    <span id="comment-children-0"></span>
</div>

<div class="reply-title" style="margin-top: 5px;"><a href="#" onclick="jComments.toggleCommentForm(0); return false;" class="ajax desc">добавить комментарий</a></div>
<div style="display: none;" id="reply_0" class="reply">
    <form action="" method="post" id="form_comment" onsubmit="return false;">
        <input type="hidden" name="reply" value="0" id="form_comment_reply" />
        <input type="hidden" name="group_id" value="{$aData.group_id}" />
        <textarea name="message" id="form_comment_text" style="height: 100px; margin-bottom:3px;" class="stretch"></textarea>
        <input type="button" class="btn btn-success btn-small button submit" value="добавить" onclick="jComments.addComment('#form_comment'); return false;" name="submit_comment" />
        <a href="#" onclick="jComments.toggleCommentForm(0,false); return false;" class="ajax cancel" style="margin-left: 5px;">отмена</a>
    </form>
</div>