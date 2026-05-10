<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

$profileId = $arResult['VARIABLES']['ID'];
$profileName = $profileId;
if ($profileId > 0) {
	if (Loader::includeModule('sale')) {
		$profile = Bitrix\Sale\OrderUserProperties::getList(
			[
				'filter' => [
					'ID' => $profileId,
				],
				'select' => [
					'ID',
					'NAME',
					'USER_ID',
				],
			]
		)->fetch();
				
		if ($profile) {
			if ($profile['USER_ID'] == $GLOBALS['USER']->GetId()) {
				$profileName = htmlspecialcharsbx($profile['NAME']);
			}
		}
	}
}

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_PROFILE', ['#ID#' => $profileName]));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_PROFILES'), $arResult['PATH_TO_PROFILES']);
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_PROFILE', ['#ID#' => $profileName]));

$this->__component->correctUserProfilesPhones();
?>
<div class="personal__wrapper">
	<?if ($arResult['AJAX_POST']) {
		$GLOBALS['APPLICATION']->RestartBuffer();

		$APPLICATION->ShowAjaxHead();
	}?>

	<?$arTmp = $APPLICATION->IncludeComponent(
		"bitrix:sale.personal.profile.detail",
		"main",
		array(
			"PATH_TO_LIST" => $arResult["PATH_TO_PROFILES"],
			"PATH_TO_DETAIL" => $arResult["PATH_TO_PROFILE_DETAIL"],
			"USE_AJAX_LOCATIONS" => $arParams['USE_AJAX_LOCATIONS_PROFILE'],
			"SET_TITLE" => "N",
			"ID" => $arResult["VARIABLES"]["ID"],
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);?>

	<?if ($arResult['AJAX_POST']) {
		die();
	}?>
</div>

<div class="bottom-links-block">
	<?// back url?>
	<?TSolution\Functions::showBackUrl(
		array(
			'URL' => $arResult['PATH_TO_PROFILES'],
			'TEXT' => Loc::getMessage('SPS_BACK_LINK'),
		)
	);?>
</div>