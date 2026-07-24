<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

TSolution\Utils::getThemeParams($arParams, [
	'BORDERED', 
	'ELEMENTS_IN_ROW', 
	'FON', 
	'IMAGES',
	'SHOW_TITLE_IN_BLOCK', 
	'TITLE_POSITION', 
]);

TSolution\Utils::setBottomPagerByLinesCount($arParams);

$arSections = $arSectionsIDs = $arAllowBuyItems = array();

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
