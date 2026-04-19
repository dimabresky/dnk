<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

TSolution\Utils::getThemeParams($arParams, ['SHOW_TITLE_IN_BLOCK', 'TITLE_POSITION', 'FON', 'BORDERED', 'ELEMENTS_IN_ROW']);
TSolution\Utils::setBottomPagerByLinesCount($arParams);

foreach ($arResult['ITEMS'] as $key => $arItem) {
	TSolution::getFieldImageData($arResult['ITEMS'][$key], array('PREVIEW_PICTURE'));
}

if ($arParams['SLIDER'] === 'Y') {
	$arParams['DISPLAY_BOTTOM_PAGER'] = false;
}
