<?php
    use bff\db\Publicator;
?>
<div class="publicator-<?= $this->sett['owner_module']; ?>">
<?php if( ! empty($aData['t'])) { ?><div class="publicator-title"><?= $aData['t']; ?></div><?php } ?>
<div class="publicator-content">
<?php if( ! empty($aData['b']))
{
   foreach($aData['b'] as $v)
   {
       switch($v['type'])
       {
           case Publicator::blockTypeText:
           {
                ?><div class="block block-text"><?= (isset($v['text']) ? $v['text'] : ''); ?></div><?php
           } break;
           case Publicator::blockTypeQuote:
           {
                ?><div class="block block-quote"><?= (isset($v['text']) ? $v['text'] : ''); ?></div><?php
           } break;
           case Publicator::blockTypePhoto:
           {
                ?><div class="block block-photo">
                    <div class="photo"><img src="<?= ($this->sett['photos_url'].$aData['id'].'_'.$v['photo']); ?>" alt="" /></div><?php
                  ?><div class="text"><?= (isset($v['text']) ? $v['text'] : ''); ?></div>
                  </div><?php
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
                  </div><?php
           } break;
           case Publicator::blockTypeSubtitle:
           {
                ?><div class="block block-subtitle"><?= (isset($v['text']) ? $v['text'] : ''); ?></div><?php
           } break;
       }
   }
} ?>
</div>
</div>