<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $arTheme, $APPLICATION;

//$APPLICATION->ShowHeadScripts();
$APPLICATION->ShowAjaxHead();

// cart
$bOrderViewBasket = (trim($arTheme['ORDER_VIEW']['VALUE']) === 'Y');

$arInherite = [];
if($arSection){
	$arInherite = TSolution::getSectionInheritedUF(array(
		'sectionId' => $arSection['ID'],
		'iblockId' => $arSection['IBLOCK_ID'],
		'select' => array(
			'UF_OFFERS_TYPE',
		),
		'filter' => array(
			'GLOBAL_ACTIVE' => 'Y',
		),
		'enums' => array(
			'UF_OFFERS_TYPE',
		),
	));
}

$typeSKU = TSolution\Functions::getValueWithSection([
	'CODE' => 'CATALOG_PAGE_DETAIL_SKU',
	'SECTION_VALUE' => $arInherite['UF_OFFERS_TYPE']
]);
if ($arElement['TYPE'] != \Bitrix\Catalog\ProductTable::TYPE_SKU) {
	$typeSKU = TSolution::GetBackParametrsValues(SITE_ID)['TYPE_SKU'];
}

$arParams['OID'] = 0;
if ($arElement['TYPE'] == \Bitrix\Catalog\ProductTable::TYPE_SKU && $typeSKU == 'TYPE_1') {
	$context=\Bitrix\Main\Context::getCurrent();
	$request=$context->getRequest();
    if ($oidParam = TSolution::GetFrontParametrValue('CATALOG_OID')) {
        if ($oid = $request->getQuery($oidParam)) {
            if(array_key_exists($oid, current(CCatalogSku::getOffersList($arElement['ID'], $arElement['IBLOCK_ID'], null, ['ID', 'NAME'])))) {
                $arParams['OID'] = $oid;
            }
        }
	}
}
?>
<div class="product-container detail clearfix1" itemscope itemtype="http://schema.org/Product">
	<?@include_once('page_blocks/'.$arTheme["USE_FAST_VIEW_PAGE_DETAIL"]["VALUE"].'.php');?>
</div>
<?
$arRegion = TSolution\Regionality::getCurrentRegion();
if($arRegion){
	$arTagSeoMarks = array();
	foreach($arRegion as $key => $value){
		if(strpos($key, 'PROPERTY_REGION_TAG') !== false && strpos($key, '_VALUE_ID') === false){
			$tag_name = str_replace(array('PROPERTY_', '_VALUE'), '', $key);
			$arTagSeoMarks['#'.$tag_name.'#'] = $key;
		}
	}

	if($arTagSeoMarks){
		TSolution\Regionality::addSeoMarks($arTagSeoMarks);
	}
}

$arExtensions = ['fancybox', 'detail', 'swiper_init', 'swiper_events', 'gallery', 'video', 'catalog', 'rating', 'rate'];
TSolution\Extensions::init($arExtensions);
?>
