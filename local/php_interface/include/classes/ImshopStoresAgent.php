<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use CIBlockElement;
use CIBlockSection;

/**
 * Агент: карта магазинов IMSHOP в upload/sales.json.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\ImshopStoresAgent::run();
 * Интервал — DNK_IMSHOP_STORES_AGENT_INTERVAL (сек).
 */
final class ImshopStoresAgent
{
    private const FEED_FILENAME = 'sales.json';

    private const FEED_OUTPUT_DIR = 'upload';

    public static function run(): string
    {
        $return = '\\Dnk\\PhpInterface\\ImshopStoresAgent::run();';

        self::write(self::stores());

        return $return;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function stores(): array
    {
        $iblockId = defined('DNK_PICKUP_STORES_IBLOCK_ID') ? (int) DNK_PICKUP_STORES_IBLOCK_ID : 0;
        if ($iblockId <= 0 || !Loader::includeModule('iblock')) {
            return [];
        }

        $dbList = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'NAME' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            false
        );
        if (!is_object($dbList)) {
            return [];
        }

        $rows = [];
        $sectionIds = [];
        while ($element = $dbList->GetNextElement()) {
            $fields = $element->GetFields();
            $name = trim((string) ($fields['~NAME'] ?? $fields['NAME'] ?? ''));
            $id = (int) ($fields['ID'] ?? 0);
            if ($id <= 0 || $name === '') {
                continue;
            }

            $sectionId = (int) ($fields['IBLOCK_SECTION_ID'] ?? 0);
            if ($sectionId > 0) {
                $sectionIds[$sectionId] = $sectionId;
            }

            $rows[] = [
                'id' => $id,
                'name' => $name,
                'sectionId' => $sectionId,
                'properties' => $element->GetProperties(),
            ];
        }

        $cities = self::sectionNames($iblockId, array_values($sectionIds));
        $stores = [];
        foreach ($rows as $row) {
            $stores[] = self::store($row, $cities);
        }

        return $stores;
    }

    /**
     * @param list<int> $sectionIds
     * @return array<int, string>
     */
    private static function sectionNames(int $iblockId, array $sectionIds): array
    {
        if ($sectionIds === []) {
            return [];
        }

        $names = [];
        $sections = CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'ID' => $sectionIds,
            ],
            false,
            ['ID', 'NAME']
        );
        while ($section = $sections->Fetch()) {
            if (!is_array($section)) {
                continue;
            }
            $id = (int) ($section['ID'] ?? 0);
            $name = trim((string) ($section['NAME'] ?? ''));
            if ($id > 0 && $name !== '') {
                $names[$id] = $name;
            }
        }

        return $names;
    }

    /**
     * @param array{id: int, name: string, sectionId: int, properties: array<string, mixed>} $row
     * @param array<int, string> $cities
     * @return array<string, string>
     */
    private static function store(array $row, array $cities): array
    {
        $properties = $row['properties'];
        $store = [
            'id' => (string) $row['id'],
            'name' => $row['name'],
            'address' => self::text(self::property($properties, 'ADDRESS')),
            'line1' => self::schedule(is_array($properties['SCHEDULE'] ?? null) ? $properties['SCHEDULE'] : []),
            'line2' => '',
        ];

        $city = $cities[$row['sectionId']] ?? '';
        if ($city !== '') {
            $store['city'] = $city;
        }

        $phone = self::join(self::property($properties, 'PHONE'));
        if ($phone !== '') {
            $store['phone'] = $phone;
        }

        $subway = self::join(self::property($properties, 'METRO'));
        if ($subway !== '') {
            $store['subway'] = $subway;
        }

        $coords = self::coords(self::text(self::property($properties, 'MAP')));
        if ($coords !== null) {
            $store['lat'] = $coords['lat'];
            $store['lon'] = $coords['lon'];
        }

        return $store;
    }

    /**
     * @param array<string, mixed> $properties
     */
    private static function property(array $properties, string $code): mixed
    {
        $property = $properties[$code] ?? null;
        if (!is_array($property)) {
            return '';
        }

        return $property['~VALUE'] ?? $property['VALUE'] ?? '';
    }

    /**
     * @param array<string, mixed> $property
     */
    private static function schedule(array $property): string
    {
        $value = $property['~VALUE'] ?? $property['VALUE'] ?? '';
        if (is_array($value) && array_key_exists('TEXT', $value)) {
            return self::text($value['TEXT']);
        }

        return self::join($value);
    }

    private static function join(mixed $value): string
    {
        if (!is_array($value)) {
            return self::text($value);
        }
        if (array_key_exists('TEXT', $value)) {
            return self::text($value['TEXT']);
        }

        $parts = [];
        foreach ($value as $item) {
            if (is_array($item) && array_key_exists('TEXT', $item)) {
                $item = $item['TEXT'];
            }
            $text = self::text($item);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        return implode(', ', $parts);
    }

    private static function text(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    /**
     * @return array{lat: string, lon: string}|null
     */
    private static function coords(string $map): ?array
    {
        if (preg_match('/^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$/', $map, $matches) !== 1) {
            return null;
        }

        $lat = (float) $matches[1];
        $lon = (float) $matches[2];
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return null;
        }

        return [
            'lat' => number_format($lat, 6, '.', ''),
            'lon' => number_format($lon, 6, '.', ''),
        ];
    }

    /**
     * @param list<array<string, string>> $stores
     */
    private static function write(array $stores): void
    {
        $outputDir = Utils::resolveDocumentRootSubdir(self::FEED_OUTPUT_DIR);
        if (!is_dir($outputDir) && !CheckDirPath($outputDir . '/')) {
            return;
        }

        $json = json_encode($stores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return;
        }

        $targetPath = $outputDir . '/' . self::FEED_FILENAME;
        $tempPath = $targetPath . '.tmp';
        if (file_put_contents($tempPath, $json . "\n") === false) {
            return;
        }

        rename($tempPath, $targetPath);
    }
}
