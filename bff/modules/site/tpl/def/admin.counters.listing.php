<?php
    $bRotate = in_array($f['tab'], array(0));
?>
<?= tplAdmin::blockStart('Добавление счетчика', false, array('id'=>'SiteCountersFormBlock','style'=>'display:none;')) ?>
    <div id="SiteCountersFormContainer"></div>
<?= tplAdmin::blockStop() ?>

<?= tplAdmin::blockStart('Счетчики', true, array('id'=>'SiteCountersListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>'+ добавить счетчик', 'class'=>'ajax', 'onclick'=>'return jSiteCountersFormManager.action(\'add\',0);')) ?>
    <?php
        $aTabs = array(
            0 => array('t'=>'Все', 'link'=> $this->adminLink(bff::$event.'&tab=0')),
        );
    ?>
    <div class="tabsBar" id="SiteCountersListTabs" style="display: none;">
        <?php foreach($aTabs as $k=>$v) { ?>
            <span class="tab <?php if($f['tab']==$k) { ?>tab-active<?php } ?>"><a href="<?= $v['link'] ?>"><?= $v['t'] ?></a></span>
        <?php } ?>
    </div>
    <div class="actionBar">
        <form method="get" action="<?= $this->adminLink(null) ?>" id="SiteCountersListFilters" onsubmit="return false;" class="form-inline">
            <input type="hidden" name="s" value="<?= bff::$class ?>" />
            <input type="hidden" name="ev" value="<?= bff::$event ?>" />
            <input type="hidden" name="page" value="<?= $f['page'] ?>" />
            <input type="hidden" name="tab" value="<?= $f['tab'] ?>" />
            <div class="left"></div>
            <div class="right">
                <div id="SiteCountersProgress" class="progress" style="display: none;"></div>
            </div>
            <div class="clear"></div>
        </form>
    </div>

    <table class="table table-condensed table-hover admtbl tblhover" id="SiteCountersListTable">
        <thead>
            <tr class="header nodrag nodrop">
                <th width="70">ID</th>
                <th width="150" class="left">Название</th>
                <th class="left">Код счетчика</th>
                <th width="100">Дата создания</th>
                <th width="135">Действие</th>
            </tr>
        </thead>
        <tbody id="SiteCountersList">
            <?= $list ?>
        </tbody>
    </table>
<?= tplAdmin::blockStop() ?>

<div>
    <div class="left">
        
    </div>
    <div class="right desc" style="width:60px; text-align:right;">
        <?php if($bRotate){ ?>&darr; &uarr;<?php } ?>
    </div>
</div>

<script type="text/javascript">
var jSiteCountersFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#SiteCountersFormContainer');
        $progress = $('#SiteCountersProgress');
        $block = $('#SiteCountersFormBlock');
        $blockCaption = $block.find('span.caption');

        <?php if(!empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<?php } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jSiteCountersList.toggle(false);
            if(jSiteCountersForm) jSiteCountersForm.onShow();
        } else {
            jSiteCountersList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html((type == 'add' ? 'Добавление счетчика' : 'Редактирование счетчика'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, { duration:500, offset:-300 } );
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jSiteCountersList.toggle(true);
            }
        }, function(p){ process = p; $progress.toggle(); });
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

var jSiteCountersList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false, tab = <?= $f['tab'] ?>;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#SiteCountersProgress');
        $block     = $('#SiteCountersListBlock');
        $list      = $block.find('#SiteCountersList');
        $listTable = $block.find('#SiteCountersListTable');
        $listPgn   = $block.find('#SiteCountersListPgn');
        filters    = $block.find('#SiteCountersListFilters').get(0);

        $list.on('click', 'a.counter-edit', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) jSiteCountersFormManager.action('edit',id);
            return false;
        });

        $list.on('click', 'a.counter-toggle', function(){
            var id = intval($(this).attr('rel'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.on('click', 'a.counter-del', function(){
            var id = intval($(this).attr('rel'));
            if(id>0) del(id, this);
            return false;
        });

        <?php if($bRotate) { ?>bff.rotateTable($list, ajaxUrl+'rotate', $progress, false, {tab: tab});<?php } ?>
    });

    function isProcessing()
    {
        return processing;
    }

    function del(id, link)
    {
        bff.ajaxDelete('Удалить?', id, ajaxUrl+'delete&id='+id, link, {progress: $progress, repaint: false});
        return false;
    }

    function updateList(updateUrl)
    {
        if(isProcessing()) return;
        var f = $(filters).serialize();
        bff.ajax(ajaxUrl, f, function(data){
            if(data) {
                $list.html( data.list );
                $listPgn.html( data.pgn );
                if(updateUrl !== false && bff.h) {
                    window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                }
                <?php if($bRotate) { ?>$list.tableDnDUpdate();<?php } ?>
            }
        }, function(p){ if(p){ $progress.show(); } else { $progress.hide(); } processing = p; $list.toggleClass('disabled'); });
    }

    function setPage(id)
    {
        // нет постраничности
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            setPage(1);
            if(resetForm) {
                //
            }
            updateList();
        },
        page: function (id)
        {
            if(isProcessing()) return false;
            setPage(id);
            updateList();
        },
        onTab: function(tabNew, link)
        {
            if(isProcessing() || tabNew == tab) return false;
            setPage(1);
            tab = filters.tab.value = tabNew;
            updateList();
            bff.onTab(link);
            return false;
        },
        refresh: function(resetPage,updateUrl)
        {
            if(resetPage) setPage(0);
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