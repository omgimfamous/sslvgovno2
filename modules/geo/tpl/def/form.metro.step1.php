<?php

foreach($data as $k=>$v):
?>
<a href="#" class="j-branch" data="{id:<?= $v['id'] ?>,city:<?= $city_id ?>}">
    <span class="i-formpage__metroselect__item" style="background-color: <?= $v['color'] ?>"></span>
    <span class="inlblk"><?= $v['t'] ?></span>
</a>
<?
endforeach;