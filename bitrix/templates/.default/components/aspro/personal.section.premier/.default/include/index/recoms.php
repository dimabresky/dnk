<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
?>

<?$APPLICATION->IncludeComponent(
	"aspro:wrapper.block.premier", 
	"recoms", 
	array(
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "N",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "N",
		"COMPONENT_TEMPLATE" => "recoms",
		"SHOW_TITLE" => "Y",
		"TITLE" => Loc::getMessage('SPS_MAIN_BLOCK_TITLE_RECOMS'),
		"ELEMENT_COUNT" => $arParams['RCM_ELEMENTS_COUNT'] ?? "10",
		"RCM_TYPE" => $arParams['RCM_TYPE'] ?? "any_personal",
		"RCM_PROD_ID" => "",
		"SHOW_FROM_SECTION" => "N",
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
