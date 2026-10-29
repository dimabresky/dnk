<?php

namespace Dnk\PhpInterface;

use Bitrix\Catalog\StoreProductTable;
use Bitrix\Main\Loader;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use RuntimeException;
use Throwable;

/**
 * Агент: LINK_REGION товара по складам регионов с остатком больше нуля.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\CatalogLinkRegionAgent::run();
 * Интервал — 3600 сек.
 */
final class CatalogLinkRegionAgent
{
    public static function run(): string
    {
        $return = '\\Dnk\\PhpInterface\\CatalogLinkRegionAgent::run();';

        try {
            self::sync();
        } catch (Throwable $e) {
            AddMessage2Log($e->getMessage(), 'dnk.catalog_link_region');
        }

        return $return;
    }

    /**
     * @return array{processed:int,updated:int,cleared:int}
     */
    public static function sync(): array
    {
        $empty = ['processed' => 0, 'updated' => 0, 'cleared' => 0];

        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            throw new RuntimeException('Failed to load iblock or catalog module.');
        }

        $catalogIblockId = defined('DNK_CATALOG_IBLOCK_ID') ? (int) DNK_CATALOG_IBLOCK_ID : 0;
        if ($catalogIblockId <= 0) {
            throw new RuntimeException('DNK_CATALOG_IBLOCK_ID is not defined.');
        }

        if (!self::hasLinkRegionProperty($catalogIblockId)) {
            throw new RuntimeException('Catalog property LINK_REGION is missing.');
        }

        $regionIblockId = self::regionIblockId();
        if ($regionIblockId <= 0) {
            throw new RuntimeException('Region infoblock aspro_premier_regions is missing.');
        }

        $storeToRegions = self::storeToRegions($regionIblockId);
        if ($storeToRegions === []) {
            // Empty STORES_LINK must not wipe every product.
            return $empty;
        }

        $productRegions = self::productRegions($storeToRegions);

        $processed = 0;
        $updated = 0;
        $cleared = 0;
        foreach (self::currentRegions($catalogIblockId) as $elementId => $linked) {
            ++$processed;
            $next = self::sortedIds($productRegions[$elementId] ?? []);
            if (self::sortedIds($linked) === $next) {
                continue;
            }

            CIBlockElement::SetPropertyValuesEx($elementId, $catalogIblockId, [
                'LINK_REGION' => $next !== [] ? $next : false,
            ]);
            ++$updated;
            if ($next === []) {
                ++$cleared;
            }
        }

        return [
            'processed' => $processed,
            'updated' => $updated,
            'cleared' => $cleared,
        ];
    }

    private static function hasLinkRegionProperty(int $catalogIblockId): bool
    {
        $res = CIBlockProperty::GetList([], [
            'IBLOCK_ID' => $catalogIblockId,
            'CODE' => 'LINK_REGION',
        ]);
        $row = is_object($res) ? $res->Fetch() : false;

        return is_array($row) && (int) ($row['ID'] ?? 0) > 0;
    }

    private static function regionIblockId(): int
    {
        $res = CIBlock::GetList([], [
            'TYPE' => 'aspro_premier_regionality',
            'CODE' => 'aspro_premier_regions',
            'CHECK_PERMISSIONS' => 'N',
        ]);
        $row = is_object($res) ? $res->Fetch() : false;

        return is_array($row) ? (int) ($row['ID'] ?? 0) : 0;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private static function storeToRegions(int $regionIblockId): array
    {
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $regionIblockId,
                'ACTIVE' => 'Y',
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID', 'PROPERTY_STORES_LINK']
        );
        if (!is_object($res)) {
            return [];
        }

        $map = [];
        while ($row = $res->Fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $regionId = (int) ($row['ID'] ?? 0);
            if ($regionId <= 0) {
                continue;
            }
            foreach (self::values($row['PROPERTY_STORES_LINK_VALUE'] ?? null) as $value) {
                $storeId = self::positiveId($value);
                if ($storeId > 0) {
                    $map[$storeId][$regionId] = $regionId;
                }
            }
        }

        return $map;
    }

    /**
     * @param array<int, array<int, int>> $storeToRegions
     * @return array<int, array<int, int>>
     */
    private static function productRegions(array $storeToRegions): array
    {
        $rows = StoreProductTable::getList([
            'filter' => [
                '@STORE_ID' => array_keys($storeToRegions),
                '>AMOUNT' => 0,
            ],
            'select' => ['PRODUCT_ID', 'STORE_ID'],
        ]);

        $products = [];
        while ($row = $rows->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $productId = (int) ($row['PRODUCT_ID'] ?? 0);
            $storeId = (int) ($row['STORE_ID'] ?? 0);
            if ($productId <= 0 || !isset($storeToRegions[$storeId])) {
                continue;
            }
            foreach ($storeToRegions[$storeId] as $regionId) {
                $products[$productId][$regionId] = $regionId;
            }
        }

        return $products;
    }

    /**
     * @return array<int, array<int, int>>
     */
    private static function currentRegions(int $catalogIblockId): array
    {
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $catalogIblockId,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID', 'PROPERTY_LINK_REGION']
        );
        if (!is_object($res)) {
            return [];
        }

        $current = [];
        while ($row = $res->Fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $elementId = (int) ($row['ID'] ?? 0);
            if ($elementId <= 0) {
                continue;
            }
            if (!isset($current[$elementId])) {
                $current[$elementId] = [];
            }
            foreach (self::values($row['PROPERTY_LINK_REGION_VALUE'] ?? null) as $value) {
                $regionId = self::positiveId($value);
                if ($regionId > 0) {
                    $current[$elementId][$regionId] = $regionId;
                }
            }
        }

        return $current;
    }

    /**
     * @return list<mixed>
     */
    private static function values(mixed $value): array
    {
        return is_array($value) ? $value : [$value];
    }

    private static function positiveId(mixed $value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }
        if (!is_string($value)) {
            return 0;
        }
        $value = trim($value);
        if ($value === '' || !ctype_digit($value)) {
            return 0;
        }

        return (int) $value;
    }

    /**
     * @param array<int, int> $ids
     * @return list<int>
     */
    private static function sortedIds(array $ids): array
    {
        $ids = array_values($ids);
        sort($ids);

        return $ids;
    }
}
