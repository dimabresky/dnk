<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (empty($arLanding)) {
    return;
}
?>
<?php
if (
    strlen($arLanding['DETAIL_TEXT'])
    || (
        $arParams['SHOW_LANDINGS'] !== 'N'
        && $arLanding['PROPERTY_SIMILAR_VALUE']
        && ($landingSearchIblockId = TSolution\Cache::$arIBlocks[SITE_ID]['aspro_premier_catalog']['aspro_premier_search'][0])
    )
):?>
    <div class="group_description_block bottom font_15">
        <?if (strlen($arLanding['DETAIL_TEXT'])):?>
            <?=$arLanding['DETAIL_TEXT'];?>
        <?endif;?>

        <?$APPLICATION->ShowViewContent('sotbit_seometa_bottom_desc');?>

        <?if (
            $arParams['SHOW_LANDINGS'] !== 'N'
            && $arLanding['PROPERTY_SIMILAR_VALUE']
            && ($landingSearchIblockId = TSolution\Cache::$arIBlocks[SITE_ID]['aspro_premier_catalog']['aspro_premier_search'][0])
        ):?>
            <?php
            $arLandingsFilter['ID'] = $arLanding['PROPERTY_SIMILAR_VALUE'];
            $GLOBALS['arLandingsFilter'] = $arLandingsFilter;
            ?>
            <?$APPLICATION->IncludeComponent(
                'bitrix:news.list',
                'landings_search_list',
                [
                    'IBLOCK_TYPE' => 'aspro_premier_catalog',
                    'IBLOCK_ID' => $landingSearchIblockId,
                    'NEWS_COUNT' => $arParams['LANDING_SECTION_COUNT'],
                    'SHOW_COUNT' => $arParams['LANDING_SEARCH_COUNT_VISIBLE'],
                    'SORT_BY1' => 'SORT',
                    'SORT_ORDER1' => 'ASC',
                    'SORT_BY2' => 'ID',
                    'SORT_ORDER2' => 'DESC',
                    'FILTER_NAME' => 'arLandingsFilter',
                    'FIELD_CODE' => [
                        0 => '',
                        1 => '',
                    ],
                    'PROPERTY_CODE' => [
                        0 => 'URL_CONDITION',
                        1 => 'REDIRECT_URL',
                        2 => 'QUERY',
                        3 => '',
                    ],
                    'CHECK_DATES' => 'Y',
                    'DETAIL_URL' => '',
                    'AJAX_MODE' => 'N',
                    'AJAX_OPTION_JUMP' => 'N',
                    'AJAX_OPTION_STYLE' => 'Y',
                    'AJAX_OPTION_HISTORY' => 'N',
                    'CACHE_TYPE' => $arParams['CACHE_TYPE'],
                    'CACHE_TIME' => $arParams['CACHE_TIME'],
                    'CACHE_FILTER' => 'Y',
                    'CACHE_GROUPS' => 'N',
                    'PREVIEW_TRUNCATE_LEN' => '',
                    'ACTIVE_DATE_FORMAT' => 'j F Y',
                    'SET_TITLE' => 'N',
                    'SET_STATUS_404' => 'N',
                    'INCLUDE_IBLOCK_INTO_CHAIN' => 'N',
                    'ADD_SECTIONS_CHAIN' => 'N',
                    'HIDE_LINK_WHEN_NO_DETAIL' => 'N',
                    'PARENT_SECTION' => '',
                    'PARENT_SECTION_CODE' => '',
                    'INCLUDE_SUBSECTIONS' => 'Y',
                    'PAGER_TEMPLATE' => '',
                    'DISPLAY_TOP_PAGER' => 'N',
                    'DISPLAY_BOTTOM_PAGER' => 'N',
                    'PAGER_TITLE' => '',
                    'PAGER_SHOW_ALWAYS' => 'N',
                    'PAGER_DESC_NUMBERING' => 'N',
                    'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
                    'PAGER_SHOW_ALL' => 'N',
                    'AJAX_OPTION_ADDITIONAL' => '',
                    'COMPONENT_TEMPLATE' => 'landings_search_list',
                    'SET_BROWSER_TITLE' => 'N',
                    'SET_META_KEYWORDS' => 'N',
                    'SET_META_DESCRIPTION' => 'N',
                    'SET_LAST_MODIFIED' => 'N',
                    'PAGER_BASE_LINK_ENABLE' => 'N',
                    'TITLE_BLOCK' => $arParams['LANDING_TITLE'] ?? '',
                    'SHOW_404' => 'N',
                    'MESSAGE_404' => '',
                ],
                false, ['HIDE_ICONS' => 'Y']
            );?>
        <?endif;?>
    </div>
<?endif;?>
<?php
$langing_seo_h1 = strip_tags(htmlspecialchars_decode($arLanding['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] != '' ? $arLanding['IPROPERTY_VALUES']['ELEMENT_PAGE_TITLE'] : $arLanding['NAME']));
$APPLICATION->SetTitle($langing_seo_h1);

if ($arLanding['IPROPERTY_VALUES']['ELEMENT_META_TITLE']) {
    $APPLICATION->SetPageProperty('title', strip_tags(htmlspecialchars_decode($arLanding['IPROPERTY_VALUES']['ELEMENT_META_TITLE'])));
} else {
    $APPLICATION->SetPageProperty('title', strip_tags(htmlspecialchars_decode($arLanding['NAME'].$postfix)));
}

if ($arLanding['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION']) {
    $APPLICATION->SetPageProperty('description', strip_tags(htmlspecialchars_decode($arLanding['IPROPERTY_VALUES']['ELEMENT_META_DESCRIPTION'])));
}

if ($arLanding['IPROPERTY_VALUES']['ELEMENT_META_KEYWORDS']) {
    $APPLICATION->SetPageProperty('keywords', $arLanding['IPROPERTY_VALUES']['ELEMENT_META_KEYWORDS']);
}
?>
