<?php

?>
<table class="table table-condensed table-hover admtbl">
<thead>
    <tr class="header">
        <th width="40">#</th>
        <th width="520" class="left">Правило</th>
        <th class="left">Результат</th>
    </tr>
</thead>
<?php
    $i = 1;
    foreach(array(
        0 => 'Левый ключ ВСЕГДА меньше правого',
        1 => 'Наименьший левый ключ ВСЕГДА равен 1',
        2 => 'Наибольший правый ключ ВСЕГДА равен двойному числу узлов',
        3 => 'Разница между правым и левым ключом ВСЕГДА нечетное число',
        4 => 'Если уровень узла нечетное число то тогда левый ключ ВСЕГДА четное число, то же самое и для четных узлов',
        5 => 'Ключи ВСЕГДА уникальны, вне зависимости от того правый он или левый',
    ) as $k=>$v) {
        ?>
        <tr class="row<?= $i%2 ?>">
            <td><?= ($i++); ?></td>
            <td class="left"><?= $v ?></td>
            <?php
                if($aData[$k]=='') { ?><td class="clr-success left">OK</td><?php }
                else { ?><td class="clr-error left">ошибка: <?= $aData[$k] ?></td><?php } ?>
        </tr>
        <?php
    }
?>
</table>