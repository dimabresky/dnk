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

/**
 * @param array<string, mixed> $price
 */
if (!function_exists('dnkPatchGiftBasketDisplayPrice'))
{
	function dnkPatchGiftBasketDisplayPrice(array &$price): void
	{
		if (empty($price['CURRENCY']))
		{
			return;
		}

		$displayRatioPrice = 0.01;
		$displayDiscountPercent = 99;
		$currency = (string)$price['CURRENCY'];
		$minQuantity = isset($price['MIN_QUANTITY']) ? (float)$price['MIN_QUANTITY'] : 1.0;
		if ($minQuantity <= 0)
		{
			$minQuantity = 1.0;
		}

		$displayPrice = $displayRatioPrice * $minQuantity;

		$price['RATIO_PRICE'] = $displayRatioPrice;
		$price['PRICE'] = $displayPrice;
		$price['PRINT_RATIO_PRICE'] = (string)\CCurrencyLang::CurrencyFormat($displayRatioPrice, $currency, true);
		$price['PRINT_PRICE'] = (string)\CCurrencyLang::CurrencyFormat($displayPrice, $currency, true);
		$price['PERCENT'] = $displayDiscountPercent;

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
}

/**
 * @param array<int, array<string, mixed>>|null $itemPrices
 */
if (!function_exists('dnkPatchGiftBasketItemPrices'))
{
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
}

/**
 * @param array<string, mixed> $item
 */
if (!function_exists('dnkPatchGiftBasketItem'))
{
	function dnkPatchGiftBasketItem(array &$item): void
	{
		if (isset($item['ITEM_PRICES']) && is_array($item['ITEM_PRICES']))
		{
			dnkPatchGiftBasketItemPrices($item['ITEM_PRICES']);
		}

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

				if (isset($offer['ITEM_PRICES']) && is_array($offer['ITEM_PRICES']))
				{
					dnkPatchGiftBasketItemPrices($offer['ITEM_PRICES']);
				}
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

				if (isset($jsOffer['ITEM_PRICES']) && is_array($jsOffer['ITEM_PRICES']))
				{
					dnkPatchGiftBasketItemPrices($jsOffer['ITEM_PRICES']);
				}
			}
			unset($jsOffer);
		}
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
