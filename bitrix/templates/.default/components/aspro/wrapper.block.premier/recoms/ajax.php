<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
$this->setFrameMode(false);

if (!Loader::includeModule(VENDOR_MODULE_ID)) {
    exit;
}

$bAjax = 'Y' == $arParams['IS_AJAX'];

// set params from module
TSolution\Functions::replaceListParams($arParams, ['PROPERTY_CODE' => 'PROPERTY_CODE']);

$bShowTitle = isset($arParams['SHOW_TITLE']) && 'Y' === $arParams['SHOW_TITLE'] && isset($arParams['TITLE']) && strlen($arParams['TITLE']);
?>
<?if ($bShowTitle) { ?>
    <div class="main-block__title-wrapper">
        <h3 class="main-block__title switcher-title">
            <div class="main-block__title-inner">
                <span><?= $arParams['TITLE']; ?></span>
            </a>
        </h3>
    </div>
<?}?>
<?php
if ($bAjax) {
    $GLOBALS['APPLICATION']->RestartBuffer();
}

$filterName = $arParams['FILTER_NAME'] ?? 'arrGoodsFilter';
$GLOBALS[$filterName] = TSolution\Regionality::mergeFilterWithRegionFilter($GLOBALS[$filterName]);

$iblockId = $arParams['CATALOG_IBLOCK_ID'] ?? TSolution::GetFrontParametrValue('CATALOG_IBLOCK_ID');
?>
<div class="bigdata-wrapper">
    <?$elements = $APPLICATION->IncludeComponent(
        'bitrix:catalog.section',
        'catalog_block',
        [
            'CACHE_TYPE' => $arParams['CACHE_TYPE'] ?? 'A',
            'CACHE_TIME' => $arParams['CACHE_TIME'] ?? '36000000',
            'CACHE_FILTER' => $arParams['CACHE_FILTER'] ?? 'Y',
            'CACHE_GROUPS' => $arParams['CACHE_GROUPS'] ?? 'N',
            'DETAIL_URL' => '',
            'FILTER_NAME' => $filterName,
            'HIT_PROP' => 'HIT',
            'IBLOCK_TYPE' => 'aspro_premier_catalog',
            'IBLOCK_ID' => $iblockId,
            'PAGE_ELEMENT_COUNT' => $arParams['LINKED_CATALOG_COUNT'] ?? TSolution::GetFrontParametrValue('COUNT_LINKED_GOODS') ?? '20',
            'PAGE_ELEMENT_COUNT' => 0,
            'SHOW_PRODUCTS_'.$iblockId => 'Y',
            'PROPERTY_CODE' => $arParams['PROPERTY_CODE'],
            'ELEMENT_SORT_FIELD' => 'shows',
            'ELEMENT_SORT_ORDER' => 'desc',
            'ELEMENT_SORT_FIELD2' => 'ID',
            'ELEMENT_SORT_ORDER2' => 'DESC',
            'TYPE_SKU' => TSolution::isMobileTemplate() ? $arParams['TYPE_SKU'] : 'TYPE_2',
            'SECTION_ID' => 'Y' === $arParams['SHOW_FROM_SECTION'] ? $arParams['SECTION_ID'] : '',
            'SECTION_CODE' => '',
            'FIELD_CODE' => $arParams['LINKED_FIELD_CODE'] ?? $arParams['LIST_FIELD_CODE'] ?? $arParams['FIELD_CODE'],
            'ELEMENTS_TABLE_TYPE_VIEW' => 'FROM_MODULE',
            'SHOW_SECTION' => 'N',
            'COUNT_IN_LINE' => '',
            'LINE_ELEMENT_COUNT' => '4',
            'STORES' => $arParams['STORES'],
            'PRICE_CODE' => $arParams['PRICE_CODE'],
            'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
            'SHOW_DISCOUNT_TIME' => $arParams['SHOW_DISCOUNT_TIME'],
            'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
            'SHOW_PREVIEW_TEXT' => 'N',
            'SHOW_GALLERY' => $arParams['SHOW_GALLERY'],
            'MAX_GALLERY_ITEMS' => $arParams['MAX_GALLERY_ITEMS'],
            'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'],
            'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'],
            'DISPLAY_TOP_PAGER' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'N',
            'PAGER_TITLE' => '',
            'PAGER_TEMPLATE' => 'ajax',
            'PAGER_SHOW_ALWAYS' => 'N',
            'PAGER_DESC_NUMBERING' => 'N',
            'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
            'PAGER_SHOW_ALL' => 'N',
            'INCLUDE_SUBSECTIONS' => 'Y',
            'SHOW_ALL_WO_SECTION' => 'Y',
            'META_KEYWORDS' => '',
            'META_DESCRIPTION' => '',
            'BROWSER_TITLE' => '',
            'ADD_SECTIONS_CHAIN' => 'N',
            'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
            'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
            'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
            'CURRENCY_ID' => $arParams['CURRENCY_ID'],
            'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
            'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
            'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
            'SHOW_HINTS' => $arParams['SHOW_HINTS'],
            'SHOW_POPUP_PRICE' => $arParams['SHOW_POPUP_PRICE'],

            'SHOW_ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'],
            'USE_FAST_VIEW_PAGE_DETAIL' => $arParams['USE_FAST_VIEW_PAGE_DETAIL'],
            'EXPRESSION_FOR_FAST_VIEW' => $arParams['EXPRESSION_FOR_FAST_VIEW'],

            'SHOW_RATING' => $arParams['SHOW_RATING'],
            'OPT_BUY' => $arParams['OPT_BUY'],

            'ADD_PROPERTIES_TO_BASKET' => 'N',
            'PARTIAL_PRODUCT_PROPERTIES' => 'Y',

            'SKU_IBLOCK_ID' => $arParams['SKU_IBLOCK_ID'],
            'SKU_TREE_PROPS' => $arParams['SKU_TREE_PROPS'],
            'SKU_PROPERTY_CODE' => $arParams['SKU_PROPERTY_CODE'],

            'OFFER_TREE_PROPS' => $arParams['SKU_TREE_PROPS'],
            'OFFERS_PROPERTY_CODE' => $arParams['SKU_PROPERTY_CODE'],
            'OFFERS_FIELD_CODE' => array_merge(['ID', 'NAME'], (array) $arParams['LIST_OFFERS_FIELD_CODE']),
            'OFFERS_SORT_FIELD' => $arParams['SKU_SORT_FIELD'],
            'OFFERS_SORT_ORDER' => $arParams['SKU_SORT_ORDER'],
            'OFFERS_SORT_FIELD2' => $arParams['SKU_SORT_FIELD2'],
            'OFFERS_SORT_ORDER2' => $arParams['SKU_SORT_ORDER2'],

            'ELEMENT_IN_ROW' => 'Y' === $APPLICATION->GetProperty('MENU') ? 4 : 5,
            'AJAX_REQUEST' => $arParams['IS_AJAX'],
            'TEXT_CENTER' => false,
            'IMG_CORNER' => false,
            'GRID_GAP' => '20',
            'GRID_GAP_MOBILE' => '20',
            'ROW_VIEW' => true,
            'BORDER' => true,
            'ITEM_HOVER_SHADOW' => true,
            'DARK_HOVER' => false,
            'ROUNDED' => true,
            'ROUNDED_IMAGE' => true,
            'ITEM_PADDING' => true,
            'MAXWIDTH_WRAP' => false,
            'MOBILE_SCROLLED' => false,
            'NARROW' => 'Y',
            'IS_CATALOG_PAGE' => 'Y',
            'ITEMS_OFFSET' => true,
            'IMAGES' => 'PICTURE',
            'IMAGE_POSITION' => 'LEFT',
            'SHOW_PREVIEW' => true,
            'SHOW_TITLE' => false,
            'TITLE_POSITION' => '',
            'TITLE' => '',
            'RIGHT_TITLE' => '',
            'RIGHT_LINK' => '',
            'POSITION_BTNS' => $arParams['LINE_TO_ROW'],
            'CHECK_REQUEST_BLOCK' => $arParams['CHECK_REQUEST_BLOCK'],
            'IS_AJAX' => TSolution::checkAjaxRequest(),
            'SHOW_PROPS_TABLE' => strtolower(TSolution::GetFrontParametrValue('SHOW_TABLE_PROPS')),

            'ORDER_VIEW' => $arParams['ORDER_VIEW'],
            'USE_REGION' => $arParams['USE_REGION'],

            'AJAX' => $arParams['IS_AJAX'] ? 'Y' : 'N',

            'COMPATIBLE_MODE' => $arParams['COMPATIBLE_MODE'] ?? 'Y',
            'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
            'SHOW_PRICE_COUNT' => $arParams['SHOW_PRICE_COUNT'],

            'SLIDER' => true,
            'SLIDER_BUTTONS_BORDERED' => false,
            'IS_COMPACT_SLIDER' => false,
            'ITEM_380' => '2',
            'ITEM_768' => '3',
            'ITEM_992' => '4',
            'ITEM_1200' => '5',

//            'BIG_DATA_MODE' => 'Y',
//            'BIGDATA_COUNT' => $arParams['ELEMENT_COUNT'] ?? '5',
//            'RCM_TYPE' => $arParams['RCM_TYPE'] ?? 'similar',
//            'RCM_PROD_ID' => $arParams['RCM_PROD_ID'] ?? '',
//            'SHOW_FROM_SECTION' => $arParams['SHOW_FROM_SECTION'] ?? 'N',
//            'PRODUCT_ROW_VARIANTS' => "[{'VARIANT':'5','BIG_DATA':true}]",

            'USE_MAIN_ELEMENT_SECTION' => 'Y',

            'ACTION_VARIABLE' => 'action',

            'COMPATIBLE_MODE' => 'Y',
            'SET_META_DESCRIPTION' => 'N',
            'SET_TITLE' => 'N',
            'SET_BROWSER_TITLE' => 'N',
        ],
        $this->__component,
        ['HIDE_ICONS' => 'Y']
    ); ?>
</div>

<?php
if ($bAjax) {
    exit;
}
?>
