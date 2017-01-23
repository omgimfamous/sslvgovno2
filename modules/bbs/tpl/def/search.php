<?php

    /**
     * Поиск объявлений: layout
     * @var $this BBS
     */

    tpl::includeJS(array('history'), true);
    extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

    # Типы списка:
    $aListTypes = array(
        BBS::LIST_TYPE_LIST    => array('t'=>_t('search','Списком'), 'i'=>'fa fa-th-list','a'=>0),
        BBS::LIST_TYPE_GALLERY => array('t'=>_t('search','Галереей'),'i'=>'fa fa-th','a'=>0),
        BBS::LIST_TYPE_MAP     => array('t'=>_t('search','На карте'),'i'=>'fa fa-map-marker','a'=>0),
    );
    if( ! $cat['addr'] ) unset($aListTypes[BBS::LIST_TYPE_MAP]);
    if( ! isset($aListTypes[$f_lt]) ) $f_lt = key($aListTypes);
    $aListTypes[$f_lt]['a'] = true;
    if( $isMap = ($f_lt == BBS::LIST_TYPE_MAP) ) {
        Geo::mapsAPI(false);
        if (Geo::mapsType() == Geo::MAPS_TYPE_GOOGLE) {
            tpl::includeJS('markerclusterer/markerclusterer', false);
        }
    }

    # Типы сортировки:
    $aSortTypes = array(
        'new' => array('t'=>_t('search','Самые новые')),
    );
    if( $cat['price'] ) {
        $aSortTypes['price-asc'] = array('t'=>_t('search','От дешевых к дорогим'));
        $aSortTypes['price-desc'] = array('t'=>_t('search','От дорогих к дешевым'));
    }
    if( ! isset($aSortTypes[$f_sort]) ) $f_sort = key($aSortTypes);
