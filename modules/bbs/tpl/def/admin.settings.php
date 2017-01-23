<?php
tplAdmin::adminPageSettings(array('icon' => false));
?>
<form method="post" action="" id="j-bbs-settings-form" enctype="multipart/form-data">
    <input type="hidden" name="save" value="1" />
    <input type="hidden" name="tab" value="<?= HTML::escape($tab) ?>" class="j-tab-current" />

    <div class="tabsBar j-tabs">
        <? foreach($aData['tabs'] as $k=>$v){ ?>
        <span class="tab j-tab<? if($k==$tab){ ?> tab-active<? } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
        <? } ?>
    </div>
    <div style="margin:10px 0 10px 10px;">

        <!-- general -->
        <div id="j-tab-general" class="j-tab-form">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title" style="width:250px;">Срок публикации объявления:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="1" max="1000" maxlength="4" name="item_publication_period" value="<?= (!empty($item_publication_period) ? intval($item_publication_period) : 0 ) ?>" />
                        <div class="help-inline">в днях</div>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title">Срок продления объявления:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="1" max="1000" maxlength="4" name="item_refresh_period" value="<?= (!empty($item_refresh_period) ? intval($item_refresh_period) : 0 ) ?>" />
                        <div class="help-inline">в днях</div>
                    </td>
                </tr>
                <tr<? if( ! FORDEV){ ?> class="displaynone"<? } ?>><td colspan="2"><hr /></td></tr>
                <tr<? if( ! FORDEV){ ?> class="displaynone"<? } ?>>
                    <td class="row1 field-title">Контакты в объявлении:</td>
                    <td class="row2">
                        <label class="radio"><input type="radio" name="items_contacts" value="1"<? if($items_contacts == 1) { ?> checked="checked"<? } ?> /> указывать для каждого объявления<span class="desc">  (по умолчанию копируются из профиля)</span></label>
                        <label class="radio"><input type="radio" name="items_contacts" value="2"<? if($items_contacts == 2) { ?> checked="checked"<? } ?> /> брать из профиля пользователя<? if(bff::shopsEnabled()) { ?> / магазина<? } ?><span class="desc"> (в объявлении не редактируются)</span></label>
                    </td>
                </tr>
                <tr><td colspan="2"><hr /></td></tr>
                <tr>
                    <td class="row1 field-title">Уведомлять пользователей<br />о завершении публикации объявлений:</td>
                    <td class="row2">
                        <? foreach($item_unpublicated_soon_days as $day): ?>
                        <label class="checkbox inline">
                            <input type="checkbox" name="item_unpublicated_soon[]" value="<?= $day ?>" <?= in_array($day,$item_unpublicated_soon) ? 'checked="checked"' : '' ?>> за <?= tpl::declension($day, _t('', 'день;дня;дней')); ?>
                        </label>
                        <? endforeach; ?>
                    </td>
                </tr>
                <tr<? if( ! FORDEV){ ?> class="displaynone"<? } ?>>
                    <td class="row1 field-title">Кол-во отправляемых уведомлений:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="30" max="999" maxlength="3" name="item_unpublicated_soon_messages" value="<?= (!empty($item_unpublicated_soon_messages) ? intval($item_unpublicated_soon_messages) : 100 ) ?>">
                        <div class="help-inline">за один раз<span class="desc"> &mdash; оптимальное значение: 50 - 100</span></div>
                    </td>
                </tr>
                <tr><td colspan="2"><hr /></td></tr>
                <tr>
                    <td class="row1 field-title">Уровень подкатегории,<br />отображаемый в фильтре поиска:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="2" name="categories_filter_level" value="<?= HTML::escape($categories_filter_level) ?>">
                        <div class="help-inline">минимальное значение — <strong>2</strong></div>
                    </td>
                </tr>
                <tr><td colspan="2"><hr /></td></tr>
                <tr<? if ( !bff::shopsEnabled()){ ?> class="displaynone"<? } ?>>
                    <td class="row1 field-title">Возможность импорта объявлений:</td>
                    <td class="row2">
                        <select name="items_import" class="j-items-import-select" style="width: auto;">
                            <?= HTML::selectOptions(array(
                                    BBS::IMPORT_ACCESS_ADMIN  => 'только администратору',
                                    BBS::IMPORT_ACCESS_CHOSEN => 'избранным магазинам',
                                    BBS::IMPORT_ACCESS_ALL    => 'всем магазинам',
                                ), $items_import)
                            ?>
                        </select>
                        <div class="help-inline <? if($items_import != BBS::IMPORT_ACCESS_CHOSEN){ ?> displaynone<? } ?> j-items-import-counter">активных магазинов &mdash; <span class="bold"><?= $import_active_shops ?></span></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- limits -->
        <div id="j-tab-limits" class="j-tab-form hide">
            <table class="admtbl tbledit">
                <tbody>
                <? if(BBS::categoryFormEditable()): ?>
                    <tr>
                        <td colspan="2">
                            <div class="alert alert-info">Сейчас пользователю доступна возможность <strong>редактирования</strong> (изменения) категории при редактировании объявления, таким образом он сможет обойти данные настройки лимитирования.</div>
                        </td>
                    </tr>
                <? endif; ?>
                <tr>
                    <td class="row1 field-title" style="width:185px;">Публикация объявлений - <br /><strong>пользователи</strong>:</td>
                    <td class="row2">
                        <select name="items_limits_user" class="j-items_limits_user" style="width: auto;">
                            <?= HTML::selectOptions(array(
                                BBS::LIMITS_NONE        => 'без ограничений',
                                BBS::LIMITS_COMMON      => 'общий лимит',
                                BBS::LIMITS_CATEGORY    => 'по категориям',
                            ), $items_limits_user)
                            ?>
                        </select>
                    </td>
                </tr>
                <tr class="j-items-limits-user j-items-limits-user-<?= BBS::LIMITS_COMMON ?><?= $items_limits_user != BBS::LIMITS_COMMON ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Общий лимит:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="0" max="9999" maxlength="4" name="items_limits_user_common" value="<?= (!empty($items_limits_user_common) ? intval($items_limits_user_common) : 0 ) ?>" />
                        <div class="help-inline">/ сутки <span class="desc">(во все категории)</span></div>
                    </td>
                </tr>
                <tr class="j-items-limits-user j-items-limits-user-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_user != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Лимит по-умолчанию:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="0" max="9999" maxlength="4" name="items_limits_user_category_default" value="<?= (!empty($items_limits_user_category_default) ? intval($items_limits_user_category_default) : 0 ) ?>" />
                        <div class="help-inline">/ сутки</div><br />
                    </td>
                </tr>
                <tr class="j-items-limits-user j-items-limits-user-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_user != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Категории:</td>
                    <td class="row2">
                        <select id="j-items-limits-user-category-select"></select>
                        <a href="#" class="ajax desc" id="j-items-limits-user-category-add">+ добавить лимит на категорию</a>
                    </td>
                </tr>
                <tr class="j-items-limits-user j-items-limits-user-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_user != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title"></td>
                    <td class="row2">
                        <table id="j-items-limits-user-categories">
                        </table>
                    </td>
                </tr>
                </tbody>

                <tbody class="j-items-limits-shop-all<? if ( !bff::shopsEnabled()){ ?> displaynone<? } ?>">
                <tr><td colspan="2"><hr /></td></tr>
                <tr>
                    <td class="row1 field-title">Публикация объявлений - <br /><strong>магазины</strong>:</td>
                    <td class="row2">
                        <select name="items_limits_shop" class="j-items_limits_shop" style="width: auto;">
                            <?= HTML::selectOptions(array(
                                BBS::LIMITS_NONE        => 'без ограничений',
                                BBS::LIMITS_COMMON      => 'общий лимит',
                                BBS::LIMITS_CATEGORY    => 'по категориям',
                            ), $items_limits_shop)
                            ?>
                        </select>
                        <div class="desc help-inline">настройки лимитирования для магазина не действуют, в случае если владельцу магазина был разрешен <strong>импорт объявлений</strong>.</div>
                    </td>
                </tr>
                <tr class="j-items-limits-shop j-items-limits-shop-<?= BBS::LIMITS_COMMON ?><?= $items_limits_shop != BBS::LIMITS_COMMON ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Общий лимит:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="0" max="9999" maxlength="4" name="items_limits_shop_common" value="<?= (!empty($items_limits_shop_common) ? intval($items_limits_shop_common) : 0 ) ?>" />
                        <div class="help-inline">/ сутки <span class="desc">(во все категории)</span></div>
                    </td>
                </tr>
                <tr class="j-items-limits-shop j-items-limits-shop-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_shop != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Лимит по-умолчанию:</td>
                    <td class="row2">
                        <input class="input-mini" type="number" min="0" max="9999" maxlength="4" name="items_limits_shop_category_default" value="<?= (!empty($items_limits_shop_category_default) ? intval($items_limits_shop_category_default) : 0 ) ?>" />
                        <div class="help-inline">/ сутки</div><br />
                    </td>
                </tr>
                <tr class="j-items-limits-shop j-items-limits-shop-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_shop != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title">Категории:</td>
                    <td class="row2">
                        <select id="j-items-limits-shop-category-select"></select>
                        <a href="#" class="ajax desc" id="j-items-limits-shop-category-add">+ добавить лимит на категорию</a>
                    </td>
                </tr>
                <tr class="j-items-limits-shop j-items-limits-shop-<?= BBS::LIMITS_CATEGORY ?><?= $items_limits_shop != BBS::LIMITS_CATEGORY ? ' displaynone' : '' ?>">
                    <td class="row1 field-title"></td>
                    <td class="row2">
                        <table id="j-items-limits-shop-categories">
                        </table>
                    </td>
                </tr>
                </tbody>

            </table>
        </div>

        <!-- spam -->
        <div id="j-tab-spam" class="j-tab-form hide">
            <table class="admtbl tbledit">
            <?= $this->locale->buildForm($aData, 'bbs-settings-instructions', '
            <tr>
                <td class="row1 field-title" style="width: 100px;">Минус-слова:</td>
                <td class="row2">
                    <textarea name="items_spam_minuswords[<?= $key ?>]" rows="12" placeholder="Укажите запрещенные слова через запятую"><?= ! empty($aData[\'items_spam_minuswords\'][$key]) ? HTML::escape($aData[\'items_spam_minuswords\'][$key]) : \'\' ?></textarea>
                </td>
            </tr>'); ?>
            <tr>
                <td>Дубликаты:</td>
                <td><label class="checkbox inline"><input type="checkbox" name="items_spam_duplicates" value="1" <?= ! empty($items_spam_duplicates) ? 'checked="checked"' : '' ?>/>выполнять проверку дубликатов по заголовку / описанию объявления</label></td>
            </tr>
            </table>
        </div>

        <!-- images -->
        <div id="j-tab-images" class="j-tab-form hide">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1 field-title" style="width:100px;">Водяной знак:</td>
                    <td class="row2">
                        <div style="margin-bottom:10px;">
                            <input type="hidden" name="images_watermark_delete" class="j-images-watermark-delete-flag" value="0" />
                            <input type="file" name="images_watermark" class="j-images-watermark-upload" size="17" <? if($images_watermark['exists']) { ?> style="display: none;"<? } ?> />
                            <? if ($images_watermark['exists']) { ?>
                            <div class="j-images-watermark-preview">
                                <img src="<?= $images_watermark['file']['url']; ?>" class="thumbnail" alt="" />
                                <a href="#" class="but-text bold cross desc ajax j-images-watermark-delete-cross">удалить</a>
                            </div>
                            <? } ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title">Расположение:</td>
                    <td class="row2">
                        <select name="images_watermark_pos_x" class="input-small">
                            <?= HTML::selectOptions(array('left' => 'слева', 'center' => 'по-центру', 'right' => 'справа'), $images_watermark['pos_x'])
                            ?>
                        </select>
                        <select name="images_watermark_pos_y" class="input-small">
                            <?= HTML::selectOptions(array('top' => 'сверху', 'center' => 'по-центру', 'bottom' => 'внизу'), $images_watermark['pos_y'])
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- instructions -->
        <div id="j-tab-instructions" class="j-tab-form hide">
            <table class="admtbl tbledit">
                <?= $this->locale->buildForm($aData, 'bbs-settings-instructions', '
    <tr>
        <td class="row1">
            <div style="margin-bottom: 10px;">Форма добавления:</div>
            <?= tpl::jwysiwyg($aData[\'form_add_\'.$key], \'form_add[\'.$key.\']\', 0, 120); ?>
        </td>
    </tr>
    <tr>
        <td class="row1">
            <div style="margin: 10px 0;">Форма редактирования:</div>
            <?= tpl::jwysiwyg($aData[\'form_edit_\'.$key], \'form_edit[\'.$key.\']\', 0, 120); ?>
        </td>
    </tr>'); ?>
            </table>
        </div>

        <!-- share -->
        <div id="j-tab-share" class="j-tab-form hide">
            <table class="admtbl tbledit">
                <tr>
                    <td class="row1">
                        <div style="margin-bottom: 10px;">Код для страницы "Просмотр объявления":</div>
                        <textarea rows="12" name="item_share_code"><?= HTML::escape($item_share_code) ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <hr class="cut" />
        <div class="footer">
            <input type="submit" class="btn btn-success button submit" value="Сохранить настройки" />
        </div>

    </div>
</form>

<script type="text/javascript">
//<![CDATA[
    var jBbsSettings = (function() {
        var $form, currentTab = 'general';
        var limitCats = <?= func::php2js($aCatsLimit) ?>;
        $(function() {
            $form = $('#j-bbs-settings-form');
           
            $form.find('.j-tabs').on('click', '.j-tab', function() {
                onTab($(this).data('tab'), this);
            });
            onTab('<?= $tab ?>', 0);

            $('.j-images-watermark-delete-cross', $form).on('click', function() {
                $('.j-images-watermark-delete-flag', $form).val(1);
                $('.j-images-watermark-preview', $form).hide();
                $('.j-images-watermark-upload', $form).show();
                return false;
            });

            $form.on('change', '.j-items-import-select', function(){
                var v = intval($(this).val());
                $form.find('.j-items-import-counter').toggleClass('displaynone',
                    v !== intval(<?= BBS::IMPORT_ACCESS_CHOSEN ?>));
                $form.find('.j-items-limits-shop-all').toggleClass('displaynone',
                    v === intval(<?= BBS::IMPORT_ACCESS_ALL ?>));
            });

            $form.on('change', '.j-items_limits_user', function(){
                var v = intval($(this).val());
                $form.find('.j-items-limits-user').addClass('displaynone');
                $form.find('.j-items-limits-user-'+v).removeClass('displaynone');
            });

            var $limitUserCatSelect = $('#j-items-limits-user-category-select');
            var $limitUserCat = $('#j-items-limits-user-categories');
            $form.on('click', '#j-items-limits-user-category-add', function(e){
                e.preventDefault();
                limitCatAdd($limitUserCat, $limitUserCatSelect, 'user', $limitUserCatSelect.val());
            });
            $limitUserCat.on('click', '.j-delete', function(e){
                e.preventDefault();
                var $el = $(this);
                $el.closest('tr').remove();
                makeLimitOptions($limitUserCat, $limitUserCatSelect);
            });
            <? if( ! empty($items_limits_user_category)): foreach($items_limits_user_category as $k => $v): ?>
                limitCatAdd($limitUserCat, $limitUserCatSelect, 'user', <?= $k ?>, <?= $v ?>);
            <? endforeach; endif; ?>
            makeLimitOptions($limitUserCat, $limitUserCatSelect);


            $form.on('change', '.j-items_limits_shop', function(){
                var v = intval($(this).val());
                $form.find('.j-items-limits-shop').addClass('displaynone');
                $form.find('.j-items-limits-shop-'+v).removeClass('displaynone');
            });

            var $limitShopCatSelect = $('#j-items-limits-shop-category-select');
            var $limitShopCat = $('#j-items-limits-shop-categories');
            $form.on('click', '#j-items-limits-shop-category-add', function(e){
                e.preventDefault();
                limitCatAdd($limitShopCat, $limitShopCatSelect, 'shop', $limitShopCatSelect.val());
            });
            $limitShopCat.on('click', '.j-delete', function(e){
                e.preventDefault();
                var $el = $(this);
                $el.closest('tr').remove();
                makeLimitOptions($limitShopCat, $limitShopCatSelect);
            });
            <? if( ! empty($items_limits_shop_category)): foreach($items_limits_shop_category as $k => $v): ?>
            limitCatAdd($limitShopCat, $limitShopCatSelect, 'shop', <?= $k ?>, <?= $v ?>);
            <? endforeach; endif; ?>
            makeLimitOptions($limitShopCat, $limitShopCatSelect);

        });

        function onTab(tab, link)
        {
            if (currentTab == tab)
                return;

            $form.find('.j-tab-form').hide();
            $form.find('#j-tab-' + tab).show();

            bff.onTab(link);
            currentTab = tab;
            $form.find('.j-tab-current').val(tab);

            if (bff.h) {
                window.history.pushState({}, document.title, '<?= $this->adminLink(bff::$event) ?>&tab=' + tab);
            }
        }

        function makeLimitOptions($block, $select)
        {
            var html = '';
            for(var i in limitCats){
                if( ! limitCats.hasOwnProperty(i)) continue;
                var c = i.substr(1);
                if($block.find('[data-cat="'+c+'"]').length) continue;
                html += '<option value="'+c+'">'+limitCats[i]+'</option>';
            }
            $select.html(html);
            $select.closest('tr').toggle(html.length > 0);
        }

        function limitCatAdd($block, $select, mode, cat, val)
        {
            cat = intval(cat);
            if( ! val) val = 0;
            if( ! limitCats.hasOwnProperty('s'+cat)) return;
            if($block.find('[data-cat="'+cat+'"]').length) return;
            $block.append('<tr data-cat="'+cat+'">'+
                '<td>'+limitCats['s'+cat]+':</td> '+
                '<td><input class="input-mini" type="number" min="0" max="9999" maxlength="4" name="items_limits_'+mode+'_category['+cat+']" value="'+val+'" /> / сутки</td>'+
                '<td><a class="but cross j-delete" href="#" style="margin-left: 10px;"></a></td>'+
            '</tr>');
            makeLimitOptions($block, $select);
        }

        return {
            onTab: onTab
        }
    }());
//]]>
</script>