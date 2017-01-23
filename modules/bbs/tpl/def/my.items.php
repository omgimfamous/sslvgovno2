<?php
    /**
     * Кабинет пользователя: Мои объявления
     * @var $this BBS
     */

    tpl::includeJS(array('history'), true);
    tpl::includeJS('bbs.my', false, 4);
    $f['qq'] = HTML::escape($f['qq']);
?>

<form action="" id="j-my-items-form" class="form-search">
<input type="hidden" name="c" value="<?= $f['c'] ?>" id="j-my-items-cat-value" />
<input type="hidden" name="status" value="<?= $f['status'] ?>" id="j-my-items-status-value" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />
<input type="hidden" name="pp" value="<?= $f['pp'] ?>" id="j-my-items-pp-value" />

<? # Фильтр списка ?>
<div class="u-cabinet__sub-navigation">
    <ul class="nav nav-pills" id="j-my-items-cat">
        <li class="dropdown">
            <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="javascript:void(0);">
                <b class="j-cat-title"><?= $cat_active['title'] ?></b>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu j-cat-list">
                <?= $cats ?>
            </ul>
        </li>
        <? if(DEVICE_DESKTOP_OR_TABLET) {
            foreach($status as $k=>$v) {
                ?><li class="u-cabinet__sub-navigation__sort hidden-phone<? if($f['status'] == $k) { ?> active<? } ?> j-status-options"><a href="#" class="j-status-option" data-value="<?= $k ?>"><span><?= $v['title'] ?></span> <i class="label u-cabinet__sub-navigation__sort__label j-counter"><?= $counters[$k] ?></i></a></li><?
            }
        } ?>
        <li class="u-cabinet__sub-navigation__search pull-right">
            <div class="input-append">
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-medium search-query visible-desktop j-q" />
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-small search-query visible-tablet j-q" />
                <input type="text" name="qq" value="<?= $f['qq'] ?>" class="input-small search-query visible-phone j-q" />
                <button type="button" class="btn j-q-submit"><i class="fa fa-search"></i></button>
            </div>
        </li>
    </ul>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-cabinet__sub-navigation_mobile">
        <div class="u-cabinet__sub-navigation__type visible-phone" id="j-my-items-status-arrows">
            <table>
                <tr>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_left j-left"><a href="#"><i class="fa fa-chevron-left"></i></a></div></td>
                    <td class="u-cabinet__sub-navigation__type__title j-title"><?= $status[$f['status']]['title'] ?></td>
                    <td><div class="u-cabinet__sub-navigation__type__arrow u-cabinet__sub-navigation__type__arrow_right j-right"><a href="#"><i class="fa fa-chevron-right"></i></a></div></td>
                </tr>
            </table>
        </div>
    </div>
    <? } ?>
    <div class="clearfix"></div>
</div>

