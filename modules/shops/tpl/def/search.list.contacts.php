<?php

$is_phone = ($device == bff::DEVICE_PHONE);

if($listType == Shops::LIST_TYPE_LIST) { ?>
<ul class="sh-page__list__contacts__dropdown__table">
    <? if($phones){ ?>
    <li>
        <div style="vertical-align: top;"><?= _t('shops', 'Телефон') ?>:&nbsp;</div>
        <div><?= $phones ?></div>
    </li>
    <? } ?>
    <? if($skype){ ?><li><div>Skype:</div><div><?= $skype ?></div></li><? } ?>
    <? if($icq){ ?><li><div>ICQ:</div><div><?= $icq ?></div></li><? } ?>
    <? if($social && $socialTypes) { ?>
    <li class="sh-page__list__item_social">
        <? foreach($social as $v) {
            if ($v && isset($socialTypes[$v['t']])) {
                ?><a href="<?= bff::urlAway($v['v']) ?>" rel="nofollow" target="_blank" class="sh-social sh-social_<?= $socialTypes[$v['t']]['icon'] ?>"></a><?
            }
           } ?>
    </li>
    <? } ?>
</ul>
<? }
else if($listType = Shops::LIST_TYPE_MAP) { ?>
    <div class="sr-page__map__balloon<? if($is_phone){ ?> sr-page__map__balloon_mobile<? } ?>">
        <? if($logo && ! $is_phone){ ?><div class="sh-page__map__balloon_img"><a href="<?= $link ?>"><img class="rel br2 zi3 shadow" src="<?= $logo ?>" alt="<?= $title ?>" /></a></div><? } ?>
        <div class="sh-page__map__balloon_descr">
            <h6><a href="<?= $link ?>"><?= $title ?></a></h6>
            <? if($has_contacts) { ?>
                <table>
                    <tbody>
                        <? if($phones){ ?><tr><td width="60"><?= _t('shops', 'Телефон') ?>:</td><td><?= $phones ?></td></tr><? } ?>
                        <? if($skype){ ?><tr><td width="60">Skype:</td><td><?= $skype ?></td></tr><? } ?>
                        <? if($icq){ ?><tr><td width="60">ICQ:</td><td><?= $icq ?></td></tr><? } ?>
                    </tbody>
                </table>
            <? } ?>
            <? if($region_id){ ?><div style="margin:5px 0;"><?= $region_title.', '.$addr_addr ?></div><? } ?>
            <? if($items){ ?><a href="<?= $link ?>"><?= _t('shops', 'Показать').' '.tpl::declension($items, _t('shops','объявление;объявления;объявлений')) ?> &rsaquo;</a><? } ?>
        </div>
    </div>
<? }