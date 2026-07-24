<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arDefaultParams = array(
	'TYPE_SKU' => 'N',
	'FILTER_HIT_PROP' => 'block',
	'OFFER_TREE_PROPS' => array('-'),
);
$arParams = array_merge($arDefaultParams, $arParams);
if (!empty($arResult['ITEMS'])) {
	$arOffers = [];
	$showQuantityComplect = 'N';

	if ($GLOBALS[$arParams['FILTER_NAME']] && $arFilter = $GLOBALS[$arParams['FILTER_NAME']]['ITEMS']) {
		foreach ($arFilter as $items) {
			$arItems[] = $items['OFFER_ID'] ?? $items['ID'];
		}

		foreach ($arResult['ITEMS'] as $key => $arItem) {
			if (isset($arFilter[$arItem['ID']])) {
				$arResult['ITEMS'][$key]['QUANTITY_COMPLECT']['QUANTITY'] = $arFilter[$arItem['ID']]['QUANTITY'];

				if ($arResult['ITEMS'][$key]['QUANTITY_COMPLECT']['QUANTITY'] > 1 && $showQuantityComplect !== 'Y') {
					$showQuantityComplect = 'Y';
				}
			}
		}

		foreach ($arResult['ITEMS'] as $key => $arItem) {
			$arResult['ITEMS'][$key]['QUANTITY_COMPLECT']['SHOW_QUANTITY'] = $showQuantityComplect;
		}

		$arFilterSKU = array_flip(array_column($GLOBALS[$arParams['FILTER_NAME']]['ITEMS'], 'OFFER_ID'));

		if ($arFilterSKU) {
			foreach ($arResult['ITEMS'] as $key => $arItem) {
				if ($arItem['OFFERS']) {
					array_push($arOffers, ...$arItem['OFFERS']);
				}
				$arNewItemsList[$arItem['ID']] = $arItem;
			}

			$arOffers = array_filter($arOffers, fn ($offer) => isset($arFilterSKU[$offer['ID']]));


			$arOffersTmp = [];
			foreach ($arOffers as $key => $arOffer) {
				if (!$arOffer['PREVIEW_PICTURE']) {
					$arOffer['PREVIEW_PICTURE'] = $arNewItemsList[$arOffer["LINK_ELEMENT_ID"]]['PREVIEW_PICTURE'];
				}

				if (isset($arFilter[$arOffer['LINK_ELEMENT_ID']])) {
					$arOffer['QUANTITY_COMPLECT']['QUANTITY'] = $arFilter[$arOffer['LINK_ELEMENT_ID']]['QUANTITY'];
				}

				if ($arOffer['QUANTITY_COMPLECT']['QUANTITY'] > 1 && $showQuantityComplect !== 'Y') {
					$showQuantityComplect = 'Y';
				}

				$arOffersTmp[$arOffer['ID']] = $arOffer;
			}
			$arOffers = $arOffersTmp;

			$arNewItemsList += $arOffers;

			$arResult['ITEMS'] = [];
			foreach ($arItems as $items) {
				$arResult['ITEMS'][$items] = $arNewItemsList[$items];
				$arResult['ITEMS'][$items]['QUANTITY_COMPLECT']['SHOW_QUANTITY'] = $showQuantityComplect;
			}

			unset($arOffers, $arOffersTmp);
		}
	}
}
