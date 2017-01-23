<?php

# Тип владельца объявления (Частное лицо/Бизнес)
$aOwnerTypes = array(
    BBS::OWNER_PRIVATE => ( ! empty($owner_private_form) ? $owner_private_form : _t('bbs','Частное лицо')),
);
if($owner_business) {
    $aOwnerTypes[BBS::OWNER_BUSINESS] = ( ! empty($owner_business_form) ? $owner_business_form : _t('bbs','Бизнес'));
}
if( empty($item['owner_type']) ) $item['owner_type'] = BBS::OWNER_PRIVATE;

?>
<div class="control-group<? if( sizeof($aOwnerTypes) == 1 ) { ?> hide<? } ?>">
    <div class="controls"><?
    foreach($aOwnerTypes as $id=>$title): ?>
        <label class="radio inline">
            <input name="owner_type" value="<?= $id ?>" type="radio" <? if($item['owner_type'] == $id) { ?>checked="checked"<? } ?> class="j-required" /><?= $title ?>
        </label><?
    endforeach;
  ?></div>
</div><?