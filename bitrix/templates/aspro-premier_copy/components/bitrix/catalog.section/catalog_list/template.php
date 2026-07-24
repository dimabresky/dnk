<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}?>

<?$this->setFrameMode(true); ?>
<??>
<?if($arResult['ITEMS']):?>
    <?php
    $templateData['ITEMS'] = true;

    $bHasBottomPager = $arParams['DISPLAY_BOTTOM_PAGER'] == 'Y' && $arResult['NAV_STRING'];
    $bUseSchema = !(isset($arParams['NO_USE_SHCEMA_ORG']) && $arParams['NO_USE_SHCEMA_ORG'] == 'Y');
    $bAjax = $arParams['AJAX_REQUEST'] == 'Y';
    $bMobileScrolledItems = $arParams['MOBILE_SCROLLED'];

    $bShowActionIcons = ($arParams['DISPLAY_COMPARE'] == 'Y' || $arParams['SHOW_FAVORITE'] == 'Y' || $arParams['SHOW_ONE_CLICK_BUY']);
    $bShowRating = $arParams['SHOW_RATING'] == 'Y';

    $bOrderViewBasket = $arParams['ORDER_VIEW'];
    $basketURL = (strlen(trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE'])) ? trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE']) : '');

    $gridClass .= ' grid-list--items grid-list--items-1';

    if($bMobileScrolledItems) {
        $gridClass .= ' mobile-scrolled mobile-scrolled--items-2 mobile-offset';
    } else {
        $gridClass .= ' grid-list--compact';
    }

    $gridClass .= ' gap';
    $gridClass .= ' grid-list--no-gap';

    $itemClass = ' bg-theme-parent-hover color-theme-parent-all js-popup-block';

    if ($arParams['TEXT_CENTER']) {
        $itemClass .= ' catalog-block__item--centered';
    }

    $bUseSelectOffer = false;
    $bShowSKUDescription = $arParams['SHOW_SKU_DESCRIPTION'] === 'Y';

    $bBottomButtons = (isset($arParams['POSITION_BTNS']) && $arParams['POSITION_BTNS'] == '4');
    ?>
    <?$templateData['HAS_CHARACTERISTICS'] = false; ?>
    <?if(!$bAjax):?>
    <div class="catalog-items <?=$templateName; ?>_template <?=$arParams['IS_COMPACT_SLIDER'] ? 'compact-catalog-slider' : ''; ?>">
        <!-- noindex -->
		<template class="props-template">
			<?TSolution\Functions::showBlockHtml([
				'FILE' => 'catalog/props/list.php',
				'PARAMS' => [
                    'CLASS' => 'js-prop',
					'FONT_CLASSES' => 'font_13',
                    'WRAPPER_CLASSES' => '',
                    'GAP_SIZE' => '6',
				],
			]);?>
		</template>
		<!-- /noindex -->
        <div class="fast_view_params" data-params="<?=urlencode(serialize($arTransferParams)); ?>"></div>
        <?if ($arResult['SKU_CONFIG']):?><div class="js-sku-config" data-value='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arResult['SKU_CONFIG'], false, true)); ?>'></div><?endif; ?>
        <div class="catalog-list" <?if ($bUseSchema):?>itemscope itemtype="http://schema.org/ItemList" itemprop="mainEntity"<?endif; ?> >
            <?if ($bUseSchema):?>
            <meta itemprop="name" content="<?=htmlspecialcharsbx( GetMessage('CATALOG_SECTION_LIST') . ($arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] ?: $arResult['NAME']))?>" />
            <meta itemprop="numberOfItems" content="<?=htmlspecialcharsbx($arResult['NAV_RESULT']->NavRecordCount)?>" />
            <?endif; ?>
            <div class="js_append ajax_load list grid-list <?=$gridClass; ?>">
    <?endif; ?>
        <?foreach($arResult['ITEMS'] as $arItem) {?>
            <?$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_EDIT'));
            $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams['IBLOCK_ID'], 'ELEMENT_DELETE'), ['CONFIRM' => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')]);

            $item_id = $arItem['ID'];

            if (isset($arParams['ID_FOR_TABS']) && $arParams['ID_FOR_TABS'] == 'Y') {
                $arItem['strMainID'] = $this->GetEditAreaId($arItem['ID']).'_'.$arParams['FILTER_HIT_PROP'];
            } else {
                $arItem['strMainID'] = $this->GetEditAreaId($arItem['ID']);
            }

            $elementName = TSolution\Product\Common::getElementName($arItem);

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

            <?
            $itemSideIcons = TSolution\Product\Common::getSideIcons([
                'ITEM' => ($arCurrentOffer ?: $arItem),
                'PARAMS' => $arParams,
                'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
                'SHOW_COMPARE' => $arParams['DISPLAY_COMPARE'],
                'SHOW_ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'],
                'ONE_CLICK_ICON_CLASSES' => $arCurrentOffer ? 'hide-600' : '',
                'SIDE_CLASSES' => 'visible-600',
            ]); ?>

            <div class="catalog-list__wrapper grid-list__item grid-list-border-outer">
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
                                                <?if(
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

                <div class="catalog-list__item <?=$itemClass; ?>" id="<?=$arItem['strMainID']; ?>">
                    <?if ($arItem['SKU']['PROPS']):?>
                        <template class="offers-template-json">
                            <?=TSolution\SKU::getOfferTreeJson($arItem['SKU']['OFFERS']); ?>
                        </template>
                        <?$bUseSelectOffer = true; ?>
                    <?endif; ?>
                    <div class="catalog-list__inner flexbox flexbox--direction-row height-100" <?if ($bUseSchema):?>itemprop="itemListElement" itemscope="" itemtype="http://schema.org/Product"<?endif; ?>>
                        <?$arImgConfig = [
                            'TYPE' => 'catalog_list',
                            'ADDITIONAL_IMG_CLASS' => 'js-replace-img',
                        ]; ?>
                        <div class="js-config-img" data-img-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arImgConfig, false, true)); ?>'></div>
                        <?if ($bUseSchema):?>
                            <?$ratingValue = $arItem['PROPERTIES']['EXTENDED_REVIEWS_RAITING']['VALUE'];
                            $reviewCount = intval($arItem['PROPERTIES']['EXTENDED_REVIEWS_COUNT']['VALUE']) ;?>
                            <?TSolution\Scheme\Common::showAggregateRating($ratingValue, $reviewCount);?>
                            <meta itemprop="description" content="<?=htmlspecialcharsbx(strip_tags($arItem['PREVIEW_TEXT'] ?: $arItem['NAME'])); ?>" />
                            <meta itemprop="category" content="<?=htmlspecialcharsbx($arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'] ?: $arResult['NAME'])?>" />
                        <?endif; ?>
                        <?=TSolution\Product\Image::showImage(
                            array_merge(
                                [
                                    'ITEM' => $arItem,
                                    'PARAMS' => $arParams,
                                    'STICKY' => true,
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
                            class="catalog-list__info flex-1 flexbox flexbox--direction-row"
                            data-id="<?= $arCurrentOffer ? $arCurrentOffer['ID'] : $arItem['ID']; ?>"
                            data-item="<?=$dataItem; ?>"
                            <?if ($bUseSchema):?>itemprop="offers" itemscope itemtype="http://schema.org/Offer"<?endif; ?>
                        >
                            <div class="catalog-list__info-top">
                                <div class="catalog-list__info-inner grid-list grid-list--items gap gap--24">
                                    <div class="catalog-list__info-topic">
                                        <?// element title?>
                                        <div class="catalog-list__info-title lineclamp-3 height-auto-t600 font_16 fw-500 font_14--to-600">
                                            <?if ($bUseSchema):?>
                                                <link itemprop="url" href="<?=Tsolution\Utils::getAbsolutePath($arItem['DETAIL_PAGE_URL']); ?>">
                                            <?endif; ?>
                                            <a href="<?=$arItem['DETAIL_PAGE_URL']; ?>" class="dark_link switcher-title js-popup-title color-theme-target"><span><?=$elementName; ?></span></a>

                                            <?TSolution\Product\Common::showSubTitle($arItem, 'font_13 mt mt--2');?>
                                        </div>
                                        <?if ($bShowRating || strlen($status) || strlen($article)):?>

                                            <div class="catalog-list__info-tech">
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

                                    <?if ($arItem['SKU']['PROPS']):?>
                                        <div class="catalog-table__item-wrapper hide-600">
                                            <div
                                            class="sku-props sku-props--list"
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

                                    <?$bSKUPreviewDescription = $bShowSKUDescription && strlen($arCurrentOffer['PREVIEW_TEXT']); ?>
                                    <?$showPreviewText = boolval(strlen($arItem['PREVIEW_TEXT']) || $bSKUPreviewDescription); ?>
                                    <div class="catalog-list__info-text-props compact-hidden-t600">
                                        <?if ($showPreviewText):?>
                                            <?if ($showPreviewText):?>
                                                <div
                                                class="catalog-list__info-text font_13 compact-hidden-t600 js-preview-description"
                                                itemprop="description"
                                                >
                                                    <?if($bSKUPreviewDescription):?>
                                                        <?=$arCurrentOffer['PREVIEW_TEXT']; ?>
                                                    <?else:?>
                                                        <?=$arItem['PREVIEW_TEXT']; ?>
                                                    <?endif; ?>
                                                </div>
                                            <?endif; ?>
                                        <?endif; ?>
                                        <?$templateData['HAS_CHARACTERISTICS'] = true; ?>
                                        <?TSolution\Functions::showBlockHtml([
                                            'FILE' => '/catalog/props_in_section.php',
                                            'ITEM' => $arItem,
                                            'PARAMS' => [
                                                'WRAPPER_CLASSES' => 'mt mt--16',
                                                'FONT_CLASSES' => 'font_13',
                                                'TEXT_CLASSES' => 'secondary-color',
                                                'GAP_SIZE' => '6',
                                                'SHOW_HINTS' => $arParams['SHOW_HINTS'],
                                            ],
                                        ]);?>
                                    </div>
                                </div>
                            </div>
                            <div class="catalog-list__info-bottom">
                                <div class="js-popup-price" data-price-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arPriceConfig, false, true)); ?>'>
                                    <?php
                                    $prices->show();
                                    ?>
                                </div>
                                <?// element btns?>
                                <?php
                                $arBtnConfig = [
                                    'BASKET_URL' => $basketURL,
                                    'BASKET' => $bOrderViewBasket,
                                    'ORDER_BTN' => $bOrderButton,
                                    'BTN_CLASS' => 'btn-wide btn-lg',
                                    'QUESTION_BTN' => $arItem['PROPERTIES']['FORM_QUESTION']['VALUE'] == 'Y',
                                    'ONE_CLICK_BUY' => $arParams['SHOW_ONE_CLICK_BUY'] === 'Y',
                                    'BTN_CLASS_MORE' => 'btn-wide btn-lg',
                                    'BTN_IN_CART_CLASS' => 'btn-wide btn-lg',
                                    'BTN_ORDER_CLASS' => 'btn-wide btn-lg btn-transparent-bg',
                                    'BTN_CLASS_SUBSCRIBE' => 'btn-wide btn-lg btn-transparent',
                                    'SHOW_COUNTER' => false,
                                    'DISPLAY_COMPARE' => $arParams['DISPLAY_COMPARE'],
                                    'CATALOG_IBLOCK_ID' => $arItem['IBLOCK_ID'],
                                    'ITEM_ID' => $arItem['ID'],
                                ];
                                if ($arItem['SHOW_MORE']) {
                                    $arBtnConfig['SHOW_MORE'] = true;
                                }

                                $arBasketConfig = TSolution\Product\Basket::getOptions(array_merge(
                                    $arBtnConfig,
                                    [
                                        'ITEM' => ($arCurrentOffer ? $arCurrentOffer : $arItem),
                                        'IS_OFFER' => (bool) $arCurrentOffer,
                                        'PARAMS' => $arParams,
                                        'TOTAL_COUNT' => $totalCount,
                                        'HAS_PRICE' => $prices->isGreaterThanZero(),
                                        'EMPTY_PRICE' => $prices->isEmpty(),
                                    ]
                                ));
                                ?>
                                <?// if ($arBasketConfig['HTML']):?>
                                <?if ($bShowActionIcons || $arCurrentOffer || $arBasketConfig['HTML']):?>
                                    <div class="catalog-list__info-bottom-btns">
                                        <div class="line-block line-block--gap line-block--gap-20 line-block--align-normal flexbox--wrap">
                                            <div class="line-block__item js-btn-state-wrapper  <?= $arCurrentOffer ? 'hide-600' : ''; ?> <?= !$arBasketConfig['HTML'] ? ' hidden' : ''; ?>">
                                                <div class="js-replace-btns js-config-btns" data-btn-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arBtnConfig, false, true)); ?>'>
                                                    <?=$arBasketConfig['HTML']; ?>
                                                </div>
                                            </div>
                                            <?if ($arCurrentOffer):?>
                                                <div class="visible-600 line-block__item flex-1">
                                                    <?=TSolution\Product\Basket::getMoreButton(['ITEM' => $arCurrentOffer]);?>
                                                </div>
                                            <?endif; ?>
                                            <?if ($bShowActionIcons):?>
                                                <div class="hide-600">
                                                    <?=TSolution\Product\Common::getActionIcons([
                                                        'ITEM' => $arCurrentOffer ?: $arItem,
                                                        'PARAMS' => $arParams,
                                                        'SHOW_FAVORITE' => $arParams['SHOW_FAVORITE'],
                                                        'SHOW_COMPARE' => $arParams['DISPLAY_COMPARE'],
                                                    ]);?>
                                                </div>
                                            <?endif; ?>
                                        </div>
                                    </div>
                                <?endif; ?>
                                <?// hint?>
                                <?if ($arItem['INCLUDE_TEXT']):?>
                                    <div class="block-with-icon block-with-icon--mt-14">
                                        <?=TSolution::showIconSvg('icon block-with-icon__icon', SITE_TEMPLATE_PATH.'/images/svg/catalog/info_big.svg', '', '', true, false); ?>
                                        <div class="block-with-icon__text color_666 font_13">
                                            <?=$arItem['INCLUDE_TEXT']; ?>
                                        </div>
                                    </div>
                                <?endif; ?>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        <?}?>

        <?if ($bHasBottomPager && $bMobileScrolledItems):?>
            <?if($bAjax):?>
                <div class="wrap_nav bottom_nav_wrapper">
            <?endif; ?>

                <?$bHasNav = (strpos($arResult['NAV_STRING'], 'more_text_ajax') !== false); ?>
                <div class="bottom_nav mobile_slider <?= $bHasNav ? '' : ' hidden-nav'; ?>" data-parent=".catalog-list" data-append=".grid-list" <?= $bAjax ? "style='display: none; '" : ''; ?>>
                    <?=$arResult['NAV_STRING']; ?>
                </div>

            <?if($bAjax):?>
                </div>
            <?endif; ?>
        <?endif; ?>

    <?if(!$bAjax):?>
            </div> <?// .js_append ajax_load block grid-list?>
    <?endif; ?>

        <?if($bAjax):?>
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
            <div class="bottom_nav <?= $bMobileScrolledItems ? 'hide-600' : ''; ?>" <?= $arParams['AJAX_REQUEST'] == 'Y' ? "style='display: none; '" : ''; ?> data-all_count="<?=$arResult['NAV_RESULT']->NavRecordCount; ?>" data-count="<?=$arResult['NAV_RESULT']->NavRecordCount; ?>" data-parent=".catalog-list" data-append=".ajax_load">
                <?if($arParams['DISPLAY_BOTTOM_PAGER']):?>
                    <?=$arResult['NAV_STRING']; ?>
                <?endif; ?>
            </div>
        </div>

        <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>

    <?if($bAjax):?>
        </div>
    <?endif; ?>

    <?if(!$bAjax):?>
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
<?elseif($arParams['IS_CATALOG_PAGE'] == 'Y'):?>
    <div class="no_goods catalog_block_view">
        <div class="no_products">
            <div class="wrap_text_empty">
                <?if($_REQUEST['set_filter']) {?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products_filter.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?} else {?>
                    <?$APPLICATION->IncludeFile(SITE_DIR.'include/section_no_products.php', [], ['MODE' => 'html',  'NAME' => GetMessage('EMPTY_CATALOG_DESCR')]); ?>
                <?}?>
            </div>
        </div>
    </div>
<?endif; ?>
