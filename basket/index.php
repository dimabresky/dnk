<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");
?>
<?
if (TSolution::GetFrontParametrValue('ORDER_VIEW') !== 'Y') {
	LocalRedirect(SITE_DIR);
}
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:sale.basket.basket", 
	"v2", 
	[
		"COLUMNS_LIST" => [
			0 => "NAME",
			1 => "DISCOUNT",
			2 => "PROPS",
			3 => "DELETE",
			4 => "DELAY",
			5 => "TYPE",
			6 => "PRICE",
			7 => "QUANTITY",
			8 => "SUM",
		],
		"OFFERS_PROPS" => [
			0 => "SIZES",
			1 => "COLOR_REF",
		],
		"PATH_TO_ORDER" => "/order/",
		"HIDE_COUPON" => "N",
		"PRICE_VAT_SHOW_VALUE" => "Y",
		"COUNT_DISCOUNT_4_ALL_QUANTITY" => "N",
		"USE_PREPAYMENT" => "N",
		"SET_TITLE" => "N",
		"AJAX_MODE_CUSTOM" => "Y",
		"SHOW_MEASURE" => "Y",
		"PICTURE_WIDTH" => "100",
		"PICTURE_HEIGHT" => "100",
		"SHOW_FULL_ORDER_BUTTON" => "Y",
		"SHOW_FAST_ORDER_BUTTON" => "Y",
		"COMPONENT_TEMPLATE" => "v2",
		"QUANTITY_FLOAT" => "N",
		"ACTION_VARIABLE" => "action",
		"TEMPLATE_THEME" => "blue",
		"AUTO_CALCULATION" => "Y",
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO",
		"USE_GIFTS" => "Y",
		"GIFTS_PLACE" => "BOTTOM",
		"GIFTS_BLOCK_TITLE" => "Выберите один из подарков",
		"GIFTS_HIDE_BLOCK_TITLE" => "N",
		"GIFTS_TEXT_LABEL_GIFT" => "Подарок",
		"GIFTS_PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"GIFTS_PRODUCT_PROPS_VARIABLE" => "prop",
		"GIFTS_SHOW_OLD_PRICE" => "Y",
		"GIFTS_SHOW_DISCOUNT_PERCENT" => "Y",
		"GIFTS_SHOW_NAME" => "Y",
		"GIFTS_SHOW_IMAGE" => "Y",
		"GIFTS_MESS_BTN_BUY" => "Выбрать",
		"GIFTS_MESS_BTN_DETAIL" => "Подробнее",
		"GIFTS_PAGE_ELEMENT_COUNT" => "4",
		"GIFTS_CONVERT_CURRENCY" => "N",
		"GIFTS_HIDE_NOT_AVAILABLE" => "N",
		"COLUMNS_LIST_EXT" => [
			0 => "PREVIEW_PICTURE",
			1 => "DISCOUNT",
			2 => "DELETE",
			3 => "DELAY",
			4 => "TYPE",
			5 => "SUM",
			6 => "PROPERTY_COLOR_REF2",
			7 => "PROPERTY_PROP_2065",
			8 => "PROPERTY_SIZES5",
			9 => "PROPERTY_SIZES3",
			10 => "PROPERTY_SIZES4",
		],
		"CORRECT_RATIO" => "Y",
		"COMPATIBLE_MODE" => "Y",
		"ADDITIONAL_PICT_PROP_135" => "-",
		"ADDITIONAL_PICT_PROP_136" => "-",
		"BASKET_IMAGES_SCALING" => "adaptive",
		"USE_ENHANCED_ECOMMERCE" => "N",
		"DEFERRED_REFRESH" => "N",
		"USE_DYNAMIC_SCROLL" => "Y",
		"SHOW_FILTER" => "Y",
		"SHOW_RESTORE" => "Y",
		"COLUMNS_LIST_MOBILE" => [
			0 => "PREVIEW_PICTURE",
			1 => "DISCOUNT",
			2 => "DELETE",
			3 => "DELAY",
			4 => "TYPE",
			5 => "SUM",
			6 => "",
		],
		"TOTAL_BLOCK_DISPLAY" => [
			0 => "top",
		],
		"DISPLAY_MODE" => "extended",
		"PRICE_DISPLAY_MODE" => "Y",
		"SHOW_DISCOUNT_PERCENT" => "Y",
		"DISCOUNT_PERCENT_POSITION" => "top-left",
		"PRODUCT_BLOCKS_ORDER" => "props,sku,columns",
		"USE_PRICE_ANIMATION" => "Y",
		"LABEL_PROP" => [
		],
		"LABEL_PROP_MOBILE" => "",
		"LABEL_PROP_POSITION" => "top-left",
		"EMPTY_BASKET_HINT_PATH" => "/",
		"ADDITIONAL_PICT_PROP_134" => "-",
		"ADDITIONAL_PICT_PROP_20" => "-",
		"ADDITIONAL_PICT_PROP_42" => "-",
		"ADDITIONAL_PICT_PROP_48" => "-",
		"ADDITIONAL_PICT_PROP_49" => "-",
		"ADDITIONAL_PICT_PROP_41" => "-",
		"ADDITIONAL_PICT_PROP_14" => "-",
		"ADDITIONAL_PICT_PROP_15" => "-",
		"PATH_TO_BASKET" => "basket/"
	],
	false
);?>

<div class="basket-bigdata-block print-hide">
	<?$APPLICATION->IncludeComponent(
		"aspro:wrapper.block.premier", 
		"recoms", 
		array(
			"CACHE_FILTER" => "N",
			"CACHE_GROUPS" => "N",
			"CACHE_TIME" => "36000000",
			"CACHE_TYPE" => "N",
			"COMPONENT_TEMPLATE" => "recoms",
			"SHOW_TITLE" => "Y",
			"TITLE" => "Вам может понравиться",
			"ELEMENT_COUNT" => "5",
			"RCM_TYPE" => "bestsell",
			"RCM_PROD_ID" => "",
			"SHOW_FROM_SECTION" => "N",
			"SECTION_ID" => "",
		),
		false,
		array("HIDE_ICONS" => "Y")
	);?>
</div>
<?/*
<?$APPLICATION->IncludeComponent(
	"bitrix:main.include", 
	"basket", 
	array(
		"COMPONENT_TEMPLATE" => "basket",
		"PATH" => "/include/comp_basket_bigdata.php",
		"AREA_FILE_SHOW" => "file",
		"AREA_FILE_SUFFIX" => "",
		"AREA_FILE_RECURSIVE" => "Y",
		"EDIT_TEMPLATE" => "standard.php",
		"PRICE_CODE" => array(
			0 => "BASE",
			1 => "OPT",
		),
		"STORES" => array(
			0 => "1",
			1 => "2",
			2 => "",
		),
		"BIG_DATA_RCM_TYPE" => "bestsell",
		"STIKERS_PROP" => "HIT",
		"SALE_STIKER" => "SALE_TEXT"
	),
	false
);?>
*/?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>