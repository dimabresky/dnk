<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $mobileColumns
 * @var array $arParams
 * @var string $templateFolder
 */

global $arTheme;

$usePriceInAdditionalColumn = in_array('PRICE', $arParams['COLUMNS_LIST']) && $arParams['PRICE_DISPLAY_MODE'] === 'Y';
$useSumColumn = in_array('SUM', $arParams['COLUMNS_LIST']);
$useActionColumn = in_array('DELETE', $arParams['COLUMNS_LIST']);
$useFavoriteColumn = $arTheme["SHOW_FAVORITE"]["VALUE"] === "Y";


$restoreColSpan = 2 + $usePriceInAdditionalColumn + $useSumColumn + $useActionColumn;

$positionClassMap = array(
    'left' => 'basket-item-label-left',
    'center' => 'basket-item-label-center',
    'right' => 'basket-item-label-right',
    'bottom' => 'basket-item-label-bottom',
    'middle' => 'basket-item-label-middle',
    'top' => 'basket-item-label-top'
);

$discountPositionClass = '';
if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y' && !empty($arParams['DISCOUNT_PERCENT_POSITION'])) {
    foreach (explode('-', $arParams['DISCOUNT_PERCENT_POSITION']) as $pos) {
        $discountPositionClass .= isset($positionClassMap[$pos]) ? ' ' . $positionClassMap[$pos] : '';
    }
}

