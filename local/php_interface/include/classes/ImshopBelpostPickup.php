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

    public static function supports(int $deliveryId): bool
    {
        return in_array($deliveryId, self::DELIVERY_IDS, true);
    }

    public static function locations(string $town): array
    {
        if ($town === '' || !Loader::includeModule('awz.belpost')) {
            return [];
        }

        return self::collect(PvzTable::class, ['=TOWN' => $town], 'belpost');
    }
}
