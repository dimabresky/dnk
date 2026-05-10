<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	CPremier as Solution;

Loc::loadMessages(__FILE__);

$bAuthorized = $GLOBALS['USER']->isAuthorized();
$bSubscribe = Loader::includeModule('subscribe');
$bSale = Solution::isSaleMode() && Solution::isCabinetAvailable() && ((Solution::checkVersionModule('16.5.3', 'catalog') && !$bAuthorized) || $bAuthorized);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_UNSUBSCRIBE'));
}

if ($bSale) {
	$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_SUBSCRIBE_SALE'));
}
else {
	$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_SUBSCRIBE'));
}
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.mail.unsubscribe",
		"",
		Array(
			"COMPOSITE_FRAME_MODE" => "A",
			"COMPOSITE_FRAME_TYPE" => "AUTO"
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);?>
</div>
