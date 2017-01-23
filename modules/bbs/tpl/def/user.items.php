<?php
    /**
     * Список объявлений пользователя: layout
     * @var $this BBS
     */
    if($empty) {
        echo $this->showInlineMessage(_t('bbs', 'Список объявлений пользователя пустой'));
        return;
    }
    tpl::includeJS(array('history'), true);
    tpl::includeJS('bbs.user', false, 2);
?>

<form action="" id="j-user-view-items-list">
<input type="hidden" name="c" value="<?= $f['c'] ?>" class="j-cat-value" />
<input type="hidden" name="page" value="<?= $f['page'] ?>" />

<div class="l-center">
    <div class="l-center__content v-page__content_center">

        <div class="sr-page__result__navigation rel">
            <div class="sr-page__result__navigation__title pull-left"><h1 class="pull-left"><?= _t('bbs', 'Объявления пользователя') ?></h1></div>

            <div class="pull-right hidden-phone">
                <ul class="nav nav-pills pull-left j-cat">
                    <li class="dropdown">
                        <a class="dropdown-toggle j-cat-dropdown" data-toggle="dropdown" href="#">
                            <b class="j-cat-title"><?= $cat_active['title'] ?></b>
                            <i class="fa fa-caret-down"></i>
                        </a>
                        <ul class="dropdown-menu">
                            <? foreach($cats as $v) {
                                if( empty($v['sub']) ) {
                                    ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?
                                } else {
                                    ?><li class="nav-header"><?= $v['title'] ?></li><?
                                    foreach($v['sub'] as $vv) {
                                        ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?
                                    }
                                }
                            } ?>
                        </ul>
                    </li>
                </ul>
            </div>
            <div class="clearfix"></div>
        </div>

        <? # Список объявлений ?>
        <div class="j-list">
            <div class="sr-page__list sr-page__list_desktop hidden-phone j-list-<?= bff::DEVICE_DESKTOP ?> j-list-<?= bff::DEVICE_TABLET ?>">
                <? if( $device == bff::DEVICE_DESKTOP || $device == bff::DEVICE_TABLET ) {
                    echo $this->searchList(bff::DEVICE_DESKTOP, $f['lt'], $items);
                } ?>
            </div>
            <div class="sr-page__list sr-page__list_mobile visible-phone j-list-<?= bff::DEVICE_PHONE ?>">
                <? if( $device == bff::DEVICE_PHONE ) {
                    echo $this->searchList(bff::DEVICE_PHONE, $f['lt'], $items);
                } ?>
            </div>
        </div>

    </div>
</div>
<div class="clearfix"></div>
</form>

<? # Постраничная навигация ?>
<div id="j-user-view-items-pgn">
    <?= $pgn ?>
</div>

<script type="text/javascript">
<? js::start(); ?>
    $(function(){
        jBBSUserItems.init(<?= func::php2js(array(
            'lang' => array(),
            'ajax' => true,
        )) ?>);
    });
<? js::stop(); ?>
</script>