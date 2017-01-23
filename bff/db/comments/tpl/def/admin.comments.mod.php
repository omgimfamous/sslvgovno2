<?php
    /**
     * @var $this bff\db\Comments
     */
?>
<script type="text/javascript">
<?php js::start(); ?>
var jCommentsPgn = new bff.pgn('#j-comments-pagenation', {type:'prev-next', ajax:true, targetList: '#j-comments-list', targetPagenation: '#j-comments-pgn'});
var jCommentsMod = (function()
{
    var $progress;
    var _processing = false;
    
    $(function(){
        $progress = $('#j-comments-progress');
    });
    
    return {
        act: function(id, item_id, act)
        {
            if(_processing) return false;
            _processing = true;
            
            switch(act) {
                case 'd': { act = 'comment-delete';   } break;
                case 'm': { act = 'comment-moderate'; } break;
            }
            
            bff.ajax('<?= $this->urlListingAjax; ?>&group_id=<?= $this->groupID ?>&act='+act, {comment_id: id, item_id: item_id}, function(data) {
                if(data) {
                    var left = $('#comment_id_'+id).slideUp().siblings(':visible').length;
                    if(!left) jCommentsPgn.update();
                }
                _processing = false;
            }, $progress);
        },
        mass: function(act, extra)
        {
            var $c = $('input.j-comm-check:visible:checked');
            
            switch(act) {
                case 'd': { act = 'comment-delete-mass';   } break;
                case 'm': { act = 'comment-moderate-mass'; } break;
                case 'all': {
                    var $all = $('input.j-comm-check:visible');
                    if( ! $all.length) return false;
                    if( ! $c.length || $c.length < $all.length) {
                        $all.prop('checked', true); //доотмечаем неотмеченные
                        $(extra).prop('checked', true);
                    } else {
                        $all.prop('checked', false); //снимаем все
                        $(extra).prop('checked', false);
                    }
                    return false;
                } break;
            }
            
            if(!$c.length) {
                bff.error('Нет отмеченных комментариев');
                return false;
            } else {
                if(!bff.confirm('sure')) return false;
            }

            bff.ajax('<?= $this->urlListingAjax; ?>&act='+act+'&mass=1&group_id=<?= $this->groupID ?>', $c.serialize(), function(data) {
                if(data) {
                    var left = $c.parent().slideUp().siblings(':visible').length;
                    if(!left) jCommentsPgn.update();
                }
                _processing = false;
            }, $progress);
        }
    };
}());
<?php js::stop(); ?>
</script>

<div class="actionBar">
    <form action="" class="form-inline">
        <input type="checkbox" class="j-comm-check-all" id="j-comm-check-all" onclick="jCommentsMod.mass('all', this);" /> Отмеченные:&nbsp;
        <a href="#" onclick="jCommentsMod.mass('m'); return false;" class="clr-success ajax">Одобрить</a>, <label class="inline"><a href="#" onclick="jCommentsMod.mass('d'); return false;" class="clr-error ajax">Удалить</a></label>
        <div id="j-comments-progress" style="display:none;" class="progress"></div>
    </form>
</div>

<div class="comments" id="j-comments-list">
    <?= $list ?>
</div>

<form action="<?= $this->adminLink(null) ?>" method="get" name="pagenation" id="j-comments-pagenation">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="offset" value="<?= $offset ?>" />
</form>
<div class="pagenation" id="j-comments-pgn">
    <?= $pgn ?>
</div>
