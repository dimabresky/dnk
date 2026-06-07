<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

global $pathForAjax;

$application = Bitrix\Main\Application::getInstance();
$context = $application->getContext();
$request = $context->getRequest();
$session = $application->getSession();

$arAvailableSort = [
    [
        'PROP' => 'UF_ASPRO_COM_RATING',
        'ORDER' => 'SORT_DESC',
        'MESSAGE' => 'RATING_DESC',
    ],
    [
        'PROP' => 'UF_ASPRO_COM_RATING',
        'ORDER' => 'SORT_ASC',
        'MESSAGE' => 'RATING_ASC',
    ],
    [
        'PROP' => 'DateFormated',
        'ORDER' => 'SORT_ASC',
        'MESSAGE' => 'DATE_ASC',
    ],
    [
        'PROP' => 'DateFormated',
        'ORDER' => 'SORT_DESC',
        'MESSAGE' => 'DATE_DESC',
    ],
    [
        'PROP' => 'UF_ASPRO_COM_LIKE',
        'ORDER' => 'SORT_DESC',
        'MESSAGE' => 'LIKE_DESC',
    ],
];
$sort = $session['REVIEW_SORT_PROP'] ? $session['REVIEW_SORT_PROP'] : 'UF_ASPRO_COM_RATING';
$sort_order = $session['REVIEW_SORT_ORDER'] ? $session['REVIEW_SORT_ORDER'] : 'SORT_DESC';

foreach ($arAvailableSort as $value) {
    if ($value['PROP'] == $sort && $value['ORDER'] == $sort_order) {
        $currentSort = $value;
    }
}

$arFilterButtons = $arParams['REVIEW_FILTER_BUTTONS'] ?? [];
$arAvailableFilter = [];
$arSessionFilter = $session['filter'];

