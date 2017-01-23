<?php

/**
 * Поиск объявлений: форма поиска (layout)
 * @var $this BBS
 */

tpl::includeJS(array('bbs.search'), false, 7);

# фильтр: категория (определяется в BBS::search)
$catData = bff::filter('bbs-search-category');
$catID = ( ! empty($catData['id']) ? $catData['id'] : 0 );
$catACTIVE = ($catID > 0);
$catACTIVE_STEP = ($catACTIVE ? ($catData['subs'] || $catData['numlevel']>1 ? 2 : 1) : 1);

if ($catACTIVE) {
    $catDP_Extra = array(
        'price' => array(
            'enabled' => $catData['price'],
            'sett'    => ( $catData['price'] && ! empty($catData['price_sett']) ? $catData['price_sett'] : array() ) ),
        'photos' => ($catData['photos'] > 0),
        'owner_search' => $catData['owner_search'],
        'owner_business' => ! empty($catData['owner_business']),
        'owner_business_title' => array(
            BBS::OWNER_PRIVATE  => ( ! empty($catData['owner_private_search']) ? $catData['owner_private_search'] : _t('bbs','От частных лиц')),
            BBS::OWNER_BUSINESS => ( ! empty($catData['owner_business_search']) ? $catData['owner_business_search'] : _t('bbs','Только бизнес объявления'))
        ),
        'f'   => &$f,
        'cat' => &$catData,
    );
}
extract($f, EXTR_REFS | EXTR_PREFIX_ALL, 'f');

