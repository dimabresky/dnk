<?php

namespace Dnk\PhpInterface;

use CIBlockElement;
use CIBlockPropertyEnum;

/**
 * Синхронизация свойства HIT со списком MARKER_DLYA_SAYTA (VALUE → XML_ID варианта HIT).
 * NEW не выставляется отсюда (модуль dnk.stickers).
 *
 * Поведение (единое правило):
 * - в HIT не больше одного управляемого стикера (RECOMMEND / HIT / STOCK), и только того, что соответствует маркеру;
 * - «Хит» / «Скидка» / «СПЕЦИАЛЬНОЕ ПРЕДЛОЖЕНИЕ» — снять остальные управляемые, добавить целевой;
 * - пустой маркер / «Новинка» / несмапленное значение — снять все управляемые; NEW и прочие не трогаем.
 */
final class IblockProductMarkerHitEvents
{
    /** Маркер VALUE (после trim) → XML_ID варианта свойства HIT (без NEW). */
    private const MARKER_VALUE_TO_HIT_XML_ID = [
        'СПЕЦИАЛЬНОЕ ПРЕДЛОЖЕНИЕ' => 'RECOMMEND',
        'Хит' => 'HIT',
        'Скидка' => 'STOCK',
    ];

    /** Стикеры, которыми управляет sync из маркера (не трогаем NEW и прочие). */
    private const MANAGED_HIT_XML_IDS = ['RECOMMEND', 'HIT', 'STOCK'];

    /** @var array<int, array<string, int>> iblockId => [xmlId => enumId] */
    private static array $managedHitEnumIdsByXmlCache = [];

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
     * Синхронизирует HIT из MARKER_DLYA_SAYTA. Для массового прогона и событий.
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
        $markerEnumRow = null;
        if ($markerEnumId !== null) {
            $row = CIBlockPropertyEnum::GetByID($markerEnumId);
            $markerEnumRow = is_array($row) ? $row : null;
        }

        $activeHitXmlId = self::resolveManagedHitXmlIdFromMarker($markerEnumRow);
        $managedByXml = self::getManagedHitEnumIdsByXml($iblockId);
        $activeEnumId = ($activeHitXmlId !== null && isset($managedByXml[$activeHitXmlId]))
            ? $managedByXml[$activeHitXmlId]
            : null;

        $current = self::getCurrentHitEnumIds($iblockId, $elementId);
        $next = self::buildNextHitKeepingUnmanaged(
            $current,
            self::enumIdsToSet($managedByXml),
            $activeEnumId
        );

        return self::saveHitIfChanged($elementId, $iblockId, $current, $next);
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

    /**
     * XML_ID управляемого HIT-стикера по маркеру, либо null (очистить все управляемые).
     *
     * @param array<string, mixed>|null $markerEnumRow
     */
    private static function resolveManagedHitXmlIdFromMarker(?array $markerEnumRow): ?string
    {
        if ($markerEnumRow === null || Utils::isMarkerNovinkaEnumRow($markerEnumRow)) {
            return null;
        }

        return self::resolveHitXmlIdFromMarker($markerEnumRow);
    }

    /**
     * @return array<string, int> xmlId => enumId
     */
    private static function getManagedHitEnumIdsByXml(int $iblockId): array
    {
        if (isset(self::$managedHitEnumIdsByXmlCache[$iblockId])) {
            return self::$managedHitEnumIdsByXmlCache[$iblockId];
        }

        $allowed = [];
        foreach (self::MANAGED_HIT_XML_IDS as $xmlId) {
            $allowed[strtoupper($xmlId)] = $xmlId;
        }

        $map = [];
        foreach (Utils::buildIblockListPropertyEnumXmlIdMap($iblockId, 'HIT') as $enumId => $xmlId) {
            $key = strtoupper(trim((string) $xmlId));
            if (isset($allowed[$key])) {
                $map[$allowed[$key]] = (int) $enumId;
            }
        }

        self::$managedHitEnumIdsByXmlCache[$iblockId] = $map;

        return $map;
    }

    /**
     * @param array<string, int> $managedByXml xmlId => enumId
     *
     * @return array<int, true>
     */
    private static function enumIdsToSet(array $managedByXml): array
    {
        $set = [];
        foreach ($managedByXml as $enumId) {
            $set[(int) $enumId] = true;
        }

        return $set;
    }

    /**
     * @return list<int>
     */
    private static function getCurrentHitEnumIds(int $iblockId, int $elementId): array
    {
        $current = [];
        $res = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => 'HIT']);
        while ($row = $res->Fetch()) {
            $v = $row['VALUE'] ?? null;
            $id = Utils::coerceIblockListEnumId(is_array($v) ? ($v[0] ?? null) : $v);
            if ($id !== null) {
                $current[] = $id;
            }
        }

        return array_values(array_unique($current));
    }

    /**
     * @param list<int> $current
     * @param array<int, true> $managedEnumIds
     */
    private static function buildNextHitKeepingUnmanaged(array $current, array $managedEnumIds, ?int $targetEnumId): array
    {
        $next = [];
        foreach ($current as $enumId) {
            if (!isset($managedEnumIds[$enumId])) {
                $next[] = $enumId;
            }
        }

        if ($targetEnumId !== null && !in_array($targetEnumId, $next, true)) {
            $next[] = $targetEnumId;
        }

        return array_values($next);
    }

    /**
     * @param list<int> $current
     * @param list<int> $next
     */
    private static function saveHitIfChanged(int $elementId, int $iblockId, array $current, array $next): bool
    {
        $sortedCurrent = $current;
        sort($sortedCurrent);
        $sortedNext = $next;
        sort($sortedNext);
        if ($sortedCurrent === $sortedNext) {
            return false;
        }

        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
            'HIT' => $next !== [] ? $next : false,
        ]);

        return true;
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
        if (strcasecmp($xmlId, Utils::MARKER_NOVINKA_XML_ID) === 0) {
            return null;
        }

        $allowed = array_unique(array_values(self::MARKER_VALUE_TO_HIT_XML_ID));
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
