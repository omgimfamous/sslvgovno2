<div class="tabsBar" id="j-instr-tabs">
    <?php $i = 1;
       foreach($tabs as $k=>$v) { ?>
         <span class="tab<?php if($i == 1) { ?> tab-active<?php } ?>"><a href="#" onclick="return jInstr.onTab('tab<?= $i ?>', this);"><?= $k ?></a></span>
    <?php $i++; } ?>
    <div class="progress" style="display:none;" id="j-intr-progress"></div>
</div>
<form action="" id="j-instr-form">
<div id="j-instr-tabs-content">
    <?php $i = 1;
       foreach($tabs as $tabTitle=>$tabInstr) { $tab = 'tab'.$i; ?>
    <div id="j-instr-<?= $tab ?>" class="j-instr <?php if($i != 1) { ?> hidden<?php } ?>">
        <table class="admtbl tbledit">
            <?php foreach($tabInstr as $k=>$v) { ?>
            <tr>
                <td class="row1"><span class="field-title"><?= $v['t'] ?></span>:</td>
                <td class="row2">
                    <?php  if(empty($v['field'])) $v['field'] = 'wy';
                        switch($v['field']) {
                            case 'text': {
                                ?><input type="text" name="instr[<?= $k ?>]" id="instr_text_<?= $k ?>" style="width: 595px;" value="<?= ( isset($data[$k]) ? HTML::escape($data[$k]) : '' ) ?>" /><?php
                            } break;
                            case 'wy': {
                                ?><textarea name="instr[<?= $k ?>]" id="instr_wy_<?= $k ?>" class="wy" style="height: 135px; width: 600px"><?= ( isset($data[$k]) ? $data[$k] : '' ) ?></textarea><?php
                            } break;
                        } ?>
                </td>
            </tr>
            <?php } ?>
        </table>
    </div>
    <?php $i++; } ?>
</div>
</form>
<br />
<input type="button" class="btn btn-success button submit" value="Сохранить" onclick="jInstr.update();" />

<script type="text/javascript">
var jInstr = (function(){
    var urlAjax = '<?= $this->adminLink('instructions'); ?>';
    var $tabs, $progress;
    
    $(function(){
        $tabs = $('div.j-instr');
        $progress = $('#j-instr-progress');
        $('textarea.wy').bffWysiwyg({autogrow: false});
    });
    
    return {
        onTab: function(key,link){
            $tabs.addClass('hidden');
            $tabs.filter('#j-instr-'+key).removeClass('hidden');
            bff.onTab(link);
            return false;
        },
        update: function() {
            var form = $('#j-instr-form');
            form.addClass('disabled');
            bff.ajax(urlAjax, form.serializeArray(), function(data){
                if(data){
                    bff.success('Инструкции успешно сохранены');
                }
                form.removeClass('disabled');
            }, $progress);
        }
    };
}());
</script>