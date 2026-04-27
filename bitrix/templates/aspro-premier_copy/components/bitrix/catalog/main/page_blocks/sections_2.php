<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if($arParams['SECTIONS_TYPE_VIEW'] === 'FROM_MODULE'){
	$blockTemplateOptions = $GLOBALS['arTheme']['SECTIONS_TYPE_VIEW_CATALOG']['LIST'][$GLOBALS['arTheme']['SECTIONS_TYPE_VIEW_CATALOG']['VALUE']];
	$imagesPosition = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_IMAGES_POSITION']['VALUE'];
	$elementsInRow = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_ELEMENTS_COUNT']['VALUE'];
	$images = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_IMAGES']['VALUE'];
}
else{
	$imagesPosition = $arParams['SECTIONS_IMAGES_POSITION'];
	$elementsInRow = $arParams['SECTIONS_ELEMENTS_COUNT'];
	$images = $arParams['SECTIONS_IMAGES'];
}
$sectionsCountFilter = $arParams["SECTION_COUNT_ELEMENTS"] === "Y" && TSolution::GetFrontParametrValue('HIDE_NOT_AVAILABLE') == "Y" ? 'CNT_AVAILABLE' : 'CNT_ACTIVE';
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.section.list", 
	"sections_list", 
	array(
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"IBLOCK_ID"	=> $arParams["IBLOCK_ID"],
		"CACHE_TYPE"	=>	$arParams["CACHE_TYPE"],
		"CACHE_TIME"	=>	$arParams["CACHE_TIME"],
		"CACHE_FILTER"	=>	$arParams["CACHE_FILTER"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"COUNT_ELEMENTS" => $arParams["SECTION_COUNT_ELEMENTS"],
		"COUNT_ELEMENTS_FILTER" => $sectionsCountFilter,
		"FILTER_NAME"	=>	$arParams["FILTER_NAME"],
		"TOP_DEPTH" => min((int)$arParams["SECTION_TOP_DEPTH"], 2),
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"ADD_SECTIONS_CHAIN" => "N",
		"COMPONENT_TEMPLATE" => "main",
		"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
		"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SECTION_FIELDS" => array(
			0 => "NAME",
			2 => "PICTURE",
			3 => "",
		),
		"SECTION_USER_FIELDS" => array(
			0 => "UF_CATALOG_ICON",
			1 => "UF_TRANSPARENT_PICTURE",
			2 => "",
		),
		"ELEMENTS_IN_ROW" => $bShowLeftBlock ? 3 : 4,
		"IMAGES_POSITION" => $imagesPosition,
		"IMAGES" => $images,
		"NARROW" => "Y",
		"CHECK_REQUEST_BLOCK" => TSolution::checkRequestBlock("catalog_sections"),
		"IS_AJAX" => TSolution::checkAjaxRequest(),
		"MOBILE_SCROLLED" => "N",
		"MOBILE_COMPACT" => "Y",
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO"
	),
	false
);?>
