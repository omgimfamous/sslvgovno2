var jForm = (function(){
    var inited = false, edit = false, $form, form,
        o = {itemID:0,
             catsRootID:0,catsMain:{},catTypesEx:false,catTypeSeek:0,catEditable:false,
             imgLimit:0,imgMaxSize:0,imgUploaded:0,imgData:{},
             geoCityID:0,
             phonesLimit:0, phonesData:{}, contactsFromProfile: false,
             lang:{}},
        img = {url:'',$block:0,type:{},$togglers:0,uploader:0,active:{},
            classes: {
                active: 'j-img-active',
                first: 'i-form__addphotos__item_first',
                preview: 'i-form__addphotos__item_img'
            }
        },
        cat = {$form:0, $id:0, $owner:0, cacheF:{}, cacheD:{}},
        geo = {$block:0, cityID:0, metro:{data:{}}, district:{enabled:false, $block:0, data:{}},
            addr:{$block:0,map:{},editor:{},lastQuery:''}},
        map = {$block:0,map:0,marker:0},
        contacts = {phones:0};

    function init()
    {
        $form = $('#j-i-form');
        cat.$form = $form.find('.j-cat-form');
        cat.$id = $form.find('.j-cat-value');
        cat.$owner = $form.find('.j-cat-owner');
        o.edit = (o.itemID > 0);

        // publisher
        var $publisherType = $form.find('.j-publisher-type');
        if($publisherType.length) {
            $form.on('click', '.j-publisher-toggle', function(){
                var $e = $(this), type, dataKey, data;
                if ($e.hasClass('disabled') || $e.hasClass('active')) return;
                $e.addClass('active').siblings().removeClass('active');
                type = intval($e.data('type')); $publisherType.val(type);
                dataKey = 'contacts_'+(type === 0 ? 'user' : 'shop');
                if (o.edit || ! o.hasOwnProperty(dataKey)) return;
                data = o[dataKey];
                contacts.phones.view(data.phones);
                form.$field('skype').val(data.skype);
                form.$field('icq').val(data.icq);
                $form.find('#j-i-name-block').toggle(type === 0);
                if (data.city_id) {
                    var cd = data.city_data;
                    geo.$block.find('.j-geo-city-select-ac').val(cd.title);
                    geo.fn.onCity(data.city_id, {changed:true, title:cd.title, data:[
                        data.city_id, ''/*no search*/, cd.metro, cd.pid
                    ]});
                    geo.addr.$addr.val(data.addr_addr);
                    geo.addr.$lat.val(data.addr_lat);
                    geo.addr.$lon.val(data.addr_lon);
                    geoMapSearch();
                }
            });
        }

        // text length counters
        bff.maxlength($('#j-i-title', $form), {
            limit: 70, nobr: true,
            message: $('#j-i-title-maxlength', $form),
            //onChange: function($e){ app.inputError($e, ! $.trim($e.val()).length ); },
            lang: {
                left: o.lang.maxlength_symbols_left,
                symbols: o.lang.maxlength_symbols.split(';')
            }
        });
        bff.maxlength($('#j-i-descr', $form), {
            limit: 4000, cut: false,
            message: $('#j-i-descr-maxlength', $form),
            //onChange: function($e) { app.inputError($e, ! $.trim($e.val()).length ); },
            lang: {
                left: o.lang.maxlength_symbols_left,
                symbols: o.lang.maxlength_symbols.split(';')
            }
        });

        // cat select
        catSelect();

        // cat type
        cat.$form.on('click', '.j-cat-type', function(){
            catType($(this).val());
        });
        cat.$form.find('.j-cat-types .j-cat-type:checked').click();
        cat.$form.removeClass('hide');

        // video
        $form.on('keyup paste', '.j-video', function(){
            var $input = $(this);
            var data = $.trim($input.val());
            if( ! data.length ) {
                app.inputError($input, false);
            } else {
                var ok = false;
                var videoProviders = [/youtube\.com/,/youtu\.be/,/vimeo\.com/,/player\.vimeo\.com/,/rutube\.ru/];
                for(var i in videoProviders) {
                    if( videoProviders[i].test(data) ) {
                        ok = true;
                    }
                }
                app.inputError($input, ! ok);
            }
        });

        // images
        img.url = bff.ajaxURL('bbs&ev=img&hash='+app.csrf_token+'&item_id='+o.itemID, '');
        img.$block = $form.find('.j-images');
        img.type = {
            $ajax: img.$block.find('.j-images-type-ajax'),
            $simple: img.$block.find('.j-images-type-simple')
        };
        img.$togglers = img.$block.find('.j-togglers');
        img.$togglers.on('click', '.j-toggler', function(e){ nothing(e);
            var type = $(this).data('type');
            img.$togglers.toggleClass('hide');
            img.$block.find('.j-images-type').toggle();
            img.$block.find('.j-images-type-value').val(type);
        });
        img.uploader = new qq.FileUploaderBasic({
            button: null,
            action: img.url+'upload',
            limit: intval(o.imgLimit), sizeLimit: o.imgMaxSize,
            uploaded: intval(o.imgUploaded),
            multiple: true, allowedExtensions: ['jpeg','jpg','png','gif'],
            onSubmit: function(id, fileName) {
                return imgProgress(id, 'start', false);
            },
            onComplete: function(id, fileName, resp) {
                if(resp && resp.success) {
                    imgProgress(id, 'preview', resp);
                    imgRotate(true);
                } else {
                    if(resp.errors) {
                        app.alert.error(resp.errors);
                        imgProgress(id, 'remove');
                    }
                }
                return true;
            },
            onCancel: function(id, fileName) {
                imgProgress(id, 'remove');
            },
            showMessage: function(message, code) {
                app.alert.error(message);
            },
            messages: {
                typeError: o.lang.upload_typeError,
                sizeError: o.lang.upload_sizeError,
                minSizeError: o.lang.upload_minSizeError,
                emptyError: o.lang.upload_emptyError,
                limitError: o.lang.upload_limitError,
                onLeave: o.lang.upload_onLeave
            }
        });
        img.type.$ajax.find('.j-img-upload .j-img-link').each(function(){
            img.uploader._createUploadButton(this);
        });
        img.type.$ajax.on('click', '.j-img-preview .j-img-link', function(e){ nothing(e);
            imgRemove( $(this).data('id') );
        });
        if(o.edit) {
            for(var i in o.imgData) {
                if( o.imgData.hasOwnProperty(i) ) {
                    imgProgress('img'+i, 'start', true);
                    imgProgress('img'+i, 'preview', o.imgData[i]);
                }
            }
        }
        imgRotate();
        var imgUnloadProcessed = false;
        app.$W.bind('beforeunload', function(){
            if( ! imgUnloadProcessed && intval(o.itemID) === 0) {
                imgUnloadProcessed = true;
                var fn = [];
                for(var i in img.active) {
                    if( img.active.hasOwnProperty(i) && img.active[i].data!==false ) {
                        var data = img.active[i].data;
                        if( data.tmp ) {
                            fn.push( data.filename );
                        }
                    }
                }
                if( fn.length ) {
                    bff.ajax(img.url+'delete-tmp', {filenames:fn}, false, false, {async:false});
                }
            }
        });

        //geo
        geo.fn = (function(){
            geo.$block = $form.find('.j-geo');
            //city
            geo.cityID = o.geoCityID;
            //metro
            geo.metro.$block = $('.j-geo-metro');
            geo.metro.$empty = geo.metro.$block.find('.j-geo-metro-link-empty');
            geo.metro.$selected = geo.metro.$block.find('.j-geo-metro-link-selected');
            geo.metro.$value = geo.metro.$block.find('.j-geo-metro-value');
            geo.metro.popup = app.popup('form-geo-metro-select',
                $('.j-geo-metro-popup', geo.metro.$block),
                $('.j-geo-metro-link', geo.metro.$block), {
                onInit: function($p){
                    var _this = this,
                        $step1 = $p.find('.j-step1'),
                        $step2 = $p.find('.j-step2');
                    $p.on('click', '.j-branch', function(e){ nothing(e);
                        var branch = $(this).metadata();
                        if( branch && geo.metro.data.hasOwnProperty(branch.city) ) {
                            $step1.hide();
                            $step2.html( geo.metro.data[branch.city].stations[branch.id] ).show();
                        }
                    });
                    $p.on('click', '.j-station', function(e){ nothing(e);
                        var station = $(this).metadata();
                        if( station && geo.metro.data.hasOwnProperty(station.city) && station.branch ) {
                            var branch = geo.metro.data[station.city].data[station.branch];
                            geo.metro.$selected.find('.j-color').css({backgroundColor:branch.color});
                            geo.metro.$selected.find('.j-title').html(branch.t + ' &raquo; ' + branch.st[station.id].t);
                            geo.metro.$selected.show();
                            geo.metro.$empty.hide();
                        }
                        geo.metro.$value.val(station.id);
                        geo.metro.popup.hide();
                    });
                    $p.on('click', '.j-back', function(e){ nothing(e);
                        $step2.hide().html('');
                        $step1.show();
                    });
                },
                onShow: function($p){
                    if( ! geo.metro.data.hasOwnProperty(geo.cityID) ) {
                        geo.fn.refreshMetro(geo.cityID, function(){
                            $p.fadeIn(100);
                        });
                    } else {
                        $p.fadeIn(100);
                    }
                }
            });
            //addr
            geo.addr.$block = geo.$block.find('#j-i-geo-addr');
            geo.addr.$addr = geo.addr.$block.find('#j-i-geo-addr-addr');
            geo.addr.$lat = geo.addr.$block.find('#j-i-geo-addr-lat');
            geo.addr.$lon = geo.addr.$block.find('#j-i-geo-addr-lon');

            geo.district.$block = geo.$block.find('.j-geo-district');
            geo.district.enabled = geo.district.$block.length > 0;
            if(geo.district.enabled){
                geo.district.$select = geo.district.$block.find('select');
            }

            geoMapInit();

            return {
                onCity: function(cityID, ex)
                {
//                    if( ! ex.changed ) return;
                    cityID = intval(cityID);
                    geo.cityID = cityID;
                    var $inputID = geo.$block.find('.j-geo-city-select-id');
                    $inputID.val(cityID);
                    app.inputError($inputID, false);
                    // metro
                    geo.metro.popup.hide();
                    geo.metro.$empty.show();
                    geo.metro.$selected.hide();
                    geo.metro.$value.val(0);
                    if( ex.data === false || intval(ex.data[2]) === 0 ) {
                        geo.metro.$block.hide();
                    } else {
                        geo.fn.refreshMetro(cityID);
                        geo.metro.$block.show();
                    }
                    // district
                    if (geo.district.enabled) {
                        if (cityID > 0) {
                            if (geo.district.data.hasOwnProperty(cityID)) {
                                geo.district.$select.html(geo.district.data[cityID]);
                                geo.district.$block.toggleClass('hide', geo.district.$select.find('option').length <= 1);
                            } else {
                                bff.ajax(bff.ajaxURL('geo','districts-list'), {city:cityID, opts:true}, function(data) {
                                    if (data && data.success) {
                                        geo.district.data[cityID] = data.districts;
                                        geo.district.$select.html(geo.district.data[cityID]);
                                        geo.district.$block.toggleClass('hide', geo.district.$select.find('option').length <= 1);
                                    }
                                });
                            }
                        } else {
                            geo.district.$block.addClass('hide');
                        }
                    }
                    // map
                    if(ex.title && ex.title.length > 0) {
                         geoMapSearch(true);
                    }
                    svcPricesUpdate();
                },
                refreshMetro: function(cityID, callback)
                {
                    if( geo.metro.data.hasOwnProperty(cityID) ) {
                        var data = geo.metro.data[cityID];
                        geo.metro.$block.find('.j-step2').hide().html('');
                        geo.metro.$block.find('.j-step1').html(data.branches).show();
                        callback = callback || $.noop; callback();
                    } else {
                        bff.ajax(bff.ajaxURL('geo','form-metro'), {city:cityID}, function(resp){
                            if(resp && resp.success) {
                                geo.metro.data[cityID] = resp;
                                geo.fn.refreshMetro(cityID, callback);
                            }
                        });
                    }
                }
            };
        }());

        //user: phones
        contacts.phones = userPhonesInit(o.phonesLimit, o.phonesData);
        //user: register phone
        app.user.phoneInput($form.find('.j-phone-number'));

        //submit
        var form = app.form($form, false, {noEnterSubmit:true});
        var formButton = $form.find('.j-submit:last');
        bff.iframeSubmit($form, function(resp, errors){
            if(resp && resp.success) {
                bff.redirect( $('<div/>').html(resp.successPage).text() );
            } else {
                form.fieldsError(resp.fields, errors);
                formButton.button('reset');
            }
        },{
            beforeSubmit: function(){
                if( ! form.checkRequired() ) {
                    if (o.registerPhone && phoneInput.input.val().length < 9) {
                        app.inputError(phoneInput.input, true, false);
                    }
                    return false;
                }
                var $priceBlock = $form.find('.j-price-block');
                if ($priceBlock.length) {
                    var $price = $priceBlock.find('.j-price');
                    if ( ! $price.is('.j-required') && (intval($price.val()) <=0 || parseFloat($price.val()) <= 0) && ! $priceBlock.find('.j-price-var:checked').length) {
                        var $priceMod = $priceBlock.find('.j-price-mod');
                        if ( ! $priceMod.length || ! $priceMod.is(':checked') ) {
                            form.fieldError('price', o.lang.price); return false;
                        }
                    }
                }
                if( ! app.user.logined() && ! bff.isEmail( form.fieldStr('email') ) ) {
                    form.fieldError('email', o.lang.email_wrong); return false;
                }
                formButton.button('loading');
                return true;
            }
        });
        $form.on('click', '.j-cancel', function(e){ nothing(e);
            history.back();
        });

        // action: publicate
        $('#j-i-form-action-publicate').on('click', function(e){ nothing(e);
            bff.ajax(bff.ajaxURL('bbs', 'item-status&status=publicate'), {id:o.itemID,hash:app.csrf_token,form:true}, function(resp, errors){
                if( resp && resp.success ) {
                    app.alert.success(resp.message || '');
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    app.alert.error(errors);
                }
            });
        });
        // action: refresh
        $('#j-i-form-action-refresh').on('click', function(e){ nothing(e);
            bff.ajax(bff.ajaxURL('bbs', 'item-status&status=refresh'), {id:o.itemID,hash:app.csrf_token,form:true}, function(resp, errors){
                if( resp && resp.success ) {
                    app.alert.success(resp.message || '');
                    setTimeout(function(){ location.reload(); }, 1500);
                } else {
                    app.alert.error(errors);
                }
            });
        });

        //svc
        if (!o.edit) {
            $form.on('click', '.j-svc-item', function(){
                $(this).addClass('active').find('.j-svc-descr').removeClass('hide');
                $(this).siblings().removeClass('active').find('.j-svc-descr').addClass('hide');
            });
            svcPricesUpdate();
        }
    }

    function catSelect()
    {
        var $popup = $('.j-cat-select-popup', $form), popup;
        var $linkEmpty = $('.j-cat-select-link-empty', $form),
            $linkSelected = $('.j-cat-select-link-selected', $form);
        var cache = {};

        if( o.itemID > 0 && ! o.catEditable ) {
            $linkSelected.on('click', function(e){
                nothing(e);
            });
            return;
        }

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
            catForm(data.id);
            svcPricesUpdate();
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
                    bff.ajax(bff.ajaxURL('bbs','form&ev=catsList'), {parent:parentID, device:device}, function(data){
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

    function catForm(id)
    {
        if( cat.cacheF.hasOwnProperty(id) ) {
            var data = cat.cacheF[id];
            // cat_type, dp, price
            cat.$form.html( data.form );
            // owner_type
            cat.$owner.html( data.owner );
            // photos limit
            var photosLimit = intval(data.photos);
            img.type.$ajax.find('.j-img-slot').hide().filter(':lt('+photosLimit+')').show();
            img.type.$simple.find('.j-img-slot').hide().filter(':lt('+photosLimit+')').show();
            img.uploader.setLimit(photosLimit);
            // addr
            geo.addr.$block.toggle( intval(data.addr) > 0 );
            if(intval(data.addr) > 0){
                geoMapInit();
            }
        } else {
            bff.ajax(bff.ajaxURL('bbs','item-form-cat'), {id:id}, function(data){
                if(data && data.success) {
                    cat.cacheF[id] = data;
                    catForm(id);
                }
            });
        }
    }

    function catType(id)
    {
        if( o.catTypesEx ) return;
        if( intval(id) == intval(o.catTypeSeek) ) {
            cat.$form.find('.j-dp:not(.j-dp-ex-in_seek)').addClass('hide');
        } else {
            cat.$form.find('.j-dp.hide').removeClass('hide');
            cat.$form.find('.j-dp-child-hidden:not(.hide)').addClass('hide');
        }
    }

    function imgProgress(id, step, data)
    {
        var $slot, $preview;
        switch(step)
        {
            case 'start': {
                $slot = img.type.$ajax.find('.j-img-slot:not(.'+img.classes.active+'):visible:first');
                if( $slot.length ) {
                    $slot.addClass(img.classes.active);
                    if (data === false) {
                        $slot.find('.j-img-upload').hide();
                        $slot.find('.j-img-loading').show();
                    }
                    img.active[id] = {slot:$slot,data:false};
                } else {
                    return false;
                }
            } break;
            case 'preview': {
                if( img.active.hasOwnProperty(id) ) {
                    $slot = img.active[id].slot;
                    $slot.addClass(img.classes.preview);
                    $preview = $slot.find('.j-img-preview');
                    $preview.find('.j-img-link').data('id', id);
                    $preview.find('.j-img-img').attr('src', data.i).data('id', id);
                    $preview.find('.j-img-fn').val(data.filename).attr('name','images['+data.id+']');
                    img.active[id].data = data;
                    $slot.find('.j-img-upload, .j-img-loading').hide();
                    $preview.show();
                }
            } break;
            case 'remove': {
                imgRemove(id);
            } break;
        }
        return true;
    }

    function imgRemove(id)
    {
        if( img.active.hasOwnProperty(id) )
        {
            var $slot = img.active[id].slot;
            var data = img.active[id].data;
            var clearSlot = function(id, $slot) {
                $slot.removeClass(img.classes.active+' '+img.classes.preview+' '+img.classes.first);
                $slot.find('.j-img-loading').hide();
                $slot.find('.j-img-upload').show();
                img.type.$ajax.find('.j-img-slot:visible:last').after($slot);
                img.type.$ajax.find('.j-img-slot:visible:first').addClass(img.classes.first);
                img.uploader.decrementUploaded();
                delete img.active[id];
                imgRotate(true);
            };
            if( data !== false ) {
                bff.ajax(img.url+'delete',
                    {image_id:data.id, filename:data.filename}, function(resp){
                    if(resp && resp.success) {
                        var $preview = $slot.find('.j-img-preview');
                        $preview.hide();
                        $preview.find('.j-img-img').attr('src', '');
                        $preview.find('.j-img-fn').val('').attr('name','');
                        $preview.hide();
                        $slot.find('.j-img-upload').show();
                        clearSlot(id, $slot);
                    } else {
                        app.alert.error(resp.errors);
                    }
                });
            } else {
                clearSlot(id, $slot);
            }
        }
    }

    function imgRotate(update)
    {
        var $slots = img.type.$ajax.find('.j-img-slots');
        if(update === true) {
            $slots.sortable('refresh');
        } else {
            $slots.sortable({
                items: '.'+img.classes.active,
                beforeStop: function(event,ui) {
                    img.type.$ajax.find('.'+img.classes.first).removeClass(img.classes.first);
                    img.type.$ajax.find('.j-img-slot:visible:first').addClass(img.classes.first);
                }
            });
        }
    }

    function geoMapInit()
    {
        if(geo.addr.inited) return;
        if( ! $('#j-i-geo-addr-map').is(':visible')) return;
        geo.addr.inited = 1;

        geo.addr.map = app.map('j-i-geo-addr-map', [geo.addr.$lat.val(), geo.addr.$lon.val()], function(map){
            if (this.isYandex()) {
                map.controls.remove('searchControl');
            }

            geo.addr.mapEditor = bff.map.editor();
            geo.addr.mapEditor.init({
                map: map, version: '2.1',
                coords: [geo.addr.$lat, geo.addr.$lon],
                address: geo.addr.$addr,
                addressKind: 'house',
                updateAddressIgnoreClass: 'typed'
            });

            geo.addr.$addr.bind('change keyup input', $.debounce(function(){
                if( ! $.trim(geo.addr.$addr.val()).length ) {
                    geo.addr.$addr.removeClass('typed');
                } else {
                    geo.addr.$addr.addClass('typed');
                    geoMapSearch();
                }
            }, 700));
            geoMapSearch();
        }, {zoom: 12});
    }

    function geoMapSearch(newCity)
    {
        newCity = newCity || false;

        if( ! geo.addr.mapEditor) { return; }
        var $country = geo.$block.find('.j-geo-city-select-country');
        var country = o.geoCountry;
        if($country.length){
            country = $country.find('option:selected').text();
        }
        var query = [country];
        var city = $.trim( geo.$block.find('.j-geo-city-select-ac').val() );
        if(city) query.push(city);
        var addr = $.trim( geo.addr.$addr.val() );
        if(addr && newCity !== true) query.push(addr);
        query = query.join(', ');
        if( geo.addr.lastQuery == query ) return;
        geo.addr.mapEditor.search( geo.addr.lastQuery = query, false, function(){
            geo.addr.mapEditor.centerByMarker();
        } );
    }

    function userPhonesInit(limit, data)
    {
        var index  = 0, total = 0;
        var $block = $form.find('#j-i-phones');

        function add(value)
        {
            if(limit>0 && total>=limit) return;
            index++; total++;
            $block.append('<div class="i-formpage__contacts__item">'+
                                '<div class="input-prepend">'+
                                    '<span class="add-on"><i class="ico ico__phone-dark"></i></span>'+
                                    '<input type="tel" maxlength="30" name="phones['+index+']" value="'+value.replace(/"/g, "&quot;")+'" class="input-large j-phone" '+(o.contactsFromProfile ? ' readonly="readonly"' : '')+' placeholder="'+ o.lang.phones_tip+'" />'+
                                '</div>'+
                                (total==1 && ! o.contactsFromProfile ? '&nbsp;<a class="pseudo-link-ajax j-plus" href="#"><small>'+ o.lang.phones_plus+'</small></a>':'')+
                           '</div>');
        }

        function view(data)
        {
            index  = 0; total = 0;
            $block.html('');
            data = data || {};
            for(var i in data) {
                if( data.hasOwnProperty(i) ) {
                    add(data[i].v);
                }
            }
            if( ! total && ! o.contactsFromProfile ) {
                add('');
            }
        }

        if ( ! o.contactsFromProfile) {
            $block.on('click', '.j-plus', function(e){ nothing(e);
                add('');
            });
        }

        view(data);

        return {
            view: view,
            phoneRequired: function(alert){
                var entered = false, i = 1;
                var $phones = $block.find('.j-phone').removeClass('input-error');
                $phones.each(function(){
                    if ($(this).val().length > 0) entered = true;
                    else if (i==1){ $(this).addClass('input-error'); }
                    i++;
                });
                if(!entered){
                    if (alert) {
                        app.alert.error(o.lang.phones_req);
                        $phones.get(0).focus();
                    }
                    return true;
                }
                return false;
            }
        };
    }

    function svcPricesUpdate()
    {
        var catID = intval(cat.$id.val());
        var cityID = intval(geo.$block.find('.j-geo-city-select-id').val());
        var $svcBlock = $form.find('.j-svc-block');
        if ( catID > 0 && cityID > 0 && $svcBlock.length )
        {
            bff.ajax(bff.ajaxURL('bbs','item-form-svc-prices'), {cat:catID, city:cityID}, function(data){
                if(data && data.success) {
                    for(var i in data.prices) {
                        $svcBlock.find('.j-svc-'+i+' .j-price').html(data.prices[i]);
                    }
                    $svcBlock.show();
                }
            });
        }
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = $.extend(o, options || {});
            $(function(){
                init();
            });
        },
        dpSelect: function(id, val, prefix)
        {
            var key = id+'-'+val;
            if( ! intval(val)) cat.cacheD[key] = {form:''};
            if( cat.cacheD.hasOwnProperty(key) ) {
                var data = cat.cacheD[key];
                $('.j-dp-child-'+id, $form).html( ( data.form.length ? data.form : '') ).
                    closest('.j-control-group').toggleClass('hide j-dp-child-hidden', ! data.form.length);
            } else {
                bff.ajax(bff.ajaxURL('bbs','dp-child'), {dp_id:id, dp_value:val, name_prefix:prefix, search:false}, function(data){
                    if(data && data.success) {
                        cat.cacheD[key] = data;
                        jForm.dpSelect(id, val);
                    }
                });
            }
        },
        onCitySelect: function(cityID, cityTitle, ex){
            geo.fn.onCity(cityID, ex);
        }
    };
}());