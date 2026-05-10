<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

$arParams['DATE_FORMAT'] = $arParams['DATE_FORMAT'] ?? '';
if (!strlen($arParams['DATE_FORMAT'])) {
	$arParams['DATE_FORMAT'] = 'j F Y';
}

$arResult['DATE'] = FormatDate($arParams['DATE_FORMAT'], time());
?>
<div class="personal__block personal__block--account">
	<div class="personal__account bordered outer-rounded-x p p--32 relative overflow-block height-100">
		<div class="personal__account__inner flexbox flexbox--justify-between relative height-100">
			<div class="personal__account__title font_13">
				<?=Loc::getMessage('SPA_BILL_AT', ['#DATE#' => $arResult['DATE']])?>
			</div>
			<div class="personal__account__value mt mt--16 font_28 fw-500 color_dark">
				<?if (is_array($arResult['ACCOUNT_LIST'])):?>
					<?foreach ($arResult['ACCOUNT_LIST'] as $accountValue):?>
						<div><?=$accountValue['SUM']?></div>
					<?endforeach;?>
				<?endif;?>
			</div>
		</div>
	</div>
</div>