<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PriceMaths;
use TSolution\Itemaction;

// need for solution class and variables
if (!include_once ($_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/vendor/php/solution.php')) {
    return false;
}

global $APPLICATION;

/**
 * This file modifies result for every request (including AJAX).
 * Use it to edit output result for "{{ mustache }}" templates.
 *
 * @var array $result
 */
$mobileColumns = isset($this->arParams['COLUMNS_LIST_MOBILE'])
    ? $this->arParams['COLUMNS_LIST_MOBILE']
    : $this->arParams['COLUMNS_LIST'];
$mobileColumns = array_fill_keys($mobileColumns, true);

$result['BASKET_ITEM_RENDER_DATA'] = [];

$servicesIblockId = Option::get(TSolution::moduleID, 'SERVICES_IBLOCK_ID', CPremierCache::$arIBlocks[SITE_ID]['aspro_premier_content']['aspro_premier_services'][0]);
$catalogIblockId = Option::get(TSolution::moduleID, 'CATALOG_IBLOCK_ID', CPremierCache::$arIBlocks[SITE_ID]['aspro_premier_catalog']['aspro_premier_catalog'][0]);
$bCache = Option::get(TSolution::moduleID, 'SERVICES_CACHE', 'N') === 'Y';
$cacheTime = Option::get(TSolution::moduleID, 'SERVICES_CACHE_TIME', '36000');
$cacheGroups = Option::get(TSolution::moduleID, 'SERVICES_CACHE_GROUPS', 'N');

$countInAnnounce = Option::get(TSolution::moduleID, 'SERVICES_COUNT_IN_ANNOUNCE', '2');

$showOldPrice = Option::get(TSolution::moduleID, 'SHOW_OLD_PRICE', 'Y');
$showDiscountPercent = Option::get(TSolution::moduleID, 'SHOW_DISCOUNT_PERCENT', 'Y');
$discountPrice = Option::get(TSolution::moduleID, 'DISCOUNT_PRICE', '');
$showMeasure = Option::get(TSolution::moduleID, 'SHOW_MEASURE', 'N');
$priceType = explode(',', Option::get(TSolution::moduleID, 'PRICES_TYPE', 'BASE'));
$currency = Bitrix\Sale\Internals\SiteCurrencyTable::getSiteCurrency(SITE_ID);
$priceVat = Option::get(TSolution::moduleID, 'PRICE_VAT_INCLUDE', 'Y');
$showPopupPrice = Option::get(TSolution::moduleID, 'SHOW_POPUP_PRICE', 'Y');
$compatibleMode = Option::get(TSolution::moduleID, 'CATALOG_COMPATIBLE_MODE', 'N');
$usePriceCount = Option::get(TSolution::moduleID, 'USE_PRICE_COUNT', 'Y');
$showPriceCount = Option::get(TSolution::moduleID, 'SHOW_PRICE_COUNT', '1');
$bUseFastView = Option::get(TSolution::moduleID, 'USE_FAST_VIEW_PAGE_DETAIL', 'Y') !== 'NO';

$bServicesRegionality = Option::get(TSolution::moduleID, 'SERVICES_REGIONALITY', 'N') === 'Y'
    && Option::get(TSolution::moduleID, 'USE_REGIONALITY', 'N') === 'Y'
    && Option::get(TSolution::moduleID, 'REGIONALITY_FILTER_ITEM', 'N') === 'Y';

$arRegion = TSolution\Regionality::getCurrentRegion();
if ($arRegion) {
    if ($arRegion['LIST_PRICES']) {
        if (reset($arRegion['LIST_PRICES']) != 'component') {
            $priceType = array_keys($arRegion['LIST_PRICES']);
        }
    }
}

$link_services_in_basket = [];
foreach ($this->basketItems as $arItem) {
    /* fill buy services array */
    if ($arItem['PROPS']) {
        $arPropsByCode = array_column($arItem['PROPS'], null, 'CODE');
        $isServices = isset($arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]) && $arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]['VALUE'] > 0;
        if ($isServices) {
            $services_info = [
                'BASKET_ID' => $arItem['ID'],
                'PRODUCT_ID' => $arItem['PRODUCT_ID'],
                'QUANTITY' => $arItem['QUANTITY'],
                'PRODUCT_PRICE_ID' => $arItem['PRODUCT_PRICE_ID'],
                'CURRENCY' => $arItem['CURRENCY'],
                'NEED_SHOW_OLD_SUM' => $arItem['SUM_DISCOUNT_PRICE'] > 0 ? 'Y' : 'N',
                'VALUE' => $arItem['FULL_PRICE'],
                'PRINT_VALUE' => $arItem['FULL_PRICE_FORMATED'],
                'DISCOUNT_VALUE' => $arItem['PRICE'],
                'PRINT_DISCOUNT_VALUE' => $arItem['PRICE_FORMATED'],
                'DISCOUNT_DIFF' => $arItem['DISCOUNT_PRICE'],
                'PRINT_DISCOUNT_DIFF' => $arItem['DISCOUNT_PRICE_FORMATED'],
                // 'FULL_PRICE_FORMATED' => $arItem["FULL_PRICE_FORMATED"],
                // 'SUM_FORMATED' => $arItem["SUM"],
                // 'SUM_FULL_PRICE_FORMATED' => $arItem["SUM_FULL_PRICE_FORMATED"],
            ];

            $link_services_in_basket[$arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]['VALUE']][$arItem['PRODUCT_ID']] = $services_info;
        }
    }
}

