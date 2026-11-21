<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Awz\Belpost\Helper as BelpostHelper;
use Awz\Belpost\PvzTable as BelpostPvzTable;
use Awz\Europost\Helper as EuropostHelper;
use Awz\Europost\PvzTable as EuropostPvzTable;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;
use Bitrix\Sale\Order;

/**
 * Точки ПВЗ для webhook доставок IMSHOP и запись выбранной точки в заказ.
 */
final class ImshopPickupEvents
{
    private const LANGUAGE_ID = 'ru';

    public static function onPickupLocationsBuild(Event $event): ?EventResult
    {
        $deliveryId = (int) $event->getParameter('DELIVERY_ID');
        $carrier = self::carrier($deliveryId);
        if ($carrier === null) {
            return null;
        }

        $town = self::townName(
            trim((string) $event->getParameter('LOCATION_CODE')),
            trim((string) $event->getParameter('CITY'))
        );

        return new EventResult(
            EventResult::SUCCESS,
            ['LOCATIONS' => $carrier::locations($town)]
        );
    }

    public static function onOrderBeforeSave(Event $event): void
    {
        $order = $event->getParameter('ORDER');
        $payload = $event->getParameter('PAYLOAD');
        if (!$order instanceof Order || !is_array($payload)) {
            return;
        }

        $pickupId = trim((string) ($payload['pickupLocationId'] ?? ''));
        if (!preg_match('/^(belpost|europost):(.+)$/', $pickupId, $match)) {
            return;
        }

        $deliveryId = (int) ($payload['delivery'] ?? 0);
        $pointId = trim($match[2]);
        if ($deliveryId <= 0 || $pointId === '') {
            return;
        }

        if ($match[1] === 'belpost' && ImshopBelpostPickup::supports($deliveryId)) {
            self::writePoint($order, $deliveryId, $pointId, 'awz.belpost', BelpostHelper::class, BelpostPvzTable::class);

            return;
        }

        if ($match[1] === 'europost' && ImshopEuropostPickup::supports($deliveryId)) {
            self::writePoint($order, $deliveryId, $pointId, 'awz.europost', EuropostHelper::class, EuropostPvzTable::class);
        }
    }

    /**
     * @param class-string<BelpostHelper|EuropostHelper> $helper
     * @param class-string<BelpostPvzTable|EuropostPvzTable> $table
     */
    private static function writePoint(
        Order $order,
        int $deliveryId,
        string $pointId,
        string $module,
        string $helper,
        string $table,
    ): void {
        if (!Loader::includeModule($module)) {
            return;
        }

        $properties = $order->getPropertyCollection();
        $point = $properties->getItemByOrderPropertyCode($helper::getPropPvzCode($deliveryId));
        if ($point !== null) {
            $point->setValue($pointId);
        }

        $addressCode = $helper::getPropAddress($deliveryId);
        if ($addressCode === '') {
            return;
        }

        $address = $properties->getItemByOrderPropertyCode($addressCode);
        $pointData = $table::getPvz($pointId);
        if ($address === null || !is_array($pointData)) {
            return;
        }

        $address->setValue($helper::formatPvzAddress($deliveryId, $pointData));
    }

    /**
     * @return class-string<ImshopPostalPickup>|null
     */
    private static function carrier(int $deliveryId): ?string
    {
        foreach ([ImshopEuropostPickup::class, ImshopBelpostPickup::class] as $carrier) {
            if ($carrier::supports($deliveryId)) {
                return $carrier;
            }
        }

        return null;
    }

    private static function townName(string $locationCode, string $fallback): string
    {
        $fromLocation = self::cityFromLocationCode($locationCode);
        if ($fromLocation !== '') {
            return $fromLocation;
        }

        return $fallback;
    }

    private static function cityFromLocationCode(string $locationCode): string
    {
        if ($locationCode === '' || !Loader::includeModule('sale')) {
            return '';
        }

        $rows = LocationTable::getList([
            'filter' => [
                '=CODE' => $locationCode,
                '=PARENTS.NAME.LANGUAGE_ID' => self::LANGUAGE_ID,
                '=PARENTS.TYPE.NAME.LANGUAGE_ID' => self::LANGUAGE_ID,
            ],
            'select' => [
                'I_NAME_LANG' => 'PARENTS.NAME.NAME',
                'I_TYPE_CODE' => 'PARENTS.TYPE.CODE',
            ],
            'order' => [
                'PARENTS.DEPTH_LEVEL' => 'asc',
            ],
        ]);

        $city = '';
        while ($row = $rows->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            if ((string) ($row['I_TYPE_CODE'] ?? '') === 'CITY') {
                $city = trim((string) ($row['I_NAME_LANG'] ?? ''));
            }
        }

        return $city;
    }
}
