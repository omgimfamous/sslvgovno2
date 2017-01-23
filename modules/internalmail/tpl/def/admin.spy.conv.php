<?php

?>

<?= tplAdmin::blockStart('Сообщения / <a href="'.$list_url.'">Cообщения пользователя</a> / Переписка', true); ?>

<table class="admtbl tbledit">
<tr class="row1">
    <td style="width:65px;">
        <a href="#" onclick="return bff.userinfo(<?= $u['id'] ?>);">
            <img id="im_avatar" class="img-polaroid" src="<?= $u['avatar'] ?>" alt="" width="50" />
        </a>
    </td>
    <td style="padding-bottom: 20px;">
        <a href="#" onclick="return bff.userinfo(<?= $u['id'] ?>);" class="ajax"><?= ( !empty($u['name']) ? $u['name'] : $u['login'] ) ?></a>
        <? if( ! $u['activated'] ) { ?>&nbsp;<span class="disabled">[неактивирован]</span><? } ?>
    </td>
    <td class="right">
        <a href="#" onclick="return bff.userinfo(<?= $i['id'] ?>);" class="ajax"><?= ( !empty($i['name']) ? $i['name'] : $i['login'] ) ?></a>
        <? if( ! $i['activated'] ) { ?>&nbsp;<span class="disabled">[неактивирован]</span><? } ?>
        <br />
        <span class="label"><?= tpl::declension($total, array('сообщение','сообщения','сообщений')) ?></span>
        <br />
    </td>
    <td style="width:65px;" class="right">
        <a href="#" onclick="return bff.userinfo(<?= $i['id'] ?>);">
            <img id="im_avatar" class="img-polaroid" src="<?= $i['avatar'] ?>" alt="" width="50" />
        </a>
    </td>
</tr>
</table>

<hr class="cut" />

<table class="admtbl tbledit im-conv-list" id="j-im-conv-list">
    <?= $list ?>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="filters" id="j-im-conv-pgn">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="u" value="<?= $u['id'] ?>" />
    <input type="hidden" name="i" value="<?= $i['id'] ?>" />
    <input type="hidden" name="page" value="1" class="j-page-value" />
    <div class="j-pages"><?= $pgn ?></div>
</form>

<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
//<![CDATA[
    $(function()
    {
        var $list = $('#j-im-conv-list');
        var $pgn = $('#j-im-conv-pgn');
        $pgn.on('click', '.j-page', function(e){ nothing(e);
            $pgn.find('.j-page-value').val( $(this).data('page') );
            bff.ajax(document.location, $pgn.serialize(), function(data){
                if(data) {
                    $list.html(data.list);
                    $pgn.find('.j-pages').html(data.pgn);
                }
            }, function(){
                $list.toggleClass('disabled');
            });
        });
    });
//]]>
</script>