<? # Групповые действия с объявлениями ?>
<div class="u-ads__actions hide" id="j-my-items-sel-actions">
    <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
    <div class="u-ads__actions_desktop hidden-phone j-my-items-sel-actions-<?= bff::DEVICE_DESKTOP ?> j-my-items-sel-actions-<?= bff::DEVICE_TABLET ?>">
        <span class="u-ads_actions__count"><span class="j-sel-title"></span>:</span>
        <ul class="unstyled j-sel-actions hide" data-status="1">
            <li><a href="#" class="j-sel-action" data-act="mass-unpublicate"><?= _t('bbs.my', 'Снять с публикации') ?></a></li>
            <? /* ?><li><a href="#" class="j-sel-action" data-act="mass-promote"><?= _t('bbs.my', 'Рекламировать') ?></a></li><? */ ?>
            <li><a href="#" class="j-sel-action" data-act="mass-refresh"><?= _t('bbs.my', 'Продлить') ?></a></li>
        </ul>
        <ul class="unstyled j-sel-actions hide" data-status="2">
            <li><a href="#" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
        </ul>
        <ul class="unstyled j-sel-actions hide" data-status="3">
            <li><a href="#" class="j-sel-action" data-act="mass-publicate"><?= _t('bbs.my', 'Активировать') ?></a></li>
            <li><a href="#" class="j-sel-action" data-act="mass-delete"><?= _t('bbs.my', 'Удалить') ?></a></li>
        </ul>
        <div class="clearfix"></div>
    </div>
    <? } ?>
    <? if(DEVICE_PHONE) { ?>
    <div class="u-ads__actions_mobile visible-phone j-my-items-sel-actions-<?= bff::DEVICE_PHONE ?>">
        <span class="u-ads_actions__count"><span class="j-sel-title"></span>:</span>
        <span class="j-sel-actions hide" data-status="1">
            <a class="btn btn-small j-sel-action" data-act="mass-unpublicate" href="#"><i class="fa fa-times"></i></a>
            <? /* ?><a class="btn btn-small j-sel-action" data-act="mass-promote" href="#"><i class="fa fa-gift"></i></a><? */ ?>
            <a class="btn btn-small j-sel-action" data-act="mass-refresh" href="#"><i class="fa fa-refresh"></i></a>
        </span>
        <span class="j-sel-actions hide" data-status="2">
            <a class="btn btn-small j-sel-action" data-act="mass-delete" href="#"><i class="fa fa-times"></i></a>
        </span>
        <span class="j-sel-actions hide" data-status="3">
            <a class="btn btn-small j-sel-action" data-act="mass-publicate" href="#"><i class="fa fa-arrow-up"></i></a>
            <a class="btn btn-small j-sel-action" data-act="mass-delete" href="#"><i class="fa fa-times"></i></a>
        </span>
        <div class="clearfix"></div>
    </div>
    <? } ?>
</div>

<? # Список объявлений ?>
<div class="u-ads__list sr-page__list" id="j-my-items-list">
    <div class="u-ads__list_desktop sr-page__list_desktop l-table hidden-phone j-my-items-list-<?= bff::DEVICE_DESKTOP ?> j-my-items-list-<?= bff::DEVICE_TABLET ?>">
        <? if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) echo $list; ?>
    </div>
    <div class="u-ads__list_mobile sr-page__list_mobile visible-phone j-my-items-list-<?= bff::DEVICE_PHONE ?>">
        <? if( $device == bff::DEVICE_PHONE ) echo $list; ?>
    </div>
</div>

<? # Постраничная навигация ?>
<div class="u-cabinet__pagination u-fav__pagenation">
    <div class="pull-left" id="j-my-items-pgn">
        <?= $pgn ?>
    </div>
    <ul id="j-my-items-pp" class="u-cabinet__list__pagination__howmany nav nav-pills pull-right hidden-phone<?= ( ! $total ? ' hide' : '' ) ?>">
        <li class="dropdown">
            <a class="dropdown-toggle j-pp-dropdown" data-toggle="dropdown" href="#">
                <span class="j-pp-title"><?= $pgn_pp[$f['pp']]['t'] ?></span>
                <i class="fa fa-caret-down"></i>
            </a>
            <ul class="dropdown-menu">
                <? foreach($pgn_pp as $k=>$v): ?>
                    <li><a href="#" class="<? if($k == $f['pp']) { ?>active <? } ?>j-pp-option" data-value="<?= $k ?>"><?= $v['t'] ?></a></li>
                <? endforeach; ?>
            </ul>
        </li>
    </ul>
    <div class="clearfix"></div>
</div>
</form>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jMyItems.init(<?= func::php2js(array(
            'lang' => array(
                'sel_selected' => _t('bbs.my', 'Выбрано <b>[items]</b>'),
                'sel_items_desktop' => _t('', 'объявление;объявления;объявлений'),
                'sel_items_tablet' => _t('', 'объявление;объявления;объявлений'),
                'sel_items_phone' => _t('', 'об-е;об-я;об-й'),
                'delete_confirm' => _t('bbs.my', 'Удалить объявление?'),
                'delete_confirm_mass' => _t('bbs.my', 'Удалить отмеченные объявления?'),
            ),
            'status' => $status,
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>