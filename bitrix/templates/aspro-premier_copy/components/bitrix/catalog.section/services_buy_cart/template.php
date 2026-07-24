<?
use Bitrix\Main\Localization\Loc,
	TSolution\Product\Service;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);
$templateData['EPILOG_EXTENSIONS'] = [];

$bOrderViewBasket = $arParams['ORDER_VIEW'];
$bShowPicture = ($arParams['SHOW_PICTURE'] ?? 'N') === 'Y';
$visibleCount = (int)($arParams['VISIBLE_COUNT'] ?? 2);

$inBasketCount = 0;
if (
	isset($arParams['SERVICES_IN_BASKET']) &&
	is_array($arParams['SERVICES_IN_BASKET'])
) {
	$inBasketCount = count($arParams['SERVICES_IN_BASKET']);
}
if ($inBasketCount > 0) {
	$visibleCount = max($visibleCount, $inBasketCount);
}

$bShowMore = is_array($arResult['ITEMS']) && $visibleCount < count($arResult['ITEMS']);
?>
<?if ($arResult['ITEMS']):?>
	<?
	$templateData['EPILOG_EXTENSIONS']['catalog'] = 'catalog';
	$basketUrl = TSolution::GetFrontParametrValue('BASKET_PAGE_URL');
	?>
	<div class="services-buy services-items services-items--table">
		<div class="services-items__inner">
			<div class="grid-list grid-list--items grid-list--items-1 gap grid-list--no-gap">
				<?
				$counter = 0;
				?>
				<?foreach ($arResult['ITEMS'] as $key => $arItem):?>
					<?
					++$counter;

					$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT'));
					$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));

					$elementName = TSolution\Product\Common::getElementName($arItem);

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

					$arItem['strMainID'] = $this->GetEditAreaId($arItem['ID']);

					$bCanBuy = Service::getCanBuy($arItem);
					$dataItem = TSolution::getDataItem($arItem);

					$prices = new TSolution\Product\Prices(
						$arItem,
						$arParams,
						[
							'PRICE_FONT' => 14,
						]
					);

					$serviceInBasket = $arParams['SERVICES_IN_BASKET'][$arItem['ID']] ?? [];

					if (
						$serviceInBasket &&
						$prices->isFilled()
					) {
						if ($prices->getCurrentPrice()['PRICE_ID'] == $serviceInBasket['PRODUCT_PRICE_ID']) {
							unset(
								$arItem['ITEM_PRICES'],
								$arItem['PRICES'],
								$arItem['PRICE_MATRIX']
							);

							$prices->item = $arItem;
							$prices->options = [
								'PRICES' => [
									'PRICE_CURRENCY' => $serviceInBasket['CURRENCY'],
									'VALUE' => $serviceInBasket['VALUE'],
									'PRINT_VALUE' => $serviceInBasket['PRINT_VALUE'],
									'DISCOUNT_VALUE' => $serviceInBasket['DISCOUNT_VALUE'],
									'PRINT_DISCOUNT_VALUE' => $serviceInBasket['PRINT_DISCOUNT_VALUE'],
									'DISCOUNT_DIFF' => $serviceInBasket['DISCOUNT_DIFF'],
									'PRINT_DISCOUNT_DIFF' => $serviceInBasket['PRINT_DISCOUNT_DIFF'],
								],
							];
						}
					}
					?>
					<div class="grid-list__item <?=($counter > $visibleCount ? ' hidden' : '')?><?=($serviceInBasket ? ' order_top_service' : '')?>">
						<div class="services-item mb mb--2 js-popup-block<?=($serviceInBasket ? ' services-item--selected' : '')?>" id="<?=$this->GetEditAreaId($arItem['ID'])?>_services_table">
							<?if ($imageSrc):?>
								<meta itemprop="image" content="<?=$imageSrc?>" />
							<?endif;?>

							<div class="flexbox flexbox--direction-row flexbox--align-center gap gap--8">
								<div class="services-item__info color-theme-parent-all" data-id="<?=$arItem['ID']?>" data-item="<?=$dataItem?>">
									<div class="line-block line-block--gap line-block--gap-8">
										<div class="line-block__item services-item__on-off-switch">
											<input
												type="checkbox"
												name="buy_switch_services"
												id="<?=$arItem['strMainID']?>_switch"
												class="js-item-action-switch"
												data-id="<?=$arItem['ID']?>"
												data-action="service" <?=($serviceInBasket ? ' checked' : '')?>
												autocomplete="off"
											/>
											<label for="<?=$arItem['strMainID']?>_switch"> &nbsp;</label>
										</div>

										<div class="line-block__item">
											<div class="line-block line-block--flex-wrap line-block--gap line-block--gap-8">
												<div class="line-block__item">
													<span
														class="color_dark color_dark--opacity services-item__info-title lineclamp-1 no-decoration font_13"
														title="<?=htmlspecialcharsbx($elementName)?>"
													>
														<span><?=$elementName?></span>
													</span>
												</div>

												<?
												$arBtnConfig = [
													'ITEM' => $arItem,
													'ITEM_ID' => $arItem['ID'],
													'BASKET_URL' => $basketURL,
													'BASKET' => $bOrderViewBasket,
													'ORDER_BTN' => $bOrderButton,
													'BTN_CLASS' => 'btn-xs hidden',
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
													'HAS_PRICE' => ($prices->isCatalogFilled() && $prices->isGreaterThanZero()) || ($serviceInBasket && $prices->hasCustomPrices()),
													'EMPTY_PRICE' => $prices->isEmpty(),
												];
												$arBasketConfig = TSolution\Product\Basket::getOptions($arBtnConfig);
												?>
												<div class="line-block__item services-item__counter">
													<?=$arBasketConfig['HTML']?>
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="services-item__price js-popup-price">
									<?$prices->show();?>
								</div>
							</div>
						</div>
					</div>
				<?endforeach;?>
			</div>
		</div>

		<?if ($bShowMore):?>
            <div class="services-items__more color_dark pointer font_13 mt mt--8" data-open="<?=htmlspecialcharsbx(Loc::getMessage('ALL_BUY_SERVICES'))?>" data-close="<?=htmlspecialcharsbx(Loc::getMessage('HIDE_BUY_SERVICES'))?>">
				<span class="dotted" ><?=Loc::getMessage('ALL_BUY_SERVICES')?></span>
			</div>
        <?endif;?>

        <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>
	</div>
<?endif;?>
