<?php

namespace Dnk\Stickers;

use Bitrix\Main\Loader;
use CIBlockElement;
use CIBlockPropertyEnum;

/**
 * Merge-safe read/write for multiple list HIT property by enum XML_ID.
 */
final class HitProperty
{
    /** @var array<string, int|null> */
    private static array $enumIdCache = [];

    /**
     * @return list<int>
     */
    public static function getEnumIds(int $iblockId, int $elementId, string $propertyCode): array
    {
        if ($iblockId <= 0 || $elementId <= 0 || $propertyCode === '' || !Loader::includeModule('iblock')) {
            return [];
        }

        $values = [];
        $rs = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => $propertyCode]);
        while ($row = $rs->Fetch()) {
            $value = $row['VALUE'] ?? null;
            if ($value === null || $value === '' || $value === false) {
                continue;
            }
            $id = (int) $value;
            if ($id > 0) {
                $values[] = $id;
            }
        }

        return array_values(array_unique($values));
    }

    public static function getEnumIdByXmlId(int $iblockId, string $propertyCode, string $xmlId): ?int
    {
        $xmlId = trim($xmlId);
        if ($iblockId <= 0 || $propertyCode === '' || $xmlId === '' || !Loader::includeModule('iblock')) {
            return null;
        }

        $cacheKey = $iblockId . '|' . $propertyCode . '|' . strtoupper($xmlId);
        if (array_key_exists($cacheKey, self::$enumIdCache)) {
            return self::$enumIdCache[$cacheKey];
        }

        $id = null;

        // Prefer Aspro-compatible lookup by EXTERNAL_ID / XML_ID on property enum.
        $rs = \CIBlockProperty::GetPropertyEnum(
            $propertyCode,
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'EXTERNAL_ID' => $xmlId,
            ]
        );
        $row = $rs->Fetch();
        if (is_array($row) && (int) ($row['ID'] ?? 0) > 0) {
            $id = (int) $row['ID'];
        }

        if ($id === null) {
            $rs2 = CIBlockPropertyEnum::GetList(
                ['SORT' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    'CODE' => $propertyCode,
                    'XML_ID' => $xmlId,
                ]
            );
            $row2 = $rs2->Fetch();
            if (is_array($row2) && (int) ($row2['ID'] ?? 0) > 0) {
                $id = (int) $row2['ID'];
            }
        }

        self::$enumIdCache[$cacheKey] = $id;

        return $id;
    }

    public static function hasSticker(int $iblockId, int $elementId, string $propertyCode, string $xmlId): bool
    {
        $enumId = self::getEnumIdByXmlId($iblockId, $propertyCode, $xmlId);
        if ($enumId === null) {
            return false;
        }

        return in_array($enumId, self::getEnumIds($iblockId, $elementId, $propertyCode), true);
    }

    /**
     * Adds sticker enum to current HIT values (merge). Returns true if property changed.
     */
    public static function addSticker(int $iblockId, int $elementId, string $propertyCode, string $xmlId): bool
    {
        $enumId = self::getEnumIdByXmlId($iblockId, $propertyCode, $xmlId);
        if ($enumId === null) {
            return false;
        }

        $current = self::getEnumIds($iblockId, $elementId, $propertyCode);
        if (in_array($enumId, $current, true)) {
            return false;
        }

        $current[] = $enumId;
        self::setEnumIds($iblockId, $elementId, $propertyCode, $current);

        return true;
    }

    /**
     * Removes sticker enum from current HIT values (merge). Returns true if property changed.
     */
    public static function removeSticker(int $iblockId, int $elementId, string $propertyCode, string $xmlId): bool
    {
        $enumId = self::getEnumIdByXmlId($iblockId, $propertyCode, $xmlId);
        if ($enumId === null) {
            return false;
        }

        $current = self::getEnumIds($iblockId, $elementId, $propertyCode);
        $next = array_values(array_filter(
            $current,
            static fn (int $id): bool => $id !== $enumId
        ));

        if (count($next) === count($current)) {
            return false;
        }

        self::setEnumIds($iblockId, $elementId, $propertyCode, $next);

        return true;
    }

    /**
     * @param list<int> $enumIds
     */
    public static function setEnumIds(int $iblockId, int $elementId, string $propertyCode, array $enumIds): void
    {
        if ($iblockId <= 0 || $elementId <= 0 || $propertyCode === '' || !Loader::includeModule('iblock')) {
            return;
        }

        $values = array_values(array_unique(array_filter(
            array_map('intval', $enumIds),
            static fn (int $id): bool => $id > 0
        )));

        StickerService::beginInternalWrite();
        try {
            CIBlockElement::SetPropertyValuesEx(
                $elementId,
                $iblockId,
                [$propertyCode => $values !== [] ? $values : false]
            );
        } finally {
            StickerService::endInternalWrite();
        }
    }
}
