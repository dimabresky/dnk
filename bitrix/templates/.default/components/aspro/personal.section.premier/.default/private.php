<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$consentsUrl = $arResult['PATH_TO_CONSENTS'] ?? rtrim($arParams['SEF_FOLDER'] ?? '', '/') . '/consents/';
$certRequestsUrl = $arResult['PATH_TO_CERTIFICATE_REQUESTS'] ?? rtrim($arParams['SEF_FOLDER'] ?? '', '/') . '/certificate_requests/';

if ($arParams['SET_TITLE'] === 'Y') {
	$APPLICATION->SetTitle(Loc::getMessage('SPS_TITLE_PRIVATE'));
}
$APPLICATION->AddChainItem(Loc::getMessage('SPS_CHAIN_PRIVATE'));

$this->__component->correctUserPhones();
?>
<div class="personal__wrapper">
	<?$APPLICATION->IncludeComponent(
		"bitrix:main.profile",
		"main",
		Array(
			"SET_TITLE" => "N",
			"AJAX_MODE" => $arParams["AJAX_MODE_PRIVATE"],
			"SEND_INFO" => $arParams["SEND_INFO_PRIVATE"],
			"CHECK_RIGHTS" => $arParams["CHECK_RIGHTS_PRIVATE"],
			"SHOW_CHANGE_PASSWORD_FORM" => $arResult["SHOW_CHANGE_PASSWORD_FORM"],
		),
		$component,
		array("HIDE_ICONS" => "Y")
	);?>
</div>

<div class="bottom-links-block mt mt--24">
	<a class="btn btn-default btn-transparent-bg" href="<?=htmlspecialcharsbx($consentsUrl)?>"><?=Loc::getMessage('SPS_CONSENTS_LINK')?></a>
	<a class="btn btn-default btn-transparent-bg" href="<?=htmlspecialcharsbx($certRequestsUrl)?>"><?=Loc::getMessage('SPS_CERTIFICATE_REQUESTS_LINK')?></a>
</div>