<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Awz\Europost\PvzTable;
use Bitrix\Main\Loader;

/**
 * Точки ПВЗ Европочты для webhook доставок IMSHOP.
 */
final class ImshopEuropostPickup extends ImshopPostalPickup
{
    /** @var list<int> */
    private const DELIVERY_IDS = [18, 16];

    public static function supports(int $deliveryId): bool
    {
        return in_array($deliveryId, self::DELIVERY_IDS, true);
    }

    public static function locations(string $town): array
    {
        if ($town === '' || !Loader::includeModule('awz.europost')) {
            return [];
        }

        return self::collect(PvzTable::class, ['=TOWN' => $town], 'europost');
    }
}
