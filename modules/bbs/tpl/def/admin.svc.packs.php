<?php

    tpl::includeJS(array('wysiwyg','ui.sortable'), true);
    $aData = HTML::escape($aData, 'html', array('title','keyword'));
    $saveUrl = $this->adminLink('svc_packs&act=update');
    $nActiveTab = $this->input->get('tab',TYPE_UINT);

    tplAdmin::adminPageSettings(array('link'=>array('title'=>'+ добавить пакет', 'href'=>$this->adminLink('svc_packs_create')), 'icon'=>false));
?>

<div class="tabsBar">
    <form id="j-bbs-svc-packs-tabs" action="">
    <? foreach($packs as $v) { $packID = $v['id']; if(empty($nActiveTab)) $nActiveTab = $packID; ?>
        <div class="left">
            <span style="margin: 0 2px;" class="tab<? if($nActiveTab == $packID){ ?> tab-active<? } ?>" data-id="<?= $packID ?>" onclick="return jSvcServicepacks.onTab(this);"><?= $v['title'] ?></span>
            <input type="hidden" name="svc[<?= $packID ?>]" value="<?= $packID ?>" />
        </div>
    <? } ?>
    </form>
    <div class="progress right" style="display:none;" id="j-bbs-svc-packs-progress"></div>
    <div class="clear"></div>
</div>

