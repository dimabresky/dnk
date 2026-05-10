
<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$orderId = $arResult['VARIABLES']['ID'];

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
}

if ($arParams['SET_TITLE'] === 'Y') {
	if ($orderDate) {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ORDER_CANCEL_DATE', ['#ID#' => $orderAccountId, '#DATE#' => $orderDate]));
	}
	else {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ORDER_CANCEL', ['#ID#' => $orderAccountId]));
	}
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ORDERS'), $arResult['PATH_TO_ORDERS']);
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ORDER_CANCEL', ['#ID#' => $orderAccountId]));
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.personal.order.cancel",
		"main",
		array(
			"PATH_TO_LIST" => $arResult["PATH_TO_ORDERS"],
			"PATH_TO_DETAIL" => $arResult["PATH_TO_ORDER_DETAIL"],
			"SET_TITLE" => "N",
			"ID" => $orderId,
			"REASONS" => $arParams["~ORDER_CANCEL_REASONS"],
			"REASON_REQUIRED" => $arParams["ORDER_CANCEL_REASON_REQUIRED"],
		),
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