<?php

    $aTabs = array();
    if( Geo::manageRegions(Geo::lvlCountry) ) $aTabs['regions_country'] = array('Страны');
    if( Geo::manageRegions(Geo::lvlRegion) ) $aTabs['regions_region'] = array('Области');
    $aTabs['regions_city'] = array('Города');
    if( Geo::manageRegions(Geo::lvlMetro) ) $aTabs['regions_metro'] = array('Метро');
    $sTabsActiveKey = ( isset($aTabs[bff::$event]) ? bff::$event : key($aTabs) );
    $aTabs[$sTabsActiveKey]['a'] = 1;
    tplAdmin::adminPageSettings(array('title'=>'Регионы / '.$aTabs[$sTabsActiveKey][0]));
    $countryFilter = '&country='.$country;
?>

<div class="tabsBar" id="geo-regions-tabs">
    <?php foreach($aTabs as $k=>$v) { ?>
        <span class="tab<?php if( ! empty($v['a'])){ ?> tab-active<?php } ?>"><a href="<?= $this->adminLink($k.$countryFilter) ?>"><?= $v[0] ?></a></span>
    <?php } ?>
    <div class="progress" style="display:none;" id="geo-regions-progress"></div>
    <div class="right"><a href="#" class="ajax cancel" onclick="jGeoRegions.resetCache(); return false;">сбросить кеш</a></div>
    <div class="clear"></div>
</div>