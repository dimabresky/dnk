<?
use TSolution\Product\Service;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arParams['BORDERED'] = $arParams['BORDERED'] ?? 'Y';
$arParams['ELEMENTS_IN_ROW'] = $arParams['ELEMENTS_IN_ROW'] ?? '3';
$arParams['FON'] = $arParams['FON'] ?? 'N';
$arParams['IMAGES'] = $arParams['IMAGES'] ?? 'PICTURES';
$arParams['SHOW_TITLE_IN_BLOCK'] = $arParams['SHOW_TITLE_IN_BLOCK'] ?? 'Y';
$arParams['TITLE_POSITION'] = $arParams['TITLE_POSITION'] ?? 'LEFT';

// refresh services with linked products
TSolution\Itemaction\Service::getItems();

// exclude unavailable services
foreach ($arResult['ITEMS'] as $key => $arItem) {
	$bCanBuy = Service::getCanBuy($arItem);
	if (!$bCanBuy) {
		unset($arResult['ITEMS'][$key]);
	}
}

TSolution\Utils::setBottomPagerByLinesCount($arParams);

foreach ($arResult['ITEMS'] as $key => &$arItem) {
	$arItem['DETAIL_PAGE_URL'] = TSolution::FormatNewsUrl($arItem);
	
	$arItem['MIDDLE_PROPS'] = array();
	if ($arItem['DISPLAY_PROPERTIES']) {
		foreach ($arItem['DISPLAY_PROPERTIES'] as $key2 => $arProp) {
			if (($key2 === 'EMAIL' || $key2 === 'PHONE'|| $key2 === 'SITE') && $arProp['VALUE']) {
				$arItem['MIDDLE_PROPS'][$key2] = $arProp;
				unset($arItem['DISPLAY_PROPERTIES'][$key2]);
			}
		}
	}

	$arItem['FORMAT_PROPS'] = TSolution::PrepareItemProps($arItem['DISPLAY_PROPERTIES']);

	TSolution::getFieldImageData($arItem, array('PREVIEW_PICTURE'));
}
unset($arItem);
