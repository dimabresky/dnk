<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arExtensions = ['catalog', 'notice', 'catalog_block', 'images', 'stickers', 'prices', 'section_gallery', 'input_numeric'];
$arExtensionsMobile = ['counter'];

if($arParams['SLIDER'] === true || $arParams['SLIDER'] === 'Y'){
	$arExtensions[] = 'swiper';
}
if ($arParams['SHOW_RATING'] === 'Y') {
	$arExtensions[] = 'rating';
	$arExtensions[] = 'rate';
}
if ($arParams['TYPE_SKU'] !== 'TYPE_2') {
	$arExtensions[] = 'select_offer_load';
	$arExtensions[] = 'smart_filter.ui';
	$arExtensionsMobile[] = 'popup.offers';

	TSolution\Product\Common::showOffersPopup();
}

//	big data json answers
if ($arParams['BIG_DATA_MODE'] === 'Y') {
	$request = \Bitrix\Main\Context::getCurrent()->getRequest();

	if (
		$request->isAjaxRequest() &&
		($request->get('action') === 'deferredLoad')
	) {
		$content = ob_get_contents();
		ob_end_clean();

		list(, $itemsContainer) = explode('<!-- items-container -->', $content);

		$component::sendJsonAnswer(array(
			'items' => $itemsContainer,
		));
	}

	$arExtensions[] = 'bigdata';
}

TSolution\Extensions::init($arExtensions);
TSolution\ExtensionsMobile::init($arExtensionsMobile);

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
