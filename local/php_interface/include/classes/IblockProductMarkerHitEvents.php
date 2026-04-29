<?php

namespace Dnk\PhpInterface;

use CIBlockElement;
use CIBlockPropertyEnum;

/**
 * Синхронизация свойства HIT со списком MARKER_DLYA_SAYTA (VALUE → XML_ID варианта HIT).
 */
final class IblockProductMarkerHitEvents
{
    /** Маркер VALUE (после trim) → XML_ID варианта свойства HIT */
    private const MARKER_VALUE_TO_HIT_XML_ID = [
        'СПЕЦИАЛЬНОЕ ПРЕДЛОЖЕНИЕ' => 'RECOMMEND',
        'Хит' => 'HIT',
        'Новинка' => 'NEW',
        'Скидка' => 'STOCK',
    ];

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
     * Заполняет HIT из MARKER_DLYA_SAYTA. Для массового прогона и событий.
     *
     * @return bool true, если выполнено сохранение свойства HIT
     */
    public static function syncHitFromMarkerForElement(int $iblockId, int $elementId): bool
    {
        if (!defined('DNK_CATALOG_IBLOCK_ID') || $iblockId !== (int) DNK_CATALOG_IBLOCK_ID || $elementId <= 0) {
            return false;
        }
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }

        $propMarker = Utils::getIblockPropertyByCode($iblockId, 'MARKER_DLYA_SAYTA');
        $propHit = Utils::getIblockPropertyByCode($iblockId, 'HIT');
        if ($propMarker === null || $propHit === null) {
            return false;
        }
        if ((string) ($propMarker['PROPERTY_TYPE'] ?? '') !== 'L' || (string) ($propHit['PROPERTY_TYPE'] ?? '') !== 'L') {
            return false;
        }
        if ((string) ($propMarker['MULTIPLE'] ?? 'N') === 'Y') {
            return false;
        }
        if ((string) ($propHit['MULTIPLE'] ?? 'N') !== 'Y') {
            return false;
        }

        $markerPropId = (int) ($propMarker['ID'] ?? 0);
        if ($markerPropId <= 0) {
            return false;
        }

        $markerEnumId = self::getSingleMarkerEnumId($iblockId, $elementId, $markerPropId);
        $hitXmlId = null;

        if ($markerEnumId !== null) {
            $markerEnumRow = CIBlockPropertyEnum::GetByID($markerEnumId);
            $hitXmlId = self::resolveHitXmlIdFromMarker(is_array($markerEnumRow) ? $markerEnumRow : null);
        }

        if ($hitXmlId === null || $hitXmlId === '') {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
                'HIT' => false,
            ]);

            return true;
        }

        $hitEnumId = Utils::getIblockListPropertyEnumIdByXmlId($iblockId, 'HIT', $hitXmlId);
        if ($hitEnumId === null) {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
                'HIT' => false,
            ]);

            return true;
        }

        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
            'HIT' => [$hitEnumId],
        ]);

        return true;
    }

    /**
     * @param array<string, mixed> $arFields
     */
    private static function syncAfterSave(array $arFields): void
    {
        self::syncHitFromMarkerForElement(
            (int) ($arFields['IBLOCK_ID'] ?? 0),
            (int) ($arFields['ID'] ?? 0)
        );
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
    private static function resolveHitXmlIdFromMarker(?array $markerEnumRow): ?string
    {
        if ($markerEnumRow === null) {
            return null;
        }

        $value = trim((string) ($markerEnumRow['VALUE'] ?? ''));
        if ($value !== '') {
            $normValue = self::normalizeUtf8Lower($value);
            foreach (self::MARKER_VALUE_TO_HIT_XML_ID as $label => $hitXml) {
                if ($normValue === self::normalizeUtf8Lower($label)) {
                    return $hitXml;
                }
            }
        }

        $xmlId = trim((string) ($markerEnumRow['XML_ID'] ?? ''));
        $allowed = ['RECOMMEND', 'HIT', 'NEW', 'STOCK'];
        foreach ($allowed as $one) {
            if (strcasecmp($xmlId, $one) === 0) {
                return $one;
            }
        }

        return null;
    }

    private static function normalizeUtf8Lower(string $value): string
    {
        return mb_strtolower($value, 'UTF-8');
    }
}
