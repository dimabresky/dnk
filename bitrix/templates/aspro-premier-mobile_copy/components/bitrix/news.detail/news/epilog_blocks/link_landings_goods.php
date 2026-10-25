<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;

$goodsIds = array_values(array_filter((array)($templateData['GOODS']['VALUE'] ?? [])));
if (!$goodsIds) {
	return;
}

$filterName = 'arrGoodsFilter';
$filterDir = 'landings';
$catalogIBlockID = $templateData['GOODS']['IBLOCK_ID'] ?: ($arParams['CATALOG_IBLOCK_ID'] ?: TSolution::GetFrontParametrValue('CATALOG_IBLOCK_ID'));
$arParams['IBLOCK_ID'] = $catalogIBlockID;

$bCheckAjaxBlock = TSolution::checkRequestBlock('goods-list-inner');
$isAjax = (TSolution::checkAjaxRequest() && $bCheckAjaxBlock) ? 'Y' : 'N';
if (
	isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
	strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
	isset($_GET['ajax_get_filter']) && $_GET['ajax_get_filter'] === 'Y'
) {
	$isAjax = 'N';
}

$arItemsFilter = [
	'ID' => $goodsIds,
	'SECTION_GLOBAL_ACTIVE' => 'Y',
	'ACTIVE' => 'Y',
	'IBLOCK_ID' => $catalogIBlockID,
];
TSolution::makeElementFilterInRegion($arItemsFilter);
if (is_array($GLOBALS['arRegionLink'] ?? null)) {
	$arItemsFilter = array_merge($GLOBALS['arRegionLink'], $arItemsFilter);
}

$blockTitle = $templateData['H3_GOODS']
	?: ($arParams['T_GOODS'] ?: Loc::getMessage('T_GOODS'));
?>
<div class="detail-block ordered-block list_items link_landings_goods" id="link_landings_goods">
	<?TSolution\Template\Epilog\Blocks::showBlockTitle($blockTitle);?>
	<div class="main-wrapper">
	<div class="js-load-wrapper ajax-pagination-wrapper <?=$APPLICATION->ShowViewContent('section_additional_class');?>" data-class="goods-list-inner">
	<?
	$GLOBALS['preFilterLanding'] = TSolution\Regionality::mergeSmartPreFilterWithRegionFilter($arItemsFilter);
	$arParams['FILTER_VIEW'] = 'COMPACT';

	$frame = null;
	if ($isAjax !== 'Y') {
		$frame = new \Bitrix\Main\Page\FrameHelper('catalog-elements-landing-block');
		$frame->begin();
		$frame->setAnimation(true);
	}

	include_once __DIR__.'/catalog/sort.php';

	if (!is_array($GLOBALS[$filterName] ?? null)) {
		$GLOBALS[$filterName] = [];
	}
	foreach ($arItemsFilter as $filterKey => $filterValue) {
		if (is_int($filterKey) || array_key_exists($filterKey, $GLOBALS[$filterName])) {
			continue;
		}
		$GLOBALS[$filterName][$filterKey] = $filterValue;
	}

	switch (TSolution\Template\DisplayTypes::getInstance()->normalizeCurrent()) {
		case 'price':
			$arParams['DISPLAY'] = 'catalog_table';
			break;
		case 'list':
			$arParams['DISPLAY'] = 'catalog_list';
			break;
		default:
			$arParams['DISPLAY'] = 'catalog_block';
	}

	$linerow = TSolution\Template\DisplayTypes::getInstance()->getElementsInRow($APPLICATION->GetProperty('MENU') === 'Y');
	$view = TSolution\Template\DisplayTypes::getInstance()->normalizeCurrent();
	?>
	<div class="inner_wrapper relative">
		<?if ($isAjax === 'Y'):?>
			<?$APPLICATION->RestartBuffer();?>
		<?else:?>
			<div class="ajax_load <?=$view?>-view">
		<?endif;?>

			<?TSolution\Functions::showBlockHtml([
				'FILE' => '/detail_linked_goods.php',
				'PARAMS' => array_merge(
					$arParams,
					[
						'CHECK_REQUEST_BLOCK' => $bCheckAjaxBlock,
						'FILTER_NAME' => $filterName,
						'IS_AJAX' => $isAjax,
						'LINE_TO_ROW' => $linerow,
						'ELEMENT_IN_ROW' => $linerow,
						'ELEMENT_SORT_FIELD' => $arAvailableSort[$sortKey]['SORT'],
						'ELEMENT_SORT_ORDER' => strtoupper($order),
						'SHOW_PROPS_TABLE' => strtolower(TSolution::GetFrontParametrValue('SHOW_TABLE_PROPS')),
						'ITEM_HOVER_SHADOW' => true,
						'IS_CATALOG' => true,
					]
				)
			]);?>

			<!--noindex-->
			<?if (!empty($GLOBALS[$filterName])):?>
				<script class="smart-filter-filter" data-skip-moving="true">
					var filter = <?=\Bitrix\Main\Web\Json::encode($GLOBALS[$filterName]);?>
				</script>
			<?endif;?>
			<?if (!empty($arAvailableSort[$sortKey]['SORT'])):?>
				<script class="smart-filter-sort" data-skip-moving="true">
					var sort = <?=\Bitrix\Main\Web\Json::encode([$arAvailableSort[$sortKey]['SORT'] => strtoupper($order)]);?>
				</script>
			<?endif;?>
			<!--/noindex-->

		<?if ($isAjax === 'Y'):?>
			<?die();?>
		<?else:?>
			</div>
		<?endif;?>
	</div>
	<?if ($frame) {
		$frame->end();
	}?>
	</div>
	</div>
</div>
