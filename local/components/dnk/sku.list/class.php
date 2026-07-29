<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Dnk\PhpInterface\Utils;

/**
 * Component for displaying related products by GRUPPIROVKATOVAROVNASAYTE property.
 * Shows products with the same grouping value as a row of shade image links or volume text links.
 */
class DnkSkuListComponent extends CBitrixComponent
{
    private const PROPERTY_CODE = 'GRUPPIROVKATOVAROVNASAYTE';

    private const SHADE_PROPERTY_CODE = 'OTTENOK';

    private const VOLUME_PROPERTY_CODE = 'NOMINALNYY_OBEM';

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
            $this->arResult['VARIANT_MODE'] = Utils::SKU_VARIANT_MODE_SHADE;
            $this->includeComponentTemplate();
            return;
        }

        $cacheTime = (int) ($this->arParams['CACHE_TIME'] ?? 3600);
        $cacheId = $iblockId . '_' . $elementId . '_' . $shadesIblockId . '_v4_vol';
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
                $this->arResult['VARIANT_MODE'] = Utils::SKU_VARIANT_MODE_SHADE;
            } else {
                $bundle = $this->getRelatedProducts(
                    $iblockId,
                    $elementId,
                    $groupingValue,
                    $shadesIblockId
                );
                $this->arResult['VARIANT_MODE'] = $bundle['mode'];
                if ($bundle['mode'] === Utils::SKU_VARIANT_MODE_VOLUME) {
                    $this->finalizeVolumeResult($bundle['items']);
                } else {
                    $this->arResult['ITEMS'] = $bundle['items'];
                    $this->arResult['CURRENT_ITEM'] = $this->resolveCurrentItem($this->arResult['ITEMS']);
                }
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
     * @return array{items: array, mode: string}
     */
    private function getRelatedProducts(
        int $iblockId,
        int $currentElementId,
        $groupingValue,
        int $shadesIblockId
    ): array {
        $groupingValue = (string) $groupingValue;
        $resolution = Utils::resolveSkuGroupVariantElementIds($iblockId, $groupingValue, $shadesIblockId);
        $visibleIds = $resolution['visible'];
        $mode = $resolution['mode'];

        if ($visibleIds === []) {
            return ['items' => [], 'mode' => $mode];
        }

        $ottenokEnumXmlIdMap = Utils::buildIblockListPropertyEnumXmlIdMap($iblockId, self::SHADE_PROPERTY_CODE);

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
                'PROPERTY_' . self::VOLUME_PROPERTY_CODE,
            ]
        );

        $rawItems = [];
        while ($ob = $rs->GetNext()) {
            $id = (int) $ob['ID'];
            if (!isset($visibleIds[$id])) {
                continue;
            }

            $pictureId = (int) ($ob['DETAIL_PICTURE'] ?: $ob['PREVIEW_PICTURE']);
            $pictureSrc = $pictureId > 0 ? CFile::GetPath($pictureId) : '';

            $enumId = Utils::coerceIblockListEnumId(
                $ob['PROPERTY_' . self::SHADE_PROPERTY_CODE . '_ENUM_ID'] ?? null
            );
            $ottenokXmlId = ($enumId !== null && isset($ottenokEnumXmlIdMap[$enumId]))
                ? $ottenokEnumXmlIdMap[$enumId]
                : '';

            $volumeLabel = trim((string) (
                $ob['PROPERTY_' . self::VOLUME_PROPERTY_CODE . '_VALUE']
                ?? $ob['PROPERTY_' . self::VOLUME_PROPERTY_CODE]
                ?? ''
            ));

            $rawItems[] = [
                'ID' => $id,
                'NAME' => $ob['NAME'],
                'DETAIL_PAGE_URL' => $ob['DETAIL_PAGE_URL'] ?? '',
                'PICTURE_SRC' => $pictureSrc,
                'IS_CURRENT' => $id === $currentElementId,
                'OTTENOK_XML_ID' => $ottenokXmlId,
                'VARIANT_LABEL' => $volumeLabel,
            ];
        }

        if ($mode === Utils::SKU_VARIANT_MODE_VOLUME) {
            $items = [];
            foreach ($rawItems as $row) {
                if ($row['VARIANT_LABEL'] === '') {
                    continue;
                }
                $items[] = [
                    'ID' => $row['ID'],
                    'NAME' => $row['NAME'],
                    'DETAIL_PAGE_URL' => $row['DETAIL_PAGE_URL'],
                    'VARIANT_LABEL' => $row['VARIANT_LABEL'],
                    'IS_CURRENT' => $row['IS_CURRENT'],
                ];
            }

            return ['items' => $items, 'mode' => $mode];
        }

        $ottenokXmlIds = [];
        foreach ($rawItems as $row) {
            $xmlId = trim((string) ($row['OTTENOK_XML_ID'] ?? ''));
            if ($xmlId !== '') {
                $ottenokXmlIds[$xmlId] = true;
            }
        }

        $shadesMap = ($shadesIblockId > 0 && $ottenokXmlIds !== [])
            ? $this->loadShadesByXmlIds($shadesIblockId, array_keys($ottenokXmlIds))
            : [];

        $items = [];
        foreach ($rawItems as $row) {
            $ottenokXmlId = trim((string) ($row['OTTENOK_XML_ID'] ?? ''));
            if ($ottenokXmlId === '' || !isset($shadesMap[$ottenokXmlId])) {
                continue;
            }

            $shade = $shadesMap[$ottenokXmlId];
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

        return ['items' => $items, 'mode' => Utils::SKU_VARIANT_MODE_SHADE];
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

    /**
     * Режим объёма: полный список вариантов; при менее чем двух — блок не показывается.
     *
     * @param array $items
     */
    private function finalizeVolumeResult(array $items): void
    {
        if (count($items) < 2) {
            $this->arResult['ITEMS'] = [];
            $this->arResult['CURRENT_ITEM'] = null;

            return;
        }

        $this->arResult['ITEMS'] = $items;
        $this->arResult['CURRENT_ITEM'] = $this->resolveCurrentItem($items);
    }
}
