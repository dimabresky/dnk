<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$arDefaultParams = [
    'TYPE_SKU' => 'N',
    'FILTER_HIT_PROP' => 'block',
    'OFFER_TREE_PROPS' => ['-'],
];
$arParams = array_merge($arDefaultParams, $arParams);
$arParams['DISPLAY_COMPARE'] = $arParams['DISPLAY_COMPARE'] ? 'Y' : 'N';

$bShowHintTextItem = in_array('INCLUDE_TEXT', $arParams['PROPERTY_CODE']);

$bShowSKU = $arParams['TYPE_SKU'] !== 'TYPE_2';
if (!empty($arResult['ITEMS'])) {
    if ($bShowHintTextItem) {?>
        <?ob_start(); ?>
            <?$APPLICATION->IncludeComponent(
                'bitrix:main.include',
                '',
                [
                    'AREA_FILE_SHOW' => 'page',
                    'AREA_FILE_SUFFIX' => 'help_text',
                    'EDIT_TEMPLATE' => '',
                ]
            ); ?>
        <?$help_text = ob_get_contents();
        ob_end_clean();
        $bshowHelpTextFromFile = true;
        $arResult['INCLUDE_TEXT_FILE'] = false;
        if (strlen(trim($help_text)) < 1) {
            $bshowHelpTextFromFile = false;
        } else {
            $bIsBitrixDiv = (strpos($help_text, 'bx_incl_area') !== false);
            $textWithoutTags = strip_tags($help_text);
            if ($bIsBitrixDiv && (strlen(trim($textWithoutTags)) < 1)) {
                $bshowHelpTextFromFile = false;
            }
        }

        if ($bshowHelpTextFromFile) {
            $arResult['INCLUDE_TEXT'] = $help_text;
            $arResult['INCLUDE_TEXT_FILE'] = true;
        }
    }

    if ($bShowSKU) {
        $arSKU = (array) CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);

        if (!empty($arSKU) && is_array($arSKU)) {
            /* get sku tree props */
            $arParams['SKU_IBLOCK_ID'] = $arSKU['IBLOCK_ID'];
            $arParams['LINK_SKU_PROP_CODE'] = 'CML2_LINK';
            $arParams['USE_CATALOG_SKU'] = true;

            $bUseModuleProps = Bitrix\Main\Config\Option::get('iblock', 'property_features_enabled', 'N') === 'Y';
            if ($bUseModuleProps) {
                $arParams['OFFERS_CART_PROPERTIES'] = Bitrix\Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($arSKU['IBLOCK_ID'], ['CODE' => 'Y']);
                if ($featureProps = Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes($arSKU['IBLOCK_ID'], ['CODE' => 'Y'])) {
                    $arParams['SKU_TREE_PROPS'] = $featureProps;
                }
                if ($featureProps = Bitrix\Iblock\Model\PropertyFeature::getListPageShowPropertyCodes($arSKU['IBLOCK_ID'], ['CODE' => 'Y'])) {
                    $arParams['SKU_PROPERTY_CODE'] = $featureProps;
                }
            }

            if (!$arParams['SKU_TREE_PROPS'] && isset($arParams['OFFERS_CART_PROPERTIES']) && is_array($arParams['OFFERS_CART_PROPERTIES'])) {
                $arParams['SKU_TREE_PROPS'] = $arParams['OFFERS_CART_PROPERTIES'];
            }

            $obSKU = new TSolution\SKU($arParams);
            if ($arParams['SKU_IBLOCK_ID'] && $arParams['SKU_TREE_PROPS']) {
                $arTreeFilter = [
                    '=IBLOCK_ID' => $arParams['SKU_IBLOCK_ID'],
                    'CODE' => $arParams['SKU_TREE_PROPS'],
                ];
                $obSKU->getTreePropsByFilter($arTreeFilter, $arSKU);
                $arResult['SKU_CONFIG'] = $obSKU->config;
                $arResult['SKU_CONFIG']['ADD_PICT_PROP'] = $arParams['ADD_PICT_PROP'];
                $arResult['SKU_CONFIG']['SHOW_GALLERY'] = $arParams['SHOW_GALLERY'];
                $arResult['SKU_CONFIG']['SHOW_SKU_DESCRIPTION'] = $arParams['SHOW_SKU_DESCRIPTION'];

                $arResult['SKU_CONFIG']['USE_SIDE_ICONS'] = 'Y';
                $arResult['SKU_CONFIG']['ICONS_PROPS']['ORIENT'] = 'vertical';
                $arResult['SKU_CONFIG']['ICONS_PROPS']['SIDE_CLASSES'] = 'visible-600';

                // set only existed values for props
                $arFilterSKU = $GLOBALS[$arParams['FILTER_NAME']];
                if ($arResult['ITEMS']) {
                    if ($arFilterSKU && $arFilterSKU['OFFERS_ID']) {
                        foreach ($arResult['ITEMS'] as $key => $arItem) {
                            if ($arItem['OFFERS']) {
                                $arResult['ITEMS'][$key]['OFFERS'] = array_filter($arItem['OFFERS'], function ($arValue) use ($arFilterSKU) {
                                    return in_array($arValue['ID'], $arFilterSKU['OFFERS_ID']);
                                });
                            }
                        }
                    }
                    $obSKU->setItems($arResult['ITEMS']);
                    $obSKU->getNeedValues();
                }

                $obSKU->getPropsValue();
            }
        }
    }

    $arNewItemsList = $arGoodsSectionsIDs = [];
    foreach ($arResult['ITEMS'] as $key => $arItem) {
        if ($arItem['PRODUCT_PROPERTIES_FILL']) {
            foreach ($arItem['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo) {
                if (isset($arItem['PRODUCT_PROPERTIES'][$propID])) {
                    unset($arItem['PRODUCT_PROPERTIES'][$propID]);
                }
            }
        }

        if (is_array($arItem['PROPERTIES']['CML2_ARTICLE']['VALUE']) && $arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']) {
            $arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'] = reset($arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE']);
            $arResult['ITEMS'][$key]['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'] = $arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'];
        }

        if (($arItem['DETAIL_PICTURE'] && $arItem['PREVIEW_PICTURE']) || (!$arItem['DETAIL_PICTURE'] && $arItem['PREVIEW_PICTURE'])) {
            $arItem['DETAIL_PICTURE'] = $arItem['PREVIEW_PICTURE'];
        }

        $arItem['GALLERY'] = TSolution\Functions::getSliderForItem([
            'TYPE' => 'catalog_block',
            'PROP_CODE' => $arParams['ADD_PICT_PROP'],
            // 'ADD_DETAIL_SLIDER' => false,
            'ITEM' => $arItem,
            'PARAMS' => $arParams,
        ]);
        array_splice($arItem['GALLERY'], $arParams['MAX_GALLERY_ITEMS']);

        if (!empty($arItem['DISPLAY_PROPERTIES'])) {
            foreach ($arItem['DISPLAY_PROPERTIES'] as $propKey => $arDispProp) {
                if ($arDispProp['PROPERTY_TYPE'] == 'F') {
                    unset($arItem['DISPLAY_PROPERTIES'][$propKey]);
                }
            }
        }

        $arItem['PROPS'] = [];
        if (!empty($arItem['DISPLAY_PROPERTIES'])) {
            foreach ($arItem['DISPLAY_PROPERTIES'] as $propKey => $arDispProp) {
                if ($arDispProp['PROPERTY_TYPE'] == 'F' || $arDispProp['CODE'] == $arParams['STIKERS_PROP']) {
                    unset($arItem['DISPLAY_PROPERTIES'][$propKey]);
                }
            }
            $arItem['PROPS'] = TSolution::PrepareItemProps($arItem['DISPLAY_PROPERTIES']);
            TSolution\LinkableProperty::resolve($arItem['PROPS'], $arItem['IBLOCK_ID'], $arItem['IBLOCK_SECTION_ID']);
        }

        if ($arItem['IBLOCK_SECTION_ID']) {
            if ($bShowHintTextItem) {
                $resGroups = CIBlockElement::GetElementGroups($arItem['ID'], true, ['ID']);
                while ($arGroup = $resGroups->Fetch()) {
                    $arItem['SECTIONS'][$arGroup['ID']] = $arGroup['ID'];
                }
            }

            /* get UF_INCLUDE_TEXT */
            if ($bShowHintTextItem) {
                $sectionHelpText = '';
                $sectionID = $arItem['SECTIONS'] ? reset($arItem['SECTIONS']) : $arItem['IBLOCK_SECTION_ID'];
                $arSection = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', 'ID' => $sectionID, 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'UF_INCLUDE_TEXT']);

                if (strlen($arSection['UF_INCLUDE_TEXT'])) {
                    $sectionHelpText = $arSection['UF_INCLUDE_TEXT'];
                }
                if (!$sectionHelpText) {
                    if ($arSection['DEPTH_LEVEL'] > 2) {
                        $arSectionParent = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', 'ID' => $arSection['IBLOCK_SECTION_ID'], 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'UF_INCLUDE_TEXT']);
                        if (strlen($arSectionParent['UF_INCLUDE_TEXT'])) {
                            $sectionHelpText = $arSectionParent['UF_INCLUDE_TEXT'];
                        }

                        if (!$sectionHelpText) {
                            $arSectionRoot = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', '<=LEFT_BORDER' => $arSection['LEFT_MARGIN'], '>=RIGHT_BORDER' => $arSection['RIGHT_MARGIN'], 'DEPTH_LEVEL' => 1, 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'UF_INCLUDE_TEXT']);
                            if (strlen($arSectionRoot['UF_INCLUDE_TEXT'])) {
                                $sectionHelpText = $arSectionRoot['UF_INCLUDE_TEXT'];
                            }
                        }
                    } else {
                        $arSectionRoot = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', '<=LEFT_BORDER' => $arSection['LEFT_MARGIN'], '>=RIGHT_BORDER' => $arSection['RIGHT_MARGIN'], 'DEPTH_LEVEL' => 1, 'IBLOCK_ID' => $arParams['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'UF_INCLUDE_TEXT']);
                        if (strlen($arSectionRoot['UF_INCLUDE_TEXT'])) {
                            $sectionHelpText = $arSectionRoot['UF_INCLUDE_TEXT'];
                        }
                    }
                }
            }
        }

        if ($bShowHintTextItem) {
            if ($arItem['DISPLAY_PROPERTIES']['INCLUDE_TEXT']['~VALUE']) {
                $arItem['INCLUDE_TEXT'] = $arItem['DISPLAY_PROPERTIES']['INCLUDE_TEXT']['~VALUE']['TEXT'];
            } elseif ($sectionHelpText) {
                $arItem['INCLUDE_TEXT'] = $sectionHelpText;
            } elseif ($arResult['INCLUDE_TEXT_FILE']) {
                $arItem['INCLUDE_TEXT'] = $arResult['INCLUDE_TEXT'];
            }
        }

        $arItem['LAST_ELEMENT'] = 'N';

        if ($arParams['IBINHERIT_TEMPLATES']) {
            TSolution\Property\IBInherited::modifyItemTemplates($arParams, $arItem);
        }

        $arItem['HAS_SKU'] = !$bShowSKU && $arItem['OFFERS'];

        $arItem['PRODUCT_ANALOG'] = ($arItem['PROPERTIES']['OUT_OF_PRODUCTION']['VALUE'] ?? 'N') === 'Y' && !empty($arItem['PROPERTIES']['PRODUCT_ANALOG']['VALUE']);
        if ($arItem['PRODUCT_ANALOG'] && !empty($arItem['PROPERTIES']['PRODUCT_ANALOG_FILTER']['VALUE'])) {
            $arItem['PRODUCT_ANALOG_FILTER'] = $arItem['PROPERTIES']['PRODUCT_ANALOG_FILTER']['VALUE'];
        }

        $arItem['SHOW_MORE'] = $arItem['PRODUCT_ANALOG'] || ($arParams['TYPE_SKU'] === 'TYPE_2' && $arItem['HAS_SKU']);

        if ($arItem['OFFERS']) {
            if ($bShowSKU && !$arItem['PRODUCT_ANALOG']) {
                /* get SKU for item */
                if (isset($arItem['OFFER_ID_SELECTED']) && $arItem['OFFER_ID_SELECTED'] > 0) {
                    $obSKU->setSelectedItem($arItem['OFFER_ID_SELECTED']);
                }

                $obSKU->setItems($arItem['OFFERS']);
                $obSKU->getMatrix();

                $arItem['SKU'] = [
                    'CURRENT' => $obSKU->currentItem,
                    'OFFERS' => $obSKU->items,
                    'PROPS' => $obSKU->treeProps,
                ];
            } else {
                TSolution\Product\Prices::fixOffersMinPrice($arItem['OFFERS'], $arParams);
                $arItem['MIN_PRICE'] = TSolution\Product\Price::getMinPriceFromOffersExt($arItem['OFFERS']);
            }
        }

        $arNewItemsList[$key] = $arItem;
    }

    $arNewItemsList[$key]['LAST_ELEMENT'] = 'Y';
    $arResult['ITEMS'] = $arNewItemsList;

    unset($arNewItemsList);
}
