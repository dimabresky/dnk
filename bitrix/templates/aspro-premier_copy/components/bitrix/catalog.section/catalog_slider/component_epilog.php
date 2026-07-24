<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['catalog', 'deal', 'notice', 'prices', 'stickers', 'input_numeric', 'images'];
if ($arParams['SLIDER'] === true || $arParams['SLIDER'] === 'Y') {
	$arExtensions[] = 'swiper';
}
if ($arParams['SHOW_RATING'] === 'Y') {
	$arExtensions[] = 'rating';
	$arExtensions[] = 'rate';
}
if ($arParams['TYPE_SKU'] !== 'TYPE_2') {
	$arExtensions[] = 'select_offer_load';
}
if ($templateData['HAS_CHARACTERISTICS']) {
	// $arExtensions[] = 'chars';
	$arExtensions[] = 'hint';
}


TSolution\Extensions::init($arExtensions);

if (isset($templateData['TEMPLATE_LIBRARY']) && !empty($templateData['TEMPLATE_LIBRARY'])) {
	$loadCurrency = false;
	if (!empty($templateData['CURRENCIES'])) {
		$loadCurrency = \Bitrix\Main\Loader::includeModule('currency');
	}

	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);

	if ($loadCurrency) {
	?>
		<script type="text/javascript">
			BX.Currency.setCurrencies(<?=$templateData['CURRENCIES'];?>);
		</script>
	<?
	}
}
if (!$templateData['ITEMS']) {
	$GLOBALS['APPLICATION']->SetPageProperty('BLOCK_CATALOG_TAB', 'hidden');
}
