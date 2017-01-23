<div class="row-fluid">
    <div class="l-page index-page span12">
<? if(DEVICE_DESKTOP_OR_TABLET) { ?>
        <? if( ! empty($titleh1) ): ?>
            <h1 class="align-center hidden-phone"><?= $titleh1; ?></h1>
            <div class="l-spacer hidden-phone"></div>
            <h3 class="align-center hidden-phone">На сайте размещено <span class="index__catlist__item__count_all"><?= $nTotal = config::get('bbs_items_total_publicated', 0); ?></span> <?= tpl::declension($nTotal, _t('filter','объявление;объявления;объявлений'), false) ?></h3>
        <? endif; ?>
        <div class="index__catlist hidden-phone">
            <div class="b-category-list cols open">
                <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
            <?= BBS::i()->catsList('index', bff::DEVICE_DESKTOP, 0); ?>
        </div>
        <?= $last ?>
        <div class="l-info  hidden-phone"><?= $seotext; ?></div>
<? } else { ?>
    <?= $last ?>
<? } ?>
    </div>
</div>
<script>
$(function(){
$('.for-open').click(function(){
$(this).hide();
$('.for-close').show();
$('.b-category-list').removeClass('open');
});
$('.for-close').click(function(){
$(this).hide();
$('.for-open').show();
$('.b-category-list').addClass('open');
});
 });
</script>
