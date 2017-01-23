<?php

    /**
     * Поиск магазинов: layout
     * @var $this Shops
     */

    tpl::includeJS(array('history'), true);
    extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

    # Типы списка:
    $listTypes = array(
        Shops::LIST_TYPE_LIST => array('t'=>_t('shops','Списком'), 'i'=>'fa fa-th-list','a'=>0),
        Shops::LIST_TYPE_MAP  => array('t'=>_t('shops','На карте'),'i'=>'fa fa-map-marker','a'=>0),
    );
    if( ! isset($listTypes[$f_lt]) ) $f_lt = key($listTypes);
    $listTypes[$f_lt]['a'] = true;
    if( $isMap = ($f_lt == Shops::LIST_TYPE_MAP) ) {
        Geo::mapsAPI(false);
        if (Geo::mapsType() == Geo::MAPS_TYPE_GOOGLE) {
            tpl::includeJS('markerclusterer/markerclusterer', false);
        }
    }

?>
<div class="row-fluid">
    <div class="l-page<? if( ! $isMap ) { ?> l-page_right<? } ?> sr-page sr-shops span12">
        <? if( ! $isMap ) { ?><div class="l-table"><div class="l-table-row"><? } ?>
            <div class="l-main<? if( ! $isMap ) { ?> l-table-cell<? } ?>">
                <div class="l-main__content">
                    <div id="j-shops-search-list">
                        <? # Хлебные крошки: ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) {
                               echo tpl::getBreadcrumbs($cat['crumbs'], false, 'breadcrumb');
                        } ?>
                        <div class="sr-page__result__navigation rel">
                            <div class="sr-page__result__navigation__title pull-left"><h1 class="pull-left"><?= ( $f_c > 0 ? $cat['titleh1'] : ( ! empty($f_q) ? _t('shops', 'Результаты поиска по запросу "[query]"', array('query'=>$f_q)) : _t('shops', 'Все магазины') ) ) ?></h1></div>
                            <div class="sr-page__list__navigation_view pull-right">
                                <? # Тип списка: ?>
                                <div id="j-f-listtype" class="<?= (empty($items) ? 'hide' : '') ?>">
                                <? foreach($listTypes as $k=>$v) {
                                        ?><a href="javascript:void(0);" data="{id:<?= $k ?>}" data-id="<?= $k ?>" class="j-type<? if($v['a']){ ?> active<? } ?>"><i class="<?= $v['i'] ?>"></i><span class="hidden-phone"><?= $v['t'] ?></span></a><?
                                   } ?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? # Результаты поиска (список магазинов): ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                            <div class="hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
                                <?= $this->searchList(bff::DEVICE_DESKTOP, $f_lt, $items, $num_start); ?>
                            </div>
                        <? } if(DEVICE_PHONE) { ?>
                            <div class="visible-phone j-list-<?= bff::DEVICE_PHONE ?>">
                                <?= $this->searchList(bff::DEVICE_PHONE, $f_lt, $items, $num_start); ?>
                            </div>
                        <? } ?>
                        <? # Постраничная навигация: ?>
                        <div id="j-shops-search-pgn">
                            <?= $pgn ?>
                        </div>
                    </div>
                </div>
            </div>
            <? # Баннер (справа): ?>
            <? if(DEVICE_DESKTOP_OR_TABLET && ! $isMap && ($bannerRight = Banners::view('shops_search_right')) ) { ?>
            <div class="l-right l-table-cell visible-desktop">
                <div class="l-right__content">
                    <? if(User::id() && !User::shopID() && bff::shopsEnabled()): ?>
                    <div class="l-right__content__btn">
                        <a href="<?= Shops::url('my.open') ?>" class="btn btn-block btn-info">
                            <i class="icon-plus icon-white"></i><span> <?= _t('shops', 'Открыть магазин'); ?></span>
                        </a>
                    </div>
                    <? endif; ?>
                    <div class="l-banner banner-right">
                        <div class="l-banner__content">
                            <?= $bannerRight ?>
                        </div>
                    </div>
                </div>
            </div>
            <? } ?>
        <? if( ! $isMap ) { ?></div></div><? } ?>
        <div class="l-info">
            <? if($cat['id'] > 0) echo $cat['seotext'] ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
<?
    if($isMap) {
        foreach($items as &$v) { unset($v['descr']); } unset($v);
    } else {
        $items = array();
    }
?>
    $(function(){
        jShopsSearch.init(<?= func::php2js(array(
            'lang'=>array(
                'map_toggle_open' => _t('shops', 'больше карты'),
                'map_toggle_close' => _t('shops', 'меньше карты'),
                'map_content_loading' => _t('shops', 'Загрузка, подождите...'),
                'map_show_items' => _t('shops', 'Показать объявления'),
            ),
            'listtype' => $listTypes,
            'items'    => $items,
            'defaultCoords' => Geo::mapDefaultCoords(true),
            'ajax'     => false,
        )) ?>);
    });
<? js::stop(); ?>
</script>
<?

# актуализируем данные формы поиска
# формируемой позже в фаблоне /tpl/filter.php
$this->searchFormData($f);