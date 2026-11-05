<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;

/**
 * Оставляет в наличии IMSHOP склады региона Аспро, если региональность включена.
 */
final class ImshopAvailabilityEvents
{
    public static function onAvailabilityWarehousesBuild(Event $event): ?EventResult
    {
        if (!Loader::includeModule('aspro.premier') || !class_exists(\CPremierRegionality::class)) {
            return null;
        }

        if (!\CPremierRegionality::checkUseRegionality()) {
            return null;
        }

        $city = trim((string) $event->getParameter('CITY'));
        if ($city === '') {
            return null;
        }

        $region = self::regionByName(\CPremierRegionality::getRegions(), $city);
        if ($region === null) {
            return null;
        }

        $storeIds = self::regionStoreIds($region['LIST_STORES'] ?? null);
        if ($storeIds === null) {
            return null;
        }

        $warehouses = $event->getParameter('WAREHOUSES');
        $availability = $event->getParameter('AVAILABILITY');
        if (!is_array($warehouses) || !is_array($availability)) {
            return null;
        }

        $keptWarehouses = [];
        foreach ($warehouses as $warehouse) {
            if (!is_array($warehouse)) {
                continue;
            }

            $storeId = (int) ($warehouse['warehouseId'] ?? 0);
            $online = ($warehouse['online'] ?? false) === true;
            if ($storeId <= 0 || (!isset($storeIds[$storeId]) && !$online)) {
                continue;
            }

            if ($online && !isset($storeIds[$storeId])) {
                $warehouse['public'] = false;
            }

            $keptWarehouses[] = $warehouse;
        }

        $warehouseIds = [];
        foreach ($keptWarehouses as $warehouse) {
            $warehouseIds[(string) $warehouse['warehouseId']] = true;
        }

        $keptAvailability = [];
        foreach ($availability as $row) {
            if (!is_array($row)) {
                continue;
            }

            $warehouseId = trim((string) ($row['warehouseId'] ?? ''));
            if ($warehouseId !== '' && isset($warehouseIds[$warehouseId])) {
                $keptAvailability[] = $row;
            }
        }

        return new EventResult(EventResult::SUCCESS, [
            'WAREHOUSES' => $keptWarehouses,
            'AVAILABILITY' => $keptAvailability,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function regionByName(mixed $regions, string $city): ?array
    {
        if (!is_array($regions)) {
            return null;
        }

        $needle = mb_strtolower($city);
        foreach ($regions as $region) {
            if (!is_array($region)) {
                continue;
            }

            $name = trim((string) ($region['NAME'] ?? ''));
            if ($name !== '' && mb_strtolower($name) === $needle) {
                return $region;
            }
        }

        return null;
    }

    /**
     * @return array<int, true>|null
     */
    private static function regionStoreIds(mixed $listStores): ?array
    {
        if (!is_array($listStores) || $listStores === [] || reset($listStores) === 'component') {
            return null;
        }

        $ids = [];
        foreach ($listStores as $storeId) {
            if (is_numeric($storeId) && (int) $storeId > 0) {
                $ids[(int) $storeId] = true;
            }
        }

        return $ids === [] ? null : $ids;
    }
}
