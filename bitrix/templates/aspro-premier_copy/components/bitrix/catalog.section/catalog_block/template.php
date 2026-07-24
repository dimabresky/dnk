<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Web\Json;

$this->setFrameMode(true);

$bBigDataMode = $arParams['BIG_DATA_MODE'] === 'Y';
?>
<?if ($arResult['ITEMS']):?>
    <!-- items-container -->
    <?php
    $templateData['ITEMS'] = true;
    $templateData['HAS_CHARACTERISTICS'] = false;

    $bHasBottomPager = $arParams['DISPLAY_BOTTOM_PAGER'] == 'Y' && $arResult['NAV_STRING'];
    $bUseSchema = !(isset($arParams['NO_USE_SHCEMA_ORG']) && $arParams['NO_USE_SHCEMA_ORG'] == 'Y');
    $bAjax = $arParams['AJAX_REQUEST'] == 'Y';
    $bMobileScrolledItems = $arParams['MOBILE_SCROLLED'];

    $bSlider = $arParams['SLIDER'] === true || $arParams['SLIDER'] === 'Y';
    $bShowRating = $arParams['SHOW_RATING'] == 'Y';

    $elementInRow = $arParams['ELEMENT_IN_ROW'];

    $bOrderViewBasket = $arParams['ORDER_VIEW'];
    $basketURL = (strlen(trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE'])) ? trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE']) : '');

    $bUseSelectOffer = false;

    if ($bSlider) {
        $bDots1200 = $arParams['DOTS_1200'] === 'Y' ? 1 : 0;
        if($arParams['ITEM_1200']) {
            $items1200 = intval($arParams['ITEM_1200']);
        } else {
            $items1200 = $arParams['ELEMENT_IN_ROW'] ? $arParams['ELEMENT_IN_ROW'] : 1;
        }

        if($arParams['ITEM_768']) {
            $items768 = intval($arParams['ITEM_768']);
        } else {
            $items768 =
                $arParams['ELEMENT_IN_ROW'] > 1 ? 2 : 1;
        }

        if($arParams['ITEM_992']) {
            $items992 = intval($arParams['ITEM_992']);
        } else {
            $items992 = ($items1200 - 1);
        }

        if($arParams['ITEM_380']) {
            $items380 = intval($arParams['ITEM_380']);
        } else {
            $items380 = 1;
        }

        if($arParams['ITEM_0']) {
            $items0 = intval($arParams['ITEM_0']);
        } else {
            $items0 = 1;
        }

        $sliderClasses = ' swiper slider-solution slider-solution--hide-before-loaded mobile-offset mobile-offset--right';
        $sliderWrapperClasses = ' swiper-wrapper mobile-scrolled--items-2';
        $elementSliderClasses = ' swiper-slide swiper-slide--height-auto';
    } else {
        if($bMobileScrolledItems) {
            $gridClass .= ' mobile-scrolled mobile-scrolled--items-2 mobile-offset';
        } else {
            $gridClass .= ' grid-list--compact';
            if ($arParams['BORDERED'] === 'N') {
                $gridClass .= ' row-gap row-gap--40';
            }
        }

        $gridClass .= TSolution\Functions::getGridClassByCount([1200, 992, 768], $elementInRow);
    }

$itemClass = ' outer-rounded-x bg-theme-parent-hover border-theme-parent-hover color-theme-parent-all js-popup-block';
if (!$bSlider) {
    $itemClass .= ' shadow-hovered shadow-hovered-f600 shadow-no-border-hovered';
}

