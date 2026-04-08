<?
use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$this->setFrameMode(true);

$bMaxWidthWrap = (
    !isset($arParams['MAXWIDTH_WRAP']) ||
    (isset($arParams['MAXWIDTH_WRAP']) && $arParams['MAXWIDTH_WRAP'] !== "N")
);

// set catalog params from module
$arParamsCatalog = $arParams;
TSolution\Functions::replaceListParams($arParamsCatalog);
$countElements = count($arResult['ITEMS']);

$bModulePhotoTags = Loader::includeModule('aspro.phototags');
?>
<?if($arResult['ITEMS']):?>
    <div class="lookbooks-list-card swipeignore <?=$templateName?>-template">
        <?if($bMaxWidthWrap):?>
        <div class="maxwidth-theme">
        <?endif;?>
            <div class="fadeslider swiper-nav-offset relative">
                <?if($countElements > 1):?>
                    <?TSolution\Functions::showBlockHtml([
                        'FILE' => 'ui/slider-navigation.php',
                        'PARAMS' => [
                            'CLASSES' => 'slider-nav--shadow fadeslider-nav-btn slider-nav--no-auto-hide hide-600',
                        ]
                    ]);?>
                <?endif;?>
                <div class="lookbooks__wrap mobile-scrolled mobile-offset gap gap--x">
                    <?foreach($arResult['ITEMS'] as $j => $arItem):?>
                        <?
                        // edit/add/delete buttons for edit mode
                        $this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_EDIT'));
                        $this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem['IBLOCK_ID'], 'ELEMENT_DELETE'), array('CONFIRM' => Loc::getMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
                        // use detail link?
                        $bDetailLink = $arParams['SHOW_DETAIL_LINK'] != 'N' && (!strlen($arItem['DETAIL_TEXT']) ? ($arParams['HIDE_LINK_WHEN_NO_DETAIL'] !== 'Y' && $arParams['HIDE_LINK_WHEN_NO_DETAIL'] != 1) : true);

                        // preview image
                        $bImage = (isset($arItem['FIELDS']['PREVIEW_PICTURE']) && $arItem['PREVIEW_PICTURE']['SRC']);
                        $nImageID = ($bImage ? (is_array($arItem['FIELDS']['PREVIEW_PICTURE']) ? $arItem['FIELDS']['PREVIEW_PICTURE']['ID'] : $arItem['FIELDS']['PREVIEW_PICTURE']) : "");
                        $imageSrc = ($bImage ? CFile::getPath($nImageID) : SITE_TEMPLATE_PATH.'/images/svg/noimage_content.svg');
                        
                        $bShowSection = ($arParams['SHOW_SECTION_NAME'] == 'Y' && ($arItem['IBLOCK_SECTION_ID'] && $arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]));
                        ?>
                        <div class="fadeslider__item <?=($j ? '' : 'fadeslider__item--active')?>" <?=($j ? 'style="display: none;"' : '')?>>
                            <div class="lookbooks__item flexbox flexbox--direction-row flexbox--column-t767 gap gap--12" id="<?=$this->GetEditAreaId($arItem['ID']);?>" data-id="<?=$arItem['ID']?>">

                                <div class="lookbooks__image flex-1">
                                    <div class="ui-card cover image-rounded-x">
                                        <div class="ui-card__image height-100">
                                            <?
                                            $a_alt = (is_array($arItem["PREVIEW_PICTURE"]) && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_ALT"] : $arItem["NAME"] ));
                                            $a_title = (is_array($arItem["PREVIEW_PICTURE"]) && strlen($arItem["PREVIEW_PICTURE"]['DESCRIPTION']) ? $arItem["PREVIEW_PICTURE"]['DESCRIPTION'] : ($arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] ? $arItem["IPROPERTY_VALUES"]["ELEMENT_PREVIEW_PICTURE_FILE_TITLE"] : $arItem["NAME"] ));
                                            ?>
                                            <img src="<?=$imageSrc;?>" class="ui-card__img img" alt="<?=$a_alt;?>" title="<?=$a_title;?>" />
                                        </div>

                                        <div class="ui-card__info ui-card__info--absolute flexbox flexbox--direction-row flexbox--justify-between flexbox--align-end gap gap--24 z-index-1 ui-card__info--absolute-off-40">
                                            <div class="flexbox">
                                                <?if($bShowSection):?>
                                                    <div class="font_13 color_light color_light--opacity lineclamp-2 mb mb--8"><?=$arResult['SECTIONS'][$arItem['IBLOCK_SECTION_ID']]['NAME']?></div>
                                                <?endif;?>
                                                <div class="font_24 color_light fw-500 lineclamp-2"><?=$arItem['NAME']?></div>
                                            </div>
                                            
                                            <?= TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH . '/images/svg/arrows.svg#right-hollow', 'arrow white-stroke mb mb--4', ['WIDTH' => 6, 'HEIGHT' => 12]); ?>
                                        </div>

                                        <?if($bDetailLink):?>
                                        <a href="<?=$arItem['DETAIL_PAGE_URL']?>" class="ui-card__link"></a>
                                        <?endif;?>
                                    </div>
                                </div>
                                
                                <?$arGoodsIds = TSolution\Functions::getCrossLinkedItems($arItem, array('LINK_GOODS', 'LINK_GOODS_FILTER'));?>
                                <?if (!empty($arGoodsIds['IBLOCK_ID'])):?>
                                    <?
                                    if (
                                        $bModulePhotoTags &&
                                        !empty($arItem['PROPERTIES']['PHOTOTAGS']['VALUE'])
                                    ) {
                                        $arPhotoTagsProductsIds = \Aspro\Phototags\General::getIblockElementPropertyTagsValueProductsIds(
                                            $arItem['IBLOCK_ID'],
                                            $arItem['PROPERTIES']['PHOTOTAGS']['ID'],
                                            $arItem['ID'],
                                            $arItem['PROPERTIES']['PHOTOTAGS']['~VALUE'],
                                            $bWithOffersProducts = true
                                        );

                                        $arGoodsIds['VALUE'] = array_unique(array_merge($arGoodsIds['VALUE'], $arPhotoTagsProductsIds));
                                    }
                                    ?>
                                    <?if ($arGoodsIds['VALUE']):?>
                                        <div class="lookbooks__goods no-shrinked bordered white-bg outer-rounded-x relative overflow-block">
                                            <div class="lookbooks__goods-list scrollbar scrollbar--overscroll-auto">
                                                <?$GLOBALS[$arParams['FILTER_NAME_GOODS']]['ID'] = $arGoodsIds['VALUE'];?>
                                                <?$APPLICATION->IncludeComponent(
                                                    "bitrix:catalog.section",
                                                    "catalog_list_simple",
                                                    [
                                                        'IBLOCK_ID' => $arGoodsIds['IBLOCK_ID'],
                                                        'PRICE_CODE' => $arParamsCatalog['PRICE_CODE'],
                                                        "CONVERT_CURRENCY" => $arParamsCatalog["CONVERT_CURRENCY"],
                                                        "CURRENCY_ID" => $arParamsCatalog["CURRENCY_ID"],
                                                        'FILTER_NAME' => $arParams['FILTER_NAME_GOODS'],
                                                        'PROPERTIES' => [],
                                                        'SHOW_OLD_PRICE' => 'Y',
                                                        'CACHE_TYPE' => $arParams['CACHE_TYPE'],
                                                        'CACHE_TIME' => $arParams['CACHE_TIME'],
                                                        'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
                                                        'FILTER_ELEMENT' => $arItem['ID'],
                                                        'SHOW_ALL_WO_SECTION' => 'Y',
                                                        'USE_PRICE_COUNT' => 'N', //$arParams['USE_PRICE_COUNT']
                                                        'SHOW_PRICE_COUNT' => '1',
                                                        'SHOW_POPUP_PRICE' => 'Y',
                                                        'COMPATIBLE_MODE' => 'Y',
                                                        'TYPE_SKU' => 'TYPE_2',
                                                        "DISPLAY_COMPARE"	=>	$arParamsCatalog["DISPLAY_COMPARE"],
                                                        "SHOW_FAVORITE" => $arParamsCatalog["SHOW_FAVORITE"],
                                                        "ORDER_VIEW" => $arParamsCatalog["ORDER_VIEW"],
                                                    ],
                                                    false, array("HIDE_ICONS"=>"Y")
                                                );?>
                                                <?unset($GLOBALS[$arParams['FILTER_NAME']]['ID']);?>
                                            </div>
                                        </div>
                                    <?endif;?>
                                <?endif;?>
                                
                            </div>
                        </div>
                    <?endforeach;?>
                </div>
            </div>
        <?if($bMaxWidthWrap):?>
        </div>
        <?endif;?>
    </div>

    <script>
  BX.Aspro.Loader.once({
    appear: ['.lookbooks-list-card'],
    add: {ext: 'fadeslider'},
  });
  </script>
<?endif;?>