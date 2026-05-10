<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc,
	Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

if (!Loader::includeModule(VENDOR_MODULE_ID)) die();

$arItems = TSolution\Itemaction\Subscribe::getItems();
?>
<div class="personal__block personal__block--subscribe-products">
	<?if ($arItems):?>
		<?
		$bAjax = $arParams['IS_AJAX'] == 'Y';

		// set params from module
		TSolution\Functions::replaceListParams($arParams, ['PROPERTY_CODE' => 'PROPERTY_CODE']);

		$offersID = TSolution\Itemaction\Subscribe::getOffersIDFromItems($arItems);
		$GLOBALS['arrSubscribeItems'] = [
			'ID' => $arItems,
			'OFFERS_ID' => $offersID
		];
		?>
		<div class="personal__block--subscribe-products__title">
			<h5><?=Loc::getMessage('TITLE_SUBSCRIPTIONS')?></h5>
			<div class="js_clear_subscription color-theme-hover font_13 secondary-color"><?=Bitrix\Main\Localization\Loc::getMessage('CLEAR_SUBSCRIPTIONS')?></div>
		</div>
		<?
		if ($bAjax) {
			$GLOBALS['APPLICATION']->RestartBuffer();
		}
		?>

		<?$APPLICATION->IncludeComponent(
			"bitrix:catalog.section",
			"catalog_block",
			array(
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"CACHE_FILTER" => $arParams["CACHE_FILTER"],
				"CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
				"DETAIL_URL" => "",
				"FILTER_NAME" => "arrSubscribeItems",
				"HIT_PROP" => "HIT",
				"IBLOCK_TYPE" => "aspro_premier_catalog",
				"IBLOCK_ID"	=> $arParams['CATALOG_IBLOCK_ID'] ?? TSolution::GetFrontParametrValue('CATALOG_IBLOCK_ID'),
				"PAGE_ELEMENT_COUNT" => $arParams["ELEMENT_COUNT"],
				// "PAGE_ELEMENT_COUNT" => $arParams['LINKED_CATALOG_COUNT'] ?? TSolution::GetFrontParametrValue('COUNT_LINKED_GOODS') ?? "20",
				"PROPERTY_CODE"	=> $arParams['LINKED_PROPERTY_CODE'] ?? $arParams['LIST_PROPERTY_CODE'] ?? $arParams['PROPERTY_CODE'],
				"ELEMENT_SORT_FIELD" => "SORT",
				"ELEMENT_SORT_ORDER" => "ASC",
				"ELEMENT_SORT_FIELD2" => "ID",
				"ELEMENT_SORT_ORDER2" => "DESC",
				//"SECTION_ID" => "",
				//"SECTION_CODE" => "",
				"FIELD_CODE" => $arParams['LINKED_FIELD_CODE'] ?? $arParams['LIST_FIELD_CODE'] ?? $arParams['FIELD_CODE'],
				"SHOW_SECTION" => "Y",
				"COUNT_IN_LINE" => "",
				"LINE_ELEMENT_COUNT" => "",
				"STORES" => $arParams["STORES"],
				"PRICE_CODE" => $arParams["PRICE_CODE"],
				"SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
				"SHOW_DISCOUNT_TIME" => $arParams['SHOW_DISCOUNT_TIME'],
				"SHOW_DISCOUNT_PERCENT" => $arParams['SHOW_DISCOUNT_PERCENT'],
				"SHOW_PREVIEW_TEXT" => "N",
				"SHOW_GALLERY" => $arParams["SHOW_GALLERY"],
				"MAX_GALLERY_ITEMS" => $arParams['MAX_GALLERY_ITEMS'],
				"ADD_PICT_PROP" => $arParams["ADD_PICT_PROP"],
				"OFFER_ADD_PICT_PROP" => $arParams["OFFER_ADD_PICT_PROP"],
				"DISPLAY_TOP_PAGER"	=>	"N",
				"DISPLAY_BOTTOM_PAGER"	=>	"Y",
				"PAGER_TITLE"	=>	"",
				"PAGER_TEMPLATE"	=>	"ajax",
				"PAGER_SHOW_ALWAYS"	=>	"N",
				"PAGER_DESC_NUMBERING"	=>	"N",
				"PAGER_DESC_NUMBERING_CACHE_TIME"	=>	"36000",
				"PAGER_SHOW_ALL" => "N",
				"INCLUDE_SUBSECTIONS" => "Y",
				"SHOW_ALL_WO_SECTION" => "Y",
				"IS_CATALOG_PAGE" => 'Y',
				"META_KEYWORDS" => "",
				"META_DESCRIPTION" => "",
				"BROWSER_TITLE" => "",
				"ADD_SECTIONS_CHAIN" => "N",
				"DISPLAY_COMPARE" => $arParams["DISPLAY_COMPARE"],
				"SHOW_FAVORITE" => $arParams["SHOW_FAVORITE"],
				"CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
				"CURRENCY_ID" => $arParams["CURRENCY_ID"],
				"PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
				"HIDE_NOT_AVAILABLE" => $arParams["HIDE_NOT_AVAILABLE"],
				"HIDE_NOT_AVAILABLE_OFFERS" => $arParams["HIDE_NOT_AVAILABLE_OFFERS"],
				"SHOW_HINTS" => $arParams["SHOW_HINTS"],
				"SHOW_POPUP_PRICE" => $arParams["SHOW_POPUP_PRICE"],
				"DISPLAY_UNSUBSCRIBE" => 'Y', // show "unsubscribe" button with "to cart" button

				"SHOW_ONE_CLICK_BUY" => $arParams["SHOW_ONE_CLICK_BUY"],
				"USE_FAST_VIEW_PAGE_DETAIL" => $arParams["USE_FAST_VIEW_PAGE_DETAIL"],
				"EXPRESSION_FOR_FAST_VIEW" => $arParams["EXPRESSION_FOR_FAST_VIEW"],

				"SHOW_RATING" => $arParams['SHOW_RATING'],
				"OPT_BUY" => $arParams['OPT_BUY'],

                'ADD_PROPERTIES_TO_BASKET' => $arParams['ADD_PROPERTIES_TO_BASKET'],
                'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
                'PARTIAL_PRODUCT_PROPERTIES' => $arParams['PARTIAL_PRODUCT_PROPERTIES'],
                'OFFERS_CART_PROPERTIES' => $arParams['OFFERS_CART_PROPERTIES'],
                'PRODUCT_PROPERTIES' => $arParams['PRODUCT_PROPERTIES'],

				"SKU_IBLOCK_ID"	=>	$arParams["SKU_IBLOCK_ID"],
				"SKU_TREE_PROPS"	=>	$arParams["SKU_TREE_PROPS"],
				"SKU_PROPERTY_CODE"	=>	$arParams["SKU_PROPERTY_CODE"],

				"OFFER_TREE_PROPS" => $arParams['SKU_TREE_PROPS'],
				"OFFERS_PROPERTY_CODE" => $arParams['SKU_PROPERTY_CODE'],
				"OFFERS_FIELD_CODE" => array_merge(['ID', 'NAME'], (array)$arParams["LIST_OFFERS_FIELD_CODE"]),
				"OFFERS_SORT_FIELD" => $arParams["SKU_SORT_FIELD"],
				"OFFERS_SORT_ORDER" => $arParams["SKU_SORT_ORDER"],
				"OFFERS_SORT_FIELD2" => $arParams["SKU_SORT_FIELD2"],
				"OFFERS_SORT_ORDER2" => $arParams["SKU_SORT_ORDER2"],

				"ELEMENT_IN_ROW" => $APPLICATION->GetProperty('MENU') === 'Y' ? 4 : 5,
				"AJAX_REQUEST" => $arParams['IS_AJAX'],
				"MOBILE_SCROLLED" => false,
				"NARROW" => "Y",
				"CHECK_REQUEST_BLOCK" => $arParams['CHECK_REQUEST_BLOCK'],

				"ORDER_VIEW" => $arParams['ORDER_VIEW'],
				"USE_REGION" => $arParams['USE_REGION'],

				"COMPATIBLE_MODE" => $arParams['COMPATIBLE_MODE'] ?? 'Y',
				"USE_PRICE_COUNT" => $arParams['USE_PRICE_COUNT'],
				"SHOW_PRICE_COUNT" => $arParams['SHOW_PRICE_COUNT'],
			),
			false,
			array("HIDE_ICONS" => "Y")
		);?>

		<?
		if ($bAjax) {
			die();
		}
		?>
	<?else:?>
		<div class="alert alert-success"><?=Loc::getMessage('NO_SUBSCRIBE_ITEMS')?></div>
	<?endif;?>
</div>
