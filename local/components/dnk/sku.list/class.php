<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Dnk\PhpInterface\Utils;

/**
 * Component for displaying related products by GRUPPIROVKATOVAROVNASAYTE property.
 * Shows products with the same grouping value as a row of shade image links.
 */
class DnkSkuListComponent extends CBitrixComponent
{
    private const PROPERTY_CODE = 'GRUPPIROVKATOVAROVNASAYTE';

    private const SHADE_PROPERTY_CODE = 'OTTENOK';

    public function executeComponent()
    {
        if (!CModule::IncludeModule('iblock')) {
            ShowError(GetMessage('DNK_SKU_LIST_MODULE_IBLOCK_NOT_INSTALLED'));
            return;
        }

        $iblockId = (int) ($this->arParams['IBLOCK_ID'] ?? 0);
        $elementId = (int) ($this->arParams['ELEMENT_ID'] ?? 0);
        $shadesIblockId = (int) ($this->arParams['SHADES_IBLOCK_ID'] ?? 49);

        if ($iblockId <= 0 || $elementId <= 0) {
            $this->arResult['ITEMS'] = [];
            $this->includeComponentTemplate();
            return;
        }

        $cacheTime = (int) ($this->arParams['CACHE_TIME'] ?? 3600);
        $cacheId = $iblockId . '_' . $elementId . '_' . $shadesIblockId;
        $cachePath = '/dnk/sku.list';

        if ($this->startResultCache($cacheTime, $cacheId, $cachePath)) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cachePath);
            $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            if ($shadesIblockId > 0) {
                $CACHE_MANAGER->RegisterTag('iblock_id_' . $shadesIblockId);
            }
            $CACHE_MANAGER->EndTagCache();

            $groupingValue = $this->getCurrentElementGroupingValue($iblockId, $elementId);

            if ($groupingValue === null || $groupingValue === '') {
                $this->arResult['ITEMS'] = [];
                $this->arResult['CURRENT_ITEM'] = null;
            } else {
                $this->arResult['ITEMS'] = $this->getRelatedProducts(
                    $iblockId,
                    $elementId,
                    $groupingValue,
                    $shadesIblockId
                );
                $this->arResult['CURRENT_ITEM'] = $this->resolveCurrentItem($this->arResult['ITEMS']);
            }

