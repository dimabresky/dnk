<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$orderId = $arResult['VARIABLES']['ID'];
$orderDate = '';

try {
	$order = Bitrix\Sale\Order::load($orderId);
}
catch (\Exception $e) {
	$order = null;
}

if (
	(!$order && $orderId) ||
	($order && $order->getId() != $orderId)
) {
	$orderId = urldecode($arResult['VARIABLES']['ID']);
	$order = Bitrix\Sale\Order::loadByAccountNumber($orderId);
}

if ($order) {
	$orderId = $order->getId();
	$orderAccountId = $order->getField('ACCOUNT_NUMBER') ?: $orderId;
	$orderDate = new Bitrix\Main\Type\DateTime($order->getField('DATE_INSERT'));
	$orderDate = FormatDate($arParams['DATE_FORMAT'], $orderDate->getTimestamp());
	
	// ask question
	$APPLICATION->AddViewContent('more_text_title', '<a class="order__ask-question font_15 fw-500" data-event="jqm" data-param-id="'.\TSolution::getFormID('aspro_premier_order_question').'" data-autoload-order_id="'.$orderAccountId.'">'.Loc::getMessage('SPS_ASK_QUESTION').'</a>');
}

if ($arParams['SET_TITLE'] === 'Y') {
	if ($orderDate) {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ORDER_DATE', ['#ID#' => $orderAccountId, '#DATE#' => $orderDate]));
	}
	else {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ORDER', ['#ID#' => $orderAccountId]));
	}
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ORDERS'), $arResult['PATH_TO_ORDERS']);
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ORDER', ['#ID#' => $orderAccountId]));

$arComponentParams = [
	"PATH_TO_LIST" => $arResult["PATH_TO_ORDERS"],
	"PATH_TO_CANCEL" => $arResult["PATH_TO_ORDER_CANCEL"],
	"PATH_TO_CATALOG" => $arResult["PATH_TO_CATALOG"],
	"PATH_TO_COPY" => $arResult["PATH_TO_ORDER_COPY"],
	"PATH_TO_BASKET" => $arResult["PATH_TO_BASKET"],
	"PATH_TO_PAYMENT" => $arResult["PATH_TO_PAYMENT"],
	"SET_TITLE" => "N",
	"ID" => $orderId,
	"ACTIVE_DATE_FORMAT" => $arParams["DATE_FORMAT"],
	"ALLOW_INNER" => $arParams["ALLOW_INNER"],
	"ONLY_INNER_FULL" => $arParams["ONLY_INNER_FULL"],
	"CACHE_TYPE" => $arParams["CACHE_TYPE"],
	"CACHE_TIME" => $arParams["CACHE_TIME"],
	"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
	"RESTRICT_CHANGE_PAYSYSTEM" => $arParams["ORDER_RESTRICT_CHANGE_PAYSYSTEM"],
	"HIDE_STATUSES" => $arParams["ORDER_HIDE_STATUSES"],
	"CHANGE_STATUS_COLOR" => $arParams["ORDER_CHANGE_STATUS_COLOR"],
	"DISALLOW_CANCEL" => $arParams["ORDER_DISALLOW_CANCEL"],
	"REFRESH_PRICES" => $arParams["ORDER_REFRESH_PRICES"],
	"HIDE_USER_INFO" => $arParams["ORDER_HIDE_USER_INFO"],
	"CUSTOM_SELECT_PROPS" => $arParams["CUSTOM_SELECT_PROPS"]
];

foreach($arParams as $key => $value) {
	if (preg_match('/^DELIVERY_INFO_PROP_\d+/', $key)) {
		$arComponentParams[$key] = $value;
	}
	elseif (preg_match('/^PROP_\d+$/', $key)) {
		$arComponentParams[$key] = $value;
	}
}
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.personal.order.detail",
		"main",
		$arComponentParams,
		$component,
		array("HIDE_ICONS" => "Y")
	);?>
</div>

<div class="bottom-links-block">
	<?// back url?>
	<?TSolution\Functions::showBackUrl(
		array(
			'URL' => $arResult['PATH_TO_ORDERS'],
			'TEXT' => Loc::getMessage('SPS_BACK_LINK'),
		)
	);?>
</div>
