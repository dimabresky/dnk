<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale,
	Bitrix\Sale\Internals\StatusLangTable;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

$arParams['HIDE_STATUSES'] = isset($arParams['HIDE_STATUSES']) && is_array($arParams['HIDE_STATUSES']) ? $arParams['HIDE_STATUSES'] : [];
$arParams['CHANGE_STATUS_COLOR'] = isset($arParams['CHANGE_STATUS_COLOR']) && strlen($arParams['CHANGE_STATUS_COLOR']) ? $arParams['CHANGE_STATUS_COLOR'] : '';

$bShowFavorit = $arParams['SHOW_FAVORITE'] != 'N';

$uniqId = 'sale-order-detail--'.$arResult['ID'];

$emptyDeliveryServiceId = Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId();
?>
<div id="<?=$uniqId?>" class="personal__block personal__block--order">
	<?if ($arResult['ERRORS']['FATAL']):?>
		<div class="alert alert-danger"><?=implode('<br />', $arResult['ERRORS']['FATAL']);?></div>

		<?if ($arParams['AUTH_FORM_IN_TEMPLATE'] && isset($arResult['ERRORS']['FATAL'][$this->__component::E_NOT_AUTHORIZED])):?>
			<?//$APPLICATION->AuthForm('', false, false, 'N', false);?>
		<?endif;?>
	<?else:?>
		<?if ($arResult['ERRORS']['NONFATAL']):?>
			<div class="alert alert-danger"><?=implode('<br />', $arResult['ERRORS']['NONFATAL']);?></div>
		<?endif;?>

		<?
		$arStatuses = $arVisibleStatuses = [];
		$arLastVisibleStatus = false;
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);

		$orderClass = $registry->getOrderClassName();
		$order = $orderClass::load($arResult['ID']);

		$orderStatusClassName = $registry->getOrderStatusClassName();
		$arStatusesNames = $orderStatusClassName::getAllStatusesNames(LANGUAGE_ID); // order SORT => ASC
		if ($arStatusesNames) {
			$bColored = false;
			foreach ($arStatusesNames as $statusId => $statusName) {
				$bHidden = in_array($statusId, $arParams['HIDE_STATUSES']);
				$bColored |= $statusId === $arParams['CHANGE_STATUS_COLOR'];

				$arStatuses[$statusId] = [
					'ID' => $statusId,
					'NAME' => $statusName,
					'HIDDEN' => $bHidden,
					'COLORED' => $bColored,
				];

				if (!$bHidden) {
					$arVisibleStatuses[$statusId] =& $arStatuses[$statusId];
					$arLastVisibleStatus =& $arVisibleStatuses[$statusId];
				}

				$arStatuses[$statusId]['LAST_VISIBLE'] =& $arLastVisibleStatus;
			}
		}

		// collect statuses descriptions
		$result = StatusLangTable::getList([
			'select' => ['*'],
			'filter' => [
				'LID' => LANGUAGE_ID,
			],
		]);
		while ($row = $result->fetch()) {
			if (isset($arStatuses[$row['STATUS_ID']])) {
				$arStatuses[$row['STATUS_ID']]['DESCRIPTION'] = $row['DESCRIPTION'];
			}
		}

		$svgStatusSprite = $this->__folder.'/images/svg/status.svg';
		$svgIconsSprite = $this->__folder.'/images/svg/icons.svg';

		$bGuest = $arParams['GUEST_MODE'] === 'Y';
		$bCanceled = $arResult['CANCELED'] === 'Y';
		$bPayed = $arResult['PAYED'] === 'Y';
		$bAllowPay = !$bCanceled && $arResult['IS_ALLOW_PAY'] === 'Y';
		$bDeducted = $arResult['DEDUCTED'] === 'Y';
		$cntPayments2Pay = 0;

		$deliveryInfo = '';
		if (!$bCanceled && !$bDeducted) {
			if ($deliveryInfoPropertyId = $arParams['DELIVERY_INFO_PROP_'.$arResult['PERSON_TYPE_ID']] ?? '') {
				$order = Sale\Order::load($arResult['ID']);
				$propertyCollection = $order->getPropertyCollection();
				$deliveryInfoProperty = $propertyCollection->getItemByOrderPropertyId($deliveryInfoPropertyId);
				if (
					$deliveryInfoProperty &&
					(
						$deliveryInfoProperty->getType() === 'STRING' ||
						$deliveryInfoProperty->getType() === 'TEXT'
					)
				) {
					$deliveryInfo = $deliveryInfoProperty->getViewHtml();
				}
			}
		}

		$arOffersIblocks = [];
		if (TSolution::isSaleMode()) {
			if (Loader::includeModule('catalog')) {
				$rsCatalog = CCatalog::GetList(['sort' => 'asc']);
				while ($ar = $rsCatalog->Fetch()) {
					if ($ar['OFFERS_IBLOCK_ID']) {
						$arOffersIblocks[] = $ar['OFFERS_IBLOCK_ID'];
					}
				}
			}
		}

		$arProducts = $arOffersIdsWithoutImages = $arProductsUrls = [];
		$arBasketItems = array_values($arResult['BASKET']); // reset keys
		$arProductsIDs = array_column($arBasketItems, 'PRODUCT_ID');
		if ($arProductsIDs) {
			$arProductsIDs = array_unique($arProductsIDs);

			$dbRes = CIBlockElement::GetList(
				[],
				['ID' => $arProductsIDs],
				false, 
				false,
				[
					'ID', 
					'IBLOCK_ID',
					'PREVIEW_PICTURE',
					'DETAIL_PICTURE',
					'DETAIL_PAGE_URL',
				]
			);
			while ($arItem = $dbRes->GetNext()) {
				$arProductsUrls[$arItem['ID']] = $arItem['DETAIL_PAGE_URL'];
				unset($arItem['DETAIL_PAGE_URL']);

				if (
					$arItem['PREVIEW_PICTURE'] ||
					$arItem['DETAIL_PICTURE']
				) {
					$arProducts[$arItem['ID']] = $arItem;
				}
				elseif (in_array($arItem['IBLOCK_ID'], $arOffersIblocks)) {
					if (!isset($arOffersIdsWithoutImages[$arItem['IBLOCK_ID']])) {
						$arOffersIdsWithoutImages[$arItem['IBLOCK_ID']] = [];
					}

					$arOffersIdsWithoutImages[$arItem['IBLOCK_ID']][] = $arItem['ID'];
				}
			}

			if ($arOffersIdsWithoutImages) {
				$arOffersIdsByProductsIds = [];
				foreach ($arOffersIdsWithoutImages as $offerIblockId => $arOffersIds) {
					$arProductsList = CCatalogSKU::getProductList($arOffersIds, $offerIblockId);
					if ($arProductsList) {
						foreach ($arProductsList as $offerId => $arOfferInfo) {
							$arOffersIdsByProductsIds[$arOfferInfo['ID']][] = $offerId;
						}
					}
				}

				if ($arOffersIdsByProductsIds) {
					$dbRes = CIBlockElement::GetList(
						[],
						['ID' => array_keys($arOffersIdsByProductsIds)],
						false, 
						false,
						[
							'ID', 
							'IBLOCK_ID',
							'PREVIEW_PICTURE',
							'DETAIL_PICTURE',
						]
					);
					while ($arItem = $dbRes->Fetch()) {
						if (
							$arItem['PREVIEW_PICTURE'] ||
							$arItem['DETAIL_PICTURE']
						) {
							foreach ($arOffersIdsByProductsIds[$arItem['ID']] as $offerId) {
								$arProducts[$offerId] = $arItem;
							}
						}
					}
				}
			}
		}

		$orderStatusPopover = new TSolution\Popover\OrderStatus($svgStatusSprite, $arStatuses, $arVisibleStatuses);
		?>		
		<div class="order-pane">
			<div class="order-pane__col">
				<div class="order__bar order__bar--grey order__bar--status outer-rounded-x">
					<?
					$statusClass = 'simple';
					if ($bCanceled) {
						$statusClass = 'canceled';
					}
					elseif ($arLastVisibleStatus['ID'] === $arStatuses[$arResult['STATUS_ID']]['LAST_VISIBLE']['ID']) {
						$statusClass = 'last';
					}
					elseif ($arStatuses[$arResult['STATUS_ID']]['LAST_VISIBLE']['COLORED']) {
						$statusClass = 'colored';
					}

					$bShowStatusPopup = $arVisibleStatuses && ($statusClass === 'simple' || $statusClass === 'colored');
					?>
					<div class="order__status order__status--line order__status--<?=$statusClass?> xpopover-toggle"<?=($bShowStatusPopup ? $orderStatusPopover->showToggleAttrs() : '')?>>
						<div class="order__status__text flexbox flexbox--row flexbox--align-center">
							<div class="order__status__icon"><?=TSolution::showSpriteIconSvg($svgStatusSprite.'#'.$statusClass.'-16-16', 'status fill-theme', ['WIDTH' => 16, 'HEIGHT' => 16]);?></div>
							<?if ($bCanceled):?>
								<div class="order__status__value font_13"><?=Loc::getMessage('SPOD_CANCELED')?></div>
							<?else:?>
								<?if ($bShowStatusPopup):?>
									<a class="order__status__value dotted font_13"><?=$arStatuses[$arResult['STATUS_ID']]['LAST_VISIBLE']['NAME']?></a>
								<?else:?>
									<div class="order__status__value"><?=$arStatuses[$arResult['STATUS_ID']]['LAST_VISIBLE']['NAME']?></div>
								<?endif;?>
							<?endif;?>
						</div>

						<?if ($bShowStatusPopup):?>
							<div class="order__status__steps">
								<?
								$bMark = true;
								?>
								<?foreach ($arVisibleStatuses as $statusId => $arStatus):?>
									<div class="order__status__step<?=($bMark ? ' mark' : '')?>" title="<?=htmlspecialcharsbx($arStatus['NAME'])?>"></div>
									<?
									if (
										$bMark &&
										$statusId === $arStatuses[$arResult['STATUS_ID']]['LAST_VISIBLE']['ID']
									) {
										// do not mark next steps
										$bMark = false;
									}
									?>
								<?endforeach;?>
							</div>

							<?$orderStatusPopover->showContent($arResult, $statusClass);?>
						<?endif;?>
					</div>

					<?if (strlen($deliveryInfo)):?>
						<div class="order__delivery-status font_14 color_dark">
							<?=$deliveryInfo?>
						</div>
					<?endif;?>
				</div>

				<?if ($arResult['ORDER_PROPS']):?>
					<?$cntItems = 0;?>
					<?ob_start();?>
						<?foreach ($arResult['ORDER_PROPS'] as $property):?>
							<?
							$value = '';

							if ($property['TYPE'] == 'Y/N') {
								$value = Loc::getMessage('SPOD_' . ($property['VALUE'] === 'Y' ? 'YES' : 'NO'));
							}
							else {
								if (
									$property['MULTIPLE'] === 'Y' &&
									$property['TYPE'] !== 'FILE' &&
									$property['TYPE'] !== 'LOCATION'
								) {
									$propertyList = unserialize($property['VALUE'], ['allowed_classes' => false]);

									foreach ($propertyList as $propertyElement) {
										$value .= htmlspecialcharsbx($propertyElement).'</br>';
									}
								}
								elseif ($property['TYPE'] == 'FILE') {
									$value = $property['VALUE'];
								}
								else {
									$value = htmlspecialcharsbx($property['VALUE']);
								}
							}
							?>
							<?if (strlen($value)):?>
								<?++$cntItems;?>
								<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
									<div class="order__info__title font_13 secondary-color"><?=htmlspecialcharsbx($property['NAME'])?></div>
									<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=$value?></div>
								</div>
							<?endif;?>
						<?endforeach;?>

						<?if (strlen($arResult['USER_DESCRIPTION'])):?>
							<?++$cntItems;?>
							<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
								<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_ORDER_DESC')?></div>
								<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=nl2br(htmlspecialcharsbx($arResult['USER_DESCRIPTION']))?></div>
							</div>
						<?endif;?>
					<?$html = trim(ob_get_clean());?>

					<?if (strlen($html)):?>
						<div class="order__info order__info--props bordered outer-rounded-x" id="order__props">
							<div class="order__info__caption">
								<?/*
								<div class="order__info__icon">
									<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#customer-16-14', 'stroke-dark-light', ['WIDTH' => 16, 'HEIGHT' => 14]);?>
								</div>
								*/?>
								<div class="order__info__heading fw-500 font_18 color_dark">
									<?=Loc::getMessage('SPOD_CUSTOMER')?>
								</div>
							</div>

							<div class="order__info__items order__info__items--columns order__info__items--toggled mt mt--20"><?=$html?></div>

							<?if ($cntItems > 2):?>
								<a class="order__info__items-toggle mt mt--20 font_13 color_dark flexbox--inline dotted<?=($cntItems > 4 ? ' a4' : '')?>"><?=Loc::getMessage('SPOD_TOGGLE_OPEN')?></a>
							<?endif;?>
						</div>
					<?endif;?>
				<?endif;?>

				<?if ($arResult['SHIPMENT']):?>
					<?
					$shipmentCollection = $order->getShipmentCollection();

					foreach ($arResult['SHIPMENT'] as $i => $shipment) {
						if (
							!$shipment['DELIVERY_ID'] ||
							(
								$emptyDeliveryServiceId &&
								$shipment['DELIVERY_ID'] == $emptyDeliveryServiceId
							)
						) {
							unset($arResult['SHIPMENT'][$i]);
						}
					}
					?>
					<?$i = 0;?>
					<?foreach ($arResult['SHIPMENT'] as $shipment):?>
						<?
						$bShipmentDeducted = $shipment['DEDUCTED'] === 'Y';

						$store = $arResult['DELIVERY']['STORE_LIST'][$shipment['STORE_ID']] ?? [];
						$bShowStore = $store && is_array($store) && strlen($store['ADDRESS']);
						$bShowMap = $bShowStore && $store['GPS_S'] && $store['GPS_N'];

						// get extra services
						$oShipment = $shipmentCollection->getItemById($shipment['ID']);
						$deliveryExtraService = $oShipment->getExtraServices();
						?>
						<div class="order__info order__info--delivery bordered outer-rounded-x" <?=($i ? '' : ' id="order__shipments"')?>>
							<?++$i;?>
							<div class="order__info__caption">
								<?/* 
								<div class="order__info__icon">
									<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#delivery-16-15', 'stroke-dark-light', ['WIDTH' => 16, 'HEIGHT' => 15]);?>
								</div> 
								*/?>

								<div class="order__info__heading fw-500 font_18 color_dark">
									<?if (count($arResult['SHIPMENT']) > 1):?>
										<?=Loc::getMessage('SPOD_DELIVERY_TITLE', ['#ID#' => $shipment['ACCOUNT_NUMBER'] ?: $shipment['ID']])?>
									<?else:?>
										<?=Loc::getMessage('SPOD_DELIVERY_TYPE')?>
									<?endif;?>
								</div>

								<?// shipment status?>
								<?if (!$bCanceled):?>
									<div class="order__info__status order__shipment-status<?=($bShipmentDeducted ? ' personal-color--green' : ' personal-color--red')?>">
										<?if ($bShipmentDeducted):?>
											<?=Loc::getMessage('SPOD_SHIPMENT_DEDUCTED')?>
										<?else:?>
											<?=Loc::getMessage('SPOD_SHIPMENT_NOTDEDUCTED')?>
											<?//=htmlspecialcharsbx($shipment['STATUS_NAME'])?>
										<?endif;?>
									</div>
								<?endif;?>
							</div>

							<?$cntItems = 0;?>
							<div class="order__info__items order__info__items--columns order__info__items--toggled mt mt--20">

								<?// shipment name?>
								<?++$cntItems;?>
								<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
									<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_DELIVERY_SERVICE')?></div>
									<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=htmlspecialcharsbx($shipment['DELIVERY_NAME'])?></div>
								</div>

								<?// shipment price?>
								<?++$cntItems;?>
								<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
									<?if ($shipment['PRICE_DELIVERY']):?>
										<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_DELIVERY_SUM')?></div>
										<div class="order__info__value order__info__value--price">
											<?
											(new TSolution\Product\Prices(
												[],
												[
													'SHOW_DISCOUNT_PERCENT' => 'N',
												],
												[
													'SHOW_SCHEMA' => false,
													'PRICES' => [
														'VALUE' => $shipment['PRICE_DELIVERY_FORMATTED'],
														'DISCOUNT_VALUE' => $shipment['PRICE_DELIVERY_FORMATTED'],
														'PRICE_CURRENCY' => $shipment['CURRENCY'],
													],
												]
											))->show();
											?>
										</div>
									<?endif;?>
								</div>

								<?// shipment tracking?>
								<?if (strlen($shipment['TRACKING_NUMBER'])):?>
									<?++$cntItems;?>
									<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
										<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_ORDER_TRACKING_NUMBER')?></div>
										<div class="order__info__value word-break mt mt--2 font_14 color_dark">
											<?if (strlen($shipment['TRACKING_URL'])):?>
												<a class="check-tracking font_13" href="<?=$shipment['TRACKING_URL']?>" target="_blank" rel="nofollow"><?=htmlspecialcharsbx($shipment['TRACKING_NUMBER'])?></a>
											<?else:?>
												<span><?=htmlspecialcharsbx($shipment['TRACKING_NUMBER'])?></span>
											<?endif;?>
										</div>
									</div>
								<?endif;?>

								<?// shipment store address & map?>
								<?if ($bShowStore):?>
									<?++$cntItems;?>
									<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
										<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_SHIPMENT_STORE')?></div>
										<div class="order__info__value word-break mt mt--2 font_14 color_dark">
											<div class="order__info__value__address">
												<?if ($bShowMap):?>
													<span class="show_on_map">
														<span class="text_wrap font_13 color-theme">
															<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#showonmap-16-14', 'on_map fill-theme', ['WIDTH' => 10, 'HEIGHT' => 14]);?>
															<span class="text dotted">
																<?if (strlen($store['ADDRESS'])):?>
																	<?=htmlspecialcharsbx($store['ADDRESS'])?>
																<?else:?>
																	<?=Loc::getMessage('SPOD_STORE_SHOW_ON_MAP');?>
																<?endif;?>
															</span>
														</span>
													</span>
												<?else:?>
													<?if (strlen($store['ADDRESS'])):?>
														<span><?=htmlspecialcharsbx($store['ADDRESS'])?></span>
													<?endif;?>
												<?endif;?>
											</div>
										</div>
									</div>

									<?if ($bShowMap):?>
										<div class="order__info__item order__info__item--wide<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?> store__map-wrapper hidden">
											<div class="store__map bordered outer-rounded-x">
												<?$APPLICATION->IncludeComponent(
													"bitrix:map.yandex.view",
													"map",
													Array(
														"API_KEY" => \Bitrix\Main\Config\Option::get('fileman', 'yandex_map_api_key', ''),
														"INIT_MAP_TYPE" => "MAP",
														"COMPONENT_TEMPLATE" => "map",
														"COMPOSITE_FRAME_MODE" => "A",
														"COMPOSITE_FRAME_TYPE" => "AUTO",
														"CONTROLS" => array(
															0 => "ZOOM",
															1 => "SMALLZOOM",
															2 => "TYPECONTROL",
														),
														"OPTIONS" => array(
															0 => "ENABLE_DBLCLICK_ZOOM",
															1 => "ENABLE_DRAGGING",
														),
														"MAP_DATA" => serialize(
															array(
																'yandex_lon' => $store['GPS_S'],
																'yandex_lat' => $store['GPS_N'],
																'yandex_scale' => 17,
																'PLACEMARKS' => array(
																	array(
																		"LON" => $store['GPS_S'],
																		"LAT" => $store['GPS_N'],
																		"TEXT" => htmlspecialcharsbx($store['TITLE'].(strlen($store['ADDRESS']) ? ', '.$store['ADDRESS'] : ''))
																	)
																)
															)
														),
														"MAP_WIDTH" => "100%",
														"MAP_HEIGHT" => "300",
														"MAP_ID" => "",
														"ZOOM_BLOCK" => array(
															"POSITION" => "right center",
														)
													),
													false
												);?>
											</div>
										</div>
									<?endif;?>
								<?endif;?>

								<?// shipment products?>
								<?if (
									$shipment['ITEMS'] &&
									count($arResult['SHIPMENT']) > 1
								):?>
									<?++$cntItems;?>
									<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
										<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_SHIPMENT_PRODUCTS')?></div>
										<div class="order__info__value word-break mt mt--2 font_14 color_dark">
											<a href="" class="js-show-shipment-products">
												<span class="text_wrap font_13 color-theme">
													<span class="text dotted"><?=Loc::getMessage('SPOD_SHIPMENT_PRODUCTS_COUNT', ['#COUNT#' => count($shipment['ITEMS'])])?></span>
												</span>
											</a>
										</div>
									</div>

									<div class="order__info__item order__info__item--wide<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?> order__cart__items bordered outer-rounded-x hidden">
										<?foreach ($shipment['ITEMS'] as $arItem):?>
											<?
											$productTitle = htmlspecialcharsbx(str_replace(['&#8381;', '&nbsp;'], [Loc::getMessage('SPOD_RUB'), ' '], $arItem['NAME']));
											$productMeasure = htmlspecialcharsbx($arItem['MEASURE_NAME'] ?: Loc::getMessage('SPOD_DEFAULT_MEASURE'));
											$productQuantity = $arItem['QUANTITY'];
											$productImg = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
											$productUrl = '';

											if (isset($arResult['BASKET'][$arItem['BASKET_ID']])) {
												$productId = $arResult['BASKET'][$arItem['BASKET_ID']]['PRODUCT_ID'];
												$productUrl = $arProductsUrls[$productId] ?? $arResult['BASKET'][$arItem['BASKET_ID']]['DETAIL_PAGE_URL'];

												if ($imgId = isset($arProducts[$productId]) ? ($arProducts[$productId]['PREVIEW_PICTURE'] ?: $arProducts[$productId]['DETAIL_PICTURE']) : false) {
													$arImg = \CFile::ResizeImageGet($imgId, ['width' => 56, 'height' => 56], BX_RESIZE_IMAGE_PROPORTIONAL, true);
													$productImg = $arImg['src'];
												}
												else {
													if ($arResult['BASKET'][$arItem['BASKET_ID']]['PICTURE']['SRC']) {
														$productImg = $arResult['BASKET'][$arItem['BASKET_ID']]['PICTURE']['SRC'];
													}
												}
											}
											?>
											<div class="order__cart__item">
												<div class="order__cart__item__inner bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all">
													<div class="order__cart__item__image">
														<div class="order__cart__item__image__wrapper">
															<a href="<?=$productUrl?>" class="image-list__link">
																<img src="<?=$productImg?>" data-src="<?=$productImg?>" alt="<?=$productTitle?>" title="<?=$productTitle?>" class="img-responsive rounded-x js-popup-image">
															</a>
														</div>
													</div>
													
													<div class="order__cart__item__body">
														<div class="order__cart__item__left">
															<div class="order__cart__item__name lineclamp-4 font_14 color_dark">
																<a href="<?=$productUrl?>" class="dark_link switcher-title color-theme-target js-popup-title"><?=$productTitle?></a>
															</div>
														</div>

														<div class="order__cart__item__right">
															<div class="order__cart__item__quantity font_14 color_dark line-block">
																<span class="count"><?=$productQuantity?> <?=$productMeasure?></span>
															</div>
														</div>
													</div>
												</div>
											</div>
										<?endforeach;?>
									</div>
								<?endif;?>

								<?// shipment extra services?>
								<?if ($deliveryExtraService):?>
									<?
									$extraServiceManager = new \Bitrix\Sale\Delivery\ExtraServices\Manager($shipment['DELIVERY_ID']);
									$extraServiceManager->setValues($deliveryExtraService);
									$extraService = $extraServiceManager->getItems();
									?>
									<?if ($extraService):?>
										<?foreach ($extraService as $itemId => $item):?>
											<?
											$params = $item->getParams();
											$value = $item->getValue();
											$description = $item->getDescription();
											?>
											<?if (
												(
													$params['TYPE'] === 'Y/N' &&
													$value === 'Y'
												) ||
												(
													$params['TYPE'] === 'STRING' &&
													$value
												) ||
												(
													$params['TYPE'] === 'ENUM' &&
													$value &&
													isset($params['PRICES'][$value]) &&
													$params['PRICES'][$value]['PRICE'] > 0
												)
											):?>
												<?++$cntItems;?>
												<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
													<div class="order__info__title font_13 secondary-color">
														<?=$item->getName()?>

														<?if (strlen($description)):?>
															<?$descPopover = new TSolution\Popover\Tooltip();?>
															<span class="order__info__description xpopover-toggle fill-grey-hover" <?$descPopover->showToggleAttrs()?>>
																<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#description-16-16', '', ['WIDTH' => 16,'HEIGHT' => 16]);?>

																<?$descPopover->showContent($description);?>
															</span>
														<?endif;?>
														
													</div>
													<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=$item->getDisplayValue()?></div>
												</div>
											<?endif;?>
										<?endforeach;?>
									<?endif;?>
								<?endif;?>
							</div>

							<?if ($cntItems > 2):?>
								<a class="order__info__items-toggle mt mt--20 font_13 color_dark flexbox--inline dotted<?=($cntItems > 4 ? ' a4' : '')?>"><?=Loc::getMessage('SPOD_TOGGLE_OPEN')?></a>
							<?endif;?>
						</div>
					<?endforeach;?>
				<?endif;?>

				<?if ($arResult['PAYMENT']):?>
					<?$i = 0;?>
					<?foreach ($arResult['PAYMENT'] as $payment):?>
						<?
						if (!$payment['PAY_SYSTEM_ID']) {
							continue;
						}

						$bPaymentPaid = $payment['PAID'] === 'Y';
						$bCashPayment = $payment['PAY_SYSTEM']['IS_CASH'] === 'Y' || $payment['PAY_SYSTEM']['ACTION_FILE'] === 'cash';
						$bInnerPayment = $payment['PAY_SYSTEM']['ACTION_FILE'] === 'inner';
						$bChangeablePayment = !$bPaymentPaid && !$bCanceled && !$bGuest && $arResult['LOCK_CHANGE_PAYSYSTEM'] !== 'Y';

						$bPayment2Pay = !$bPaymentPaid && $bAllowPay && !$bCashPayment;
						if ($bPayment2Pay) {
							++$cntPayments2Pay;
						}


						$paymentTitle = Loc::getMessage('SPOD_PAYMENT_TITLE', ['#ID#' => $payment['ACCOUNT_NUMBER'] ?: $payment['ID']]);
						// if (isset($payment['DATE_BILL'])) {
						// 	$paymentTitle .= ' '.Loc::getMessage('SPOD_FROM').' '.$payment['DATE_BILL_FORMATED'];
						// }
						?>
						<div class="order__info order__info--payment bordered outer-rounded-x"<?=($i ? '' : ' id="order__payments"')?>>
							<?++$i;?>
							<div class="order__info__caption">
								<?/*
								<div class="order__info__icon">
									<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#payment-16-16', 'stroke-dark-light', ['WIDTH' => 16, 'HEIGHT' => 16]);?>
								</div>
								*/?>
								<div class="order__info__heading fw-500 font_18 color_dark">
									<?if (count($arResult['PAYMENT']) > 1):?>
										<?=$paymentTitle?>
									<?else:?>
										<?=Loc::getMessage('SPOD_PAYMENT_TYPE')?>
									<?endif;?>
								</div>

								<?// payment status?>
								<?if (!$bCanceled):?>
									<div class="order__info__status order__pay-status<?=($bPaymentPaid ? ' personal-color--green' : ' personal-color--red')?>">
										<?=Loc::getMessage($bPaymentPaid ? 'SPOD_PAYMENT_PAID' : ($bAllowPay ? 'SPOD_PAYMENT_NOTPAID' : 'SPOD_PAYMENT_RESTRICTED_PAID'))?>
									</div>
								<?endif;?>
							</div>

							<?$cntItems = 0;?>
							<div class="order__info__items order__info__items--columns order__info__items--toggled mt mt--20">

								<?// payment name?>
								<?++$cntItems;?>
								<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
									<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_PAYSYSTEM_SERVICE')?></div>
									<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=htmlspecialcharsbx($payment['PAY_SYSTEM_NAME'])?></div>
								</div>

								<?// payment price?>
								<?if ($payment['SUM']):?>
									<?++$cntItems;?>
									<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
										<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_PAYMENT_SUM')?></div>
										<div class="order__info__value order__info__value--price">
											<?
											(new TSolution\Product\Prices(
												[],
												[
													'SHOW_DISCOUNT_PERCENT' => 'N',
												],
												[
													'SHOW_SCHEMA' => false,
													'PRICES' => [
														'VALUE' => $payment['PRICE_FORMATED'],
														'DISCOUNT_VALUE' => $payment['PRICE_FORMATED'],
														'PRICE_CURRENCY' => $payment['CURRENCY'],
													],
												]
											))->show();
											?>
										</div>
									</div>
								<?endif;?>

								<?// payment checks?>
								<?if ($payment['CHECK_DATA']):?>
									<?
									$listCheckLinks = [];
									foreach ($payment['CHECK_DATA'] as $checkInfo) {
										$title = Loc::getMessage('SPOD_CHECK_NUM', array('#CHECK_NUMBER#' => $checkInfo['ID'])).' - '. htmlspecialcharsbx($checkInfo['TYPE_NAME']);

										if (strlen($checkInfo['LINK'])) {
											$link = $checkInfo['LINK'];
											$listCheckLinks[] = '<span><a class="font_13" href="'.$link.'" target="_blank">'.$title.'</a></span>';
										}
									}
									?>

									<?if ($listCheckLinks):?>
										<?++$cntItems;?>
										<div class="order__info__item<?=($cntItems > 4 ? ' a4' : ($cntItems > 2 ? ' a2' : ''))?>">
											<div class="order__info__title font_13 secondary-color"><?=Loc::getMessage('SPOD_CHECK_TITLE')?></div>
											<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=implode(', ', $listCheckLinks)?></div>
										</div>
									<?endif;?>
								<?endif;?>
							</div>

							<?if ($cntItems > 2):?>
								<a class="order__info__items-toggle mt mt--20 font_13 color_dark flexbox--inline dotted<?=($cntItems > 4 ? ' a4' : '')?>"><?=Loc::getMessage('SPOD_TOGGLE_OPEN')?></a>
							<?endif;?>

							<?// payment buttons?>
							<?if (!$bInnerPayment && ($bPayment2Pay || $bChangeablePayment)):?>
								<div class="order__info__items">
									<div class="order__info__item">
										<div class="pt pt--20 line-block line-block--gap line-block--gap-12">
											<?if ($bPayment2Pay):?>
												<?if ($payment['PAY_SYSTEM']['PSA_NEW_WINDOW'] === 'Y'):?>
													<a href="<?=htmlspecialcharsbx($payment['PAY_SYSTEM']['PSA_ACTION_FILE'])?>" target="_blank" class="btn btn-default btn-xs"><?=Loc::getMessage('SPOD_PAYMENT_PAY')?></a>
												<?else:?>
													<span class="btn btn-default btn-xs js-pay-payment" data-title="<?=htmlspecialcharsbx($paymentTitle)?>"><?=Loc::getMessage('SPOD_PAYMENT_PAY')?></span>
													<template class="order__info__payment-template"><?=preg_replace('/<script\s/i', '$0data-skip-moving="true"', $payment['BUFFERED_OUTPUT'])?></template>
												<?endif;?>
											<?endif;?>

											<?if ($bChangeablePayment):?>
												<?
												$parentComponent = $this->__component->__parent;
												$dataParams = [
													'PARENT_COMPONENT' => $parentComponent ? $parentComponent->__name : '',
													'PARENT_COMPONENT_TEMPLATE' => $parentComponent ? $parentComponent->__template->__name : '',
													'PARENT_COMPONENT_PAGE' => $parentComponent ? $parentComponent->__templatePage : '',

													'ACCOUNT_NUMBER' => urlencode($arResult['ACCOUNT_NUMBER']),
													'PAYMENT_NUMBER' => urlencode($payment['ACCOUNT_NUMBER']),
													'PATH_TO_PAYMENT' => urlencode($arParams['PATH_TO_PAYMENT']),
													'REFRESH_PRICES' => $arParams['REFRESH_PRICES'],
													'RETURN_URL' => urlencode($arResult['RETURN_URL']),
													'ONLY_INNER_FULL' => $arParams['ONLY_INNER_FULL'],
													'ALLOW_INNER' => $arParams['ALLOW_INNER'],
												];

												$dataParams = $GLOBALS['APPLICATION']->ConvertCharsetArray($dataParams, SITE_CHARSET, 'UTF-8');
												$dataParams = json_encode($dataParams);
												$dataParams = htmlspecialcharsbx($dataParams);
												?>
												<span class="btn btn-default btn-xs btn-secondary-black js-change-payment" data-event="jqm" data-name="change_payment" data-param-form_id="change_payment" data-param-params="<?=$dataParams?>"><?=Loc::getMessage('SPOD_PAYMENT_CHANGE')?></span>
											<?endif;?>
										</div>
									</div>
								</div>
							<?endif;?>
						</div>
					<?endforeach;?>
				<?endif;?>

				<?if ($arResult['BASKET']):?>
					<div class="order__cart__items bordered outer-rounded-x" id="order__products">
						<?foreach ($arBasketItems as $i => $arItem):?>
							<?
							$productId = $arItem['PRODUCT_ID'];
							$bInnerPayment = $arItem['MODULE'] == 'sale'; // inner payment
							$productTitle = htmlspecialcharsbx(str_replace(['&#8381;', '&nbsp;'], [Loc::getMessage('SPOD_RUB'), ' '], $arItem['NAME']));
							$productMeasure = htmlspecialcharsbx($arItem['MEASURE_NAME'] ?: Loc::getMessage('SPOD_DEFAULT_MEASURE'));

							$productImg = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
							if ($imgId = isset($arProducts[$productId]) ? ($arProducts[$productId]['PREVIEW_PICTURE'] ?: $arProducts[$productId]['DETAIL_PICTURE']) : false) {
								$arImg = \CFile::ResizeImageGet($imgId, ['width' => 56, 'height' => 56], BX_RESIZE_IMAGE_PROPORTIONAL, true);
								$productImg = $arImg['src'];
							}

							$productUrl = $arProductsUrls[$productId] ?? $arItem['DETAIL_PAGE_URL'];

							$dataItem = TSolution::getDataItem([
								'ID' => $productId,
								'IBLOCK_ID' => isset($arProducts[$productId]) ? $arProducts[$productId]['IBLOCK_ID'] : false,
								'NAME' => $productTitle,
								'DETAIL_PAGE_URL' => $productUrl,
								'PREVIEW_PICTURE' => $productImg,
								'DETAIL_PICTURE' => $productImg,
							]);
							?>
							<div class="order__cart__item js-popup-block">
								<div class="order__cart__item__inner bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all" data-id="<?=$productId?>" data-item="<?=$dataItem?>">
									<div class="order__cart__item__image">
										<div class="order__cart__item__image__wrapper">
											<?if ($productUrl):?>
												<a href="<?=$productUrl?>" class="image-list__link">
											<?else:?>
												<span class="image-list__link">
											<?endif;?>
												<img src="<?=$productImg?>" data-src="<?=$productImg?>" alt="<?=$productTitle?>" title="<?=$productTitle?>" class="img-responsive rounded-x js-popup-image" />
											<?if ($productUrl):?>
												</a>
											<?else:?>
												</span>
											<?endif;?>
										</div>
									</div>

									<div class="order__cart__item__body">
										<div class="order__cart__item__left">
											<div class="order__cart__item__name lineclamp-4 font_14">
												<?if ($productUrl):?>
													<a href="<?=$productUrl?>" class="dark_link switcher-title color-theme-target js-popup-title"><?=$productTitle?></a>
												<?else:?>
													<span class="switcher-title js-popup-title"><?=$productTitle?></span>
												<?endif;?>
											</div>

											<?if (isset($arItem['PROPS']) && is_array($arItem['PROPS'])):?>
												<?
												foreach ($arItem['PROPS'] as $j => $itemProps) {
													if (
														in_array(
															$itemProps['CODE'],
															[
																'IN_STOCK',
																'FORM_ORDER',
																'PRICE_CURRENCY',
																'STATUS',
																'BNR_TOP_COLOR',
																'WB_STATUS',
															]
														)
													) {
														unset($arItem['PROPS'][$j]);
													}
												}
												?>
											<?endif;?>

											<?
											if (
												isset($arResult['PROPERTY_DESCRIPTION']) && 
												is_array($arResult['PROPERTY_DESCRIPTION'])
											) {
												foreach ($arResult['PROPERTY_DESCRIPTION'] as $iblockId => $arProperties) {
													foreach ($arProperties as $propCode => $arProperty) {
														if (
															isset($arItem[$propCode.'_VALUE']) &&
															strlen($arItem[$propCode.'_VALUE']) &&
															isset($arItem[$propCode.'_VALUE_ID']) &&
															$arItem[$propCode.'_VALUE_ID']
														) {
															$arItem['PROPS'][] = [
																'ID' => $arItem[$propCode.'_VALUE_ID'],
																'NAME' => $arProperty['NAME'],
																'VALUE' => $arItem[$propCode.'_VALUE'],
																'CODE' => $propCode,
															];
														}
													}
												}
											}
											?>

											<?if ($arItem['PROPS'] && is_array($arItem['PROPS'])):?>
												<div class="order__info__items">
													<?foreach ($arItem['PROPS'] as $itemProps):?>
														<div class="order__info__item">
															<div class="order__info__title font_13 secondary-color"><?=htmlspecialcharsbx($itemProps['NAME'])?>:</div>
															<div class="order__info__value word-break mt mt--2 font_14 color_dark"><?=htmlspecialcharsbx($itemProps['VALUE'])?></div>
														</div>
													<?endforeach;?>
												</div>
											<?endif;?>
										</div>

										<div class="order__cart__item__right">
											<div class="order__cart__item__quantity font_14 color_dark line-block line-block--gap line-block--gap-8">
												<?// product price?>
												<?
												(new TSolution\Product\Prices(
													[],
													[
														'SHOW_DISCOUNT_PERCENT' => 'N',
														'SHOW_OLD_PRICE' => 'Y',
													],
													[
														'SHOW_SCHEMA' => false,
														'PRICE_FONT' => 14,
														'PRICE_WEIGHT' => 400,
														'PRICES' => [
															'VALUE' => $arItem['BASE_PRICE_FORMATED'],
															'DISCOUNT_VALUE' => $arItem['PRICE_FORMATED'],
															'PRICE_CURRENCY' => $arItem['CURRENCY'],
														],
													]
												))->show();
												?>
												<span class="count">x <?=$arItem['QUANTITY']?> <?=$productMeasure?></span>
											</div>

											<div class="order__cart__item__total">
												<?// total price?>
												<?
												(new TSolution\Product\Prices(
													[],
													[
														'SHOW_DISCOUNT_PERCENT' => 'N',
													],
													[
														'SHOW_SCHEMA' => false,
														'PRICES' => [
															'VALUE' => $arItem['FORMATED_SUM'],
															'DISCOUNT_VALUE' => $arItem['FORMATED_SUM'],
															'PRICE_CURRENCY' => $arItem['CURRENCY'],
														],
													]
												))->show();
												?>
											</div>
										</div>
									</div>

									<?if (
										$productId &&
										!$bInnerPayment &&
										$bShowFavorit
									):?>
										<div class="order__cart__item__actions">
											<?=\TSolution\Product\Common::getActionIcon([
												'ITEM' => [
													'ID' => $productId,
												],
												'PARAMS' => $arParams,
												'WRAPPER_ICON' => 'favorite_white',
												'ACTIVE_ICON' => 'favorite_active',
												'CLASS' => 'sm',
												'SVG_SIZE' => ['WIDTH' => 20,'HEIGHT' => 20],
												'ORIENT' => 'vertical',
											])?>
										</div>
									<?endif;?>
								</div>
							</div>
						<?endforeach;?>
					</div>
				<?endif;?>
			</div>

			<div class="order-pane__col order-pane__col--aside sticky-block">
				<?if (!$bCanceled):?>
					<div class="order__bar<?=($bPayed ? ' order__bar--green personal-color--green' : ' order__bar--red personal-color--red')?> outer-rounded-x">
						<div class="order__pay-status fw-500">
							<?=Loc::getMessage($bPayed ? 'SPOD_PAID' : ($bAllowPay ? 'SPOD_NOTPAID' : 'SPOD_RESTRICTED_PAID'))?>
						</div>
					</div>
				<?endif;?>

				<div class="order__card outer-rounded-x p p--24">
				<div class="order__card__list line-block line-block--column line-block--align-normal line-block--gap line-block--gap-6 font_13 pb pb--16 border-bottom">
						<div class="order__card__item">
							<div class="order__card__product secondary-color"><?=Loc::getMessage('SPOD_GOODS', ['#COUNT#' => count($arResult['BASKET'])])?></div>
							<div class="order__card__price"><?=$arResult['PRODUCT_SUM_FORMATED']?></div>
						</div>

						<?if ($arResult['PRICE_DELIVERY']):?>
							<div class="order__card__item">
								<div class="order__card__product secondary-color"><?=Loc::getMessage('SPOD_DELIVERY')?></div>
								<div class="order__card__price"><?=$arResult['PRICE_DELIVERY_FORMATED']?></div>
							</div>
						<?endif;?>

						<?if (
							isset($arResult['SUM_REST']) &&
							$arResult['SUM_REST'] > 0 &&
							isset($arResult['SUM_PAID']) &&
							$arResult['SUM_PAID'] > 0
						):?>
							<div class="order__card__item">
								<div class="order__card__product secondary-color"><?=Loc::getMessage('SPOD_ORDER_SUM_PAID')?></div>
								<div class="order__card__price"><?=$arResult['SUM_PAID_FORMATED']?></div>
							</div>

							<div class="order__card__item">
								<div class="order__card__product secondary-color"><?=Loc::getMessage('SPOD_ORDER_SUM_REST')?></div>
								<div class="order__card__price"><?=$arResult['SUM_REST_FORMATED']?></div>
							</div>
						<?endif;?>
					</div>

					<div class="order__card__total pt pt--16 line-block line-block--justify-between line-block--gap line-block--gap--16 font_24 fw-500 color_dark">
						<span><?=Loc::getMessage('SPOD_TOTAL')?></span>
						<span class="order__card__cost"><?=$arResult['PRICE_FORMATED']?></span>
					</div>

					<div class="order__card__buttons pt pt--16 line-block line-block--column line-block--align-normal line-block--gap line-block--gap--12">
						<?if ($cntPayments2Pay):?>
							<a href="#order__payments" class="btn btn-elg btn-default order__card__button order__card__button--pay"><?=loc::getMessage('SPOD_ORDER_PAY')?></a>
						<?endif;?>

						<a href="<?=$arResult['URL_TO_COPY']?>" class="btn btn-lg btn-default btn-transparent-bg order__card__button order__card__button--copy"><?=Loc::getMessage('SPOD_ORDER_COPY')?></a>
					</div>
				</div>

				<?if ($arResult['CAN_CANCEL'] === 'Y'):?>
					<div class="order__card__button--cancel__wrapper">
						<a href="<?=$arResult['URL_TO_CANCEL']?>" class="order__card__button order__card__button--cancel no-decoration secondary-to-title-hover"><?=Loc::getMessage('SPOD_ORDER_CANCEL')?></a>
					</div>
				<?endif;?>
			</div>
		</div>

		<script>
		BX.message({
			SPOD_TOGGLE_OPEN: '<?=Loc::getMessage('SPOD_TOGGLE_OPEN')?>',
			SPOD_TOGGLE_CLOSE: '<?=Loc::getMessage('SPOD_TOGGLE_CLOSE')?>',
		});

		new BX.Sale.PersonalOrderComponent.PersonalOrderDetail(
			'#<?=$uniqId?>',
			<?=CUtil::PhpToJSObject([
				'id' => $arResult['ID'],
			])?>
		);
		</script>
	<?endif;?>
</div>