<?php
    $mngCountry  = Geo::manageRegions(Geo::lvlCountry);
    $mngRegion   = Geo::manageRegions(Geo::lvlRegion);
    $mngDistrict = Geo::manageRegions(Geo::lvlDistrict);
    $mngMetro    = Geo::manageRegions(Geo::lvlMetro);

    if( $mngDistrict ) {
        $linkCityFormEx = $this->adminLink('regions_city_form'.( $mngCountry ?'&country='.$country:'').'&pid='.$pid.'&id=');
    }
    if( $mngMetro ) {
        $linkMetroListing = $this->adminLink('regions_metro&country='.$country.'&city=');
    }
?>
<div class="actionBar">
<form action="<?= $this->adminLink(NULL) ?>" method="get" name="GeoRegionsFilter" id="GeoRegionsFilter" class="form-inline">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="main" value="<?= $main ?>" id="GeoRegionsFilterMain" />
    <?php if( $mngCountry ): ?>
    <div class="left" style="margin-right: 10px;">
        <a href="<?= $this->adminLink('regions_country') ?>"><?= _t('geo','Страна') ?></a>:&nbsp;<select name="country" style="width:120px;" onchange="$('#GeoRegionsFilter').submit();"><?= $country_options ?></select>
    </div>
    <?php endif; ?>
    <div class="left">
        <?php if( $mngRegion ): ?>
        <a href="<?= $this->adminLink('regions_region') ?>"><?= _t('geo','Области') ?></a>:<select name="pid" style="width:150px; margin: 0 5px;" onchange="$('#GeoRegionsFilterMain').val(0); $('#GeoRegionsFilter').submit();"><?= $region_options ?></select>
        <?php endif; ?>
        <?php if($pid > 0 || ! $mngRegion) { ?>
            <a class="ajax" id="geo-regions-add-link" href="#">+ <?= _t('', 'добавить') ?></a>
        <?php } ?>
        <span id="geo-regions-progress" style="margin-right:5px; display:none;" class="progress"></span>
    </div>
    <div class="right">
        <?php if($main): ?>
            <a href="#" onclick="$('#GeoRegionsFilterMain').val(0); $('#GeoRegionsFilter').submit(); return false;"><?= _t('geo','все') ?></a> / <?= _t('geo','только основные') ?>
        <?php else: ?>
            <?= _t('geo','все') ?> / <a href="#" onclick="$('#GeoRegionsFilterMain').val(1); $('#GeoRegionsFilter').submit(); return false;"><?= _t('geo','только основные') ?></a>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</form>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="geo-regions-listing">
<thead>
<tr class="header nodrag nodrop">
    <th width="65"><?= _t('','ID') ?></th>
    <th class="left" style="padding-left:5px;"><?= _t('','Название') ?></th>
    <th width="150"><?= _t('','Keyword') ?></th>
    <?php if( $mngMetro ) { ?><th width="150"><?= _t('geo','Метро') ?></th><?php } ?>
    <th width="120"><?= _t('','Действие') ?></th>
</tr>
</thead>
<?php
    foreach($cities as $k=>$v):
    $id = $v['id'];
?>
<tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
    <td class="small"><?= $id ?></td>
    <td class="left" rel="title"><?= $v['title'] ?></td>
    <td><?= $v['keyword'] ?></td>
    <?php if( $mngMetro ) { ?><td><?php if($v['metro']) { ?><a href="<?= $linkMetroListing.$id ?>"><?= _t('geo','станции') ?></a><?php } else { ?><span class="desc">-</span><?php } ?></td><?php } ?>
    <td>
        <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" onclick="return jGeoRegions.toggle(<?= $id ?>, this, 0);" href="#"></a>
        <a class="but <?php if(!$v['main']){ ?>un<?php } ?>fav" title="<?= _t('geo','основной') ?>" onclick="return jGeoRegions.toggle(<?= $id ?>, this, 1);" href="#"></a>
        <?php if( $mngDistrict ) { ?>
            <a class="but edit" href="<?= $linkCityFormEx.$id ?>"></a>
        <?php } else { ?>
            <a class="but edit" href="#" onclick="return jGeoRegions.edit(<?= $id ?>);"></a>
        <?php } ?>
        <?php if(FORDEV){ ?><a class="but del" href="#" onclick="return jGeoRegions.del(<?= $id ?>, this);"></a><?php } ?>
    </td>
</tr>
<?php endforeach;
if( empty($cities) ): ?>
<tr class="norecords">
    <td colspan="<?= 4 + ( $mngMetro ?1:0) ?>"><?= _t('', 'список пуст') ?></td>
</tr>
<?php endif; unset($cities); ?>
</table>