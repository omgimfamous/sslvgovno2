<?php
?>
<ul class="f-navigation__country_change__links">
    <? foreach($countries as $v): ?>
    <li><a href="<?= $v['link'] ?>" data="{id:<?= $v['id'] ?>,pid:0,key:'<?= $v['keyword'] ?>'}"><?= $v['title'] ?></a></li>
    <? endforeach; ?>
</ul>

