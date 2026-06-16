<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Loader;

/** @var array $arParams */
/** @var array $arResult */

$arParams['~MESS_BTN_BUY'] ??= '';
$arParams['~MESS_BTN_DETAIL'] ??= '';
$arParams['~MESS_BTN_COMPARE'] ??= '';
$arParams['~MESS_BTN_SUBSCRIBE'] ??= '';
$arParams['~MESS_BTN_ADD_TO_BASKET'] ??= '';
$arParams['~MESS_NOT_AVAILABLE'] ??= '';
$arParams['~MESS_SHOW_MAX_QUANTITY'] ??= '';
$arParams['~MESS_RELATIVE_QUANTITY_MANY'] ??= '';
$arParams['MESS_RELATIVE_QUANTITY_MANY'] ??= '';
$arParams['~MESS_RELATIVE_QUANTITY_FEW'] ??= '';
$arParams['MESS_RELATIVE_QUANTITY_FEW'] ??= '';

$giftDisplayPrice = 0.01;
$giftDisplayDiscountPercent = 99;

/**
 * @param array<string, mixed> $price
 */
function dnkPatchGiftBasketDisplayPrice(array &$price): void
{
	global $giftDisplayPrice, $giftDisplayDiscountPercent;
	if (empty($price['CURRENCY']))
	{
		return;
	}

	$currency = (string)$price['CURRENCY'];
	$minQuantity = isset($price['MIN_QUANTITY']) ? (float)$price['MIN_QUANTITY'] : 1.0;
	if ($minQuantity <= 0)
	{
		$minQuantity = 1.0;
	}

	$displayRatioPrice = $giftDisplayPrice;
	$displayPrice = $displayRatioPrice * $minQuantity;

	$price['RATIO_PRICE'] = $displayRatioPrice;
	$price['PRICE'] = $displayPrice;
	$price['PRINT_RATIO_PRICE'] = (string)\CCurrencyLang::CurrencyFormat($displayRatioPrice, $currency, true);
	$price['PRINT_PRICE'] = (string)\CCurrencyLang::CurrencyFormat($displayPrice, $currency, true);
	$price['PERCENT'] = $giftDisplayDiscountPercent;

	if (isset($price['RATIO_BASE_PRICE']))
	{
		$discount = (float)$price['RATIO_BASE_PRICE'] - $displayRatioPrice;
		if ($discount < 0)
		{
			$discount = 0.0;
		}

		$price['DISCOUNT'] = $discount;
		$price['PRINT_DISCOUNT'] = (string)\CCurrencyLang::CurrencyFormat($discount, $currency, true);
	}
}

/**
 * @param array<int, array<string, mixed>>|null $itemPrices
 */
function dnkPatchGiftBasketItemPrices(?array &$itemPrices): void
{
	if (empty($itemPrices) || !is_array($itemPrices))
	{
		return;
	}

	foreach ($itemPrices as &$price)
	{
		if (is_array($price))
		{
			dnkPatchGiftBasketDisplayPrice($price);
		}
	}
	unset($price);
}

/**
 * @param array<string, mixed> $item
 */
function dnkPatchGiftBasketItem(array &$item): void
{
	dnkPatchGiftBasketItemPrices($item['ITEM_PRICES']);

	if (!empty($item['ITEM_START_PRICE']) && is_array($item['ITEM_START_PRICE']))
	{
		dnkPatchGiftBasketDisplayPrice($item['ITEM_START_PRICE']);
	}

	if (!empty($item['OFFERS']) && is_array($item['OFFERS']))
	{
		foreach ($item['OFFERS'] as &$offer)
		{
			if (!is_array($offer))
			{
				continue;
			}

			dnkPatchGiftBasketItemPrices($offer['ITEM_PRICES']);
		}
		unset($offer);
	}

	if (!empty($item['JS_OFFERS']) && is_array($item['JS_OFFERS']))
	{
		foreach ($item['JS_OFFERS'] as &$jsOffer)
		{
			if (!is_array($jsOffer))
			{
				continue;
			}

			dnkPatchGiftBasketItemPrices($jsOffer['ITEM_PRICES']);
		}
		unset($jsOffer);
	}
}

if (!empty($arResult['ITEMS']) && is_array($arResult['ITEMS']) && Loader::includeModule('currency'))
{
	foreach ($arResult['ITEMS'] as &$item)
	{
		if (is_array($item))
		{
			dnkPatchGiftBasketItem($item);
		}
	}
	unset($item);
}
