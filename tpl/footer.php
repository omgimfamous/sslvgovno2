<?php
    $aFooterMenu = Sitemap::view('footer');
    $aCounters = Site::i()->getCounters();

?>

<!-- BEGIN footer -->
<? if(DEVICE_DESKTOP_OR_TABLET): ?>
<p class="c-scrolltop" id="j-scrolltop" style="display: none;">
    <a href="#"><span><i class="fa fa-arrow-up"></i></span><?= _t('', 'Наверх'); ?></a>
</p>
<div id="footer" class="l-footer hidden-phone">
    <div class="content">
        <div class="container-fluid  l-footer__content">
            <div class="row-fluid l-footer__content_padding">
                <div class="span4">
                    <?= config::get('copyright_'.LNG); ?>
<?  ?>
                </div>
                <div class="span2">
                    <? if( ! empty($aFooterMenu['col1']['sub']) ) { ?>
                    <ul><? foreach($aFooterMenu['col1']['sub'] as $v) {
                            echo '<li><a href="'.$v['link'].'"'.($v['target'] === '_blank' ? ' target="_blank"' : '').' class="'.$v['style'].'">'.$v['title'].'</a></li>';
                           } ?>
                    </ul>
                    <? } ?>
                </div>
                <div class="span3">
                    <? if( ! empty($aFooterMenu['col2']['sub']) ) { ?>
                    <ul><? foreach($aFooterMenu['col2']['sub'] as $v) {
                            echo '<li><a href="'.$v['link'].'"'.($v['target'] === '_blank' ? ' target="_blank"' : '').' class="'.$v['style'].'">'.$v['title'].'</a></li>';
                           } ?>
                    </ul>
                    <? } ?>
                </div>
                <div class="span3">
                    <? if( ! empty($aFooterMenu['col3']['sub']) ) { ?>
                    <ul><? foreach($aFooterMenu['col3']['sub'] as $v) {
                            echo '<li><a href="'.$v['link'].'"'.($v['target'] === '_blank' ? ' target="_blank"' : '').' class="'.$v['style'].'">'.$v['title'].'</a></li>';
                           } ?>
                    </ul>
                    <? } ?>
                    <div class="l-footer__content__counters">
                        <?  # Выбор языка:
                        $languages = bff::locale()->getLanguages(false);
                        if (sizeof($languages) > 1) { ?>
                            <div class="l-footer__lang rel">
                                <?= _t('', 'Язык:') ?> <a class="dropdown-toggle ajax ajax-ico" id="j-language-dd-link" data-current="<?= LNG ?>" href="javascript:void(0);">
                                    <span class="lnk"><?= $languages[LNG]['title'] ?></span> <i class="fa fa-caret-down"></i>
                                </a>
                                <div class="dropdown-menu dropdown-block pull-left box-shadow" id="j-language-dd">
                                    <ul>
                                        <? foreach($languages as $k=>$v) { ?>
                                            <li>
                                                <a href="<?= bff::urlLocaleChange($k) ?>" class="ico <? if($k==LNG){ ?> active<? } ?>">
                                                    <img src="<?= SITEURL_STATIC.'/img/lang/'.$k.'.gif' ?>" alt="" />
                                                    <span><?= $v['title'] ?></span>
                                                </a>
                                            </li>
                                        <? } ?>
                                    </ul>
                                </div>
                            </div>
                            <script type="text/javascript">
                                <? js::start() ?>
                                $(function(){
                                    app.popup('language', '#j-language-dd', '#j-language-dd-link');
                                });
                                <? js::stop() ?>
                            </script>
                        <? }
                        ?>
                        <div class="l-footer__content__counters__list">
                        <? if( ! empty($aCounters)) { ?>
                            <? foreach($aCounters as $v) { ?><div class="item"><?= $v['code'] ?></div><? } ?>
                        <? } ?>
                        </div>
                    </div>
                    <div class="clearfix"></div>
                </div>
            </div>
        </div>
    </div>
</div>
<? endif; ?>
<? if(DEVICE_PHONE): ?>
<div id="footer" class="l-footer l-footer_mobile visible-phone">
    <div class="l-footer_mobile__menu">
    <? if( ! empty($aFooterMenu['col1']['sub']) ) { ?>
        <ul><? foreach($aFooterMenu['col1']['sub'] as $v) {
                echo '<li><a href="'.$v['link'].'"'.($v['target'] === '_blank' ? ' target="_blank"' : '').' class="'.$v['style'].'">'.$v['title'].'</a></li>';
            } ?>
        </ul>
    <? } ?>
    </div>
    <div class="l-footer_mobile__menu">
        <? if( ! empty($aFooterMenu['col2']['sub']) ) { ?>
            <ul><? foreach($aFooterMenu['col2']['sub'] as $v) {
                    echo '<li><a href="'.$v['link'].'"'.($v['target'] === '_blank' ? ' target="_blank"' : '').' class="'.$v['style'].' pseudo-link">'.$v['title'].'</a></li>';
                } ?>
            </ul>
        <? } ?>
    </div>
    <div class="l-footer_mobile__lang mrgt20">
    <?  # Выбор языка:
    $languages = bff::locale()->getLanguages(false);
    if (sizeof($languages) > 1) { ?>
        <div class="l-footer__lang rel">
            <?= _t('', 'Язык:') ?> <a class="dropdown-toggle ajax ajax-ico" id="j-language-dd-phone-link" data-current="<?= LNG ?>" href="javascript:void(0);">
                <span class="lnk"><?= $languages[LNG]['title'] ?></span> <i class="fa fa-caret-down"></i>
            </a>
            <div class="dropdown-menu dropdown-block box-shadow" id="j-language-dd-phone">
                <ul>
                    <? foreach($languages as $k=>$v) { ?>
                        <li>
                            <a href="<?= bff::urlLocaleChange($k) ?>" class="ico <? if($k==LNG){ ?> active<? } ?>">
                                <img src="<?= SITEURL_STATIC.'/img/lang/'.$k.'.gif' ?>" alt="" />
                                <span><?= $v['title'] ?></span>
                            </a>
                        </li>
                    <? } ?>
                </ul>
            </div>
        </div>
        <script type="text/javascript">
            <? js::start() ?>
            $(function(){
                app.popup('language-phone', '#j-language-dd-phone', '#j-language-dd-phone-link');
            });
            <? js::stop() ?>
        </script>
    <? }
    ?>
    </div>
    <div class="l-footer_mobile__copy mrgt15 mrgb30">
        <?= config::get('copyright_'.LNG); ?>
<?  ?>
        <br>
    </div>
</div>
<? endif; ?>
<!-- END footer -->
<? include 'js.php'; ?>
<?= js::renderInline(js::POS_FOOT); ?>
<?

?>