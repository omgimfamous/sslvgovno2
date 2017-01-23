<div class="actionBar">
<form action="<?= $this->adminLink(null) ?>" method="get" name="GeoRegionsFilter" id="GeoRegionsFilter" class="form-inline">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="main" value="<?= $main ?>" id="GeoRegionsFilterMain" />
    <?php if( Geo::manageRegions(Geo::lvlCountry) ): ?>
    <div class="left" style="margin-right: 10px;">
        <a href="<?= $this->adminLink('regions_country') ?>"><?= _t('geo', 'Страна') ?></a>:&nbsp;<select name="country" style="width:120px;" onchange="$('#GeoRegionsFilter').submit();"><?= $country_options ?></select>
    </div>
    <?php endif; ?>
    <div class="left">
        <a class="ajax" style="margin: 0 5px;" id="geo-regions-add-link" href="#">+ <?= _t('', 'добавить') ?></a>
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
    <th width="120"><?= _t('','Действие') ?></th>
</tr>
</thead>
<?php
    $sCitiesLink = $this->adminLink('regions_city&pid=');
    foreach($regions as $k=>$v):
    $id = $v['id'];
?>
<tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
    <td class="small"><?= $id ?></td>
    <td class="left" rel="title"><?= $v['title'] ?></td>
    <td><?= $v['keyword'] ?></td>
    <td>
        <a class="but folder" href="<?= $sCitiesLink.$id.'&country='.$v['pid'] ?>" title="<?= _t('geo','список городов') ?>"></a>
        <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" style="display: none;" onclick="return jGeoRegions.toggle(<?= $id ?>, this, 0);" href="#"></a>
        <a class="but <?php if(!$v['main']){ ?>un<?php } ?>fav" title="<?= _t('geo','основной') ?>" onclick="return jGeoRegions.toggle(<?= $id ?>, this, 1);" href="#"></a>
        <a class="but edit" href="#" onclick="return jGeoRegions.edit(<?= $id ?>);"></a>
        <?php if(FORDEV){ ?><a class="but del" href="#" onclick="return jGeoRegions.del(<?= $id ?>, this);"></a><?php } ?>
    </td>
</tr>
<?php endforeach;
if( empty($regions) ): ?>
<tr class="norecords">
    <td colspan="4"><?= _t('','список пуст') ?></td>
</tr>
<?php endif; unset($regions); ?>
</table>