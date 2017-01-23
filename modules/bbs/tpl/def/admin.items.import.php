<?php
/**
 * @var $this BBS
 */
tpl::includeJS(array('autocomplete'), true);
tplAdmin::adminPageSettings(array('icon' => false));
$aLang = $this->locale->getLanguages(false);
echo tplAdmin::blockStart('Объявления / Импорт / Экспорт', false);
?>
<input type="hidden" name="tab-current" class="j-tab-current" value="<?= $tab_form ?>">
<div class="tabsBar j-tabs">
    <? foreach($aData['tabs'] as $k => $v){ ?>
    <span class="tab j-tab<? if($k == $tab_form){ ?> tab-active<? } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="form-progress"></div>
</div>

<table class="admtbl tbledit j-mainFields">
    <tr>
        <td class="row1 field-title" width="120">Категория<span class="required-mark">*</span>:</td>
        <td class="row2">
            <? foreach($cats as $lvl => $v):
            ?><select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;"><?= $v['cats'] ?></select><?
            endforeach;
            ?>
        </td>
    </tr>
    <tr <? if (sizeof($aLang) <= 1){ ?>class="hidden"<? } ?>>
        <td class="row1">Локализация:</td>
        <td>
            <select name="language" class="j-language-select" style="width:100px;">
                <? foreach($aLang as $lngId => $lng) { ?>
                <option value="<?= $lngId ?>"><?= $lng['title'] ?></option>
                <? } ?>
            </select>
        </td>
    </tr>
</table>
<div id="j-services-tabs-content">
    <div id="j-tab-import" class="j-tab-form hidden">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cat_id" id="j-cat_id" value="" />
            <table class="admtbl tbledit" style="margin-top:5px;">
                <tr>
                    <td class="row1 field-title" width="120">Пользователь<span class="required-mark">*</span>:</td>
                    <td>
                        <input type="hidden" name="user_id" value="0" id="j-item-user-id" />
                        <input type="text" name="email" value="" id="j-item-user-email" class="autocomplete input-large" placeholder="Введите e-mail пользователя" />
                        <? if( BBS::publisher(BBS::PUBLISHER_SHOP) ) { ?>
                        <a href="javascript:void(0);" id="j-item-user-help" data-placement="right" data-toggle="tooltip" title="только пользователи с открытыми магазинами"><i class="icon-question-sign"></i></a>
                        <script type="text/javascript">$(function () {
                                if (bff.bootstrapJS()) {
                                    $('#j-item-user-help').tooltip();
                                }
                            });</script>
                        <? } else if( BBS::publisher(BBS::PUBLISHER_USER_OR_SHOP) ) { ?>
                        <div id="j-item-user-publisher" style="display: none; margin:5px;">
                            <label class="inline radio"><input type="radio" name="shop" value="0" checked="checked" />Частное лицо</label>
                            <label class="inline radio"><input type="radio" name="shop" value="1" />Магазин</label>
                        </div>
                        <? } ?>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title">Статус объявлений:</td>
                    <td style="height: 27px; padding-left: 7px;">
                        <label class="radio inline"><input type="radio" name="state" value="<?= BBS::STATUS_PUBLICATED ?>" checked="checked" />опубликованы</label>
                        <label class="radio inline"><input type="radio" name="state" value="<?= BBS::STATUS_PUBLICATED_OUT ?>" />сняты с публикации</label>
                    </td>
                </tr>
                <tr>
                    <td class="row1 field-title">Файл импорта:</td>
                    <td>
                        <div class="form-upload">
                            <div class="upload-file">
                                <table>
                                    <tbody class="desc">
                                        <tr><td>
                                                <div class="upload-btn">
                                                    <span class="upload-mask">
                                                        <input type="file" name="file" id="j-import_file" />
                                                    </span>
                                                    <a class="ajax">выбрать файл <span class="desc">(*.xml формат)</span></a>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody></table>
                                <div class="upload-res" id="j-import_file_cur"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
            <input type="submit" class="btn btn-small btn-success" id="j-import" disabled="disabled" value="Импортировать" style="margin: 10px 0 0;" />
            <input type="button" class="btn btn-small" id="j-import-getFile" disabled="disabled" onclick="jImport.doImport(true); return false;" value="Скачать шаблон файла" style="margin: 10px 0 0;" />
        </form>
    </div>
    <div id="j-tab-export" class="j-tab-form hidden">
        <form action="" method="post">
            <table class="admtbl tbledit" style="margin-top:5px;">
                <tr>
                    <td class="row1 field-title" width="120">Статус объявлений:</td>
                    <td>
                        <label class="radio inline"><input type="radio" name="state" value="0" checked="checked" />все</label>
                        <label class="radio inline"><input type="radio" name="state" value="1" />только опубликованные</label>
                    </td>
                </tr>
            </table>
            <div class="hidden alert alert-warning" id="j-warning-Info" style="margin-top:10px;"></div>
            <div class="left" style="margin: 10px 0 0;">
                <input type="button" class="btn btn-small btn-success" id="j-import-export" disabled="disabled" onclick="jImport.doExport(); return false;" value="Экспортировать" />
                <div class="help-inline desc hidden" id="j-exportDesc">Будет выгружено: <span class="j-counter"></span></div>
            </div>
            <div class="clear"></div>
        </form>
    </div>
</div>
<?= tplAdmin::blockStop(); ?>

<?= tplAdmin::blockStart('Импорт объявлений', false, array('id' => 'BbsImportsListBlock')); ?>
<div class="tabsBar" id="j-imports-tabs">
    <? foreach (array('admin' => 'Администраторы', 'user'=>'Пользователи') as $k=>$v) { ?>
            <span class="tab j-tab<? if($k == $tab_list){ ?> tab-active<? } ?>" data-tab="<?= $k ?>"><?= $v ?></span>
    <? } ?>
    <div class="progress" style="margin-left: 5px; display: none;" id="BbsImportsProgress"></div>
</div>
<div id="j-imports-tabs-content">
    <div class="actionBar">
        <form method="get" action="<?= $this->adminLink(NULL) ?>" id="BbsImportsListFilters" onsubmit="return false;" class="form-inline">
            <input type="hidden" name="s" value="<?= bff::$class ?>" />
            <input type="hidden" name="ev" value="<?= bff::$event ?>" />
            <input type="hidden" name="page" value="<?= $f['page'] ?>" />
            <input type="hidden" name="tab_list" value="<?= $tab_list ?>" />
            <label class="relative">
                <input type="hidden" name="uid" id="j-imports-user-id" value="0">
                <input type="text" name="uemail" class="autocomplete" id="j-imports-user" style="width: 160px;" placeholder="ID / E-mail пользователя" value="" autocomplete="off">
             </label>
            <input type="button" class="btn btn-small button cancel" onclick="jBbsImportsList.submitFilter();" value="фильтровать">
            <a class="ajax cancel" onclick="jBbsImportsList.submitFilter(true); return false;">сбросить</a>
            <div class="clear"></div>
        </form>
    </div>
    <table class="table table-condensed table-hover admtbl tblhover" id="BbsImportsListTable">
        <thead>
            <tr class="header nodrag nodrop">
                <th width="25">ID</th>
                <th width="125" class="left">Категория</th>
                <th width="90">Обработано</th>
                <th class="left">Комментарий</th>
                <th width="100">Статус</th>
                <th width="120">Создан</th>
                <th width="100">Действие</th>
            </tr>
        </thead>
        <tbody id="BbsImportsList">
            <?= $list ?>
        </tbody>
    </table>
    <div id="BbsImportsListPgn"><?= $pgn ?></div>
</div>
<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
    var jImport = (function () {
        var $progress, $langSelect, currentTab, $catField;
        var $templateButton = $('#j-import-getFile');
        var $exportButton = $('#j-import-export');
        var $importButton = $('#j-import');
        var catID = 0;
        var $importForm = $('#j-tab-import').find('form:eq(0)');
        var $exportForm = $('#j-tab-export').find('form:eq(0)');
        var $exportInfo = $('#j-exportDesc');
        var $warningAlert = $('#j-warning-Info');
        var $fileField = $('#j-import_file');
        var $curFileName = $('#j-import_file_cur');

        $(function(){
            $progress = $('#form-progress');
            $langSelect = $('.j-language-select');
            $catField = $('#j-cat_id');

            $('.j-tabs').on('click', '.j-tab', function () {
                onTab($(this).data('tab'), this);
            });

            $('.j-mainFields').on('change', '.cat-select', function () {
                catSelect($(this));
                buttonsState();
            });

            $langSelect.on('change', function () {
                buttonsState();
            });

            $exportForm.on('change', 'input[name=state]', function () {
                jImport.doExport(true);
            });

            $importForm.on('click', 'a.j-import-file-cancel', function () {
                $curFileName.html('');
                $fileField.val('');
                buttonsState();
            });

            $('#j-item-user-email').autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                    {valueInput: $('#j-item-user-id'), onSelect: function (id, title, ex) {
                            var $publisher = $('#j-item-user-publisher');
                            if ($publisher.length)
                                $publisher.toggle(intval(ex.data[2]) > 0);
                        }});

            $fileField.on('change', function () {
                var filename = $(this).val();
                var ext = filename.split('.').pop();
                if (ext !== 'xml') {
                    $(this).val('');
                    $curFileName.html('');
                    alert('Допустимый формат файлов импорта: xml');
                    return;
                }

                var file = this.value.split("\\");
                file = file[file.length - 1];
                if (file.length > 30)
                    file = file.substring(0, 30) + '...';
                var html = '<a href="javascript:void(0);" class="j-import-file-cancel"></a>' + file;
                $curFileName.html(html);

                buttonsState();
            });

            bff.iframeSubmit($importForm, function (data, errors) {
                if (data && data.success) {
                    bff.success('Импортирование объявлений было успешно инициировано');
                    jBbsImportsList.refreshAdminTab();
                } else if (errors) {
                    bff.error(errors);
                } else {
                    bff.error('Не удалось выполнить импорт');
                }
            });

            onTab('<?= $tab_form ?>', 0, true);
            buttonsState();
        });

        function buttonsState()
        {
            if (catID <= 0 || $langSelect.val() == '') {
                $templateButton.prop('disabled', true);
                $exportButton.prop('disabled', true);
                $importButton.prop('disabled', true);
            } else {
                $templateButton.prop('disabled', false);
                $exportButton.prop('disabled', false);
                $importButton.prop('disabled', ($fileField.val() == ''));
            }
        }

        function catSelect($select)
        {
            catID = intval($select.val());

            $select.nextAll().remove();

            if (catID <= 0 && $select.prev('.j-cat-select').length == 0) {
                $catField.val('');
                jImport.doExport(true);
                return;
            }
            else {
                if (catID <= 0)
                    catID = $select.prev('.j-cat-select').val();
                $catField.val(catID);
                jImport.doExport(true);
            }

            bff.ajax('<?= $this->adminLink('ajax&act=item-form-cat'); ?>', {cat_id: catID}, function (data) {
                if (data.subs > 0) {
                    $select.after('<select class="cat-select" autocomplete="off" style="margin: 0 5px 7px 0;">' + data.cats + '</select>');
                }
            }, $progress);
        }

        function onTab(tab, link, onload)
        {
            if (currentTab == tab)
                return;

            $('.j-tab-form').hide();
            $('#j-tab-' + tab).show();

            bff.onTab(link);
            currentTab = tab;
            $('.j-tab-current').val(tab);

            if (bff.h && onload!==true) {
                window.history.pushState({}, document.title, '<?= $this->adminLink(bff::$event) ?>&tab=' + tab);
            }
        }

        return {
            doImport: function (template) {
                if (template === true) {
                    var link = "<?= $this->adminLink('import'); ?>&act=import-template&catId=" + catID + "&langKey=" + $langSelect.val() + "&" + $importForm.serialize();
                    bff.redirect(link);
                } else {
                    $importForm.submit();
                }
            },
            doExport: function (count) {
                var link = "<?= $this->adminLink('import&act=export&catId='); ?>" + catID + "&langKey=" + $langSelect.val() + "&" + $exportForm.serialize();
                if (count) {
                    $exportInfo.addClass('hidden').find('.j-counter').html('');
                    $warningAlert.hide();
                    if (catID > 0) {
                        link += '&count=true';
                        bff.ajax(link, {}, function (data) {
                            if (data.warning) {
                                $warningAlert.html(data.warning).show();
                            }
                            if (data.count) {
                                $exportInfo.removeClass('hidden').find('.j-counter').html(data.count);
                            }
                        }, $progress);
                    }
                } else {
                    bff.redirect(link);
                }
            },
            onTab: onTab
        }
    }());

    var jBbsImportsList = (function()
    {
        var $progress, $tabs, $block, $list, $listTable, $listPgn, filters, currentTab, $userId, $userEmail, processing = false;
        var ajaxUrl = '<?= $this->adminLink(bff::$event . '&act='); ?>';

        $(function () {
            $progress = $('#BbsImportsProgress');
            $block = $('#BbsImportsListBlock');
            $tabs = $('#j-imports-tabs');
            $list = $block.find('#BbsImportsList');
            $listTable = $block.find('#BbsImportsListTable');
            $listPgn = $block.find('#BbsImportsListPgn');
            filters = $block.find('#BbsImportsListFilters').get(0);
            $userId = $block.find('#j-imports-user-id');
            $userEmail = $block.find('#j-imports-user');

            $list.on('click', 'a.item-del', function () {
                var id = intval($(this).attr('rel'));
                if (id > 0 && bff.confirm('sure'))
                    del(id, this);
                return false;
            });

            $tabs.on('click', '.j-tab', function () {
                onTab($(this).data('tab'), this);
            });

            $userEmail.autocomplete('<?= $this->adminLink('ajax&act=item-user') ?>',
                {valueInput: $userId, minChars: 1}
            );
        });

        function isProcessing()
        {
            return processing;
        }

        function setProcessing(p) {
            processing = p;
        }

        function updateList(updateUrl)
        {
            if (isProcessing())
                return;
            var f = $(filters).serialize();

            bff.ajax(ajaxUrl, f, function (data) {
                if (data) {
                    $list.html(data.list);
                    $listPgn.html(data.pgn);
                    if (updateUrl !== false && bff.h) {
                        window.history.pushState({}, document.title, $(filters).attr('action') + '?' + f);
                    }
                }
            }, function(p) {
                $progress.toggle();
                setProcessing(p);
                $list.toggleClass('disabled');
            });
        }

        function setPage(id)
        {
            filters.page.value = intval(id);
        }

        function del(id, link)
        {
            var f = [];
            bff.ajax(ajaxUrl + 'import-cancel&id=' + id, f, function (data) {
                if (data && data.success) {
                    setProcessing(false);
                    updateList();
                }
                return false;
            });
        }

        function onTab(tab, link)
        {
            if (currentTab == tab)
                return;

            filters.tab_list.value = tab;
            updateList();

            bff.onTab(link);
            currentTab = tab;
        }

        return {
            page: function (id)
            {
                if (isProcessing())
                    return false;
                setPage(id);
                updateList();
            },
            refreshAdminTab: function()
            {
                setPage(0);
                onTab('admin', $tabs.find('.j-tab[data-tab="admin"]'));
                updateList(false);
            },
            importInfo: function (itemID)
            {
                if (itemID) {
                    $.fancybox('', {ajax: true, href: '<?= $this->adminLink('ajax&act=import-info&id=') ?>' + itemID});
                }
                return false;
            },
            submitFilter: function (reset)
            {
                if(reset === true) {
                    $userEmail.val('');
                    $userId.val('');
                }
                setPage(0);
                updateList();
            },
            del: del
        };
    }());
</script>