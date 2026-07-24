<?
use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (($arParams['BIG_DATA_MODE'] ?? 'N') === 'Y') {
    $component = $this->getComponent();
    $arParams = $component->applyTemplateModifications();
}

$arDefaultParams = array(
    'TYPE_SKU' => 'N',
    'FILTER_HIT_PROP' => 'block',
    'OFFER_TREE_PROPS' => array('-'),
    'BIG_DATA_MODE' => 'N',
    'BIGDATA_COUNT' => '10',
);
$arParams = array_merge($arDefaultParams, $arParams);

$arParams['DISPLAY_COMPARE'] = $arParams['DISPLAY_COMPARE'] ? 'Y' : 'N';

if ($arParams['SHOW_PROPS'] == 'Y') {
    $arParams['SHOW_GALLERY'] = 'N';
}

$bShowSKU = $arParams['TYPE_SKU'] !== 'TYPE_2';
if (!empty($arResult['ITEMS'])) {
    if ($bShowSKU) {
        $arSKU = (array)CCatalogSKU::GetInfoByProductIBlock($arParams['IBLOCK_ID']);

        if (!empty($arSKU) && is_array($arSKU)) {
            /* get sku tree props */
            $arParams['SKU_IBLOCK_ID'] = $arSKU['IBLOCK_ID'];
            $arParams['LINK_SKU_PROP_CODE'] = 'CML2_LINK';
            $arParams['USE_CATALOG_SKU'] = true;

            $bUseModuleProps = \Bitrix\Main\Config\Option::get("iblock", "property_features_enabled", "N") === "Y";
            if ($bUseModuleProps) {
                $arParams['OFFERS_CART_PROPERTIES'] = \Bitrix\Catalog\Product\PropertyCatalogFeature::getBasketPropertyCodes($arSKU['IBLOCK_ID'], ['CODE' => 'Y']);
                if ($featureProps = \Bitrix\Catalog\Product\PropertyCatalogFeature::getOfferTreePropertyCodes($arSKU["IBLOCK_ID"], array('CODE' => 'Y'))) {
                    $arParams['SKU_TREE_PROPS'] = $featureProps;
                }
                if ($featureProps = \Bitrix\Iblock\Model\PropertyFeature::getListPageShowPropertyCodes($arSKU["IBLOCK_ID"], array('CODE' => 'Y'))) {
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
                    'CODE' => $arParams['SKU_TREE_PROPS']
                ];
                $obSKU->getTreePropsByFilter($arTreeFilter, $arSKU);
                $arResult['SKU_CONFIG'] = $obSKU->config;
                $arResult['SKU_CONFIG']['ADD_PICT_PROP'] = $arParams['ADD_PICT_PROP'];
                $arResult['SKU_CONFIG']['SHOW_GALLERY'] = $arParams['SHOW_GALLERY'];

                $arResult['SKU_CONFIG']['USE_SIDE_ICONS'] = 'Y';
                $arResult['SKU_CONFIG']['SHOW_POPUP_PRICE'] = 'Y';
                $arResult['SKU_CONFIG']['ICONS_PROPS']['ORIENT'] = 'vertical';
                $arResult['SKU_CONFIG']['ICONS_PROPS']['CLASS'] = 'lg item-action__inner--only-icon btn btn-lg bordered';
                $arResult['SKU_CONFIG']['ICONS_PROPS']['SHOW_NOTIFICATION'] = 'N';
                $arResult['SKU_CONFIG']['SHOW_ONE_CLICK_BUY'] = $arParams['SHOW_ONE_CLICK_BUY'];

                // set only existed values for props
                $arFilterSKU = $GLOBALS[$arParams['FILTER_NAME']];
                if ($arResult['ITEMS']) {
                    if ($arFilterSKU && $arFilterSKU['OFFERS_ID']) {
                        foreach ($arResult['ITEMS'] as $key => $arItem) {
                            if ($arItem['OFFERS']) {
                                $arResult['ITEMS'][$key]['OFFERS'] = array_filter($arItem['OFFERS'], function($arValue) use ($arFilterSKU){
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
            /* */
        }
    }

    $arNewItemsList = [];
    foreach ($arResult['ITEMS'] as $key => $arItem) {
        if($arItem['PRODUCT_PROPERTIES_FILL']){
            foreach($arItem['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo){
                if(isset($arItem['PRODUCT_PROPERTIES'][$propID])){
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
                if ('F' == $arDispProp['PROPERTY_TYPE']) {
                    unset($arItem['DISPLAY_PROPERTIES'][$propKey]);
                }
            }
        }

        $arItem['PROPS'] = [];
        if (!empty($arItem['DISPLAY_PROPERTIES'])) {
            foreach ($arItem['DISPLAY_PROPERTIES'] as $propKey => $arDispProp) {
                if ('F' == $arDispProp['PROPERTY_TYPE'] || $arDispProp["CODE"] == $arParams["STIKERS_PROP"]) {
                    unset($arItem['DISPLAY_PROPERTIES'][$propKey]);
                }
            }
            $arItem['PROPS'] = TSolution::PrepareItemProps($arItem['DISPLAY_PROPERTIES']);
            TSolution\LinkableProperty::resolve($arItem['PROPS'], $arItem['IBLOCK_ID'], $arItem['IBLOCK_SECTION_ID']);
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
                    'PROPS' => $obSKU->treeProps
                ];

                TSolution\SKU::setSku2ItemValues($arItem, $arParams, true);
                /* */
            } else {
                TSolution\SKU::setSku2ItemValues($arItem, $arParams);

                TSolution\Product\Prices::fixOffersMinPrice($arItem['OFFERS'], $arParams);
                $arItem['MIN_PRICE'] = TSolution\Product\Price::getMinPriceFromOffersExt($arItem['OFFERS']);
            }
        }

        $arNewItemsList[$key] = $arItem;
    }

    $arNewItemsList[$key]['LAST_ELEMENT'] = 'Y';
    $arResult['ITEMS'] = $arNewItemsList;

    $pageElementIds = [];
    foreach ($arResult['ITEMS'] as $arCatalogItem) {
        $pageElementIds[] = (int) $arCatalogItem['ID'];
    }
    $skuExtraCountMap = Utils::getSkuGroupExtraCountMap(
        (int) $arParams['IBLOCK_ID'],
        47,
        $pageElementIds
    );
    foreach ($arResult['ITEMS'] as $itemKey => $arCatalogItem) {
        $arResult['ITEMS'][$itemKey]['DNK_SKU_EXTRA_COUNT'] = (int) (
            $skuExtraCountMap[(int) $arCatalogItem['ID']] ?? 0
        );
    }

    unset($arNewItemsList);
}
