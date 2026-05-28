<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

global $arTheme, $APPLICATION;
$APPLICATION->AddViewContent('right_block_class', 'catalog_page ');

$bShowLeftBlock = ($arTheme['LEFT_BLOCK_CATALOG_ROOT']['VALUE'] === 'Y' && !defined('ERROR_404'));
$APPLICATION->SetPageProperty('MENU', 'N');
?>
<div class="main-wrapper flexbox flexbox--direction-row">
	<div class="section-content-wrapper <?=($bShowLeftBlock ? 'with-leftblock' : '');?> flex-1">
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
		<?$html = ob_get_contents();?>
		<?ob_end_clean();?>
		<?if(trim($html)):?>
			<div class="text_before_items">
				<?= $html; ?>
			</div>
		<?endif;?>
		<?unset($html);?>
		
		<?
		// get section items count and subsections
		$arParams['CHECK_DATES'] = 'Y';
		$arItemFilter = TSolution::GetCurrentSectionElementFilter($arResult["VARIABLES"], $arParams, false);
		$arSubSectionFilter = TSolution::GetCurrentSectionSubSectionFilter($arResult["VARIABLES"], $arParams, false);
		$itemsCnt = TSolution\Cache::CIBlockElement_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]))), $arItemFilter, array());
		$arSubSections = TSolution\Cache::CIBlockSection_GetList(array("CACHE" => array("TAG" => TSolution\Cache::GetIBlockCacheTag($arParams["IBLOCK_ID"]), "MULTI" => "Y")), $arSubSectionFilter, false, array("ID"));
		?>
		<?if(!$itemsCnt && !$arSubSections):?>
			<div class="alert alert-warning"><?=GetMessage("SECTION_EMPTY")?></div>
		<?else:?>
			<?TSolution::CheckComponentTemplatePageBlocksParams($arParams, __DIR__);?>

			<?$sViewElementTemplate = ($arParams["SECTIONS_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["SECTIONS_TYPE_VIEW_CATALOG"]["VALUE"] : $arParams["SECTIONS_TYPE_VIEW"]);?>
			<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>

			<?if(!$arSubSections):?>
				<?// section elements?>
				<?if(strlen($arParams["FILTER_NAME"])):?>
					<?$GLOBALS[$arParams["FILTER_NAME"]] = array_merge((array)$GLOBALS[$arParams["FILTER_NAME"]], $arItemFilter);?>
				<?else:?>
					<?$arParams["FILTER_NAME"] = "arrFilter";?>
					<?$GLOBALS[$arParams["FILTER_NAME"]] = $arItemFilter;?>
				<?endif;?>
				
				<?$sViewElementTemplate = ($arParams["SECTION_ELEMENTS_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["ELEMENTS_CATALOG_PAGE"]["VALUE"] : $arParams["SECTION_ELEMENTS_TYPE_VIEW"]);?>
				<?@include_once('page_blocks/'.$sViewElementTemplate.'.php');?>
			<?endif;?>
		<?endif;?>

		<?// outro text?>
		<?ob_start();?>
		<?$APPLICATION->IncludeComponent(
			"bitrix:main.include",
			"",
			Array(
				"AREA_FILE_SHOW" => "page",
				"AREA_FILE_SUFFIX" => "bottom",
				"EDIT_TEMPLATE" => ""
			)
		);?>
		<?$html = ob_get_contents();?>
		<?ob_end_clean();?>
		<?if(trim($html)):?>
			<div class="text_after_items">
				<?$APPLICATION->IncludeComponent(
					"bitrix:main.include",
					"",
					Array(
						"AREA_FILE_SHOW" => "page",
						"AREA_FILE_SUFFIX" => "bottom",
						"EDIT_TEMPLATE" => ""
					)
				);?>
			</div>
		<?endif;?>
		<?unset($html);?>
	</div>
	<?if($bShowLeftBlock):?>
		<?TSolution::ShowPageType('left_block');?>
	<?endif;?>
</div>
