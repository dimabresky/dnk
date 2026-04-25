<?if( !defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true ) die();?>
<?$this->setFrameMode(true);?>
<?
global $APPLICATION;
use \Bitrix\Main\Localization\Loc;
// get element
$arItemFilter = TSolution::GetCurrentElementFilter($arResult["VARIABLES"], $arParams);
$arElement = TSolution\Cache::CIblockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "N")), $arItemFilter, false, false, array("ID", 'PREVIEW_TEXT', "IBLOCK_SECTION_ID", 'DETAIL_PICTURE', 'DETAIL_PAGE_URL', 'PROPERTY_LINK_REVIEWS', 'PROPERTY_DOCUMENTS'));

//bug fix bitrix for search element
if($arElement)
{
	$strict_check = $arParams["DETAIL_STRICT_SECTION_CHECK"] === "Y";
	if(!CIBlockFindTools::checkElement($arParams["IBLOCK_ID"], $arResult["VARIABLES"], $strict_check))
		$arElement = array();
}
?>
<?if(!$arElement && $arParams['SET_STATUS_404'] !== 'Y'):?>
	<div class="alert alert-warning"><?=GetMessage("ELEMENT_NOTFOUND")?></div>
<?elseif(!$arElement && $arParams['SET_STATUS_404'] === 'Y'):?>
	<?TSolution::goto404Page();?>
<?else:?>
	<?TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);?>

	<?$this->SetViewTarget('more_text_title');?>
		<?//share?>
		<?if($arParams['USE_SHARE'] === 'Y' && $arElement):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::showShareBlock(
					array(
						'INNER_CLASS' => ' item-action__inner item-action__inner--sm',
						'CLASS' => 'top',
						'RETURN' => true,
					)
				)]);?>
		<?endif;?>
		<?// rss?>
		<?if($arParams['USE_RSS'] !== 'N'):?>
			<?TSolution\Functions::showHeadingIcons([
				'CONTENT' => TSolution\Functions::ShowRSSIcon(
					array(
						'INNER_CLASS' => ' item-action__inner item-action__inner--sm',
						'URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['rss'],
						'RETURN' => true,
					)
				)])?>
		<?endif;?>
	<?$this->endViewTarget()?>

	<?TSolution::AddMeta(
		array(
			'og:description' => $arElement['PREVIEW_TEXT'],
			'og:image' => (($arElement['PREVIEW_PICTURE'] || $arElement['DETAIL_PICTURE']) ? CFile::GetPath(($arElement['PREVIEW_PICTURE'] ? $arElement['PREVIEW_PICTURE'] : $arElement['DETAIL_PICTURE'])) : false),
		)
	);?>
	<div class="detail <?=($templateName = $component->{'__template'}->{'__name'})?>">
		<?//element?>
		<?@include_once "page_blocks/{$arParams["ELEMENT_TYPE_VIEW"]}.php";?>

		<?
		if(is_array($arElement["IBLOCK_SECTION_ID"]) && count($arElement["IBLOCK_SECTION_ID"]) > 1){
			TSolution::CheckAdditionalChainInMultiLevel($arResult, $arParams, $arElement);
		}
		?>
		<div class="bottom-links-block">
			<?// back url?>
			<?TSolution\Functions::showBackUrl([
				'URL' => ((isset($arSection) && $arSection) ? $arSection['SECTION_PAGE_URL'] : $arResult['FOLDER'].$arResult['URL_TEMPLATES']['news']),
				'TEXT' => ($arParams['T_PREV_LINK'] ? $arParams['T_PREV_LINK'] : GetMessage('BACK_LINK')),
			]);?>
		</div>
	</div>
<?endif;?>