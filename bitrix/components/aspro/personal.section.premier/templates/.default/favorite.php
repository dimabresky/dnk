<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_CHAIN_FAVORITE'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_FAVORITE'));
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		"aspro:wrapper.block.premier", 
		"favorite", 
		array(
			"CACHE_FILTER" => "N",
			"CACHE_GROUPS" => "N",
			"CACHE_TIME" => "36000000",
			"CACHE_TYPE" => "A",
			"ELEMENT_COUNT" => "999",
			"COMPONENT_TEMPLATE" => "favorite",
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);?>
</div>
