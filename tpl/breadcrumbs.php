<?php
/**
 * Хлебные крошки
 * @var $title_key string
 */

if (DEVICE_DESKTOP_OR_TABLET) { ?>
<ul class="l-page__breadcrumb breadcrumb hidden-phone">
    <li><a href="<?= Geo::url(Geo::filterUrl()) ?>"><i class="fa fa-home"></i></a> <span class="divider">/</span></li>
    <? foreach($crumbs as $v) {
          if($v['active']){
                if($active_is_link) { ?><li><a href="<?= $v['link'] ?>"><?= $v[$title_key] ?></a></li><? }
                else { ?><li><span class="active"><?= $v[$title_key] ?></span></li><? }
          } else {
                ?><li><a href="<?= $v['link'] ?>"><?= $v[$title_key] ?></a> <span class="divider">/</span></li><?
          }
       } ?>
</ul>
<? } ?>