<div class="row-fluid">
    <div class="l-page span12">
        <?= tpl::getBreadcrumbs(array(
            array('title'=>$title,'active'=>true)
        )); ?>
        <h1><?= $title ?></h1>
        <div class="txt-content">
            <?= $content ?>
        </div>
    </div>
</div>