$arServices = $arItems = [
    'COUNT' => 0,
    'SUMM' => 0,
];

foreach ($this->basketItems as $row) {
    $buyServices = false;
    $isServices = false;

    if ($row['DELAY'] !== 'Y') {
        if ($row['PROPS']) {
            $arPropsByCode = array_column($row['PROPS'], null, 'CODE');
            $isServices = isset($arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]) && $arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]['VALUE'] > 0;
            $idParentProduct = $arPropsByCode[Itemaction\Service::BASKET_PROPERTY_CODE_SERVICE_PRODUCT]['VALUE'];
        }

        $row['LINK_SERVICES'] = [];
        if (is_array($link_services_in_basket) && count($link_services_in_basket) > 0) {
            if (isset($link_services_in_basket[$row['PRODUCT_ID']])) {
                $row['LINK_SERVICES'] = $link_services_in_basket[$row['PRODUCT_ID']];
            }
        }

        $productId = CCatalogSku::GetProductInfo($row['PRODUCT_ID']);
        $productId = is_array($productId) ? $productId['ID'] : $row['PRODUCT_ID'];
        $arElementFilter = ['ID' => $productId, 'IBLOCK_ID' => $catalogIblockId];
        // CPremier::makeElementFilterInRegion($arElementFilter);

        $itemForServ = [
            'ID' => $productId,
            'IBLOCK_ID' => $catalogIblockId,
            'PROPERTIES' => [
                'SERVICES' => [
                    'LINK_IBLOCK_ID' => $servicesIblockId,
                    'VALUE' => [],
                ],
            ],
            'DISPLAY_PROPERTIES' => [
                'SERVICES' => [
                    'LINK_IBLOCK_ID' => $servicesIblockId,
                    'VALUE' => [],
                ],
            ],
        ];

        $arElement = CPremierCache::CIBLockElement_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => CPremierCache::GetIBlockCacheTag($catalogIblockId)]], $arElementFilter, false, false, ['ID', 'IBLOCK_ID', 'PROPERTY_SERVICES']);
        if ($arElement['PROPERTY_SERVICES_VALUE']) {
            if (is_array($arElement['PROPERTY_SERVICES_VALUE'])) {
                $arServicesFromProp = $arElement['PROPERTY_SERVICES_VALUE'];
            } else {
                $arServicesFromProp = [$arElement['PROPERTY_SERVICES_VALUE']];
            }

            $itemForServ['DISPLAY_PROPERTIES']['SERVICES']['VALUE'] = $itemForServ['PROPERTIES']['SERVICES']['VALUE'] = $arServicesFromProp;
        }

        $arLinkedServices = Aspro\Functions\CAsproPremier::getCrossLinkedItems($itemForServ, ['SERVICES'], ['LINK_GOODS', 'LINK_GOODS_FILTER']);

        if ($arLinkedServices['VALUE']) {
            Aspro\Premier\Functions\Extensions::init(['catalog']);

            $GLOBALS['arBuyServicesFilterBasketPage']['ID'] = $arLinkedServices['VALUE'];
            $GLOBALS['arBuyServicesFilterBasketPage']['PROPERTY_ALLOW_BUY_VALUE'] = 'Y';
            if ($bServicesRegionality && isset($arRegion['ID'])) {
                $GLOBALS['arBuyServicesFilterBasketPage'][] = ['PROPERTY_LINK_REGION' => $arRegion['ID']];
            }
            ob_start();
            $APPLICATION->IncludeComponent(
                'bitrix:catalog.section',
                'services_buy_cart',
                [
                    'IBLOCK_ID' => $servicesIblockId,
                    'IBLOCK_TYPE' => 'aspro_premier_content',
                    'CACHE_TYPE' => $bCache && empty($row['LINK_SERVICES']) ? 'A' : 'N',
                    'CACHE_TIME' => $cacheTime,
                    'CACHE_GROUPS' => $cacheGroups,
                    'CACHE_FILTER' => 'Y',
                    'FILTER_NAME' => 'arBuyServicesFilterBasketPage',
                    'PAGE_ELEMENT_COUNT' => '999',
                    'PRICE_VAT_INCLUDE' => $priceVat,
                    'FIELD_CODE' => [
                        'NAME',
                        'PREVIEW_TEXT',
                        'PREVIEW_PICTURE',
                    ],
                    'PROPERTIES' => [],
                    'SET_TITLE' => 'N',
                    'SET_BROWSER_TITLE' => 'N',
                    'SET_META_KEYWORDS' => 'N',
                    'SET_META_DESCRIPTION' => 'N',
                    'SET_LAST_MODIFIED' => 'N',
                    'SHOW_ALL_WO_SECTION' => 'Y',
                    'COMPONENT_TEMPLATE' => 'services_buy_cart',

                    'ORDER_VIEW' => 'Y',
                    'PRICE_CODE' => $priceType,
                    'CURRENCY_ID' => $currency,
                    'CONVERT_CURRENCY' => 'Y',
                    'SHOW_OLD_PRICE' => $showOldPrice,
                    'SHOW_PICTURE' => 'N',
                    'SERVICES_IN_BASKET' => is_array($row['LINK_SERVICES']) ? $row['LINK_SERVICES'] : [],
                    'VISIBLE_COUNT' => $countInAnnounce,
                    'SHOW_POPUP_PRICE' => $showPopupPrice,
                    'COMPATIBLE_MODE' => $compatibleMode,
                    'SHOW_DISCOUNT_PERCENT' => $showDiscountPercent,
                    'DISCOUNT_PRICE' => $discountPrice,
                    'SHOW_MEASURE' => $showMeasure,
                    'USE_PRICE_COUNT' => $usePriceCount,
                    'SHOW_PRICE_COUNT' => $showPriceCount,
                ],
                false,
                ['HIDE_ICONS' => 'Y']
            );
            $htmlBuyServices = ob_get_clean();
            if ($htmlBuyServices && trim($htmlBuyServices) && strpos($htmlBuyServices, 'error') === false) {
                $buyServices = true;
            }
        }

        if ($isServices) {
            $arServices['COUNT'] += $row['QUANTITY'];
            $arServices['SUMM'] += $row['PRICE'] * $row['QUANTITY'];
        } else {
            $arItems['COUNT'] += $row['QUANTITY'];
            $arItems['SUMM'] += $row['PRICE'] * $row['QUANTITY'];
        }
    }

    $rowData = [
        'ID' => $row['ID'],
        'PRODUCT_ID' => $row['PRODUCT_ID'],
        'IBLOCK_ID' => CIBlockElement::GetIBlockByID($row['PRODUCT_ID']),
        'NAME' => isset($row['~NAME']) ? $row['~NAME'] : $row['NAME'],
        'QUANTITY' => $row['QUANTITY'],
        'PROPS' => $row['PROPS'],
        'PROPS_ALL' => $row['PROPS_ALL'],
        'HASH' => $row['HASH'],
        'SORT' => $row['SORT'],
        'DETAIL_PAGE_URL' => $row['~DETAIL_PAGE_URL'],
        'CURRENCY' => $row['CURRENCY'],
        'DISCOUNT_PRICE_PERCENT' => $row['DISCOUNT_PRICE_PERCENT'],
        'DISCOUNT_PRICE_PERCENT_FORMATED' => $row['DISCOUNT_PRICE_PERCENT_FORMATED'],
        'SHOW_DISCOUNT_PRICE' => (float) $row['DISCOUNT_PRICE'] > 0,
        'PRICE' => $row['PRICE'],
        'PRICE_FORMATED' => $row['PRICE_FORMATED'],
        'FULL_PRICE' => $row['FULL_PRICE'],
        'FULL_PRICE_FORMATED' => $row['FULL_PRICE_FORMATED'],
        'DISCOUNT_PRICE' => $row['DISCOUNT_PRICE'],
        'DISCOUNT_PRICE_FORMATED' => $row['DISCOUNT_PRICE_FORMATED'],
        'SUM_PRICE' => $row['SUM_VALUE'],
        'SUM_PRICE_FORMATED' => $row['SUM'],
        'SUM_FULL_PRICE' => $row['SUM_FULL_PRICE'],
        'SUM_FULL_PRICE_FORMATED' => $row['SUM_FULL_PRICE_FORMATED'],
        'SUM_DISCOUNT_PRICE' => $row['SUM_DISCOUNT_PRICE'],
        'SUM_DISCOUNT_PRICE_FORMATED' => $row['SUM_DISCOUNT_PRICE_FORMATED'],
        'MEASURE_RATIO' => isset($row['MEASURE_RATIO']) ? $row['MEASURE_RATIO'] : 1,
        'MEASURE_TEXT' => $row['MEASURE_TEXT'],
        'AVAILABLE_QUANTITY' => $row['AVAILABLE_QUANTITY'],
        'CHECK_MAX_QUANTITY' => $row['CHECK_MAX_QUANTITY'],
        'MODULE' => $row['MODULE'],
        'PRODUCT_PROVIDER_CLASS' => $row['PRODUCT_PROVIDER_CLASS'],
        'NOT_AVAILABLE' => $row['NOT_AVAILABLE'] === true,
        'DELAYED' => $row['DELAY'] === 'Y',
        'SKU_BLOCK_LIST' => [],
        'COLUMN_LIST' => [],
        'SHOW_LABEL' => false,
        'LABEL_VALUES' => [],
        'BRAND' => isset($row[$this->arParams['BRAND_PROPERTY'].'_VALUE'])
            ? $row[$this->arParams['BRAND_PROPERTY'].'_VALUE']
            : '',
        'LINK_SERVICES_HTML' => $buyServices ? $htmlBuyServices : '',
        'WITH_SERVICES_CLASS' => $buyServices ? 'with-services' : '',
        'SERVICES_CLASS' => $isServices ? 'hidden' : '',
        'IS_SERVICES' => $isServices,
        'HAS_SERVICES' => $buyServices,
        'USE_FAST_VIEW' => $bUseFastView,
    ];

    if ($rowData['SUM_PRICE'] == '0') {
        $value = match (TSolution::GetFrontParametrValue('MISSING_GOODS_PRICE_DISPLAY')) {
            'TEXT' => TSolution::GetFrontParametrValue('MISSING_GOODS_PRICE_TEXT'),
            'NOTHING' => '',
            default => $rowData['SUM_PRICE_FORMATED'],
        };
        $rowData['SUM_PRICE_FORMATED'] = $value;
    }

    $typeStickers = Option::get('aspro.premier', 'ITEM_STICKER_CLASS_SOURCE', 'PROPERTY_VALUE', $rowData['LID']);
    $parentItemIDs = [
        'ID' => $row['PRODUCT_ID'],
        'IBLOCK_ID' => $rowData['IBLOCK_ID'],
    ];
    if ($row['SKU_DATA']) {
        $parentItemIDs = CCatalogSku::GetProductInfo($rowData['PRODUCT_ID']);
    }

    $stickersPropList = ['HIT', 'SALE_TEXT'];
    $arProps = [];
    foreach ($stickersPropList as $code) {
        $rsProps = CIBlockElement::GetProperty(
            $parentItemIDs['IBLOCK_ID'],
            $parentItemIDs['ID'],
            ['sort', 'asc'],
            ['CODE' => $code]
        );
        while ($arProp = $rsProps->Fetch()) {
            if ($arProp['VALUE_ENUM']) {
                $arProps[] = [
                    'VALUE' => $arProp['VALUE_ENUM'],
                    'CODE' => strtolower($arProp['VALUE_XML_ID']),
                    'CLASS' => 'sticker__item--'.($typeStickers === 'PROPERTY_VALUE' ? CUtil::translit($arProp['VALUE_ENUM'], 'ru') : strtolower($arProp['VALUE_XML_ID'])),
                ];
            } elseif ($arProp['VALUE']) {
                $arProps[] = [
                    'VALUE' => $arProp['VALUE'],
                    'CODE' => strtolower($arProp['CODE']),
                    'CLASS' => 'sticker__item--'.strtolower($arProp['CODE']),
                ];
            }
        }
    }
    $rowData['STICKERS'] = $arProps;
    $rowData['SHOW_STICKERS'] = !empty($arProps);

    // product analog
    foreach (['OUT_OF_PRODUCTION', 'PRODUCT_ANALOG', 'PRODUCT_ANALOG_FILTER'] as $code) {
        $rsProps = CIBlockElement::GetProperty(
            $parentItemIDs['IBLOCK_ID'],
            $parentItemIDs['ID'],
            ['sort', 'asc'],
            ['CODE' => $code]
        );
        while ($arProp = $rsProps->Fetch()) {
            if (!$arProp['VALUE'] && !$arProp['VALUE_ENUM'] && !$arProp['VALUE_XML_ID']) {
                continue;
            }

            if ($arProp['VALUE_ENUM']) {
                $rowData[$arProp['CODE']] = $arProp['VALUE_XML_ID'] ?: $arProp['VALUE_ENUM'];
            } else {
                $rowData[$arProp['CODE']] = $arProp['VALUE'];
            }

            if ($arProp['CODE'] === 'OUT_OF_PRODUCTION' && $arProp['VALUE_ENUM'] === 'Y') {
                $rowData[$arProp['CODE']] = TSolution::getFrontParametrValue('EXPRESSION_FOR_OUT_OF_PRODUCTION_STATUS');
            }

            if ($arProp['CODE'] === 'PRODUCT_ANALOG_FILTER') {
                $rowData[$arProp['CODE']] = [
                    'URL' => $arProp['VALUE'],
                    'TITLE' => TSolution::getFrontParametrValue('EXPRESSION_PRODUCT_ANALOG_FILTER'),
                ];
            }
        }
    }

    // data-item for favorite
    $arItemData = [
        'ID' => $parentItemIDs['ID'],
        'IBLOCK_ID' => $rowData['IBLOCK_ID'],
        'NAME' => $rowData['NAME'],
    ];
    $rowData['DATA_ITEM'] = TSolution::getDataItem($arItemData);
    $rowData['DATA_FAVORITE'] = TSolution\Product\Common::getActionIcon([
        'ITEM' => $arItemData,
        'TYPE' => 'favorite',
        'WRAPPER_ICON' => 'favorite_white',
        'ACTIVE_ICON' => 'favorite_active',
        'CLASS' => 'sm',
    ]);

    // show price including ratio
    if ($rowData['MEASURE_RATIO'] != 1) {
        $price = PriceMaths::roundPrecision($rowData['PRICE'] * $rowData['MEASURE_RATIO']);
        $rowData['SHOW_MESAURE_RATIO'] = true;

        if ($price != $rowData['PRICE']) {
            $rowData['PRICE'] = $price;
            $rowData['PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($price, $rowData['CURRENCY'], true);
        }

        $fullPrice = PriceMaths::roundPrecision($rowData['FULL_PRICE'] * $rowData['MEASURE_RATIO']);
        if ($fullPrice != $rowData['FULL_PRICE']) {
            $rowData['FULL_PRICE'] = $fullPrice;
            $rowData['FULL_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($fullPrice, $rowData['CURRENCY'], true);
        }

        $discountPrice = PriceMaths::roundPrecision($rowData['DISCOUNT_PRICE'] * $rowData['MEASURE_RATIO']);
        if ($discountPrice != $rowData['DISCOUNT_PRICE']) {
            $rowData['DISCOUNT_PRICE'] = $discountPrice;
            $rowData['DISCOUNT_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($discountPrice, $rowData['CURRENCY'], true);
        }
    }

    $rowData['SHOW_PRICE_FOR'] = (float) $rowData['QUANTITY'] !== (float) $rowData['MEASURE_RATIO'];

    $hideDetailPicture = false;

    if (!empty($row['PREVIEW_PICTURE_SRC'])) {
        $rowData['IMAGE_URL'] = $row['PREVIEW_PICTURE_SRC'];
    } elseif (!empty($row['DETAIL_PICTURE_SRC'])) {
        $hideDetailPicture = true;
        $rowData['IMAGE_URL'] = $row['DETAIL_PICTURE_SRC'];
    }

    if (!empty($row['SKU_DATA'])) {
        $propMap = [];

        foreach ($row['PROPS'] as $prop) {
            $propMap[$prop['CODE']] = !empty($prop['~VALUE']) ? $prop['~VALUE'] : $prop['VALUE'];
        }

        $notSelectable = true;

        foreach ($row['SKU_DATA'] as $skuBlock) {
            $skuBlockData = [
                'ID' => $skuBlock['ID'],
                'CODE' => $skuBlock['CODE'],
                'NAME' => $skuBlock['NAME'],
            ];

            $isSkuSelected = false;
            $isImageProperty = false;

            if (count($skuBlock['VALUES']) > 1) {
                $notSelectable = false;
            }

            foreach ($skuBlock['VALUES'] as $skuItem) {
                if ($skuBlock['TYPE'] === 'S' && $skuBlock['USER_TYPE'] === 'directory') {
                    $valueId = $skuItem['XML_ID'];
                } elseif ($skuBlock['TYPE'] === 'E') {
                    $valueId = $skuItem['ID'];
                } else {
                    $valueId = $skuItem['NAME'];
                }

                $skuValue = [
                    'ID' => $skuItem['ID'],
                    'NAME' => $skuItem['NAME'],
                    'SORT' => $skuItem['SORT'],
                    'PICT' => !empty($skuItem['PICT']) ? $skuItem['PICT']['SRC'] : false,
                    'XML_ID' => !empty($skuItem['XML_ID']) ? $skuItem['XML_ID'] : false,
                    'VALUE_ID' => $valueId,
                    'PROP_ID' => $skuBlock['ID'],
                    'PROP_CODE' => $skuBlock['CODE'],
                ];

                if (
                    !empty($propMap[$skuBlockData['CODE']])
                    && ($propMap[$skuBlockData['CODE']] == $skuItem['NAME']
                    || $propMap[$skuBlockData['CODE']] == $skuItem['XML_ID']
                    || $propMap[$skuBlockData['CODE']] == $skuItem['ID']
                    )
                ) {
                    $skuValue['SELECTED'] = true;
                    $isSkuSelected = true;
                }

                $skuBlockData['SKU_VALUES_LIST'][] = $skuValue;
                $isImageProperty = $isImageProperty || !empty($skuItem['PICT']);
            }

            if (!$isSkuSelected && !empty($skuBlockData['SKU_VALUES_LIST'][0])) {
                $skuBlockData['SKU_VALUES_LIST'][0]['SELECTED'] = true;
            }

            $skuBlockData['IS_IMAGE'] = $isImageProperty;

            $rowData['SKU_BLOCK_LIST'][] = $skuBlockData;
        }
    }

    if ($row['NOT_AVAILABLE']) {
        foreach ($rowData['SKU_BLOCK_LIST'] as $blockKey => $skuBlock) {
            if (!empty($skuBlock['SKU_VALUES_LIST'])) {
                if ($notSelectable) {
                    foreach ($skuBlock['SKU_VALUES_LIST'] as $valueKey => $skuValue) {
                        $rowData['SKU_BLOCK_LIST'][$blockKey]['SKU_VALUES_LIST'][0]['NOT_AVAILABLE_OFFER'] = true;
                    }
                } elseif (!isset($rowData['SKU_BLOCK_LIST'][$blockKey + 1])) {
                    foreach ($skuBlock['SKU_VALUES_LIST'] as $valueKey => $skuValue) {
                        if ($skuValue['SELECTED']) {
                            $rowData['SKU_BLOCK_LIST'][$blockKey]['SKU_VALUES_LIST'][$valueKey]['NOT_AVAILABLE_OFFER'] = true;
                        }
                    }
                }
            }
        }
    }

    if (!empty($result['GRID']['HEADERS']) && is_array($result['GRID']['HEADERS'])) {
        $skipHeaders = [
            'NAME' => true,
            'QUANTITY' => true,
            'PRICE' => true,
            'PREVIEW_PICTURE' => true,
            'SUM' => true,
            'PROPS' => true,
            'DELETE' => true,
            'DELAY' => true,
        ];

        foreach ($result['GRID']['HEADERS'] as &$value) {
            if (
                empty($value['id'])
                || isset($skipHeaders[$value['id']])
                || ($hideDetailPicture && $value['id'] === 'DETAIL_PICTURE')
            ) {
                continue;
            }

            if ($value['id'] === 'DETAIL_PICTURE') {
                $value['name'] = Loc::getMessage('SBB_DETAIL_PICTURE_NAME');

                if (!empty($row['DETAIL_PICTURE_SRC'])) {
                    $rowData['COLUMN_LIST'][] = [
                        'CODE' => $value['id'],
                        'NAME' => $value['name'],
                        'VALUE' => [
                            [
                                'IMAGE_SRC' => $row['DETAIL_PICTURE_SRC'],
                                'IMAGE_SRC_2X' => $row['DETAIL_PICTURE_SRC_2X'],
                                'IMAGE_SRC_ORIGINAL' => $row['DETAIL_PICTURE_SRC_ORIGINAL'],
                                'INDEX' => 0,
                            ],
                        ],
                        'IS_IMAGE' => true,
                        'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                    ];
                }
            } elseif ($value['id'] === 'PREVIEW_TEXT') {
                $value['name'] = Loc::getMessage('SBB_PREVIEW_TEXT_NAME');

                if ($row['PREVIEW_TEXT_TYPE'] === 'text' && !empty($row['PREVIEW_TEXT'])) {
                    $rowData['COLUMN_LIST'][] = [
                        'CODE' => $value['id'],
                        'NAME' => $value['name'],
                        'VALUE' => $row['PREVIEW_TEXT'],
                        'IS_TEXT' => true,
                        'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                    ];
                }
            } elseif ($value['id'] === 'TYPE') {
                $value['name'] = Loc::getMessage('SBB_PRICE_TYPE_NAME');

                if (!empty($row['NOTES'])) {
                    $rowData['COLUMN_LIST'][] = [
                        'CODE' => $value['id'],
                        'NAME' => $value['name'],
                        'VALUE' => isset($row['~NOTES']) ? $row['~NOTES'] : $row['NOTES'],
                        'IS_TEXT' => true,
                        'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                    ];
                }
            } elseif ($value['id'] === 'DISCOUNT') {
                $value['name'] = Loc::getMessage('SBB_DISCOUNT_NAME');

                if ($row['DISCOUNT_PRICE_PERCENT'] > 0 && !empty($row['DISCOUNT_PRICE_PERCENT_FORMATED'])) {
                    $rowData['COLUMN_LIST'][] = [
                        'CODE' => $value['id'],
                        'NAME' => $value['name'],
                        'VALUE' => $row['DISCOUNT_PRICE_PERCENT_FORMATED'],
                        'IS_TEXT' => true,
                        'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                    ];
                }
            } elseif ($value['id'] === 'WEIGHT') {
                $value['name'] = Loc::getMessage('SBB_WEIGHT_NAME');

                if (!empty($row['WEIGHT_FORMATED'])) {
                    $rowData['COLUMN_LIST'][] = [
                        'CODE' => $value['id'],
                        'NAME' => $value['name'],
                        'VALUE' => $row['WEIGHT_FORMATED'],
                        'IS_TEXT' => true,
                        'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                    ];
                }
            } elseif (!empty($row[$value['id'].'_SRC'])) {
                $i = 0;

                foreach ($row[$value['id'].'_SRC'] as &$image) {
                    $image['INDEX'] = $i++;
                }

                $rowData['COLUMN_LIST'][] = [
                    'CODE' => $value['id'],
                    'NAME' => $value['name'],
                    'VALUE' => $row[$value['id'].'_SRC'],
                    'IS_IMAGE' => true,
                    'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                ];
            } elseif (!empty($row[$value['id'].'_DISPLAY'])) {
                $rowData['COLUMN_LIST'][] = [
                    'CODE' => $value['id'],
                    'NAME' => $value['name'],
                    'VALUE' => $row[$value['id'].'_DISPLAY'],
                    'IS_TEXT' => true,
                    'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                ];
            } elseif (!empty($row[$value['id'].'_LINK'])) {
                $linkValues = [];

                foreach ($row[$value['id'].'_LINK'] as $index => $link) {
                    $linkValues[] = [
                        'LINK' => $link,
                        'IS_LAST' => !isset($row[$value['id'].'_LINK'][$index + 1]),
                    ];
                }

                $rowData['COLUMN_LIST'][] = [
                    'CODE' => $value['id'],
                    'NAME' => $value['name'],
                    'VALUE' => $linkValues,
                    'IS_LINK' => true,
                    'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                ];
            } elseif (!empty($row[$value['id']])) {
                $rawValue = isset($row['~'.$value['id']]) ? $row['~'.$value['id']] : $row[$value['id']];
                $isHtml = !empty($row[$value['id'].'_HTML']);

                if (strpos($value['id'], 'SUB_TITLE') !== false) {
                    $rowData['SUB_TITLE'] = $rawValue;
                    continue;
                }

                if (strpos($value['id'], 'PROPERTY_') !== false) {
                    if ($propCode = str_replace(['PROPERTY_', '_VALUE'], '', $value['id'])) {
                        if (!$arProp = CIBlockElement::GetProperty(
                            $parentItemIDs['IBLOCK_ID'],
                            $parentItemIDs['ID'],
                            ['sort', 'asc'],
                            ['CODE' => $propCode]
                        )->Fetch()) {
                            $arProp = CIBlockElement::GetProperty(
                                $parentItemIDs['OFFER_IBLOCK_ID'],
                                $parentItemIDs['ID'],
                                ['sort', 'asc'],
                                ['CODE' => $propCode]
                            )->Fetch();
                        }
                        if ($arProp) {
                            if ($arProp['USER_TYPE'] === 'SAsproPremierTextWithLink') {
                                continue;
                            }
                        }
                    }
                }

                $rowData['COLUMN_LIST'][] = [
                    'CODE' => $value['id'],
                    'NAME' => $value['name'],
                    'VALUE' => $rawValue,
                    'IS_TEXT' => !$isHtml,
                    'IS_HTML' => $isHtml,
                    'HIDE_MOBILE' => !isset($mobileColumns[$value['id']]),
                ];
            }
        }

        unset($value);
    }

    if (!empty($row['LABEL_ARRAY_VALUE'])) {
        $labels = [];

        foreach ($row['LABEL_ARRAY_VALUE'] as $code => $value) {
            $labels[] = [
                'NAME' => $value,
                'HIDE_MOBILE' => !isset($this->arParams['LABEL_PROP_MOBILE'][$code]),
            ];
        }

        $rowData['SHOW_LABEL'] = true;
        $rowData['LABEL_VALUES'] = $labels;
    }

    $result['BASKET_ITEM_RENDER_DATA'][] = $rowData;
}
$result['SERVICES_RENDER_DATA'] = $arServices;
$result['ITEMS_RENDER_DATA'] = $arItems;

$totalData = [
    'DISABLE_CHECKOUT' => (int) $result['ORDERABLE_BASKET_ITEMS_COUNT'] === 0,
    'PRICE' => $result['allSum'],
    'PRICE_FORMATED' => $result['allSum_FORMATED'],
    'PRICE_WITHOUT_DISCOUNT_FORMATED' => $result['PRICE_WITHOUT_DISCOUNT'],
    'CURRENCY' => $result['CURRENCY'],
];

if ($arServices['COUNT']) {
    $totalData['SERVICES_COUNT'] = $arServices['COUNT'];
    $totalData['SERVICES_SUMM'] = CCurrencyLang::CurrencyFormat($arServices['SUMM'], $rowData['CURRENCY'], true);

    $totalData['ITEMS_COUNT'] = $arItems['COUNT'];
    $totalData['ITEMS_SUMM'] = CCurrencyLang::CurrencyFormat($arItems['SUMM'], $rowData['CURRENCY'], true);
}

if ($result['DISCOUNT_PRICE_ALL'] > 0) {
    $totalData['DISCOUNT_PRICE_FORMATED'] = $result['DISCOUNT_PRICE_FORMATED'];
}

if ($result['allWeight'] > 0) {
    $totalData['WEIGHT_FORMATED'] = $result['allWeight_FORMATED'];
}

if ($this->priceVatShowValue === 'Y') {
    $totalData['SHOW_VAT'] = true;
    $totalData['VAT_SUM_FORMATED'] = $result['allVATSum_FORMATED'];
    $totalData['SUM_WITHOUT_VAT_FORMATED'] = $result['allSum_wVAT_FORMATED'];
}

if ($this->hideCoupon !== 'Y' && !empty($result['COUPON_LIST'])) {
    $totalData['HAS_COUPON'] = true;
    $totalData['COUPON_LIST'] = $result['COUPON_LIST'];

    foreach ($totalData['COUPON_LIST'] as &$coupon) {
        if ($coupon['JS_STATUS'] === 'ENTERED') {
            $coupon['CLASS'] = 'danger';
        } elseif ($coupon['JS_STATUS'] === 'APPLYED') {
            $coupon['CLASS'] = 'success';
        } else {
            $coupon['CLASS'] = 'danger';
        }
    }
}

$result['TOTAL_RENDER_DATA'] = $totalData;
