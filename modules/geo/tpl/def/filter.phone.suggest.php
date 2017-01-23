<?php

# Выбор города/области(региона)

if( ! empty($list) ) {
    ?><li class="first" data="{id:0,pid:0,title:'<?= HTML::escape(_t('filter', 'Все регионы'), 'js') ?>',link:'<?= bff::urlBase() ?>'}"><?= _t('filter', 'Искать во всех регионах') ?></li><?
    foreach($list as $v) {
        ?><li data="{id:<?= $v['id'] ?>,pid:<?= $v['pid'] ?>,title:'<?= HTML::escape($v['title'], 'js') ?>',link:'<?= $v['link'] ?>'}"><?= ($highlight ? preg_replace('/^('.preg_quote($q).')/ui', '<strong>$1</strong>', $v['title']) : $v['title'] )  ?></li><?
    }
}