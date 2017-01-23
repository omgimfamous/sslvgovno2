<?php
    /**
     * Форма магазина: открытие / редактирование настроек магазина
     * @var $this Shops
     */
    Geo::mapsAPI(true);
    tpl::includeJS(array('qquploader'), true);
    tpl::includeJS(array('shops.form'), false, 2);
    $aData = HTML::escape($aData, 'html', array('title','descr','skype','icq','addr_addr'));
    $edit = ($id > 0);
    $titles = ! $edit;
?>
<div class="u-cabinet__settings">
    <? if( ! $edit) { ?>
    <div class="sh-shop__description">
        <?= $add_text ?>
    </div>
    <? } else { ?>
        <? if($status == Shops::STATUS_BLOCKED) { ?>
            <div class="alert alert-danger">
                <?= _t('shops', 'Ваш магазин был заблокирован модератором, причина:<br /><strong>[reason]</strong>',
                        array('reason'=>$blocked_reason)) ?>
            </div>
        <? } ?>
    <? } ?>
    <div class="u-cabinet__settings__block">
    <form class="form-horizontal" action="" id="j-shops-form">
        <? if($titles) { ?>
        <div class="u-cabinet__settings__block">
        <div class="u-cabinet__settings__block__title"><span><?= _t('shops', 'Общая информация') ?></span></div>
        <div class="u-cabinet__settings__block__content">
        <? } ?>
            <div class="u-cabinet__settings__block__form">
                <div class="control-group">
                    <label class="control-label"><?= _t('shops', 'Логотип') ?><br /><small><?= _t('shops', 'Магазины с логотипом пользуются большим доверием') ?></small></label>
                    <div class="controls">
                        <div class="u-cabinet__settings__photo span6">
                            <a class="v-author__avatar" href="#" onclick="return false;">
                                <? if( ! $edit){ ?><input type="hidden" name="logo" value="" id="j-shop-logo-fn" /><? } ?>
                                <img alt="" src="<?= $logo_preview ?>" id="j-shop-logo-preview" />
                            </a>
                        </div>
                        <div class="u-cabinet__settings__photo_upload">
                            <a href="javascript:void(0);" class="btn" id="j-shop-logo-upload"><?= _t('shops', 'Загрузить логотип') ?></a>
                            <a href="#" id="j-shop-logo-delete" class="pseudo-link-ajax ajax-ico mrgl10 remove<? if(!$logo){ ?> hide<? } ?>"><i class="fa fa-times"></i> <span><?= _t('shops', 'удалить') ?></span></a>
                            <div class="help-block"><?= _t('shops', 'Максимальный размер файла - [size]', array('size'=>$logo_maxsize_format)) ?></div>
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label for="sh_title" class="control-label"><?= _t('shops', 'Название') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="text" name="title" value="<?= $title ?>" class="input-block-level j-required" id="sh_title" maxlength="50" />
                    </div>
                </div>

                <? if($cats_on) { ?>
                <div class="control-group control-group__100" id="j-shop-cats">
                    <label class="control-label"><?= _t('shops', 'Категория') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <input type="hidden" name="category-last" value="0" class="j-cat-selected-last j-required" />
                        <div class="i-formpage__catselect rel">
                            <div class="i-formpage__catselect__close">
                                <a href="#" class="ajax ajax-ico j-cat-select-link"><span><?= _t('shops', 'Выберите категорию') ?></span> <i class="fa fa-chevron-down"></i></a>
                            </div>
                            <div class="i-formpage__catselect__popup dropdown-block box-shadow abs hide j-cat-select-popup">
                                <div class="i-formpage__catselect__popup__content">
                                    <? if( DEVICE_DESKTOP_OR_TABLET ): ?>
                                    <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-desktop">
                                        <?= $this->catsList('form', bff::DEVICE_DESKTOP, 0); ?>
                                    </div>
                                    <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-desktop hide"></div>
                                    <? endif; ?>
                                    <? if( DEVICE_PHONE ): ?>
                                    <div class="i-formpage__catselect__popup__mainlist j-cat-select-step1-phone">
                                        <?= $this->catsList('form', bff::DEVICE_PHONE, 0); ?>
                                    </div>
                                    <div class="i-formpage__catselect__popup__sublist j-cat-select-step2-phone hide"></div>
                                    <? endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="j-cat-selected-items"></div>

                </div>
                <? } ?>

                <div class="control-group">
                    <label for="sh_descr" class="control-label"><?= _t('shops', 'Описание') ?><span class="required-mark">*</span></label>
                    <div class="controls">
                        <textarea name="descr" class="input-block-level j-required" id="sh_descr" rows="6" autocapitalize="off"><?= $descr ?></textarea>
                    </div>
                </div>

                <div id="j-shop-geo">
                    <div class="control-group">
                        <label class="control-label"><?= _t('shops', 'Город') ?></label>
                        <div class="controls rel">
                            <?= Geo::i()->citySelect($region_id, true, 'region_id', array(
                                'on_change'=>'jShopsForm.onCitySelect',
                                'form' => 'shops-'.($edit ? 'settings' : 'form'),
                            )); ?>
                        </div>
                    </div>
                    <div id="j-shop-geo-addr">
                        <div class="control-group">
                            <label for="j-shop-geo-addr-addr" class="control-label"><?= _t('shops', 'Адрес магазина') ?></label>
                            <div class="controls">
                                <input type="hidden" name="addr_lat" id="j-shop-geo-addr-lat" value="<?= $addr_lat ?>" />
                                <input type="hidden" name="addr_lon" id="j-shop-geo-addr-lon" value="<?= $addr_lon ?>" />
                                <input type="text" name="addr_addr" id="j-shop-geo-addr-addr" value="<?= $addr_addr ?>" class="input-block-level" />
                            </div>
                        </div>
                        <div class="control-group i-formpage__map">
                            <div class="controls">
                                <div id="j-shop-geo-addr-map" style="height: 250px; width: 100%; max-width: 470px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <? if($titles) { ?>
        </div>
        <div class="u-cabinet__settings__block__title"><span><?= _t('shops', 'Контактные данные') ?></span></div>
        <div class="u-cabinet__settings__block__content">
        <? } ?>
            <div class="u-cabinet__settings__block__form">
                <div class="control-group">
                    <label class="control-label"><?= _t('shops', 'Контакты') ?></label>
                    <div class="controls">
                        <div class="i-formpage__contacts">
                            <div id="j-shop-phones"></div>
                            <div class="i-formpage__contacts__item">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="ico ico__skype-dark"></i></span>
                                    <input type="text" name="skype" value="<?= $skype ?>" class="input-large" placeholder="<?= _t('', 'Skype') ?>" maxlength="32" />
                                </div>
                            </div>
                            <div class="i-formpage__contacts__item i-formpage__contacts__item_last">
                                <div class="input-prepend">
                                    <span class="add-on"><i class="ico ico__icq-dark"></i></span>
                                    <input type="text" name="icq" value="<?= $icq ?>" class="input-large" placeholder="<?= _t('', 'ICQ') ?>" maxlength="20" pattern="[0-9]*" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label for="sh_website" class="control-label"><?= _t('shops', 'Ссылка на сайт') ?></label>
                    <div class="controls">
                        <div class="input-prepend">
                            <span class="add-on"><i class="fa fa-globe"></i></span>
                            <input type="text" name="site" value="<?= $site ?>" id="sh_website" class="input-large" placeholder="www.yoursite.com" maxlength="200" />
                        </div>
                    </div>
                </div>

                <div class="control-group sh-social-networks">
                    <label class="control-label"><?= _t('shops', 'Социальные сети') ?></label>
                    <div id="j-shop-social-links"></div>
                    <select id="j-shop-social-links-types" class="hide"><?= Shops::socialLinksTypes(true) ?></select>
                    <div class="controls">
                        &nbsp;<a class="pseudo-link-ajax" id="j-social-links-plus" href="#"><small>+ <?= _t('shops', 'ещё социальная сеть') ?></small></a>
                    </div>
                </div>
            </div>
        <? if($titles) { ?></div><? } ?>

        <div class="u-cabinet__settings__block__form">
            <div class="control-group">

                <? if( ! $edit) { ?>
                <label class="control-label text-right hidden-phone">
                    <div class="i-formpage__cancel"><span class="btn-link" onclick="history.back();">&laquo; <?= _t('', 'Отмена') ?></span></div>
                </label>
                <? } ?>
                <div class="controls">
                    <input type="submit" class="btn btn-success j-submit" value="<?= ($edit ? _t('shops', 'Сохранить') : _t('shops', 'Открыть магазин')) ?>" />
                    <? if($edit && bff::servicesEnabled()){ ?><a href="<?= Shops::url('shop.promote', array('id'=>$id,'from'=>'settings')) ?>" class="btn"><i class="fa fa-arrow-up"></i> <span class="hidden-phone"><?= _t('shops', 'Продвинуть магазин') ?></span><span class="visible-phone"><?= _t('shops', 'Продвинуть') ?></span></a><? } ?>
                    <? if( ! $edit) { ?><span class="i-formpage__cancel_mobile btn-link cancel" onclick="history.back();"><?= _t('', 'Отмена') ?></span><? } ?>
                </div>
            </div>
        </div>

        <? if($titles) { ?></div><? } ?>
    </form>
    </div>
