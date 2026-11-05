<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Shipment;

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
            $inStoreList = array_key_exists($storeId, $storeList) || array_key_exists((string) $storeId, $storeList);
            if (!$inStoreList && ($storeList !== [] || $regionStoreIds === null)) {
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

        if (!\CPremierRegionality::checkUseRegionality()) {
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

    /**
     * Переключает отгрузку на службу, отмеченную после скрытия самовывоза, и переписывает сумму этого ответа.
     *
     * @param mixed $order
     * @param mixed $arUserResult
     * @param mixed $request
     * @param mixed $arParams
     * @param mixed $arResult
     */
    public static function onSaleComponentOrderResultPrepared($order, &$arUserResult, $request, &$arParams, &$arResult): void
    {
        // shortcut: saveOrderAjax does not fire this event; payment sum stays until the next checkout ajax posts the new delivery id.
        if (!$order instanceof Order || !is_array($arResult)) {
            return;
        }

        $deliveries = $arResult['JS_DATA']['DELIVERY'] ?? null;
        if (!is_array($deliveries) || $deliveries === []) {
            return;
        }

        $shipment = self::currentShipment($order);
        if ($shipment === null) {
            return;
        }

        if (self::deliveryKey($deliveries, $shipment->getDeliveryId()) !== null) {
            return;
        }

        $checked = self::checkedDelivery($deliveries);
        if ($checked === null) {
            return;
        }

        if (is_array($arUserResult)) {
            $arUserResult['DELIVERY_ID'] = $checked['id'];
        }
        if (isset($arResult['USER_VALS']) && is_array($arResult['USER_VALS'])) {
            $arResult['USER_VALS']['DELIVERY_ID'] = $checked['id'];
        }

        $shipment->setField('CUSTOM_PRICE_DELIVERY', 'N');
        $shipment->setField('DISCOUNT_PRICE', 0);
        $switched = $shipment->setField('DELIVERY_ID', $checked['id']);
        if (!$switched->isSuccess()) {
            return;
        }

        $calculated = $order->getShipmentCollection()->calculateDelivery();
        if (!$calculated->isSuccess()) {
            self::writeRowPrice($arResult, $deliveries[$checked['key']]);

            return;
        }

        $order->doFinalAction(true);
        self::writeOrderPrice($order, $arResult, $checked['id']);
    }

    private static function currentShipment(Order $order): ?Shipment
    {
        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment instanceof Shipment && !$shipment->isSystem()) {
                return $shipment;
            }
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $deliveries
     * @return array{key: int|string, id: int}|null
     */
    private static function checkedDelivery(array $deliveries): ?array
    {
        foreach ($deliveries as $key => $delivery) {
            if (!is_array($delivery) || ($delivery['CHECKED'] ?? '') !== 'Y') {
                continue;
            }

            $id = (int) ($delivery['ID'] ?? $key);
            if ($id <= 0) {
                return null;
            }

            return ['key' => $key, 'id' => $id];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $arResult
     * @param array<string, mixed> $delivery
     */
    private static function writeRowPrice(array &$arResult, array $delivery): void
    {
        $price = $delivery['DELIVERY_DISCOUNT_PRICE'] ?? $delivery['PRICE'] ?? null;
        if (!is_numeric($price)) {
            return;
        }

        $currency = is_string($delivery['CURRENCY'] ?? null) ? $delivery['CURRENCY'] : '';
        if ($currency === '') {
            return;
        }

        self::shiftDeliveryTotal($arResult, (float) $price, $currency);
    }

    /**
     * @param array<string, mixed> $arResult
     */
    private static function writeOrderPrice(Order $order, array &$arResult, int $deliveryId): void
    {
        $currency = $order->getCurrency();
        $deliveryPrice = PriceMaths::roundByFormatCurrency($order->getDeliveryPrice(), $currency);
        $totalPrice = PriceMaths::roundByFormatCurrency($order->getPrice(), $currency);
        $inner = $order->getPaymentCollection()->getInnerPayment();
        $left = $inner !== null && $inner->getSum() > 0
            ? PriceMaths::roundByFormatCurrency($order->getPrice() - $inner->getSum(), $currency)
            : null;

        self::putPrices($arResult, $deliveryPrice, $totalPrice, $left, $currency);
        if (isset($arResult['JS_DATA']['TOTAL']) && is_array($arResult['JS_DATA']['TOTAL'])) {
            self::putPrices($arResult['JS_DATA']['TOTAL'], $deliveryPrice, $totalPrice, $left, $currency);
        }

        self::syncCheckedPrice($arResult, $deliveryId, $deliveryPrice, (string) \SaleFormatCurrency($deliveryPrice, $currency));
        self::putGoodsPrice($order, $arResult, $currency);
    }

    /**
     * @param array<string, mixed> $arResult
     */
    private static function shiftDeliveryTotal(array &$arResult, float $deliveryPrice, string $currency): void
    {
        self::shiftOneTotal($arResult, $deliveryPrice, $currency);
        if (isset($arResult['JS_DATA']['TOTAL']) && is_array($arResult['JS_DATA']['TOTAL'])) {
            self::shiftOneTotal($arResult['JS_DATA']['TOTAL'], $deliveryPrice, $currency);
        }
    }

    /**
     * @param array<string, mixed> $bucket
     */
    private static function shiftOneTotal(array &$bucket, float $deliveryPrice, string $currency): void
    {
        $previousDelivery = is_numeric($bucket['DELIVERY_PRICE'] ?? null) ? (float) $bucket['DELIVERY_PRICE'] : $deliveryPrice;
        $delta = $deliveryPrice - $previousDelivery;
        $bucket['DELIVERY_PRICE'] = $deliveryPrice;
        $bucket['DELIVERY_PRICE_FORMATED'] = \SaleFormatCurrency($deliveryPrice, $currency);

        if (is_numeric($bucket['ORDER_TOTAL_PRICE'] ?? null)) {
            $totalPrice = (float) $bucket['ORDER_TOTAL_PRICE'] + $delta;
            $bucket['ORDER_TOTAL_PRICE'] = $totalPrice;
            $bucket['ORDER_TOTAL_PRICE_FORMATED'] = \SaleFormatCurrency($totalPrice, $currency);
        }

        if (!is_numeric($bucket['ORDER_TOTAL_LEFT_TO_PAY'] ?? null)) {
            return;
        }

        $left = (float) $bucket['ORDER_TOTAL_LEFT_TO_PAY'] + $delta;
        $bucket['ORDER_TOTAL_LEFT_TO_PAY'] = $left;
        $bucket['ORDER_TOTAL_LEFT_TO_PAY_FORMATED'] = \SaleFormatCurrency($left, $currency);
    }

    /**
     * @param array<string, mixed> $bucket
     */
    private static function putPrices(array &$bucket, float $deliveryPrice, float $totalPrice, ?float $left, string $currency): void
    {
        $bucket['DELIVERY_PRICE'] = $deliveryPrice;
        $bucket['DELIVERY_PRICE_FORMATED'] = \SaleFormatCurrency($deliveryPrice, $currency);
        $bucket['ORDER_TOTAL_PRICE'] = $totalPrice;
        $bucket['ORDER_TOTAL_PRICE_FORMATED'] = \SaleFormatCurrency($totalPrice, $currency);

        if ($left === null || !array_key_exists('ORDER_TOTAL_LEFT_TO_PAY', $bucket) || $bucket['ORDER_TOTAL_LEFT_TO_PAY'] === '' || $bucket['ORDER_TOTAL_LEFT_TO_PAY'] === null) {
            return;
        }

        $bucket['ORDER_TOTAL_LEFT_TO_PAY'] = $left;
        $bucket['ORDER_TOTAL_LEFT_TO_PAY_FORMATED'] = \SaleFormatCurrency($left, $currency);
    }

    /**
     * @param array<string, mixed> $arResult
     */
    private static function putGoodsPrice(Order $order, array &$arResult, string $currency): void
    {
        $bonusApplied = !empty($arResult['JS_DATA']['ASPRO_BONUS']['USES']);
        $basket = $order->getBasket();
        if (!$bonusApplied && $basket === null) {
            return;
        }

        $goods = $bonusApplied
            ? $order->getPrice() - $order->getDeliveryPrice()
            : PriceMaths::roundByFormatCurrency($basket->getPrice(), $currency);
        $goodsFormatted = \SaleFormatCurrency($goods, $currency);

        $arResult['ORDER_PRICE'] = $goods;
        $arResult['ORDER_PRICE_FORMATED'] = $goodsFormatted;
        if (isset($arResult['JS_DATA']['TOTAL']) && is_array($arResult['JS_DATA']['TOTAL'])) {
            $arResult['JS_DATA']['TOTAL']['ORDER_PRICE'] = $goods;
            $arResult['JS_DATA']['TOTAL']['ORDER_PRICE_FORMATED'] = $goodsFormatted;
        }

        if ($bonusApplied || !is_numeric($arResult['PRICE_WITHOUT_DISCOUNT_VALUE'] ?? null)) {
            return;
        }

        $discount = PriceMaths::roundByFormatCurrency(
            $order->getDiscountPrice() + ((float) $arResult['PRICE_WITHOUT_DISCOUNT_VALUE'] - $goods),
            $currency
        );
        $discountFormatted = \SaleFormatCurrency($discount, $currency);
        $arResult['DISCOUNT_PRICE'] = $discount;
        $arResult['DISCOUNT_PRICE_FORMATED'] = $discountFormatted;
        if (isset($arResult['JS_DATA']['TOTAL']) && is_array($arResult['JS_DATA']['TOTAL'])) {
            $arResult['JS_DATA']['TOTAL']['DISCOUNT_PRICE'] = $discount;
            $arResult['JS_DATA']['TOTAL']['DISCOUNT_PRICE_FORMATED'] = $discountFormatted;
        }
    }

    /**
     * @param array<string, mixed> $arResult
     */
    private static function syncCheckedPrice(array &$arResult, int $deliveryId, float $price, string $formatted): void
    {
        foreach (['JS_DATA', null] as $bucket) {
            $deliveries = $bucket === null ? ($arResult['DELIVERY'] ?? null) : ($arResult['JS_DATA']['DELIVERY'] ?? null);
            if (!is_array($deliveries)) {
                continue;
            }

            $key = self::deliveryKey($deliveries, $deliveryId);
            if ($key === null || !is_array($deliveries[$key])) {
                continue;
            }

            $deliveries[$key]['PRICE'] = $price;
            $deliveries[$key]['PRICE_FORMATED'] = $formatted;
            unset($deliveries[$key]['DELIVERY_DISCOUNT_PRICE'], $deliveries[$key]['DELIVERY_DISCOUNT_PRICE_FORMATED']);

            if ($bucket === null) {
                $arResult['DELIVERY'][$key] = $deliveries[$key];
            } else {
                $arResult['JS_DATA']['DELIVERY'][$key] = $deliveries[$key];
            }
        }
    }
}
