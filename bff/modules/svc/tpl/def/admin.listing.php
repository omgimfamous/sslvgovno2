<?php
    $bRotate = true;
?>
<?= tplAdmin::blockStart('Добавление услуги', false, array('id'=>'SvcSvcFormBlock','style'=>'display:none;')) ?>
    <div id="SvcSvcFormContainer"></div>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart('Услуги', true, array('id'=>'SvcSvcListBlock','class'=>( ! empty($act) ? 'hidden' : '')),
        array('title'=>'+ добавить', 'class'=>'ajax', 'onclick'=>'return jSvcSvcFormManager.action(\'add\',0);')) ?>

<div class="tabsBar" id="SvcSvcListTabs">
    <?php foreach($types as $v) { ?>
        <span class="tab <?php if($f['tab']==$v['id']) { ?>tab-active<?php } ?>"><a href="<?= $this->adminLink(bff::$event.'&tab='.$v['id']) ?>"><?= $v['title_list'] ?></a></span>
    <?php } ?>
    <div id="SvcSvcProgress" class="progress" style="display: none;"></div>
</div>
<div class="actionBar displaynone">
    <form method="get" action="<?= $this->adminLink(null) ?>" id="SvcSvcListFilters" onsubmit="return false;">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="tab" value="<?= $f['tab'] ?>" />
    </form>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="SvcSvcListTable">
<thead>
    <tr class="header nodrag nodrop">
        <th width="70">ID</th>
        <th class="left">Название</th>
        <th class="left" width="200">Модуль</th>
        <th width="135">Действие</th>
    </tr>
</thead>
<tbody id="SvcSvcList">
    <?= $list ?>
</tbody>
</table>

<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">
        <?php if($bRotate){ ?>&darr; &uarr;<?php } ?>
    </div>
    <div class="clear clearfix"></div>
</div>
<?= tplAdmin::blockStop() ?>

<script type="text/javascript">
var jSvcSvcFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#SvcSvcFormContainer');
        $progress = $('#SvcSvcProgress');
        $block = $('#SvcSvcFormBlock');
        $blockCaption = $block.find('span.caption');

        <?php if(!empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<?php } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jSvcSvcList.toggle(false);
            if(jSvcSvcForm) jSvcSvcForm.onShow();
        } else {
            jSvcSvcList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? 'Добавление услуги' : 'Редактирование услуги'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, { duration:500, offset:-300 } );
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jSvcSvcList.toggle(true);
            }
        }, $progress);
    }

    function action(type, id, params)
    {
        params = $.extend(params || {}, {act:type});
        switch(type) {
            case 'add':
            {
                if( id > 0 ) return action('edit', id, params);
                if($block.is(':hidden')) {
                    initForm(type, id, params);
                } else {
                    action('cancel');
                }
            } break;
            case 'cancel':
            {
                $block.hide();
                onFormToggle(false);
            } break;
            case 'edit':
            {
                if( ! (id || 0) ) return action('add', 0, params);
                params.id = id;
                initForm(type, id, params);
            } break;
        }
        return false;
    }

    return {
        action: action
    };
}());

var jSvcSvcList =
(function()
{
    var $progress, $block, $list, $listTable, filters, processing = false, tab = <?= $f['tab'] ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#SvcSvcProgress');
        $block     = $('#SvcSvcListBlock');
        $list      = $block.find('#SvcSvcList');
        $listTable = $block.find('#SvcSvcListTable');
        filters    = $block.find('#SvcSvcListFilters').get(0);

        $list.delegate('a.svc-edit', 'click', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) jSvcSvcFormManager.action('edit',id);
            return false;
        });

        $list.delegate('a.svc-toggle', 'click', function(){
            var id = intval($(this).attr('rel'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.delegate('a.svc-del', 'click', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del(id, this);
            return false;
        });

        bff.rotateTable($list, ajaxUrl+'rotate', $progress, false, {tab: tab});
    });

    function isProcessing()
    {
        return processing;
    }

    function del(id, link)
    {
        bff.ajaxDelete('sure', id, ajaxUrl+'delete&id='+id, link, {progress: $progress, repaint: false});
        return false;
    }

    function updateList(updateUrl)
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax(ajaxUrl, f, function(data){
            if(data) {
                $list.html( data.list );
                if(updateUrl !== false && bff.h) {
                    window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                }
                $list.tableDnDUpdate();
            }
        }, function(p){ $progress.toggle(); processing = p; $list.toggleClass('disabled'); });
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            if(resetForm) {
                //
            }
            updateList();
        },
        onTab: function(tabNew, link)
        {
            if(isProcessing() || tabNew == tab) return false;
            tab = filters.tab.value = tabNew;
            updateList();
            bff.onTab(link);
            return false;
        },
        refresh: function(resetPage,updateUrl)
        {
            updateList(updateUrl);
        },
        toggle: function(show)
        {
            if(show === true) {
                $block.show();
                if(bff.h) window.history.pushState({}, document.title, $(filters).attr('action') + '?' + $(filters).serialize());
            }
            else $block.hide();
        }
    };
}());

</script>