<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Awz\Belpost\PvzTable as BelpostPvzTable;
use Awz\Europost\PvzTable as EuropostPvzTable;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location\LocationTable;

/**
 * Точки ПВЗ Европочты и Белпочты для webhook доставок IMSHOP.
 */
final class ImshopPickupEvents
{
    /** @var list<int> */
    private const EUROPOST_DELIVERY_IDS = [18, 16];

    /** @var list<int> */
    private const BELPOST_DELIVERY_IDS = [25, 24];

    private const BELPOST_CITY_TYPE = 'г';

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
            ['LOCATIONS' => $town === '' ? [] : self::locations($carrier, $town)]
        );
    }

    private static function carrier(int $deliveryId): ?string
    {
        if (in_array($deliveryId, self::EUROPOST_DELIVERY_IDS, true)) {
            return 'europost';
        }
        if (in_array($deliveryId, self::BELPOST_DELIVERY_IDS, true)) {
            return 'belpost';
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function locations(string $carrier, string $town): array
    {
        if ($carrier === 'europost') {
            if (!Loader::includeModule('awz.europost')) {
                return [];
            }

            return self::points(EuropostPvzTable::class, ['=TOWN' => $town], 'europost');
        }

        if (!Loader::includeModule('awz.belpost')) {
            return [];
        }

        return self::points(BelpostPvzTable::class, self::belpostFilter($town), 'belpost');
    }

    /**
     * @param class-string<BelpostPvzTable|EuropostPvzTable> $table
     * @param array<string, string> $filter
     * @return list<array<string, mixed>>
     */
    private static function points(string $table, array $filter, string $prefix): array
    {
        $locations = [];
        $rows = $table::getList([
            'select' => ['*'],
            'filter' => $filter,
        ]);
        while ($row = $rows->fetch()) {
            if (!is_array($row)) {
                continue;
            }
            $location = self::location($row, $prefix);
            if ($location !== null) {
                $locations[] = $location;
            }
        }

        return $locations;
    }

    /**
     * @return array<string, string>
     */
    private static function belpostFilter(string $town): array
    {
        $district = BelpostPvzTable::getList([
            'select' => ['DISTRICT'],
            'filter' => ['=TOWN' => $town, '=CITY_TYPE' => self::BELPOST_CITY_TYPE],
            'limit' => 1,
        ])->fetch();
        if (is_array($district)) {
            $name = trim((string) ($district['DISTRICT'] ?? ''));
            if ($name !== '') {
                return ['=DISTRICT' => $name];
            }
        }

        return ['=TOWN' => $town];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private static function location(array $row, string $prefix): ?array
    {
        $prm = $row['PRM'] ?? null;
        if (!is_array($prm)) {
            return null;
        }

        $id = trim((string) ($row['PVZ_ID'] ?? ''));
        $title = self::plainText((string) ($prm['name'] ?? ''));
        $address = self::plainText((string) ($prm['full_address'] ?? ''));
        $city = self::plainText((string) ($row['TOWN'] ?? ''));
        $lat = trim((string) ($prm['latitude'] ?? ''));
        $lon = trim((string) ($prm['longitude'] ?? ''));
        if ($id === '' || $title === '' || $address === '' || $city === '' || !self::hasCoordinates($lat, $lon)) {
            return null;
        }

        $location = [
            'id' => $prefix . ':' . $id,
            'title' => $title,
            'address' => $address,
            'city' => $city,
            'lat' => $lat,
            'lon' => $lon,
        ];

        $time = self::plainText((string) ($prm['info'] ?? ''));
        if ($time !== '') {
            $location['time'] = $time;
        }

        return $location;
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

    private static function hasCoordinates(string $lat, string $lon): bool
    {
        if ($lat === '' || $lon === '' || !is_numeric($lat) || !is_numeric($lon)) {
            return false;
        }

        return (float) $lat !== 0.0 || (float) $lon !== 0.0;
    }

    private static function plainText(string $value): string
    {
        $text = trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
}
