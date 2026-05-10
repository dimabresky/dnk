<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_PROFILES'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_PROFILES'));

$bHasDetailUrl = ($arResult['PATH_TO_PROFILE_DETAIL'] != $arParams['SEF_FOLDER']) && ($arResult['PATH_TO_PROFILE_DETAIL'] != $arResult['PATH_TO_PROFILES']);

$arComponentParams = [
	"SHOW_DETAIL_LINK" => $bHasDetailUrl ? 'Y' : 'N',
	"PATH_TO_DETAIL" => $arResult["PATH_TO_PROFILE_DETAIL"],
	"PATH_TO_DELETE" => $arResult["PATH_TO_PROFILE_DELETE"],
	"PER_PAGE" => 999,
	"SET_TITLE" => "N",
];

foreach ($arParams as $key => $value) {
	if (preg_match('/^PROP_\d+_PROFILE_LIST$/', $key, $match)) {
		$arComponentParams[$key] = is_array($value) ? $value : (strlen($value) ? (array) $value : []);
	}
}

$this->__component->correctUserProfilesPhones();
?>
<div class="personal__wrapper">
	<?if ($arResult['AJAX_POST']) {
		$GLOBALS['APPLICATION']->RestartBuffer();

		$APPLICATION->ShowAjaxHead();
	}?>

	<?$APPLICATION->IncludeComponent(
		"bitrix:sale.personal.profile.list",
		"main",
		$arComponentParams,
		$component,
		array("HIDE_ICONS" => "Y")
	);?>

	<?if ($arResult['AJAX_POST']) {
		die();
	}?>
</div>
