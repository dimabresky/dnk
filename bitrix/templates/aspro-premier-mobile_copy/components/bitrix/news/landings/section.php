<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

global $arTheme, $APPLICATION;

// $bShowLeftBlock = ($arTheme['NEWS_PAGE_LEFT_BLOCK']['VALUE'] === 'Y' && !defined('ERROR_404'));
// $APPLICATION->SetPageProperty('MENU', ($bShowLeftBlock ? 'Y' : 'N' ));

// geting section items count and section
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

$arItemFilter = TSolution::GetCurrentSectionElementFilter($arResult["VARIABLES"], $arParams);
$arSectionFilter = TSolution::GetCurrentSectionFilter($arResult["VARIABLES"], $arParams);
$itemsCnt = TSolution\Cache::CIblockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());
$arSection = TSolution\Cache::CIblockSection_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arSectionFilter, false, array('ID', 'NAME', 'DESCRIPTION', 'PICTURE', 'DETAIL_PICTURE'), true);
?>
<?if(!$arSection && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("SECTION_NOTFOUND")?></div>
<?elseif(!$arSection && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?TSolution::goto404Page();?>
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