?>
<div class="row-fluid">
    <div class="l-page<? if( ! $isMap ) { ?> l-page_right<? } ?> sr-page span12">
        <? if( ! $isMap ) { ?><div class="l-table"><div class="l-table-row"><? } ?>
            <div class="l-main<? if( ! $isMap ) { ?> l-table-cell<? } ?>">
                <div class="l-main__content">
                    <div id="j-bbs-search-list">
                        <ul class="sr-page__main__navigation nav nav-tabs">
                            <?
                            # Типы категорий:
                            if( empty($cat['types']) ) {
                                $cat['types'] = array(array('id'=>BBS::TYPE_OFFER,'title'=>_t('search','Объявления'),'items'=>$total));
                            }
                            if(DEVICE_DESKTOP_OR_TABLET) {
                                foreach($cat['types'] as $k=>$v) {
                                    ?><li class="<? if($k == $f_ct) { ?>active <? } ?>hidden-phone"><a href="javascript:void(0);" class="j-f-cattype-desktop" data="{id:<?= $v['id'] ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}" data-id="<?= $v['id'] ?>"><b><?= $v['title'] ?></b></a></li><?
                                }
                            }
                            if(DEVICE_PHONE) { ?>
                            <li class="sr-page__navigation__type dropdown rel <? if( sizeof($cat['types']) > 1 ) { ?>visible-phone<? } else { ?>hidden<? } ?>">
                                <a class="dropdown-toggle" id="j-f-cattype-phone-dd-link" data-current="<?= $f_ct ?>" href="javascript:void(0);">
                                    <span class="lnk"><?= $cat['types'][$f_ct]['title'] ?></span> <i class="fa fa-caret-down"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-block box-shadow" id="j-f-cattype-phone-dd">
                                    <?
                                        foreach($cat['types'] as $k=>$v) {
                                        ?><li><a href="javascript:void(0);" class="j-f-cattype-phone" data="{id:<?= $k ?>,title:'<?= HTML::escape($v['title'], 'js') ?>'}"><?= $v['title'] ?></a> </li><?
                                    }
                                    ?>
                                </ul>
                            </li>
                            <? }
                            # Сортировка:
                            if( sizeof($aSortTypes) > 1 ) {
                            ?>
                            <li class="sr-page__navigation__sort pull-right dropdown rel">
                                <a class="dropdown-toggle" id="j-f-sort-dd-link" data-current="<?= $f_sort ?>" href="javascript:void(0);">
                                    <span class="hidden-phone"><?= _t('search', 'Сортировка') ?> : </span>
                                    <span class="visible-phone pull-left"><i class="fa fa-refresh"></i>&nbsp;</span>
                                    <span class="lnk"><?= $aSortTypes[$f_sort]['t'] ?></span>
                                </a>
                                <ul class="dropdown-menu dropdown-block box-shadow" id="j-f-sort-dd">
                                    <? foreach($aSortTypes as $k=>$v) { ?><li><a href="javascript:void(0);" class="j-f-sort" data="{key:'<?= $k ?>',title:'<?= HTML::escape($v['t'], 'js') ?>'}"><?= $v['t'] ?></a></li><? } ?>
                                </ul>
                            </li>
                            <? } ?>
                        </ul>
                        <? # Хлебные крошки: ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) {
                               echo tpl::getBreadcrumbs($cat['crumbs'], false, 'breadcrumb');
                        } ?>
                        <div class="sr-page__result__navigation rel">
                            <div class="sr-page__result__navigation__title pull-left"><h1 class="pull-left"><?= ( $f_c > 0 ? $cat['titleh1'] : ( ! empty($f_q) ? _t('search', 'Результаты поиска по запросу "[query]"', array('query'=>$f_q)) : _t('search', 'Поиск объявлений') ) ) ?></h1></div>
                            <div class="sr-page__list__navigation_view pull-right">
                                <? # Тип списка: ?>
                                <div id="j-f-listtype" class="<?= (empty($items) ? 'hide' : '') ?>">
                                <? foreach($aListTypes as $k=>$v) {
                                        ?><a href="javascript:void(0);" data="{id:<?= $k ?>}" data-id="<?= $k ?>" class="j-type<? if($v['a']){ ?> active<? } ?>"><i class="<?= $v['i'] ?>"></i><span class="hidden-phone"><?= $v['t'] ?></span></a><?
                                   } ?>
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                        <? # Результаты поиска (список объявлений): ?>
                        <? if(DEVICE_DESKTOP_OR_TABLET) { ?>
                        <!-- for: desktop & tablet -->
                        <div class="hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
                            <?= $this->searchList(bff::DEVICE_DESKTOP, $f_lt, $items, $num_start); ?>
                        </div>
                        <? } if(DEVICE_PHONE) { ?>
                        <!-- for: mobile -->
                        <div class="visible-phone j-list-<?= bff::DEVICE_PHONE ?>">
                            <?= $this->searchList(bff::DEVICE_PHONE, $f_lt, $items, $num_start); ?>
                        </div>
                        <? } ?>
                        <? # Постраничная навигация: ?>
                        <div id="j-bbs-search-pgn">
                            <?= $pgn ?>
                        </div>
                    </div>
                </div>
            </div>
            <? # Баннер (справа): ?>
            <? if(DEVICE_DESKTOP_OR_TABLET && ! $isMap && ($bannerRight = Banners::view('bbs_search_right', array('cat'=>$cat['id'], 'region'=>$f['region']))) ) { ?>
            <div class="l-right l-table-cell visible-desktop">
                <div class="l-right__content">
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
            <? if($cat['id'] > 0 && $f['page'] <= 1) echo $cat['seotext'] ?>
        </div>
    </div>
</div>
<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBBSSearch.init(<?= func::php2js(array(
            'lang'=>array(
                'range_from' => _t('filter','от'),
                'range_to'   => _t('filter','до'),
                'btn_reset'  => _t('filter','Не важно'),
                'map_toggle_open' => _t('search', 'больше карты'),
                'map_toggle_close' => _t('search', 'меньше карты'),
                'metro_declension' => _t('filter','станция;станции;станций'),
            ),
            'cattype'  => $cat['types'],
            'cattype_ex' => BBS::CATS_TYPES_EX,
            'listtype' => $aListTypes,
            'sort'     => $aSortTypes,
            'items'    => ( $f_lt == BBS::LIST_TYPE_MAP ? $items : array() ),
            'defaultCoords' => Geo::mapDefaultCoords(true),
            'ajax'     => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>
<?

# актуализируем данные формы поиска
# формируемой позже в фаблоне /tpl/filter.php
$this->searchFormData($f);