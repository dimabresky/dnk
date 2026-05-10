<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ORDERS'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ORDERS'));
?>
<div class="personal__wrapper">
	<?$arFilterResult = $APPLICATION->IncludeComponent(
		"aspro:sale.personal.order.filter.premier",
		"",
		array(
			"BY_STATUS" => $arParams["ORDER_FILTER_BY_STATUS"],
			"BY_PAYED" => $arParams["ORDER_FILTER_BY_PAYED"],
			"BY_YEAR" => $arParams["ORDER_FILTER_BY_YEAR"],
			"HIDE_STATUSES" => $arParams["ORDER_HIDE_STATUSES"],
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);?>

	<?
	$arComponentParams = [
		"PATH_TO_DETAIL" => $arResult["PATH_TO_ORDER_DETAIL"],
		"PATH_TO_CANCEL" => $arResult["PATH_TO_ORDER_CANCEL"],
		"PATH_TO_CATALOG" => $arResult["PATH_TO_CATALOG"],
		"PATH_TO_COPY" => $arResult["PATH_TO_ORDER_COPY"],
		"PATH_TO_BASKET" => $arResult["PATH_TO_BASKET"],
		"PATH_TO_PAYMENT" => $arResult["PATH_TO_PAYMENT"],
		"SAVE_IN_SESSION" => "N",
		"ORDERS_PER_PAGE" => $arParams["ORDERS_PER_PAGE"],
		"SET_TITLE" => "N",
		"ID" => $arResult["VARIABLES"]["ID"],
		"NAV_TEMPLATE" => $arParams["NAV_TEMPLATE"],
		"ACTIVE_DATE_FORMAT" => $arParams["DATE_FORMAT"],
		"HISTORIC_STATUSES" => $arParams["ORDER_HISTORIC_STATUSES"],
		"ALLOW_INNER" => $arParams["ALLOW_INNER"],
		"ONLY_INNER_FULL" => $arParams["ONLY_INNER_FULL"],
		"CACHE_TYPE" => $arParams["CACHE_TYPE"],
		"CACHE_TIME" => $arParams["CACHE_TIME"],
		"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
		"DEFAULT_SORT" => $arParams["ORDER_DEFAULT_SORT"],
		"RESTRICT_CHANGE_PAYSYSTEM" => $arParams["ORDER_RESTRICT_CHANGE_PAYSYSTEM"],
		"HIDE_STATUSES" => $arParams["ORDER_HIDE_STATUSES"],
		"CHANGE_STATUS_COLOR" => $arParams["ORDER_CHANGE_STATUS_COLOR"],
		"REFRESH_PRICES" => $arParams["ORDER_REFRESH_PRICES"],
		"DISALLOW_CANCEL" => $arParams["ORDER_DISALLOW_CANCEL"],
		"AJAX_NAV" => $arResult["AJAX_NAV"] ? "Y" : "N",
		"ACTIVE_FILTER" => isset($arFilterResult) && is_array($arFilterResult) && ($arFilterResult["ACTIVE_FILTER"] ?? false) ? "Y" : "N",
		"SHOW_DETAIL_LINK" => $arParams["SHOW_ORDER_PAGE"],
	];
	
	foreach($arParams as $key => $value) {
		if (preg_match('/^DELIVERY_INFO_PROP_\d+/', $key)) {
			$arComponentParams[$key] = $value;
		}
	}
	?>

	<?if ($arResult['AJAX_POST']) {
		$GLOBALS['APPLICATION']->RestartBuffer();

		$APPLICATION->ShowAjaxHead();
	}?>

	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.personal.order.list",
		"main",
		$arComponentParams,
		$component,
		array("HIDE_ICONS" => "Y")
	);?>

	<?if ($arResult['AJAX_POST']) {
		die();
	}?>
</div>
