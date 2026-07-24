<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['catalog', 'catalog_block', 'prices', 'input_numeric'];
if($arParams['SLIDER'] === true || $arParams['SLIDER'] === 'Y'){
	$arExtensions[] = 'swiper';
}

TSolution\Extensions::init($arExtensions);

if (isset($templateData['TEMPLATE_LIBRARY']) && !empty($templateData['TEMPLATE_LIBRARY'])){
	$loadCurrency = false;
	if (!empty($templateData['CURRENCIES']))
		$loadCurrency = \Bitrix\Main\Loader::includeModule('currency');
	CJSCore::Init($templateData['TEMPLATE_LIBRARY']);
	if ($loadCurrency){?>
	<script type="text/javascript">
		BX.Currency.setCurrencies(<? echo $templateData['CURRENCIES']; ?>);
	</script>
	<?}
}
if (!$templateData['ITEMS']) {
	$GLOBALS['APPLICATION']->SetPageProperty('BLOCK_CATALOG_TAB', 'hidden');
}
