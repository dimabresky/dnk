<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Web\Json;

$this->setFrameMode(true);
?>
<?if ($arResult['ITEMS']):?>
    <!-- items-container -->
    <?php
    $templateData['ITEMS'] = true;
    $templateData['HAS_CHARACTERISTICS'] = false;

    $bUseSchema = !(isset($arParams['NO_USE_SHCEMA_ORG']) && $arParams['NO_USE_SHCEMA_ORG'] == 'Y');

    $bShowRating = $arParams['SHOW_RATING'] == 'Y';

    $elementInRow = $arParams['ELEMENT_IN_ROW'];

    $bShowActionIcons = ($arParams['DISPLAY_COMPARE'] == 'Y' || $arParams['SHOW_FAVORITE'] == 'Y' || $arParams['SHOW_ONE_CLICK_BUY']);
    $bOrderViewBasket = $arParams['ORDER_VIEW'];
    $basketURL = (strlen(trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE'])) ? trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE']) : '');

    $bUseSelectOffer = false;

    /* slider classes */
    $sliderClasses = 'swiper slider-solution mobile-offset mobile-offset--right pb--24';
    $sliderWrapperClasses = 'swiper-wrapper mobile-scrolled--items-2';
    $elementSliderClasses = 'swiper-slide swiper-slide--height-auto swiper-slide--width-100';

    $itemClass = ['outer-rounded-x bg-theme-parent-hover border-theme-parent-hover color-theme-parent-all js-popup-block'];
    if ($arParams['BORDERED'] !== 'N') {
        $itemClass[] = 'bordered';
    }

    $itemClass = TSolution\Utils::implodeClasses($itemClass);
    ?>
    <div class="catalog-items <?=$templateName; ?>_template">
        <div class="fast_view_params" data-params="<?=urlencode(serialize($arTransferParams)); ?>"></div>

        <?if ($arResult['SKU_CONFIG']):?>
            <div class="js-sku-config" data-value='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arResult['SKU_CONFIG'], false, true)); ?>'></div>
        <?endif; ?>

        <div class="catalog-slider relative swiper-nav-offset" <?if ($bUseSchema):?>itemscope itemtype="http://schema.org/ItemList"<?endif; ?> >
            <?php
            $sliderOptions = [
                'rewind' => $arParams['SLIDER_LOOP'] === 'Y',
                'autoplay' => false,
                'slidesPerView' => 'auto',
                'freeMode' => true,
                'breakpoints' => [
                    600 => [
                        'freeMode' => false,
                        'slidesPerView' => 1,
                    ],
                ],
            ];
    ?>
            <div class="js_append ajax_load appear-block <?=$sliderClasses; ?>" data-plugin-options='<?=Json::encode($sliderOptions); ?>'>
                <div class="<?=$sliderWrapperClasses; ?>">
                    <?foreach ($arResult['ITEMS'] as $arItem):?>
                        <?php
                $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT'));
                        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE'), ['CONFIRM' => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')]);

                        $item_id = $arItem['ID'];

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
                                if ($arCurrentOffer['PREVIEW_PICTURE']) {
                                    $arCurrentOffer['DETAIL_PICTURE'] = $arCurrentOffer['PREVIEW_PICTURE'];
                                }
                                $arOfferGallery = TSolution\Functions::getSliderForItem([
                                    'TYPE' => 'catalog_block',
                                    'PROP_CODE' => $arParams['OFFER_ADD_PICT_PROP'],
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
                            'PRICE_FONT' => '20 font_14--to-600',
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
                        ]);
                        ?>
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
                                    ]); ?>
                                <?endif; ?>
                            <?endif; ?>
                        <?$itemDiscount = ob_get_clean(); ?>
                        <?$itemSideIcons = TSolution\Product\Common::getSideIcons([
                            'ITEM' => ($arCurrentOffer ?: $arItem),
                            'PARAMS' => $arParams,
                            'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
                            'SHOW_COMPARE' => $arParams['DISPLAY_COMPARE'],
                            'SHOW_ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'],
                            'SIDE_CLASSES' => 'visible-600',
                        ]); ?>

                        <?$isShowBottomBlock = $arCurrentOffer; ?>

                        <div class="deal deal--big <?=$elementSliderClasses; ?> grid-list__item grid-list-border-outer <?=$itemClass; ?> <?= $isShowBottomBlock ? 'has-offers' : ''; ?>"
                            data-hovered="false"
                            id="<?=$arItem['strMainID']; ?>"
                            <?if ($bUseSchema):?>itemprop="itemListElement" itemscope="" itemtype="http://schema.org/Product"<?endif; ?>
                        >
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
                                            <?foreach ($arItem['PRODUCT_PROPERTIES'] as $propID => $propInfo):?>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group fill-animate">
                                                            <?if (
                                                                $arItem['PROPERTIES'][$propID]['PROPERTY_TYPE'] == 'L'
                                                                && $arItem['PROPERTIES'][$propID]['LIST_TYPE'] == 'C'
                                                            ):?>
                                                                <label class="font_14"><span><?=$arItem['PROPERTIES'][$propID]['NAME']; ?></span></label>
                                                                <?foreach ($propInfo['VALUES'] as $valueID => $value):?>
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
                                                                        <?foreach ($propInfo['VALUES'] as $valueID => $value):?>
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

                            <?if ($arItem['SKU']['PROPS']):?>
                                <template class="offers-template-json">
                                    <?=TSolution\SKU::getOfferTreeJson($arItem['SKU']['OFFERS']); ?>
                                </template>
                                <?$bUseSelectOffer = true; ?>
                            <?endif; ?>

                            <?/* description column */?>
                            <div class="deal__col deal__col--description flexbox">
                                <?// element title?>
                                <div class="flexbox">
                                    <div class="catalog-block__info-title lineclamp-3 height-auto-t600 mb mb--4">
                                        <?if ($bUseSchema):?>
                                            <link itemprop="url" href="<?=$arItem['DETAIL_PAGE_URL']; ?>">
                                        <?endif; ?>

                                        <a href="<?=$arItem['DETAIL_PAGE_URL']; ?>" class="switcher-title font_20 font_14--to-600 fw-500 dark_link js-popup-title color-theme-target mb mb--2"><span><?=$elementName; ?></span></a>
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
                                </div>

                                <?if ($arItem['PREVIEW_TEXT']):?>
                                    <div class="catalog-list__info-text font_15 mt mt--20 hide-600 no-margin-p"  <?= $bUseSchema ? 'itemprop="description"' : '' ?>>
                                        <?=$arItem['PREVIEW_TEXT']; ?>
                                    </div>
                                <?endif; ?>
                            </div>
                            <??>

                            <?/* image column */?>
                            <div class="deal__image deal__image--320 item-action-static-fill-svg">
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
                            </div>
                            <??>

                            <?/* purchase column */?>
                            <div class="deal__col deal__col--280 ml ml--auto flexbox">
                                <?if ($bUseSchema):?>
                                    <meta itemprop="name" content="<?=$arItem['NAME']; ?>">
                                    <link itemprop="url" href="<?=$arItem['DETAIL_PAGE_URL']; ?>">
                                <?endif; ?>

                                <div class="flex-1 flexbox"
                                    data-id="<?= $arCurrentOffer ? $arCurrentOffer['ID'] : $arItem['ID']; ?>"
                                    data-item="<?=$dataItem; ?>"
                                    <?if ($bUseSchema):?>itemprop="offers" itemscope itemtype="http://schema.org/Offer"<?endif; ?>
                                >
                                    <?/* element prices */?>
                                    <div class="js-popup-price deal__price" data-price-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arPriceConfig, false, true)); ?>'>
                                        <?$prices->show(); ?>
                                    </div>
                                    <??>

                                    <?/* element offsers */?>
                                    <?if ($arItem['SKU']['PROPS']):?>
                                        <div class="catalog-table__item-wrapper hide-600">
                                            <div class="sku-props sku-props--list"
                                                data-site-id="<?=SITE_ID; ?>"
                                                data-item-id="<?=$arItem['ID']; ?>"
                                                data-iblockid="<?=$arItem['IBLOCK_ID']; ?>"
                                                data-offer-id="<?=$arCurrentOffer['ID']; ?>"
                                                data-offer-iblockid="<?=$arCurrentOffer['IBLOCK_ID']; ?>"
                                                data-offers-id='<?=str_replace('\'', '"', CUtil::PhpToJSObject($GLOBALS[$arParams['FILTER_NAME']]['OFFERS_ID'], false, true)); ?>'
                                            >
                                                <div class="line-block line-block--flex-wrap line-block--gap line-block--gap-0 line-block--flex-100">
                                                    <?=TSolution\SKU\Template::showSkuPropsHtml($arItem['SKU']['PROPS']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?endif; ?>
                                    <??>

                                    <?/* element buttons */?>
                                    <?php
                                    $arBtnConfig = [
                                        'BASKET_URL' => $basketURL,
                                        'BASKET' => $bOrderViewBasket,
                                        'ORDER_BTN' => $bOrderButton,
                                        'BTN_CLASS' => 'btn-wide btn-lg',
                                        'BTN_CLASS_MORE' => 'btn-wide btn-lg bg-theme-target border-theme-target',
                                        'BTN_IN_CART_CLASS' => 'btn-wide btn-lg',
                                        'BTN_CLASS_SUBSCRIBE' => 'btn-wide btn-lg',
                                        'BTN_ORDER_CLASS' => 'btn-wide btn-lg btn-transparent-bg',
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
                                        <div class="catalog-block__info-bottom1 mt mt--16 <?= $arCurrentOffer ? 'catalog-block__info-bottom--with-sku' : ''; ?>">
                                        <div class="flexbox flexbox--direction-row flexbox--align-center gap gap--20">
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

                                            <?if ($bShowActionIcons):?>
                                                <div class="hide-600">
                                                    <?=TSolution\Product\Common::getActionIcons([
                                                        'ITEM' => $arCurrentOffer ?: $arItem,
                                                        'PARAMS' => $arParams,
                                                        'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
                                                        'SHOW_COMPARE' => $arParams['DISPLAY_COMPARE'],
                                                        'SHOW_ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'],
                                                    ]); ?>
                                                </div>
                                            <?endif; ?>
                                            </div>
                                        </div>
                                    <?endif; ?>
                                    <??>
                                </div>


                            </div>
                            <??>
                        </div>
                    <?endforeach; ?>
                </div>

                <?TSolution\Functions::showBlockHtml([
                    'FILE' => 'ui/slider-pagination.php',
                    'PARAMS' => [
                        'CLASSES' => 'swiper-pagination--dark-light swiper-pagination--short',
                    ],
                ]); ?>

                <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>
            </div> <?/* .js_append .ajax_load */?>

            <?TSolution\Functions::showBlockHtml([
                'FILE' => 'ui/slider-navigation.php',
                'PARAMS' => [
                    'CLASSES' => 'slider-nav slider-nav--shadow',
                ],
            ]); ?>

            <?$this->SetViewTarget('more_text_title'); ?>
                <?TSolution\Functions::showBlockHtml([
                    'FILE' => '/catalog/element_count_in_section.php',
                    'PARAMS' => [
                        'HEADING_COUNT_ELEMENTS' => $arParams['HEADING_COUNT_ELEMENTS'] == 'Y',
                        'COUNT_ELEMENTS' => $arResult['NAV_RESULT']->NavRecordCount,
                    ],
                ]); ?>
            <?$this->EndViewTarget(); ?>
        </div>
    </div>

    <?TSolution\Template\Page::showCountdown($templateData['USE_COUNTDOWN']);?>

    <script>
        typeof input_numeric === "function" && input_numeric(".counter__count");

        <?if ($bUseSelectOffer):?>
            typeof useOfferSelect === 'function' && useOfferSelect();
        <?endif;?>
    </script>

    <!-- items-container -->
<?elseif ($arParams['IS_CATALOG_PAGE'] == 'Y'):?>
    <div class="no_goods catalog_block_view">
        <div class="no_products">
            <div class="wrap_text_empty">
                <?if ($_REQUEST['set_filter']):?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products_filter.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?else:?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?endif; ?>
            </div>
        </div>
    </div>
<?endif; ?>
