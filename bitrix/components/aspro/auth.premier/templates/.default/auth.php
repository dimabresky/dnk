<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION, $USER, $arTheme;
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$APPLICATION->SetTitle(GetMessage('TITLE'));
$APPLICATION->SetPageProperty('TITLE_CLASS', 'center');
$APPLICATION->SetPageProperty('MENU', 'N');

$bPopupAuth = (isset($_POST['POPUP_AUTH']) ? $_POST['POPUP_AUTH'] === 'Y' : false);
?>
<?if(!$USER->IsAuthorized()):?>
	<?if(!isset($_SERVER["HTTP_X_REQUESTED_WITH"])):?>
		<div class="pk-page">
			<div class="form">
				<div class="top-text font_15">
					<?$APPLICATION->IncludeFile(SITE_DIR."include/auth_description.php", Array(), Array("MODE" => "html", "NAME" => GetMessage("AUTH_INCLUDE_AREA")));?>
				</div>
			</div>
		</div>
	<?endif;?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:system.auth.form",
		"main",
		Array(
			"AUTH_URL" => $arResult["SEF_FOLDER"].$arResult["URL_TEMPLATES"]["auth"],
			"REGISTER_URL" => $arResult["SEF_FOLDER"].$arResult["URL_TEMPLATES"]["registration"],
			"FORGOT_PASSWORD_URL" => $arResult["SEF_FOLDER"].$arResult["URL_TEMPLATES"]["forgot_password"],
			"CHANGE_PASSWORD_URL" => $arResult["SEF_FOLDER"].$arResult["URL_TEMPLATES"]["change_password"],
			"PROFILE_URL" => $arResult["SEF_FOLDER"],
			"SHOW_ERRORS" => "Y",
			"POPUP_AUTH" => $bPopupAuth ? 'Y' : 'N',
			"BACKURL" => ((isset($_REQUEST['backurl']) && $_REQUEST['backurl']) ? $_REQUEST['backurl'] : "")
		)
	);?>
<?elseif(strlen($_REQUEST['backurl'])):?>
	<script>location.href = <?var_export($_REQUEST['backurl'])?></script>
<?else:?>
	<?$url = ($arTheme["PERSONAL_PAGE_URL"]["VALUE"] ? $arTheme["PERSONAL_PAGE_URL"]["VALUE"] : $arParams["SEF_FOLDER"]);?>
	<?if (
		strpos($_SERVER['HTTP_REFERER'], $url) === false &&
		strpos($_SERVER['HTTP_REFERER'], SITE_DIR.'ajax/form.php') === false
	):?>
		<?LocalRedirect($url);?>
	<?endif;?>
<?endif;?>