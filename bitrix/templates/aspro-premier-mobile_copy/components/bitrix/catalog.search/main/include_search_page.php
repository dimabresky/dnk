<?
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arSearchPageParams = array(
	"RESTART" => $arParams["RESTART"],
	"NO_WORD_LOGIC" => $arParams["NO_WORD_LOGIC"],
	"USE_LANGUAGE_GUESS" => $arParams["USE_LANGUAGE_GUESS"],
	"CHECK_DATES" => $arParams["CHECK_DATES"],
	"USE_TITLE_RANK" => ($arParams['SHOW_SORT_RANK_BUTTON'] === 'Y' ? 'Y' : 'N'),
	"DEFAULT_SORT" => "rank",
	"FILTER_NAME" => "",
	"SHOW_WHERE" => "N",
	"arrWHERE" => array(),
	"SHOW_WHEN" => "N",
	"PAGE_RESULT_COUNT" => 200,
	"DISPLAY_TOP_PAGER" => "N",
	"DISPLAY_BOTTOM_PAGER" => "N",
	"FROM_AJAX" => $isAjaxFilter,
	"PAGER_TITLE" => "",
	"PAGER_SHOW_ALWAYS" => "N",
	"PAGER_TEMPLATE" => "N",
);

$arSearchPageParams = array_merge($arSearchPageParams, $arSearchPageFilter);

if (
	Loader::includeModule('aspro.smartsearch') &&
	'N' !== Bitrix\Main\Config\Option::get('aspro.smartsearch', 'ENABLED', 'Y', SITE_ID)
) {
	$arElements = $APPLICATION->IncludeComponent(
		"aspro:smartsearch.page", 
		"", 
		$arSearchPageParams, 
		$component, 
		array("HIDE_ICONS" => "Y")
	);
}
else {
	$arElements = $APPLICATION->IncludeComponent(
		"bitrix:search.page", 
		"", 
		$arSearchPageParams, 
		$component, 
		array("HIDE_ICONS" => "Y")
	);
}