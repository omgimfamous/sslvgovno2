<?php
    /**
     * @var $this BBS
     */
?>
<?= tplAdmin::blockStart('Объявления / Категории / Пакетные настройки', 'icon-th-list'); ?>
<form action="" method="post" id="j-categories-packetActions-form">
    <table class="admtbl tbledit">
        <tr>
            <td colspan="2">
                <div class="well well-small">
                    Отметьте одну или несколько из доступных настроек.<br/>
                    При сохранении выбранные настройки будут изменены во <strong>всех категориях</strong> объявлений.
                </div>
                <br />
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1" style="width:200px;">
                <label class="checkbox"><input type="checkbox" name="actions[currency_default]" class="j-action-toggler" />Валюта по-умолчанию:</label>
            </td>
            <td class="row2">
                <select name="currency_default" style="width:70px;">
                    <?= Site::currencyOptions( Site::currencyDefault('id') ); ?>
                </select>
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1">
                <label class="checkbox"><input type="checkbox" name="actions[photos_max]" class="j-action-toggler" />Фотографии:</label>
            </td>
            <td class="row2">
                <label><input class="input-mini" type="number" min="<?= BBS::CATS_PHOTOS_MIN ?>" max="<?= BBS::CATS_PHOTOS_MAX ?>" maxlength="2" name="photos_max" value="<?= BBS::CATS_PHOTOS_MIN ?>" /><span class="help-inline"> &mdash; максимально доступное кол-во фотографий в объявлении (<?= BBS::CATS_PHOTOS_MIN ?> - <?= BBS::CATS_PHOTOS_MAX ?>)</span></label>
            </td>
        </tr>
        <tr class="j-action">
            <td class="row1">
                <label class="checkbox"><input type="checkbox" name="actions[list_type]" class="j-action-toggler" />Вид списка по-умолчанию:</label>
            </td>
            <td class="row2">
                <select name="list_type" style="width:100px;">
                    <option value="<?= BBS::LIST_TYPE_LIST ?>">Список</option>
                    <option value="<?= BBS::LIST_TYPE_GALLERY ?>">Галлерея</option>
                    <option value="<?= BBS::LIST_TYPE_MAP ?>">Карта</option>
                </select>
            </td>
        </tr>
        <tr class="footer">
            <td colspan="2">
                <hr />
                <input type="button" class="btn btn-success button submit j-submit" value="Сохранить" />
                <a href="<?= $this->adminLink('categories_listing'); ?>" class="btn">Отмена</a>
            </td>
        </tr>
    </table>
</form>
<script type="text/javascript">
<?php js::start(); ?>
    $(function(){
        var $form = $('#j-categories-packetActions-form');

        $form.on('click', '.j-submit', function(){
            if ( ! $form.find('.j-action-toggler:checked').length) {
                bff.error('Отметьте как минимум одну из доступных настроек');
                return;
            }
            if ( ! bff.confirm('sure') ) return;
            var $btn = $(this), btnTitle = $btn.prop('disabled',true).val();
            bff.ajax('<?= $this->adminLink(bff::$event) ?>', $form.serialize(), function(data, errors){
                if(data && data.success) {
                    bff.success('Обновление прошло успешно, затронуто категорий: <strong>'+data.updated+'</strong>');
                } else {
                    bff.error(errors);
                }
            }, function(p){
                $btn.val( p ? 'Подождите...' : btnTitle ).prop('disabled', p);
            });
        });
    });
<?php js::stop(); ?>
</script>