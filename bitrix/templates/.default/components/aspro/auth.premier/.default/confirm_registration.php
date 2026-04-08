<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION, $USER, $arTheme;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$APPLICATION->AddChainItem(GetMessage("TITLE"));
$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->SetPageProperty("TITLE_CLASS", "center");
$APPLICATION->SetPageProperty('MENU', 'N');
?>
<?if(!$USER->IsAuthorized()):?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:system.auth.confirmation",
		"main",
		array(
			"USER_ID" => "confirm_user_id", 
			"CONFIRM_CODE" => "confirm_code", 
			"LOGIN" => "login",
			"URL" => $arParams["SEF_FOLDER"].$arParams["SEF_URL_TEMPLATES"]["confirm"], 
		)
	);?>
<?else:?>
	<?
	$url = ($arTheme["PERSONAL_PAGE_URL"]["VALUE"] ? $arTheme["PERSONAL_PAGE_URL"]["VALUE"] : $arParams["SEF_FOLDER"]);
	LocalRedirect($url);
	?>
<?endif;?>