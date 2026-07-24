<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?$this->setFrameMode(true);?>
<?use \Bitrix\Main\Web\Json;
global $arTheme;
?>
<?if($arResult["ITEMS"]):?>
    <?
    $currencyList = '';
    if (!empty($arResult['CURRENCIES'])) {
        $templateLibrary[] = 'currency';
        $currencyList = CUtil::PhpToJSObject($arResult['CURRENCIES'], false, true, true);
    }
    $templateData = array(
        'TEMPLATE_LIBRARY' => $templateLibrary,
        'CURRENCIES' => $currencyList
    );
    unset($currencyList, $templateLibrary);

    $bShowCompare = $arParams['DISPLAY_COMPARE'] == 'Y';
    $bShowFavorit = $arParams['SHOW_FAVORITE'] == 'Y';
    $bShowRating = $arParams['SHOW_RATING'] == 'Y';
    $bOrderViewBasket = $arParams['ORDER_VIEW'];
    $basketURL = (strlen(trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE'])) ? trim($arTheme['ORDER_VIEW']['DEPENDENT_PARAMS']['URL_BASKET_SECTION']['VALUE']) : '');
    ?>
    <div class="content_wrapper_block catalog-list-simple <?=$templateName;?>">
        <?
        foreach($arResult["ITEMS"] as $key => $arItem){?>
            <?
            $this->AddEditAction($arItem['ID'].'_list_simple', $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_EDIT"));
            $this->AddDeleteAction($arItem['ID'].'_list_simple', $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arParams["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BCS_ELEMENT_DELETE_CONFIRM')));

            $elementName = TSolution\Product\Common::getElementName($arItem);

            $arParams["SHOW_GALLERY"] = "N";
            $arParams["SHOW_FAST_VIEW"] = "N";
            $arParams["SHOW_STICKERS"] = "N";

            $bOrderButton = ($arItem["PROPERTIES"]["FORM_ORDER"]["VALUE_XML_ID"] == "YES");
            $dataItem = TSolution::getDataItem($arItem);

            $totalCount = TSolution\Product\Quantity::getTotalCount([
                'ITEM' => $arItem,
                'PARAMS' => $arParams
            ]);
            ?>

            <div class="catalog-list-simple__item color-theme-parent-all p p--24 js-popup-block border-bottom" id="<?=$this->GetEditAreaId($arItem['ID']);?>_list_simple">
                <div class="catalog-list-simple__inner flexbox flexbox--direction-row gap gap--20">
                    <div class="catalog-list-simple__image-wrapper">
                        <?$arImgConfig = [
                            'TYPE' => 'catalog_block',
                            'ADDITIONAL_IMG_CLASS' => 'js-replace-img',
                            'ADDITIONAL_WRAPPER_CLASS' => 'catalog-list-simple__image',
                        ];?>
                        <div class="js-config-img" data-img-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arImgConfig, false, true))?>'></div>

                        <?=TSolution\Product\Image::showImage(
                            array_merge(
                                [
                                    'ITEM' => $arItem,
                                    'PARAMS' => $arParams,
                                    // 'CONTENT_TOP' => $itemDiscount,
                                ],
                                $arImgConfig
                            )
                        )?>
                    </div>

                    <div class="catalog-list-simple__info flexbox gap gap--20 flexbox--justify-between" data-id="<?=($arCurrentOffer ? $arCurrentOffer['ID'] : $arItem['ID'])?>"
                        data-item="<?=$dataItem;?>">
                        <div class="catalog-list-simple__info-top">
                            <div class="catalog-list-simple__title mb mb--6">
                                <a href="<?=$arItem["DETAIL_PAGE_URL"]?>" class="dark_link font_14 switcher-title js-popup-title color-theme-target"><span><?=$elementName;?></span></a>
                            </div>

                            <?// element price?>
                            <?$arPriceConfig = [
                                'PRICE_CODE' => $arParams['PRICE_CODE'],
                                'PRICE_FONT' => '18',
                                'PARAMS' => [
                                    'SHOW_DISCOUNT_PERCENT' => 'N', // hide discount in js_item_detail.php
                                ],
                            ];
                            ?>
                            <div class="js-popup-price" data-price-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arPriceConfig, false, true))?>'>
                                <?php
                                $prices = (new TSolution\Product\Prices(
                                    ($arCurrentOffer ? $arCurrentOffer : $arItem),
                                    array_merge($arParams, ['SHOW_POPUP_PRICE' => 'Y']),
                                    [
                                        'STICKY' => ($arParams['POPUP_PRICE_STICKY'] ?? 'N') === 'Y',
                                    ]
                                ))->show();
                                ?>
                            </div>
                        </div>

                        <?// element btns?>
                        <?$arBtnConfig = [
                            'BASKET_URL' => $basketURL,
                            'BASKET' => $bOrderViewBasket,
                            'ORDER_BTN' => $bOrderButton,
                            'BTN_CLASS' => 'btn-sm',
                            'BTN_CLASS_MORE' => 'btn-sm bg-theme-target border-theme-target',
                            'BTN_IN_CART_CLASS' => 'btn-sm',
                            'BTN_CLASS_SUBSCRIBE' => 'btn-sm',
                            'BTN_ORDER_CLASS' => 'btn-sm btn-transparent-border',
                            'ONE_CLICK_BUY' => false,
                            'SHOW_COUNTER' => false,
                            'CATALOG_IBLOCK_ID' => $arItem['IBLOCK_ID'],
                            'ITEM_ID' => $arItem['ID'],
                        ];
                        if ($arItem['SHOW_MORE']) {
                            $arBtnConfig['SHOW_MORE'] = true;
                            $arItem['CAN_BUY'] = 'N';
                            $totalCount = 0;
                        }?>
                        <?
                        $arBasketConfig = TSolution\Product\Basket::getOptions(array_merge(
                            $arBtnConfig,
                            [
                                'ITEM' => ($arCurrentOffer ?: $arItem),
                                'IS_OFFER' => (boolean)$arCurrentOffer,
                                'PARAMS' => $arParams,
                                'TOTAL_COUNT' => $totalCount,
                                'HAS_PRICE' => $prices->isGreaterThanZero(),
                                'EMPTY_PRICE' => $prices->isEmpty(),
                            ]
                        ));?>
                        <?if (
                            ($bShowCompare
                            || $bShowFavorit
                            || ($arCurrentOffer
                                || (!$arCurrentOffer && $arBasketConfig['HTML'])
                            )
                            || $arItem['SKU']['PROPS'])
                            && !isset($arParams['HIDE_BUY_BUTTON'])
                        ):?>
                            <div class="catalog-list-simple__info-bottom">
                                <div class="line-block line-block--8 line-block--8-vertical flexbox--wrap flexbox--justify-between">
                                <?if (
                                        (
                                            $bShowCompare ||
                                            $bShowFavorit
                                        ) &&
                                        (
                                            $arBasketConfig['ACTION'] !== 'MORE'
                                        )
                                    ):?>
                                        <div class="line-block__item js-replace-icons line-block line-block--gap line-block--gap-16">
                                            <?if ($bShowCompare):?>
                                                <?=\TSolution\Product\Common::getActionIcon([
                                                    'ITEM' => ($arCurrentOffer ?: $arItem),
                                                    'PARAMS' => $arParams,
                                                    'WRAPPER_ICON' => 'compare_white',
							                        'ACTIVE_ICON' => 'compare_active',
                                                    'TYPE' => 'compare',
                                                    'CLASS' => 'sm',
                                                    'SVG_SIZE' => ['WIDTH' => 20,'HEIGHT' => 20],
                                                    'ORIENT' => 'horizontal',
                                                ])?>
                                            <?endif;?>

                                            <?if ($bShowFavorit):?>
                                                <?=\TSolution\Product\Common::getActionIcon([
                                                    'ITEM' => ($arCurrentOffer ?: $arItem),
                                                    'PARAMS' => $arParams,
                                                    'WRAPPER_ICON' => 'favorite_white',
							                        'ACTIVE_ICON' => 'favorite_active',
                                                    'CLASS' => 'sm',
                                                    'SVG_SIZE' => ['WIDTH' => 20,'HEIGHT' => 20],
                                                    'ORIENT' => 'horizontal',
                                                ])?>
                                            <?endif;?>
                                        </div>
                                    <?endif;?>

                                    <div class="line-block__item js-btn-state-wrapper ml ml--auto <?=(!$arBasketConfig['HTML'] ? ' hidden' : '');?>">
                                        <div class="js-replace-btns js-config-btns" data-btn-config='<?=str_replace('\'', '"', CUtil::PhpToJSObject($arBtnConfig, false, true))?>'>
                                            <?=$arBasketConfig['HTML']?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?endif;?>
                    </div>
                </div>
            </div>
        <?}?>
    </div>

    <?TSolution\Vendor\Include\Component::bonusesCalculate(params: ['ITEMS' => $arResult['ITEMS']]);?>
<?endif;?>
