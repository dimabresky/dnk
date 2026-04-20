<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

global $arTheme, $APPLICATION;
\TSolution\Extensions::init(['contacts', 'map']);

$bUseMap = TSolution::GetFrontParametrValue('CONTACTS_USE_MAP', SITE_ID) != 'N';
$typeMap = TSolution::GetFrontParametrValue('CONTACTS_TYPE_MAP', SITE_ID);
$bUseFeedback = TSolution::GetFrontParametrValue('CONTACTS_USE_FEEDBACK', SITE_ID) != 'N';

$APPLICATION->SetPageProperty("HIDE_LEFT_BLOCK", "Y");

Bitrix\Main\Loader::includeModule('catalog');

$arStore = CCatalogStore::GetList(array('ID' => 'ASC'), array('ID' => $arResult['STORE']), false, false, array("ID"))->Fetch();
?>
<?if($arStore):?>
	<?$APPLICATION->IncludeComponent(
		"bitrix:catalog.store.detail",
		"main",
		Array(
			"CACHE_TIME" => $arParams["CACHE_TIME"],
			"STORE" => $arResult["STORE"],
			"TITLE" => $arParams["TITLE"],
			"PATH_TO_ELEMENT" => $arResult["PATH_TO_ELEMENT"],
			"PATH_TO_LISTSTORES" => $arResult["PATH_TO_LISTSTORES"],
			"SET_TITLE" => $arParams["SET_TITLE"],
			"USE_MAP" => $bUseMap ? "Y" : "N",
			"MAP_TYPE" => $typeMap === 'GOOGLE' ? 1 : 0,
			"USE_FEEDBACK" => $bUseFeedback,
		),
		$component
	);?>
<?else:?>
	<?TSolution::goto404Page();?>
<?endif;?>
<?
if ($arParams['SET_TITLE'] == 'Y') {
	$APPLICATION->SetTitle($_SESSION['SHOP_TITLE']);
	$APPLICATION->SetPageProperty("title", $_SESSION['SHOP_TITLE']);
	$APPLICATION->AddChainItem($_SESSION['SHOP_TITLE'], "");
}
?>

<?$this->SetViewTarget('more_text_title');?>
	<?// share top?>
	<?if($arStore):?>
		<?TSolution\Extensions::init('share');?>
		<?TSolution\Functions::showHeadingIcons([
			'CONTENT' => TSolution\Functions::showShareBlock(
			array(
				'INNER_CLASS' => 'item-action__inner item-action__inner--sm',
				'CLASS' => 'top',
				'RETURN' => true,
			)
		)]);?>
	<?endif;?>
<?$this->endViewTarget()?>

<div class="bottom-links-block">
    <?// back url?>
    <?TSolution\Functions::showBackUrl(
		array(
			'URL' => $arResult['FOLDER'].$arResult['SEF_URL_TEMPLATES']['liststores'],
            'TEXT' => ($arParams['T_PREV_LINK'] ? $arParams['T_PREV_LINK'] : GetMessage('BACK_LINK')),
		)
	);?>
</div>