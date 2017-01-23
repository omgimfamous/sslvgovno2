<?php
/**
 * Блог: просмотр поста - содержание
 * @var $this Blog
 */
 use bff\db\Publicator;

if( ! empty($aData['b']))
{
    $galleryIndex = 0;
    foreach($aData['b'] as $v)
    {
        switch($v['type'])
        {
            case Publicator::blockTypeSubtitle:
            {
                $size = 'h'.$v['size'];
                ?><div class="block block-subtitle"><<?= $size ?>><?= $v['text'][LNG]; ?></<?= $size ?>></div><?
            } break;
            case Publicator::blockTypeText:
            {
                ?><div class="block block-text"><?= $v['text'][LNG]; ?></div><?
            } break;
            case Publicator::blockTypePhoto:
            {
                ?><div class="faq-help-img">
                    <img src="<?= $v['url'][Publicator::szView]; ?>" alt="<?= $v['alt'][LNG] ?>" />
                    <?= ( ! empty($v['text'][LNG]) ? '<div class="faq-help-img_msg">'.$v['text'][LNG].'</div>' : ''); ?>
                  </div><?
            } break;
            case Publicator::blockTypeGallery:
            {
                $galleryIndex++;
                ?><div class="txt-gallery">
                    <div class="fotorama" id="imageGallery<?= $galleryIndex ?>" data-auto="false">
                    <? foreach($v['p'] as $gp) { ?>
                        <a href="<?= ($gp['url'][Publicator::szView]) ?>" data-thumb="<?= ($gp['url'][Publicator::szThumbnail]) ?>" data-caption="<?= $gp['alt'][LNG] ?>" alt="<?= $gp['alt'][LNG] ?>"></a>
                    <? } ?>
                    </div>
                  </div>
                  <script type="text/javascript">
                    <? js::start(); ?>
                    $(function(){
                        $('#imageGallery<?= $galleryIndex ?>').fotorama({
                          width: '100%', maxwidth: '100%', maxheight: 640,
                          allowfullscreen: false,
                          thumbmargin: 10,
                          thumbborderwidth: 1,
                          ratio: 750/640,
                          nav: 'thumbs', fit: 'contain',
                          keyboard: true,
                          loop: true, click: true, swipe: true
                        });
                    });
                    <? js::stop(); ?>
                  </script>
                <?
            } break;
            case Publicator::blockTypeVideo:
            {
                ?><div class="block block-video">
                    <object width="<?= $this->sett['video_width']; ?>" height="<?= $this->sett['video_height']; ?>">
                        <param name="allowfullscreen" value="true" />
                        <param name="allowscriptaccess" value="always" />
                        <param name="wmode" value="transparent" />
                        <param name="movie" value="<?= $v['video']; ?>" />
                        <embed src="<?= $v['video']; ?>" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" wmode="transparent" width="<?= $this->sett['video_width']; ?>" height="<?= $this->sett['video_height']; ?>"></embed>
                    </object>
                  </div><?
            } break;
        }
    }

    if( $galleryIndex > 0 ) {
        tpl::includeJS(array('fotorama/fotorama'), false);
        tpl::includeCSS('/js/fotorama/fotorama', false);
    }
} ?>

<div class="clear"></div>