<div id="j-bbs-svc-packs-tabs-content">
    <?
    foreach($packs as $k=>$v):
        $packID = $v['id'];
    ?>
    <div id="j-bbs-svc-packs-<?= $packID ?>"<? if($nActiveTab != $packID){ ?> class="hidden"<? } ?>>
        <form action="<?= $saveUrl ?>" class="j-svc-pack-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $packID ?>" />
        <table class="admtbl tbledit">
            <?= $this->locale->buildForm($v, 'bbs-svc-packs-'.$packID,'
            <tr>
                <td class="row1"><span class="field-title">Название</span>:</td>
                <td class="row2">
                    <input type="text" name="title_view[<?= $key ?>]" value="<?= ( isset($aData[\'title_view\'][$key]) ? HTML::escape($aData[\'title_view\'][$key]) : \'\') ?>" class="stretch lang-field" />
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">Описание<br />(краткое)</span>:</td>
                <td class="row2">
                    <textarea name="description[<?= $key ?>]" class="lang-field" rows="4"><?= (isset($aData[\'description\'][$key]) ? $aData[\'description\'][$key] : \'\'); ?></textarea>
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">Описание<br />(подробное)</span>:</td>
                <td class="row2">
                    <?= tpl::jwysiwyg((isset($aData[\'description_full\'][$key]) ? $aData[\'description_full\'][$key] : \'\'), \'description_full-\'.$key.$aData[\'id\'].\',description_full[\'.$key.\']\', 0, 130); ?>
                </td>
            </tr>
            '); ?>
            <tr>
                <td class="row1" width="130"><span class="field-title">Стоимость</span>:</td>
                <td class="row2">
                    <input type="text" name="price" value="<?= $v['price'] ?>" maxlength="6" style="width: 60px;" pattern="[0-9\.\,]*" />&nbsp;<span class="desc"><?= $curr['title_short'] ?></span>
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">Услуги входящие<br/>в пакет</span>:</td>
                <td class="row2">
                    <table class="admtbl tbledit svc-block table-hover" style="width:420px;">
                    <? foreach($svc as $sk=>$sv)
                    {
                        $checked = !empty( $v['svc'][$sk] );
                        $enabled = ! empty( $sv['on'] );
                        if( $enabled ) {
                            if( $sv['id'] == BBS::SERVICE_PRESS && ! BBS::PRESS_ON ) {
                                $enabled = false;
                            }
                        }
                    ?>
                    <tr class="svc-line" data-svc="<?= $sk ?>"<? if( ! $enabled) { ?> style="display: none;" <? } ?>>
                        <td><label class="checkbox"><input type="checkbox" class="j-chk" <? if($checked){ ?> checked="checked" <? } ?> name="svc[<?= $sk ?>][id]" onclick="jSvcServicepacks.onSvc(this);" value="<?= $sv['id'] ?>" /><?= $sv['title'] ?></label></td>
                        <td>
                            <? switch($sv['id'])
                              {
                                case BBS::SERVICE_UP: { ?>
                                    <input type="text" class="j-cnt" name="svc[<?= $sk ?>][cnt]" value="<?= ($checked ? $v['svc'][$sk]['cnt'] : '')  ?>" style="width:50px;" maxlength="3" /><span class="desc">&nbsp;- количество поднятий</span>
                                <? } break;
                                case BBS::SERVICE_MARK:
                                case BBS::SERVICE_FIX:
                                case BBS::SERVICE_PREMIUM:
                                case BBS::SERVICE_QUICK:
                                { ?>
                                    <input type="text" class="j-cnt" name="svc[<?= $sk ?>][cnt]" value="<?= ($checked ? $v['svc'][$sk]['cnt'] : '')  ?>" style="width:50px;" maxlength="3" /><span class="desc">&nbsp;- количество дней</span>
                                <? } break;
                                case BBS::SERVICE_PRESS: { ?>
                                    <span class="desc">единоразово</span>
                                <? } break;
                              } ?>
                        </td>
                    </tr>
                    <? } ?>
                    </table>
                </td>
            </tr>
            <?  $oIcon = BBS::svcIcon($packID);
                foreach($oIcon->getVariants() as $iconField=>$icon) {
                    $oIcon->setVariant($iconField);
                    $icon['uploaded'] = ! empty($v[$iconField]);
                ?>
                <tr>
                    <td class="row1">
                        <span class="field-title"><?= $icon['title'] ?></span>:<? if(sizeof($icon['sizes']) == 1) { $sz = current($icon['sizes']); ?><br /><span class="desc"><?= ($sz['width'].'x'.$sz['height']) ?></span><? } ?>
                    </td>
                    <td class="row2">
                        <input type="file" name="<?= $iconField ?>" <? if($icon['uploaded']){ ?>style="display:none;" <? } ?> />
                        <? if($icon['uploaded']) { ?>
                            <div style="margin:5px 0;">
                                <input type="hidden" name="<?= $iconField ?>_del" class="del-icon" value="0" />
                                <img src="<?= $oIcon->url($packID, $v[$iconField], $icon['key']) ?>" alt="" /><br />
                                <a href="#" class="ajax desc cross but-text" onclick="return jSvcServicepacks.iconDelete(this);">удалить</a>
                            </div>
                        <? } ?>
                    </td>
                </tr>
                <? }
            ?>
            <tr>
                <td class="row1"><span class="field-title">Цвет</span>:</td>
                <td class="row2">
                    <input type="text" name="color" value="<?= $v['color'] ?>" class="input-mini" />
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">В форме добавления</span>:</td>
                <td class="row2">
                    <input type="checkbox" name="add_form" <? if($v['add_form']){ ?>checked="checked"<? } ?> />
                </td>
            </tr>
            <tr>
                <td class="row1"><span class="field-title">Включен</span>:</td>
                <td class="row2">
                    <input type="checkbox" name="on" <? if($v['on']) { ?>checked="checked" <? } ?> />
                </td>
            </tr>
            <tr class="footer">
                <td colspan="2">
                    <div class="left">
                        <input type="submit" class="btn btn-success button submit" value="Сохранить" />
                        <input type="button" class="btn btn-danger button delete" value="Удалить" onclick="jSvcServicepacks.del(<?= $packID ?>);" />
                    </div>
                    <div class="right desc">
                        последние изменения: <span class="j-last-modified"><?= tpl::date_format2($v['modified'], true); ?>, <a class="bold desc ajax" href="#" onclick="return bff.userinfo(<?= $v['modified_uid'] ?>);"><?= $v['modified_login'] ?></a></span>
                    </div>
                    <div class="clear"></div>
                </td>
            </tr>
        </table>
        </form>
    </div>
    <? endforeach; ?>
</div>

<script type="text/javascript">
var jSvcServicepacks = (function(){
    var urlAjax = '<?= $this->adminLink('svc_packs&act='); ?>';
    var $tabs, $progress;

    $(function(){
        $tabs = $('#j-bbs-svc-packs-tabs-content > div');
        $progress = $('#j-bbs-svc-packs-progress');

        var $packsOrder = $('#j-bbs-svc-packs-tabs').sortable({
            update: function( event, ui ) {
                bff.ajax(urlAjax+'reorder', $packsOrder.serialize(), function(data,errors) {
                    if(data && data.success) {
                        bff.success('Порядок пакетов услуг был успешно изменен');
                    }
                }, $progress);
            }
        });
        $packsOrder.sortable('refresh');

        $('.j-svc-pack-form').each(function(){
            var $form = $(this);
            bff.iframeSubmit($form, function(data){
                if(data && data.success) {
                    bff.success('Настройки успешно сохранены');
                    setTimeout(function(){ location.reload(); }, 1000);
                }
            });
        });
    });
    
    return {
        onTab: function(link)
        {
            var packID = $(link).data('id');
            $tabs.addClass('hidden');
            $tabs.filter('#j-bbs-svc-packs-'+packID).removeClass('hidden');
            $(link).addClass('tab-active').parent().siblings().find('.tab').removeClass('tab-active');
            if( bff.h ) {
                window.history.pushState({}, document.title, '<?= $this->adminLink('svc_packs&tab=') ?>'+packID);
            }
            return false;
        },
        del: function(packID)
        {
            if(!bff.confirm('sure')) return;
            bff.ajax(urlAjax+'del', {id:packID}, function(data){
                if(data && data.success){
                    bff.error('Пакет услуг успешно удалён', {success: true});
                    setTimeout(function(){
                        bff.redirect(data.redirect);
                    }, 1000);
                }
            }, $progress);
        },
        onSvc: function(check)
        {
            var block = $(check).parents('.svc-line:first');
            var cnt = block.find('.j-cnt');
            if( $(check).is(':checked') ) {
                cnt.focus();
            } else {
                cnt.val('');
            }
        },
        iconDelete: function(link){
            var $block = $(link).parent();
            $block.hide().find('input.del-icon').val(1);
            $block.prev().show();
            return false;
        }
    };
}());
</script>
<?
