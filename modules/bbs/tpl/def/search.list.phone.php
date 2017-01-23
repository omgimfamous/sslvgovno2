<?php

/**
 * Поиск объявлений: список (phone)
 * @var $this BBS
 */

$lng_fav_in = _t('bbs', 'Добавить в избранное');
$lng_fav_out = _t('bbs', 'Удалить из избранного');
$lng_quick = _t('bbs', 'срочно');

if($list_type == BBS::LIST_TYPE_LIST) { ?>
<div class="sr-page__list sr-page__list_mobile visible-phone">
    <? foreach($items as &$v) { ?>
    <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
        <table>
            <tr>
                <td colspan="2" class="sr-page__list__item_descr">
                    <h5><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h5>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                </td>
            </tr>
            <tr>
                <td class="sr-page__list__item_date"><?= $v['publicated'] ?></td>
                <td class="sr-page__list__item_price">
                    <? if($v['price_on']) { ?>
                        <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                        <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                    <? } ?>
                </td>
            </tr>
        </table>
    </div>
    <? } unset($v); ?>
</div>
<? } else if($list_type == BBS::LIST_TYPE_GALLERY) { ?>
<div class="sr-page__gallery sr-page__gallery_mobile visible-phone">
<?  $i = 1;
    foreach($items as &$v) {
    if( $i == 1 ) { ?><div class="thumbnails"><? } ?>
        <div class="sr-page__gallery__item thumbnail span4<? if($v['svc_marked']){ ?> selected<? } ?>">
            <div class="sr-page__gallery__item_img align-center">
                <a class="thumb stack rel inlblk" href="<?= $v['link'] ?>" title="<?= $v['title'] ?>">
                    <img class="rel br2 zi3 shadow" src="<?= $v['img_m'] ?>" alt="<?= $v['title'] ?>">
                    <? if($v['imgs'] > 1) { ?>
                    <span class="abs border b2 shadow">&nbsp;</span>
                    <span class="abs border r2 shadow">&nbsp;</span>
                    <? } ?>
                </a>
            </div>
            <div class="sr-page__gallery__item_descr">
                <h4>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                    <? if($v['svc_quick']) { ?><span class="label label-warning"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a>
                </h4>
                <p class="sr-page__gallery__item_price">
                    <? if($v['price_on']) { ?>
                        <strong><?= $v['price'] ?></strong>
                        <small><?= $v['price_mod'] ?></small>
                    <? } ?>
                </p>
            </div>
        </div>
        <? if( $i++ == 3 ) { ?></div><? $i = 1; } else { ?><div class="spacer"></div><? }
       } unset($v);
       if( $i!=1 ) { ?></div><? }
    ?>
</div>
<? } else if($list_type == BBS::LIST_TYPE_MAP ) { ?>
<? if( ! Request::isAJAX()) { ?>
<div class="sr-page__map sr-page__map_mobile visible-phone">
    <div class="sr-page__map_ymap span12">
        <div class="j-search-map-phone" style="height: 300px; width: 100%;"></div>
    </div>
</div>
<div class="sr-page__list sr-page__list_mobile j-maplist visible-phone">
<? } ?>
    <? foreach($items as &$v) { ?>
    <div class="sr-page__list__item<? if($v['svc_marked']){ ?> selected<? } ?>">
        <table>
            <tr>
                <td colspan="2" class="sr-page__list__item_descr">
                    <h5><? if($v['svc_quick']) { ?><span class="label label-warning quickly"><?= $lng_quick ?></span>&nbsp;<? } ?><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></h5>
                    <? if($v['fav']) { ?>
                    <a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_out ?>"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
                    <? } else { ?>
                    <a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $v['id'] ?>}" title="<?= $lng_fav_in ?>"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
                    <? } ?>
                </td>
            </tr>
            <tr>
                <td class="sr-page__list__item_date"><?= $v['publicated'] ?></td>
                <td class="sr-page__list__item_price">
                    <? if($v['price_on']) { ?>
                        <?if ($v['price']) { ?><strong><?= $v['price'] ?></strong><? } ?>
                        <?if ($v['price_mod']) { ?><small><?= $v['price_mod'] ?></small><? } ?>
                    <? } ?>
                </td>
            </tr>
        </table>
    </div>
    <? } unset($v); ?>
<? if( ! Request::isAJAX()) { ?>
</div>
<? } ?>
<? }