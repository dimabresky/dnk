<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	Bitrix\Sale;

Loc::loadMessages(__FILE__);

Loader::includeModule('sale');

$paymentId = $_REQUEST['PAYMENT_ID'] ?? '';
if ($paymentId) {
	$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
	$paymentClassName = $registry->getPaymentClassName();

	if ($paymentClassName) {
		if (strpos($paymentId, '/') === false) {
			$arFilter = [
				'ID' => $paymentId,
			];
		}
		else {
			$arFilter = [
				'ACCOUNT_NUMBER' => $paymentId,
			];
		}

		$dbRes = $paymentClassName::getList([
			'filter' => $arFilter,
			'select' => [
				'ID',
				'ORDER_ID',
				'ACCOUNT_NUMBER',
			],
		]);
		$arPayment = $dbRes->fetch();
		if ($arPayment) {
			$paymentId = $arPayment['ACCOUNT_NUMBER'] ?: $paymentId ; // for title
			$orderId = $arPayment['ORDER_ID'];
			$_REQUEST['ORDER_ID'] = $orderId;
		}
	}
}

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_PAYMNET', ['#ID#' => $paymentId]));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_PAYMENT', ['#ID#' => $paymentId]));
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:sale.order.payment",
	"",
	Array(
	),
	$component,
	array("HIDE_ICONS" => "Y")
);?>
<?die();?>
