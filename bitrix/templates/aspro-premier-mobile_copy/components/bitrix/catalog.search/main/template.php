<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $arTheme, $searchQuery;

TSolution\Extensions::init(['search_page']);

$isAjax = "N";
if (
	isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" &&
	isset($_GET["ajax_get"]) &&
	$_GET["ajax_get"] == "Y" ||
	(
		isset($_GET["ajax_basket"]) &&
		$_GET["ajax_basket"] == "Y"
	) ||
	(
		isset($_GET["control_ajax"]) &&
		$_GET["control_ajax"] == "Y"
	)
) {
	$isAjax = "Y";
}

if (
	isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" &&
	isset($_GET["ajax_get_filter"]) &&
	$_GET["ajax_get_filter"] == "Y" &&
	!isset($_GET["control_ajax"])
) {
	$isAjaxFilter = "Y";
}

$catalogIBlockID = $arParams["IBLOCK_ID"];
$arParams["AJAX_FILTER_CATALOG"] = "N";
$bShowLeftBlock = false;

$APPLICATION->SetPageProperty("MENU", 'N');
$APPLICATION->AddViewContent('right_block_class', 'catalog_page search_page');

if (
	$arParams["FILTER_NAME"] == '' ||
	!preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER_NAME"])
) {
	$arParams["FILTER_NAME"] = "searchFilter";
}

$bShowFilter = ($arTheme["SEARCH_VIEW_TYPE"]["VALUE"] == "with_filter");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/jquery.history.js');

// bitrix:search.page arrFILTER
$arSearchPageFilter = array(
	'arrFILTER' => array('iblock_'.$arParams['IBLOCK_TYPE']),
	'arrFILTER_iblock_'.$arParams['IBLOCK_TYPE'] => array($arParams['IBLOCK_ID']),
);

$arSKU = array();
if (TSolution::isSaleMode() && $arParams['IBLOCK_ID']) {
	$arSKU = CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);
	if($arSKU['IBLOCK_ID']){
		$dbRes = CIBlock::GetByID($arSKU['IBLOCK_ID']);
		if($arSkuIblock = $dbRes ->Fetch()){
			$arSearchPageFilter['arrFILTER'][] = 'iblock_'.$arSkuIblock['IBLOCK_TYPE_ID'];
			$arSearchPageFilter['arrFILTER'] = array_unique($arSearchPageFilter['arrFILTER']);
			if(!$arSearchPageFilter['arrFILTER_iblock_'.$arSkuIblock['IBLOCK_TYPE_ID']]){
				$arSearchPageFilter['arrFILTER_iblock_'.$arSkuIblock['IBLOCK_TYPE_ID']] = array();
			}
			$arSearchPageFilter['arrFILTER_iblock_'.$arSkuIblock['IBLOCK_TYPE_ID']][] = $arSKU['IBLOCK_ID'];
		}
	}
}

// show bitrix.search_page content
$APPLICATION->ShowViewContent('comp_search_page');

// include bitrix.search_page
ob_start();
include 'include_search_page.php';
$searchPageContent = ob_get_clean();

if(!strlen($searchQuery) && !$GLOBALS['isSearchQueryByImage']){
    $searchQuery = $_GET['q'];
}

$arRegion = TSolution\Regionality::getCurrentRegion();

// define landings in search & replace search result
include 'include_landing_define.php';
?>
<div class="top-content-block <?=$APPLICATION->ShowViewContent('top_class');?>">
	<?=$searchPageContent;?>

	<?include 'include_landing_top.php';?>

	<?$APPLICATION->ShowViewContent('top_content');?><?$APPLICATION->ShowViewContent('top_content2');?>
</div>

