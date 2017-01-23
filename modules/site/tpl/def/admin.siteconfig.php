<?php
    tpl::includeJS('tablednd', true);
    Geo::mapsAPI(true);
    tplAdmin::adminPageSettings(array('icon'=>false));
?>
<style type="text/css">
    .j-city { cursor: pointer; padding-left: 5px; border-radius: 3px; margin-bottom: 1px; }
    .j-city-row-drag { background-color: #d3d2d4; }
    .j-city:hover:not(.j-city-selected) { background-color: #eee; }
    .j-city-selected { background-color: #ddd; }
    .j-city .cnt { color: grey; float:right; }

    .j-country { cursor: pointer; padding-left: 5px; border-radius: 3px; margin-bottom: 1px; }
    .j-country-row-drag { background-color: #d3d2d4; }
    .j-country:hover:not(.j-country-selected) { background-color: #eee; }
    .j-country-selected { background-color: #ddd; }
    .j-country .cnt { color: grey; float:right; }
</style>
<form method="post" action="" id="j-site-config-form" onsubmit="return jSiteConfig.onSubmit();">
<input type="hidden" name="saveconfig" value="1" />
<input type="hidden" name="tab" value="<?= HTML::escape($tab) ?>" class="j-tab-current" />
<div class="tabsBar j-tabs">
    <? foreach($tabs as $k=>$v){ ?>
        <span class="tab j-tab<? if($k==$tab){ ?> tab-active<? } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
    <? } ?>
</div>
<div style="margin:15px 0;">

<!-- general -->
<div id="j-tab-general" class="j-tab-form">
    <table class="admtbl tbledit"> 
    <?= $this->locale->buildForm($aData, 'siteconfig-general', '
    <tr>
        <td class="row1 field-title" style="width:140px;">Название сайта:</td>
        <td class="row2">
             <input class="stretch lang-field" type="text" name="title[<?= $key ?>]" value="<?= $aData[\'title\'][$key] ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1 field-title">Название сайта:<br /><span class="desc small">(панель администратора)</span></td>
        <td class="row2">
             <input class="stretch lang-field" type="text" name="title_admin[<?= $key ?>]" value="<?= $aData[\'title_admin\'][$key] ?>" />
        </td>
    </tr>
    <tr>
        <td colspan="2"><hr class="cut" /></td>
    </tr>
    <tr>
        <td class="row1 field-title">Копирайт:</td>
        <td class="row2"><?= tpl::jwysiwyg($aData[\'copyright\'][$key], \'copyright[\'.$key.\']\', 0, 115); ?></td>
    </tr>
    '); ?>
    </table>
</div>

<!-- contact -->
<div id="j-tab-contact" class="j-tab-form" style="display: none;">
    <table class="admtbl tbledit">
    <?= $this->locale->buildForm($aData, 'siteconfig-contact', '
    <tr>
        <td class="row1 field-title" style="width:140px;">Заголовок страницы:</td>
        <td class="row2">
             <input class="stretch lang-field" type="text" name="contacts_form_title[<?= $key ?>]" value="<?= $aData[\'contacts_form_title\'][$key] ?>" />
        </td>
    </tr>
    <tr>
        <td class="row1 field-title">Текст страницы:</td>
        <td class="row2"><?= tpl::jwysiwyg($aData[\'contacts_form_text\'][$key], \'contacts_form_text[\'.$key.\']\', 0, 115); ?></td>
    </tr>
    <tr>
        <td class="row1 field-title">Заголовок формы контактов:</td>
        <td class="row2">
             <input class="stretch lang-field" type="text" name="contacts_form_title2[<?= $key ?>]" value="<?= $aData[\'contacts_form_title2\'][$key] ?>" />
        </td>
    </tr>
    '); ?>
    </table>
</div>

<!-- geo -->
<div id="j-tab-geo" class="j-tab-form" style="display: none;">
    <? if($geo_disabled) { ?>
    <div class="alert alert-info" style="margin-bottom: 10px;">
        Изменение настроек ограничено поскольку в выбранных регионах уже опубликованы: <?= '<strong>'.join('</strong>, <strong>', $geo_disabled_counters).'</strong>'; ?>
    </div>
    <? } ?>
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title" style="width:125px;">Формирования URL:</td>
            <td class="row2">
                <select name="geo_url" class="j-geo-url-select"<? if($geo_disabled) { ?> disabled="disabled"<? } ?> style="width: 193px;">
                <? foreach($geo_url_settings as $k=>$v) { ?>
                    <option value="<?= $k ?>" data-ex="<?= HTML::escape($v['ex']) ?>" <? if($geo_url == $k){ ?> selected="selected" <? } ?>><?= $v['t'] ?></option>
                <? } ?>
                </select>
                <? if($geo_disabled) { ?><input type="hidden" name="geo_url" value="<?= HTML::escape($geo_url) ?>" /><? } ?>
                <span class="help-inline j-geo-url-example"><?= $geo_url_settings[$geo_url]['ex'] ?></span>
                <div class="alert alert-info hide j-geo-url-warning" style="margin-top: 5px;">
                    При изменении типа формирование URL, ссылки на уже существующие объявления<? if(bff::shopsEnabled()){ ?> и магазины<? } ?> будут изменены.<br />
                    Предыдущие ссылки будут по прежнему работать, но уже с перенаправлением.
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="cut" /></td>
        </tr>
        <tr>
            <td class="row1 field-title">Фильтр по региону:</td>
            <td class="row2">
                <select name="geo_covering" class="j-geo-covering-select" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> style="width: 193px;">
                <? foreach($geo_covering_settings as $k=>$v) { ?>
                    <option value="<?= $k ?>" <? if($geo_covering == $k){ ?> selected="selected" <? } ?>><?= $v['t'] ?></option>
                <? } ?>
                </select>
                <? if($geo_disabled) { ?><input type="hidden" name="geo_covering" value="<?= HTML::escape($geo_covering) ?>" /><? } ?>
                <div class="j-geo-covering-<?= Geo::COVERING_COUNTRIES ?><? if($geo_covering != Geo::COVERING_COUNTRIES){ ?> hide<? } ?> j-geo-covering-settings" style="margin: 5px 0;">
                    <div>
                        <span class="bold" style="">Доступные страны:</span>
                        <span class="bold" style="margin-left: 120px;">Выбранные страны:</span>
                    </div>
                    <div class="j-geo-covering-countries-available" style="overflow-y:scroll; overflow-x:hidden; padding:5px; background-color: #fff; width: 180px; height: 230px; border: 1px solid #ccc; border-radius: 5px; margin: 5px 0 0 0; float: left;">
                        <? foreach($geo_covering_lvl[Geo::lvlCountry]['items'] as &$v) {
                            if( ! empty($geo_covering_countries[ $v['id'] ])) continue; ?>
                            <div class="j-country" data-id="<?= $v['id'] ?>"><span class="j-title"><?= $v['title'] ?></span></div>
                        <? } unset($v); ?>
                    </div>
                    <div class="left" style="padding-top: 65px; margin: 0 10px;">
                        <div class="j-geo-covering-countries-select-arrows">
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="add-all" value="&gt;&gt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="add-selected" value="&gt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="remove-selected" value="&lt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="remove-all" value="&lt;&lt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                        </div>
                    </div>
                    <div style="overflow-y:scroll; overflow-x:hidden; padding:5px; background-color: #fff; width: 180px; height: 230px; border: 1px solid #ccc; border-radius: 5px; margin: 5px 5px 0 0; float: left;">
                        <table style="width: 100%;">
                            <tbody class="j-geo-covering-countries-selected">
                            <? if ( ! empty($geo_covering_countries)) {
                                foreach($geo_covering_countries as &$v) {
                                    ?><tr>
                                    <td class="j-country" data-id="<?= $v['id'] ?>" data-cnt="<?= ($v['items_cnt']) ?>">
                                        <span class="j-title"><?= $v['title'] ?></span>
                                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_COUNTRIES ?>][<?= Geo::lvlCountry ?>][]" value="<?= $v['id'] ?>" />
                                        <div class="cnt"><?= $v['items_cnt'] ?></div>
                                    </td>
                                    </tr><?
                                } unset($v);
                            } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="j-geo-covering-<?= Geo::COVERING_COUNTRY ?><? if($geo_covering != Geo::COVERING_COUNTRY){ ?> hide<? } ?> j-geo-covering-settings" style="margin: 5px 0;">
                    <select name="geo_covering_lvl[<?= Geo::COVERING_COUNTRY ?>][<?= Geo::lvlCountry ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlCountry ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlCountry]['options'] ?></select>
                    <? if($geo_disabled) { ?>
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_COUNTRY ?>][<?= Geo::lvlCountry ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlCountry]['selected']) ?>" />
                    <? } ?>
                </div>
                <div class="j-geo-covering-<?= Geo::COVERING_REGION ?><? if($geo_covering != Geo::COVERING_REGION){ ?> hide<? } ?> j-geo-covering-settings" style="margin: 5px 0;">
                    <select name="geo_covering_lvl[<?= Geo::COVERING_REGION ?>][<?= Geo::lvlCountry ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlCountry ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlCountry]['options'] ?></select>
                    <select name="geo_covering_lvl[<?= Geo::COVERING_REGION ?>][<?= Geo::lvlRegion ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlRegion ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlRegion]['options'] ?></select>
                    <? if($geo_disabled) { ?>
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_REGION ?>][<?= Geo::lvlCountry ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlCountry]['selected']) ?>" />
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_REGION ?>][<?= Geo::lvlRegion ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlRegion]['selected']) ?>" />
                    <? } ?>
                </div>
                <div class="j-geo-covering-<?= Geo::COVERING_CITIES ?><? if($geo_covering != Geo::COVERING_CITIES){ ?> hide<? } ?> j-geo-covering-settings" style="margin: 5px 0;">
                    <div>
                        <select name="geo_covering_lvl[<?= Geo::COVERING_CITIES ?>][<?= Geo::lvlCountry ?>]" class="j-geo-covering-dd" data-lvl="<?= Geo::lvlCountry ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlCountry]['options'] ?></select><br />
                        <select name="geo_covering_lvl[<?= Geo::COVERING_CITIES ?>][<?= Geo::lvlRegion ?>]" class="j-geo-covering-dd" data-lvl="<?= Geo::lvlRegion ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlRegion]['options'] ?></select>
                        <span class="bold" style="margin-left: 45px;">Выбранные города:</span>
                    </div>
                    <div class="j-geo-covering-city-available" style="overflow-y:scroll; overflow-x:hidden; padding:5px; background-color: #fff; width: 180px; height: 230px; border: 1px solid #ccc; border-radius: 5px; margin: 5px 0 0 0; float: left;">
                        <? foreach($geo_covering_lvl[Geo::lvlCity]['items'] as &$v) { ?>
                            <div class="j-city" data-id="<?= $v['id'] ?>"><span class="j-title"><?= $v['title'] ?></span></div>
                        <? } unset($v); ?>
                    </div>
                    <div class="left" style="padding-top: 65px; margin: 0 10px;">
                        <div class="j-geo-covering-city-select-arrows">
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="add-all" value="&gt;&gt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="add-selected" value="&gt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="remove-selected" value="&lt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                            <div class="button">
                                <span class="left"></span>
                                <input type="button" class="btn btn-mini button j-arrow" data-action="remove-all" value="&lt;&lt;" style="width: 25px; margin-bottom:2px;">
                            </div>
                        </div>
                    </div>
                    <div style="overflow-y:scroll; overflow-x:hidden; padding:5px; background-color: #fff; width: 180px; height: 230px; border: 1px solid #ccc; border-radius: 5px; margin: 5px 5px 0 0; float: left;">
                        <table style="width: 100%;">
                            <tbody class="j-geo-covering-city-selected">
                            <? if ( ! empty($geo_covering_cities)) {
                                foreach($geo_covering_cities as &$v) {
                                    ?><tr>
                                        <td class="j-city" data-id="<?= $v['id'] ?>" data-cnt="<?= ($v['items_cnt']) ?>">
                                            <span class="j-title"><?= $v['title'] ?></span>
                                            <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_CITIES ?>][<?= Geo::lvlCity ?>][]" value="<?= $v['id'] ?>" />
                                            <div class="cnt"><?= $v['items_cnt'] ?></div>
                                        </td>
                                      </tr><?
                                } unset($v);
                            } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="j-geo-covering-<?= Geo::COVERING_CITY ?><? if($geo_covering != Geo::COVERING_CITY){ ?> hide<? } ?> j-geo-covering-settings" style="margin: 5px 0;">
                    <select name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlCountry ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlCountry ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlCountry]['options'] ?></select>
                    <select name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlRegion ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlRegion ?>" style="width: 193px;"><?= $geo_covering_lvl[Geo::lvlRegion]['options'] ?></select>
                    <select name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlCity ?>]" <? if($geo_disabled) { ?>disabled="disabled"<? } ?> class="j-geo-covering-dd" data-lvl="<?= Geo::lvlCity ?>" style="width: 193px;<? if( ! $geo_covering_lvl[Geo::lvlRegion]['selected']){ ?> display: none;<? } ?>"><?= $geo_covering_lvl[Geo::lvlCity]['options'] ?></select>
                    <? if($geo_disabled) { ?>
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlCountry ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlCountry]['selected']) ?>" />
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlRegion ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlRegion]['selected']) ?>" />
                        <input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_CITY ?>][<?= Geo::lvlCity ?>]" value="<?= HTML::escape($geo_covering_lvl[Geo::lvlCity]['selected']) ?>" />
                    <? } ?>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2"><hr class="cut" /></td>
        </tr>
        <tr>
            <td class="row1 field-title">Точка на карте<br>по-умолчанию:</td>
            <td class="row2">
                <input type="hidden" name="geo_default_coords" id="j-geo-default-coords" value="<?= HTML::escape($aData['geo_default_coords']) ?>" />
                <div id="j-geo-default-coords-map" class="map-google" style="height: 400px; width: 100%;"></div>
            </td>
        </tr>
    </table>
