<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Awz\Belpost\PvzTable;
use Bitrix\Main\Loader;

/**
 * Точки ПВЗ Белпочты для webhook доставок IMSHOP.
 */
final class ImshopBelpostPickup extends ImshopPostalPickup
{
    /** @var list<int> */
    private const DELIVERY_IDS = [25, 24];

    private const CITY_TYPE = 'г';

    public static function supports(int $deliveryId): bool
    {
        return in_array($deliveryId, self::DELIVERY_IDS, true);
    }

    public static function locations(string $town): array
    {
        if ($town === '' || !Loader::includeModule('awz.belpost')) {
            return [];
        }

        return self::collect(PvzTable::class, self::filter($town), 'belpost');
    }

    /**
     * @return array<string, string>
     */
    private static function filter(string $town): array
    {
        $district = PvzTable::getList([
            'select' => ['DISTRICT'],
            'filter' => ['=TOWN' => $town, '=CITY_TYPE' => self::CITY_TYPE],
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
}
