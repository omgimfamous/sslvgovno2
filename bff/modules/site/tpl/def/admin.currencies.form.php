<?= $this->locale->buildForm($aData, 'currencies-item',
'<tr class="required">
    <td class="row1 field-title" width="130">Название:</td>
    <td class="row2">
        <input type="text" name="title[<?= $key ?>]" value="<?= HTML::escape($aData[\'title\'][$key]); ?>" class="stretch lang-field" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Название, краткое:</td>
    <td class="row2">
        <input type="text" name="title_short[<?= $key ?>]" value="<?= HTML::escape($aData[\'title_short\'][$key]); ?>" class="stretch lang-field" />
    </td>
</tr>
<tr>
    <td class="row1 field-title">Название, с подбором:</td>
    <td class="row2">
        <input type="text" name="title_decl[<?= $key ?>]" value="<?= HTML::escape($aData[\'title_decl\'][$key]); ?>" class="stretch lang-field" />
    </td>
</tr>'); ?>
<tr class="required">
    <td class="row1"><span class="field-title">Keyword:</span></td>
    <td class="row2">
        <input type="hidden" name="curr_id" value="<?= $id ?>" />
        <input class="stretch" type="text" name="keyword" value="<?= HTML::escape($aData['keyword']) ?>" />
    </td>
</tr>
<tr class="required">
    <td class="row1"><span class="field-title">Курс, <?= Site::currencyDefault() ?>:</span></td>
    <td class="row2">
        <input class="stretch" type="text" name="rate" value="<?= $rate ?>" />
    </td>
</tr>
<tr>
    <td class="row1"><span class="field-title">Включен:</span></td>
    <td class="row2">
        <input type="checkbox" name="enabled" <?php if($enabled){ ?>checked="checked"<?php } ?> />
    </td>
</tr>
