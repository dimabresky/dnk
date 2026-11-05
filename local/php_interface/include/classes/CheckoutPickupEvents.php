<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\ExtraServices\Manager as ExtraServicesManager;
use Bitrix\Sale\Delivery\Services\Base as DeliveryService;
use Bitrix\Sale\Delivery\Services\EmptyDeliveryService;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;

/**
 * Убирает самовывоз из чекаута, если для карты не осталось складов выдачи.
 */
final class CheckoutPickupEvents
{
    /**
     * id профиля «Самовывоз», который видит чекаут.
     *
     * @var list<int>
     */
    private const PICKUP_DELIVERY_IDS = [];

    /**
     * @param mixed $order
     * @param mixed $arUserResult
     * @param mixed $request
     * @param mixed $arParams
     * @param mixed $arResult
     * @param mixed $arDeliveryServiceAll
     * @param mixed $arPaySystemServiceAll
     */
    public static function onSaleComponentOrderCreated(
        $order,
        &$arUserResult,
        $request,
        &$arParams,
        &$arResult,
        &$arDeliveryServiceAll,
        &$arPaySystemServiceAll,
    ): void {
        if (!$order instanceof Order || !is_array($arUserResult) || !is_array($arDeliveryServiceAll)) {
            return;
        }

        $siteId = (string) $order->getSiteId();
        $removed = [];
        foreach ($arDeliveryServiceAll as $deliveryId => $service) {
            $deliveryId = (int) $deliveryId;
            if (!in_array($deliveryId, self::PICKUP_DELIVERY_IDS, true)) {
                continue;
            }
            if (self::visibleStoreIds($deliveryId, $siteId) !== []) {
                continue;
            }

            unset($arDeliveryServiceAll[$deliveryId]);
            $removed[$deliveryId] = true;
        }

        if ($removed === []) {
            return;
        }

        $shipment = self::currentShipment($order);
        if (!$shipment instanceof Shipment || !isset($removed[(int) $shipment->getDeliveryId()])) {
            return;
        }

        self::replaceShipment($order, $shipment, $arDeliveryServiceAll, $arUserResult);
    }

    /**
     * @return list<int>
     */
    private static function visibleStoreIds(int $deliveryId, string $siteId): array
    {
        $configured = self::configuredStoreIds($deliveryId);
        if ($configured === [] || !Loader::includeModule('catalog')) {
            return [];
        }

        $visible = [];
        $rows = \CCatalogStore::GetList(
            [],
            [
                'ACTIVE' => 'Y',
                'ID' => $configured,
                'ISSUING_CENTER' => 'Y',
                '+SITE_ID' => $siteId,
            ],
            false,
            false,
            ['ID']
        );
        while ($row = $rows->Fetch()) {
            $storeId = (int) ($row['ID'] ?? 0);
            if ($storeId > 0) {
                $visible[] = $storeId;
            }
        }

        return $visible;
    }

    /**
     * @return list<int>
     */
    private static function configuredStoreIds(int $deliveryId): array
    {
        $stores = ExtraServicesManager::getStoresList($deliveryId);
        if (!is_array($stores)) {
            return [];
        }

        $ids = [];
        foreach ($stores as $storeId) {
            if (is_numeric($storeId) && (int) $storeId > 0) {
                $ids[] = (int) $storeId;
            }
        }

        return $ids;
    }

    private static function currentShipment(Order $order): ?Shipment
    {
        foreach ($order->getShipmentCollection() as $shipment) {
            if (!$shipment->isSystem()) {
                return $shipment;
            }
        }

        return null;
    }

    /**
     * @param array<int, DeliveryService> $services
     * @param array<string, mixed> $arUserResult
     */
    private static function replaceShipment(Order $order, Shipment $shipment, array $services, array &$arUserResult): void
    {
        $delivery = reset($services);
        if ($delivery instanceof DeliveryService) {
            self::assignDelivery($order, $shipment, $delivery, $arUserResult);

            return;
        }

        self::assignEmpty($order, $shipment, $arUserResult);
    }

    /**
     * @param array<string, mixed> $arUserResult
     */
    private static function assignDelivery(
        Order $order,
        Shipment $shipment,
        DeliveryService $delivery,
        array &$arUserResult,
    ): void {
        $deliveryId = (int) $delivery->getId();
        $name = $delivery->isProfile() ? $delivery->getNameWithParent() : $delivery->getName();

        $order->isStartField();
        $shipment->setFields([
            'DELIVERY_ID' => $deliveryId,
            'DELIVERY_NAME' => $name,
            'CURRENCY' => $order->getCurrency(),
        ]);
        $arUserResult['DELIVERY_ID'] = $deliveryId;

        $storeIds = self::configuredStoreIds($deliveryId);
        $buyerStore = (int) ($arUserResult['BUYER_STORE'] ?? 0);
        if ($storeIds !== [] && !in_array($buyerStore, $storeIds, true)) {
            $buyerStore = $storeIds[0];
        }
        if ($storeIds === []) {
            $buyerStore = 0;
        }
        $arUserResult['BUYER_STORE'] = $buyerStore;
        $shipment->setStoreId($buyerStore);

        $order->getShipmentCollection()->calculateDelivery();
        $order->doFinalAction(true);
    }

    /**
     * @param array<string, mixed> $arUserResult
     */
    private static function assignEmpty(Order $order, Shipment $shipment, array &$arUserResult): void
    {
        $service = DeliveryManager::getById(EmptyDeliveryService::getEmptyDeliveryServiceId());
        if (!is_array($service)) {
            return;
        }

        $deliveryId = (int) ($service['ID'] ?? 0);
        $order->isStartField();
        $shipment->setFields([
            'DELIVERY_ID' => $deliveryId,
            'DELIVERY_NAME' => (string) ($service['NAME'] ?? ''),
            'CURRENCY' => $order->getCurrency(),
        ]);
        $arUserResult['DELIVERY_ID'] = $deliveryId;
        $arUserResult['BUYER_STORE'] = 0;
        $shipment->setStoreId(0);
    }
}