if (in_array('RATING', $arFilterButtons)) {
    $disabled = count((array)$arResult['AVAILABLE_RATING']) < 2 && empty($session['REVIEW_FILTER']['RATING']);
    $arAvailableFilter['RATING'] = [
        'NAME' => GetMessage('T_FILTER_RATING'),
        'TYPE' => 'LIST',
        'INPUT_TYPE' => 'checkbox',
        'SUBTYPE' => 'rating',
        'VALUES' => [
            [
                'TITLE' => GetMessage('T_FILTER_RATING_1'),
                'VALUE' => '1',
                'DISABLED' => $disabled || !in_array('1', $arResult['AVAILABLE_RATING']),
            ],
            [
                'TITLE' => GetMessage('T_FILTER_RATING_2'),
                'VALUE' => '2',
                'DISABLED' => $disabled || !in_array('2', $arResult['AVAILABLE_RATING']),
            ],
            [
                'TITLE' => GetMessage('T_FILTER_RATING_3'),
                'VALUE' => '3',
                'DISABLED' => $disabled || !in_array('3', $arResult['AVAILABLE_RATING']),
            ],
            [
                'TITLE' => GetMessage('T_FILTER_RATING_4'),
                'VALUE' => '4',
                'DISABLED' => $disabled || !in_array('4', $arResult['AVAILABLE_RATING']),
            ],
            [
                'TITLE' => GetMessage('T_FILTER_RATING_5'),
                'VALUE' => '5',
                'DISABLED' => $disabled || !in_array('5', $arResult['AVAILABLE_RATING']),
            ],
        ],
        'CURRENT_VALUE' => !empty($session['REVIEW_FILTER']['RATING']) ? (array) $session['REVIEW_FILTER']['RATING'] : [],
    ];
}
if (in_array('PHOTO', $arFilterButtons)) {
    $arAvailableFilter['PHOTO'] = [
        'NAME' => GetMessage('T_FILTER_PHOTO'),
        'TYPE' => 'CHECKBOX',
        'DISABLED' => !$arResult['AVAILABLE_PHOTO'],
        'CURRENT_VALUE' => isset($session['REVIEW_FILTER']) && isset($session['REVIEW_FILTER']['PHOTO']) ? htmlspecialcharsbx($session['REVIEW_FILTER']['PHOTO']) : 'N',
    ];
}
if (in_array('TEXT', $arFilterButtons)) {
    $arAvailableFilter['TEXT'] = [
        'NAME' => GetMessage('T_FILTER_TEXT'),
        'TYPE' => 'CHECKBOX',
        'DISABLED' => !$arResult['AVAILABLE_TEXT'],
        'CURRENT_VALUE' => isset($session['REVIEW_FILTER']) && isset($session['REVIEW_FILTER']['TEXT']) ? htmlspecialcharsbx($session['REVIEW_FILTER']['TEXT']) : 'N',
    ];
}
$bShowFilter = count($arAvailableFilter);
?>
<div class="reviews_sort flexbox gap gap--36<?=empty($arResult['Comments']) ? ' hidden' : ''; ?>">
    <?php
    if (!$bAjaxPost && $arParams['OFFER_ID'] && in_array('OFFER', $arParams['REVIEW_FILTER_BUTTONS'])) {
        $selectedOfferCount = $arResult['REVIEWS_COUNT_PER_OID'][$arParams['OFFER_ID']] ?? 0;
        TSolution\Functions::showBlockHtml([
            'FILE' => 'comments/sku_comments_switch.php',
            'DATA_COUNT' => Json::encode($arResult['REVIEWS_COUNT_PER_OID']),
            'OFFER_COUNT' => $selectedOfferCount,
            'OID' => htmlspecialcharsbx($request['OFFER_ID']),
            'LANG' => [
                'T_OFFERS_FILTER_ALL' => Loc::getMessage('T_OFFERS_FILTER_ALL'),
                'T_OFFERS_FILTER_CURRENT_OFFER' => Loc::getMessage('T_OFFERS_FILTER_CURRENT_OFFER'),
                'T_SELECT_CURRENT_OFFER' => Loc::getMessage('T_SELECT_CURRENT_OFFER'),
            ],
            'PARAMS' => [
                'SHOW_ALL_OFFERS' => $request['reviewsVariantMode'] !== 'offer' || !$selectedOfferCount,
                'HIDE_BLOCK' => !$arResult['REVIEWS_COUNT'],
            ],
        ]);
    }
    ?>
    <div class="filter-panel sort_header border-bottom pb pb--16">
        <?=$topImages; ?>

        <!--noindex-->
            <div class="filter-panel__sort">
                <form id="review-sort-form" class="review-sort-form filter-panel__sort-form" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="reviews_sort" value="Y" />
                    <input type="hidden" name="reviews_filter" value="Y" />
                    <input type="hidden" name="ajax_url" value="<?=$pathForAjax.'/ajax.php'; ?>">
                    <?if ($request['AVAILABLE_OFFERS']):?>
                        <input type="hidden" name="filter[AVAILABLE_OFFERS]" value="<?=htmlspecialcharsbx($request['AVAILABLE_OFFERS']); ?>">
                    <?endif; ?>

                    <div class="filter-panel__sort-form__inner flexbox flexbox--row flexbox--wrap gap gap--8">
                        <div class="filter-panel__sort-form__item dropdown-select dropdown-select--with-dropdown">
                            <button type="button" class="btn--no-btn-appearance dropdown-select__title font_14 fill-dark-light bordered rounded-x">
                                <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#sort', 'mr mr--12', ['WIDTH' => 12, 'HEIGHT' => 12]); ?>
                                <span><?=Loc::getMessage($sort_order && $sort && $currentSort ? $currentSort['MESSAGE'] : 'NOTHING_SELECTED');?></span>
                                <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/arrows.svg#down', 'dropdown-select__icon-down', ['WIDTH' => 5, 'HEIGHT' => 3]); ?>
                            </button>

                            <div class="dropdown-select__list dropdown-menu-wrapper dropdown-menu-wrapper--woffset" role="menu">
                                <div class="dropdown-menu-inner rounded-x">
                                    <?foreach ($arAvailableSort as $prop => $arVals):?>
                                        <?$isCurrent = $sort == $arVals['PROP'] && $sort_order == $arVals['ORDER']; ?>
                                        <label class="dropdown-select__list-item font_15 radio">
                                            <input
                                                class="review-sort-form__input"
                                                id="sort-panel-<?=strtolower($arVals['MESSAGE']); ?>"
                                                name="sort"
                                                type="radio"
                                                value="<?=$arVals['PROP'].':'.$arVals['ORDER']; ?>"
                                                <?=$isCurrent ? 'checked' : ''; ?>
                                            />
                                            <span class="dropdown-menu-item color_222<?=$isCurrent ? ' dropdown-menu-item--current' : ''; ?>">
                                                <span><?=Loc::getMessage($arVals['MESSAGE']); ?></span>
                                                <?if ($isCurrent):?>
                                                    <?=TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/form_icons.svg#checkmark', 'stroke-dark-light', [
                                                        'WIDTH' => 12,
                                                        'HEIGHT' => 9,
                                                    ]);?>
                                                <?endif; ?>
                                            </span>
                                        </label>
                                    <?endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <?foreach ($arAvailableFilter as $filter => $arOption):?>
                            <?if ($arOption['TYPE'] === 'LIST'):?>
                                <div class="filter-panel__sort-form__item">
                                    <div class="line-block line-block--gap line-block--gap-8 line-block--flex-wrap">
                                        <?foreach($arOption['VALUES'] as $arValue):?>
                                            <?php
                                            $checked = $arOption['INPUT_TYPE'] === 'checkbox' ? in_array($arValue['VALUE'], $arOption['CURRENT_VALUE']) : $arOption['CURRENT_VALUE'] == $arValue['VALUE'];
                                            $name = 'filter['.$filter.']'.($arOption['INPUT_TYPE'] === 'checkbox' ? '[]' : '');
                                            $title = $arValue['TITLE'];
                                            if (($arOption['SUBTYPE'] ?? '') === 'rating') {
                                                $title = TSolution::showSpriteIconSvg(SITE_TEMPLATE_PATH.'/images/svg/catalog/item_icons.svg#star-13-13', 'rating__star-svg--filled', [
                                                    'WIDTH' => 16,
                                                    'HEIGHT' => 16,
                                                ]);
                                                $title .= '<span>'.$arValue['VALUE'].'</span>';
                                            }
                                            ?>
                                            <label class="review-sort-form__input-container chip chip--transparent bordered font_15 <?=$arOption['INPUT_TYPE'];?>" title="<?=$arValue['TITLE'];?>">
                                                <input class="review-sort-form__input hidden"
                                                    type="<?=$arOption['INPUT_TYPE'];?>"
                                                    name="<?=$name;?>"
                                                    value="<?=$arValue['VALUE'];?>"
                                                    <?=$checked ? 'checked' : '';?>
                                                    <?=$arValue['DISABLED'] ? 'disabled' : '';?>
                                                >
                                                <span class="chip__label line-block line-block--gap line-block--gap-8"><?=$title;?></span>
                                            </label>
                                        <?endforeach;?>
                                    </div>
                                </div>
                            <?endif;?>

                            <?if ($arOption['TYPE'] === 'CHECKBOX'): // Filter type checkbox?>
                                <div class="filter-panel__sort-form__item filter label_block">
                                    <input class="form-checkbox__input review-sort-form__input"
                                        id="filter-panel-<?=strtolower($filter); ?>"
                                        name="filter[<?=$filter; ?>]"
                                        type="checkbox"
                                        value="Y"
                                        <?=$arOption['DISABLED'] ? 'disabled' : '';?>
                                        <?=$arOption['CURRENT_VALUE'] === 'Y' ? 'checked' : ''; ?>
                                    >
                                    <label class="form-checkbox__label form-checkbox__label--sm" for="filter-panel-<?=strtolower($filter); ?>">
                                        <span class="form-checkbox__box form-checkbox__box--static"></span>
                                        <?=$arOption['NAME']; ?>
                                    </label>
                                </div>
                            <?endif; ?>
                        <?endforeach; ?>
                    </div>
                </form>
            </div>
        <!--/noindex-->
    </div>
</div>
