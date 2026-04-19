<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

if ($arResult['ITEMS']) {
	foreach($arResult['ITEMS'] as $i => $arItem) {
		if (!is_array($arItem['FIELDS']['PREVIEW_PICTURE'])) {
			unset($arResult['ITEMS'][$i]);
		}
	}
} else {
	return;
}

global $arTheme;
$slideshowSpeed = abs(intval($arTheme['PARTNERSBANNER_SLIDESSHOWSPEED']['VALUE']));
$animationSpeed = abs(intval($arTheme['PARTNERSBANNER_ANIMATIONSPEED']['VALUE']));
$bSlider = $arParams['SLIDER'] === "Y";

$bMaxWidthWrap = (
	!isset($arParams['MAXWIDTH_WRAP']) ||
	(isset($arParams['MAXWIDTH_WRAP']) && $arParams['MAXWIDTH_WRAP'] !== "N")
);

$templateData['ITEMS'] = true;

$bBordered = $arParams['BORDERED'] != 'N';
$bFon = $arParams['FON'] != 'N';

$bShowTitle = $arParams['TITLE'] && $arParams['FRONT_PAGE'] && $arParams['SHOW_TITLE'];
$bShowTitleLink = $arParams['RIGHT_TITLE'] && $arParams['RIGHT_LINK'];

$bHaveMore = count($arResult['ITEMS']) > $arParams['ITEMS_COUNT_SLIDER'];

$blockClasses = '';
if ($bShowTitle) {
	$blockClasses .= ' brands-list--with-text';
}
$blockClasses .= ' brands-list--narrow';

$itemClasses = ['brands-list__item outer-rounded-x'];
if ($bSlider) {
	$itemClasses[] = 'shine';
} else {
	$itemClasses[] = 'shadow-hovered shadow-no-border-hovered shadow-no-border-hovered--with-picture';
}
if ($bBordered) {
	$itemClasses[] = 'bordered';
}

if ($bFon) {
	$itemClasses[] = 'grey-bg-fixed';
} else {
	$itemClasses[] = 'white-bg-fixed';
}

