<?php use bff\db\Dynprops;

?>
<div class="actionBar">
    <strong>Динамические свойства доступные для наследования:</strong>
</div>

<table class="table table-condensed table-hover admtbl">
    <thead>
        <tr class="header nodrop nomove">
        <?php if(FORDEV){ ?><th width="30">DF</th><?php } ?>
            <th class="left">Название<span id="progress-inherit" style="margin-left:5px; display:none;" class="progress"></span></th>
            <th width="200">Тип</th>
            <th width="170">Действие</th>
        </tr>
    </thead>
    <?php
    if( ! empty($aData['dynprops'])):

        foreach($aData['dynprops'] as $k=>$v): ?>
        <tr class="row<?= ($k%2); ?>" id="dnd-<?= $v['id']; ?>">
        <?php  if(FORDEV): ?><td>f<?= $v['data_field']; ?></td><?php endif; ?>
            <td class="left"><?= $v['title']; ?><?php if($v['is_search']): ?> <span class="desc">[поиск]</span><?php endif; ?></td>
            <td><?= Dynprops::getTypeTitle($v['type']); ?></td>
            <td>              
                <?php if($v['inherited']): ?>
                    <a class="but"></a>
                <?php else: ?>
                    <a class="but add disabled" title="наследовать" href="#" onclick="return dpAddInherit(this, <?= $v['id']; ?>, <?= $aData['owner_id']; ?>);"></a>
                <?php endif; ?>
                <a class="but edit disabled" target="_blank" href="<?= $aData['url_action']; ?>&act=edit&dynprop=<?= $v['id']; ?>&owner=<?= $v[ $this->ownerColumn ]; ?>"></a>
                <input type="button" class="btn btn-mini button submit" value="копировать" onclick="dpAddCopy(this, <?= $v['id']; ?>, <?= $aData['owner_id']; ?>);" />
            </td>
        </tr>
        <?php
        endforeach;

    else: ?>
    <tr class="norecords">
        <td colspan="<?= (FORDEV?4:3); ?>">нет доступных динамических свойств для наследования</td>
    </tr>
 <?php endif; ?>
</table>