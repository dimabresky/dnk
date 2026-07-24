<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc,
	TSolution\Product\Service;

Loc::loadMessages(__FILE__);

if (empty($arResult['ITEMS'])) return;

$visibleCount = (int)($arParams['VISIBLE_COUNT'] ?? 2);
if (
	empty($arResult['ITEMS']) ||
	$visibleCount >= count($arResult['ITEMS'])
) {
	// skip list because all services are in announce
	echo '<span style="display:none">error</span>';
	return;
}

$templateData['EPILOG_EXTENSIONS'] = ['ui-card', 'ui-card.pattern', 'ui-card.ratio', 'prices', 'catalog'];

$bOrderViewBasket = $arParams['ORDER_VIEW'];
$basketUrl = TSolution::GetFrontParametrValue('BASKET_PAGE_URL');

$bShowImage = $arParams['IMAGES'] !== 'N' && in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE']);
$bIcons = $arParams['IMAGES'] === 'ICONS';
$bBordered = $arParams['BORDERED'] === 'Y';
$bFon = $arParams['FON'] === 'Y';

$gridClass = ['grid-list'];
$gridClass[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW']);
if (
	!isset($arParams['MOBILE_SCROLLED'])
	|| isset($arParams['MOBILE_SCROLLED']) && $arParams['MOBILE_SCROLLED']
) {
	$gridClass[] = 'mobile-scrolled mobile-scrolled--items-2 mobile-offset';
} else {
	$gridClass[] = 'grid-list--normal';
}

$itemWrapperClasses = ['ui-card grid-list__item ui-card--image-scale stroke-theme-parent-all colored_theme_hover_bg-block color-theme-parent-all'];
if ($bBordered) {
	$itemWrapperClasses[] = 'bordered';
}
if ($bFon) {
	$itemWrapperClasses[] = 'grey-bg';
}
if ($bBordered || $bFon) {
	$itemWrapperClasses[] = 'outer-rounded-x';
}

$bMaxWidthWrap = (
	!isset($arParams['MAXWIDTH_WRAP']) ||
	(isset($arParams['MAXWIDTH_WRAP']) && $arParams['MAXWIDTH_WRAP'] !== "N")
);

$gridClass = TSolution\Utils::implodeClasses($gridClass);
$itemWrapperClasses = TSolution\Utils::implodeClasses($itemWrapperClasses);
?>
<?if (!$arParams['IS_AJAX']):?>
	<div class="services-buy services-list <?=$templateName?>-template">
		<?=TSolution\Functions::showTitleBlock([
			'PATH' => 'services-list',
			'PARAMS' => $arParams
		]);?>

		<?if ($bMaxWidthWrap):?>
			<div class="maxwidth-theme">
		<?endif;?>
		<div class="<?=$gridClass;?>">
<?endif;?>
		<?foreach ($arResult['ITEMS'] as $i => $arItem):?>
			<?
			// edit/add/delete buttons for edit mode
			$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
			$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => Loc::getMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));

			$elementName = TSolution\Product\Common::getElementName($arItem);

			// use detail link?
			$bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);

			// preview image
			if ($bIcons) {
				$nImageID = is_array($arItem['PROPERTIES'])
					&& array_key_exists('ICON', $arItem['PROPERTIES'])
					&& array_key_exists('VALUE', $arItem['PROPERTIES']['ICON'])
						? $arItem['PROPERTIES']['ICON']['VALUE']
						: '';
			} else {
				$nImageID = is_array($arItem['PREVIEW_PICTURE'])
					? $arItem['PREVIEW_PICTURE']['ID']
					: $arItem['FIELDS']['PREVIEW_PICTURE'];
			}

			$imageSrc = $nImageID ? CFile::getPath($nImageID) : '';

			$bOrderButton = $arItem['PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES';
			$bCanBuy = Service::getCanBuy($arItem);
			$dataItem = TSolution::getDataItem($arItem);
			?>
			<div class="<?=$itemWrapperClasses;?>" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
				<div class="flexbox height-100 js-popup-block">
					<?if ($imageSrc):?>
						<meta itemprop="image" content="<?=$imageSrc?>" />
					<?endif;?>

					<?if ($bShowImage && $imageSrc):?>
						<?
						$imageWrapperClassList = [];

						if (!$bBordered && !$bFon) {
							$imageWrapperClassList[] = 'image-rounded-x';
						}
						if ($bIcons) {
							$imageWrapperClassList[] = 'p-inline p-inline--24 pt pt--24 pb pb--20';
						} else {
							$imageWrapperClassList[] = 'ui-card__image';
						}

						$imageWrapperClassList = TSolution\Utils::implodeClasses($imageWrapperClassList);
						?>
						<?if ($bDetailLink):?>
							<a class="<?=$imageWrapperClassList;?>" href="<?=$arItem['DETAIL_PAGE_URL'];?>">
						<?else:?>
							<div class="<?=$imageWrapperClassList;?>">
						<?endif;?>

								<?if ($bIcons && $nImageID):?>
									<?=TSolution::showIconSvg('fill-theme', $imageSrc, class_icon: 'fill-theme');?>
								<?else:?>
									<img src="<?=$imageSrc;?>" class="ui-card__img img js-popup-image" alt="<?=($arItem['PREVIEW_PICTURE']['ALT'] ?? $elementName)?>" title="<?=($arItem['PREVIEW_PICTURE']['ALT'] ?? $elementName)?>" decoding="async">
								<?endif;?>

						<?if ($bDetailLink):?>
							</a>
						<?else:?>
							</div>
						<?endif;?>
					<?endif;?>

					<div class="p-block p-block--20 p-inline p-inline--24 flexbox">
						<?if ($bDetailLink):?>
							<a href="<?=$arItem['DETAIL_PAGE_URL'];?>" class="blog-item__title color-theme-target font_16 no-decoration switcher-title lineclamp-3">
								<?=$elementName?>
							</a>
						<?else:?>
							<div class="blog-item__title color-theme-target font_16 switcher-title lineclamp-3">
								<?=$elementName?>
							</div>
						<?endif;?>

						<?if (
							$arParams['SHOW_PREVIEW_TEXT'] !== 'N' &&
							strlen($arItem['PREVIEW_TEXT'])
						):?>
							<div class="blog-item__text lineclamp-4 font_14 secondary-color mt mt--8">
								<?=$arItem['PREVIEW_TEXT'];?>
							</div>
						<?endif;?>

						<div class="pt pt--24 mt mt--auto flexbox flexbox--direction-row gap gap--12 flexbox--justify-between flex-grow-0">
							<div class="price-flex">
								<?
								$prices = (new TSolution\Product\Prices(
									$arItem,
									$arParams
								))->show();
								?>
							</div>
							<div class="order-info-btn line-block line-block--gap line-block--gap-12 line-block--align-normal"
								data-id="<?=$arItem['ID']?>" data-item="<?=$dataItem?>">
								<?
								$arBtnConfig = [
									'ITEM' => $arItem,
									'ITEM_ID' => $arItem['ID'],
									'BASKET_URL' => $basketURL,
									'BASKET' => $bOrderViewBasket,
									'ORDER_BTN' => $bOrderButton,
									'BTN_CLASS' => 'btn-sm',
									'BTN_ORDER_CLASS' => 'btn-sm',
									'BTN_IN_CART_CLASS' => 'btn-sm',
									'BTN_CALLBACK_CLASS' => 'btn-transparent-border',
									'DETAIL_PAGE' => false,
									'SHOW_COUNTER' => false,
									'SHOW_MORE' => false,
									'CATALOG_IBLOCK_ID' => $arItem['IBLOCK_ID'],
									'ADD_SERVICE' => true,
									'ORDER_FORM_ID' => $arParams['FORM_ID_ORDER_SERVISE'] ? $arParams['FORM_ID_ORDER_SERVISE'] : 'aspro_'.TSolution::solutionName.'_order_services',
									'CONFIG' => array_merge(
										TSolution\Product\Basket::getConfig(),
										[
											'EXPRESSION_ORDER_BUTTON' => $arParams['EXPRESSION_SERVICE_ORDER_BUTTON'] ?: Loc::getMessage('S_ORDER_SERVISE'),
											'EXPRESSION_ADDTOBASKET_BUTTON' => $arParams['EXPRESSION_SERVICE_ADD_BUTTON'] ?: Loc::getMessage('S_ADD_SERVISE'),
										],
									),
									'TOTAL_COUNT' => $bCanBuy ? PHP_INT_MAX : 0,
									'HAS_PRICE' => $prices->isCatalogFilled() && $prices->isGreaterThanZero(),
									'EMPTY_PRICE' => $prices->isEmpty(),
								];
								$arBasketConfig = TSolution\Product\Basket::getOptions($arBtnConfig);
								?>
								<div class="line-block__item ">
									<?=$arBasketConfig['HTML']?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		<?endforeach;?>

		<?if ($arParams['IS_AJAX']):?>
			<div class="wrap_nav bottom_nav_wrapper">
		<?endif;?>
			<?if ($arParams['DISPLAY_BOTTOM_PAGER']):?>
				<?$bHasNav = (strpos($arResult["NAV_STRING"], 'more_text_ajax') !== false);?>
				<div class="bottom_nav mobile_slider <?=($bHasNav ? '' : ' hidden-nav');?>" data-parent=".services-list" data-append=".grid-list" <?=($arParams["IS_AJAX"] ? "style='display: none; '" : "");?>>
					<?if ($bHasNav):?>
						<?=$arResult["NAV_STRING"]?>
					<?endif;?>
				</div>
			<?endif;?>

            <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>

		<?if ($arParams['IS_AJAX']):?>
			</div>
		<?endif;?>


<?if (!$arParams['IS_AJAX']):?>
	<?if ($bMaxWidthWrap):?>
		</div>
	<?endif;?>
	</div>
<?endif;?>

	<?// bottom pagination?>
	<?if ($arParams['IS_AJAX']):?>
		<div class="wrap_nav bottom_nav_wrapper">
	<?endif;?>

	<div class="bottom_nav_wrapper nav-compact">
		<div class="bottom_nav hide-600" <?=($arParams['IS_AJAX'] ? "style='display: none; '" : "");?> data-parent=".services-list" data-append=".grid-list">
			<?if ($arParams['DISPLAY_BOTTOM_PAGER']):?>
				<?=$arResult['NAV_STRING']?>
			<?endif;?>
		</div>
	</div>

	<?if ($arParams['IS_AJAX']):?>
		</div>
	<?endif;?>
<?if (!$arParams['IS_AJAX']):?>
	</div> <?// .services-list?>
<?endif;?>
