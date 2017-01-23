<?php

/**
 * @var $this BBS
 */

$lng_from = _t('bbs.my', 'С');
$lng_to = _t('bbs.my', 'По');
$lng_a_view = _t('bbs.my', 'Посмотреть');
$lng_a_edit = _t('bbs.my', 'Редактировать');
$lng_a_edit_phone = _t('bbs.my', 'Изменить');
$lng_a_unpublicate = _t('bbs.my', 'Деактивировать');
$lng_a_publicate = _t('bbs.my', 'Активировать');
$lng_a_promote = _t('bbs.my', 'Рекламировать');
$lng_a_delete = _t('bbs.my', 'Удалить');
$lng_st = _t('bbs.my', 'Статистика');
$lng_st_views = _t('bbs.my', 'просмотры');
$lng_st_contacts = _t('bbs.my', 'контакты');
$lng_st_messages = _t('bbs.my', 'сообщения');
$lng_blocked = _t('bbs.my', 'ЗАБЛОКИРОВАНО');

if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET )
{
    foreach($items as $v):
        $ID = $v['id'];
        $messages_url = InternalMail::url('item.messages', array('item'=>$ID));
        $moderated = ($v['moderated'] > 0 || ! BBS::premoderation());
        ?>
        <div class="u-ads__list__item l-table-row">
            <div class="u-ads__list__left l-table-cell">
                <div class="u-ads__list__item__check">
                    <label class="checkbox pull-right"><input type="checkbox" name="i[]" class="j-check-desktop" value="<?= $ID ?>" /></label>
                    <div class="clearfix"></div>
                    <small class="grey">
                        <?= $lng_from ?>: <?= tpl::dateFormat($v['publicated'], '%d %b %Y') ?><br />
                        <?= $lng_to ?>: <?= tpl::dateFormat($v['publicated_to'], '%d %b %Y') ?>
                    </small>
                </div>
            </div>
            <div class="u-ads__list__item__content l-table-cell">
                <div class="sr-page__list__item">
                    <table>
                        <tbody>
                        <tr>
                            <td class="sr-page__list__item_img" rowspan="2">
                                <span class="rel inlblk">
                                    <a href="<?= $v['link'].'?from=my' ?>" class="thumb stack rel inlblk">
                                        <? if( $v['imgs'] ) { ?>
                                        <img src="<?= $v['img_s'] ?>" alt="<?= $v['title'] ?>" class="rel br2 zi3 shadow" />
                                        <? } else { ?>
                                        <img src="<?= $img_default ?>" alt="<?= $v['title'] ?>" class="rel br2 zi3 shadow" />
                                        <? } ?>
                                    </a>
                                </span>
                            </td>
                            <td class="sr-page__list__item_descr">
                                <h3>
                                    <a href="<?= $v['link'].'?from=my' ?>"><?= $v['title'] ?></a>
                                    <? if($v['status'] == BBS::STATUS_BLOCKED) { ?><span class="text-error">(<?= $lng_blocked ?>)</span><? } ?>
                                </h3>
                                <p>
                                    <small><?= $v['cat_title'] ?></small>
                                </p>
                            </td>
                            <td class="sr-page__list__item_price">
                                <? if($v['price_on']) { ?>
                                    <strong><?= $v['price'] ?></strong>
                                    <small><?= $v['price_mod'] ?></small>
                                <? } ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" class="u-ads__list__item__content__block_nav">
                                <div class="u-ads__list__item__content__block_nav_links">
                                    <? if($v['status'] != BBS::STATUS_BLOCKED && $moderated) { ?>
                                        <a href="<?= $v['link'].'?from=my' ?>" class="ico"><i class="fa fa-check"></i> <span><?= $lng_a_view ?></span></a>
                                    <? } ?>
                                    <a href="<?= BBS::url('item.edit', array('id'=>$ID,'from'=>'my')) ?>" class="ico"><i class="fa fa-edit"></i> <span><?= $lng_a_edit ?></span></a>
                                    <? if($v['status'] == BBS::STATUS_PUBLICATED && $moderated) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="unpublicate" class="ico red j-i-status"><i class="fa fa-times"></i> <span><?= $lng_a_unpublicate ?></span></a>
                                    <? } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="delete" class="ico red j-i-status"><i class="fa fa-times"></i> <span><?= $lng_a_delete ?></span></a>
                                    <? } ?>
                                </div>
                                <div class="u-ads__list__item__content__block_nav_buttons">
                                    <? if( ! $v['messages_total']) { ?>
                                        <a href="#" onclick="return false;" class="btn btn-small disabled"><i class="fa fa-envelope"></i> 0&nbsp;</a>
                                    <? } else {
                                         if($v['messages_new']) { ?><a href="<?= $messages_url ?>" class="btn btn-small btn-success"><i class="fa fa-envelope white"></i> +<?= $v['messages_new'] ?></a><? }
                                         else { ?><a href="<?= $messages_url ?>" class="btn btn-small"><i class="fa fa-envelope"></i> <?= $v['messages_total'] ?></a><? }
                                       } ?>
                                    <? if($v['status'] == BBS::STATUS_PUBLICATED && $moderated && bff::servicesEnabled()) { ?>
                                        <a href="<?= BBS::url('item.promote', array('id'=>$ID,'from'=>'my')) ?>" class="btn btn-small btn-success"><?= $lng_a_promote ?></a>
                                    <? } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="publicate" class="btn btn-small btn-info j-i-status"><i class="fa fa-arrow-up white"></i> <?= $lng_a_publicate ?></a>
                                    <? } ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <div class="u-ads__list__item__content__block_stat">
                                    <div class="spacer"></div>
                                    <span><?= $lng_st ?>:</span>
                                    <span><i class="fa fa-eye"></i> <?= $lng_st_views ?>: <b><?= $v['views_item_total'] ?></b></span>
                                    <span> <?= $lng_st_contacts ?>: <b><?= $v['views_contacts_total'] ?></b></span>
                                    <span><i class="fa fa-comment"></i> <?= $lng_st_messages ?>: <b><?= $v['messages_total'] ?></b></span>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?
    endforeach;
    if( empty($items) ) {
        echo $this->showInlineMessage(_t('bbs.my', 'Список объявлений пустой'));
    }
}