?>
<div class="row-fluid">
    <div class="f-msearch rel span12">
    <!-- START main search and filter area -->
    <noindex>
    <?
        if( DEVICE_DESKTOP_OR_TABLET ) {
            echo Geo::i()->filterForm(bff::DEVICE_DESKTOP);
        }
    ?>
    <form id="j-f-form" action="<?= BBS::url('items.search', $catData) ?>" method="get" class="form-inline rel">
        <input type="hidden" name="c" value="<?= $f_c ?>"<? if( ! $f_c ) { ?> disabled="disabled"<? } ?> />
        <input type="hidden" name="ct" value="<?= $f_ct ?>" />
        <input type="hidden" name="lt" value="<?= $f_lt ?>" />
        <input type="hidden" name="sort" value="<?= $f_sort ?>" />
        <input type="hidden" name="page" value="<?= $f_page ?>" />
        <? if( DEVICE_DESKTOP_OR_TABLET ) { ?>
        <!--for: desktop and tablet-->
        <div class="f-msearch_desktop hidden-phone">
            <table width="100%">
                <tr>
                    <td class="category" width="20">
                        <a class="f-msearch_desktop__category btn nowrap" href="#" id="j-f-cat-desktop-link">
                            <span class="title"><?= ( $catACTIVE ? $catData['dropdown']['title'] : _t('bbs','Все категории') ) ?></span>
                            <i class="fa fa-caret-down"></i>
                        </a>
                    </td>
                    <td class="input">
                        <input type="text" name="q" id="j-f-query" placeholder="<?= _t('bbs','Поиск объявлений...') ?>" autocomplete="off" style="width: 100%" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                        <div id="j-search-quick-dd" class="f-qsearch hidden-tablet rel hide">
                            <div class="f-qsearch__results j-search-quick-dd-list"></div>
                        </div>
                    </td>
                    <td width="70">
                        <button type="submit" class="btn pull-left j-submit"><?= _t('bbs','Найти') ?></button>
                    </td>
                </tr>
            </table>
            <? /* Фильтр по категории (desktop) */ ?>
            <div id="j-f-cat-desktop-popup" class="f-msearch__categories f-msearch__subcategories dropdown-title-block box-shadow abs hide">
                <div id="j-f-cat-desktop-step1"<? if($catACTIVE_STEP != 1) { ?> class="hide"<? } ?>>
                    <?= $this->catsList('search', bff::DEVICE_DESKTOP, 0); ?>
                </div>
                <div id="j-f-cat-desktop-step2"<? if($catACTIVE_STEP != 2) { ?> class="hide"<? } ?>>
                    <? if($catACTIVE_STEP == 2) echo $this->catsList('search', bff::DEVICE_DESKTOP, $catData['dropdown']['id']); ?>
                </div>
            </div>
            <? if($catACTIVE) { ?>
            <!-- START filter for category-->
            <div class="f-catfilter rel">
                <div class="f-catfilter__content rel" id="j-f-desktop">
                    <?  /* Фильтр дин. свойств */
                        $dp = $this->dp()->form($catID, array(), true, true, false, 'search.form.desktop', $this->module_dir_tpl, $catDP_Extra);
                        echo $dp['form'];
                    ?>
                </div>
            </div>
            <!-- END filter for category -->
            <? } ?>
        </div>
        <? } if(DEVICE_PHONE) { ?>
        <!--for: mobile-->
        <div class="f-msearch_mobile visible-phone">
            <?= Geo::i()->filterForm(bff::DEVICE_PHONE); ?>
            <? if( ! bff::isIndex() ) { ?>
            <!--STAR select category-->
            <div class="select-ext select-ext-group">
                <div class="select-ext-container " style="width:100%">
                    <a class="select-ext-bnt" href="#" id="j-f-cat-phone-link">
                        <span><?= ( $catACTIVE ? $catData['title'] : _t('bbs','Все категории') ) ?></span>
                        <i class="fa fa-caret-down"></i>
                    </a>
                    <div id="j-f-cat-phone-popup" class="select-ext-drop hide"  style="width:99%;">
                        <!-- START categories change as index page -->
                        <div class="f-index__mobile f-index__mobile__mainfilter">
                            <? /* Фильтр по категории (phone) */ ?>
                            <div class="f-msearch__categories">
                                <div id="j-f-cat-phone-step1"<? if($catACTIVE_STEP != 1) { ?> class="hide"<? } ?>>
                                    <?= $this->catsList('search', bff::DEVICE_PHONE, 0); ?>
                                </div>
                                <div id="j-f-cat-phone-step2"<? if($catACTIVE_STEP != 2) { ?> class="hide"<? } ?>>
                                    <? if($catACTIVE_STEP == 2) echo $this->catsList('search', bff::DEVICE_PHONE, $catID); ?>
                                </div>
                            </div>
                        </div>
                        <!-- END categories change as index -->
                    </div>
                </div>
            </div>
            <!--END select category-->
            <? } ?>
            <div class="input-append span12">
                <input type="text" name="mq" placeholder="<?= _t('bbs', 'Поиск объявлений...') ?>" value="<?= HTML::escape($f_q) ?>" maxlength="80" />
                <button type="submit" class="btn j-submit"><i class="fa fa-search"></i></button>
            </div>
            <? if($catACTIVE) { ?>
            <!--STAR filter category-->
            <div class="f-catfiltermob">
                <span class="f-catfiltermob__content__title">
                    <a class="ajax" href="javascript:void(0);"><?= ( $f_filter_active ? _t('bbs', 'Изменить настройки фильтра') : _t('bbs', 'Фильтровать результаты') ) ?></a>
                </span>
                <div class="f-catfiltermob__content hide" id="j-f-phone">
                    <?  /* Фильтр дин. свойств */
                        $dp = $this->dp()->form($catID, array(), true, true, false, 'search.form.phone', $this->module_dir_tpl, $catDP_Extra);
                        echo $dp['form'];
                    ?>
                    <div class="clearfix"></div>
                    <button type="button" class="btn btn-small btn-info j-submit"><?= _t('bbs', 'Отфильтровать') ?></button>
                    <button type="button" class="btn btn-small j-cancel"><?= _t('bbs', 'Отменить') ?></button>
                </div>
            </div>
            <!--STAR filter category-->
            <? } ?>
        </div>
        <? } ?>
    </form>
    </noindex>
    <!-- END main search and filter area -->
    </div>
<? if( DEVICE_PHONE && bff::isIndex() ) { ?>
    <!-- START categories change on index page -->
    <div class="f-index__mobile visible-phone">
        <div id="j-f-cat-phone-index-step1">
            <? /* Фильтр по категории на главной (phone) */
               echo BBS::i()->catsList('index', bff::DEVICE_PHONE, 0);
            ?>
        </div>
        <div id="j-f-cat-phone-index-step2" class="hide"></div>
    </div>
    <!-- END categories change on index -->
<? } ?>
</div>