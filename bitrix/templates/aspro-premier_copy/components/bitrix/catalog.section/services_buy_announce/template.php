<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc,
	TSolution\Product\Service;

Loc::loadMessages(__FILE__);

if (empty($arResult['ITEMS'])) return;

$templateData['EPILOG_EXTENSIONS'] = ['ui-card', 'ui-card.pattern', 'ui-card.ratio', 'prices', 'catalog'];

$visibleCount = (int)($arParams['VISIBLE_COUNT'] ?? 2);
$bShowMore = is_array($arResult['ITEMS']) && $visibleCount < count($arResult['ITEMS']);

$bOrderViewBasket = $arParams['ORDER_VIEW'];
$basketUrl = TSolution::GetFrontParametrValue('BASKET_PAGE_URL');

$bShowImage = $arParams['IMAGES'] !== 'N' && in_array('PREVIEW_PICTURE', $arParams['FIELD_CODE']);
$bIcons = $arParams['IMAGES'] === 'ICONS';

$gridClass = ['grid-list grid-list--normal outer-rounded-x bordered gap gap--0 overflow-block'];
$gridClass[] = \TSolution\Functions::getGridClassByCount(['768', '992', '1200'], $arParams['ELEMENTS_IN_ROW']);

$itemWrapperClasses = ['ui-card grid-list__item ui-card--image-scale stroke-theme-parent-all colored_theme_hover_bg-block color-theme-parent-all border-bottom'];

$gridClass = TSolution\Utils::implodeClasses($gridClass);
$itemWrapperClasses = TSolution\Utils::implodeClasses($itemWrapperClasses);
?>
<div class="services-buy services-list <?=$templateName?>-template">
	<div class="fw-500 font_14 color_222 mb mb--8"><?=GetMessage("SERVICES_TITLE_BLOCK");?></div>
	<div class="<?=$gridClass;?>">
		<?
		$counter = 0;
		?>
		<?foreach ($arResult['ITEMS'] as $i => $arItem):?>
			<?
			++$counter;
			if (
				$bShowMore &&
				$counter > $visibleCount
			) {
				break;
			}

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

						if ($bIcons) {
							$imageWrapperClassList[] = 'p-inline p-inline--12 pt pt--12 pb pb--12';
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

					<div class="p-block p-block--16 p-inline p-inline--16 flexbox">
						<?if ($bDetailLink):?>
							<a href="<?=$arItem['DETAIL_PAGE_URL'];?>" class="blog-item__title color-theme-target font_14 no-decoration switcher-title lineclamp-1">
								<?=$elementName?>
							</a>
						<?else:?>
							<div class="blog-item__title color-theme-target font_14 switcher-title lineclamp-1">
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

						<div class="pt pt--12 mt mt--auto flexbox flexbox--direction-row gap gap--12 flexbox--align-end flexbox--justify-between flex-grow-0">
							<div class="price-flex">
								<?
								$prices = (new TSolution\Product\Prices(
									$arItem,
									$arParams,
									[
										'PRICE_FONT' => 14,
									]
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
									'BTN_CLASS' => 'btn-xs',
									'BTN_ORDER_CLASS' => 'btn-xs',
									'BTN_IN_CART_CLASS' => 'btn-xs',
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
	</div>

	<?if ($bShowMore):?>
		<div class="services-items__more link-opacity-color link-opacity-color--hover pointer font_13 mt mt--8" data-open="<?=htmlspecialcharsbx(Loc::getMessage('ALL_BUY_SERVICES'))?>" data-close="<?=htmlspecialcharsbx(Loc::getMessage('HIDE_BUY_SERVICES'))?>">
			<span class="dotted" ><?=Loc::getMessage('ALL_BUY_SERVICES')?></span>
		</div>
	<?endif;?>

    <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>
</div> <?// .services-list?>
