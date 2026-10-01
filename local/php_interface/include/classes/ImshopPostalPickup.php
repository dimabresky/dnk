<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

/**
 * Общая сборка точки IMSHOP из строки ПВЗ Европочты или Белпочты.
 */
abstract class ImshopPostalPickup
{
    abstract public static function supports(int $deliveryId): bool;

    /**
     * @return list<array<string, mixed>>
     */
    abstract public static function locations(string $town): array;

    /**
     * @param class-string $table
     * @param array<string, string> $filter
     * @return list<array<string, mixed>>
     */
    protected static function collect(string $table, array $filter, string $prefix): array
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
