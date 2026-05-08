<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<?if (!$arResult['ERRORS'] && empty($arResult['ITEMS'])):?>
	<?return;?>
<?endif;?>

<?
$srcYouTube = "https://www.youtube.com";
$bWide = $arParams["WIDE"] === 'Y';
$arParams["SHOW_TITLE"] = $arParams['TITLE'] && $arParams['SHOW_TITLE'];
$bBordered = ($arParams["BORDERED"] === 'Y');

$bMaxWidthWrap = (
	!isset($arParams['MAXWIDTH_WRAP']) ||
	(isset($arParams['MAXWIDTH_WRAP']) && $arParams['MAXWIDTH_WRAP'] !== "N")
);

$bMobileScrolledItems = (
	!isset($arParams['MOBILE_SCROLLED']) || 
	(isset($arParams['MOBILE_SCROLLED']) && $arParams['MOBILE_SCROLLED'])
);

$itemClass = ['ui-card ui-card--image-scale height-100 flexbox flex-auto color-theme-parent-all'];

$arParams['RIGHT_LINK'] = !empty($arParams["CHANNEL_ID_YOUTUBE"] ?? '') ? $arResult['RIGHT_LINK'].$arParams["CHANNEL_ID_YOUTUBE"] : '';

$gridClass = ['grid-list grid-items-'.$arParams['ELEMENTS_ROW']];
$gridClass[] = \TSolution\Functions::getGridClassByCount(['992', '1200'], $arParams['ELEMENTS_ROW']);

if ($bMobileScrolledItems) {
	$gridClass[] = 'mobile-scrolled mobile-scrolled--items-2 mobile-offset';
} else {
	$gridClass[] = 'grid-list--normal';
}

$imageClass = ['ui-card__image ui-card__image--ratio-16-9 ui-card--image-scale image-rounded-x pointer'];

$gridClass = TSolution\Utils::implodeClasses($gridClass);
$itemClass = TSolution\Utils::implodeClasses($itemClass);
$imageClass = TSolution\Utils::implodeClasses($imageClass);
?>
<div class="youtube-list <?=$templateName?>-template type-<?=$typeBlock?>">
	<?=\TSolution\Functions::showTitleBlock([
		'PATH' => '',
		'PARAMS' => $arParams,
	]);?>

	<?if($bMaxWidthWrap):?>
		<div class="maxwidth-theme <?=$bWide ? ' maxwidth-theme--no-maxwidth' : '';?>">
	<?endif;?>
		<?if ($arResult['ERRORS'] && $GLOBALS['USER']->IsAdmin()):?>
			<div class="alert alert-danger">
				<?=$arResult['ERRORS']['MESSAGE']?>
			</div>
		<?endif;?>

			<div class="<?=$gridClass?> youtube-list__items">
				<?foreach ($arResult['ITEMS'] as $arItem):?>
					<div class="grid-list__item">
						<div class="<?=$itemClass;?> youtube-list__item">
							<div class="<?=$imageClass;?> youtube-list__item-player-container _youtube-video" data-video-id="<?=$arItem['ID']?>">
								<div id="youtube-player-id-<?=$arItem['ID'];?>">
									<img src="<?=$arItem['IMAGE']?>" class="ui-card__img img" alt="<?=$arItem["TITLE"];?>" title="<?=$arItem["TITLE"];?>">

									<?if ($arItem['DURATION']):?>
										<time class="video-block-duration video-block-duration--bottom-right font_13 mb mb--12 mr mr--12"><?=$arItem['DURATION'];?></time>
									<?endif;?>
									
									<div class="video-block video-block--cover">
										<div class="video-block__play video-block__play--transparent video-block__play--circle ml ml--12 mb mb--12"></div>
									</div>
								</div>
							</div>

							<a class="ui-card__info mt mt--12 flexbox row-gap row-gap--8 no-decoration color-theme-target" href="<?=$srcYouTube."/watch?v=".$arItem['ID'];?>" target="_blank" rel="nofollow">
								<div class="ui-card__title switcher-title"><?=$arItem["TITLE"];?></div>
								<div class="ui-card__text font_13 secondary-color"><?=FormatDate('d F Y', strtotime($arItem['DATE_FROM']), 'SHORT');?></div>
							</a>
						</div>
					</div>
				<?endforeach;?>
			</div>
	<?if($bMaxWidthWrap):?>
		</div>
	<?endif;?>
</div>