<?php
    $edit = ( $id > 0 );
    tpl::includeJS(array('autocomplete'), true);
?>

<div id="j-shop-form">

    <div class="tabsBar j-shop-form-tabs">
        <? foreach($tabs as $k=>$v) { ?>
            <span class="tab<? if($k == $tab){ ?> tab-active<? } ?> st-<?= $k ?>"><a href="#" onclick="return jShop.tab('<?= $k ?>', this);"><?= $v ?></a></span>
        <? } ?>
        <div class="progress" style="margin-left: 5px; display: none;" id="j-shop-form-progress"></div>
        <? if($edit && $status != Shops::STATUS_REQUEST) { ?><div class="right"><a href="<?= $link ?>" target="_blank">Страница магазина &rarr; </a></div><? } ?>
        <div class="clear"></div>
    </div>

    <div id="j-shop-form-info" class="j-shop-forms hidden">
        <form action="" name="shopForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>" />
        <input type="hidden" name="act" value="info" />
        <table class="admtbl tbledit">
            <tr style="width:150px;">
                <td class="row1 field-title">Владелец<? if( ! Shops::categoriesEnabled()) { ?><span class="required-mark">*</span><? } ?>:</td>
                <td class="row2">
                    <? if($edit && ! empty($user)) { ?>
                        <a href="#" class="ajax<? if($user['blocked']){ ?> text-error<? } ?>" onclick="return bff.userinfo(<?= $user['user_id'] ?>);"><?= $user['email'] ?></a>
                    <? } else { ?>
                        <div<? if($edit) { ?> style="display: none;" <? } ?>>
                            <input type="hidden" name="user_id" value="<?= ( ! empty($user['user_id']) ? $user['user_id'] : 0 ) ?>" class="j-shop-user-id" />
                            <input type="text" name="" value="<?= ( ! empty($user['email']) ? HTML::escape($user['email']) : '' ) ?>" class="input-xlarge autocomplete j-shop-user-ac" placeholder="Введите e-mail пользователя" />
                        </div>
                        <? if($edit) { ?><div><i>не указан</i> - <a href="#" class="ajax desc" onclick="$(this).parent().prev().show().find('input:text').focus(); $(this).parent().remove(); return false;">изменить</a></div><? } ?>
                    <? } ?>
                </td>
            </tr>
            <?= $tab_info ?>
            <tr class="footer">
                <td colspan="2" class="row1">
                    <input type="submit" class="btn btn-success button submit j-submit-ajax" value="Сохранить" data-loading-text="Сохраняем..." />
                    <input type="button" class="btn button cancel" value="Отмена" onclick="history.back();" />
                </td>
            </tr>
        </table>
        </form>
    </div>

    <div id="j-shop-form-claims" class="j-shop-forms hidden"></div>

</div>

<script type="text/javascript">
    //<![CDATA[
    var jShop = (function(){
        var $container, $progress, id = '<?= $id ?>';

        $(function(){
            $container = $('#j-shop-form');
            $progress = $container.find('#j-shop-form-progress');
            onTab('<?= $tab ?>');
        });

        function initTab(key)
        {
            var $tab = $container.find('#j-shop-form-'+key);
            if($tab.hasClass('j-inited')) {
                $tab.show(); return;
            }

            switch(key)
            {
                case 'info':
                {
                    var $form = $tab.find('form');
                    bff.iframeSubmit($form, function(data){
                        if(data && data.success) {
                            <? if( ! $id ) { ?>
                                bff.success('Магазин успешно создан');
                                bff.redirect('<?= $this->adminLink('listing') ?>', false, 1);
                            <? } else { ?>
                                bff.success('Настройки магазина успешно сохранены');
                                if(data.reload) setTimeout(function(){ location.reload(); }, 1000);
                            <? } ?>
                        }
                    },{
                        button: $form.find('.j-submit-ajax')
                    });

                    //user
                    $form.find('.j-shop-user-ac').autocomplete('<?= $this->adminLink('ajax&act=user-autocomplete', 'shops') ?>',
                        {valueInput: $form.find('.j-shop-user-id'),onSelect: function(id, title, ex){}});
                } break;
                case 'claims':
                {
                    bff.ajax('<?= $this->adminLink('edit&act=claims') ?>', {id:id}, function(data){
                        if(data) {
                            $tab.html(data.html);
                        }
                    }, $progress);
                } break;
            }

            $tab.addClass('j-inited').show();
        }

        function onTab(key, link)
        {
            if(link === false) {
                link = $container.find('.j-shop-form-tabs .st-'+key);
            }
            $container.find('.j-shop-forms').hide();
            initTab(key);
            if(key == 'info' && jShopInfo) jShopInfo.onShow();
            $(link).parent().addClass('tab-active').siblings().removeClass('tab-active');
            return false;
        }

        return {
            tab: onTab
        };
    }());
    //]]>
</script>