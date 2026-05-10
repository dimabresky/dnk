<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_ACCOUNT'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_ACCOUNT'));
?>
<div class="personal__wrapper">
	<?if ($arParams['SHOW_ACCOUNT_COMPONENT'] !== 'N'):?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:sale.personal.account",
			"main",
			Array(
				"SET_TITLE" => "N",
				"PATH_TO_ACCOUNT" => $arResult["PATH_TO_ACCOUNT"],
				"DATE_FORMAT" => $arParams["DATE_FORMAT"],
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>

	<?if ($arParams['SHOW_ACCOUNT_PAY_COMPONENT'] !== 'N'):?>
		<?
		$arAmounts = CUtil::JsObjectToPhp($arParams['~ACCOUNT_PAYMENT_SELL_TOTAL'] ?? '[]', true);
		if ($arAmounts) {
			foreach ($arAmounts as $i => $arAmount) {
				if (
					!$arAmount ||
					!is_array($arAmount) ||
					!$arAmount['active'] ||
					!$arAmount['value']
				) {
					unset($arAmounts[$i]);
					continue;
				}
				
				$arAmounts[$i] = $arAmount['value'];
			}
		}
		?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:sale.account.pay",
			"main",
			array(
				"COMPONENT_TEMPLATE" => "main",
				"REFRESHED_COMPONENT_MODE" => "Y",
				"ELIMINATED_PAY_SYSTEMS" => $arParams["ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS"],
				"PATH_TO_BASKET" => $arResult["PATH_TO_BASKET"],
				"PATH_TO_PAYMENT" => $arResult["PATH_TO_PAYMENT"],
				"PERSON_TYPE" => $arParams["ACCOUNT_PAYMENT_PERSON_TYPE"],
				"REDIRECT_TO_CURRENT_PAGE" => "N",
				"SELL_AMOUNT" => $arAmounts,
				"SELL_CURRENCY" => $arParams["ACCOUNT_PAYMENT_SELL_CURRENCY"],
				"SELL_SHOW_FIXED_VALUES" => $arParams["ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES"],
				"SELL_SHOW_RESULT_SUM" =>  $arParams["ACCOUNT_PAYMENT_SELL_SHOW_RESULT_SUM"],
				"SELL_TOTAL" => $arAmounts,
				"SELL_USER_INPUT" => $arParams["ACCOUNT_PAYMENT_SELL_USER_INPUT"],
				"SELL_VALUES_FROM_VAR" => "N",
				"SELL_VAR_PRICE_VALUE" => "",
				"SET_TITLE" => "N",
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>
</div>
