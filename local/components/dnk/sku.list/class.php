<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Component for displaying related products by GRUPPIROVKATOVAROVNASAYTE property.
 * Shows products with the same grouping value as a row of circular image links.
 */
class DnkSkuListComponent extends CBitrixComponent
{
    private const PROPERTY_CODE = 'GRUPPIROVKATOVAROVNASAYTE';

    public function executeComponent()
    {
        if (!CModule::IncludeModule('iblock')) {
            ShowError(GetMessage('DNK_SKU_LIST_MODULE_IBLOCK_NOT_INSTALLED'));
            return;
        }

        $iblockId = (int) ($this->arParams['IBLOCK_ID'] ?? 0);
        $elementId = (int) ($this->arParams['ELEMENT_ID'] ?? 0);

        if ($iblockId <= 0 || $elementId <= 0) {
            $this->arResult['ITEMS'] = [];
            $this->includeComponentTemplate();
            return;
        }

        $cacheTime = (int) ($this->arParams['CACHE_TIME'] ?? 3600);
        $cacheId = $iblockId . '_' . $elementId;
        $cachePath = '/dnk/sku.list';

        if ($this->startResultCache($cacheTime, $cacheId, $cachePath)) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cachePath);
            $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            $CACHE_MANAGER->EndTagCache();

            $groupingValue = $this->getCurrentElementGroupingValue($iblockId, $elementId);

            if ($groupingValue === null || $groupingValue === '') {
                $this->arResult['ITEMS'] = [];
                $this->arResult['CURRENT_ITEM'] = null;
            } else {
                $this->arResult['ITEMS'] = $this->getRelatedProducts($iblockId, $elementId, $groupingValue);
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
     * @return array
     */
    private function getRelatedProducts(int $iblockId, int $currentElementId, $groupingValue): array
    {
        $items = [];

        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'PROPERTY_' . self::PROPERTY_CODE => $groupingValue,
            ],
            false,
            false,
            ['ID', 'NAME', 'DETAIL_PICTURE', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'CODE', 'IBLOCK_SECTION_ID']
        );

        while ($ob = $rs->GetNext()) {
            $pictureId = (int) ($ob['DETAIL_PICTURE'] ?: $ob['PREVIEW_PICTURE']);
            $pictureSrc = $pictureId > 0
                ? CFile::GetPath($pictureId)
                : '';

            $items[] = [
                'ID' => (int) $ob['ID'],
                'NAME' => $ob['NAME'],
                'DETAIL_PAGE_URL' => $ob['DETAIL_PAGE_URL'] ?? '',
                'PICTURE_SRC' => $pictureSrc,
                'IS_CURRENT' => (int) $ob['ID'] === $currentElementId,
            ];
        }

        return $items;
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
