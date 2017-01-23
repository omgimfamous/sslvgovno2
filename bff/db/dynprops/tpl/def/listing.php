<?php use bff\db\Dynprops;
    $bAllowCopyAction = ( ! empty($aData['dynprops']));
?>
<?= tplAdmin::blockStart('Динамические свойства'); ?>
<div class="actionBar">
    <ul class="breadcrumb">
        <?php if($aData['owner_parent']!=0): ?>
        <li class="inline"><a href="<?= $aData['url_listing']; ?>&owner=<?= $aData['owner_parent']['id']; ?>"><?= $aData['owner_parent']['title']; ?></a><span class="divider">&rarr;</span></li>
        <?php endif; ?>
        <li class="active inline"><?= $aData['owner_title']; ?></li>
    </ul>
</div>
<form action="" method="post" id="dynprop_chk_form" class="form-inline">
<table class="table table-condensed table-hover admtbl tblhover" id="dynprop_listing">
    <thead>
        <tr class="header nodrag nodrop">
        <?php if($bAllowCopyAction){ ?><th width="25"><label class="checkbox inline"><input type="checkbox" class="chk-all" /></label></th><?php } ?>
        <?php if(FORDEV){ ?><th width="35">DF</th><?php } ?>
            <th class="left">Название</th>
            <?php if($this->inherit): ?><th width="100">Наследование</th><?php endif; ?>
            <th width="60">Поиск</th>
            <?php if($this->cacheKey): ?><th width="65">Кеш ключ</th><?php endif; ?>
            <th width="210">Тип</th>
            <th width="80">Действие</th>
        </tr>
    </thead>
    <?php
    $cols = 4; if(FORDEV) $cols+=1; if($this->inherit) $cols++; if($this->cacheKey) $cols++; if($bAllowCopyAction) $cols++;

    if( ! empty($aData['dynprops']))
    {   $i=1;
        foreach($aData['dynprops'] as $v) { $inherited = $v['inherited']; $ID = $v['id']; ?>
        <tr class="row<?= ($i++%2); ?><?php if($this->inherit===1 && $inherited): ?> nodrag nodrop<?php endif; ?>" id="dnd-<?= $ID; ?>">
        <?php if($bAllowCopyAction): ?><td><label class="checkbox inline"><input type="checkbox" name="dynprop[<?= $ID ?>]" class="chk" /></label></td><?php endif; ?>
        <?php if(FORDEV): ?><td class="small">f<?= $v['data_field']; ?></td><?php endif; ?>
            <td class="left"><?= $v['title'] ?><?= ($v['req'] ? '<span class="clr-error">*</span> ':'') ?><?php if($v['parent']): ?><a class="but chain" style="margin-left:5px;"></a><?php endif; ?></td>
            <?php if($this->inherit): ?><td><?= (!$inherited?'нет':'да'); ?></td><?php endif; ?>
            <td><?php if( ! $this->isStoringText($v['type'])){ ?><a class="but <?= (!$v['is_search']?'un':'') ?>checked check dp-toggle" title="Поиск" href="#" data-type="is_search" data-toggle-type="check" data-id="<?= $ID ?>"></a><?php } ?></td>
            <?php if($this->cacheKey) { ?><td class="desc"><?= $v['cache_key'] ?></td><?php } ?>
            <td><span class="dp<?= $v['type'] ?> small"><?= Dynprops::getTypeTitle($v['type']); ?></span></td>
            <td>
                <a class="but edit<?php if($inherited): ?> disabled<?php endif; ?>" title="редактировать" href="<?= $aData['url_action_owner']; ?>edit&dynprop=<?= $ID; ?><?php if($inherited): ?>&owner=<?= $v[ $this->ownerColumn ]; ?>&owner_from=<?= $aData['owner_id']; ?><?php endif; ?>"></a>
                <?php if( !$inherited || $this->isInheritParticular() ): ?>
                <a class="but del<?php if($inherited): ?> disabled<?php endif; ?>" title="удалить" href="<?= $aData['url_action_owner']; ?>del&dynprop=<?= $ID; ?><?php if($inherited): ?>&inherit=1<?php endif; ?>" onclick="if(!confirm('Удалить поле безвозвратно?')) return false;"></a>
                <?php else: ?>
                <a class="but"></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php
        }
    } else {
    ?>
    <tr class="norecords">
        <td colspan="<?= $cols; ?>">нет динамических свойств</td>
    </tr>
    <?php } ?>
    <tfoot>
        <tr class="footer nodrag nodrop">
            <td colspan="<?= $cols ?>">
                <div class="left">
                    <a title="добавить новое" href="<?= $aData['url_action_owner']; ?>add" class="but add"></a>
                    <?php if($aData['owner_parent']!=0 && $this->isInheritParticular()): ?>
                        <a title="наследовать" href="#" onclick="return dpShowInherits(this);" class="but add disabled"></a>
                    <?php endif; ?>
                </div>
                <div class="left" id="dynprop_chk_submit_block" style="display: none; margin-top: -3px; margin-left: 5px;">
                    <label class="inline">Скопировать отмеченные в: <select name="owner_to" style="width: 250px;" id="dynprop_chk_cat"></select> <input type="button" class="btn btn-mini button submit" id="dynprop_chk_submit" value="Скопировать" /></label>
                </div>
                <div class="right desc" style="width:80px; text-align:right;">
                    <span id="progress" style="margin-left:5px; display:none;" class="progress"></span>
                    &nbsp;&nbsp; &darr; &uarr;
                </div>
                <div class="clear clearfix"></div>
            </td>
        </tr>
    </tfoot>
