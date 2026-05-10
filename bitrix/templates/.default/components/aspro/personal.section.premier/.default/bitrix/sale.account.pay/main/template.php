<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

TSolution\Extensions::init(['chip']);
TSolution\Popover\Tooltip::initExtensions();

$svgIconsSprite = $this->__folder.'/images/svg/icons.svg';
$wrapperId = str_shuffle(mb_substr($arResult['SIGNED_PARAMS'], 0, 10));

$descPopover = new TSolution\Popover\Tooltip();
?>
<div class="personal__block personal__block--account-replenish">
	<div class="personal__top-form bordered outer-rounded-x personal__top-form--replenish bx-sap p p--32" id="bx-sap<?=$wrapperId?>">
		<?if($arResult['errorMessage']):?>
			<div class="alert alert-danger"><?=implode('<br />', (array)$arResult['errorMessage'])?></div>
		<?else:?>
			<h4><?=Loc::getMessage('SAP_BUY_MONEY')?></h4>

			<form action="" method="post" class="form mt mt--24">
				<div class="form-header">
					<div class="text">
						<div class="title switcher-title font_24 color_222"><?=Loc::getMessage('SAP_BUY_MONEY')?></div>
					</div>
				</div>

				<div class="form-body">
					<?if ($arParams['SELL_VALUES_FROM_VAR'] != 'Y'):?>
						<div class="form-group form-group--input">
							<label for="<?=CUtil::JSEscape(htmlspecialcharsbx($arParams['VAR']))?>" class="font_13 color_dark"><?=Loc::getMessage('SAP_SUM')?>&nbsp;<span class="required-star">*</span></label>

							<div class="line-block line-block--12 flexbox--direction-row">
								<div class="line-block__item" style="width:100%;">
									<div class="input">
										<input type="text" class="form-control input-lg sale-acountpay-input" value="" name="<?=CUtil::JSEscape(htmlspecialcharsbx($arParams['VAR']))?>"<?=($arParams['SELL_USER_INPUT'] === 'N' ? ' disabled' : '')?> />
										<span class="currency-label"><?=$arResult['FORMATED_CURRENCY']?></span>
									</div>
								</div>

								<div class="line-block__item line-block__item--btn">
									<button type="submit" class="btn btn-default btn-lg" value="Y"><?=Loc::getMessage('SAP_BUTTON')?></button>
								</div>
							</div>

							<?if ($arParams['SELL_SHOW_FIXED_VALUES'] === 'Y'):?>
								<div class="fixedpay mt mt--12">
									<div class="line-block line-block--gap line-block--gap-8 flexbox--direction-row flexbox--wrap">
										<?foreach ($arParams['SELL_TOTAL'] as $valueChanging):?>
											<div class="line-block__item font_13">
												<a hrf="javascript:void(0);" class="chip chip--pill-shape">
													<span class="chip__label"><?=CUtil::JSEscape(htmlspecialcharsbx($valueChanging))?></span>
												</a>
											</div>
										<?endforeach;?>
									</div>
								</div>
							<?endif;?>
						</div>
					<?else:?>
						<?if ($arParams['SELL_SHOW_RESULT_SUM'] === 'Y'):?>
							<div class="form-group">
								<h3 class="sale-acountpay-title"><?=Loc::getMessage('SAP_SUM')?></h3>
								<h2><?=SaleFormatCurrency($arResult['SELL_VAR_PRICE_VALUE'], $arParams['SELL_CURRENCY'])?></h2>
							</div>
						<?endif;?>

						<input type="hidden" name="<?=CUtil::JSEscape(htmlspecialcharsbx($arParams['VAR']))?>" class="sale-acountpay-input" value="<?=CUtil::JSEscape(htmlspecialcharsbx($arResult['SELL_VAR_PRICE_VALUE']))?>" />
					<?endif;?>

					<div class="form-group form-group--paysystems">
						<div class="grid-list grid-list--items grid-list--items-2-from-601">
							<?foreach ($arResult['PAYSYSTEMS_LIST'] as $key => $paySystem):?>
								<div class="grid-list__item">
									<div class="paysystem">
										<div class="form-radiobox">
											<input id="PAY_SYSTEM_ID_<?=$paySystem['ID']?>" type="radio" class="form-radiobox__input" name="PAY_SYSTEM_ID" value="<?=$paySystem['ID']?>" <?=(!$key ? ' checked' : '')?> />

											<label for="PAY_SYSTEM_ID_<?=$paySystem['ID']?>" class="form-radiobox__label flexbox--wrap bordered outer-rounded-x">
												<span><?=CUtil::JSEscape(htmlspecialcharsbx($paySystem['NAME']))?></span>

												<?if (strlen($paySystem['DESCRIPTION'])):?>
													<span class="paysystem__description xpopover-toggle fill-grey-hover" <?$descPopover->showToggleAttrs()?>>
														<?=TSolution::showSpriteIconSvg($svgIconsSprite.'#description-16-16', '', ['WIDTH' => 16,'HEIGHT' => 16]);?>

														<?$descPopover->showContent($paySystem['DESCRIPTION']);?>
													</span>
												<?endif;?>

												<?if (strlen($paySystem['LOGOTIP'])):?>
													<span class="paysystem__logo mt mt--24">
														<img src="<?=$paySystem['LOGOTIP']?>" title="<?=htmlspecialcharsbx($paySystem['NAME'])?>" />
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

				<div class="form-footer"<?=($arParams['SELL_VALUES_FROM_VAR'] === 'Y' ? ' style="display:block;"' : '')?>>
					<div class="form-footer__buttons">
						<button type="submit" class="btn btn-default btn-lg" value="Y"><?=Loc::getMessage('SAP_BUTTON')?></button>
					</div>
				</div>
				
				<script>
				new BX.saleAccountPay(<?=CUtil::PhpToJSObject([
					'wrapperId' => $wrapperId,
					'templateName' => $this->__component->GetTemplateName(),
					'signedParams' => $arResult['SIGNED_PARAMS'],
					'templateFolder' => CUtil::JSEscape($templateFolder),
					'url' => CUtil::JSEscape($this->__component->GetPath().'/ajax.php'),
					'alertMessages' => [
						'wrongInput' => Loc::getMessage('SAP_ERROR_INPUT'),
					],
				])?>);
				</script>
			</form>
		<?endif;?>
	</div>
</div>