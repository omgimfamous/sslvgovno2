<?php
    $useRegions = Geo::manageRegions(Geo::lvlRegion);
    $linkRegionsListing = $this->adminLink('regions_region&country=');
?>
<div class="actionBar">
<form action="<?= $this->adminLink(null) ?>" method="get" name="GeoRegionsFilter" id="GeoRegionsFilter" class="form-inline">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <div class="left">
        <a class="ajax" style="margin: 0 5px;" id="geo-regions-add-link" href="#">+ <?= _t('geo', 'добавить страну') ?></a>
        <span id="geo-regions-progress" style="margin-right:5px; display:none;" class="progress"></span>
    </div>
    <div class="clear"></div>
</form>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="geo-regions-listing">
<thead>
<tr class="header nodrag nodrop">
    <th width="65"><?= _t('', 'ID') ?></th>
    <th class="left"><?= _t('', 'Название') ?></th>
    <th width="120"><?= _t('', 'Действие') ?></th>
</tr>
</thead>
<?php
    foreach($countries as $k=>$v): $id = $v['id'];
?>
<tr class="row<?= ($k%2) ?>" id="dnd-<?= $id ?>">
    <td class="small"><?= $id ?></td>
    <td class="left" rel="title"><?= $v['title'] ?></td>
    <td>
        <?php if($useRegions) { ?><a class="but folder" href="<?= $linkRegionsListing.$id ?>" title="<?= _t('geo', 'список областей(регионов)') ?>"></a><?php } ?>
        <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" onclick="return jGeoRegions.toggle(<?= $id ?>, this);" href="#"></a>
        <a class="but edit" href="#" onclick="return jGeoRegions.edit(<?= $id ?>);"></a>
        <?php if(FORDEV){ ?><a class="but del" href="#" onclick="return jGeoRegions.del(<?= $id ?>, this);"></a><?php } ?>
    </td>
</tr>
<?php endforeach;
if( empty($countries) ): ?>
<tr class="norecords">
    <td colspan="3">
        <?= _t('', 'список пуст') ?>
    </td>
</tr>
<?php endif; unset($countries); ?>
</table>