$itemClasses = TSolution\Utils::implodeClasses($itemClasses);
?>
<?if (!$arParams['IS_AJAX']):?>
<div class="brands-list <?=$blockClasses?>">
	<?=\TSolution\Functions::showTitleBlock([
		'PATH' => 'brands-list',
		'PARAMS' => $arParams,
	]);?>

	<?if ($bMaxWidthWrap):?>
		<div class="maxwidth-theme">
	<?endif;?>
		<?if ($bSlider):?>
			<?
			$countSlides = count($arResult['ITEMS']);
			$arOptions = [
				// Disable preloading of all images
				'preloadImages' => false,
				'lazy' => false,
				'keyboard' => true,
				'init' => false,
				'countSlides' => $countSlides,
				'rewind'=> true,
				'freeMode' => ['enabled' => true, 'momentum' => true],
				'slidesPerView' => 'auto',
				'pagination' => false,
				'autoplay' => ['delay' => $slideshowSpeed,],
				'type' => 'main_brands',
			];				
			?>
			<div class="brands-list__slider-wrap swiper-nav-offset">
			<div class="swiper slider-solution slider-solution--static-dots mobile-offset mobile-offset--right appear-block brands-list__items-wrapper" data-plugin-options='<?=json_encode($arOptions)?>'>
			<div class="swiper-wrapper">
		<?else:?>
			<?
			$brandsItemsWrapperClassList = ['brands-list__items-wrapper grid-list--fill-bg1 grid-list grid-list--items grid-list--items-2-from-601 mobile-scrolled mobile-scrolled--items-3 mobile-offset'];
			if ($arParams['IS_TOP_MENU']) {
				$brandsItemsWrapperClassList[] = 'gap gap--8 brands-list__items-wrapper--shrinked';
			} else {
				$brandsItemsWrapperClassList[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW'] ?? $arParams['COUNT_IN_LINE']);
			}

			$brandsItemsWrapperClassList = TSolution\Utils::implodeClasses($brandsItemsWrapperClassList);
			?>
			<div class="<?=$brandsItemsWrapperClassList;?>">
		<?endif;?>
<?endif;?>
			<?foreach($arResult['ITEMS'] as $itemKey => $arItem):?>
				<?
				// edit/add/delete buttons for edit mode
				if (!$arParams['HIDE_ICONS']) {
					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
				}
				// use detail link?
				$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);
				// preview image
				$bImage = (isset($arItem['FIELDS']['PREVIEW_PICTURE']) && $arItem['PREVIEW_PICTURE']['SRC']);
				$nImageID = ($bImage ? (is_array($arItem['FIELDS']['PREVIEW_PICTURE']) ? $arItem['FIELDS']['PREVIEW_PICTURE']['ID'] : $arItem['FIELDS']['PREVIEW_PICTURE']) : "");
				$arImage = ($bImage ? CFile::ResizeImageGet($nImageID, array('width' => 200, 'height' => 80), BX_RESIZE_IMAGE_PROPORTIONAL_ALT, true) : array());
				$imageSrc = ($bImage ? $arImage['src'] : SITE_TEMPLATE_PATH.'/images/svg/noimage_brand.svg');
				?>
				<div class="grid-list__item <?=($bSlider ? 'hover_blink swiper-slide' : '');?>" <?=$arParams['HIDE_ICONS'] ? '' : 'id="'.$this->GetEditAreaId($arItem['ID']).'"';?>>
					<div class="<?=$itemClasses?>">
						<?if ($bDetailLink):?><a  class="brands-list__item-link item-link-absolute" href="<?=$arItem['DETAIL_PAGE_URL']?>"></a><?endif;?>
						<div class="brands-list__image-wrapper">
							<img class="brands-list__image " src="<?=$imageSrc?>" <?if ($bSlider):?>data-src=''<?endif;?> alt="<?=($bImage ? $arItem['PREVIEW_PICTURE']['ALT'] : $arItem['NAME'])?>" title="<?=($bImage ? $arItem['PREVIEW_PICTURE']['TITLE'] : $arItem['NAME'])?>" />
						</div>
					</div>
				</div>
			<?endforeach;?>

			<?if ($arParams["DISPLAY_BOTTOM_PAGER"]):?>
				<?if ($arParams['IS_AJAX']):?>
					<div class="wrap_nav bottom_nav_wrapper">
				<?endif;?>
					<?$bHasNav = (strpos($arResult["NAV_STRING"], 'more_text_ajax') !== false);?>
					<div class="bottom_nav mobile_slider <?=($bHasNav ? '' : ' hidden-nav');?>" data-parent=".brands-list" data-append=".grid-list" <?=($arParams["IS_AJAX"] ? "style='display: none; '" : "");?>>
						<?if ($bHasNav):?>
							<?=$arResult["NAV_STRING"]?>
						<?endif;?>
					</div>

				<?if ($arParams['IS_AJAX']):?>
					</div>
				<?endif;?>
			<?endif;?>
		<?if (!$arParams['IS_AJAX']):?>
			<?if ($bSlider):?>
				</div>
				</div>
				<?if ($arOptions['countSlides'] > 1):?>
					<?TSolution\Functions::showBlockHtml([
						'FILE' => 'ui/slider-navigation.php',
						'PARAMS' => [
							'CLASSES' => 'slider-nav slider-nav--shadow',
						]
					]);?>
				<?endif;?>
			<?endif;?>
		</div>
		<?endif;?>
		
		<?if ($arParams["DISPLAY_BOTTOM_PAGER"]):?>
			<?// bottom pagination?>
			<?if ($arParams['IS_AJAX']):?>
				<div class="wrap_nav bottom_nav_wrapper">
			<?endif;?>

			<div class="bottom_nav_wrapper nav-compact">
				<div class="bottom_nav hide-600" <?=($arParams['IS_AJAX'] ? "style='display: none; '" : "");?> data-parent=".brands-list" data-append=".grid-list">
					<?if ($arParams['DISPLAY_BOTTOM_PAGER']):?>
						<?=$arResult['NAV_STRING']?>
					<?endif;?>
				</div>
			</div>

			<?if ($arParams['IS_AJAX']):?>
				</div>
			<?endif;?>
		<?endif;?>
	
<?if (!$arParams['IS_AJAX']):?>
	<?if ($bMaxWidthWrap):?>
		</div>
	<?endif;?>

	<script>
	BX.Aspro.Loader.once({
		appear: ['.brands-list'],
		add: {ext: 'swiper_init'},
	});
	</script>
</div>
<?endif?>