if ($arParams['BORDERED'] !== 'N') {
    $itemClass .= ' bordered';
}
?>
    <?if (!$bAjax):?>
    <div class="catalog-items <?=$templateName; ?>_template <?=$arParams['IS_COMPACT_SLIDER'] ? 'compact-catalog-slider' : ''; ?>">
        <!-- noindex -->
		<template class="props-template">
			<?TSolution\Functions::showBlockHtml([
				'FILE' => 'catalog/props/list.php',
				'PARAMS' => [
					'CLASS' => 'js-prop',
					'FONT_CLASSES' => 'font_12',
                    'WRAPPER_CLASSES' => '',
                    'GAP_SIZE' => '2',
				],
			]);?>
		</template>
		<!-- /noindex -->
        <div class="fast_view_params" data-params="<?=urlencode(serialize($arTransferParams)); ?>"></div>
        <?if ($arResult['SKU_CONFIG']):?><div class="js-sku-config" data-value='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arResult['SKU_CONFIG'], false, true)); ?>'></div><?endif; ?>
        <div class="catalog-block<?= $bSlider ? ' relative swiper-nav-offset' : ''; ?>" <?if ($bUseSchema):?>itemscope itemtype="http://schema.org/ItemList" itemprop="mainEntity"<?endif; ?> >
             <?if ($bUseSchema):?>
            <meta itemprop="name" content="<?=htmlspecialcharsbx(GetMessage('CATALOG_SECTION_LIST') . ($arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] ?: $arResult['NAME']))?>" />
            <meta itemprop="numberOfItems" content="<?=htmlspecialcharsbx($arResult['NAV_RESULT']->NavRecordCount)?>" />
            <?endif; ?>
            <?if ($bSlider):?>
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
                <div class="js_append ajax_load block appear-block <?=$sliderClasses; ?>" data-plugin-options='<?= Json::encode($sliderOptions); ?>'>
                <?if ($sliderWrapperClasses):?>
                    <div class="<?= $sliderWrapperClasses; ?>">
                <?endif; ?>
            <?else:?>
                <div class="js_append ajax_load block grid-list grid-list--fill-bg <?=$gridClass; ?>">
            <?endif; ?>
    <?endif; ?>
        <?foreach($arResult['ITEMS'] as $arItem) {
            $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT'));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE'), ['CONFIRM' => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')]);

            $item_id = $arItem['ID'];

            // if (isset($arParams['ID_FOR_TABS']) && $arParams['ID_FOR_TABS'] == 'Y') {
            // 	$arItem["strMainID"] = $this->GetEditAreaId($arItem['ID'])."_".$arParams["FILTER_HIT_PROP"];
            // } else {
            // 	$arItem["strMainID"] = $this->GetEditAreaId($arItem['ID']);
            // }
            $arItem['strMainID'] = $this->GetEditAreaId($arItem['ID']);

            $elementName = TSolution\Product\Common::getElementName($arItem);

            // use order button?
            $bOrderButton = ($arItem['PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES');
            $dataItem = TSolution::getDataItem($arItem);

            $article = $arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'];

            // unset($arItem['OFFERS']); // get correct totalCount
            $totalCount = TSolution\Product\Quantity::getTotalCount([
                'ITEM' => $arItem,
                'PARAMS' => $arParams,
            ]);
            $arStatus = TSolution\Product\Quantity::getStatus([
                'ITEM' => $arItem,
                'PARAMS' => $arParams,
                'TOTAL_COUNT' => $totalCount,
            ]);

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
                    if($arCurrentOffer['PREVIEW_PICTURE']) {
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

                if ($arCurrentOffer['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'] || $arCurrentOffer['DISPLAY_PROPERTIES']['ARTICLE']['VALUE']) {
                    $article = $arCurrentOffer['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE'] ?? $arCurrentOffer['DISPLAY_PROPERTIES']['ARTICLE']['VALUE'];
                }

                $arItem['DISPLAY_PROPERTIES']['FORM_ORDER'] = $arCurrentOffer['DISPLAY_PROPERTIES']['FORM_ORDER'];
                $arItem['DISPLAY_PROPERTIES']['PRICE'] = $arCurrentOffer['DISPLAY_PROPERTIES']['PRICE'];

                if($arParams['SET_SKU_TITLE'] !== 'N') {
                    $arItem['NAME'] = $arCurrentOffer['NAME'];
                    $elementName = TSolution\Product\Common::getElementName($arCurrentOffer);
                }

                $arItem['OFFER_PROP'] = TSolution::PrepareItemProps($arCurrentOffer['DISPLAY_PROPERTIES']);
                TSolution\LinkableProperty::resolve($arItem['OFFER_PROP'], $arCurrentOffer['IBLOCK_ID'], $arItem['IBLOCK_SECTION_ID']);

                $dataItem = TSolution::getDataItem($arCurrentOffer);

                $totalCount = TSolution\Product\Quantity::getTotalCount([
                    'ITEM' => $arCurrentOffer,
                    'PARAMS' => $arParams,
                ]);
                $arStatus = TSolution\Product\Quantity::getStatus([
                    'ITEM' => $arCurrentOffer,
                    'PARAMS' => $arParams,
                    'TOTAL_COUNT' => $totalCount,
                ]);
            }
            $bOrderButton = ($arItem['DISPLAY_PROPERTIES']['FORM_ORDER']['VALUE_XML_ID'] == 'YES');

            $status = $arStatus['NAME'];
            $statusCode = $arStatus['CODE'];

            /* sku replace end */

            if ($arItem['SHOW_MORE']) {
                $arItem['CAN_BUY'] = 'N';
                $totalCount = 0;
            }

            $arPriceConfig = [
                    'PRICE_CODE' => $arParams['PRICE_CODE'],
                    'PRICE_FONT' => '16 font_14--to-600',
                    'SHOW_SCHEMA' => $bUseSchema,
            ];

            $prices = (new TSolution\Product\Prices(
                $arCurrentOffer ?: $arItem,
                $arParams,
                $arPriceConfig
            ));

            TSolution\Product\Basket::setProductData([
                'TOTAL_COUNT' => $totalCount,
                'PRICE' => $prices,
            ]);?>

            <?ob_start(); ?>
                <?if ($arParams['SHOW_DISCOUNT_TIME'] === 'Y' && $arParams['SHOW_DISCOUNT_TIME_IN_LIST'] !== 'N'):?>
                    <?php
                    $discountDateTo = '';
                    $arDiscount = TSolution\Product\Price::getDiscountByItemID($arItem['ID']);
                    $discountDateTo = $arDiscount ? $arDiscount['ACTIVE_TO'] : '';
                    ?>
                    <?if ($discountDateTo):?>
                        <?$templateData['USE_COUNTDOWN'] = true;?>
                        <?TSolution\Functions::showDiscountCounter([
                            'ICONS' => true,
                            'DATE' => $discountDateTo,
                            'ITEM' => $arItem,
                        ]);?>
                    <?endif;?>
                <?endif;?>
            <?$itemDiscount = ob_get_clean();?>

            <?$itemSideIcons = TSolution\Product\Common::getSideIcons([
                'ITEM' => ($arCurrentOffer ?: $arItem),
                'PARAMS' => $arParams,
                'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
                'SHOW_COMPARE' => $arParams['DISPLAY_COMPARE'],
                'SHOW_ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'],
                'ONE_CLICK_ICON_CLASSES' => $arCurrentOffer ? 'hide-600' : '',
            ]); ?>

            <?$isHasProps = (!$bSlider);?>
            <?$isShowBottomBlock = ($arCurrentOffer || $isHasProps); ?>

            <div class="catalog-block__wrapper<?= $elementSliderClasses; ?> grid-list__item grid-list-border-outer <?= $isShowBottomBlock ? 'has-offers' : ''; ?>"<?= $arItem['BIG_DATA'] ? ' data-bigdata data-rcm="'.$arItem['RCM_ID'].'"' : ''; ?> data-hovered="false">
                <?if (TSolution::isSaleMode()):?>
                    <div class="basket_props_block" id="bx_basket_div_<?=$arItem['ID']; ?>_<?=$arParams['FILTER_HIT_PROP']; ?>" style="display: none;">
                        <?if (!empty($arItem['PRODUCT_PROPERTIES_FILL'])):?>
                            <?foreach ($arItem['PRODUCT_PROPERTIES_FILL'] as $propID => $propInfo):?>
                                <input type="hidden" name="<?=$arParams['PRODUCT_PROPS_VARIABLE']; ?>[<?=$propID; ?>]" value="<?=htmlspecialcharsbx($propInfo['ID']); ?>">
                                <?php
                                if (isset($arItem['PRODUCT_PROPERTIES'][$propID])) {
                                    unset($arItem['PRODUCT_PROPERTIES'][$propID]);
                                }
                                ?>
                            <?endforeach; ?>
                        <?endif; ?>
                        <?if ($arItem['PRODUCT_PROPERTIES']):?>
                            <div class="wrapper">
                                <?foreach($arItem['PRODUCT_PROPERTIES'] as $propID => $propInfo):?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group fill-animate">
                                                <?if (
                                                    $arItem['PROPERTIES'][$propID]['PROPERTY_TYPE'] == 'L'
                                                    && $arItem['PROPERTIES'][$propID]['LIST_TYPE'] == 'C'
                                                ):?>
                                                    <label class="font_14"><span><?=$arItem['PROPERTIES'][$propID]['NAME']; ?></span></label>
                                                    <?foreach($propInfo['VALUES'] as $valueID => $value):?>
                                                        <div class="form-radiobox">
                                                            <label class="form-radiobox__label">
                                                                <input class="form-radiobox__input" type="radio" name="<?=$arParams['PRODUCT_PROPS_VARIABLE']; ?>[<?=$propID; ?>]" value="<?=$valueID; ?>">
                                                                <span class="bx_filter_input_checkbox">
                                                                    <span><?=$value; ?></span>
                                                                </span>
                                                                <span class="form-radiobox__box"></span>
                                                            </label>
                                                        </div>
                                                    <?endforeach; ?>
                                                <?else:?>
                                                    <label class="font_14"><span><?=$arItem['PROPERTIES'][$propID]['NAME']; ?></span></label>
                                                    <div class="input">
                                                        <select class="form-control" name="<?=$arParams['PRODUCT_PROPS_VARIABLE']; ?>[<?=$propID; ?>]">
                                                            <?foreach($propInfo['VALUES'] as $valueID => $value):?>
                                                                <option value="<?=$valueID; ?>" <?= $valueID == $propInfo['SELECTED'] ? '"selected"' : ''; ?>><?=$value; ?></option>
                                                            <?endforeach; ?>
                                                        </select>
                                                    </div>
                                                <?endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?endforeach; ?>
                            </div>
                        <?endif; ?>
                    </div>
                <?endif; ?>
                <div class="catalog-block__item <?=$itemClass; ?>" id="<?=$arItem['strMainID']; ?>">
                    <div class="catalog-block__underlay"></div>
                    <?if ($arItem['SKU']['PROPS']):?>
                        <template class="offers-template-json">
                            <?=TSolution\SKU::getOfferTreeJson($arItem['SKU']['OFFERS']); ?>
                        </template>
                        <?$bUseSelectOffer = true; ?>
                    <?endif; ?>
                    <div class="catalog-block__inner flexbox height-100" <?if ($bUseSchema):?>itemprop="itemListElement" itemscope="" itemtype="http://schema.org/Product"<?endif; ?>>
                        <?if ($bUseSchema):?>
                            <?$ratingValue = $arItem['PROPERTIES']['EXTENDED_REVIEWS_RAITING']['VALUE'];
                            $reviewCount = intval($arItem['PROPERTIES']['EXTENDED_REVIEWS_COUNT']['VALUE']) ;?>
                            <?TSolution\Scheme\Common::showAggregateRating($ratingValue, $reviewCount);?>
                            <meta itemprop="description" content="<?=htmlspecialcharsbx(strip_tags($arItem['PREVIEW_TEXT'] ?: $arItem['NAME'])); ?>" />
                            <meta itemprop="category" content="<?=htmlspecialcharsbx($arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] ?: $arResult['NAME'])?>" />
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
                                    'CONTENT_TOP' => $itemDiscount,
                                    'CONTENT_SIDE' => $itemSideIcons,
                                ],
                                $arImgConfig
                            )
                        ); ?>

                        <?if ($bUseSchema):?>
                            <?$imagePath = $arItem['PREVIEW_PICTURE']['SRC'] ?? $arItem['DETAIL_PICTURE']['SRC'] ?? SITE_TEMPLATE_PATH . '/images/svg/noimage_product.svg';?>
                            <meta itemprop="name" content="<?=$arItem['NAME']; ?>">
                            <meta itemprop="image" content="<?= Tsolution\Utils::getAbsolutePath($imagePath)?>">
                            <link itemprop="url" href="<?=Tsolution\Utils::getAbsolutePath($arItem['DETAIL_PAGE_URL']); ?>">
                        <?endif; ?>
                        <div
                            class="catalog-block__info flex-1 flexbox flexbox--justify-between"
                            data-id="<?= $arCurrentOffer ? $arCurrentOffer['ID'] : $arItem['ID']; ?>"
                            data-item="<?=$dataItem; ?>"
                            <?if ($bUseSchema):?>itemprop="offers" itemscope itemtype="http://schema.org/Offer"<?endif; ?>
                        >
                            <div class="catalog-block__info-top">
                                <div class="catalog-block__info-inner">
                                    <div class="js-popup-price mb mb--8" data-price-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arPriceConfig, false, true)); ?>'>
                                        <?php
                                        $prices->show();
                                        ?>
                                    </div>

                                    <?// element title?>
                                    <div class="catalog-block__info-title lineclamp-3 height-auto-t600 font_14">
                                        <?if ($bUseSchema):?>
                                            <link itemprop="url" href="<?=Tsolution\Utils::getAbsolutePath($arItem['DETAIL_PAGE_URL']); ?>">
                                        <?endif; ?>
                                        <a href="<?=$arItem['DETAIL_PAGE_URL']; ?>" class="dark_link js-popup-title color-theme-target"><span><?=$elementName; ?></span></a>
                                    </div>
                                    <?TSolution\Product\Common::showSubTitle($arItem, 'font_13 mt mt--2');?>
                                    <?if ($bShowRating || strlen($status) || strlen($article)):?>

                                        <div class="catalog-block__info-tech">
                                            <div class="line-block line-block--gap line-block--gap-12 line-block--row-gap line-block--row-gap-4 flexbox--wrap js-popup-info">
                                                <?// rating?>
                                                <?if ($bShowRating):?>
                                                    <div class="line-block__item font_12">
                                                        <?=TSolution\Product\Common::getRatingHtml([
                                                            'ITEM' => $arItem,
                                                            'PARAMS' => $arParams,
                                                        ]); ?>
                                                    </div>
                                                    <div class="line-block__item font_12">
                                                        <?=TSolution\Product\Common::getReviewsCountHtml([
                                                            'ITEM' => $arItem,
                                                            'PARAMS' => $arParams,
                                                        ]); ?>
                                                    </div>
                                                <?endif; ?>

                                                <?// status?>
                                                <?if (strlen($status) && !isset($arParams['HIDE_STATUS_BUTTON'])):?>
                                                    <div class="line-block__item font_12">
                                                        <?TSolution\Product\Quantity::show(
                                                            $statusCode,
                                                            $status,
                                                            [
                                                                'USE_SHEMA_ORG' => $bUseSchema,
                                                            ]
                                                        ); ?>
                                                    </div>
                                                <?endif; ?>

                                                <?// article?>
                                                <?if (strlen($article)):?>
                                                    <div class="line-block__item font_12 secondary-color">
                                                        <span class="article"><?=GetMessage('S_ARTICLE'); ?>&nbsp;<span
                                                             class="js-replace-article"
                                                             data-value="<?=$arItem['DISPLAY_PROPERTIES']['CML2_ARTICLE']['VALUE']; ?>"
                                                            ><?=$article; ?></span></span>
                                                    </div>
                                                <?endif; ?>
                                            </div>
                                        </div>
                                    <?endif; ?>
                                    <?php $APPLICATION->IncludeComponent(
                                        'dnk:sku.list',
                                        'catalog_block',
                                        [
                                            'CACHE_TYPE' => 'N',
                                            'CACHE_TIME' => 3600,
                                            'IBLOCK_ID' => (int) $arItem['IBLOCK_ID'],
                                            'ELEMENT_ID' => (int) $arItem['ID'],
                                            'SHADES_IBLOCK_ID' => 47,
                                        ],
                                        $component,
                                        ['HIDE_ICONS' => 'Y']
                                    ); ?>
                                </div>
                            </div>
                        </div>
                        <?// element btns?>
                        <?php
                        $arBtnConfig = [
                            'BASKET_URL' => $basketURL,
                            'BASKET' => $bOrderViewBasket,
                            'ORDER_BTN' => $bOrderButton,
                            'BTN_CLASS' => 'btn-sm btn-wide',
                            'BTN_CLASS_MORE' => 'btn-sm bg-theme-target border-theme-target',
                            'BTN_IN_CART_CLASS' => 'btn-sm',
                            'BTN_CLASS_SUBSCRIBE' => 'btn-sm',
                            'BTN_ORDER_CLASS' => 'btn-sm btn-wide btn-transparent-bg',
                            'ONE_CLICK_BUY' => false,
                            'SHOW_COUNTER' => false,
                            'CATALOG_IBLOCK_ID' => $arItem['IBLOCK_ID'],
                            'ITEM_ID' => $arItem['ID'],
                        ];

                        if ($arItem['SHOW_MORE']) {
                            $arBtnConfig['SHOW_MORE'] = true;
                        }

                        $arBasketConfig = TSolution\Product\Basket::getOptions(array_merge(
                            $arBtnConfig,
                            [
                                'ITEM' => ($arCurrentOffer ?: $arItem),
                                'IS_OFFER' => (bool) $arCurrentOffer,
                                'PARAMS' => $arParams,
                                'TOTAL_COUNT' => $totalCount,
                                'HAS_PRICE' => $prices->isGreaterThanZero(),
                                'EMPTY_PRICE' => $prices->isEmpty(),
                            ]
                        ));
                        ?>

                        <?if (
                            ($isShowBottomBlock || $arBasketConfig['HTML'])
                            && !isset($arParams['HIDE_BUY_BUTTON'])
                        ):?>
                            <div class="catalog-block__info-bottom <?= $arCurrentOffer ? 'catalog-block__info-bottom--with-sku' : ''; ?>">
                                <div class="catalog-block__info-bottom-wrapper">
                                    <div class="js-btn-state-wrapper flex-1 <?= $arCurrentOffer ? 'hide-600' : ''; ?> <?= !$arBasketConfig['HTML'] ? ' hidden' : ''; ?>">
                                        <div class="js-replace-btns js-config-btns" data-btn-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arBtnConfig, false, true)); ?>'>
                                            <?=$arBasketConfig['HTML']; ?>
                                        </div>
                                    </div>
                                    <?if ($arCurrentOffer):?>
                                        <div class="visible-600 flex-1">
                                            <?=TSolution\Product\Basket::getMoreButton([
                                                'ITEM' => $arCurrentOffer,
                                                'BTN_CLASS_MORE' => '',
                                            ]); ?>
                                        </div>
                                    <?endif; ?>
                                </div>
                                <?if ($isHasProps):?>
                                    <div class="catalog-block__offers hide-600">
                                        <div
                                        class="sku-props sku-props--block"
                                        data-site-id="<?=SITE_ID; ?>"
                                        data-item-id="<?=$arItem['ID']; ?>"
                                        data-iblockid="<?=$arItem['IBLOCK_ID']; ?>"
                                        data-offer-id="<?=$arCurrentOffer['ID']; ?>"
                                        data-offer-iblockid="<?=$arCurrentOffer['IBLOCK_ID']; ?>"
                                        data-offers-id='<?=str_replace('\'', '"', CUtil::PhpToJSObject($GLOBALS[$arParams['FILTER_NAME']]['OFFERS_ID'], false, true)); ?>'
                                        >
                                            <?if ($arItem['SKU']['PROPS']):?>
                                                <div class="line-block line-block--flex-wrap line-block--gap line-block--gap-0 line-block--align-flex-end line-block--flex-100">
                                                    <?=TSolution\SKU\Template::showSkuPropsHtml($arItem['SKU']['PROPS']); ?>
                                                </div>
                                            <?endif; ?>
                                            <?$templateData['HAS_CHARACTERISTICS'] = true; ?>
                                            <?TSolution\Functions::showBlockHtml([
                                                'FILE' => '/catalog/props_in_section.php',
                                                'ITEM' => $arItem,
                                                'PARAMS' => [
                                                    'WRAPPER_CLASSES' => 'mt mt--16',
                                                    'FONT_CLASSES' => 'font_12',
                                                    'TEXT_CLASSES' => 'secondary-color',
                                                    'GAP_SIZE' => '2',
                                                    'SHOW_HINTS' => $arParams['SHOW_HINTS'],
                                                ],
                                            ]); ?>
                                        </div>
                                    </div>
                                <?endif; ?>
                            </div>
                        <?endif; ?>
                    </div>
                </div>

            </div>
        <?}?>

        <?if (!$bSlider):?>
            <?if ($bHasBottomPager && $bMobileScrolledItems):?>
                <?if ($bAjax):?>
                    <div class="wrap_nav bottom_nav_wrapper">
                <?endif; ?>

                    <?$bHasNav = (strpos($arResult['NAV_STRING'], 'more_text_ajax') !== false); ?>
                    <div class="bottom_nav mobile_slider <?= $bHasNav ? '' : ' hidden-nav'; ?>" data-parent=".catalog-block" data-append=".grid-list" <?= $bAjax ? "style='display: none; '" : ''; ?>>
                        <?=$arResult['NAV_STRING']; ?>
                    </div>

                <?if ($bAjax):?>
                    </div>
                <?endif; ?>
            <?endif; ?>
        <?endif; ?>

    <?if (!$bAjax):?>
            <?if ($sliderWrapperClasses):?>
                </div>
            <?endif; ?>
            </div> <?// .js_append ajax_load block grid-list?>

            <?if ($bSlider):?>
                <?TSolution\Functions::showBlockHtml([
                    'FILE' => 'ui/slider-navigation.php',
                    'PARAMS' => [
                        'CLASSES' => 'slider-nav slider-nav--shadow',
                    ],
                ]); ?>
            <?endif; ?>
    <?endif; ?>

        <?if ($bAjax):?>
            <div class="wrap_nav bottom_nav_wrapper">
        <?endif; ?>

        <?$this->SetViewTarget('more_text_title'); ?>
            <?TSolution\Functions::showBlockHtml([
                'FILE' => '/catalog/element_count_in_section.php',
                'PARAMS' => [
                    'HEADING_COUNT_ELEMENTS' => $arParams['HEADING_COUNT_ELEMENTS'] == 'Y',
                    'COUNT_ELEMENTS' => $arResult['NAV_RESULT']->NavRecordCount,
                ],
            ]); ?>
        <?$this->EndViewTarget(); ?>

        <div class="bottom_nav_wrapper nav-compact">
            <div class="bottom_nav <?= $bMobileScrolledItems ? 'hide-600' : ''; ?>" data-all_count="<?=$arResult['NAV_RESULT']->NavRecordCount; ?>" data-count="<?=$arResult['NAV_RESULT']->NavRecordCount; ?>" data-parent=".catalog-block" data-append=".ajax_load">
                <?if ($arParams['DISPLAY_BOTTOM_PAGER']):?>
                    <?=$arResult['NAV_STRING']; ?>
                <?endif; ?>
            </div>
        </div>

        <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>

    <?if ($bAjax):?>
        </div>
    <?endif; ?>

    <?if (!$bAjax):?>
        </div> <?// .catalog-block?>
    </div> <?// .catalog-items?>
    <?endif; ?>

    <?TSolution\Template\Page::showCountdown($templateData['USE_COUNTDOWN']);?>

    <script>
        typeof input_numeric === "function" && input_numeric(".counter__count");

        <?if ($bUseSelectOffer):?>
            typeof useOfferSelect === 'function' && useOfferSelect();
        <?endif;?>
    </script>

    <!-- items-container -->
<?elseif($arParams['IS_CATALOG_PAGE'] == 'Y' && !$bBigDataMode):?>
    <div class="no_goods catalog_block_view">
        <div class="no_products">
            <div class="wrap_text_empty">
                <?if ($_REQUEST['set_filter']) {?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products_filter.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?} else {?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?}?>
            </div>
        </div>
    </div>
<?endif; ?>

<?if ($bBigDataMode):?>
    <?$signer = new Bitrix\Main\Security\Sign\Signer(); ?>
    <script>
    new JBigData(
        <?=CUtil::PhpToJSObject([
            'siteId' => $component->getSiteId(),
            'count' => $arParams['BIGDATA_COUNT'],
            'bigData' => $arResult['BIG_DATA'],
            'parameters' => $signer->sign(base64_encode(serialize($arResult['ORIGINAL_PARAMETERS'])), 'catalog.section'),
            'template' => $signer->sign($templateName, 'catalog.section'),
        ]); ?>
    );
    </script>
<?endif; ?>
