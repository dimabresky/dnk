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
<div class="personal__main-private__title font_clamp--16-14 color-theme-target">
	<?=Loc::getMessage('SPA_BILL_AT', ['#DATE#' => $arResult['DATE']])?>
</div>
<div class="personal__main-private__value">
	<?if (is_array($arResult['ACCOUNT_LIST'])):?>
		<?foreach ($arResult['ACCOUNT_LIST'] as $accountValue):?>
			<div><?=$accountValue['SUM']?></div>
		<?endforeach;?>
	<?endif;?>
</div>