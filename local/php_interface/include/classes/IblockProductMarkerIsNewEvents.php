<?php

namespace Dnk\PhpInterface;

use CIBlockElement;
use CIBlockPropertyEnum;

/**
 * Синхронизация свойства IS_NEW со списком MARKER_DLYA_SAYTA (VALUE «Новинка» → enum IS_NEW).
 */
final class IblockProductMarkerIsNewEvents
{
    /** @var list<string> */
    private const IS_NEW_ENUM_XML_IDS = ['Y', 'NEW', 'YES'];

    private const MARKER_NOVINKA_VALUE = 'Новинка';

    private const MARKER_NOVINKA_XML_ID = 'NEW';

    public static function onAfterIBlockElementAdd(array &$arFields): void
    {
        if (isset($arFields['RESULT']) && $arFields['RESULT'] === false) {
            return;
        }
        self::syncAfterSave($arFields);
    }

    public static function onAfterIBlockElementUpdate(array &$arFields): void
    {
        if (empty($arFields['RESULT'])) {
            return;
        }
        self::syncAfterSave($arFields);
    }

    /**
     * Заполняет IS_NEW из MARKER_DLYA_SAYTA. Для массового прогона и событий.
     *
     * @return bool true, если выполнено сохранение свойства IS_NEW
     */
    public static function syncIsNewFromMarkerForElement(int $iblockId, int $elementId): bool
    {
        if (!defined('DNK_CATALOG_IBLOCK_ID') || $iblockId !== (int) DNK_CATALOG_IBLOCK_ID || $elementId <= 0) {
            return false;
        }
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }

        $propMarker = Utils::getIblockPropertyByCode($iblockId, 'MARKER_DLYA_SAYTA');
        $propIsNew = Utils::getIblockPropertyByCode($iblockId, 'IS_NEW');
        if ($propMarker === null || $propIsNew === null) {
            return false;
        }
        if ((string) ($propMarker['PROPERTY_TYPE'] ?? '') !== 'L' || (string) ($propIsNew['PROPERTY_TYPE'] ?? '') !== 'L') {
            return false;
        }
        if ((string) ($propMarker['MULTIPLE'] ?? 'N') === 'Y') {
            return false;
        }
        if ((string) ($propIsNew['MULTIPLE'] ?? 'N') === 'Y') {
            return false;
        }

        $markerPropId = (int) ($propMarker['ID'] ?? 0);
        if ($markerPropId <= 0) {
            return false;
        }

        $markerEnumId = self::getSingleMarkerEnumId($iblockId, $elementId, $markerPropId);
        $isNovinka = false;

        if ($markerEnumId !== null) {
            $markerEnumRow = CIBlockPropertyEnum::GetByID($markerEnumId);
            $isNovinka = self::isMarkerNovinka(is_array($markerEnumRow) ? $markerEnumRow : null);
        }

        if ($isNovinka) {
            $isNewEnumId = self::resolveIsNewEnumId($iblockId);
            if ($isNewEnumId === null) {
                return false;
            }

            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
                'IS_NEW' => $isNewEnumId,
            ]);
        } else {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
                'IS_NEW' => false,
            ]);
        }

        return true;
    }

    /**
     * @param array<string, mixed> $arFields
     */
    private static function syncAfterSave(array $arFields): void
    {
        self::syncIsNewFromMarkerForElement(
            (int) ($arFields['IBLOCK_ID'] ?? 0),
            (int) ($arFields['ID'] ?? 0)
        );
    }

    private static function resolveIsNewEnumId(int $iblockId): ?int
    {
        foreach (self::IS_NEW_ENUM_XML_IDS as $xmlId) {
            $enumId = Utils::getIblockListPropertyEnumIdByXmlId($iblockId, 'IS_NEW', $xmlId);
            if ($enumId !== null) {
                return $enumId;
            }
        }

        return null;
    }

    private static function getSingleMarkerEnumId(int $iblockId, int $elementId, int $propertyId): ?int
    {
        $res = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['ID' => $propertyId]);
        while ($row = $res->Fetch()) {
            $v = $row['VALUE'] ?? null;
            $id = Utils::coerceIblockListEnumId(is_array($v) ? ($v[0] ?? null) : $v);
            if ($id !== null) {
                return $id;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed>|null $markerEnumRow результат CIBlockPropertyEnum::GetByID
     */
    private static function isMarkerNovinka(?array $markerEnumRow): bool
    {
        if ($markerEnumRow === null) {
            return false;
        }

        $value = trim((string) ($markerEnumRow['VALUE'] ?? ''));
        if ($value !== '' && mb_strtolower($value, 'UTF-8') === mb_strtolower(self::MARKER_NOVINKA_VALUE, 'UTF-8')) {
            return true;
        }

        $xmlId = trim((string) ($markerEnumRow['XML_ID'] ?? ''));

        return strcasecmp($xmlId, self::MARKER_NOVINKA_XML_ID) === 0;
    }
}
