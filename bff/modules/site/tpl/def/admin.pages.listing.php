<?php
    $urlEdit = $this->adminLink('pagesEdit&id=');
    $urlDelete = $this->adminLink('pagesListing&act=delete&id=');
?>
<?= tplAdmin::blockStart('Страницы / Список страниц', true, array(), array(
    'title'=>'+ добавить страницу', 'href'=>$this->adminLink('pagesAdd')
)) ?>
<table class="table table-condensed table-hover admtbl tblhover">
<thead>
    <tr class="header">
        <?php if(FORDEV) { ?><th width="60">ID</th><?php } ?>
        <th class="left">Заголовок</th>
        <th width="250">Имя файла</th>
        <th width="85">Действие</th>
    </tr>
</thead>
<?php foreach($pages as $k=>$v) { ?>
<tr class="row<?= $k%2 ?>">
    <?php if(FORDEV) { ?><td><?= $v['id'] ?></td><?php } ?>
    <td class="left"><?= $v['title'] ?></td>
    <td><a href="<?= SITEURL.'/'.$v['filename'].Site::$pagesExtension ?>" target="_blank"><?= $v['filename'].Site::$pagesExtension ?></a></td>
    <td>
        <a class="but edit" href="<?= $urlEdit.$v['id'] ?>"></a>
        <?php if( ! $v['issystem'] || FORDEV ) { ?><a class="but del" href="#" onclick="bff.confirm('sure', {r: '<?= $urlDelete.$v['id'] ?>'}); return false;"></a><?php } else { ?><a class="but" href="#" onclick="return false;"></a><?php } ?>
    </td>
</tr>
<?php } if(empty($pages)){ ?>
<tr class="norecords">
    <td colspan="<?= (FORDEV?4:3) ?>">нет страниц</td>
</tr>
<?php } ?>
</table>