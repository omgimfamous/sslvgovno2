var jContactsForm = (function(){
    var inited = false, o = {lang:{}, url_submit:'', captcha_url:''}, $form;

    function init()
    {
        $form = $('#j-contacts-form');
        var f = app.form($form, function()
        {
            if( ! f.checkRequired({focus:true}) ) return;

            if( ! bff.isEmail( f.fieldStr('email') ) ) {
                return f.fieldError('email', o.lang.email);
            }
            if( f.fieldStr('message').length < 10 ) {
                return f.fieldError('message', o.lang.message);
            }
            if( ! app.user.logined() && o.captcha && ! f.fieldStr('captcha').length ) {
                return f.fieldError('captcha', o.lang.captcha);
            }
            f.ajax(o.url_submit, {}, function(data,errors){
                if(data && data.success) {
                    f.alertSuccess(o.lang.success);
                    f.reset();
                    refreshCaptha();
                } else {
                    f.fieldsError(data.fields, errors);
                    if (data.captcha) {
                        refreshCaptha();
                    }
                }
            });
            return false;
        });
    }

    function refreshCaptha()
    {
        $form.find('#j-contacts-form-captcha-code').attr('src', o.captcha_url+'&r='+Math.random(1));
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){ init(); });
        },
        refreshCaptha: refreshCaptha
    };
}());