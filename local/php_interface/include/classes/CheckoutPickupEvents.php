<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;

/**
 * Убирает самовывоз из выбора доставки, если в итоговом списке складов пусто.
 */
final class CheckoutPickupEvents
{
    /**
     * @param array<string, mixed> $arResult
     * @param mixed $arParams
     */
    public static function onSaleComponentOrderJsData(&$arResult, &$arParams): void
    {
        // shortcut: total stays on the removed delivery until the next checkout ajax; saveOrderAjax does not fire this event, reprice here if an order must not save that id.
        if (!is_array($arResult) || !isset($arResult['JS_DATA']['DELIVERY']) || !is_array($arResult['JS_DATA']['DELIVERY'])) {
            return;
        }

        $storeList = $arResult['JS_DATA']['STORE_LIST'] ?? [];
        if (!is_array($storeList)) {
            $storeList = [];
        }

        $regionStoreIds = self::regionStoreIds();
        $removedChecked = false;

        foreach (DNK_PICKUP_DELIVERY_IDS as $pickupId) {
            $pickupId = (int) $pickupId;
            if (!self::dropDelivery($arResult, $pickupId, $storeList, $regionStoreIds)) {
                continue;
            }
            $removedChecked = true;
        }

        if ($removedChecked) {
            self::checkFirstRemaining($arResult);
        }
    }

    /**
     * @param array<string, mixed> $arResult
     * @param array<int|string, mixed> $storeList
     * @param array<int, true>|null $regionStoreIds
     */
    private static function dropDelivery(array &$arResult, int $pickupId, array $storeList, ?array $regionStoreIds): bool
    {
        $jsKey = self::deliveryKey($arResult['JS_DATA']['DELIVERY'], $pickupId);
        if ($jsKey === null) {
            return false;
        }

        $delivery = $arResult['JS_DATA']['DELIVERY'][$jsKey];
        $configured = is_array($delivery) ? ($delivery['STORE'] ?? []) : [];
        if (!is_array($configured)) {
            $configured = [];
        }

        if (self::hasVisibleStore($configured, $storeList, $regionStoreIds)) {
            return false;
        }

        $wasChecked = is_array($delivery) && ($delivery['CHECKED'] ?? '') === 'Y';
        unset($arResult['JS_DATA']['DELIVERY'][$jsKey]);

        if (isset($arResult['DELIVERY']) && is_array($arResult['DELIVERY'])) {
            $resultKey = self::deliveryKey($arResult['DELIVERY'], $pickupId);
            if ($resultKey !== null) {
                unset($arResult['DELIVERY'][$resultKey]);
            }
        }

        return $wasChecked;
    }

    /**
     * @param array<int|string, mixed> $deliveries
     */
    private static function deliveryKey(array $deliveries, int $pickupId): int|string|null
    {
        if (array_key_exists($pickupId, $deliveries)) {
            return $pickupId;
        }

        $stringId = (string) $pickupId;
        if (array_key_exists($stringId, $deliveries)) {
            return $stringId;
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $configured
     * @param array<int|string, mixed> $storeList
     * @param array<int, true>|null $regionStoreIds
     */
    private static function hasVisibleStore(array $configured, array $storeList, ?array $regionStoreIds): bool
    {
        foreach ($configured as $storeId) {
            if (!is_numeric($storeId) || (int) $storeId <= 0) {
                continue;
            }

            $storeId = (int) $storeId;
            if (!array_key_exists($storeId, $storeList) && !array_key_exists((string) $storeId, $storeList)) {
                continue;
            }
            if ($regionStoreIds !== null && !isset($regionStoreIds[$storeId])) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @return array<int, true>|null
     */
    private static function regionStoreIds(): ?array
    {
        if (!Loader::includeModule('aspro.premier') || !class_exists(\CPremierRegionality::class)) {
            return null;
        }

        $region = \CPremierRegionality::getCurrentRegion();
        if (!is_array($region) || empty($region['LIST_STORES']) || !is_array($region['LIST_STORES'])) {
            return null;
        }
        if (reset($region['LIST_STORES']) === 'component') {
            return null;
        }

        $ids = [];
        foreach ($region['LIST_STORES'] as $storeId) {
            if (is_numeric($storeId) && (int) $storeId > 0) {
                $ids[(int) $storeId] = true;
            }
        }

        return $ids;
    }

    /**
     * @param array<string, mixed> $arResult
     */
    private static function checkFirstRemaining(array &$arResult): void
    {
        foreach (['JS_DATA', null] as $bucket) {
            $deliveries = $bucket === null ? ($arResult['DELIVERY'] ?? null) : ($arResult['JS_DATA']['DELIVERY'] ?? null);
            if (!is_array($deliveries) || $deliveries === []) {
                continue;
            }

            foreach ($deliveries as $delivery) {
                if (is_array($delivery) && ($delivery['CHECKED'] ?? '') === 'Y') {
                    continue 2;
                }
            }

            $firstKey = array_key_first($deliveries);
            if ($bucket === null) {
                $arResult['DELIVERY'][$firstKey]['CHECKED'] = 'Y';
            } else {
                $arResult['JS_DATA']['DELIVERY'][$firstKey]['CHECKED'] = 'Y';
            }
        }
    }
}