$labelPositionClass = '';
if (!empty($arParams['LABEL_PROP_POSITION'])) {
    foreach (explode('-', $arParams['LABEL_PROP_POSITION']) as $pos) {
        $labelPositionClass .= isset($positionClassMap[$pos]) ? ' ' . $positionClassMap[$pos] : '';
    }
}
?>
<script id="basket-item-template" type="text/html">
    <tr class="basket-items-list-item-wrapper height-100{{#SHOW_RESTORE}} basket-items-list-item-container-expend{{/SHOW_RESTORE}} {{SERVICES_CLASS}} {{WITH_SERVICES_CLASS}} js-popup-block" id="basket-item-{{ID}}" data-entity="basket-item" data-id="{{ID}}">
        {{#SHOW_RESTORE}}
            <td class="basket-items-list-notification" colspan="<?= $restoreColSpan ?>">
                <div class="basket-items-list-item-notification-inner basket-items-list-item-notification-removed" id="basket-item-height-aligner-{{ID}}">
                    {{#SHOW_LOADING}}
                        <div class="basket-items-list-item-overlay"></div>
                    {{/SHOW_LOADING}}
                    <div class="basket-items-list-item-removed-container">
                        <div>
                            <?= Loc::getMessage('SBB_GOOD_CAP') ?> <strong>{{NAME}}</strong> <?= Loc::getMessage('SBB_BASKET_ITEM_DELETED') ?>.
                        </div>
                        <div class="basket-items-list-item-removed-block">
                            <a href="javascript:void(0)" data-entity="basket-item-restore-button">
                                <?= Loc::getMessage('SBB_BASKET_ITEM_RESTORE') ?>
                            </a>
                            <span class="basket-items-list-item-clear-btn" data-entity="basket-item-close-restore-button"></span>
                        </div>
                    </div>
                </div>
            </td>
        {{/SHOW_RESTORE}}
        {{^SHOW_RESTORE}}
            <?
            if (in_array('PREVIEW_PICTURE', $arParams['COLUMNS_LIST'])) {
            ?>
                <td class="basket-items-list-item-picture" {{#HAS_SERVICES}}rowspan="2" {{/HAS_SERVICES}}>
                    <div class="basket-item-block-image<?= (!isset($mobileColumns['PREVIEW_PICTURE']) ? ' hidden-xs' : '') ?>">
                        {{#USE_FAST_VIEW}}
                            <div class="fast_view" data-event="jqm" data-param-form_id="fast_view" data-param-iblock_id="{{IBLOCK_ID}}" data-param-id="{{PRODUCT_ID}}" data-param-item_href="{{DETAIL_PAGE_URL}}" data-name="fast_view">
                                <?= \TSolution::showIconSvg("side-search", SITE_TEMPLATE_PATH . "/images/svg/catalog/Fancy_side.svg"); ?>
                            </div>
                        {{/USE_FAST_VIEW}}
                        {{#DETAIL_PAGE_URL}}
                            <a href="{{DETAIL_PAGE_URL}}" class="basket-item-image-link">
                        {{/DETAIL_PAGE_URL}}

                        <img class="basket-item-image js-popup-image" alt="{{NAME}}" src="{{{IMAGE_URL}}}{{^IMAGE_URL}}<?= $templateFolder ?>/images/no_photo.png{{/IMAGE_URL}}">

                        {{#SHOW_LABEL}}
                            <div class="basket-item-label-text basket-item-label-big <?= $labelPositionClass ?>">
                                {{#LABEL_VALUES}}
                                    <div{{#HIDE_MOBILE}} class="hidden-xs" {{/HIDE_MOBILE}}>
                                        <span title="{{NAME}}">{{NAME}}</span>
                            </div>
                            {{/LABEL_VALUES}}
                    </div>
                    {{/SHOW_LABEL}}

                    {{#DETAIL_PAGE_URL}}
                        </a>
                    {{/DETAIL_PAGE_URL}}
                    </div>
                </td>
            <?
            }
            ?>
            <td class="basket-items-list-item-descriptions-inner" id="basket-item-height-aligner-{{ID}}">
                <div class="basket-item-block-info">
                    <?
                    if (isset($mobileColumns['DELETE'])) {
                    ?>
                        <span class="basket-item-actions-remove" data-entity="basket-item-delete"></span>
                    <?
                    }
                    ?>
                    {{#SHOW_STICKERS}}
                        <div class="stickers-basket sticker sticker--static mb mb--8 print-hide">
                            {{#STICKERS}}
                                <div class="stickers-basket--item sticker__item {{CLASS}} font_12 ">
                                    {{VALUE}}
                                </div>
                            {{/STICKERS}}
                        </div>
                    {{/SHOW_STICKERS}}
                    <div class="basket-item-info-title">
                        <h2 class="basket-item-info-name">
                            {{#DETAIL_PAGE_URL}}
                                <a href="{{DETAIL_PAGE_URL}}" class="basket-item-info-name-link">
                            {{/DETAIL_PAGE_URL}}

                            <span class="js-popup-title" data-entity="basket-item-name">{{NAME}}</span>

                            {{#DETAIL_PAGE_URL}}
                                </a>
                            {{/DETAIL_PAGE_URL}}
                        </h2>
                        {{#SUB_TITLE}}
                            <div class="secondary-color font_13">{{SUB_TITLE}}</div>
                        {{/SUB_TITLE}}
                        {{#OUT_OF_PRODUCTION}}
                            {{#PRODUCT_ANALOG}}
                                <span class="status-container status-icon nostock font_12 mt mt--8" data-state="nostock">
                                    <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/catalog/item_status_icons.svg#nostock", "status__svg-icon mb mt--1", ['WIDTH' => 13,'HEIGHT' => 12]);?>
                                    <span class="js-replace-status nostock">{{OUT_OF_PRODUCTION}}</span>
                                </span>
                            {{/PRODUCT_ANALOG}}
                        {{/OUT_OF_PRODUCTION}}
                    </div>
                    {{#NOT_AVAILABLE}}
                        <div class="basket-items-list-item-warning-container">
                            <div class="alert alert-warning text-center">
                                <?= Loc::getMessage('SBB_BASKET_ITEM_NOT_AVAILABLE') ?>.
                            </div>
                        </div>
                    {{/NOT_AVAILABLE}}
                    {{#DELAYED}}
                        <div class="basket-items-list-item-warning-container">
                            <div class="alert alert-warning text-center">
                                <?= Loc::getMessage('SBB_BASKET_ITEM_DELAYED') ?>.
                                <a href="javascript:void(0)" data-entity="basket-item-remove-delayed">
                                    <?= Loc::getMessage('SBB_BASKET_ITEM_REMOVE_DELAYED') ?>
                                </a>
                            </div>
                        </div>
                    {{/DELAYED}}
                    {{#WARNINGS.length}}
                        <div class="basket-items-list-item-warning-container">
                            <div class="alert alert-warning alert-dismissable" data-entity="basket-item-warning-node">
                                <span class="close" data-entity="basket-item-warning-close">&times;</span>
                                {{#WARNINGS}}
                                    <div data-entity="basket-item-warning-text">{{{.}}}</div>
                                {{/WARNINGS}}
                            </div>
                        </div>
                    {{/WARNINGS.length}}
                    <div class="basket-item-block-properties">
                        <?
                        if (!empty($arParams['PRODUCT_BLOCKS_ORDER'])) {
                            foreach ($arParams['PRODUCT_BLOCKS_ORDER'] as $blockName) {
                                switch (trim((string)$blockName)) {
                                    case 'props':
                                        if (in_array('PROPS', $arParams['COLUMNS_LIST'])) {
                        ?>
                                            {{#PROPS}}
                                                <div class="basket-item-property">
                                                    <div class="basket-item-property-name">{{{NAME}}}</div>
                                                    <div class="basket-item-property-value" data-entity="basket-item-property-value" data-property-code="{{CODE}}">
                                                        {{{VALUE}}}
                                                    </div>
                                                </div>
                                            {{/PROPS}}
                                        <?
                                        }

                                        break;
                                    case 'sku':
                                        ?>
                                        {{#SKU_BLOCK_LIST}}
                                            {{#IS_IMAGE}}
                                                <div class="basket-item-property basket-item-property-scu-image" data-entity="basket-item-sku-block">
                                                    <div class="basket-item-property-name">{{NAME}}</div>
                                                    <div class="basket-item-property-value">
                                                        <ul class="basket-item-scu-list">
                                                            {{#SKU_VALUES_LIST}}
                                                                <li class="basket-item-scu-item{{#SELECTED}} selected{{/SELECTED}}
                                                                    {{#NOT_AVAILABLE_OFFER}} not-available{{/NOT_AVAILABLE_OFFER}}" title="{{NAME}}" data-entity="basket-item-sku-field" data-initial="{{#SELECTED}}true{{/SELECTED}}{{^SELECTED}}false{{/SELECTED}}" data-value-id="{{VALUE_ID}}" data-sku-name="{{NAME}}" data-property="{{PROP_CODE}}">
                                                                    <span class="basket-item-scu-item-inner" style="background-image: url({{#PICT}}{{PICT}}{{/PICT}}{{^PICT}}<?=SITE_TEMPLATE_PATH;?>/images/noimage.png{{/PICT}});"></span>
                                                                </li>
                                                            {{/SKU_VALUES_LIST}}
                                                        </ul>
                                                    </div>
                                                </div>
                                            {{/IS_IMAGE}}

                                            {{^IS_IMAGE}}
                                                <div class="basket-item-property basket-item-property-scu-text" data-entity="basket-item-sku-block">
                                                    <div class="basket-item-property-name">{{NAME}}</div>
                                                    <div class="basket-item-property-value">
                                                        <ul class="basket-item-scu-list">
                                                            {{#SKU_VALUES_LIST}}
                                                                <li class="basket-item-scu-item{{#SELECTED}} selected{{/SELECTED}}
                                                                    {{#NOT_AVAILABLE_OFFER}} not-available{{/NOT_AVAILABLE_OFFER}}" title="{{NAME}}" data-entity="basket-item-sku-field" data-initial="{{#SELECTED}}true{{/SELECTED}}{{^SELECTED}}false{{/SELECTED}}" data-value-id="{{VALUE_ID}}" data-sku-name="{{NAME}}" data-property="{{PROP_CODE}}">
                                                                    <span class="basket-item-scu-item-inner">{{NAME}}</span>
                                                                </li>
                                                            {{/SKU_VALUES_LIST}}
                                                        </ul>
                                                    </div>
                                                </div>
                                            {{/IS_IMAGE}}
                                        {{/SKU_BLOCK_LIST}}

                                        {{#HAS_SIMILAR_ITEMS}}
                                            <div class="basket-items-list-item-double" data-entity="basket-item-sku-notification">
                                                <div class="alert alert-info alert-dismissable text-center">
                                                    {{#USE_FILTER}}
                                                        <a href="javascript:void(0)" class="basket-items-list-item-double-anchor" data-entity="basket-item-show-similar-link">
                                                    {{/USE_FILTER}}
                                                    <?= Loc::getMessage('SBB_BASKET_ITEM_SIMILAR_P1') ?>{{#USE_FILTER}}</a>{{/USE_FILTER}}
                                                    <?= Loc::getMessage('SBB_BASKET_ITEM_SIMILAR_P2') ?>
                                                    {{SIMILAR_ITEMS_QUANTITY}} {{MEASURE_TEXT}}
                                                    <br>
                                                    <a href="javascript:void(0)" class="basket-items-list-item-double-anchor" data-entity="basket-item-merge-sku-link">
                                                        <?= Loc::getMessage('SBB_BASKET_ITEM_SIMILAR_P3') ?>
                                                        {{TOTAL_SIMILAR_ITEMS_QUANTITY}} {{MEASURE_TEXT}}?
                                                    </a>
                                                </div>
                                            </div>
                                        {{/HAS_SIMILAR_ITEMS}}
                                    <?
                                        break;
                                    case 'columns':
                                    ?>
                                        {{#COLUMN_LIST}}
                                            {{#IS_IMAGE}}
                                                <div class="basket-item-property-custom basket-item-property-custom-photo
                                                    {{#HIDE_MOBILE}}hidden-xs{{/HIDE_MOBILE}}" data-entity="basket-item-property">
                                                    <div class="basket-item-property-custom-name">{{NAME}}</div>
                                                    <div class="basket-item-property-custom-value">
                                                        {{#VALUE}}
                                                            <span>
                                                                <img class="basket-item-custom-block-photo-item" src="{{{IMAGE_SRC}}}" data-image-index="{{INDEX}}" data-column-property-code="{{CODE}}">
                                                            </span>
                                                        {{/VALUE}}
                                                    </div>
                                                </div>
                                            {{/IS_IMAGE}}

                                            {{#IS_TEXT}}
                                                <div class="basket-item-property-custom basket-item-property-custom-text
                                                    {{#HIDE_MOBILE}}hidden-xs{{/HIDE_MOBILE}}" data-entity="basket-item-property">
                                                    <div class="basket-item-property-custom-name">{{NAME}}</div>
                                                    <div class="basket-item-property-custom-value" data-column-property-code="{{CODE}}" data-entity="basket-item-property-column-value">
                                                        {{VALUE}}
                                                    </div>
                                                </div>
                                            {{/IS_TEXT}}

                                            {{#IS_HTML}}
                                                <div class="basket-item-property-custom basket-item-property-custom-text
                                                    {{#HIDE_MOBILE}}hidden-xs{{/HIDE_MOBILE}}" data-entity="basket-item-property">
                                                    <div class="basket-item-property-custom-name">{{NAME}}</div>
                                                    <div class="basket-item-property-custom-value" data-column-property-code="{{CODE}}" data-entity="basket-item-property-column-value">
                                                        {{{VALUE}}}
                                                    </div>
                                                </div>
                                            {{/IS_HTML}}

                                            {{#IS_LINK}}
                                                <div class="basket-item-property-custom basket-item-property-custom-text
                                                    {{#HIDE_MOBILE}}hidden-xs{{/HIDE_MOBILE}}" data-entity="basket-item-property">
                                                    <div class="basket-item-property-custom-name">{{NAME}}</div>
                                                    <div class="basket-item-property-custom-value" data-column-property-code="{{CODE}}" data-entity="basket-item-property-column-value">
                                                        {{#VALUE}}
                                                            {{{LINK}}}{{^IS_LAST}}<br>{{/IS_LAST}}
                                                        {{/VALUE}}
                                                    </div>
                                                </div>
                                            {{/IS_LINK}}
                                        {{/COLUMN_LIST}}
                        <?
                                        break;
                                }
                            }
                        }
                        ?>
                    </div>
                </div>

                {{#SHOW_LOADING}}
                    <div class="basket-items-list-item-overlay"></div>
                {{/SHOW_LOADING}}
            </td>
            <td class="basket-items-list-item-amount-outer">
                <div class="basket-item-amount{{#NOT_AVAILABLE}} disabled{{/NOT_AVAILABLE}}" data-entity="basket-item-quantity-block">
                    <span class="basket-item-btn-minus" data-entity="basket-item-quantity-minus"></span>
                    <div class="basket-item-amount-filed-block">
                        <input type="text" class="basket-item-amount-filed" value="{{QUANTITY}}" {{#NOT_AVAILABLE}} disabled="disabled" {{/NOT_AVAILABLE}} data-value="{{QUANTITY}}" data-entity="basket-item-quantity-field" id="basket-item-quantity-{{ID}}">
                    </div>
                    <span class="basket-item-btn-plus" data-entity="basket-item-quantity-plus"></span>


                    {{#SHOW_LOADING}}
                        <div class="basket-items-list-item-overlay"></div>
                    {{/SHOW_LOADING}}
                </div>
                <?
                if ($usePriceInAdditionalColumn) {
                ?>
                    <div class="basket-items-list-item-price basket-items-list-item-price-for-one<?= (!isset($mobileColumns['PRICE']) ? ' hidden-xs1' : '') ?>">
                        <div class="basket-item-price">
                            <div class="basket-item-price-current">
                                <span class="basket-item-price-current-text" id="basket-item-price-{{ID}}">
                                    {{{PRICE_FORMATED}}}/<div class="basket-item-amount-field-description">
                                        <?
                                        if ($arParams['PRICE_DISPLAY_MODE'] === 'Y') {
                                        ?>
                                            {{#SHOW_MESAURE_RATIO}}{{MEASURE_RATIO}} {{/SHOW_MESAURE_RATIO}}{{MEASURE_TEXT}}
                                        <?
                                        } else {
                                        ?>
                                            {{#SHOW_PRICE_FOR}}
                                                {{MEASURE_RATIO}} {{MEASURE_TEXT}} =
                                                <span id="basket-item-price-{{ID}}">{{{PRICE_FORMATED}}}</span>
                                            {{/SHOW_PRICE_FOR}}
                                            {{^SHOW_PRICE_FOR}}
                                                {{MEASURE_TEXT}}
                                            {{/SHOW_PRICE_FOR}}
                                        <?
                                        }
                                        ?>
                                    </div>
                                </span>
                            </div>

                            {{#SHOW_LOADING}}
                                <div class="basket-items-list-item-overlay"></div>
                            {{/SHOW_LOADING}}
                        </div>
                    </div>
                <?
                }
                ?>
            </td>
            {{#OUT_OF_PRODUCTION}}
                {{#PRODUCT_ANALOG_FILTER}}
                    <td class="basket-items-list-item-analog-filter flex-grow-1 flex-shrink-0 flex-basis-100 visible-600 mt mt--16">
                        <a href="{{URL}}" class="btn btn-xs btn-default btn--solution mt mt--auto" rel="nofollow">{{TITLE}}</a>
                    </td>
                {{/PRODUCT_ANALOG_FILTER}}
            {{/OUT_OF_PRODUCTION}}
            <?
            if ($useSumColumn || $useActionColumn || $useFavoriteColumn) {
            ?>
                <td class="basket-items-list-item-price<?= (!isset($mobileColumns['SUM']) ? ' hidden-xs' : '') ?>">
                    <div class="flexbox height-100">
                        <div class="basket-items-list-item-price-action-wrap flexbox flexbox--row flexbox--align-start gap gap--32">
                            <? if ($useSumColumn) { ?>
                                <div class="basket-item-price flex-1">

                                    <div class="basket-item-price-current">
                                        <div class="basket-item-price-current-value" id="basket-item-sum-price-{{ID}}">
                                            {{{SUM_PRICE_FORMATED}}}
                                        </div>
                                    </div>

                                    {{#SHOW_DISCOUNT_PRICE}}
                                        <div class="basket-item-price-old">
                                            <span class="basket-item-price-old-text" id="basket-item-sum-price-old-{{ID}}">
                                                {{{SUM_FULL_PRICE_FORMATED}}}
                                            </span>
                                        </div>
                                    {{/SHOW_DISCOUNT_PRICE}}

                                    <? if ($arParams['SHOW_DISCOUNT_PERCENT'] === 'Y') { ?>
                                        {{#DISCOUNT_PRICE_PERCENT}}
                                            <div class="stickers-basket sticker sticker--static flexbox--justify-end mt mt--4 print-hide">
                                                <div class="stickers-basket--item sticker__item sticker__item--sale-text font_12">
                                                    -{{DISCOUNT_PRICE_PERCENT_FORMATED}}
                                                </div>
                                            </div>
                                        {{/DISCOUNT_PRICE_PERCENT}}
                                    <? } ?>


                                    {{#SHOW_LOADING}}
                                        <div class="basket-items-list-item-overlay"></div>
                                    {{/SHOW_LOADING}}
                                </div>
                            <? } ?>

                            <? if ($useActionColumn) { ?>
                                <div class="basket-item-block-actions line-block line-block--column line-block--gap line-block--gap-20">
                                    <div class="basket-items-list-item-removes line-block__item">
                                        <span class="basket-item-actions-remove fill-dark-light-block" data-entity="basket-item-delete"><?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH."/images/svg/header_icons.svg#remove", "remove wrapper", ['WIDTH' => 18,'HEIGHT' => 18]);?></span>
                                        {{#SHOW_LOADING}}
                                            <div class="basket-items-list-item-overlay"></div>
                                        {{/SHOW_LOADING}}
                                    </div>
                                    <? if ($useFavoriteColumn) { ?>
                                        <div class="basket-items-list-item-favorite line-block__item" data-item="{{{DATA_ITEM}}}">
                                            {{{DATA_FAVORITE}}}
                                        </div>
                                    <? } ?>
                                </div>
                            <? } ?>
                        </div>

                        {{#OUT_OF_PRODUCTION}}
                            {{#PRODUCT_ANALOG_FILTER}}
                                <a href="{{URL}}" class="btn btn-xs btn-default btn-wide btn--solution hide-600 mt mt--24" rel="nofollow">{{TITLE}}</a>
                            {{/PRODUCT_ANALOG_FILTER}}
                        {{/OUT_OF_PRODUCTION}}
                    </div>
                </td>
            <?
            }
            ?>
        {{/SHOW_RESTORE}}
    </tr>
    {{#HAS_SERVICES}}
        {{^SHOW_RESTORE}}
            <tr class="basket-services-list-item-container " id="basket-services-item-{{ID}}" data-entity="basket-services-item" data-id="{{ID}}">

                <td class="col_with_services" colspan="<?= $restoreColSpan - 1 ?>">
                    <div class="services-buy-wrapper services_in_basket_page services_opacity <? if (in_array('PREVIEW_PICTURE', $arParams['COLUMNS_LIST'])) : ?>w_picture<? endif; ?>" data-product_id="{{PRODUCT_ID}}">
                        {{{LINK_SERVICES_HTML}}}
                    </div>
                </td>

            </tr>
        {{/SHOW_RESTORE}}
    {{/HAS_SERVICES}}
</script>
