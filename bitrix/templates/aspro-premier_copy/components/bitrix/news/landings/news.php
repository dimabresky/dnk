<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?global $arTheme, $APPLICATION;?>

<?// intro text?>
<?ob_start();?>
<?$APPLICATION->IncludeComponent(
	"bitrix:main.include",
	"",
	Array(
		"AREA_FILE_SHOW" => "page",
		"AREA_FILE_SUFFIX" => "inc",
		"EDIT_TEMPLATE" => ""
	)
);?>
<?$html = ob_get_contents();
ob_end_clean();?>
<?if($html):?>
	<div class="text_before_items">
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "page",
				"AREA_FILE_SUFFIX" => "inc",
				"EDIT_TEMPLATE" => ""
			)
		);?>
	</div>
<?endif;?>
<?
$hideEnumId = 0;
if ($arParams['FILTER_NAME'] !== '') {
	$hideProp = \Dnk\PhpInterface\Utils::getIblockPropertyByCode((int) $arParams['IBLOCK_ID'], 'NOT_SHOW_ON_LIST');
	if ($hideProp && ($hideProp['PROPERTY_TYPE'] ?? '') === 'L') {
		$enumRes = \CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => (int) $hideProp['ID']]);
		while ($enum = $enumRes->Fetch()) {
			if ($enum['XML_ID'] === 'Y' || $enum['VALUE'] === 'Y') {
				$hideEnumId = (int) $enum['ID'];
				break;
			}
		}
	}
}
if ($hideEnumId > 0) {
	$GLOBALS[$arParams['FILTER_NAME']] = (array) ($GLOBALS[$arParams['FILTER_NAME']] ?? []);
	$GLOBALS[$arParams['FILTER_NAME']][] = [
		'LOGIC' => 'OR',
		['!PROPERTY_NOT_SHOW_ON_LIST' => $hideEnumId],
		['PROPERTY_NOT_SHOW_ON_LIST' => false],
	];
}

$arItemFilter = TSolution::GetIBlockAllElementsFilter($arParams);

if ($arParams['CACHE_GROUPS'] == 'Y') {
	$arItemFilter['CHECK_PERMISSIONS'] = 'Y';
	$arItemFilter['GROUPS'] = $GLOBALS["USER"]->GetGroups();
}

$itemsCnt = TSolution\Cache::CIblockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());?>

<?if (!$itemsCnt):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
<?else:?>
	<?TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);?>

	<?$this->SetViewTarget('more_text_title');?>
		<?if($arParams['USE_RSS'] !== 'N'):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::ShowRSSIcon(
					array(
						'INNER_CLASS' => ' item-action__inner item-action__inner--sm',
						'URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss'],
						'RETURN' => true,
					)
				)])?>
		<?$arExtensions[] = 'item_action';?>
		<?\TSolution\Extensions::init($arExtensions);?>
		<?endif;?>
	<?$this->endViewTarget()?>

	<?// show filter?>
	<?// include('include/filter.php')?>

	<?if (TSolution::checkAjaxRequest()):?>
		<?$APPLICATION->RestartBuffer()?>
	<?endif;?>

	<?// section elements?>
	<?$sViewElementsTemplate = ($arParams["SECTION_ELEMENTS_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["LANDINGS_PAGE"]["VALUE"] : $arParams["SECTION_ELEMENTS_TYPE_VIEW"]);?>

	<?@include_once('page_blocks/'.$sViewElementsTemplate.'.php');?>

	<?if (TSolution::checkAjaxRequest()):?>
		<?die()?>
	<?endif;?>

<?endif;?>