if( $device == bff::DEVICE_PHONE )
{
    foreach($items as $v):
        $ID = $v['id'];
        $messages_url = InternalMail::url('item.messages', array('item'=>$ID));
        $moderated = ( $v['moderated'] > 0 );
        ?>
        <div class="u-ads__list__item">
            <div class="u-ads__list__item__content">
                <div class="sr-page__list__item u-ads__list__item__content__block">
                    <table>
                        <tr>
                            <td class="sr-page__list__item_descr">
                                <h5>
                                    <a href="<?= $v['link'].'?from=my' ?>"><?= $v['title'] ?></a>
                                    <? if($v['price_on']) { ?><? if($v['price']) { ?><strong class="nowrap"><?= $v['price'] ?></strong><? } ?><? if($v['price_mod']) { ?><small>, <?= $v['price_mod'] ?></small><? } ?><? } ?>
                                    <? if($v['status'] == BBS::STATUS_BLOCKED) { ?><small><br /><span class="text-error"><?= $lng_blocked ?></span></small><? } ?>
                                    <small> <br /><?= $v['cat_title'] ?> <br /><?= $lng_from ?>: <?= tpl::dateFormat($v['publicated']) ?> - <?= $lng_to ?>: <?= tpl::dateFormat($v['publicated_to']) ?></small>
                                </h5>
                                <label class="checkbox pull-right"><input type="checkbox" name="i[]" class="j-check-phone" value="<?= $ID ?>" /></label>
                                <div class="clearfix"></div>
                            </td>
                        </tr>
                        <tr>
                            <td class="u-ads__list__item__content__block_nav align-center">
                                <div>
                                    <a href="<?= BBS::url('item.edit', array('id'=>$ID,'from'=>'my')) ?>" class="btn"><i class="fa fa-edit"></i> <span><?= $lng_a_edit_phone ?></span></a>
                                    <? if($v['status'] == BBS::STATUS_PUBLICATED && $moderated) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="unpublicate" class="btn j-i-status"><i class="fa fa-times"></i> <span><?= $lng_a_unpublicate ?></span></a>
                                    <? } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="delete" class="btn btn-danger j-i-status"><i class="fa fa-times white"></i> <span><?= $lng_a_delete ?></span></a>
                                    <? } ?>
                                </div>
                                <div>
                                    <? if( ! $v['messages_total']) { ?>
                                        <button class="btn disabled"><i class="fa fa-envelope"></i> 0</button>
                                    <? } else {
                                         if($v['messages_new']) { ?><a href="<?= $messages_url ?>" class="btn btn-success"><i class="fa fa-envelope white"></i> +<?= $v['messages_new'] ?></a><? }
                                         else { ?><a href="<?= $messages_url ?>" class="btn"><i class="fa fa-envelope"></i> <?= $v['messages_total'] ?></a><? }
                                       } ?>
                                    <? if($v['status'] == BBS::STATUS_PUBLICATED && $moderated && bff::servicesEnabled()) { ?>
                                        <a href="<?= BBS::url('item.promote', array('id'=>$ID,'from'=>'my')) ?>" class="btn btn-success"><?= $lng_a_promote ?></a>
                                    <? } else if($v['status'] == BBS::STATUS_PUBLICATED_OUT) { ?>
                                        <a href="#" data-id="<?= $ID ?>" data-act="publicate" class="btn btn-info j-i-status"><i class="fa fa-arrow-up white"></i> <?= $lng_a_publicate ?></a>
                                    <? } ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="u-ads__list__item__content__block_stat">
                                    <div class="spacer"></div>
                                    <small>
                                        <span><i class="fa fa-eye"></i> <b><?= $v['views_item_total'] ?></b> / <b><?= $v['views_contacts_total'] ?></b></span>
                                        <span><i class="fa fa-comment"></i> <b><?= $v['messages_total'] ?></b></span>
                                    </small>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <?
    endforeach;
    if( empty($items) ) {
        echo $this->showInlineMessage(_t('bbs.my', 'Список объявлений пустой'));
    }
}