var jBBSMyImport = (function(){
    var inited = false, $form, form, $submit, $templateButton, $list, $pgn, $listForm,
        o = {url: document.location.pathname, catsRootID:0,catsMain:{},lang:{}},
        cat = {$id:0};

    function init()
    {
        $form = $('#j-my-import-form');
        $listForm = $('#j-my-import-history-form');
        $list = $('#j-my-import-history-list');
        $pgn = $('#j-my-import-history-pgn');

        $submit = $form.find('.j-submit');
        $templateButton = $form.find('.j-template');
        cat.$id = $form.find('.j-cat-value');
        
        $templateButton.click(function(){
            var query = "&act=importTemplate&" + $form.serialize()+'&sAction=template';
            bff.redirect(o.url+'?'+query);
        });
        
        // cat select
        catSelect();

        // submit
        form = app.form($form);
        bff.iframeSubmit($form, function(resp, errors){
            if(resp && resp.success) {
                app.alert.success(o.lang.success);
                setTimeout(function(){ location.reload(); }, 1000);
            } else {
                app.alert.error(errors);
            }
        },{
            beforeSubmit: function(){
                if( ! form.checkRequired() ) return false;
                return true;
            },
            button: '.j-submit'
        });
        
        // attach file
        var file_api = ( ( window.File && window.FileReader && window.FileList && window.Blob ) ? true : false );
        var $upload = $('.j-attach-block .j-upload', $form),
            $cancel = $('.j-attach-block .j-cancel', $form),
            $cancelFilename = $('.j-cancel-filename', $cancel);
    
        $upload.on('change', '.j-upload-file', function(){
            var name = (this.value || '');
            if( file_api && this.files[0] ) name = this.files[0].name;
            if( ! name.length ) return;
            name = name.replace(/.+[\\\/]/, "");
            
            var ext = name.split('.').pop();
            if (ext !== 'xml') {
                app.alert.error(o.lang.wrongFormat);
                return;
            }
            
            if(name.length > 32) name = name.substring(0,32) + '...';
            $cancelFilename.html(name+'&nbsp;&nbsp;&nbsp;&nbsp;');
            $upload.addClass('hide');
            $cancel.removeClass('hide');
            buttonsState();
        });
        
        $('.j-cancel-link', $cancel).on('click', function(e){ nothing(e);
            try{
            var file = $('.j-upload-file', $upload).get(0);
                file.parentNode.innerHTML = file.parentNode.innerHTML;
            } catch(e){}
            $upload.removeClass('hide');
            $cancel.addClass('hide');
            $cancelFilename.html('');
            buttonsState();
        });
        

        // pp
        $pp = $listForm.find('#j-my-import-history-pp');
        $ppVal = $listForm.find('#j-my-import-history-pp-value');
        $pp.on('click', '.j-pp-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $ppVal.val(value);
            $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
            onPP(value, true);
        });

        listMngr = app.list($listForm, {
            onSubmit: function(resp, ex) {
                if(ex.scroll) $.scrollTo($list, {offset: -150, duration:500, axis: 'y'});
                $list.html(resp.list);
                $pgn.html(resp.pgn);
                $pp.toggle(resp.total > 0);
            },
            onProgress: function(progress, ex) {
                if(ex.fade) $list.toggleClass('disabled');
            },
            onPopstate: function() {
                onPP($ppVal.val(), false);
            },
            ajax: true
        });

        // pgn
        $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
            listMngr.page($(this).data('page'));
        });
    }
    
    function onPP(value, submit)
    {
        $pp.find('.j-pp-title').html( $pp.find('.j-pp-option[data-value="'+intval(value)+'"]').html() );
        if( submit ) {
            listMngr.submit({scroll:true}, true);
        }
    }
    
    function buttonsState()
    {
        if(cat.$id.val() != '' && $('.j-upload-file').val() != '')
        {
            $submit.prop('disabled',false);
            $templateButton.prop('disabled',false);
        } else if(cat.$id.val()) {
            $submit.prop('disabled',true);
            $templateButton.prop('disabled',false);
        } else {
            $submit.prop('disabled',true);
            $templateButton.prop('disabled',true);
        }
    }

    function catSelect()
    {
        var $popup = $('.j-cat-select-popup', $form), popup;
        var $linkEmpty = $('.j-cat-select-link-empty', $form),
            $linkSelected = $('.j-cat-select-link-selected', $form);
        var cache = {};

        function doFilter(device, $link, $linkBlock)
        {
            var data = $link.metadata();
            var separator = ' &raquo; ';
            var id = [], title = [], parentData = {}, parentID = data.pid, currentID = data.id;
            while( cache[device].hasOwnProperty(parentID) ) {
                var parentCats = cache[device][parentID].cats;
                for(var i in parentCats) {
                    if( parentCats[i].id == currentID ) {
                        parentData = parentCats[i];
                        id.unshift(parentCats[i].id);
                        title.unshift(parentCats[i].t);
                    }
                }
                currentID = parentID;
                parentID = cache[device][parentID].pid;
            }
            $linkEmpty.hide();
            $linkSelected.find('.j-icon').attr('src', parentData.i);
            $linkSelected.find('.j-title').html( title.join(separator) );
            $linkSelected.show();
            $linkBlock.find('a').removeClass('active');
            $link.addClass('active');

            cat.$id.val(data.id); app.inputError(cat.$id, false, false);
            buttonsState();
        }

        function initPopup(device)
        {
            var $st1 = $popup.find('.j-cat-select-step1-'+device);
            var $st2 = $popup.find('.j-cat-select-step2-'+device);
            cache[device] = {};
            cache[device][o.catsRootID] = {html:'',cats:o.catsMain,pid:0};
            function st2View(parentID, fromStep1)
            {
                if( cache[device].hasOwnProperty(parentID) ) {
                    $st2.html(cache[device][parentID].html);
                    if(fromStep1) $st2.add($st1).toggleClass('hide');
                    if(app.device(app.devices.phone)) {
                        $.scrollTo($st2, {offset: -10, duration: 400, axis: 'y'});
                    }
                } else {
                    bff.ajax(bff.ajaxURL('bbs','form&ev=catsList'), {parent:parentID, device:device, showAll:true}, function(data){
                        if(data && data.success) {
                            cache[device][parentID] = data;
                            st2View(parentID, fromStep1);
                        }
                    });
                }
            }
            $st1.on('click', 'a.j-main', function(){
                var data = $(this).metadata();
                if( data.subs > 0 ) {
                    st2View(data.id, true);
                } else {
                    popup.hide();
                    doFilter(device, $(this), $st1);
                }
                return false;
            });
            $st2.on('click', '.j-back', function(){
                var prevID = intval($(this).metadata().prev);
                if( prevID == o.catsRootID ) {
                    $st2.add($st1).toggleClass('hide');
                } else {
                    st2View(prevID, false);
                }
                return false;
            });
            $st2.on('click', '.j-sub', function(){
                var data = $(this).metadata();
                if( data.subs > 0 ) {
                    st2View(data.id, false);
                } else {
                    popup.hide();
                    doFilter(device, $(this), $st2);
                }
                return false;
            });
        }

        popup = app.popup('form-cat-select', $popup, $('.j-cat-select-link', $form), {onInit: function(){
            initPopup(app.devices.desktop);
            initPopup(app.devices.phone);
        }});
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){
                init();
            });
        }
    };
}());