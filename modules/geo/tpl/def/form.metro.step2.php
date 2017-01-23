<?php

?>
<div class="i-formpage__metroselect__popup__sublist__title">
    <a href="#" class="backto ajax j-back">&laquo; <?= _t('item-form', 'Выбрать другую ветку') ?></a>
    <p class="title">
        <span class="i-formpage__metroselect__item" style="background-color: <?= $color ?>"></span>
        <strong><?= $t ?></strong>
    </p>
</div>
<div class="i-formpage__metroselect__popup__sublist__list">
    <ul>
        <? foreach($st as $k=>$v): ?>
            <li>&ndash; <a href="#" class="j-station" data="{id:<?= $v['id'] ?>,branch:<?= $id ?>,city:<?= $city_id ?>}"><?= $v['t'] ?></a></li>
        <? endforeach; ?>
    </ul>
</div>