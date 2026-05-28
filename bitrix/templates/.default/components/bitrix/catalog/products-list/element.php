<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Loader;

$this->setFrameMode(true);

Loader::includeModule('iblock');

global $arTheme, $NextSectionID;
$arSection = $arElement = [];
$bFastViewMode = (isset($_REQUEST['FAST_VIEW']) && $_REQUEST['FAST_VIEW'] == 'Y');
$bReviewsSort = (isset($_REQUEST['reviews_sort']) && $_REQUEST['reviews_sort'] == 'Y');
$arExtensions = ['catalog'];

// set params from module
TSolution\Functions::replaceDetailParams($arParams, []);

$_SESSION['BLOG_MAX_IMAGE_SIZE'] = $arParams['MAX_IMAGE_SIZE'] ?? 0.5;

if ($arResult['VARIABLES']['ELEMENT_ID'] > 0) {
    $arElementFilter = ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ID' => $arResult['VARIABLES']['ELEMENT_ID']];
} elseif (strlen(trim($arResult['VARIABLES']['ELEMENT_CODE'])) > 0) {
    $arElementFilter = ['IBLOCK_ID' => $arParams['IBLOCK_ID'], '=CODE' => $arResult['VARIABLES']['ELEMENT_CODE']];
}

if ($arParams['SHOW_DEACTIVATED'] !== 'Y') {
    $arElementFilter['ACTIVE'] = 'Y';
}

if ($GLOBALS[$arParams['FILTER_NAME']]) {
    $arElementFilter = array_merge($arElementFilter, $GLOBALS[$arParams['FILTER_NAME']]);
}

$arElement = TSolution\Cache::CIBLockElement_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], TSolution::makeElementFilterInRegion($arElementFilter), false, false, ['ID', 'IBLOCK_ID', 'TYPE', 'IBLOCK_SECTION_ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE']);

if (!$arElement) {
    Bitrix\Iblock\Component\Tools::process404(
        '', $arParams['SET_STATUS_404'] === 'Y', $arParams['SET_STATUS_404'] === 'Y', $arParams['SHOW_404'] === 'Y', $arParams['FILE_404']
    );
}

if ($arElement['IBLOCK_SECTION_ID']) {
    $sid = ((isset($arElement['IBLOCK_SECTION_ID_SELECTED']) && $arElement['IBLOCK_SECTION_ID_SELECTED']) ? $arElement['IBLOCK_SECTION_ID_SELECTED'] : $arElement['IBLOCK_SECTION_ID']);
    $arSection = TSolution\Cache::CIBlockSection_GetList(['CACHE' => ['MULTI' => 'N', 'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID'])]], ['GLOBAL_ACTIVE' => 'Y', 'ID' => $sid, 'IBLOCK_ID' => $arElement['IBLOCK_ID']], false, ['ID', 'IBLOCK_ID', 'NAME', 'IBLOCK_SECTION_ID', 'SECTION_PAGE_URL', 'DEPTH_LEVEL', 'LEFT_MARGIN', 'RIGHT_MARGIN']);

    $NextSectionID = $arSection['ID'];
}

if ($bFastViewMode) {
    include_once 'element_fast_view.php';
} elseif ($bReviewsSort) {
    include_once 'element_reviews.php';
} else {
    include_once 'element_normal.php';
}
?>
<!-- noindex -->
<template class="props-template">
    <?TSolution\Functions::showBlockHtml([
        'FILE' => 'catalog/props/list.php',
        'PARAMS' => [
            'FONT_CLASSES' => 'font_13',
        ]
    ]);?>
</template>
<!-- /noindex -->
<?
TSolution\Extensions::init($arExtensions);
