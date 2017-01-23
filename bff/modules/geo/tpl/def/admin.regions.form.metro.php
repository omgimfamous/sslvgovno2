<?php
    $edit = ! empty($id);
?>
<?= $this->locale->buildForm($aData, 'metro-item',
'<tr class="required">
    <td class="row1 field-title" width="80">Название:</td>
    <td class="row2">
        <input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" class="stretch lang-field" />
    </td>
</tr>'); ?>
<tr<?php if( ! Geo::$useMetroBranches) { ?> style="display:none;" <?php } ?>>
    <td class="row1"><span class="field-title">Тип</span>:</td>
    <td class="row2">
            <input type="hidden" name="id" value="<?= $id ?>" />
        <?php if($edit) { ?>
            <input type="hidden" name="branch" value="<?= $branch ?>" />
            <span><?= ( $branch ? 'ветка метро' : 'станция метро' ) ?></span>
        <?php } else { ?>
            <label class="radio inline"><input type="radio" name="branch" <?php if(!$branch){ ?> checked="checked"<?php } ?> value="0" onclick="$('#GeoRegionsMetroBranchSelect').show(); $('#GeoRegionsMetroBranchColor').hide();" />станция метро</label>
            <label class="radio inline"><input type="radio" name="branch" <?php if( $branch){ ?> checked="checked"<?php } ?> value="1" onclick="$('#GeoRegionsMetroBranchSelect').hide(); $('#GeoRegionsMetroBranchColor').show();" />ветка метро</label>
        <?php } ?>
    </td>
</tr>
<tr<?php if( ! Geo::$useMetroBranches || $branch) { ?> style="display:none;"<?php } ?> id="GeoRegionsMetroBranchSelect">
    <td class="row1"><span class="field-title">Ветка метро</span>:</td>
    <td class="row2">
        <?php if( ! $branch) { ?>
            <select name="pid" style="margin-right:10px;"><?= $branches ?></select>
        <?php } ?>
    </td>
</tr>
<tr<?php if( ! Geo::$useMetroBranches || ! $branch) { ?> style="display:none;"<?php } ?> id="GeoRegionsMetroBranchColor">
    <td class="row1"><span class="field-title">Цвет ветки</span>:</td>
    <td class="row2">
        <input type="text" name="color" value="<?= $color ?>" />
    </td>
</tr>