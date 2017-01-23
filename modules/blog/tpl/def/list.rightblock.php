<?php
/**
 * Блог: список постов - блок справа
 * @var $this Blog
 */
?>

<? if(DEVICE_DESKTOP_OR_TABLET) { ?>
<div class="l-right l-table-cell visible-desktop">
    <div class="l-right__content">
        <? # Категории: ?>
        <? if(Blog::categoriesEnabled() && ! empty($categories)) { ?>
        <h2><?= _t('blog', 'Категории') ?></h2>
        <div class="b-mainmenu">
            <ul class="nav">
                <? foreach($categories as &$v) { ?>
                    <? if($v['active']) { ?>
                    <li class="active"><a class="pull-right" href="<?= Blog::url('index') ?>"><i class="fa fa-times"></i></a> <span><?= $v['title'] ?></span></li>
                    <? } else { ?><li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li><? } ?>
                <? } unset($v); ?>
            </ul>
        </div>
        <? } ?>
        <? # Теги: ?>
        <? if(Blog::tagsEnabled() && ! empty($tags)) { ?>
        <h2><?= _t('blog', 'Теги') ?></h2>
        <div class="b-tags">
            <? foreach($tags as &$v) { ?>
                <? if($v['active']) { ?>
                <a href="<?= Blog::url('index') ?>" class="b-tags-item active"><?= $v['tag'] ?> <i class="fa fa-times"></i></a>
                <? } else { ?><a href="<?= $v['link'] ?>" class="b-tags-item"><?= $v['tag'] ?></a><? } ?>
            <? } unset($v); ?>
        </div>
        <? } ?>
        <? # Избранные: ?>
        <? if ( ! empty($favs)) { ?>
        <h2><?= _t('blog', 'Избранные') ?></h2>
        <div class="b-fav">
            <ul class="unstyled">
                <? foreach($favs as &$v) { ?>
                    <li><a href="<?= $v['link'] ?>"><?= $v['title'] ?></a></li>
                <? } ?>
            </ul>
        </div>
        <? } ?>
        <? # Баннер (справа): ?>
        <? if($bannerRight = Banners::view('blog_search_right')) { ?>
        <div class="l-banner banner-right">
            <div class="l-banner__content">
                <?= $bannerRight ?>
            </div>
        </div>
        <? } ?>
    </div>
</div>
<? } ?>