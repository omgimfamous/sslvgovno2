<?php
/**
 * @var $this Users
 * @var $options array
 * @var $countryList array
 */
 $itemForm = !empty($options['item-form']);
 if(empty($attr['value'])) $attr['value'] = '+'.intval($countrySelected['phone_code']);
?>
<div class="j-phone-number<? if($itemForm){ ?> i-control-phone<? } ?>">
    <a href="#" class="country-icon country-icon-<?= $countrySelected['country_code'] ?>" data-type="country-icon" data-default="<?= $countrySelected['country_code'] ?>"></a>
    <input type="text" <?= HTML::attributes($attr) ?> pattern="[0-9+]*" maxlength="30" class="j-required j-phone-number-input <? if($itemForm){ ?> input-xlarge<? } ?>" data-default="<?= '+'.intval($countrySelected['phone_code']) ?>" />
    <div class="form-control-phone-select j-phone-number-country-list hide">
        <ul>
            <? foreach ($countryList as $v) { ?>
            <li<? if ($v['id'] == $countrySelectedID) { ?> class="active"<? } ?>>
                <a href="#" data="{id:<?= $v['id'] ?>,cc:'<?= $v['country_code'] ?>',pc:'+<?= intval($v['phone_code']) ?>'}" class="j-country-item"><span class="country-icon country-icon-<?= $v['country_code'] ?>"></span> <?= $v['title'] ?> <i>+<?= $v['phone_code'] ?></i></a>
            </li>
            <? } ?>
        </ul>
    </div>
</div>