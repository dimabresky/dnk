<?php

namespace Dnk\PhpInterface;

use CIBlockElement;
use CIBlockProperty;
use CIBlockPropertyEnum;

/**
 * Синхронизация привязки BRAND (элемент инфоблока брендов) со списком BREND по XML_ID варианта списка.
 */
final class IblockProductBrandEvents
{
    public static function onAfterIBlockElementAdd(array &$arFields): void
    {
        if (isset($arFields['RESULT']) && $arFields['RESULT'] === false) {
            return;
        }
        self::syncBrandFromBrendList($arFields);
    }

    public static function onAfterIBlockElementUpdate(array &$arFields): void
    {
        if (empty($arFields['RESULT'])) {
            return;
        }
        self::syncBrandFromBrendList($arFields);
    }

    /**
     * Заполняет BRAND из BREND по XML_ID, если BRAND пустой. Для массовой миграции и событий.
     *
     * @return bool true, если вызвано сохранение значения BRAND
     */
    public static function syncBrandFromBrendListForElement(int $iblockId, int $elementId): bool
    {
        if (!defined('DNK_CATALOG_IBLOCK_ID') || $iblockId !== (int) DNK_CATALOG_IBLOCK_ID || $elementId <= 0) {
            return false;
        }
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }

        $propBrand = self::getPropertyInfo($iblockId, 'BRAND');
        $propBrend = self::getPropertyInfo($iblockId, 'BREND');
        if ($propBrand === null || $propBrend === null) {
            return false;
        }
        if ((string)($propBrand['PROPERTY_TYPE'] ?? '') !== 'E' || (string)($propBrend['PROPERTY_TYPE'] ?? '') !== 'L') {
            return false;
        }

        $brandsIblockId = (int)($propBrand['LINK_IBLOCK_ID'] ?? 0);
        if ($brandsIblockId <= 0) {
            return false;
        }

        if (self::hasBrandPropertyValues($iblockId, $elementId, (int)($propBrand['ID'] ?? 0))) {
            return false;
        }

        $enumId = self::getFirstBrendEnumId($iblockId, $elementId, (int)($propBrend['ID'] ?? 0));
        if ($enumId === null) {
            return false;
        }

        $arEnum = CIBlockPropertyEnum::GetByID($enumId);
        if (!is_array($arEnum) || !isset($arEnum['XML_ID'])) {
            return false;
        }
        $listXmlId = trim((string)$arEnum['XML_ID']);
        if ($listXmlId === '') {
            return false;
        }

        $brandElementId = Utils::getIblockElementIdByXmlId($brandsIblockId, $listXmlId);
        if ($brandElementId === null || $brandElementId <= 0) {
            return false;
        }

        $isMultiple = (string)($propBrand['MULTIPLE'] ?? 'N') === 'Y';
        $value = $isMultiple ? [$brandElementId] : $brandElementId;

        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
            'BRAND' => $value,
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $arFields
     */
    private static function syncBrandFromBrendList(array $arFields): void
    {
        self::syncBrandFromBrendListForElement(
            (int)($arFields['IBLOCK_ID'] ?? 0),
            (int)($arFields['ID'] ?? 0)
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function getPropertyInfo(int $iblockId, string $code): ?array
    {
        $res = CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $code,
            ]
        );
        $row = $res->Fetch();

        return is_array($row) ? $row : null;
    }

    private static function hasBrandPropertyValues(int $iblockId, int $elementId, int $propertyId): bool
    {
        if ($propertyId <= 0) {
            return false;
        }
        $res = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['ID' => $propertyId]);
        while ($row = $res->Fetch()) {
            $v = $row['VALUE'] ?? null;
            if (is_array($v)) {
                foreach ($v as $one) {
                    if (self::isNonEmptyPropertyScalar($one)) {
                        return true;
                    }
                }
            } elseif (self::isNonEmptyPropertyScalar($v)) {
                return true;
            }
        }

        return false;
    }

    private static function isNonEmptyPropertyScalar(mixed $value): bool
    {
        if ($value === null || $value === false) {
            return false;
        }
        if (is_string($value) && trim($value) === '') {
            return false;
        }
        if (is_numeric($value) && (int)$value === 0) {
            return false;
        }

        return true;
    }

    private static function getFirstBrendEnumId(int $iblockId, int $elementId, int $propertyId): ?int
    {
        if ($propertyId <= 0) {
            return null;
        }
        $res = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['ID' => $propertyId]);
        while ($row = $res->Fetch()) {
            $v = $row['VALUE'] ?? null;
            if (is_array($v)) {
                foreach ($v as $one) {
                    $id = self::coercePropertyEnumId($one);
                    if ($id !== null) {
                        return $id;
                    }
                }
            } else {
                $id = self::coercePropertyEnumId($v);
                if ($id !== null) {
                    return $id;
                }
            }
        }

        return null;
    }

    private static function coercePropertyEnumId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === false) {
            return null;
        }
        $id = (int)$value;

        return $id > 0 ? $id : null;
    }
}