<div class="main-wrapper flexbox flexbox--direction-row">
	<div class="section-content-wrapper <?=($bShowLeftBlock ? 'with-leftblock' : '');?> flex-1 min-width-0">
		<?
		if($arRegion)
		{
			if($arRegion['LIST_PRICES'])
			{
				if(reset($arRegion['LIST_PRICES']) != 'component')
					$arParams['PRICE_CODE'] = array_keys($arRegion['LIST_PRICES']);
			}
			if($arRegion['LIST_STORES'])
			{
				if(reset($arRegion['LIST_STORES']) != 'component')
					$arParams['STORES'] = $arRegion['LIST_STORES'];
			}
		}

		if($arParams['LIST_PRICES'])
		{
			foreach($arParams['LIST_PRICES'] as $key => $price)
			{
				if(!$price)
					unset($arParams['LIST_PRICES'][$key]);
			}
		}

		if($arParams['STORES'])
		{
			foreach($arParams['STORES'] as $key => $store)
			{
				if(!$store)
					unset($arParams['STORES'][$key]);
			}
		}

		if (is_array($arElements) && !empty($arElements))
		{
			$htmlSort = '';
			$arAdditionalData = array();
			if($arSKU)
			{
				foreach($arElements as $key => $value)
				{
					$arTmp = CIBlockElement::GetProperty($arSKU['IBLOCK_ID'], $value, array("sort" => "asc"), Array("ID"=>$arSKU['SKU_PROPERTY_ID']))->Fetch();
					if($arTmp['VALUE'])
						$arElements[$arTmp['VALUE']] = $arTmp['VALUE'];
				}
			}
			$arElements = array_values(array_unique(array_filter(array_map('intval', $arElements))));
			$arrFilter = ($GLOBALS[$arParams["FILTER_NAME"]] ? $GLOBALS[$arParams["FILTER_NAME"]] : []);
			unset($arrFilter['ID'], $arrFilter['=ID']);

			$GLOBALS[$arParams["FILTER_NAME"]] = array(
				"=ID" => $arElements,
				'IBLOCK_ID' => $catalogIBlockID,
			) + $arrFilter;

			if ($arLanding && $arCustomFilter) {
				if ($bReplaceElementsByCustomFilter) {
					$GLOBALS[$arParams["FILTER_NAME"]] = array($arCustomFilter) + $arrFilter;
				}
				else {
					$GLOBALS[$arParams["FILTER_NAME"]] = array_merge($GLOBALS[$arParams["FILTER_NAME"]], array($arCustomFilter));
				}
			}

			if($arParams['HIDE_NOT_AVAILABLE'] === 'Y'){
				$GLOBALS[$arParams["FILTER_NAME"]]['CATALOG_AVAILABLE'] = 'Y';
			}

			if($arRegion) {
				if($arRegion['LIST_STORES'] && $arParams["HIDE_NOT_AVAILABLE"] == "Y") {
					if($arParams['STORES']){
						$arStoresFilter = array(
							'STORE_NUMBER' => $arParams['STORES'],
							'>STORE_AMOUNT' => 0,
						);

						$arTmpFilter = array('!TYPE' => array('2', '3'));
						if($arStoresFilter){
							$arTmpFilter = array_merge($arTmpFilter, $arStoresFilter);

							$GLOBALS[$arParams["FILTER_NAME"]][] = array(
								'LOGIC' => 'OR',
								array('TYPE' => array('2', '3')),
								$arTmpFilter,
							);
						}
					}
				}

				$GLOBALS[$arParams["FILTER_NAME"]] = TSolution::makeElementFilterInRegion($GLOBALS[$arParams["FILTER_NAME"]]);
			}

			unset($GLOBALS[$arParams["FILTER_NAME"]]['ID']);
			$GLOBALS[$arParams["FILTER_NAME"]]['=ID'] = $arElements;

			$arItems = TSolution\Cache::CIBLockElement_GetList(
				array(
					'CACHE' => array(
						'MULTI' => 'Y',
						'TAG' => TSolution\Cache::GetIBlockCacheTag($catalogIBlockID),
					)
				),
				$GLOBALS[$arParams["FILTER_NAME"]],
				false,
				false,
				array(
					'ID',
					'IBLOCK_ID',
					'IBLOCK_SECTION_ID',
				)
			);

			$arAllSections = $arSectionsID = $arItemsID = array();

			if($arItems){
				TSolution\Extensions::init(['filter_panel', 'dropdown_select', 'smart_filter']);

				// sort
				ob_start();
				include_once 'include_sort.php';
				$htmlSort = ob_get_clean();

				$listElementsTemplate = 'catalog_'.($display == 'price' ? 'table' : ($display == 'table' ? 'block' : $display));

				if($sort === 'RANK'){
					$sectionSort = 'ID';
					$sectionSortOrder = TSolution\Search\Common::SortBySearchOrder($arElements, $arItems);
				}
			}

			$bContolAjax = (
				isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" &&
				isset($_GET["control_ajax"]) &&
				$_GET["control_ajax"] == "Y"
			);

			if (
				isset($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
				strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == "xmlhttprequest" &&
				isset($_GET["ajax_get_filter"]) && $_GET["ajax_get_filter"] == "Y"
			) {
				$isAjaxFilter = 'Y';
			}

			if (
				isset($isAjaxFilter) &&
				$isAjaxFilter == 'Y'
			) {
				$isAjax = 'N';
			}
			?>
			<?$APPLICATION->ShowViewContent('search_content');?>
			<div class="js_wrapper_items<?=($arTheme["LAZYLOAD_BLOCK_CATALOG"]["VALUE"] == "Y" ? ' with-load-block' : '')?>" >
				<div class="js-load-wrapper <?=$APPLICATION->ShowViewContent("section_additional_class");?>">

					<?if($bContolAjax):?>
						<?$APPLICATION->RestartBuffer();?>
					<?endif;?>

					<?// sort & filter?>
					<?=$htmlSort?>

					<div class="inner_wrapper relative">
					<?if($isAjax === "Y"):?>
						<?$APPLICATION->RestartBuffer();?>
					<?endif;?>

					<?if ($isAjax == "N"):?>
						<div class="ajax_load <?=TSolution\Template\DisplayTypes::getInstance()->normalizeCurrent()?>-view">
					<?endif;?>
							<?
							//$arTransferParams = array();
							$show = $arParams["PAGE_ELEMENT_COUNT"];
							$linerow = ($arParams['LINE_ELEMENT_COUNT'] == '5' ? 5 : 4);
							?>
							<?if($arItems):?>
								<?php
								// Restore search element IDs after smart filter init in include_sort/include_filter.
								$searchFilterName = $arParams['FILTER_NAME'];
								$searchElementIds = array_values(array_unique(array_filter(array_map('intval', $arElements))));
								if ($searchFilterName && $searchElementIds) {
									$appliedFilter = (array)($GLOBALS[$searchFilterName] ?? []);
									unset(
										$appliedFilter['ID'],
										$appliedFilter['=ID'],
										$appliedFilter['IBLOCK_ID'],
										$appliedFilter['FACET_OPTIONS']
									);
									$GLOBALS[$searchFilterName] = [
										'IBLOCK_ID' => (int)$catalogIBlockID,
										'=ID' => $searchElementIds,
									];
									if ($appliedFilter && (!empty($_REQUEST['set_filter']) || !empty($_REQUEST['del_filter']))) {
										$GLOBALS[$searchFilterName] += $appliedFilter;
									}
								}
								?>
								<?// section elements?>
								<?$display4ParamsName = TSolution\Template\DisplayTypes::getInstance()->getCurrent4ParamsName();?>
								<?$sViewElementsTemplate = ($arParams["ELEMENTS_".$display4ParamsName."_TYPE_VIEW"] == "FROM_MODULE" ? $arTheme["ELEMENTS_".$display4ParamsName."_TYPE_VIEW"]["VALUE"] : $arParams["ELEMENTS_".$display4ParamsName."_TYPE_VIEW"]);?>
								<?@include_once( $_SERVER["DOCUMENT_ROOT"].$arParams["CATALOG_TEMPLATE_PATH"].'/page_blocks/'.$sViewElementsTemplate.'.php');
								?>
							<?endif;?>
						</div>
					<?if($isAjax === "Y"):?>
						<?die();?>
					<?endif;?>

					<?if(isset($isAjaxFilter) && $isAjaxFilter):?>
						<script type="text/javascript">
							BX.removeCustomEvent("onAjaxSuccessFilter", function tt(e){});
							BX.addCustomEvent("onAjaxSuccessFilter", function tt(e){
								var arAjaxPageData = <?=CUtil::PhpToJSObject($arAdditionalData);?>;
								if ($('.element-count-wrapper .element-count').length) {
									$('.element-count-wrapper .element-count').text($('.js_append').closest('.catalog-items').find('.bottom_nav').attr('data-all_count'));
								}
							});
						</script>
					<?endif;?>

					</div> <?// .<div class="inner_wrapper">?>

					<?if($bContolAjax):?>
						<?die();?>
					<?endif;?>
				</div>
			</div>

			<?include 'include_landing_bottom.php';?>
		<?
		}
		else {
			if(strlen($searchQuery)) {
				echo '<div class="alert alert-danger">'.GetMessage("CT_BCSE_NOT_FOUND")."</div>";
			}
			elseif (!$GLOBALS['isSearchQueryByImage']) {
				echo '<div class="alert alert-info">'.GetMessage("CT_BCSE_EMPTY_QUERY")."</div>";
			}

			$APPLICATION->AddViewContent('top_class', 'emptys');
		}
		?>
	</div>

	<?if ($bShowLeftBlock):?>
		<?TSolution::ShowPageType('left_block');?>
	<?endif;?>
</div>
