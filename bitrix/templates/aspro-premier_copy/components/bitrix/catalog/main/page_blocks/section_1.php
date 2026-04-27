<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$bShowChilds = false;
$bShowPreviewText = false;
if($arParams['SECTION_TYPE_VIEW'] === 'FROM_MODULE'){
	$blockTemplateOptions = $GLOBALS['arTheme']['SECTION_TYPE_VIEW_CATALOG']['LIST'][$GLOBALS['arTheme']['SECTION_TYPE_VIEW_CATALOG']['VALUE']];
	$bordered = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_BORDERED']['VALUE'];
	$images = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_IMAGES']['VALUE'];
	$onFon = $blockTemplateOptions['ADDITIONAL_OPTIONS']['SECTIONS_IMAGE_ON_FON']['VALUE'];
}
else{
	$bordered = $arParams['SECTION_BORDERED'];
	$onFon = $arParams['SECTION_IMAGE_ON_FON'];
	$images = $arParams['SECTION_IMAGES'];
}
$sectionsCountFilter = $arParams["SECTION_COUNT_ELEMENTS"] === "Y" && TSolution::GetFrontParametrValue('HIDE_NOT_AVAILABLE') == "Y" ? 'CNT_AVAILABLE' : 'CNT_ACTIVE';
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:catalog.section.list", 
	".default", 
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
		"TOP_DEPTH" => $arParams["SECTION_TOP_DEPTH"],
		"TOP_DEPTH" => 1,
		"SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
		"ADD_SECTIONS_CHAIN" => ((!$iSectionsCount || $arParams['INCLUDE_SUBSECTIONS'] !== "N") ? 'N' : 'Y'),
		"COMPONENT_TEMPLATE" => ".default",
		"SECTION_ID" => $arResult["VARIABLES"]["SECTION_ID"],
		"SECTION_CODE" => $arResult["VARIABLES"]["SECTION_CODE"],
		"SECTION_FIELDS" => array(
			0 => "NAME",
			1 => "PICTURE",
		),
		"SECTION_USER_FIELDS" => array(
			0 => "UF_CATALOG_ICON",
			1 => "UF_TRANSPARENT_PICTURE",
			2 => "",
		),
		"BORDERED" => $bordered,
		"IMAGES" => $images,
		"IMAGE_ON_FON" => $onFon,
		"NARROW" => "Y",
		"CHECK_REQUEST_BLOCK" => TSolution::checkRequestBlock("catalog_sections"),
		"IS_AJAX" => TSolution::checkAjaxRequest(),
		"MOBILE_SCROLLED" => TSolution::GetFrontParametrValue('MOBILE_SCROLL_LIST_SECTIONS'),
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO"
	),
	false
);?>