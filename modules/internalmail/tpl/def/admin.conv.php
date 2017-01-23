<?php
$nInterlocutorID = $i['id'];
?>

<?= tplAdmin::blockStart('Сообщения / <a href="' . $this->adminLink('listing') . '">Личные сообщения</a> / Переписка', false); ?>

<table class="admtbl tbledit">
    <tr class="row1">
        <td style="width:65px;">
            <a href="#" onclick="return bff.userinfo(<?= $nInterlocutorID ?>);">
                <img id="im_avatar" class="img-polaroid" src="<?= $i['avatar'] ?>" alt="" width="50" />
            </a>
        </td>
        <td>
            <a href="#" onclick="return bff.userinfo(<?= $nInterlocutorID ?>);" class="ajax"><?= (!empty($i['name']) ? $i['name'] : $i['login'] ) ?></a>
            <? if($shop_id) { ?><i class="icon-shopping-cart" title="Сообщение для магазина"></i><? } ?>
            <? if( ! $i['activated'] ) { ?>&nbsp;<span class="disabled">[неактивирован]</span><? } ?>
            <br />
            <span class="label"><?= tpl::declension($total, array('сообщение', 'сообщения', 'сообщений')) ?></span>
            <br />
        </td>
    </tr>
</table>

<hr class="cut" />

<form action="" method="post" onsubmit="return imAnswer();" enctype="multipart/form-data" style="margin: 5px 0 15px 0;">
    <input type="hidden" name="i" value="<?= $nInterlocutorID ?>" />
    <input type="hidden" name="shop" value="<?= $shop_id ?>" />
    <? if( ( ! $admin && ($i['im_noreply'] || $ignored)) || $i['blocked'] ) { ?>
    <div class="alert alert-error text-center">
        <? if( $i['im_noreply'] || $ignored ) { ?>
        Пользователь запретил отправлять ему сообщения.
        <? } else { ?>
        Аккаунт пользователя заблокирован (<a href="#" onclick="return bff.userinfo(<?= $nInterlocutorID ?>);">причина</a>).
        <? } ?>
    </div>
    <? } else { ?>
    <table class="admtbl">
        <tr>
            <td colspan="3">
                <span id="warn-message" class="clr-error" style="display:none;"></span>
                <textarea style="resize: vertical;" rows="5" id="message" name="message" placeholder="Текст сообщения..." onkeyup="checkTextLength(4096, this.value, $('#warn-message').get(0));"></textarea>
            </td>
        </tr>
        <tr>
            <td>
                <div class="left">
                    <input type="submit" class="btn btn-success btn-small button submit" value="Отправить сообщение" />
                </div>
                <? if( InternalMail::attachmentsEnabled() ) { ?>
                <div class="right">
                    <div class="form-upload">
                        <div class="upload-file">
                            <table>
                                <tbody class="desc">
                                    <tr><td>
                                            <div class="upload-btn">
                                                <span class="upload-mask">
                                                    <input type="file" onchange="bff.input.file(this, 'im_attach_cur');" name="attach" id="im_attach" />
                                                </span>
                                                <a class="ajax">приложить файл</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody></table>
                            <div class="upload-res" id="im_attach_cur"></div>
                        </div>
                    </div>
                </div>
                <? } ?>
                <div class="clear-all"></div>
            </td>
        </tr>
    </table>
    <? } ?>
</form>

<hr class="cut" />

<table class="admtbl tbledit im-conv-list" id="j-im-conv-list">
    <?= $list ?>
</table>

<form action="<?= $this->adminLink(null) ?>" method="get" name="filters" id="j-im-conv-pgn">
    <input type="hidden" name="s" value="<?= bff::$class ?>" />
    <input type="hidden" name="ev" value="<?= bff::$event ?>" />
    <input type="hidden" name="i" value="<?= $nInterlocutorID ?>" />
    <input type="hidden" name="shop" value="<?= $shop_id ?>" />
    <input type="hidden" name="page" value="1" class="j-page-value" />
    <div class="j-pages"><?= $pgn ?></div>
</form>

<?= tplAdmin::blockStop(); ?>

<script type="text/javascript">
//<![CDATA[
    $(function ()
    {
        var $list = $('#j-im-conv-list');
        var $pgn = $('#j-im-conv-pgn');
        $pgn.on('click', '.j-page', function (e) {
            nothing(e);
            $pgn.find('.j-page-value').val($(this).data('page'));
            bff.ajax(document.location, $pgn.serialize(), function (data) {
                if (data) {
                    $list.html(data.list);
                    $pgn.find('.j-pages').html(data.pgn);
                }
            }, function () {
                $list.toggleClass('disabled');
            });
        });
    });

    function imAnswer()
    {
        var msg = $('#message');
        if (msg.val().trim() == '') {
            msg.focus();
            return false;
        }
        return true;
    }
//]]>
</script>