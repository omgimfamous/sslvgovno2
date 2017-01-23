<?php
    /**
     * @var $this Geo
     */
    $edit = ($id > 0);
?>

<?= tplAdmin::blockStart('Регионы / Редактировать город', false); ?>
    <form action="" name="geoCityForm" method="post">
    <input type="hidden" name="id" value="<?= $id ?>" />
    <input type="hidden" name="country" value="<?= $country ?>" />
    <input type="hidden" name="numlevel" value="<?= $numlevel ?>" />
    <table class="admtbl tbledit">
    <?= $this->locale->buildForm($aData, 'city-item',
   '<tr>
        <td class="row1" style="width:100px;"><span class="field-title">Название</span>:</td>
        <td class="row2"><input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]) ?>" class="stretch lang-field city-title" rel="title-<?= $key ?>" /></td>
    </tr>'); ?>
    <?php if(Geo::manageRegions(Geo::lvlRegion)) { ?>
    <tr class="required">
        <td class="row1" style="width:100px;"><span class="field-title">Область</span>:</td>
        <td class="row2">
            <select name="pid" id="city-region"><?= $regions_options ?></select>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <td class="row1"><span class="field-title">Координаты</span>:</td>
        <td class="row2">
            <input type="text" value="<?= $ycoords ?>" maxlength="30" name="ycoords" id="city-ycoords" class="desc" />
            <input type="button" class="btn btn-mini button submit" onclick="jGeoCity.cityCoordsUpdate();" value="обновить" />
            <input type="button" class="btn btn-mini button submit" onclick="jGeoCity.cityMap(this);" value="на карте" />
            <span id="progress-ycoords" style="margin-left:5px; display:none;" class="progress"></span>
            <div id="city-map-block" style="display: none;">
                <div id="city-map" style="height:380px;"></div>
            </div>
        </td>
    </tr>
    <tr>
        <td><span class="field-title">URL-keyword</span>:</td>
        <td><input type="text" name="keyword" value="<?= HTML::escape($keyword) ?>" class="stretch" maxlength="30" /></td>
    </tr>
    <?php if( Geo::manageRegions(Geo::lvlMetro) ): ?>
    <tr>
        <td><span class="field-title">Есть метро</span>:</td>
        <td><label class="checkbox"><input type="checkbox" name="metro" <?php if($metro){ ?> checked="checked"<?php } ?> /></label></td>
    </tr>
    <?php endif; ?>
    <tr class="footer">
        <td colspan="3">
            <input type="submit" class="btn btn-success button submit" value="Сохранить" />
            <input type="button" class="btn button cancel" value="Отмена" onclick="document.location='<?= $redirect ?>';" />
        </td>
    </tr>

    </table>
    </form>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart('Районы города'); ?>
    <table class="table table-condensed table-hover admtbl">
        <thead>
            <tr class="header">
                <?php if(FORDEV){ ?><th width="50">ID</th><?php } ?>
                <th class="left">Название</th>
                <th width="105">Действие</th>
            </tr>
        </thead>
        <tbody>
        <?php if( ! empty($districts) ) { foreach($districts as $v) { ?>
        <tr class="row1">
            <?php if(FORDEV){ ?><td><?= $v['id'] ?></td><?php } ?>
            <td class="left"><?= $v['title'] ?></td>
            <td>
                <a class="but edit" title="Редактировать" href="#" onclick="jGeoCity.districtEdit(<?= $v['id'] ?>, '<?= $this->adminLink('regions_city_districts&act=edit&city='.$id) ?>'); return false;"></a>
                <a class="but del" title="Удалить" href="#" onclick="bff.ajaxDelete('Удалить район города?', <?= $v['id'] ?>, '<?= $this->adminLink('regions_city_districts&act=delete&id='.$v['id'].'&city='.$id) ?>', this, {repaint: false}); return false;"></a>
            </td>
        </tr>
        <?php } } else { ?>
        <tr class="norecords">
            <td colspan="<?= (FORDEV?3:2) ?>">у данного города нет районов</td>
        </tr>
        <?php } ?>
        </tbody>
    </table>
    <div>
        <input type="button" class="btn btn-mini button submit" id="district-form-toggler" href="#" onclick="jGeoCity.districtForm(true,false);" value="Добавить район" />
    </div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart('Добавить район города', false, array('id'=>'district-form-block', 'style'=>'display:none;')); ?>
    <form action="<?= $this->adminLink('regions_city_districts') ?>" method="post" name="geoDistrictForm">
        <input type="hidden" name="id" value="0" />
        <input type="hidden" name="act" value="add" />
        <input type="hidden" name="city" value="<?= $id ?>" />
        <input type="hidden" name="pid" value="<?= $pid ?>" />
        <input type="hidden" name="country" value="<?= $country ?>" />
        <table class="admtbl tbledit">
            <?php
            $dataTitle = array();
            echo $this->locale->buildForm($dataTitle, 'geo-city-district', ''.'
                <tr class="required">
                    <td style="width:100px;"><span class="field-title">Название</span>:</td>
                    <td><input type="text" name="title[<?= $key ?>]" value="" class="stretch district-title <?= $key ?>" rel="title-<?= $key ?>" /></td>
                </tr>
            '); ?>
            <tr<?php if( ! FORDEV) { ?> style="display: none;"<?php } ?>>
                <td class="row1"><span class="field-title">YBounds</span>:</td>
                <td class="row2">
                     <input type="text" name="ybounds" id="district-ybounds" value="" class="stretch" />
                </td>
            </tr>
            <tr<?php if( ! FORDEV) { ?> style="display: none;"<?php } ?>>
                <td class="row1"><span class="field-title">YPolygon</span>:</td>
                <td class="row2">
                    <input type="text" name="ypoly" id="district-ypoly" value="" class="desc stretch" />
                </td>
            </tr>
            <tr>
                <td class="row2" colspan="2">
                    <div id="district-map" style="height:380px"></div>
                </td>
            </tr>
            <tr class="footer">
                <td colspan="2" class="row1">
                    <input type="submit" class="btn btn-success button submit" value="Сохранить" />
                    <input type="reset" class="btn button cancel" value="Отмена" onclick="jGeoCity.districtForm(false,false);" />
                </td>
            </tr>
        </table>
    </form>
<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
var jGeoCity = (function(){
    var coordOrder = '<?= Geo::$ymapsCoordOrder ?>';
    var city = {id:'<?= $id ?>', $region:0, $ycoords:0, $ycoords_progress:0, coords:'<?= $ycoords ?>', map:null, mapEditor:null, form:0, formChecker:null};
    var district = {polygons:{}, $ypoly:0, $ybounds:0, map:null, form:0, formChecker:null, $formBlock:0, $formToggler:0};

    $(function(){
        city.form = document.forms.geoCityForm;
        city.formChecker = new bff.formChecker( city.form );
        city.$region = $('#city-region');
        city.$ycoords = $('#city-ycoords');
        city.$ycoords_progress = $('#progress-ycoords');
        ymaps.ready(function(){
            city.map = new ymaps.Map("city-map", {
                    center: coordsToCenter(city.coords),
                    zoom: 10
                }//, {autoFitToViewport: 'always'}
            );
            city.map.controls.add('zoomControl', {top:5,left:5});

            city.mapEditor = bffmapEditorYandex();
            city.mapEditor.init({
                map: city.map,
                coords: city.$ycoords
            }, city.map.getCenter());
        });

        district.form = document.forms.geoDistrictForm;
        district.formChecker = new bff.formChecker( district.form );
        district.$ypoly = $('#district-ypoly');
        district.$ybounds = $('#district-ybounds');
        district.$formBlock = $('#district-form-block');
        district.$formToggler = $('#district-form-toggler');
        ymaps.ready(function(){
            district.map = new ymaps.Map("district-map", {
                    center: coordsToCenter(city.coords),
                    zoom: 10
                }, {autoFitToViewport: 'always'}
            );
            district.map.controls.add('zoomControl', {top:5,left:5});
        });
    });

    function coordsToCenter(coords)
    {
        coords = coords.split(',');
        if( ! coords.length || ! intval(coords[0]) ) {
            coords = ( coordOrder == 'longlat' ?
                [ymaps.geolocation.longitude, ymaps.geolocation.latitude] :
                [ymaps.geolocation.latitude,  ymaps.geolocation.longitude] );
        }
        return coords;
    }

    function getPolyGeometryEncoded(bounds)
    {
        bounds = bounds.split(';');
        bounds[0] = bounds[0].split(',');
        bounds[1] = bounds[1].split(',');
        return ymaps.geometry.Polygon.toEncodedCoordinates(new ymaps.geometry.Polygon([[bounds[0], [bounds[0][0], bounds[1][1]], bounds[1], [bounds[1][0], bounds[0][1]]],[]]));
    }

    function districtPolygon(districtID, polyGeometryEncoded)
    {
        for(var i in district.polygons) {
            district.map.geoObjects.remove(district.polygons[i]);
        }
        var polyGeometry = ymaps.geometry.Polygon.fromEncodedCoordinates(polyGeometryEncoded);
        if(polyGeometry) {
            if( ! district.polygons.hasOwnProperty(districtID)){
                district.polygons[districtID] = new ymaps.Polygon(polyGeometry, {}, {draggable:true, strokeColor:'#ffff00', strokeWidth:2, fillColor:'#ff0000', opacity:0.5});
                district.polygons[districtID].events.add('geometrychange', function(e){
                    district.$ypoly.val( ymaps.geometry.Polygon.toEncodedCoordinates(district.polygons[districtID].geometry) );
                    district.$ybounds.val( district.polygons[districtID].geometry.getBounds().join(';') );
                });
            }
            district.map.geoObjects.add( district.polygons[districtID] );
            district.polygons[districtID].editor.startEditing();
        }
    }

    function districtForm(show, edit)
    {
        if(show)
        {
            district.$formToggler.hide();
            if(edit) {
                  $('span.caption', district.$formBlock).html(edit.caption);
                  for(var lng in edit.title) {
                    $('.district-title.'+lng, district.$formBlock).val(edit.title[lng]);
                  }
                  district.$ypoly.val(edit.ypoly);
                  district.$ybounds.val(edit.ybounds);
                  district.form.elements['id'].value = edit.id;
                  district.form.elements['act'].value = 'edit';

                  if( ! edit.ypoly && ! edit.ybounds) {
                      ymaps.geocode(cityTitle()+', '+edit.title['<?= LNG ?>'], {results: 1}).then(function(res){
                            if(res.geoObjects.getLength()) {
                                var obj = res.geoObjects.get(0);
                                var bounds = obj.properties.get('boundedBy').join(';');
                                var ypoly = getPolyGeometryEncoded(bounds);
                                district.$ybounds.val(bounds);
                                district.$ypoly.val(ypoly);
                                districtPolygon(edit.id, ypoly, edit.title);
                            }
                      });
                  } else {
                    if( ! edit.ypoly ) {
                        edit.ypoly = getPolyGeometryEncoded(edit.ybounds);
                        district.$ypoly.val(edit.ypoly);
                    }
                    districtPolygon(edit.id, edit.ypoly, edit.title);
                  }
            } else {
                var ycoords = $.trim( city.$ycoords.val() ).split(',');
                if(ycoords.length == 2)
                {
                    district.map.setCenter(ycoords);
                    var ypoly = $.trim( district.$ypoly.val() );
                    if(ypoly.length) {
                        districtPolygon(0, ypoly, '');
                    } else {
                        jGeoCity.cityCoordsUpdate();
                    }
                }
            }

            district.$formBlock.slideDown('fast');
            district.formChecker.check();
        } else {
            district.$formBlock.slideUp('fast', function(){
                district.$formToggler.fadeIn('fast');
                $(this).find('span.caption').html('Добавить район города');
                district.$ybounds.val('');
                district.$ypoly.val('');
                district.form.elements['id'].value = 0;
                district.form.elements['act'].value = 'add';
            });
        }
    }

    function cityTitle()
    {
        var Q = '<?= HTML::escape($country_title, 'javascript') ?>';
//        if( intval(city.$region.val()) > 0 ) {
//            Q += ', '+$.trim( city.$region.find('option:selected').text() );
//        }
        Q += ', '+$.trim( $('.city-title:visible', city.$formBlock).val() );
        return Q;
    }

    return {
        cityCoordsUpdate: function(showCityMap){
            city.mapEditor.search(cityTitle(), 0, function(obj){
                var bounds = obj.properties.get('boundedBy').join(';');
                var ypoly = $.trim( district.$ypoly.val() );
                if( ! ypoly.length) {
                    district.$ybounds.val(bounds);
                    ypoly = getPolyGeometryEncoded(bounds);
                    district.$ypoly.val(ypoly);
                    districtPolygon(0, ypoly, '');
                }
            });
        },
        cityMap: function(btn){
            $(btn).hide();
            $('#city-map-block', city.$formBlock).show();
            city.map.container.fitToViewport();
            city.map.setBounds(city.map.getBounds(), {checkZoomRange: true});
        },
        districtForm: districtForm,
        districtEdit: function(districtID, url) {
            bff.ajax(url, {id: districtID}, function(data){
                if(data && data.success) {
                    districtForm(true, $.extend({caption: 'Редактирование района города'}, data) );
                }
            });
        }
    };
}());
</script>