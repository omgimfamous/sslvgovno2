<?php
    tplAdmin::adminPageSettings(array('icon'=>false));
?>

<form method="post" action="" id="j-blog-settings-form" enctype="multipart/form-data">
<input type="hidden" name="save" value="1" />
<input type="hidden" name="tab" value="<?= HTML::escape($tab) ?>" class="j-tab-current" />

<div class="tabsBar j-tabs">
    <? foreach($aData['tabs'] as $k=>$v){ ?>
        <span class="tab j-tab<? if($k==$tab){ ?> tab-active<? } ?>" data-tab="<?= HTML::escape($k) ?>"><?= $v['t'] ?></span>
    <? } ?>
</div>
<div style="margin:10px 0 10px 10px;">

<!-- share -->
<div id="j-tab-share" class="j-tab-form">
    <table class="admtbl tbledit">
        <tr>
            <td class="row1">
                <div style="margin-bottom: 10px;">Код для страницы "Просмотр поста":</div>
                <textarea rows="12" name="share_code"><?= (!empty($share_code) ? HTML::escape($share_code) : '') ?></textarea>
            </td>
        </tr>
    </table>
</div>

<hr class="cut" />
<div class="footer">
    <input type="submit" class="btn btn-success button submit" value="Сохранить настройки" />
</div>

</div>
</form>

<script type="text/javascript">
//<![CDATA[
var jShopSettings = (function(){
    var $form, currentTab = 'share';

    $(function(){
        $form = $('#j-blog-settings-form');
        $form.find('.j-tabs').on('click', '.j-tab', function(){
            onTab($(this).data('tab'), this);
        });
        onTab('<?= $tab ?>', 0);
    });

    function onTab(tab, link)
    {
        if(currentTab == tab)
            return;

        $form.find('.j-tab-form').hide();
        $form.find('#j-tab-'+tab).show();

        bff.onTab(link);
        currentTab = tab;
        $form.find('.j-tab-current').val(tab);

        if(bff.h) {
            window.history.pushState({}, document.title, '<?= $this->adminLink(bff::$event) ?>&tab='+tab);
        }
    }

    return {
        onTab: onTab
    }
}());
//]]>
</script>