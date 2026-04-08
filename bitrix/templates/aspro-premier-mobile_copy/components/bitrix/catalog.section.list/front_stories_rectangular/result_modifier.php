<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use Bitrix\Main\Type\Collection;

TSolution\Utils::getThemeParams($arParams, [
    'SHOW_TITLE_IN_BLOCK',
    'TITLE_POSITION',
]);

if ($arResult['SECTIONS']) {
    $arRegion = TSolution\Regionality::getRegions();

    /* add key - SECTION_ID */
    $arTmpSections = [];
    foreach ($arResult['SECTIONS'] as $arSecion) {
        $arTmpSections[$arSecion['ID']] = $arSecion;
    }
    $arResult['SECTIONS'] = $arTmpSections;
    unset($arTmpSections);

    /* set region link */
    if (
        $arParams['FILTER_NAME'] === 'arRegionLink'
        && $arRegion
        && TSolution::getFrontParametrValue('REGIONALITY_FILTER_ITEM') === 'Y'
    ) {
        /* set region filter section */
        $arFilter = [
            'IBLOCK_ID' => $arParams['IBLOCK_ID'],
            [
                'LOGIC' => 'OR',
                ['UF_REGION' => ''],
                ['UF_REGION' => $arRegion['ID']],
            ],
        ];
        $arSelect = [
            'ID',
            'IBLOCK_ID',
            'UF_REGION',
        ];
        $arSections = TSolution\Cache::CIBLockSection_GetList(
            [
                'CACHE' => [
                    'TAG' => TSolution\Cache::GetIBlockCacheTag($arParams['IBLOCK_ID']),
                    'MULTI' => 'Y',
                    'GROUP' => 'ID',
                ],
            ],
            $arFilter,
            false,
            $arSelect,
            false
        );

        if ($arSections) {
            foreach ($arResult['SECTIONS'] as $key => $arSecion) {
                if (!$arSections[$key]) {
                    unset($arResult['SECTIONS'][$key]);
                }
            }

            $sortOrder = $arParams['SORT_ORDER'] == 'ASC' ? SORT_ASC : SORT_DESC;
            $sortOrder2 = $arParams['SORT_ORDER_2'] == 'ASC' ? SORT_ASC : SORT_DESC;
            Collection::sortByColumn($arResult['SECTIONS'], [$arParams['SORT'] => $sortOrder, $arParams['SORT_2'] => $sortOrder2]);
        } else {
            $arResult['SECTIONS'] = [];
        }
    }
}

$arResult['SIGNED_PARAMS'] = TSolution\Stories::getComponentSignedParams($arParams);
