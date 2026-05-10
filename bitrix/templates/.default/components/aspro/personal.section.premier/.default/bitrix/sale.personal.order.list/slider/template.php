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

$bShowDetailLink = ($arParams['SHOW_DETAIL_LINK'] ?? 'Y') !== 'N';

$arParams['PATH_TO_ORDERS'] = $arParams['PATH_TO_ORDERS'] ?? '';
$bShowAllLink = ($arParams['SHOW_ALL_LINK'] ?? 'N') === 'Y' && strlen($arParams['PATH_TO_ORDERS']);
?>
<?if ($arResult['ERRORS']['FATAL']):?>
	<?foreach($arResult['ERRORS']['FATAL'] as $error):?>
		<?//ShowError($error);?>
	<?endforeach;?>

	<?if ($arParams['AUTH_FORM_IN_TEMPLATE'] && isset($arResult['ERRORS']['FATAL'][$this->__component::E_NOT_AUTHORIZED])):?>
		<?//$APPLICATION->AuthForm('', false, false, 'N', false);?>
	<?endif;?>
<?else:?>
	<?if ($arResult['ERRORS']['NONFATAL']):?>
		<?foreach($arResult['ERRORS']['NONFATAL'] as $error):?>
			<?//ShowError($error);?>
		<?endforeach;?>
	<?endif;?>

	<?if ($arResult['ORDERS']):?>
		<?
		$countSlides = count($arResult['ORDERS']);
		$arOptions = [
			// Disable preloading of all images
			'preloadImages' => false,
			// Enable lazy loading
			'lazy' => false,
			'keyboard' => true,
			'init' => false,
			'loop' => false,
			'countSlides' => $countSlides + ($bShowAllLink ? 1 : 0),
			'slidesPerView' => 'auto',
			'freeMode' => [
				'enabled' => true,
				'momentum' => true,
				'sticky' => true,
			],
			'spaceBetween' => 12,
			// 'rewind' => true,
			'pagination' => false,
			'watchSlidesProgress' => true, // fix slide on click on slide link in mobile template
			'breakpoints' => [
				601 => [
					'slidesPerView' => $bShowAllLink ? 'auto' : 2,
					'spaceBetween' => 24,
				],
			],
		];

		$arStatuses = $arVisibleStatuses = [];
		$arLastVisibleStatus = false;
		$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
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
		$svgSprite = $this->__folder.'/images/svg/icons.svg';

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

		$arProductsIDs = $arProductsImagesIds = $arOffersIdsWithoutImages = $arProductsUrls = [];
		foreach ($arResult['ORDERS'] as $arOrder) {
			$arProductsIDs = array_merge($arProductsIDs, array_column($arOrder['BASKET_ITEMS'], 'PRODUCT_ID'));
		}

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
					$arProductsImagesIds[$arItem['ID']] = $arItem;
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
								$arProductsImagesIds[$offerId] = $arItem;
							}
						}
					}
				}
			}
		}

		$orderStatusPopover = new TSolution\Popover\OrderStatus($svgStatusSprite, $arStatuses, $arVisibleStatuses);
		?>
		<div class="orders--slider__wrap swiper-nav-offset">
			<div class="swiper slider-solution mobile-offset mobile-offset--right orders--slider outer-rounded-x<?=($bShowAllLink ? ' swiper--hidden-on-resize' : '')?>" data-plugin-options='<?=json_encode($arOptions)?>'>
				<div class="swiper-wrapper grid-list--fill-bg">
					<?foreach ($arResult['ORDERS'] as $arOrder):?>
						<?
						$arBasketItems = array_values($arOrder['BASKET_ITEMS']); // reset keys

						$bCanceled = $arOrder['ORDER']['CANCELED'] === 'Y';
						$bPayed = $arOrder['ORDER']['PAYED'] === 'Y';
						$bAllowPay = !$bCanceled && $arOrder['ORDER']['IS_ALLOW_PAY'] === 'Y';
						$bDeducted = $arOrder['ORDER']['DEDUCTED'] === 'Y';

						$deliveryInfo = '';
						if (!$bCanceled && !$bDeducted) {
							if ($deliveryInfoPropertyId = $arParams['DELIVERY_INFO_PROP_'.$arOrder['ORDER']['PERSON_TYPE_ID']] ?? '') {
								$order = Sale\Order::load($arOrder['ORDER']['ID']);
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

						$title = Loc::getMessage('SPOL_TPL_TITLE', [
							'#DATE#' => $arOrder['ORDER']['DATE_INSERT_FORMATED'],
						]);
						?>
						<div class="swiper-slide orders__order__wrapper grid-list__item">
							<div class="orders__order outer-rounded-x bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all">
								<?if ($bShowDetailLink):?>
									<a class="item-link-absolute" href="<?=$arOrder['ORDER']['URL_TO_DETAIL']?>" title="<?=htmlspecialcharsbx($title)?>"></a>
								<?endif;?>

								<div class="orders__order__inner flexbox flexbox--direction-column">
									<div class="orders__order__tds">
										<div class="orders__order__title">
											<?if ($bShowDetailLink):?>
												<a href="<?=$arOrder['ORDER']['URL_TO_DETAIL']?>" class="dark_link color-theme-target"><?=$title?></a>
											<?else:?>
												<span><?=$title?></span>
											<?endif;?>
										</div>

										<?if (strlen($deliveryInfo)):?>
											<div class="orders__order__delivery-status">
												<?=$deliveryInfo?>
											</div>
										<?endif;?>
									</div>

									<div class="orders__order__body">
										<div class="orders__order__body-left">
											<div class="orders__order__nps">
												<div class="orders__order__number">
													<?=Loc::getMessage('SPOL_TPL_NUMBER_SIGN').$arOrder['ORDER']['ACCOUNT_NUMBER']?>
												</div>

												<?if (!$bCanceled):?>
													<div class="order__pay-status fw-500<?=($bPayed ? ' personal-color--green' : ' personal-color--red')?>">
														<?=Loc::getMessage($bPayed ? 'SPOL_TPL_PAID' : ($bAllowPay ? 'SPOL_TPL_NOTPAID' : 'SPOL_TPL_RESTRICTED_PAID'))?>
													</div>
												<?endif;?>
											</div>

											<div class="orders__order__sp">
												<?
												$statusClass = 'simple';
												if ($bCanceled) {
													$statusClass = 'canceled';
												}
												elseif ($arLastVisibleStatus['ID'] === $arStatuses[$arOrder['ORDER']['STATUS_ID']]['LAST_VISIBLE']['ID']) {
													$statusClass = 'last';
												}
												elseif ($arStatuses[$arOrder['ORDER']['STATUS_ID']]['LAST_VISIBLE']['COLORED']) {
													$statusClass = 'colored';
												}

												$bShowStatusPopup = $arVisibleStatuses && ($statusClass === 'simple' || $statusClass === 'colored');
												?>
												<div class="order__status order__status--<?=$statusClass?> xpopover-toggle"<?=($bShowStatusPopup ? $orderStatusPopover->showToggleAttrs() : '')?>>
													<div class="order__status__text flexbox flexbox--row flexbox--align-center">
														<div class="order__status__icon"><?=TSolution::showSpriteIconSvg($svgStatusSprite.'#'.$statusClass.'-16-16', 'status fill-theme', ['WIDTH' => 16, 'HEIGHT' => 16]);?></div>
														<?if ($bCanceled):?>
															<div class="order__status__value"><?=Loc::getMessage('SPOL_TPL_CANCELED')?></div>
														<?else:?>
															<?if ($bShowStatusPopup):?>
																<span class="order__status__value dark_link dotted"><?=$arStatuses[$arOrder['ORDER']['STATUS_ID']]['LAST_VISIBLE']['NAME']?></span>
															<?else:?>
																<div class="order__status__value"><?=$arStatuses[$arOrder['ORDER']['STATUS_ID']]['LAST_VISIBLE']['NAME']?></div>
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
																	$statusId === $arStatuses[$arOrder['ORDER']['STATUS_ID']]['LAST_VISIBLE']['ID']
																) {
																	// do not mark next steps
																	$bMark = false;
																}
																?>
															<?endforeach;?>
														</div>

														<?$orderStatusPopover->showContent($arOrder['ORDER'], $statusClass);?>
													<?endif;?>
												</div>
											</div>
										</div>

										<div class="orders__order__body-right">
											<div class="orders__order__products">
												<?for ($i = 2; $i >= 0; --$i):?>
													<?$arItem = $arBasketItems[$i] ?? false;?>
													<div class="orders__order__product<?=($arItem ? '' : ' orders__order__product--empty')?>">
														<?if ($arItem):?>
															<?
															$productId = $arItem['PRODUCT_ID'];
															$productTitle = htmlspecialcharsbx(str_replace(['&#8381;', '&nbsp;'], [Loc::getMessage('SPOL_TPL_RUB'), ' '], $arItem['NAME']));
															$productUrl = $arProductsUrls[$productId] ?? $arItem['DETAIL_PAGE_URL'];

															$imgSrc = SITE_TEMPLATE_PATH.'/images/svg/noimage_product.svg';
															if ($imgId = isset($arProductsImagesIds[$productId]) ? ($arProductsImagesIds[$productId]['PREVIEW_PICTURE'] ?: $arProductsImagesIds[$productId]['DETAIL_PICTURE']) : false) {
																$arImg = \CFile::ResizeImageGet($imgId, ['width' => 56, 'height' => 56], BX_RESIZE_IMAGE_PROPORTIONAL, true);
																$imgSrc = $arImg['src'];
															}
															?>
															
															<?if ($productUrl):?>
																<a href="<?=$productUrl?>" target="_blank">
															<?else:?>
																<span>
															<?endif;?>

																<img class="img-responsive rounded-x" src="<?=$imgSrc?>" alt="<?=$productTitle?>" title="<?=$productTitle?>" />
															
															<?if ($productUrl):?>
																</a>
															<?else:?>
																</span>
															<?endif;?>
														<?endif;?>
													</div>
												<?endfor;?>

												<?if (count($arBasketItems) > 3):?>
													<div class="orders__order__cnt-more-products">+<?=(count($arBasketItems) - 3)?></div>
												<?endif;?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					<?endforeach;?>

					<?if ($bShowAllLink):?>
						<div class="swiper-slide swiper-slide--all orders__order__wrapper grid-list__item">
							<div class="orders__order outer-rounded-x bordered shadow-hovered shadow-hovered-f600 shadow-no-border-hovered color-theme-parent-all">
								<?if ($bShowDetailLink):?>
									<a class="item-link-absolute" href="<?=$arParams['PATH_TO_ORDERS']?>" title="<?=htmlspecialcharsbx(Loc::getMessage('SPOL_TPL_SHOW_ALL'))?>"></a>
								<?endif;?>

								<div class="orders__order__inner flexbox flexbox--direction-column">
									<div class="orders__order__all-link__image">
										<?=TSolution::showSpriteIconSvg($svgSprite.'#orders-32-32', 'fill-theme svg-inline-more_icon', ['WIDTH' => 32, 'HEIGHT' => 32]);?>
									</div>

									<div class="orders__order__all-link__text">
										<div class="orders__order__all-link__title switcher-title font_clamp--16-14 color-theme-target font_weight--500"><?=Loc::getMessage('SPOL_TPL_SHOW_ALL')?></div>
									</div>
								</div>
							</div>
						</div>
					<?endif;?>
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
		</div>
	<?endif;?>
<?endif;?>
