var jBillsMyHistory = (function(){
    var inited = false, o = {lang:{},ajax:true}, listMngr,
    $form, $list, $pgn, $pp, $ppVal;

    function init()
    {
        $form = $('#j-my-history-form');
        $list = $form.find('#j-my-history-list');
        $pgn = $form.find('#j-my-history-pgn');

        //pp
        $pp = $form.find('#j-my-history-pp');
        $ppVal = $form.find('#j-my-history-pp-value');
        $pp.on('click', '.j-pp-option', function(e){ nothing(e);
            var value = $(this).data('value');
            $ppVal.val(value);
            $pp.find('.j-pp-dropdown').dropdown('toggle').blur();
            onPP(value, true);
        });

        listMngr = app.list($form, {
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
            ajax: o.ajax
        });

        //pgn
        if (o.ajax) {
            $pgn.on('click', '.j-pgn-page', function(e){ nothing(e);
                listMngr.page($(this).data('page'));
            });
        }
    }

    function onPP(value, submit)
    {
        $pp.find('.j-pp-title').html( $pp.find('.j-pp-option[data-value="'+intval(value)+'"]').html() );
        if( submit ) {
            listMngr.submit({scroll:true}, true);
        }
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){ init(); });
        }
    };
})();

var jBillsMyPay = (function(){
    var inited = false, o = {lang:{}, url_submit:''}, $formRequest;

    function init()
    {
        $formRequest = $('#j-my-pay-form-request');
        initForm(app.devices.desktop);
        initForm(app.devices.phone);
    }

    function initForm(device)
    {
        var $form = $('#j-my-pay-form-'+device); if ( ! $form.length ) return;
        var f = app.form($form, function(){
            if( ! f.checkRequired() ) return;
            f.ajax(o.url_submit, {}, function(resp, errors){
                if(resp && resp.success) {
                    $formRequest.html(resp.form).find('form:first').submit();
                } else {
                    app.alert.error(errors);
                }
            });
        });
        $form.on('click', '.j-ps-item', function(e){
            if ( $(e.target).is('input') || $(e.target).parents('label').length ) {
                return;
            }
            var $item = $(this);
            $item.siblings().removeClass('active').find('.j-radio').prop({checked: false});
            $item.addClass('active').find('.j-radio').prop({checked: true});
        });
        $form.on('click', '.j-ps-item .j-radio', function(e){
            var $item = $(this).closest('.j-ps-item');
            $item.addClass('active');
            $item.siblings().removeClass('active').find('.j-radio').prop({checked: false});
            $(this).prop({checked: true});
        });
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){ init(); });
        }
    };
})();