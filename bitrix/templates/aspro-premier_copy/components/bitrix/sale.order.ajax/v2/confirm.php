<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\BaseServiceHandler;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 */

if ($arParams["SET_TITLE"] == "Y")
{
	$APPLICATION->SetTitle(Loc::getMessage("SOA_ORDER_COMPLETE"));
}
?>

<? if (!empty($arResult["ORDER"])): ?>

	<table class="sale_order_full_table">
		<tr>
			<td>
				<?=Loc::getMessage("SOA_ORDER_SUC", array(
					"#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format('d.m.Y H:i'),
					"#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]
				))?>
				<? if (!empty($arResult['ORDER']["PAYMENT_ID"])): ?>
					<?=Loc::getMessage("SOA_PAYMENT_SUC", array(
						"#PAYMENT_ID#" => $arResult['PAYMENT'][$arResult['ORDER']["PAYMENT_ID"]]['ACCOUNT_NUMBER']
					))?>
				<? endif ?>
				<? if ($arParams['NO_PERSONAL'] !== 'Y'): ?>
					<br /><br />
					<?=Loc::getMessage('SOA_ORDER_SUC1', ['#LINK#' => $arParams['PATH_TO_PERSONAL']])?>
				<? endif; ?>
			</td>
		</tr>
	</table>

	<?
	if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y')
	{
		if (!empty($arResult["PAYMENT"]))
		{
			foreach ($arResult["PAYMENT"] as $payment)
			{
				if ($payment["PAID"] != 'Y')
				{
					if (!empty($arResult['PAY_SYSTEM_LIST'])
						&& array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
					)
					{
						$arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment["ID"]];

						if (empty($arPaySystem["ERROR"]))
						{
							$bePaidRedirectUrl = '';
							$actionFile = (string)($arPaySystem['ACTION_FILE'] ?? '');
							$psMode = (string)($arPaySystem['PS_MODE'] ?? '');
							$paymentUrl = (string)($arPaySystem['PAYMENT_URL'] ?? '');
							$isBePaidCheckout = mb_stripos($actionFile, 'bepaid') !== false
								&& ($psMode === 'checkout' || $paymentUrl !== '');
							$bePaidRequest = Application::getInstance()->getContext()->getRequest();
							$bePaidReturnStatus = (string)$bePaidRequest->get('status');
							$bePaidReturnToken = (string)$bePaidRequest->get('token');
							$bePaidReturnUid = (string)$bePaidRequest->get('uid');
							$bePaidReferer = (string)$bePaidRequest->getServer()->get('HTTP_REFERER');
							$isBePaidReturn = str_contains($bePaidReferer, 'bepaid.by')
								|| ($bePaidReturnStatus !== '' && ($bePaidReturnToken !== '' || $bePaidReturnUid !== ''));
							$gatewayAlreadyPaid = (string)($payment['PS_STATUS'] ?? '') === 'Y'
								|| (string)($payment['PS_STATUS_CODE'] ?? '') === 'successful';

							if ($isBePaidCheckout && !$isBePaidReturn && !$gatewayAlreadyPaid)
							{
								$bePaidRedirectUrl = $paymentUrl;
								if ($bePaidRedirectUrl === '' && $psMode === 'checkout')
								{
									$paySystemService = PaySystemManager::getObjectById((int)$payment['PAY_SYSTEM_ID']);
									$order = Order::load((int)$arResult['ORDER']['ID']);
									if ($paySystemService && $order)
									{
										$paymentItem = $order->getPaymentCollection()->getItemById((int)$payment['ID']);
										if ($paymentItem)
										{
											$initResult = $paySystemService->initiatePay(
												$paymentItem,
												null,
												BaseServiceHandler::STRING
											);
											if ($initResult->isSuccess())
											{
												$bePaidRedirectUrl = (string)$initResult->getPaymentUrl();
											}
										}
									}
								}

								$redirectParts = parse_url($bePaidRedirectUrl);
								$redirectHost = is_array($redirectParts) ? (string)($redirectParts['host'] ?? '') : '';
								$redirectScheme = is_array($redirectParts) ? (string)($redirectParts['scheme'] ?? '') : '';
								$isBePaidHost = $redirectHost === 'bepaid.by' || str_ends_with($redirectHost, '.bepaid.by');
								if ($redirectScheme !== 'https' || !$isBePaidHost)
								{
									$bePaidRedirectUrl = '';
								}
							}
							?>
							<br /><br />

							<table class="sale_order_full_table">
								<tr>
									<td class="ps_logo">
										<div class="pay_name"><?=Loc::getMessage("SOA_PAY") ?></div>
										<?=CFile::ShowImage($arPaySystem["LOGOTIP"], 100, 100, "border=0\" style=\"width:100px\"", "", false) ?>
										<div class="paysystem_name"><?=$arPaySystem["NAME"] ?></div>
										<br/>
									</td>
								</tr>
								<tr>
									<td>
										<? if (strlen($arPaySystem["ACTION_FILE"]) > 0 && $arPaySystem["NEW_WINDOW"] == "Y" && $arPaySystem["IS_CASH"] != "Y"): ?>
											<?
											$orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
											$paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
											?>
											<? if (!$isBePaidCheckout): ?>
											<script>
												window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=$orderAccountNumber?>&PAYMENT_ID=<?=$paymentAccountNumber?>');
											</script>
											<? endif ?>
										<?=Loc::getMessage("SOA_PAY_LINK", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&PAYMENT_ID=".$paymentAccountNumber))?>
										<? if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
										<br/>
											<?=Loc::getMessage("SOA_PAY_PDF", array("#LINK#" => $arParams["PATH_TO_PAYMENT"]."?ORDER_ID=".$orderAccountNumber."&pdf=1&DOWNLOAD=Y"))?>
										<? endif ?>
										<? else: ?>
											<?=$arPaySystem["BUFFERED_OUTPUT"]?>
										<? endif ?>
										<? if ($bePaidRedirectUrl !== ''): ?>
											<script>
												window.location.replace('<?=CUtil::JSEscape($bePaidRedirectUrl)?>');
											</script>
										<? endif ?>
									</td>
								</tr>
							</table>

							<?
						}
						else
						{
							?>
							<span style="color:red;"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></span>
							<?
						}
					}
					else
					{
						?>
						<span style="color:red;"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></span>
						<?
					}
				}
			}
		}
	}
	else
	{
		?>
		<br /><strong><?=$arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR']?></strong>
		<?
	}
	?>

<? else: ?>

	<b><?=Loc::getMessage("SOA_ERROR_ORDER")?></b>
	<br /><br />

	<table class="sale_order_full_table">
		<tr>
			<td>
				<?=Loc::getMessage("SOA_ERROR_ORDER_LOST", ["#ORDER_ID#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])])?>
				<?=Loc::getMessage("SOA_ERROR_ORDER_LOST1")?>
			</td>
		</tr>
	</table>

<? endif ?>