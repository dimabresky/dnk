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

$arResult["RESIZE_OPTIONS"] = [
    'width' => 580,
    'height' => 580
];

if ($arResult['SECTIONS']) {
    if (!isset($arParams["USE_CUSTOM_RESIZE"]) || $arParams["USE_CUSTOM_RESIZE"] == 'FROM_THEME') {
        $arParams["USE_CUSTOM_RESIZE"] = TSolution::GetFrontParametrValue('USE_CUSTOM_RESIZE_CATALOG_SECTIONS');
    }
    
    if ($arParams["USE_CUSTOM_RESIZE"] == "Y") {
        $arResult["RESIZE_OPTIONS"] = TSolution\Product\Image::getResizeFromIblock($arParams["IBLOCK_ID"], 'SECTION_PICTURE');
    }

    if ($arParams['SECTION_COUNT']) {
        array_splice($arResult['SECTIONS'], $arParams['SECTION_COUNT']);
    }
}