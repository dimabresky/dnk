<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Web\Json;

$this->setFrameMode(true);

$countItems = count($arResult['ITEMS']);
if (!$countItems) {
    return;
}

?>
<!-- items-container -->
<?php
$elementInRow = $arParams['ELEMENT_IN_ROW'];

$bDots1200 = $arParams['DOTS_1200'] === 'Y' ? 1 : 0;
if ($arParams['ITEM_1200']) {
    $items1200 = intval($arParams['ITEM_1200']);
} else {
    $items1200 = $arParams['ELEMENT_IN_ROW'] ? $arParams['ELEMENT_IN_ROW'] : 1;
}

if ($arParams['ITEM_768']) {
    $items768 = intval($arParams['ITEM_768']);
} else {
    $items768 =
        $arParams['ELEMENT_IN_ROW'] > 1 ? 4 : 1;
}

if($arParams['ITEM_992']) {
    $items992 = intval($arParams['ITEM_992']);
} else {
    $items992 = ($items1200 - 1);
}

if ($arParams['ITEM_380']) {
    $items380 = intval($arParams['ITEM_380']);
} else {
    $items380 = 3;
}

if ($arParams['ITEM_0']) {
    $items0 = intval($arParams['ITEM_0']);
} else {
    $items0 = 1;
}

$sliderClasses = ' swiper slider-solution slider-solution--hide-before-loaded mobile-offset mobile-offset--right';
$sliderWrapperClasses = ' swiper-wrapper mobile-scrolled--items-3';
$elementSliderClasses = ' swiper-slide swiper-slide--height-auto';

$itemClass = ' outer-rounded-x bg-theme-parent-hover border-theme-parent-hover color-theme-parent-all js-popup-block';

if ($arParams['BORDERED'] !== 'N') {
    $itemClass .= ' bordered';
}
?>
<div class="catalog-items catalog-complect <?=$templateName; ?>_template <?=$arParams['IS_COMPACT_SLIDER'] ? 'compact-catalog-slider' : ''; ?>">
    <div class="catalog-block relative swiper-nav-offset">
        <?php
        $sliderOptions = [
            'loop' => $arParams['SLIDER_LOOP'] === 'Y',
            'autoplay' => false,
            'slidesPerView' => 'auto',
            'freeMode' => true,
            'breakpoints' => [
                601 => [
                    'freeMode' => false,
                    'slidesPerView' => $items380,
                ],
                768 => [
                    'freeMode' => false,
                    'slidesPerView' => $items768,
                ],
                992 => [
                    'freeMode' => false,
                    'slidesPerView' => $items992,
                ],
                1200 => [
                    'freeMode' => false,
                    'slidesPerView' => $items1200,
                ],
            ],
        ];
