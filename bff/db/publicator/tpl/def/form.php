<?php

    use bff\db\Publicator;

    $params = $this->getSettings( array('title', 'use_wysiwyg', 'use_reformator', 'photo_wysiwyg', 'photo_align', 'photo_zoom', 'gallery_photos_limit') );
    $params['name'] = bff::$class;

    # мультиязычность
    $aLangs = $this->langs;
    $useLangs = !empty($aLangs);

    $index = mt_rand(1,1000);
?>
<div class="publicator j-publicator" data-object="<?= $js_object ?>" id="publicator-<?= $index ?>" style="margin-bottom: 75px;">
    <div class="canvas">
        <div class="publicatorTitle hidden"><?php
            if($params['title']):
                if($useLangs):
                    foreach($content['t'] as $k=>$lv) { ?><div class="<?= $k ?>"><?= $lv ?></div><?php }
                else:
                    echo ( ! empty($content['t']) ? $content['t'] : '' );
                endif;
            endif;
         ?></div>
        <div class="publicatorData hidden">
     <?php if( ! empty($content['b']))
        foreach($content['b'] as $v):
           ?><div class="block">
                <div class="type"><?= $v['type']; ?></div>
           <?php
           switch($v['type'])
           {
               case Publicator::blockTypeText:
               {
                    ?><div class="text"><?php if( ! isset($v['text'])) continue;
                        if($useLangs) {
                            foreach($v['text'] as $k=>$lv) { ?><div class="<?= $k ?>"><?= $lv ?></div><?php }
                        } else {
                            echo $v['text'];
                        } ?></div><?php
               } break;
               case Publicator::blockTypeQuote:
               {
                    ?><div class="text"><?php if( ! isset($v['text'])) continue;
                        if($useLangs) {
                            foreach($v['text'] as $k=>$lv) { ?><div class="<?= $k ?>"><?= $lv ?></div><?php }
                        } else {
                            echo $v['text'];
                        } ?></div><?php
               } break;
               case Publicator::blockTypePhoto:
               {
                    ?><div class="photo"><?= $v['photo']; ?></div><?php
                    foreach( $v['url'] as $uk=>$uv ) { ?><div class="url_<?= $uk ?>"><?= $uv ?></div><?php }
                    ?><div class="align"><?= ( ! empty($v['align']) ? $v['align'] : 'center'); ?></div><?php
                    ?><div class="zoom"><?=  ( ! empty($v['zoom']) ? 1 : 0); ?></div><?php
                    ?><div class="view"><?=  ( ! empty($v['view']) ? $v['view'] : ''); ?></div><?php
                    ?><div class="text"><?php if( ! isset($v['text'])) continue;
                        if($useLangs) {
                            foreach($v['text'] as $k=>$lv) { ?><div class="<?= $k ?>"><?= $lv ?></div><?php }
                        } else {
                            echo $v['text'];
                        } ?></div><?php
               } break;
               case Publicator::blockTypeGallery:
               {
                    ?><div class="photos"><?= func::php2js($v['p']); ?></div><?php
               } break;
               case Publicator::blockTypeVideo:
               {
                    ?><div class="source"><?= $v['source']; ?></div><?php
                    ?><div class="video"><?= $v['video']; ?></div><?php
               } break;
               case Publicator::blockTypeSubtitle:
               {
                    ?><div class="text"><?php if( ! isset($v['text'])) continue;
                        if($useLangs) {
                            foreach($v['text'] as $k=>$lv) { ?><div class="<?= $k ?>"><?= $lv ?></div><?php }
                        } else {
                            echo $v['text'];
                        } ?></div><?php
                    ?><div class="size"><?= (!empty($v['size']) ? $v['size'] : ''); ?></div><?php
               } break;
           }
           ?></div><?php
       endforeach; ?>
        </div>
        <table cellpadding="0" cellspacing="0" border="0" width="100%" class="publicatorBlocks"></table>
    </div>
    <div class="controlsBorder">
        <?php
            $nControlsWidth = ( ! empty($controls) ? sizeof($controls) : 6 ) * 41;
        ?>
        <div class="controls overflow" style="width: <?= $nControlsWidth + 26 ?>px;">
            <div class="leftCorner"></div>
            <div class="center" style="width: <?= $nControlsWidth ?>px;">
                <?php if(empty($controls) || in_array(Publicator::blockTypeText, $controls))    { ?><a href="#" class="text" rel="text"><i class="control-text">&nbsp;</i>текст</a><?php } ?>
                <?php if(empty($controls) || in_array(Publicator::blockTypePhoto, $controls))   { ?><a href="javascript:void(0);" rel="photo" class="photo relative"><div id="photoBtnPlaceholder"></div><i class="control-photo"></i>фото</a><?php } ?>
                <?php if(empty($controls) || in_array(Publicator::blockTypeGallery, $controls)) { ?><a href="#" class="gallery" rel="gallery"><i class="control-gallery">&nbsp;</i>фото+</a><?php } ?>
                <?php if(empty($controls) || in_array(Publicator::blockTypeVideo, $controls))   { ?><a href="#" class="video" rel="video"><i class="control-video">&nbsp;</i>видео</a><?php } ?>
                <?php if(empty($controls) || in_array(Publicator::blockTypeSubtitle, $controls)){ ?><a href="#" class="subtitle" rel="subtitle"><i class="control-subtitle">&nbsp;</i>h2</a><?php } ?>
                <?php if(empty($controls) || in_array(Publicator::blockTypeQuote, $controls))   { ?><a href="#" class="quote" rel="quote"><i class="control-quote">&nbsp;</i>цитата</a><?php } ?>
                <div class="clear"></div>
            </div>
            <div class="rightCorner"></div>
            <div class="clear"></div>
        </div>
        <?php if($useLangs && sizeof($aLangs) > 1): ?>
            <div class="langs overflow displaynone">
                <?php foreach($aLangs as $k){ ?><a href="#" rel="<?= $k ?>" class="but lng-<?= $k.($k === LNG ? ' active' : '') ?>"></a><?php } ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="clear"></div>
</div>
<script type="text/javascript">
<?php js::start(); ?>
<?= ( ! empty($js_object)? ' var '.$js_object.';':''); ?>
$(function(){
    <?= ( ! empty($js_object)? $js_object.' = ':''); ?>
    bffPublicator.init('#publicator-<?= $index; ?>', {
        id: <?= $id; ?>, fieldname: '<?= $fieldname; ?>',
        langs: <?= ($useLangs?func::php2js($aLangs):'false'); ?>, langs_cur: '<?= LNG ?>',
        m: <?= func::php2js($params); ?>,
        sessid: '<?= session_id(); ?>',
        url: '<?= $this->adminLink(bff::$event.'&publicator=1&act=', $this->owner_module) ?>',
        debug: <?= $debug ? 'true' : 'false' ?>
    });
});
<?php js::stop(); ?>
</script>