<div id="j-bbs-index-last-block" class="index-latest">
    <div class="index-latest__heading">
        <div class="customNavigation">
            <a class="prev j-prev"><i class="fa fa-chevron-left"></i></a>
            <a class="next j-next"><i class="fa fa-chevron-right"></i></a>
        </div>
        <h2>Последние объявления</h2>
    </div>
    <div class="sr-page__gallery sr-page__gallery_desktop">
		<div class="thumbnails owl-carousel" id="j-bbs-index-last-carousel">
		<? foreach ($items as $item) {?>
			<div class="sr-page__gallery__item index-latest__item thumbnail rel owl-item">
				<? if($item['fav']) { ?>
						<a href="javascript:void(0);" class="item-fav active j-i-fav" data="{id:<?= $item['id'] ?>}" title="Удалить из избранного"><span class="item-fav__star"><i class="fa fa-star j-i-fav-icon"></i></span></a>
					<? } else { ?>
						<a href="javascript:void(0);" class="item-fav j-i-fav" data="{id:<?= $item['id'] ?>}" title="Добавить в избранное"><span class="item-fav__star"><i class="fa fa-star-o j-i-fav-icon"></i></span></a>
					<? } ?>
				<div class="sr-page__gallery__item_img align-center">
					<a target="_blank" title="<?=$item['title'];?>" href="<?=$item['link'];?>" class="thumb stack rel inlblk">
						<img alt="<?=$item['title'];?>" src="<?=$item['img_m'];?>">
						<span class="abs border b2 shadow">&nbsp;</span>
						<span class="abs border r2 shadow">&nbsp;</span>
					</a>
                </div>
				<div class="sr-page__gallery__item_descr">
					<h4 class="dscr-hide"><a href="<?=$item['link'];?>"><?=$item['title'];?></a></h4>
					<p class="sr-page__gallery__item_price">
						<strong><?=$item['price'];?></strong>
						<?= ($item['price_mod'] ? "<small>".$item['price_mod']."</small>" : ""); ?>
					</p>
					<p><small>
						<?=$item['cat_title'];?>
					</small><br><?=$item['city_title'];?></small></p>
				</div>
			</div>
		<? } ?>
		</div>
    </div>
</div>
<?
tpl::includeCSS(array('owl.carousel'), true);
tpl::includeJS('owl.carousel.min', false);
?>
