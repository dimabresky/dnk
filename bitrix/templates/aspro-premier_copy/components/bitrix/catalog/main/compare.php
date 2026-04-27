<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$APPLICATION->AddChainItem(GetMessage("CATALOG_COMPARE_HEADER_TITLE"));
$APPLICATION->SetPageProperty("title", GetMessage("CATALOG_COMPARE_HEADER_TITLE"));
$APPLICATION->SetTitle(GetMessage("CATALOG_COMPARE_HEADER_TITLE"));
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH.'/js/sly.js');
$APPLICATION->SetPageProperty("MENU", "N");

global $arTheme;

// set params from module
TSolution\Functions::replaceListParams($arParams);

if(!in_array('PREVIEW_PICTURE', (array)$arParams["COMPARE_FIELD_CODE"]))
    $arParams["COMPARE_FIELD_CODE"][] = 'PREVIEW_PICTURE';
if(!in_array('ID', (array)$arParams["COMPARE_OFFERS_FIELD_CODE"]))
    $arParams["COMPARE_OFFERS_FIELD_CODE"][] = 'ID';
if(!in_array('QUANTITY', (array)$arParams["COMPARE_OFFERS_FIELD_CODE"]))
    $arParams["COMPARE_OFFERS_FIELD_CODE"][] = 'QUANTITY';
if(!in_array('IBLOCK_ID', (array)$arParams["COMPARE_OFFERS_FIELD_CODE"]))
    $arParams["COMPARE_OFFERS_FIELD_CODE"][] = 'IBLOCK_ID';

$arNeedMainProps = ['ARTICLE', 'CML2_ARTICLE'];
$arParams["COMPARE_PROPERTY_CODE"] = array_merge((array)$arParams["COMPARE_PROPERTY_CODE"], $arNeedMainProps);

$arNeedOffersProps = ['ARTICLE', 'CML2_ARTICLE'];
$arParams["COMPARE_OFFERS_PROPERTY_CODE"] = array_merge((array)$arParams["COMPARE_OFFERS_PROPERTY_CODE"], $arNeedOffersProps);
?>
<?$APPLICATION->IncludeComponent(
    "bitrix:catalog.compare.result",
    "main",
    array(
        "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "BASKET_URL" => $arParams["BASKET_URL"],
        "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
        "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
        "SECTION_ID_VARIABLE" => $arParams["SECTION_ID_VARIABLE"],
        "FIELD_CODE" => $arParams["COMPARE_FIELD_CODE"],
        "PROPERTY_CODE" => $arParams["COMPARE_PROPERTY_CODE"],
        "NAME" => $arParams["COMPARE_NAME"],
        "SHOW_MEASURE" => $arParams["SHOW_MEASURE"],
        "CACHE_TYPE" => $arParams["CACHE_TYPE"],
        "CACHE_TIME" => $arParams["CACHE_TIME"],
        "PRICE_CODE" => $arParams["PRICE_CODE"],
        "SKU_DETAIL_ID" => $arParams["SKU_DETAIL_ID"],
        "SHOW_GALLERY" => "N",
        "SHOW_FAVORITE" => $arParams["SHOW_FAVORITE"],
        "USE_REGION" => $arParams['USE_REGION'],
        "STORES" => $arParams['STORES'],
        // "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
        "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
        "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
        "PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
        "DISPLAY_ELEMENT_SELECT_BOX" => $arParams["DISPLAY_ELEMENT_SELECT_BOX"],
        "ELEMENT_SORT_FIELD_BOX" => $arParams["ELEMENT_SORT_FIELD_BOX"],
        "ELEMENT_SORT_ORDER_BOX" => $arParams["ELEMENT_SORT_ORDER_BOX"],
        "ELEMENT_SORT_FIELD_BOX2" => $arParams["ELEMENT_SORT_FIELD_BOX2"],
        "ELEMENT_SORT_ORDER_BOX2" => $arParams["ELEMENT_SORT_ORDER_BOX2"],
        "ELEMENT_SORT_FIELD" => $arParams["COMPARE_ELEMENT_SORT_FIELD"],
        "ELEMENT_SORT_ORDER" => $arParams["COMPARE_ELEMENT_SORT_ORDER"],
        "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
        "OFFERS_FIELD_CODE" => $arParams["COMPARE_OFFERS_FIELD_CODE"],
        "OFFERS_PROPERTY_CODE" => $arParams["COMPARE_OFFERS_PROPERTY_CODE"],
        "OFFERS_CART_PROPERTIES" => $arParams["OFFERS_CART_PROPERTIES"],
        "CONVERT_CURRENCY" => $arParams["CONVERT_CURRENCY"],
        "CURRENCY_ID" => $arParams["CURRENCY_ID"],
        'HIDE_NOT_AVAILABLE' => 'N',
        'TEMPLATE_THEME' => (isset($arParams['TEMPLATE_THEME']) ? $arParams['TEMPLATE_THEME'] : ''),
        'IMG_CORNER' => $arParams['SECTION_ITEM_LIST_IMG_CORNER'] === 'Y',
        "ORDER_VIEW" => $arParams['ORDER_VIEW'],
        "USE_COMPARE_GROUP" => $arParams["USE_COMPARE_GROUP"],
        "SHOW_DISCOUNT_TIME" => $arParams["SHOW_DISCOUNT_TIME"],
        "SHOW_OLD_PRICE" => $arParams["SHOW_OLD_PRICE"],
        "DISCOUNT_PRICE" => $arParams["DISCOUNT_PRICE"],
        "SHOW_DISCOUNT_PERCENT" => $arParams["SHOW_DISCOUNT_PERCENT"],
        "SHOW_POPUP_PRICE" => $arParams["SHOW_POPUP_PRICE"],
        "COMPATIBLE_MODE" => $arParams['COMPATIBLE_MODE'] ?? 'Y',

        "ADD_PROPERTIES_TO_BASKET" => $arParams['ADD_PROPERTIES_TO_BASKET'],
        "PARTIAL_PRODUCT_PROPERTIES" => $arParams['PARTIAL_PRODUCT_PROPERTIES'],
        "PRODUCT_PROPERTIES" =>	$arParams['PRODUCT_PROPERTIES'],
        "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
        "DISPLAY_COMPARE"	=>	$arParams["DISPLAY_COMPARE"],
        "SHOW_RATING" => $arParams['SHOW_RATING'],
    ),
    $component,
    array("HIDE_ICONS" => "Y")
);?>