            $this->includeComponentTemplate();
        }
    }

    /**
     * Get GRUPPIROVKATOVAROVNASAYTE property value of the current product.
     *
     * @param int $iblockId
     * @param int $elementId
     * @return string|int|null
     */
    private function getCurrentElementGroupingValue(int $iblockId, int $elementId)
    {
        $rs = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ID' => $elementId,
                'ACTIVE' => 'Y',
            ],
            false,
            false,
            ['ID', 'PROPERTY_' . self::PROPERTY_CODE]
        );

        if ($ob = $rs->GetNext()) {
            $value = $ob['PROPERTY_' . self::PROPERTY_CODE . '_VALUE']
                ?? $ob['PROPERTY_' . self::PROPERTY_CODE]
                ?? null;

            if (is_array($value)) {
                return !empty($value) ? reset($value) : null;
            }

            return $value;
        }

        return null;
    }

    /**
     * Все товары группы с тем же GRUPPIROVKATOVAROVNASAYTE, включая текущий элемент.
     *
     * @param int $iblockId
     * @param int $currentElementId
     * @param string|int $groupingValue
     * @param int $shadesIblockId
     * @return array
     */
    private function getRelatedProducts(
        int $iblockId,
        int $currentElementId,
        $groupingValue,
        int $shadesIblockId
    ): array {
        $rawItems = [];

        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_' . self::PROPERTY_CODE => $groupingValue,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'DETAIL_PICTURE',
                'PREVIEW_PICTURE',
                'DETAIL_PAGE_URL',
                'CODE',
                'IBLOCK_SECTION_ID',
                'PROPERTY_' . self::SHADE_PROPERTY_CODE,
            ]
        );

        while ($ob = $rs->GetNext()) {
            $pictureId = (int) ($ob['DETAIL_PICTURE'] ?: $ob['PREVIEW_PICTURE']);
            $pictureSrc = $pictureId > 0 ? CFile::GetPath($pictureId) : '';

            $enumId = Utils::coerceIblockListEnumId(
                $ob['PROPERTY_' . self::SHADE_PROPERTY_CODE . '_ENUM_ID']
                    ?? $ob['PROPERTY_' . self::SHADE_PROPERTY_CODE . '_VALUE']
                    ?? null
            );

            $rawItems[] = [
                'ID' => (int) $ob['ID'],
                'NAME' => $ob['NAME'],
                'DETAIL_PAGE_URL' => $ob['DETAIL_PAGE_URL'] ?? '',
                'PICTURE_SRC' => $pictureSrc,
                'IS_CURRENT' => (int) $ob['ID'] === $currentElementId,
                'OTTENOK_ENUM_ID' => $enumId,
            ];
        }

        $shadesMap = $shadesIblockId > 0
            ? $this->buildShadesMapByEnumIds($rawItems, $shadesIblockId)
            : [];

        $items = [];
        foreach ($rawItems as $row) {
            if (empty($row['OTTENOK_ENUM_ID']) || !isset($shadesMap[$row['OTTENOK_ENUM_ID']])) {
                continue;
            }

            $shade = $shadesMap[$row['OTTENOK_ENUM_ID']];
            $shadePicture = trim((string) ($shade['PICTURE_SRC'] ?? ''));

            $items[] = [
                'ID' => $row['ID'],
                'NAME' => $row['NAME'],
                'DETAIL_PAGE_URL' => $row['DETAIL_PAGE_URL'],
                'PICTURE_SRC' => $row['PICTURE_SRC'],
                'SHADE_NAME' => $shade['NAME'],
                'SHADE_PICTURE_SRC' => $shadePicture !== '' ? $shadePicture : $row['PICTURE_SRC'],
                'IS_CURRENT' => $row['IS_CURRENT'],
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array<string, mixed>> $rawItems
     * @param int $shadesIblockId
     * @return array<int, array{NAME: string, PICTURE_SRC: string}>
     */
    private function buildShadesMapByEnumIds(array $rawItems, int $shadesIblockId): array
    {
        $enumIds = [];
        foreach ($rawItems as $row) {
            $enumId = $row['OTTENOK_ENUM_ID'] ?? null;
            if ($enumId !== null && $enumId > 0) {
                $enumIds[$enumId] = true;
            }
        }

        if ($enumIds === []) {
            return [];
        }

        $enumIdToXmlId = [];
        foreach (array_keys($enumIds) as $enumId) {
            $arEnum = CIBlockPropertyEnum::GetByID($enumId);
            if (!is_array($arEnum)) {
                continue;
            }
            $xmlId = trim((string) ($arEnum['XML_ID'] ?? ''));
            if ($xmlId !== '') {
                $enumIdToXmlId[$enumId] = $xmlId;
            }
        }

        if ($enumIdToXmlId === []) {
            return [];
        }

        $shadesByXmlId = $this->loadShadesByXmlIds($shadesIblockId, array_values(array_unique($enumIdToXmlId)));

        $map = [];
        foreach ($enumIdToXmlId as $enumId => $xmlId) {
            if (isset($shadesByXmlId[$xmlId])) {
                $map[$enumId] = $shadesByXmlId[$xmlId];
            }
        }

        return $map;
    }

    /**
     * @param int $shadesIblockId
     * @param list<string> $xmlIds
     * @return array<string, array{NAME: string, PICTURE_SRC: string}>
     */
    private function loadShadesByXmlIds(int $shadesIblockId, array $xmlIds): array
    {
        $xmlIds = array_values(array_filter(array_unique(array_map('strval', $xmlIds))));
        if ($shadesIblockId <= 0 || $xmlIds === []) {
            return [];
        }

        $map = [];
        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => $shadesIblockId,
                'ACTIVE' => 'Y',
                'XML_ID' => $xmlIds,
            ],
            false,
            false,
            ['ID', 'NAME', 'XML_ID', 'DETAIL_PICTURE']
        );

        while ($ob = $rs->GetNext()) {
            $xmlId = trim((string) ($ob['XML_ID'] ?? ''));
            if ($xmlId === '') {
                continue;
            }

            $pictureId = (int) ($ob['DETAIL_PICTURE'] ?? 0);
            $map[$xmlId] = [
                'NAME' => (string) ($ob['NAME'] ?? ''),
                'PICTURE_SRC' => $pictureId > 0 ? (string) CFile::GetPath($pictureId) : '',
            ];
        }

        return $map;
    }

    /**
     * @param array $items
     * @return array|null
     */
    private function resolveCurrentItem(array $items)
    {
        foreach ($items as $row) {
            if (!empty($row['IS_CURRENT'])) {
                return $row;
            }
        }

        return $items[0] ?? null;
    }
}