</table>
</form>

<?php if( ! empty($aData['dynprops'])): ?>
<script type="text/javascript">
    <?php js::start(); ?>
    $(function(){
        var $list = $('#dynprop_listing');
        bff.rotateTable($list, '<?= $aData['url_action_owner']; ?>rotate', '#progress');
        $list.delegate('a.dp-toggle', 'click', function(){
            var id = intval($(this).data('id'));
            if(id > 0) {
                bff.ajaxToggle(id, '<?= $aData['url_action_owner']; ?>toggle&type='+$(this).data('type')+'&dynprop='+id,
                    {progress: '#progress', link: this});
            }
            return false;
        });
    });
    <?php js::stop(); ?>
</script>
<?php endif; ?>
<?= tplAdmin::blockStop(); ?>

<?php if( $this->isInheritParticular() ): ?>

<?= tplAdmin::blockStart('Наследование свойств', true, array('class'=>'hidden','id'=>'inherit_block')); ?>
    <div id="inherit_listing"></div>
<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
<?php js::start(); ?>
    function dpAddInherit(link, dynpropID, ownerID)
    {
        bff.ajax('<?= $aData['url_action']; ?>&act=inherit&dynprop='+dynpropID+'&owner='+ownerID, {},
            function(){
                location.reload();
            }, '#progress-inherit');
        return false;    
    }

    function dpAddCopy(btn, dynpropID, ownerID)
    {
        btn.disabled = true;
        btn.value = 'подождите...'; 
        bff.ajax('<?= $aData['url_action']; ?>&act=inherit-copy&dynprop='+dynpropID+'&owner='+ownerID, {}, function(){
            location.reload();
        }, '#progress-inherit');
        return false;
    }
    
    function dpShowInherits(link)
    {
        $(link).fadeOut();
        bff.ajax('<?= $aData['url_action']; ?>&act=inherit-list&owner=<?= $aData['owner_id']; ?>', {},
            function(data) {
                $('#inherit_listing').html(data);
                $('#inherit_block').removeClass('hidden');
            }, '#progress');
        return false;
    }
<?php js::stop(); ?>
</script>
<?php endif; ?>

<?php if( $bAllowCopyAction ): ?>
<script type="text/javascript">
<?php js::start(); ?>
    $(function(){

        // copy checked
        var $chkForm = $('#dynprop_chk_form');
        var $chk = $chkForm.find('.chk');
        var $chkAll = $chkForm.find('.chk-all');
        var $chkTo = $('#dynprop_chk_cat'), chkToReady = false;
        var $chkSubmitBlock = $('#dynprop_chk_submit_block');
        var $chkSubmitBtn = $('#dynprop_chk_submit');

        $chkSubmitBtn.click(function(){
            var $chk = $chkForm.find('.chk:checked:not(.chk-all)');
            if( ! $chk.length) {
                bff.error('Отметьте необходимые для копирования свойства');
                return;
            }
            if( intval($chkTo.val()) === 0 ) {
                bff.error('Укажите куда необходимо выполнить копирование');
                return;
            }

            bff.ajax('<?= $aData['url_action']; ?>&act=copy_to', $chkForm.serialize(), function(data){
                if(data && data.success) {
                    bff.success('Отмеченные свойства('+data.copied+'), были успешно скопированы');
                }
            });

        });

        function showChkSubmitBlock(show)
        {
            if(show) {
                if( chkToReady === false ) {
                    chkToReady = true;
                    bff.ajax('<?= $aData['url_action']; ?>&act=owners_options', {}, function(data){
                        if(data && data.success) {
                            $chkTo.html(data.opts);
                        }
                        $chkSubmitBlock.show();
                    });
                } else {
                    $chkSubmitBlock.show();
                }
            } else {
                $chkSubmitBlock.hide();
            }
        }

        $chk.click(function(){
            $chkAll.prop('checked', true);
            showChkSubmitBlock(true);
            var notChecked = $chk.filter(':not(:checked)').length;
            if( notChecked === 0 ) { // all checked
                $chkAll.removeClass('disabled');
            } else if(notChecked === $chk.length) { // all unchecked
                $chkAll.prop('checked', false).removeClass('disabled');
                showChkSubmitBlock(false);
            } else { // some checked
                $chkAll.addClass('disabled');
            }
        });

        $chkAll.click(function(){
            var check = $(this);
            showChkSubmitBlock(true);
            if( ! check.is(':checked') ) {
                $chk.prop('checked', false);
                showChkSubmitBlock(false);
            } else {
                $chk.prop('checked', true);
            }
            $chkAll.removeClass('disabled');
        });

    });
<?php js::stop(); ?>
</script>
<?php endif; ?>