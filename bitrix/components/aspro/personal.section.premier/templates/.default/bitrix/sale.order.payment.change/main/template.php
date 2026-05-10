<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

TSolution\Popover\Tooltip::initExtensions();

if ($arResult['PAYSYSTEMS_LIST']) {
	$selectedPaySystemId = 0;
	foreach ($arResult['PAYSYSTEMS_LIST'] as &$paySystem) {
		if ($paySystem['SELECTED'] = $paySystem['ID'] == $arResult['PAYMENT']['PAY_SYSTEM_ID']) {
			$selectedPaySystemId = $paySystem['ID'];
		}
	}
	unset($paySystem);
	
	if (!$selectedPaySystemId) {
		$arResult['PAYSYSTEMS_LIST'][0]['SELECTED'] = true;
	}
}

$parentComponent = $this->__component->__parent;

$paymentTitle = Loc::getMessage('SPOD_PAYMENT_TITLE', ['#ID#' => $arResult['PAYMENT']['ACCOUNT_NUMBER'] ?: $arResult['PAYMENT']['ID']]);
// if (isset($arResult['PAYMENT']['DATE_BILL'])) {
// 	$paymentTitle .= ' '.Loc::getMessage('SPOD_FROM').' '.$arResult['PAYMENT']['DATE_BILL_FORMATED'];
// }

$svgIconsSprite = $this->__folder.'/images/svg/icons.svg';
$wrapperId = rand(0, 10000);

$descPopover = new TSolution\Popover\Tooltip();
?>
<div class="personal__block personal__block--change-payment p p--32">
	<div class="personal__top-form bordered outer-rounded-x personal__top-form--change-payment bx-sopc" id="bx-sopc<?=$wrapperId?>">
		<?if ($arResult['errorMessage']):?>
			<div class="alert alert-danger"><?=implode('<br />', (array) $arResult['errorMessage']);?></div>
		<?else:?>
			<h5><?=$paymentTitle?></h5>

			<form action="" method="post" class="form">
				<div class="form-header">
					<div class="text">
						<div class="title switcher-title font_24 color_222"><?=$paymentTitle?></div>
					</div>
				</div>

				<div class="form-body">
					<div class="form-group form-group--paysystems">
						<div class="line-block line-block--20 line-block--20-vertical flexbox--wrap flexbox--direction-row">
							<?foreach ($arResult['PAYSYSTEMS_LIST'] as $key => $paySystem):?>
								<div class="line-block__item">
									<div class="paysystem">
										<div class="form-radiobox">
											<input id="PAY_SYSTEM_ID_<?=$paySystem['ID']?>" type="radio" class="form-radiobox__input" name="PAY_SYSTEM_ID" value="<?=$paySystem['ID']?>" <?=($paySystem['SELECTED'] ? ' checked' : '')?> />

											<label for="PAY_SYSTEM_ID_<?=$paySystem['ID']?>" class="form-radiobox__label bordered outer-rounded-x">
												<span><?=CUtil::JSEscape(htmlspecialcharsbx($paySystem['NAME']))?></span>

												<?if (strlen($paySystem['DESCRIPTION'])):?>
													<span class="paysystem__description xpopover-toggle fill-grey-hover" <?$descPopover->showToggleAttrs()?>>
														<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#description-16-16', '', ['WIDTH' => 16,'HEIGHT' => 16]);?>

														<?$descPopover->showContent($paySystem['DESCRIPTION']);?>
													</span>
												<?endif;?>
												<span class="form-radiobox__box"></span>
											</label>
										</div>
									</div>
								</div>
							<?endforeach;?>
						</div>
					</div>
				</div>

				<div class="form-footer">
					<div class="form-footer__buttons mt mt--24">
						<button type="submit" class="btn btn-default btn-lg" value="Y"><?=Loc::getMessage('SOPC_BUTTON')?></button>
					</div>
				</div>

				<script>
				new BX.Sale.OrderPaymentChange(<?=CUtil::PhpToJSObject([
					'wrapperId' => $wrapperId,
					'url' => CUtil::JSEscape($parentComponent ? SITE_DIR.'ajax/form.php?form_id=change_payment' : $this->__component->GetPath().'/ajax.php'),
					'templateName' => CUtil::JSEscape($templateName),
					'templateFolder' => CUtil::JSEscape($templateFolder),
					'orderId' => $arResult['PAYMENT']['ORDER_ID'],
					'accountNumber' => $arParams['ACCOUNT_NUMBER'],
					'paymentNumber' => $arParams['PAYMENT_NUMBER'],
					'inner' => $arParams['ALLOW_INNER'],
					'onlyInnerFull' => $arParams['ONLY_INNER_FULL'],
					'refreshPrices' => $arParams['REFRESH_PRICES'],
					'pathToPayment' => $arParams['PATH_TO_PAYMENT'],
					'returnUrl' => $arParams['RETURN_URL'],

					'parentComponent' => [
						'name' => $parentComponent ? $parentComponent->__name : '',
						'template' => $parentComponent ? $parentComponent->__template->__name : '',
						'page' => $parentComponent ? $parentComponent->__templatePage : '',
					],
				])?>);
				</script>
			</form>
		<?endif;?>
	</div>
</div>