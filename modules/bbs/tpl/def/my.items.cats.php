<?php

foreach($aData as $v) {
    if( empty($v['sub']) ) {
        ?><li><a href="#" data-value="<?= $v['id'] ?>" class="j-cat-option"><?= $v['title'] ?></a></li><?
    } else {
        ?><li class="nav-header"><?= $v['title'] ?></li><?
        foreach($v['sub'] as $vv) {
            ?><li><a href="#" data-value="<?= $vv['id'] ?>" class="j-cat-option"><?= $vv['title'] ?></a></li><?
        }
    }
}