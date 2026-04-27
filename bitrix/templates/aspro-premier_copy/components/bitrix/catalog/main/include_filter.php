<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

global $arTheme, $APPLICATION;

if (!isset($bSearchPage)) {
    if (strlen($arParams['FILTER_NAME'])) {
        $GLOBALS[$arParams['FILTER_NAME']] = array_merge((array) $GLOBALS[$arParams['FILTER_NAME']], $arElementFilter);
    } else {
        $arParams['FILTER_NAME'] = 'arrFilter';
        $GLOBALS[$arParams['FILTER_NAME']] = $arElementFilter;
    }
}

if ('N' !== $arTheme['SHOW_SMARTFILTER']['VALUE'] && ($itemsCnt || isset($bSearchPage))) {
    $GLOBALS['preFilterCatalog'] = TSolution\Regionality::mergeSmartPreFilterWithRegionFilter($GLOBALS['preFilterCatalog'] ?? []);
    
    TSolution\CacheableUrl::addSmartFilterNameParam($arParams['FILTER_NAME']);

    $bCompactView = 'COMPACT' == $arParams['FILTER_VIEW'];
    if ($bCompactView) {
        $template = 'main_compact';
    } else {
        $template = 'main';
        $arTheme['STICKY_SIDEBAR']['VALUE'] = 'N';
    }
    if (!$bCompactView) {
        $this->__component->__template->SetViewTarget('under_sidebar_content');
    }

    $APPLICATION->IncludeComponent(
        'bitrix:catalog.smart.filter', $template,
        [
            'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
            'IBLOCK_ID' => $arParams['IBLOCK_ID'],
            'SECTION_ID' => $arSection['ID'],
            'PREFILTER_NAME' => 'preFilterCatalog',
            'FILTER_NAME' => $arParams['FILTER_NAME'],
            'PRICE_CODE' => 'Y' !== $arParams['USE_FILTER_PRICE'] ? $arParams['PRICE_CODE'] : $arParams['FILTER_PRICE_CODE'],
            'CACHE_TYPE' => $arParams['CACHE_TYPE'],
            'CACHE_TIME' => $arParams['CACHE_TIME'],
            'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
            'SAVE_IN_SESSION' => 'N',
            'FILTER_VIEW_MODE' => ('HORIZONTAL' == $arTheme['FILTER_VIEW']['VALUE'] ? 'HORIZONTAL' : 'VERTICAL'),
            'DISPLAY_ELEMENT_COUNT' => $arParams['DISPLAY_ELEMENT_COUNT'] ?? 'Y',
            'POPUP_POSITION' => ('LEFT' == $arTheme['SIDE_MENU']['VALUE'] ? 'right' : 'left'),
            'AJAX' => TSolution::GetFrontParametrValue('AJAX_FILTER'),
            'INSTANT_RELOAD' => 'Y',
            'XML_EXPORT' => 'N',
            'TEMPLATE_THEME' => $arParams['TEMPLATE_THEME'],
            'SORT_HTML' => $sortHTML,
            'SHOW_HINTS' => $arParams['SHOW_HINTS'],
            'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
            'CURRENCY_ID' => $arParams['CURRENCY_ID'],
            'SEF_MODE' => (strlen($arResult['URL_TEMPLATES']['smart_filter']) ? 'Y' : 'N'),
            'SEF_RULE' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['smart_filter'],
            'SMART_FILTER_PATH' => $arResult['VARIABLES']['SMART_FILTER_PATH'],
            'HIDE_NOT_AVAILABLE' => TSolution::GetFrontParametrValue('HIDE_NOT_AVAILABLE'),
            'SEF_RULE_FILTER' => $arResult['URL_TEMPLATES']['smart_filter'],
            'HIDE_SMART_SEO' => $arParams['HIDE_SMART_SEO'] ?? 'N',
        ],
        $component
    );

    if (!$bCompactView) {
        $this->__component->__template->EndViewTarget();
    }
}
