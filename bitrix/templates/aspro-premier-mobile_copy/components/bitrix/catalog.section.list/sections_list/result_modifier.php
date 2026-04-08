<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

TSolution\Utils::getThemeParams($arParams, ['IMAGES', 'SHOW_TITLE_IN_BLOCK', 'TITLE_POSITION', 'IMAGES_POSITION']);

$arResult["RESIZE_OPTIONS"] = [
    'width' => 120,
    'height' => 120
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

if ($arParams["TOP_DEPTH"]>1) {

	$arSections = array();
	$arSectionsDepth3 = array();
	foreach( $arResult["SECTIONS"] as $arItem ) {
		if( $arItem["DEPTH_LEVEL"] == 1 ) { $arSections[$arItem["ID"]] = $arItem;}
		elseif( $arItem["DEPTH_LEVEL"] == 2 ) {$arSections[$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;}
		elseif( $arItem["DEPTH_LEVEL"] == 3 ) {$arSectionsDepth3[] = $arItem;}
	}
	if($arSectionsDepth3){
		foreach( $arSectionsDepth3 as $arItem) {
			foreach( $arSections as $key => $arSection) {
				if (is_array($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]) && !empty($arSection["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]])) {
					$arSections[$key]["SECTIONS"][$arItem["IBLOCK_SECTION_ID"]]["SECTIONS"][$arItem["ID"]] = $arItem;
				}
			}
		}
	}
	$arResult["SECTIONS"] = $arSections;
}