
<?= tplAdmin::blockStart('Карта сайта и меню / Управление меню', true, array(), array(), array(
    array('title'=>'сбросить кеш', 'onclick'=>"return bff.redirect('".$this->adminLink('dev_reset_cache')."')", 'icon'=>'icon-refresh'),
    array('title'=>'валидация nested-sets', 'onclick'=>"return bff.redirect('".$this->adminLink('dev_treevalidate')."')", 'icon'=>'icon-indent-left'),
    array('title'=>'удалить все разделы меню', 'onclick'=>"return bff.confirm('sure', {r:'".$this->adminLink('dev_clear')."'})", 'icon'=>'icon-remove'),
)); ?>

<div class="tabsBar">
    <?php foreach($aData['menu'] as $k=>$v): ?>
        <span class="tab<?php if($v['active']){ ?> tab-active<?php } ?>"><?php if(FORDEV){ ?><a href="<?= $this->adminLink('edit&id='.$v['id']) ?>" class="edit_s disabled">&nbsp;</a><?php } ?><a href="<?= $this->adminLink(bff::$event.'&mid='.$v['id']) ?>"><?= $v['title'] ?></a></span>
    <?php endforeach; ?>
    <div class="right">
        <div class="progress" style="display:none;" id="progress-sitemap"></div>
        <a href="<?= $this->adminLink('add&mid='.$aData['mid'].($aData['mid']?'&pid='.$aData['mid']:'')) ?>">+ добавить</a>
    </div>
</div>

<table class="table table-condensed table-hover admtbl tblhover" id="sitemap-listing">
<thead>
    <tr class="header nodrag nodrop">
        <th class="left" style="padding-left:15px">Пункты меню</th>
        <?php if(FORDEV){ ?><th width="100">Keyword</th><?php } ?>
        <th width="150"></th>
        <th class="left" width="140">Действие</th>
    </tr>
</thead>

<?php foreach($aData['items'] as $k=>$v): ?>
<tr class="row<?= ($k%2) ?>" id="dnd-<?= $v['id'] ?>" data-pid="<?= $v['pid'] ?>" data-numlevel="<?= $v['numlevel'] ?>">
    <td class="left<?php if($v['menu']){ ?> bold<?php } ?>" style="padding-left:<?= ($v['numlevel']*15-10) ?>px;">
        <a href="javascript:void(0);" title="<?= HTML::escape($v['link']) ?>" <?php if($v['menu']){ ?>class="bold"<?php } ?>><?= $v['title'] ?></a>
    </td>
    <?php if(FORDEV){ ?><td><?= $v['keyword'] ?></td><?php } ?>
    <td><?php if($v['is_system'] && FORDEV){ ?><span class="desc">системный</span><?php } ?></td>
    <td class="left">
        <a class="but <?php if($v['enabled']){ ?>un<?php } ?>block" title="вкл/выкл" href="#" onclick="return sitemapToggle(<?= $v['id'] ?>, this);"></a>
        <a class="but edit" href="<?= $this->adminLink('edit&mid='.$aData['mid'].'&id='.$v['id']) ?>" title="редактировать"></a>
        <?php if(!$v['is_system'] || FORDEV){ ?><a class="but del" href="<?= $this->adminLink('delete&mid='.$aData['mid'].'&id='.$v['id']) ?>" onclick="if(!bff.confirm('sure')) return false;" title="удалить"></a><?php } else { ?><a href="#" class="but del disabled" onclick="return false;"></a><?php } ?>
        <?php if($v['menu']){ ?>
        <a class="but add" href="<?= $this->adminLink('add&mid='.$aData['mid'].'&pid='.$v['id']) ?>" title="добавить"></a>
        <?php } ?>
    </td>
</tr>
<?php endforeach;

   if(empty($aData['items'])): ?>
<tr class="norecords nodrag nodrop">
    <td colspan="<?= (FORDEV ? 4 : 3) ?>">нет пунктов меню</td>
</tr>
<?php endif; ?>
</table>

<div class="footer">
    <div class="left desc"></div>
    <div class="right desc" style="width:30px;">
        &darr; &uarr;
    </div>
    <div class="clear clearfix"></div>
</div>

<script type="text/javascript">
//<![CDATA[
$(function(){
    bff.rotateTable('#sitemap-listing', '<?= $this->adminLink('listing&act=rotate') ?>', '#progress-sitemap');
}); 

function sitemapToggle(id, link)
{
    bff.ajaxToggle(id, '<?= $this->adminLink('listing&act=toggle') ?>', {progress: '#progress-sitemap', link: link});
}
//]]>
</script>