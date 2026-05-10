<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader,
	CPremier as Solution;

Loc::loadMessages(__FILE__);

$bAuthorized = $GLOBALS['USER']->isAuthorized();
$bSubscribe = Loader::includeModule('subscribe');
$bSale = Solution::isSaleMode() && Solution::isCabinetAvailable() && ((Solution::checkVersionModule('16.5.3', 'catalog') && !$bAuthorized) || $bAuthorized);

if ($bSale) {
	if ($arParams['SET_TITLE'] === 'Y') {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_SUBSCRIBE_SALE'));
	}
	$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_SUBSCRIBE_SALE'));
}
else {
	if ($arParams['SET_TITLE'] === 'Y') {
		$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_SUBSCRIBE'));
	}
	$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_SUBSCRIBE'));
}
?>
<div class="personal__wrapper">
	<?if ($bSubscribe):?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:subscribe.edit", 
			"main", 
			array(
				"AJAX_MODE" => "N",
				"SHOW_HIDDEN" => "N",
				"ALLOW_ANONYMOUS" => "Y",
				"SHOW_AUTH_LINKS" => "Y",
				"CACHE_TYPE" => "A",
				"CACHE_TIME" => "36000000",
				"SET_TITLE" => "N",
				"AJAX_OPTION_SHADOW" => "Y",
				"AJAX_OPTION_JUMP" => "N",
				"AJAX_OPTION_STYLE" => "Y",
				"AJAX_OPTION_HISTORY" => "N",
				"COMPONENT_TEMPLATE" => "main",
				"AJAX_OPTION_ADDITIONAL" => "",
				"COMPOSITE_FRAME_MODE" => "A",
				"COMPOSITE_FRAME_TYPE" => "AUTO"
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>

	<?if ($bSale):?>
		<?$APPLICATION->IncludeComponent(
			"aspro:wrapper.block.premier", 
			"subscribe", 
			array(
				"CACHE_FILTER" => "N",
				"CACHE_GROUPS" => "N",
				"CACHE_TIME" => "36000000",
				"CACHE_TYPE" => "A",
				"ELEMENT_COUNT" => "999",
				"COMPONENT_TEMPLATE" => "subscribe",
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);?>
	<?endif;?>
</div>
