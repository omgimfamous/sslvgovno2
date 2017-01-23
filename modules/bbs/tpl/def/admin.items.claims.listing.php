<?
    $aTabs = array(
        0 => 'Необработанные',
        1 => 'Все',
    );
?>

<div class="tabsBar" id="j-bbs-claims-status-tabs">
    <? foreach($aTabs as $k=>$v) { ?>
    <span class="tab<?= $k==$status ? ' tab-active' : '' ?>"><a href="#" class="j-tab" data-id="<?= $k ?>"><?= $v ?></a></span>
    <? } ?>
</div>

<div class="actionBar">
    <form action="" name="filter" class="form-inline" id="j-bbs-claims-filter" style="margin-left: 15px;">
        <input type="hidden" name="s" value="<?= bff::$class ?>" />
        <input type="hidden" name="ev" value="<?= bff::$event ?>" />
        <input type="hidden" name="status" value="<?= bff::$event ?>" class="j-status-id" />
        <div class="controls controls-row">
            <input type="text" name="item" placeholder="ID объявления" value="<?= ($item > 0 ? $item : '') ?>" class="input-medium" />
            <input type="submit" class="btn btn-small button submit" value="найти" />
            <label class="pull-right">по: <select name="perpage" class="j-perpage" style="width: 50px;"><?= $perpage ?></select></label>
            <div class="clearfix"></div>
        </div>
    </form>
</div>

<?= $this->viewPHP($aData, 'admin.items.claims'); ?>

<?= $pgn; ?>