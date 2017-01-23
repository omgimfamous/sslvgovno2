<?php

    $urlListing = $this->adminLink(bff::$event);
?>

<table class="table table-hover table-condensed table-striped admtbl tblhover">
<thead>
    <tr class="header">
        <th class="left" width="160">Отправитель</th>
        <th width="70">Кому</th>
        <th class="left">Сообщение</th>
        <th width="135">Дата</th>
    </tr>
</thead>
<tbody id="j-im-spy-lenta-list">
    <?= $list ?>
</tbody>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="filters" id="j-im-spy-lenta-pgn">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="page" value="1" class="j-page-value" />
    <div class="j-pages"><?= $pgn ?></div>
</form>

<script type="text/javascript">
//<![CDATA[
    $(function()
    {
        var ajaxUrl = '<?= $this->adminLink('ajax&act='); ?>';
        var $list = $('#j-im-spy-lenta-list');
        var $pgn = $('#j-im-spy-lenta-pgn');
        $pgn.on('click', '.j-page', function(e){ nothing(e);
            var pageID = $(this).data('page');
            $pgn.find('.j-page-value').val( pageID );
            bff.ajax('<?= $urlListing ?>', $pgn.serialize(), function(data){
                if(data){
                    $list.html(data.list);
                    $pgn.find('.j-pages').html(data.pgn);
                    if( bff.h ) {
                        window.history.pushState({}, document.title, '<?= $urlListing.'&page=' ?>' + pageID);
                    }
                }
            }, function(){
                $list.toggleClass('disabled');
            });
        });
    });
//]]>
</script>