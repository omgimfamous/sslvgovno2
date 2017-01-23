<?php
/**
 * Кабинет пользователя: Сообщения - список контактов
 * @var $this InternalMail
 */

$lng_fav = _t('internalmail', 'Избранные');
$lng_ignore = _t('internalmail', 'Игнорирую');
$conversation_url_user = InternalMail::url('my.chat', array('user'=>''));
$conversation_url_shop = InternalMail::url('my.chat', array('shop'=>''));

$inFolder = function(&$userFolders, $folderID){
    return ( ! empty($userFolders) && in_array($folderID, $userFolders) );
};

foreach($list as &$v)
{
    $message = &$v['message'];
    $my_shop = ($v['shop_id'] && $v['shop_id'] == $my_shop_id);
    if ($v['shop_id'] && ! $my_shop)
    {
        $c_url  = $conversation_url_shop . $v['shop']['keyword'].'-'.$v['shop']['id'];
        $c_name = $v['shop']['title'];
        $c_logo = $v['shop']['logo'];
    } else {
        $c_url  = $conversation_url_user . $v['login'].($my_shop ? '&shop=1' : '');
        $c_name = ( ! empty($v['name']) ? $v['name'] : $v['login'] );
        $c_logo = $v['avatar'];
    }
    ?>
    <div class="u-mail__list__item l-table-row<? if($v['msgs_new']>0){ ?> active<? } ?>">
        <div class="u-mail__list__item__avatar l-table-cell hidden-phone">
            <a href="<?= $c_url ?>" class="v-author__avatar">
                <img src="<?= $c_logo ?>" class="img-circle" alt="" />
            </a>
        </div>
        <div class="u-mail__list__item__content l-table-cell j-contact" data-contact="<?= $c_url ?>">
            <div class="u-mail__list__item__title pull-left">
                <a href="<?= $c_url ?>"><?= $c_name ?></a>
                <? if($v['msgs_new']>0){ ?> <span class="label label-success">+<?= $v['msgs_new'] ?></span><? } else { ?><span class="label label-all"><?= $v['msgs_total'] ?></span><? } ?>
                <? if($v['shop_id'] && $my_shop){ ?><i class="fa fa-shopping-cart"></i><? } ?>
            </div>
            <div class="u-mail__list__item__actions pull-right">
                <? if( InternalMail::foldersEnabled() ) { ?>
                    <a title="<?= $lng_fav ?>" data-user-id="<?= $v['user_id'] ?>" data-shop-id="<?= $v['shop_id'] ?>" data-folder-id="<?= InternalMail::FOLDER_FAVORITE ?>" class="item_action item-favorite j-f-action<? if( $inFolder($v['folders'], InternalMail::FOLDER_FAVORITE) ) { ?> active<? } ?>" href="#"><span><i class="fa fa-star white"></i></span></a>
                    <a title="<?= $lng_ignore ?>" data-user-id="<?= $v['user_id'] ?>" data-shop-id="<?= $v['shop_id'] ?>" data-folder-id="<?= InternalMail::FOLDER_IGNORE ?>" class="item_action item-ban j-f-action<? if( $inFolder($v['folders'], InternalMail::FOLDER_IGNORE) ) { ?> active<? } ?>" href="#"><span><i class="fa fa-ban white"></i></span></a>
                <? } ?>
            </div>
            <div class="clearfix"></div>
            <div class="u-mail__list__item__ad">
                <? if($message['item_id'] > 0 && ! empty($v['item']) ) { ?><div class="u-mail__list__item__title"><b><?= $v['item']['title'] ?></b><? if($message['item_id'] > 0) { ?><span class="visible-phone"><?= tpl::date_format3($message['created']) ?></span><? } ?></div><? } ?>
                <p class="u-mail__list__item__text<? if($message['item_id'] > 0) { ?> hidden-phone<? } ?>">
                    <?= tpl::truncate( strip_tags($message['message']), 200 ) ?>
                    <br>
                    <?= tpl::date_format3($message['created']) ?>
                </p>
            </div>
        </div>
    </div>
<? }

if( empty($list) ) {
    echo $this->showInlineMessage(_t('internalmail', 'Список сообщений пустой'));
} else {
    unset($v, $message);
}