?>
        <div class="appear-block block <?=$sliderClasses; ?>" data-plugin-options='<?=Json::encode($sliderOptions); ?>'>
            <div class="<?=$sliderWrapperClasses; ?>">
                <?$iteratorItems = 0; ?>
                <?foreach($arResult['ITEMS'] as $arItem) {
                    $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT'));
                    $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE'), ['CONFIRM' => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')]);

                    $item_id = $arItem['ID'];

                    $arItem['strMainID'] = $this->GetEditAreaId($arItem['ID']);

                    $elementName = TSolution\Product\Common::getElementName($arItem);

                    /* sku replace start */
                    $arCurrentOffer = $arItem['SKU']['CURRENT'];

                    if ($arCurrentOffer) {
                        $arItem['PARENT_IMG'] = '';
                        if ($arItem['PREVIEW_PICTURE']) {
                            $arItem['PARENT_IMG'] = $arItem['PREVIEW_PICTURE'];
                        } elseif ($arItem['DETAIL_PICTURE']) {
                            $arItem['PARENT_IMG'] = $arItem['DETAIL_PICTURE'];
                        }

                        $oid = Bitrix\Main\Config\Option::get(VENDOR_MODULE_ID, 'CATALOG_OID', 'oid');
                        if ($oid) {
                            $arItem['DETAIL_PAGE_URL'] .= '?'.$oid.'='.$arCurrentOffer['ID'];
                            $arCurrentOffer['DETAIL_PAGE_URL'] = $arItem['DETAIL_PAGE_URL'];
                        }
                        if ($arParams['SHOW_GALLERY'] === 'Y') {
                            if ($arCurrentOffer['PREVIEW_PICTURE']) {
                                $arCurrentOffer['DETAIL_PICTURE'] = $arCurrentOffer['PREVIEW_PICTURE'];
                            }
                            $arOfferGallery = TSolution\Functions::getSliderForItem([
                                'TYPE' => 'catalog_block',
                                'PROP_CODE' => $arParams['OFFER_ADD_PICT_PROP'],
                                // 'ADD_DETAIL_SLIDER' => false,
                                'ITEM' => $arCurrentOffer,
                                'PARAMS' => $arParams,
                            ]);
                            if ($arOfferGallery) {
                                $arItem['GALLERY'] = array_merge($arOfferGallery, $arItem['GALLERY']);
                                array_splice($arItem['GALLERY'], $arParams['MAX_GALLERY_ITEMS']);
                            }
                        } else {
                            if ($arCurrentOffer['PREVIEW_PICTURE'] || $arCurrentOffer['DETAIL_PICTURE']) {
                                if ($arCurrentOffer['PREVIEW_PICTURE']) {
                                    $arItem['PREVIEW_PICTURE'] = $arCurrentOffer['PREVIEW_PICTURE'];
                                } elseif ($arCurrentOffer['DETAIL_PICTURE']) {
                                    $arItem['PREVIEW_PICTURE'] = $arCurrentOffer['DETAIL_PICTURE'];
                                }
                            }
                            if (!$arCurrentOffer['PREVIEW_PICTURE'] && !$arCurrentOffer['DETAIL_PICTURE']) {
                                if ($arItem['PREVIEW_PICTURE']) {
                                    $arCurrentOffer['PREVIEW_PICTURE'] = $arItem['PREVIEW_PICTURE'];
                                } elseif ($arItem['DETAIL_PICTURE']) {
                                    $arCurrentOffer['PREVIEW_PICTURE'] = $arItem['DETAIL_PICTURE'];
                                }
                            }
                        }

                        $arItem['DISPLAY_PROPERTIES']['FORM_ORDER'] = $arCurrentOffer['DISPLAY_PROPERTIES']['FORM_ORDER'];
                        $arItem['DISPLAY_PROPERTIES']['PRICE'] = $arCurrentOffer['DISPLAY_PROPERTIES']['PRICE'];

                        if($arParams['SET_SKU_TITLE'] !== 'N') {
                            $arItem['NAME'] = $arCurrentOffer['NAME'];
                            $elementName = TSolution\Product\Common::getElementName($arCurrentOffer);
                        }

                        $arItem['OFFER_PROP'] = TSolution::PrepareItemProps($arCurrentOffer['DISPLAY_PROPERTIES']);
                    }
                    /* sku replace end */
                    ?>

                    <div class="catalog-complect__item-wrapper relative <?=$elementSliderClasses; ?> grid-list__item grid-list-border-outer <?= $isShowBottomBlock ? 'has-offers' : ''; ?>" data-hovered="false">
                        <?if ($iteratorItems++ !== 0):?>
                            <span class="catalog-complect__delimeter d-block absolute rounded white-bg"></span>
                        <?endif; ?>

                        <div class="catalog-block__item <?=$itemClass; ?>" id="<?=$arItem['strMainID']; ?>">
                            <div class="catalog-block__inner flexbox height-100">
                                <?if (
                                    $arItem['QUANTITY_COMPLECT']['SHOW_QUANTITY'] === 'Y'
                                    && $arItem['QUANTITY_COMPLECT']['QUANTITY'] > 1
                                ):?>
                                    <span class="catalog-complect__amount d-block bordered white-bg absolute z-index-1 button-rounded-x font_14 fw-500 p-inline p-inline--8 p-block p-block--4">x<?=$arItem['QUANTITY_COMPLECT']['QUANTITY']; ?></span>
                                <?endif; ?>
                                <?$arImgConfig = [
                                    'TYPE' => 'catalog_block',
                                    'ADDITIONAL_IMG_CLASS' => 'js-replace-img',
                                    'ADDITIONAL_WRAPPER_CLASS' => ($arParams['IMG_CORNER'] == 'Y' ? 'catalog-block__item--img-corner' : ''),
                                ]; ?>
                                <div class="js-config-img" data-img-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arImgConfig, false, true)); ?>'></div>

                                <?=TSolution\Product\Image::showImage(
                                    array_merge(
                                        [
                                            'ITEM' => $arItem,
                                            'PARAMS' => $arParams,
                                        ],
                                        $arImgConfig
                                    )
                                ); ?>
                                <div class="catalog-block__info flex-1 flexbox flexbox--justify-between">
                                    <div class="catalog-block__info-top">
                                        <div class="catalog-block__info-inner">
                                        <?if($arParams["SHOW_KIT_PARTS_PRICES"] === "Y"):?>
                                            <?// element price?>
                                            <?$arPriceConfig = [
                                                'PRICE_CODE' => $arParams['PRICE_CODE'],
                                                'PRICE_FONT' => '16 font_14--to-600',
                                            ]; ?>
                                            <div class="js-popup-price mb mb--8" data-price-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arPriceConfig, false, true)); ?>'>
                                                <?$prices = (new TSolution\Product\Prices(
                                                    $arCurrentOffer ?: $arItem,
                                                    $arParams,
                                                    $arPriceConfig
                                                ))->show();?>
                                            </div>
                                        <?endif;?>
                                            <?// element title?>
                                            <div class="catalog-block__info-title lineclamp-3 height-auto-t600 font_14">
                                                <a href="<?=$arItem['DETAIL_PAGE_URL']; ?>" class="dark_link js-popup-title color-theme-target"><span><?=$elementName; ?></span></a>
                                            </div>
                                            <?if ($arItem['PROPERTIES']['SUB_TITLE']['VALUE']):?>
                                                <div class="preview_text font_13 secondary-color mt mt--2">
                                                    <?if (!is_array($arItem['PROPERTIES']['SUB_TITLE']['~VALUE'])):?>
                                                        <?=$arItem['PROPERTIES']['SUB_TITLE']['VALUE']; ?>
                                                    <?else:?>
                                                        <?=$arItem['PROPERTIES']['SUB_TITLE']['~VALUE']['TEXT']; ?>
                                                    <?endif; ?>
                                                </div>
                                            <?endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?}?>
            </div>
        </div> <?// .block grid-list?>

        <?TSolution\Functions::showBlockHtml([
            'FILE' => 'ui/slider-navigation.php',
            'PARAMS' => [
                'CLASSES' => 'slider-nav slider-nav--shadow',
            ],
        ]); ?>

    </div> <?// .catalog-block?>

    <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>
</div> <?// .catalog-items?>
