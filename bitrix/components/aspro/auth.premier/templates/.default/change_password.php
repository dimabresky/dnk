<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION, $USER, $arTheme;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$APPLICATION->AddChainItem(GetMessage('TITLE'));
$APPLICATION->SetTitle(GetMessage('TITLE'));
$APPLICATION->SetPageProperty('TITLE_CLASS', 'center');
$APPLICATION->SetPageProperty('MENU', 'N');
?>
<?if(!$USER->IsAuthorized()):?>
	<?
	$arParams = array(
		"AUTH_URL" => $arParams["SEF_FOLDER"],
		"URL" => $arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["change"],
		"~AUTH_RESULT" => $APPLICATION->arAuthResult,
	);

	if(isset($_SESSION['arAuthResult'])){
		$arParams['AUTH_RESULT'] = $APPLICATION->arAuthResult = $_SESSION['arAuthResult'];
		unset($_SESSION['arAuthResult']);
	}
	?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:system.auth.changepasswd",
		"main",
		$arParams,
		false
	);?>
<?else:?>
	<?
	$url = ($arTheme["PERSONAL_PAGE_URL"]["VALUE"] ? $arTheme["PERSONAL_PAGE_URL"]["VALUE"] : $arParams["SEF_FOLDER"]);
	LocalRedirect($url);
	?>
<?endif;?>