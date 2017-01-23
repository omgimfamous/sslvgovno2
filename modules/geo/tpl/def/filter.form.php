<?php
/**
 * Фильтр региона: layout
 * @var $this Geo
 */

# при покрытии города: скрываем фильтр по региону
$coveringType = Geo::coveringType();
if ($coveringType == Geo::COVERING_CITY) {
    return;
}

# фильтр: регион
$regionID = 0;
$regionData = Geo::filter(); # user
if( ! empty($regionData['id']) ) {
    $regionID = $regionData['id'];
}
$regionLvl = $regionID ? $regionData['numlevel'] : 0;
switch($regionLvl){
    case Geo::lvlCountry;
        $country = $regionData;
        break;
    case Geo::lvlRegion:
    case Geo::lvlCity:
        $country = Geo::regionData($regionData['country']);
        break;
}

/* Фильтр по региону (desktop): popup */
if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) { ?>
    <? if($coveringType == Geo::COVERING_COUNTRIES) {
        ?>
        <div id="j-f-country-desktop-popup" class="f-navigation__region_change dropdown-block box-shadow abs hide">
            <!--for: desktop-->
            <div id="j-f-country-desktop-st0" class="f-navigation__region_change_sub hidden-phone<? if($regionID > 0) { ?> hide<? } ?>">
                <fieldset>
                    <b><?= _t('filter', 'Выберите страну'); ?></b>
                </fieldset>
                <br />
                <?= _t('filter', 'Искать объявления по <a[link]>всем странам</a>', array('link' => ' id="j-f-region-desktop-all" href="'.bff::urlBase().'" data="{id:0,pid:0,title:\''.HTML::escape(_t('filter', 'Все страны'), 'js').'\'}"')) ?>
                <hr />
                <?= $this->filterData('desktop-countries-step0', 0); ?>
                <div class="clearfix"></div>
            </div>
            <div id="j-f-region-desktop-st1" class="f-navigation__region_change_main f-navigation__region_change_in <? if( ! $regionID || $regionLvl != Geo::lvlCountry) { ?> hide<? } ?>">
                <!--for: desktop-->
                <div class="f-navigation__region_change_desktop hidden-phone">
                    <fieldset class="row-fluid">
                        <div class="span9">
                            <b id="j-f-region-desktop-country-title"><?= ! empty($country) ? $country['title'] : '' ?></b>
                        </div>
                        <div class="span3">
                            <a href="#" class="ajax change pull-right j-f-region-desktop-back"><?= _t('filter', 'Изменить страну'); ?></a>
                        </div>
                    </fieldset>
                    <form class="form-inline pull-left" action="">
                        <?= _t('filter', 'Выберите регион:') ?>
                        <div class="input-append">
                            <input type="text" id="j-f-region-desktop-st1-q" placeholder="<?= _t('filter', 'Введите первые буквы...') ?>" />
                            <button class="btn" type="button"><i class="fa fa-search"></i></button>
                        </div>
                    </form>
                    <div class="clearfix"></div>
                    <?  $attr = ' id="j-f-country-desktop-all"';
                        if( ! empty($country)){
                            $attr .= 'href="'.Geo::url(array('country' => $country['keyword'])).'" ';
                            $attr .= 'data="{id:'.$country['id'].',pid:0,key:\''.$country['keyword'].'\'}"';
                        }else{
                            $attr .= 'href="#"';
                        }
                        echo(_t('filter', 'Искать объявления по <a[attr]>всей стране</a>', array('attr' => $attr))); ?>
                    <hr />
                    <div id="j-f-region-desktop-st1-v" class="f-navigation__region_change_sub hidden-phone">
                        <? if( ! empty($country)) { echo $this->filterData('desktop-country-step1', $country['id']); } ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div id="j-f-region-desktop-st2" class="f-navigation__region_change_sub hidden-phone<? if($regionLvl < Geo::lvlRegion) { ?> hide<? } ?>" style="width: 700px;">
                <? if($regionLvl > Geo::lvlCountry) { echo $this->filterData('desktop-country-step2', $regionID); } ?>
            </div>
        </div>
    <? } else if($coveringType == Geo::COVERING_COUNTRY) { ?>
        <div id="j-f-region-desktop-popup" class="f-navigation__region_change dropdown-block box-shadow abs hide">
            <div id="j-f-region-desktop-st1" class="f-navigation__region_change_main<? if($regionID > 0) { ?> hide<? } ?>" style="width: 700px;">
                <!--for: desktop-->
                <div class="f-navigation__region_change_desktop hidden-phone">
                    <fieldset class="row-fluid">
                        <form class="form-inline pull-left" action="">
                            <?= _t('filter', 'Выберите регион:') ?>
                            <div class="input-append">
                                <input type="text" id="j-f-region-desktop-st1-q" placeholder="<?= _t('filter', 'Введите первые буквы...') ?>" />
                                <button class="btn" type="button"><i class="fa fa-search"></i></button>
                            </div>
                        </form>
                        <div class="pull-right nowrap">
                            <?= _t('filter', 'Искать объявления по') ?> <a href="<?= bff::urlBase() ?>" id="j-f-region-desktop-all" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter', 'Все регионы'), 'js') ?>'}"><?= _t('filter', 'всей стране') ?></a>
                        </div>
                    </fieldset>
                    <hr />
                    <div id="j-f-region-desktop-st1-v">
                        <?= $this->filterData('desktop-country-step1', 0); ?>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
            <div id="j-f-region-desktop-st2" class="f-navigation__region_change_sub hidden-phone<? if( ! $regionID) { ?> hide<? } ?>" style="width: 700px;">
                <? if($regionID > 0) { echo $this->filterData('desktop-country-step2', $regionID); } ?>
            </div>
        </div>
    <? } else if ($coveringType == Geo::COVERING_REGION) { ?>
        <?= $this->filterData('desktop-region'); ?>
    <? } else if ($coveringType == Geo::COVERING_CITIES) { ?>
        <?= $this->filterData('desktop-cities'); ?>
    <? } ?>
<? }

/* Фильтр по региону (phone) */
if($device == bff::DEVICE_PHONE) { ?>
    <!--STAR select rerion-->
    <div class="select-ext">
        <div class="select-ext-container" style="width:100%">
            <a class="select-ext-bnt" href="#" id="j-f-region-phone-link">
                <span><?= ( $regionID > 0 ? $regionData['title'] : _t('filter', 'Все регионы') ) ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <div id="j-f-region-phone-popup" class="select-ext-drop hide" style="width:99%;">
                <div class="select-ext-search">
                    <input type="text" autocomplete="off" style="min-width: 183px;" id="j-f-region-phone-q" />
                    <a href="#"><i class="fa fa-search"></i></a>
                </div>
                <ul class="select-ext-results" id="j-f-region-phone-q-list">
                    <?= $this->filterData('phone-presuggest', ! empty($country['id']) ? $country['id'] : 0) ?>
                </ul>
                <div class="select-ext-no-results hide">
                    <span><?= _t('filter', 'Не найдено - "[word]"', array('word'=>'<span class="word"></span>')) ?></span>
                </div>
            </div>
        </div>
    </div>
    <!--END select rerion-->
<? }