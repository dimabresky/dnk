<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	CPremier as Solution;

$url = htmlspecialcharsbx($arResult['PATH_TO_ACCOUNT']);
$bShowDetailLink = $arParams['SHOW_ACCOUNT_PAGE'] !== 'N';

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
<div class="personal__main-private__wrapper personal__main-private__wrapper--account grid-list__item">
	<div class="personal__main-private p p--24 bordered outer-rounded-x shadow-hovered shadow-hovered-f600 shadow-no-border-hovered stroke-grey-parent">
		<?if ($bShowDetailLink):?>
			<a class="item-link-absolute" href="<?=$url?>" title="<?=htmlspecialcharsbx(Loc::getMessage('SPS_MAIN_BLOCK_TITLE_ACCOUNT'))?>"></a>
		<?endif;?>

		<div class="personal__main-private__inner">
			<div class="personal__main-private__top">
				<?if ($bShowDetailLink):?>
					<span class="main-block__link">
						<span class="main-block__arrow">
							<?=Solution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#right-hollow', 'stroke-grey-target', [
								'WIDTH' => 6, 
								'HEIGHT' => 12
							]);?>
						</span>
					</span>
				<?endif;?>

				<?if ($arParams['SHOW_ACCOUNT_COMPONENT'] !== 'N'):?>	
					<?$APPLICATION->IncludeComponent(
						"bitrix:sale.personal.account",
						"index",
						Array(
							"SET_TITLE" => "N",
							"PATH_TO_ACCOUNT" => $arResult["PATH_TO_ACCOUNT"],
							"DATE_FORMAT" => $arParams["DATE_FORMAT"],
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);?>
				<?else:?>
					<div class="personal__main-private__title font_clamp--16-14 color-theme-target"><?=Loc::getMessage('SPS_MAIN_BLOCK_TITLE_ACCOUNT')?></div>
					<div class="personal__main-private__value switcher-title color_dark font_24 mt mt--4"></div>
				<?endif;?>
			</div>
			<div class="personal__main-private__bottom font_clamp--16-14">
				<?if ($arParams['SHOW_ACCOUNT_PAY_COMPONENT'] !== 'N'):?>
					<?
					$dataParams = [
						'PARENT_COMPONENT' => $this->__component->__name,
						'PARENT_COMPONENT_TEMPLATE' => $this->__name,
						'PARENT_COMPONENT_PAGE' => $this->__component->__templatePage,
						
						'REFRESHED_COMPONENT_MODE' => 'N',
						'ELIMINATED_PAY_SYSTEMS' => $arParams['ACCOUNT_PAYMENT_ELIMINATED_PAY_SYSTEMS'],
						'PATH_TO_BASKET' => $arResult['PATH_TO_BASKET'],
						'PATH_TO_PAYMENT' => $arResult['PATH_TO_PAYMENT'],
						'PERSON_TYPE' => $arParams['ACCOUNT_PAYMENT_PERSON_TYPE'],
						'REDIRECT_TO_CURRENT_PAGE' => 'N',
						'SELL_TOTAL' => $arAmounts,
						'SELL_CURRENCY' => $arParams['ACCOUNT_PAYMENT_SELL_CURRENCY'],
						'SELL_SHOW_FIXED_VALUES' => $arParams['ACCOUNT_PAYMENT_SELL_SHOW_FIXED_VALUES'],
						'SELL_SHOW_RESULT_SUM' => $arParams['ACCOUNT_PAYMENT_SELL_SHOW_RESULT_SUM'],
						'SELL_TOTAL' => $arAmounts,
						'SELL_USER_INPUT' => $arParams['ACCOUNT_PAYMENT_SELL_USER_INPUT'],
						'SELL_VALUES_FROM_VAR' => 'N',
						'SELL_VAR_PRICE_VALUE' => '',
						'SET_TITLE' => 'N',
					];
		
					$dataParams = $GLOBALS['APPLICATION']->ConvertCharsetArray($dataParams, SITE_CHARSET, 'UTF-8');
					$dataParams = json_encode($dataParams);
					$dataParams = htmlspecialcharsbx($dataParams);
					?>
					<span class="btn btn-xs btn-default btn-transparent-bg personal__main-account__replenish-balance" data-event="jqm" data-name="replenishment" data-param-form_id="replenishment" data-param-params="<?=$dataParams?>"><?=Loc::getMessage('SPS_REPLENISH_ACCOUNT')?></span>
				<?else:?>
					<?if ($bShowDetailLink):?>
						<a class="btn btn-xs btn-default btn-transparent-bg personal__main-account__more-details" href="<?=$url?>"><?=Loc::getMessage('SPS_MORE_DETAILS')?></a>
					<?endif;?>
				<?endif;?>
			</div>
		</div>
	</div>
</div>		
