<?
use TSolution\Product\Service;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

// refresh services with linked products
TSolution\Itemaction\Service::getItems();

// exclude unavailable services
foreach ($arResult['ITEMS'] as $key => $arItem) {
	$bCanBuy = Service::getCanBuy($arItem);
	if (!$bCanBuy) {
		unset($arResult['ITEMS'][$key]);
	}
}

if (
	isset($arParams['SERVICES_IN_BASKET']) &&
	is_array($arParams['SERVICES_IN_BASKET'])
) {
	$arParams['VISIBLE_COUNT'] = $arParams['VISIBLE_COUNT'] ?? 2;

	$counter = 0;
	foreach ($arResult['ITEMS'] as $key => $arItem) {
		++$counter;
		if ($arParams['SERVICES_IN_BASKET'][$arItem['ID']] ?? []) {
			if ($arParams['VISIBLE_COUNT'] < $counter) {
				$arParams['VISIBLE_COUNT'] = $counter;
			}
		}
	}
}