</div>

<!-- offline -->
<div id="j-tab-offline" class="j-tab-form" style="display: none;">
    <table class="admtbl tbledit">
        <tr>
            <td class="row1 field-title" style="width:140px;">Режим:</td>
            <td class="row2">
                <label class="radio inline"><input type="radio" name="enabled" <? if($aData['enabled']) { ?> checked="checked" <? } ?> class="j-offline-switch" value="1" />включен</label>
                <label class="radio inline"><input type="radio" name="enabled" <? if( ! $aData['enabled']) { ?> checked="checked" <? } ?> class="j-offline-switch" value="0" />выключен<span class="desc"> - для проведения технических работ</span></label>
                <div class="j-offline-link" style="margin: 7px 0;<? if($aData['enabled']) { ?> display:none;<? } ?>">Для работы с сайтом в этом режиме, перейдите по <a href="<?= Site::offlineIgnore('generate-url') ?>" target="_blank" class="bold">ссылке</a>.</div>
            </td>
        </tr>
        <?= $this->locale->buildForm($aData, 'siteconfig-offline', '
        <tr>
            <td class="row1 field-title">Причина выключения:</td>
            <td class="row2"><?= tpl::jwysiwyg($aData[\'offline_reason\'][$key], \'offline_reason[\'.$key.\']\', 0, 115); ?></td>
        </tr>
        '); ?>
    </table>
</div>

</div>
<hr class="cut" />
<div class="footer">
    <input type="submit" class="btn btn-success button submit" value="Сохранить настройки" />
</div>
</form>
<script type="text/javascript">
//<![CDATA[
var jSiteConfig = (function(){
    var $form, currentTab = 'general';
    var geoLevels = {country:<?= Geo::lvlCountry ?>,region:<?= Geo::lvlRegion ?>,city:<?= Geo::lvlCity ?>};
    var $geoCoveringSelect, map = false;
    var geoCoveringTypes = {country:<?= Geo::COVERING_COUNTRY ?>, region:<?= Geo::COVERING_REGION ?>,
                            cities:<?= Geo::COVERING_CITIES ?>, city:<?= Geo::COVERING_CITY ?>};

    $(function(){
        $form = $('#j-site-config-form');
        $form.find('.j-tabs').on('click', '.j-tab', function(){
            onTab($(this).data('tab'), this);
        });
        onTab('<?= $tab ?>', 0);

        $('.j-geo-url-select').on('change', function(){
            $('.j-geo-url-example').html($(this).find('option:selected').data('ex'));
            $('.j-geo-url-warning').removeClass('hide');
        });

        $geoCoveringSelect = $('.j-geo-covering-select');
        $geoCoveringSelect.on('change', function(){
            $('.j-geo-covering-settings').addClass('hide');
            $('.j-geo-covering-'+$(this).val()).removeClass('hide');
        });
        $('.j-geo-covering-dd').on('change', function(){
            onGeoCoveringRegionSelect(intval($(this).data('lvl')), intval($(this).val()));
        });
        var $cityAvailable = $('.j-geo-covering-city-available');
        var $citySelected = $('.j-geo-covering-city-selected');
        $cityAvailable.on('click', '.j-city', function(){ $(this).toggleClass('j-city-selected'); });
        $citySelected.on('click', '.j-city', function(){ $(this).toggleClass('j-city-selected'); });
        onGeoCoveringRotate($citySelected, false, 'j-city-row-drag');
        $('.j-geo-covering-city-select-arrows').on('click', '.j-arrow', function(){
            var action = $(this).data('action');
            if (action == 'add-all') {
                if ( ! $cityAvailable.find('.j-city').length) {
                    bff.error('Выберите область'); return;
                }
                onGeoCoveringCitiesAdd($cityAvailable.find('.j-city'), $cityAvailable, $citySelected);
            } else if (action == 'add-selected') {
                var $selected = $cityAvailable.find('.j-city-selected');
                if ( ! $selected.length) {
                    bff.error('Отметьте необходимые города'); return;
                }
                onGeoCoveringCitiesAdd($selected, $cityAvailable, $citySelected);
            } else if (action == 'remove-all') {
                if ( ! $citySelected.find('.j-city').length) {
                    return;
                }
                onGeoCoveringCitiesRemove($citySelected.find('.j-city'), $citySelected, $cityAvailable);
            } else if (action == 'remove-selected') {
                var $selected = $citySelected.find('.j-city-selected');
                if ( ! $selected.length) {
                    bff.error('Отметьте необходимые города'); return;
                }
                onGeoCoveringCitiesRemove($selected, $citySelected, $cityAvailable);
            }
        });

        var $countryAvailable = $('.j-geo-covering-countries-available');
        var $countrySelected = $('.j-geo-covering-countries-selected');
        $countryAvailable.on('click', '.j-country', function(){ $(this).toggleClass('j-country-selected'); });
        $countrySelected.on('click', '.j-country', function(){ $(this).toggleClass('j-country-selected'); });
        onGeoCoveringRotate($countrySelected, false, 'j-country-row-drag');
        $('.j-geo-covering-countries-select-arrows').on('click', '.j-arrow', function(){
            var action = $(this).data('action');
            if (action == 'add-all') {
                onGeoCoveringCountriesAdd($countryAvailable.find('.j-country'), $countryAvailable, $countrySelected);
            } else if (action == 'add-selected') {
                var $selected = $countryAvailable.find('.j-country-selected');
                if ( ! $selected.length) {
                    bff.error('Отметьте необходимые страны'); return;
                }
                onGeoCoveringCountriesAdd($selected, $countryAvailable, $countrySelected);
            } else if (action == 'remove-all') {
                if ( ! $countrySelected.find('.j-country').length) {
                    return;
                }
                onGeoCoveringCountriesRemove($countrySelected.find('.j-country'), $countrySelected, $countryAvailable);
            } else if (action == 'remove-selected') {
                var $selected = $countrySelected.find('.j-country-selected');
                if ( ! $selected.length) {
                    bff.error('Отметьте необходимые страны'); return;
                }
                onGeoCoveringCountriesRemove($selected, $countrySelected, $countryAvailable);
            }
        });

        <? if($tab == 'geo'): ?>mapInit();<? endif; ?>

        $('.j-offline-switch').on('click', function(){
            $('.j-offline-link').toggle(intval($(this).val()) === 0);
        });
    });

    function mapInit()
    {
        if(map) return;
        map = bff.map.init('j-geo-default-coords-map', [<?= HTML::escape($aData['geo_default_coords']) ?>], function(map){
            var editor = bff.map.editor();
            editor.init({
                map: map, version: '2.1',
                coords: $('#j-geo-default-coords'),
                addressKind: 'house',
                updateAddressIgnoreClass: 'typed'
            });
        }, {zoom:5});

    }

    function onTab(tab, link)
    {
        if(currentTab == tab)
            return;

        $form.find('.j-tab-form').hide();
        $form.find('#j-tab-'+tab).show();

        bff.onTab(link);
        currentTab = tab;
        $form.find('.j-tab-current').val(tab);

        if(tab == 'geo'){
            mapInit();
        }

        if(bff.h) {
            window.history.pushState({}, document.title, '<?= $this->adminLink(bff::$event) ?>&tab='+tab);
        }
    }

    var geoCoveringRegionsCache = {};
    function onGeoCoveringRegionSelect(lvl, id)
    {
        if ( lvl >= geoLevels.city) return;
        var cacheKey = lvl+'-'+id;
        if ( ! geoCoveringRegionsCache.hasOwnProperty(cacheKey))
        {
            if ( ! id) {
                $form.find('.j-geo-covering-dd[data-lvl="'+lvl+'"][value!='+id+']').val(id);
                $form.find('.j-geo-covering-dd[data-lvl="'+(lvl+1)+'"]').hide();
                $form.find('.j-geo-covering-dd[data-lvl="'+(lvl+2)+'"]').hide();
                $form.find('.j-geo-covering-city-available').html('');
            } else {
                bff.ajax('<?= $this->adminLink(bff::$event.'&act=geo-covering-options') ?>', {lvl:lvl,id:id}, function(data){
                    if(data && data.success) {
                        geoCoveringRegionsCache[cacheKey] = data;
                        onGeoCoveringRegionSelect(lvl, id);
                    }
                });
            }
        } else {
            var data = geoCoveringRegionsCache[cacheKey];
            $form.find('.j-geo-covering-dd[data-lvl="'+lvl+'"][value!='+id+']').val(id);
            $form.find('.j-geo-covering-dd[data-lvl="'+(lvl+1)+'"]').html(data.options).show();
            $form.find('.j-geo-covering-dd[data-lvl="'+(lvl+2)+'"]').hide();
            if (lvl == geoLevels.region) {
                var cityAvailable = '';
                for(var i in data.items) {
                    cityAvailable += '<div class="j-city" data-id="'+i+'"><span class="j-title">'+data.items[i]+'</span></div>';
                }
                $form.find('.j-geo-covering-city-available').html(cityAvailable);
            } else {
                $form.find('.j-geo-covering-city-available').html('');
            }
        }
    }

    function onGeoCoveringCitiesAdd($items, $from, $to)
    {
        var items = [], j = 0;
        $items.each(function(){
            items[j++] = {id:$(this).data('id'), title:$(this).find('.j-title').text()};
        });
        for(var i in items)
        {
            var v = items[i];
            var $item = $from.find('[data-id="'+v.id+'"]');
            if ( ! $to.find('.j-city[data-id="'+v.id+'"]').length)
            {
                $item.remove();
                $to.append('<tr><td class="j-city" data-id="'+v.id+'" data-cnt="0"><span class="j-title">'+v.title+'</span>'+
                    '<input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_CITIES ?>]['+geoLevels.city+'][]" value="'+ v.id+'" />'+
                    '<div class="cnt"></div></td></tr>');
            } else {
                delete items[i];
                $item.remove();
            }
        }
        onGeoCoveringRotate($to, true, 'j-city-row-drag');
    }

    function onGeoCoveringCitiesRemove($items, $from, $to)
    {
        var items = [], j = 0, k = 0, unableRemove = [];
        $items.each(function(){
            if (intval($(this).data('cnt')) > 0) {
                unableRemove[k++] = $.trim($(this).find('.j-title').text());
            } else {
                items[j++] = {id:$(this).data('id'), title:$(this).find('.j-title').text()};
            }
        });
        for(var i in items)
        {
            var v = items[i];
            var $item = $from.find('[data-id="'+v.id+'"]');
            if ( ! $to.find('.j-city[data-id="'+v.id+'"]').length)
            {
                $item.parent().remove();
                $to.append('<div class="j-city" data-id="'+v.id+'"><span class="j-title">'+v.title+'</span></div>');
            } else {
                delete items[i];
                $item.parent().remove();
            }
        }
        onGeoCoveringRotate($from, true, 'j-city-row-drag');
        if (unableRemove.length) {
            if (unableRemove.length > 1) {
                bff.error('В городах <strong>'+unableRemove.join('</strong>, <strong>')+'</strong> есть опубликованные объявления<?= (bff::shopsEnabled() ? ' или магазины' : '') ?>');
            } else {
                bff.error('В городе <strong>'+unableRemove.join('')+'</strong> есть опубликованные объявления<?= (bff::shopsEnabled() ? ' или магазины' : '') ?>');
            }
        }
    }

    function onGeoCoveringCountriesAdd($items, $from, $to)
    {
        var items = [], j = 0;
        $items.each(function(){
            items[j++] = {id:$(this).data('id'), title:$(this).find('.j-title').text()};
        });
        for(var i in items)
        {
            var v = items[i];
            var $item = $from.find('[data-id="'+v.id+'"]');
            if ( ! $to.find('.j-country[data-id="'+v.id+'"]').length)
            {
                $item.remove();
                $to.append('<tr><td class="j-country" data-id="'+v.id+'" data-cnt="0"><span class="j-title">'+v.title+'</span>'+
                    '<input type="hidden" name="geo_covering_lvl[<?= Geo::COVERING_COUNTRIES ?>]['+geoLevels.country+'][]" value="'+ v.id+'" />'+
                    '<div class="cnt"></div></td></tr>');
            } else {
                delete items[i];
                $item.remove();
            }
        }
        onGeoCoveringRotate($to, true, 'j-country-row-drag');
    }

    function onGeoCoveringCountriesRemove($items, $from, $to)
    {
        var items = [], j = 0, k = 0, unableRemove = [];
        $items.each(function(){
            if (intval($(this).data('cnt')) > 0) {
                unableRemove[k++] = $.trim($(this).find('.j-title').text());
            }
            items[j++] = {id:$(this).data('id'), title:$(this).find('.j-title').text()};
        });
        for(var i in items)
        {
            var v = items[i];
            var $item = $from.find('[data-id="'+v.id+'"]');
            if ( ! $to.find('.j-country[data-id="'+v.id+'"]').length)
            {
                $item.parent().remove();
                $to.append('<div class="j-country" data-id="'+v.id+'"><span class="j-title">'+v.title+'</span></div>');
            } else {
                delete items[i];
                $item.parent().remove();
            }
        }
        onGeoCoveringRotate($from, true, 'j-country-row-drag');
        if (unableRemove.length) {
            if (unableRemove.length > 1) {
                bff.error('В странах <strong>'+unableRemove.join('</strong>, <strong>')+'</strong> есть опубликованные объявления<?= (bff::shopsEnabled() ? ' или магазины' : '') ?>');
            } else {
                bff.error('В стране <strong>'+unableRemove.join('')+'</strong> есть опубликованные объявления<?= (bff::shopsEnabled() ? ' или магазины' : '') ?>');
            }
        }
    }

    function onGeoCoveringRotate($list, update, dragClass)
    {
        if (update) {
            $list.tableDnDUpdate();
        } else {
            $list.tableDnD({onDragClass: dragClass});
        }
    }

    return {
        onSubmit: function()
        {
            var geoCoveringType = intval( $geoCoveringSelect.val() );
            if ( geoCoveringType == geoCoveringTypes.country ) {
                if ( ! intval($form.find('.j-geo-covering-dd[data-lvl="'+geoLevels.country+'"]').val())) {
                    bff.error('Выберите страну для фильтра по региону');
                    return false;
                }
            } else if ( geoCoveringType == geoCoveringTypes.region ) {
                if ( ! intval($form.find('.j-geo-covering-dd[data-lvl="'+geoLevels.region+'"]').val())) {
                    bff.error('Выберите область для фильтра по региону');
                    return false;
                }
            } else if ( geoCoveringType == geoCoveringTypes.cities ) {
                if ( ! $form.find('.j-geo-covering-city-selected .j-city').length ) {
                    bff.error('Выберите города для фильтра по региону');
                    return false;
                }
            } else if ( geoCoveringType == geoCoveringTypes.city ) {
                var $citySelect = $form.find('.j-geo-covering-dd[data-lvl="'+geoLevels.city+'"]');
                if ($citySelect.length && ! intval($citySelect.val())) {
                    bff.error('Выберите город для фильтра по региону');
                    return false;
                }
            }
            return true;
        }
    };
}());
//]]>
</script>