</div>

<script type="text/javascript">
<? js::start() ?>
jShopsForm.init(<?= func::php2js(array(
        'edit' => $edit,
        'url_submit' => ( $edit ? Users::url('my.settings', array('act'=>'shop')) : Shops::url('my.open') ),
        'lang' => array(
            'saved_success' => _t('shops', 'Настройки успешно сохранены'),
            'logo_upload_messages' => array(
                'typeError' => _t('shops', 'Допустимы только следующие типы файлов: {extensions}'),
                'sizeError' => _t('shops', 'Файл {file} слишком большой, максимально допустимый размер {sizeLimit}'),
                'minSizeError' => _t('shops', 'Файл {file} имеет некорректный размер'),
                'emptyError' => _t('shops', 'Файл {file} имеет некорректный размер'),
                'onLeave' => _t('shops', 'Происходит загрузка изоражения, если вы покинете эту страницу, загрузка будет прекращена'),
            ),
            'logo_upload' => _t('shops', 'Загрузка логотипа'),
            'category_select' => _t('shops', 'Выберите категорию магазина'),
            'social_link' => _t('shops', 'Ссылка'),
            'phones_tip' => _t('shops', 'Номер телефона'),
            'phones_plus' => _t('shops', '+ ещё<span [attr]> телефон</span>', array('attr'=>'class="hidden-phone"')),
        ),
        //logo
        'logoMaxSize' => $logo_maxsize,
        //cats
        'catsOn' => $cats_on,
        'catsMain' => $cats_main,
        'catsRootID' => Shops::CATS_ROOTID,
        'catsLimit' => Shops::categoriesLimit(),
        'catsSelected' => $cats,
        //phones
        'phonesLimit' => Shops::phonesLimit(),
        'phonesData' => $phones,
        //social links
        'socialLinksLimit' => Shops::socialLinksLimit(),
        'socialLinksData' => $social,
        'uploadProgress'  => '<div class="align-center j-progress" style="width: 200px;  height: 80px;  float: left;  line-height: 80px;"> <img alt="" src="'.SITEURL_STATIC.'/img/loading.gif"> </div>',
    )) ?>);
<? js::stop() ?>
</script>