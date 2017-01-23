<?php
    /**
     * Посадочные страницы
     * @var $this SEO
     */
?>
<?= tplAdmin::blockStart('SEO / Посадочные страницы / Добавление', false, array('id'=>'SeoLandingPagesFormBlock','style'=>'display:none;')); ?>
    <div id="SeoLandingPagesFormContainer"></div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart('SEO / Посадочные страницы', true, array('id'=>'SeoLandingPagesListBlock','class'=>(!empty($act) ? 'hidden' : '')),
        array('title'=>'+ добавить страницу','class'=>'ajax','onclick'=>'return jSeoLandingPagesFormManager.action(\'add\',0);'),
        array()
    ); ?>
            <div class="actionBar">
                <form method="get" action="<?= $this->adminLink(NULL) ?>" id="SeoLandingPagesListFilters" onsubmit="return false;" class="form-inline">
                <input type="hidden" name="s" value="<?= bff::$class ?>" />
                <input type="hidden" name="ev" value="<?= bff::$event ?>" />
                <input type="hidden" name="page" value="<?= $f['page'] ?>" />
                
                <div class="left">
                    <div class="left">
                        <input style="width:375px;" type="text" name="title" placeholder="Название / URL / ID посадочной страницы" value="<?= HTML::escape($f['title']) ?>" />
                        <input type="button" class="btn btn-small button cancel" onclick="jSeoLandingPagesList.submit(false);" value="найти" />
                    </div>
                    <div class="left"><a class="ajax cancel" onclick="jSeoLandingPagesList.submit(true); return false;">сбросить</a></div>
                    <div class="clear"></div>
                </div>
                <div class="right">
                    <div id="SeoLandingPagesProgress" class="progress" style="display: none;"></div>
                </div>
                <div class="clear"></div>
                </form>
            </div>

            <table class="table table-condensed table-hover admtbl tblhover" id="SeoLandingPagesListTable">
                <thead>
                    <tr class="header nodrag nodrop">
                        <th width="60">ID</th>
                        <th width="300" class="left">Название</th>
                        <th class="left">URL</th>
                        <th width="135">Действие</th>
                    </tr>
                </thead>
                <tbody id="SeoLandingPagesList">
                    <?= $list ?>
                </tbody>
            </table>
            <div id="SeoLandingPagesListPgn"><?= $pgn ?></div>
            
<?= tplAdmin::blockStop(); ?>

<div>
    <div class="left"></div>
    <div class="right desc" style="width:60px; text-align:right;">
        
    </div>
</div>

<script type="text/javascript">
var jSeoLandingPagesFormManager = (function(){
    var $progress, $block, $blockCaption, $formContainer, process = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event); ?>';

    $(function(){
        $formContainer = $('#SeoLandingPagesFormContainer');
        $progress = $('#SeoLandingPagesProgress');
        $block = $('#SeoLandingPagesFormBlock');
        $blockCaption = $block.find('span.caption');

        <?php if( ! empty($act)) { ?>action('<?= $act ?>',<?= $id ?>);<?php } ?>
    });

    function onFormToggle(visible)
    {
        if(visible) {
            jSeoLandingPagesList.toggle(false);
            if(jSeoLandingPagesForm) jSeoLandingPagesForm.onShow();
        } else {
            jSeoLandingPagesList.toggle(true);
        }
    }

    function initForm(type, id, params)
    {
        if( process ) return;
        bff.ajax(ajaxUrl,params,function(data){
            if(data && (data.success || intval(params.save)===1)) {
                $blockCaption.html('SEO / Посадочные страницы / '+(type == 'add' ? 'Добавление' : 'Редактирование'));
                $formContainer.html(data.form);
                $block.show();
                $.scrollTo( $blockCaption, {duration:500, offset:-300});
                onFormToggle(true);
                if(bff.h) {
                    window.history.pushState({}, document.title, ajaxUrl + '&act='+type+'&id='+id);
                }
            } else {
                jSeoLandingPagesList.toggle(true);
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

var jSeoLandingPagesList =
(function()
{
    var $progress, $block, $list, $listTable, $listPgn, filters, processing = false;
    var ajaxUrl = '<?= $this->adminLink(bff::$event.'&act='); ?>';
    
    $(function(){
        $progress  = $('#SeoLandingPagesProgress');
        $block     = $('#SeoLandingPagesListBlock');
        $list      = $block.find('#SeoLandingPagesList');
        $listTable = $block.find('#SeoLandingPagesListTable');
        $listPgn   = $block.find('#SeoLandingPagesListPgn');
        filters    = $block.find('#SeoLandingPagesListFilters').get(0);

        $list.delegate('a.landingpage-edit', 'click', function(){
            var id = intval($(this).data('id'));
            if(id>0) jSeoLandingPagesFormManager.action('edit',id);
            return false;
        });

        $list.delegate('a.landingpage-toggle', 'click', function(){
            var id = intval($(this).data('id'));
            var type = $(this).data('type');
            if(id>0) {
                var params = {progress: $progress, link: this};
                bff.ajaxToggle(id, ajaxUrl+'toggle&type='+type+'&id='+id, params);
            }
            return false;
        });

        $list.delegate('a.landingpage-del', 'click', function(){
            var id = intval($(this).data('id'));
            if(id>0) del(id, this);
            return false;
        });

        $(window).bind('popstate',function(e){
            if('state' in window.history && window.history.state === null) return;
            var loc = document.location;
            var actForm = /act=(add|edit)/.exec( loc.search.toString() );
            if( actForm!=null ) {
                var actId = /id=([\d]+)/.exec(loc.search.toString());
                jSeoLandingPagesFormManager.action(actForm[1], actId && actId[1]);
            } else {
                jSeoLandingPagesFormManager.action('cancel');
                updateList(false);
            }
        });

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
            }
        }, function(p){ $progress.toggle(); processing = p; $list.toggleClass('disabled'); });
    }

    function setPage(id)
    {
        filters.page.value = intval(id);
    }

    return {
        submit: function(resetForm)
        {
            if(isProcessing()) return false;
            setPage(1);
            if(resetForm) {
                filters['title'].value = '';
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
        refresh: function(resetPage, updateUrl)
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