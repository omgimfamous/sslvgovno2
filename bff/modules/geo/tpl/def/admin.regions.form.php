<?php
    $aFormTitles = array(Geo::lvlCountry=>'страны', Geo::lvlRegion=>'области', Geo::lvlCity=>'города');
?>
<div class="ipopup" style="width:450px;">
    <div class="ipopup-wrapper">
        <div class="ipopup-title"><?= ($id ? 'Редактирование' : 'Добавление').' '.$aFormTitles[$numlevel] ?> </div>
        <div class="ipopup-content">
            <form action="" method="post">
                <input type="hidden" name="id" value="<?= $id ?>" />
                <input type="hidden" name="pid" value="<?= $pid ?>" />
                <input type="hidden" name="country" value="<?= $country ?>" />
                <input type="hidden" name="numlevel" value="<?= $numlevel ?>" />
                <table class="admtbl tbledit">
                <?= $this->locale->buildForm($aData, 'region-item',
               '<tr class="required">
                    <td class="row1" style="width:100px;"><span class="field-title">Название</span>:</td>
                    <td class="row2"><input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]) ?>" class="stretch lang-field" rel="title-<?= $key ?>" /></td>
                </tr>', array('popup'=>true)); ?>
                <?php if($numlevel == Geo::lvlRegion || $numlevel == Geo::lvlCity): ?>
                <tr>
                    <td><span class="field-title">URL-keyword</span>:</td>
                    <td><input type="text" name="keyword" value="<?= HTML::escape($keyword) ?>" class="stretch" maxlength="30" /></td>
                </tr>
                <?php endif; ?>
                <?php if($numlevel == Geo::lvlCity && Geo::manageRegions(Geo::lvlMetro) ): ?>
                <tr>
                    <td><span class="field-title">Есть метро</span>:</td>
                    <td><label class="checkbox"><input type="checkbox" name="metro" <?php if($metro){ ?> checked="checked"<?php } ?> /></label></td>
                </tr>
                <?php endif; ?>
                <tr class="footer">
                    <td colspan="2" class="center">
                        <input type="button" class="btn btn-success button submit" value="Сохранить" onclick="jGeoRegions.editFinish(this, <?= $id ?>);" />
                        <input type="button" class="btn button cancel" value="Отмена" onclick="$.fancybox.close();" />
                    </td>
                </tr>
                </table>
            </form>
        </div>
    </div>
</div>
