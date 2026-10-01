<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;

/**
 * Выбирает службу ПВЗ и отдаёт её точки в webhook доставок IMSHOP.
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
