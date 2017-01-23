<?php
    tpl::includeJS(array('autocomplete.fb'), true);
    $inputID = bff::$class.'-tags-select';
?>
<div class="relative">
    <input type="hidden" value="<?php $tags_ksort = $tags; ksort($tags_ksort); echo join(',',array_keys($tags_ksort)); ?>" name="tags_current" />
    <select id="<?= $inputID ?>" name="tags" class="hidden">
        <?php foreach($tags as $v) {?><option value="<?= $v['id'] ?>" class="selected"><?= $v['tag'] ?></option><?php } ?>
    </select>
    <script type="text/javascript">
    $(function(){
        $("#<?= $inputID ?>").fcbkcomplete({
            json_url: '<?= $suggestUrl ?>',
            width: '<?= $inputWidth ?>', maxitems: 30, input_min_size: 1, addontab: true,
            complete_text: '<?= _t('tags', 'Введите название тега...') ?>'
        });
    });
    </script>
</div>