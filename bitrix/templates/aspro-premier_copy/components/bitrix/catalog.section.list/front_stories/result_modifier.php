<?php

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

    /* set section custom link */
    if ($arResult['SECTIONS']) {
        foreach ($arResult['SECTIONS'] as &$arSection) {
            $arSection['UF_LINK'] = '';
        }
        unset($arSection);

        $sectionIds = array_keys($arResult['SECTIONS']);
        $arSections = CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                'ID' => $sectionIds,
            ],
            false,
            [
                'ID',
                'UF_LINK',
            ]
        );

        while ($arSection = $arSections->Fetch()) {
            $sectionId = (int)$arSection['ID'];
            if (isset($arResult['SECTIONS'][$sectionId])) {
                $arResult['SECTIONS'][$sectionId]['UF_LINK'] = (string)$arSection['UF_LINK'];
            }
        }
    }
}

$arResult['SIGNED_PARAMS'] = TSolution\Stories::getComponentSignedParams($arParams);
