<?php

$i = 0;
foreach($cats as $k=>$v): ?>
   <div class="span3" style="height: 270px;margin: 5px;">
        <a href="<?= $v['l'] ?>" class="root-category main-category main-category_1"><img class="i-category i-category_1" src="<?= $v['i'] ?>" alt="" />
     <!--   <span class="index__catlist__item__count">(<?= $v['items'] ?>)</span> -->
        <span class="root-category-title">
            <?= $v['t'] ?></span></a>
            <div class="separator"></div>
        <? if($v['subn']): ?>
                <? $j = 0; foreach($v['sub'] as $vv) { ?><a href="<?= $vv['l'] ?>"><?= $vv['t'] ?></a><? } ?>
        <? endif; ?>
   </div>
   <? endforeach; ?>
   <div class="switcher">
    <span class="label label-success pseudo-link for-close">Показать все категории</span>
    <span class="label label-success pseudo-link for-open">Скрыть полный список</span>
   </div>