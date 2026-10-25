<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

global $arTheme, $APPLICATION;

if (($arTheme['SHOW_SMARTFILTER']['VALUE'] ?? 'Y') === 'N') {
	return;
}

$catalogIblockType = CIBlock::GetArrayByID($catalogIBlockID, 'IBLOCK_TYPE_ID');
TSolution\CacheableUrl::addSmartFilterNameParam($filterName);

// shortcut: no SEF, news has no smart_filter route
$APPLICATION->IncludeComponent(
	'bitrix:catalog.smart.filter',
	'main_compact',
	[
		'IBLOCK_TYPE' => $catalogIblockType,
		'IBLOCK_ID' => $catalogIBlockID,
		'SECTION_ID' => '',
		'SHOW_ALL_WO_SECTION' => 'Y',
		'INCLUDE_SUBSECTIONS' => 'N',
		'PREFILTER_NAME' => 'preFilterLanding',
		'FILTER_NAME' => $filterName,
		'PRICE_CODE' => ($arParams['USE_FILTER_PRICE'] ?? 'N') === 'Y' ? $arParams['FILTER_PRICE_CODE'] : $arParams['PRICE_CODE'],
		'CACHE_TYPE' => $arParams['CACHE_TYPE'],
		'CACHE_TIME' => $arParams['CACHE_TIME'],
		'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
		'SAVE_IN_SESSION' => 'N',
		'FILTER_VIEW_MODE' => (($arTheme['FILTER_VIEW']['VALUE'] ?? '') === 'HORIZONTAL' ? 'HORIZONTAL' : 'VERTICAL'),
		'DISPLAY_ELEMENT_COUNT' => $arParams['DISPLAY_ELEMENT_COUNT'] ?? 'Y',
		'POPUP_POSITION' => (($arTheme['SIDE_MENU']['VALUE'] ?? '') === 'LEFT' ? 'right' : 'left'),
		'AJAX' => TSolution::GetFrontParametrValue('AJAX_FILTER'),
		'INSTANT_RELOAD' => 'Y',
		'XML_EXPORT' => 'N',
		'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'] ?? '',
		'SORT_HTML' => $sortHTML ?? '',
		'SHOW_HINTS' => $arParams['SHOW_HINTS'] ?? 'N',
		'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
		'CURRENCY_ID' => $arParams['CURRENCY_ID'],
		'SEF_MODE' => 'N',
		'HIDE_NOT_AVAILABLE' => TSolution::GetFrontParametrValue('HIDE_NOT_AVAILABLE'),
		'HIDE_SMART_SEO' => $arParams['HIDE_SMART_SEO'] ?? 'N',
	],
	$this->__component
);
