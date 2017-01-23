<?php
    /**
     * Список тегов
     * @var $this bff\db\Tags
     */
?>
<?= tplAdmin::blockStart($this->lang['add_title'], false, array('id'=>'jTagFormBlock','style'=>'display:none')) ?>
    <form action="" method="post" id="jTagForm">
        <input type="hidden" name="act" value="add-finish" />
        <input type="hidden" name="filter" value="<?= HTML::escape($filter) ?>" />
        <table class="admtbl tbledit">
            <tbody class="form_params"><?= $form ?></tbody>
            <tbody>
                <tr class="footer">
                    <td colspan="2">
                        <input type="submit" class="btn btn-success button submit" value="<?= _t('', 'Сохранить') ?>" />
                        <input type="reset" class="btn button cancel" value="<?= _t('', 'Отмена') ?>" onclick="jTags.toggle('cancel');" />
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart($this->lang['list'], true, array(), array('title'=>'+ добавить','class'=>'ajax','onclick'=>'return jTags.toggle(\'toggle\');')) ?>
    <form action="" id="jTagsFilter">
        <input type="hidden" name="page" value="<?= $page ?>" id="j-tags-page" />
    </form>

    <table class="table table-condensed table-hover admtbl tblhover">
        <thead>
            <tr class="header">
                <?php if(FORDEV){ ?><th width="50">ID</th><?php } ?>
                <th class="left">Название<div class="progress" style="display:none; margin-left: 5px;" id="jTagsProgress"></div></th>
                <?php if($this->postModerationEnabled()) { ?><th width="100">Модерация</th><?php } ?>
                <?php if( ! empty($this->urlItemsListing)) { ?><th width="100">Записи</th><?php } ?>
                <th width="50">Действие</th>
            </tr>
        </thead>
        <tbody id="jTagsList"><?= $list ?></tbody>
    </table>
    <div id="jTagsPgn"><?= $pgn ?></div>
<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
<?php tpl::includeJS(array('autocomplete'), true); ?>
var jTags = (function(){
    var $progress, $block, $blockCaption, form, formClean, fChecker, $list, $listPgn;
    var urlAjax = '<?= $this->adminLink(bff::$event.'&act=', bff::$class) ?>';
    var $formFilter, _processing = false;

    $(function(){
        form = $('#jTagForm').get(0);
        $formFilter = $('#jTagsFilter');
        fChecker = new bff.formChecker(form);
        $progress = $('#jTagsProgress');
        $block = $('#jTagFormBlock');
        $blockCaption = $block.find('span.caption');
        $list = $('#jTagsList');
        $listPgn = $('#jTagsPgn');
        formClean = $(form).html(); 
        
        $list.on('click', 'a.j-tag-edit', function(e){ nothing(e);
            var id = intval($(this).data('id'));
            if (id>0) {
                bff.ajax(urlAjax+'edit&tag_id='+id,{},function(data){
                    if(data) {
                        toggle('edit', $.extend({caption: '<?= HTML::escape($this->lang['edit'], 'js') ?>'}, data) );
                    }
                }, $progress);
            }
        });
        $list.on('click', 'a.j-tag-del', function(e){ nothing(e);
            var id = intval($(this).data('id'));
            if (id>0) {
                bff.ajaxDelete('Удалить?', id, urlAjax+'delete&tag_id='+id,
                    $(this), {progress: $progress, repaint: false});
            }
        });
        $list.on('click', 'input.j-tag-moderate', function(){
            var $check = $(this);
            var id = intval($check.data('id'));
            if(id>0) {
                bff.ajax(urlAjax+'moderate&id='+id, {}, function(data){
                    if(data && data.success) {
                        $check.prop('disabled', true);
                    }
                });
            }
        });
    });

    function toggle(type, edit)
    {
        switch(type) {
            case 'toggle': {
                if($block.is(':hidden')) {
                    toggle('show');
                } else {
                    $block.hide();   
                }
            } break; 
            case 'show': {
                $block.show();
                $.scrollTo( $blockCaption, { duration:500, offset:-300 } );
            } break;            
            case 'cancel': {
                if(form.elements['act'].value == 'edit-finish') {
                    $blockCaption.html('Добавление');
                    $(form).html(formClean);
                }
                $block.hide();
            } break;
            case 'edit': {
                toggle('show');
                $blockCaption.html(edit.caption);
                $block.find('.form_params').html(edit.form);
                $.scrollTo($blockCaption, { duration:500, offset:-300 });
                form.elements['act'].value = 'edit-finish';
                form.elements['filter'].value = $formFilter.serialize();
                $block.find(':input[name="tag"]').focus();
                // replace ac
                $(form).find('.j-tag-replace-ac').autocomplete(urlAjax+'replace-autocomplete&except='+edit.id,
                    {valueInput: $(form).find('.j-tag-replace-id')});
            } break;
        }
        fChecker.check(true, true);
        return false;
    }

    function updateList(resetPage)
    {
        if(_processing) return;
        if(resetPage) $formFilter.find('#j-tags-page').val(1);
        var filterData = $formFilter.serialize();
        bff.ajax(urlAjax+'&'+filterData, {}, function(data){
            if(data) {
                $list.html(data.list);
                $listPgn.html(data.pgn);
                form.elements['filter'].value = filterData;
            }
            if(bff.h) window.history.pushState({}, document.title, urlAjax+'&'+filterData);
        }, function(p){ _processing = p; $list.toggleClass('disabled'); $progress.toggle(); });
    }

    function page(pageID, updateList_)
    {
        $formFilter.find('#j-tags-page').val(pageID);
        if(updateList_!==false) updateList(false);
    }

    return {toggle: toggle, page: page};
}());
</script>