<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$parentComponent = $this->__component->__parent;

$wrapperId = rand(0, 10000);
?>
<?$GLOBALS['APPLICATION']->RestartBuffer();?>

<?if ($arResult['errorMessage']):?>
	<div class="alert alert-danger"><?=implode('<br />', (array) $arResult['errorMessage']);?></div>
<?else:?>
	<?if ($arResult['IS_ALLOW_PAY'] == 'N'):?>
		<div class="alert alert-warning">
			<p><b><?=Loc::getMessage('SOPC_PAY_SYSTEM_CHANGED')?></b></p>
			<p><?=Loc::getMessage('SOPC_PAY_SYSTEM_NOT_ALLOW_PAY')?></p>
		</div>
	<?elseif ($arResult['SHOW_INNER_TEMPLATE'] == 'Y'):?>
		<div class="bx-sopc" id="bx-sopc-inner<?=$wrapperId?>">										
			<p class="font_14">
				<span><?=Loc::getMessage('SOPC_TPL_SUM_TO_PAID')?>:</span>
				<span><b><?=SaleFormatCurrency($arResult['PAYMENT']['SUM'], $arResult['PAYMENT']["CURRENCY"])?></b></span>

				<br />

				<span><?=Loc::getMessage('SOPC_INNER_BALANCE')?>:</span>
				<span><b><?=SaleFormatCurrency($arResult['INNER_PAYMENT_INFO']['CURRENT_BUDGET'], $arResult['INNER_PAYMENT_INFO']["CURRENCY"])?></b></span>
			</p>

			<?if (
				(
					$arParams['ONLY_INNER_FULL'] !== 'Y' &&
					(float)$arResult['INNER_PAYMENT_INFO']['CURRENT_BUDGET'] > 0
				) ||
				(
					$arParams['ONLY_INNER_FULL'] === 'Y' &&
					$arResult['INNER_PAYMENT_INFO']['CURRENT_BUDGET'] >= $arResult['PAYMENT']['SUM']
				)
			):?>
				<?
				$inputSum = $arResult['INNER_PAYMENT_INFO']['CURRENT_BUDGET'] > $arResult['PAYMENT']['SUM'] ? $arResult['PAYMENT']['SUM'] : $arResult['INNER_PAYMENT_INFO']['CURRENT_BUDGET'];
				?>
				<?if ($arParams['ONLY_INNER_FULL'] !== 'Y'):?>
					<div class="form-group form-group--input">
						<label for="INNER_PAYMENT_VALUE" class="font_14"><?=Loc::getMessage('SOPC_SUM_OF_PAYMENT')?>: <span class="required-star">*</span></label>

						<div class="input">
							<input id="INNER_PAYMENT_VALUE" type="text" class="form-control input-lg inner-payment-form-control" value="<?=(float)$inputSum?>" name="payInner">
							<span class="currency-label"><?=str_replace('&8381;', '&#8381;', $arResult['INNER_PAYMENT_INFO']['FORMATED_CURRENCY'])?></span>
						</div>

						<div class="text_block font_13"><?=Loc::getMessage('SOPC_HANDLERS_PAY_SYSTEM_WARNING_RETURN')?></div>
					</div>
					<br />
				<?else:?>
					<input id="INNER_PAYMENT_VALUE" type="hidden" value="<?=(float)$inputSum?>" name="payInner">
				<?endif;?>

				<div class="sale-order-payment-change-payment-price">
					<button type="submit" class="btn btn-default btn-lg"<?=($inputSum <= 0 ? ' disabled' : '')?>><?=Loc::getMessage('SOPC_TPL_PAY_BUTTON')?></button>
				</div>

				<script>
				new BX.Sale.OrderInnerPayment(<?=CUtil::PhpToJSObject([
					'wrapperId' => $wrapperId,
					'url' => CUtil::JSEscape($parentComponent ? SITE_DIR.'ajax/form.php?form_id=change_payment' : $this->__component->GetPath().'/ajax.php'),
					'templateName' => CUtil::JSEscape($templateName),
					'templateFolder' => CUtil::JSEscape($templateFolder),
					'accountNumber' => $arParams['ACCOUNT_NUMBER'],
					'paymentNumber' => $arParams['PAYMENT_NUMBER'],
					'inner' => $arParams['ALLOW_INNER'],
					'onlyInnerFull' => $arParams['ONLY_INNER_FULL'],
					'valueLimit' => $inputSum,
					'returnUrl' => $arParams['RETURN_URL'],

					'parentComponent' => [
						'name' => $parentComponent ? $parentComponent->__name : '',
						'template' => $parentComponent ? $parentComponent->__template->__name : '',
						'page' => $parentComponent ? $parentComponent->__templatePage : '',
					],

					'alertMessages' => [
						'wrongInput' => Loc::getMessage('SOPC_ERROR_INPUT'),
					],
				])?>);
				</script>
			<?else:?>
				<div class="alert alert-warning">
					<?=Loc::getMessage('SOPC_LOW_BALANCE')?>
				</div>
			<?endif;?>
		</div>
	<?elseif (
		!$arResult['PAYMENT_LINK'] &&
		!$arResult['IS_CASH'] &&
		mb_strlen($arResult['TEMPLATE'])
	):?>
		<?/*
		<?=$arResult['TEMPLATE'];?>
		*/?>
	<?else:?>
		<?/*
		<div class="alert alert-success">
			<p>
				<?=Loc::getMessage(
				'SOPC_ORDER_SUC', [
					'#ORDER_ID#' => htmlspecialcharsbx($arResult['ORDER_ID']),
					'#ORDER_DATE#' => $arResult['ORDER_DATE'],
					]
				)?>
			</p>
			<p>
				<?=Loc::getMessage(
					'SOPC_PAYMENT_SUC', [
						'#PAYMENT_ID#' => htmlspecialcharsbx($arResult['PAYMENT_ID']),
					]
				)?>
			</p>
			<p>
				<?=Loc::getMessage(
					'SOPC_PAYMENT_SYSTEM_NAME', [
						'#PAY_SYSTEM_NAME#' => htmlspecialcharsbx($arResult['PAY_SYSTEM_NAME']),
					]
				)?>
			</p>
		</div>

		<?if (!$arResult['IS_CASH'] && !empty($arResult['PAYMENT_LINK'])):?>
			<br />
			<div class="font_13">
				<?=Loc::getMessage(
					'SOPC_PAY_LINK', [
						'#LINK#' => htmlspecialcharsbx($arResult['PAYMENT_LINK']),
					]
				)?>
			</div>
			<script>
			window.open('<?=CUtil::JSEscape($arResult['PAYMENT_LINK'])?>');
			</script>
		<?endif;?>

		<script>
		BX.onCustomEvent(
			'onOrderPaymentChange',
			[{
				accountNumber: '<?=$arParams['ACCOUNT_NUMBER']?>',
				paymentNumber: '<?=$arParams['PAYMENT_NUMBER']?>',
			}]
		);
		</script>
		*/?>
	<?endif;?>
<?endif;?>

<?die();?>