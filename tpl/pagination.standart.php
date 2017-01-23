<?php

# данные отсутствуют (общее их кол-во == 0)
if( $total <= 1 || ! $settings['pages'] ) return;

?>
<!-- START pagination.standart -->
<? if( $settings['arrows'] && ($prev || $next) ) { ?>
<ul class="pager pull-left">
    <li><a<? if($prev){ echo $prev; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>>&larr; <?= _t('', 'Предыдущая') ?></a></li>
    <li><a<? if($next){ echo $next; } else { ?> href="javascript:void(0);" class="disabled grey"<? } ?>><?= _t('', 'Следующая') ?> &rarr;</a></li>
</ul>
<? } ?>
<? if( $settings['pageto'] ) { ?>
<div class="pageto pull-right hidden-phone">
    <form onsubmit="return false;" class="form-inline grey f11">
        <?= _t('', 'Перейти на страницу') ?>
        <input type="text" class="j-pgn-goto" placeholder="№" />
    </form>
</div>
<? } ?>
<div class="clearfix"></div>
<div class="pagination j-pgn-pages">
    <ul>
        <? if($first) { ?><li><a<?= $first['attr'] ?>><?= $first['page'] ?></a></li><li><a<?= $first['dots'] ?>>...</a></li><? } ?>
        <? foreach($pages as $v) { ?>
            <li<? if($v['active']){ ?> class="active"<? } ?>><a<?= $v['attr'] ?>><?= $v['page'] ?></a></li>
        <? } ?>
        <? if($last) { ?><li><a<?= $last['dots'] ?>>...</a></li><li><a<?= $last['attr'] ?>><?= $last['page'] ?></a></li><? } ?>
    </ul>
</div>
<!-- END pagination.standart -->