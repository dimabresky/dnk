<?
die('123');
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

$bLinkedGoods = in_array('goods', $GLOBALS["SHOW_TYPE_ITEMS"]);
$bLinkedCatalog = in_array('catalog', $GLOBALS["SHOW_TYPE_ITEMS"]);
?>

<?if (
	$arParams['SHOW_LINK_GOODS'] == 'Y'
	&& ($bLinkedGoods || $bLinkedCatalog)
):?>
	<?
	$filterName = 'arrGoodsFilter';
	$filterDir = 'brands';

	$catalogIBlockID = TSolution::GetFrontParametrValue('CATALOG_IBLOCK_ID');
	$bCheckAjaxBlock = TSolution::checkRequestBlock("goods-list-inner");
	$isAjax = (TSolution::checkAjaxRequest() && $bCheckAjaxBlock ) ? 'Y' : 'N';

	$arItemsFilter = array(
		'PROPERTY_'.$arParams['LINK_GOODS_PROP_CODE'] => $arResult['ID'],
		'SECTION_GLOBAL_ACTIVE' => 'Y',
		'ACTIVE' => 'Y',
		'IBLOCK_ID' => $catalogIBlockID,
	);
	TSolution::makeElementFilterInRegion($arItemsFilter);
	if(is_array($GLOBALS['arRegionLink'])){
		$arItemsFilter = array_merge($GLOBALS['arRegionLink'], $arItemsFilter);
	}

	$arItems = TSolution\Cache::CIblockElement_GetList(
		[
			'CACHE' => [
				'TAG' => TSolution\Cache::GetIBlockCacheTag($catalogIBlockID),
				'MULTI' => 'Y'
			]
		], 
		$arItemsFilter, 
		false,
		['nTopCount' => 1],
		["ID"]
	);

	$GLOBALS[$filterName] = $arItemsFilter;
	?>
	<div class="detail-block ordered-block link_brands_goods<?=empty($arItems) ? ' hidden' : '';?>" id="link_brands_goods">
		<?if ($bLinkedGoods):?>
			<?
			$blockTitle = $arParams['~T_LINK_GOODS']
				? str_replace('#NAME#', $arResult['NAME'], $arParams['~T_LINK_GOODS'])
				: Loc::getMessage('EPILOG_BLOCK__GOODS', ['#NAME#' => $arResult['NAME']]);
			TSolution\Template\Epilog\Blocks::showBlockTitle($blockTitle);
			?>
		<?endif;?>
		
		<div class="main-wrapper">
			<div class="js-load-wrapper ajax-pagination-wrapper <?=$APPLICATION->ShowViewContent("section_additional_class");?>" data-class="goods-list-inner">
				<?if ($arItems):?>
					<?
					$GLOBALS['preFilterBrand'] = $arItemsFilter;
					
					if ($bLinkedCatalog) {
						include_once('catalog/sort.php');
					}

					switch ($display) {
						case 'price':
							$arParams['DISPLAY'] = 'catalog_table';
							break;
						case 'table':
						case 'title':
							$arParams['DISPLAY'] = 'catalog_block';
							break;
						case 'list':
							$arParams['DISPLAY'] = 'catalog_list';
							break;
					}
					
					$linerow = TSolution\Template\DisplayTypes::getInstance()->getElementsInRow($APPLICATION->GetProperty('MENU') === 'Y');
					?>


					<div class="inner_wrapper relative">
						<?if ($isAjax === 'Y'):?>
							<?$APPLICATION->RestartBuffer();?>
						<?else:?>
							<div class="ajax_load">
						<?endif;?>
							
							<?TSolution\Functions::showBlockHtml([
								'FILE' => '/detail_linked_goods.php',
								'PARAMS' => array_merge(
									$arParams,
									array(
										'CHECK_REQUEST_BLOCK' => $bCheckAjaxBlock,
										'FILTER_NAME' => $filterName,
										'IS_AJAX' => $isAjax,
										'LINE_TO_ROW' => $linerow,
										'ELEMENT_IN_ROW' => $linerow,
										"ELEMENT_SORT_FIELD" => $arAvailableSort[$sortKey]["SORT"],
										"ELEMENT_SORT_ORDER" => strtoupper($order),
										'SHOW_PROPS_TABLE' => strtolower(TSolution::GetFrontParametrValue('SHOW_TABLE_PROPS')),
										"ITEM_HOVER_SHADOW" => true,
										'IS_CATALOG' => $bLinkedCatalog,
									)
								)
							]);?>

						<?if ($isAjax === 'Y'):?>
							<?die();?>
						<?else:?>
							</div>
						<?endif;?>
					</div>
				<?endif;?>
			</div>
		</div>
	</div>
<?endif;?>