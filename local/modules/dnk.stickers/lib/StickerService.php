<?php

namespace Dnk\Stickers;

use Bitrix\Main\Loader;
use CIBlockElement;

/**
 * Sticker remember / assign / expire operations by rule XML_ID.
 */
final class StickerService
{
    private static int $internalWriteDepth = 0;

    public static function beginInternalWrite(): void
    {
        ++self::$internalWriteDepth;
    }

    public static function endInternalWrite(): void
    {
        if (self::$internalWriteDepth > 0) {
            --self::$internalWriteDepth;
        }
    }

    public static function isInternalWrite(): bool
    {
        return self::$internalWriteDepth > 0;
    }

    /**
     * Remember products that already have the sticker in HIT (ASSIGNED_AT = now, no HIT change).
     *
     * @return array{scanned: int, tracked: int, skipped: int}
     */
    public static function rememberExisting(string $xmlId): array
    {
        $stats = ['scanned' => 0, 'tracked' => 0, 'skipped' => 0];
        if (!Config::isEnabled() || !Loader::includeModule('iblock')) {
            return $stats;
        }

        $xmlId = strtoupper(trim($xmlId));
        $iblockId = Config::getIblockId();
        $propertyCode = Config::getHitPropertyCode();
        $enumId = HitProperty::getEnumIdByXmlId($iblockId, $propertyCode, $xmlId);
        if ($iblockId <= 0 || $enumId === null) {
            return $stats;
        }

        $batchSize = Config::getBatchSize();
        $lastId = 0;

        while (true) {
            $rs = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => $iblockId,
                    '>ID' => $lastId,
                    'PROPERTY_' . $propertyCode => $enumId,
                ],
                false,
                ['nTopCount' => $batchSize],
                ['ID', 'IBLOCK_ID']
            );

            $countInBatch = 0;
            while ($item = $rs->Fetch()) {
                ++$countInBatch;
                $elementId = (int) ($item['ID'] ?? 0);
                $lastId = $elementId;
                if ($elementId <= 0) {
                    continue;
                }
                ++$stats['scanned'];

                if (AssignmentTracker::trackIfMissing(
                    $iblockId,
                    $elementId,
                    $xmlId,
                    Config::SOURCE_REMEMBER
                )) {
                    ++$stats['tracked'];
                } else {
                    ++$stats['skipped'];
                }
            }

            if ($countInBatch < $batchSize) {
                break;
            }
        }

        return $stats;
    }

    /**
     * Remember for all enabled rules.
     *
     * @return array<string, array{scanned: int, tracked: int, skipped: int}>
     */
    public static function rememberAllEnabled(): array
    {
        $result = [];
        foreach (Config::getEnabledRules() as $rule) {
            $result[$rule['xml_id']] = self::rememberExisting($rule['xml_id']);
        }

        return $result;
    }

    /**
     * Assign sticker to elements matching rule assign_filter (admin button).
     * Does not remove stickers from elements outside the filter.
     *
     * @return array{scanned: int, assigned: int, tracked: int, skipped: int, error: string}
     */
    public static function assignByFilter(string $xmlId): array
    {
        $stats = [
            'scanned' => 0,
            'assigned' => 0,
            'tracked' => 0,
            'skipped' => 0,
            'error' => '',
        ];

        if (!Config::isEnabled() || !Loader::includeModule('iblock')) {
            $stats['error'] = 'disabled';

            return $stats;
        }

        $rule = Config::getRuleByXmlId($xmlId);
        if ($rule === null || !$rule['enabled']) {
            $stats['error'] = 'rule_disabled';

            return $stats;
        }

        $filter = $rule['assign_filter'] ?? [];
        if (!is_array($filter) || $filter === []) {
            $stats['error'] = 'empty_filter';

            return $stats;
        }

        $iblockId = Config::getIblockId();
        $propertyCode = Config::getHitPropertyCode();
        $xmlId = $rule['xml_id'];
        if ($iblockId <= 0 || HitProperty::getEnumIdByXmlId($iblockId, $propertyCode, $xmlId) === null) {
            $stats['error'] = 'invalid_config';

            return $stats;
        }

        $baseFilter = Config::normalizeAssignFilter($filter);
        $batchSize = Config::getBatchSize();
        $lastId = 0;

        while (true) {
            // Always AND catalog scope + pagination with the user filter
            // so top-level LOGIC=OR cannot OR away IBLOCK_ID / >ID.
            $listFilter = [
                'LOGIC' => 'AND',
                [
                    'IBLOCK_ID' => $iblockId,
                    '>ID' => $lastId,
                ],
                $baseFilter,
            ];

            $rs = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                $listFilter,
                false,
                ['nTopCount' => $batchSize],
                ['ID', 'IBLOCK_ID']
            );

            $countInBatch = 0;
            while ($item = $rs->Fetch()) {
                ++$countInBatch;
                $elementId = (int) ($item['ID'] ?? 0);
                $lastId = $elementId;
                if ($elementId <= 0) {
                    continue;
                }
                ++$stats['scanned'];

                if (HitProperty::addSticker($iblockId, $elementId, $propertyCode, $xmlId)) {
                    ++$stats['assigned'];
                }

                if (AssignmentTracker::trackIfMissing(
                    $iblockId,
                    $elementId,
                    $xmlId,
                    Config::SOURCE_FILTER
                )) {
                    ++$stats['tracked'];
                } else {
                    ++$stats['skipped'];
                }
            }

            if ($countInBatch < $batchSize) {
                break;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, array{scanned: int, assigned: int, tracked: int, skipped: int, error: string}>
     */
    public static function assignByFilterAllEnabled(): array
    {
        $result = [];
        foreach (Config::getEnabledRules() as $rule) {
            $filter = $rule['assign_filter'] ?? [];
            if (!is_array($filter) || $filter === []) {
                continue;
            }
            $result[$rule['xml_id']] = self::assignByFilter($rule['xml_id']);
        }

        return $result;
    }

    /**
     * Assign sticker on element create according to rule.
     */
    public static function assignOnCreate(int $elementId, array $rule): bool
    {
        if (!Config::isEnabled() || empty($rule['auto_on_create']) || empty($rule['enabled'])) {
            return false;
        }

        $iblockId = Config::getIblockId();
        $propertyCode = Config::getHitPropertyCode();
        $xmlId = strtoupper(trim((string) ($rule['xml_id'] ?? '')));
        if ($iblockId <= 0 || $elementId <= 0 || $xmlId === '') {
            return false;
        }

        HitProperty::addSticker($iblockId, $elementId, $propertyCode, $xmlId);
        AssignmentTracker::trackIfMissing($iblockId, $elementId, $xmlId, Config::SOURCE_CREATE);

        return true;
    }

    /**
     * Sync assignment registry with current HIT for tracked rules (manual set/unset).
     */
    public static function syncManualTracking(int $iblockId, int $elementId): void
    {
        if (!Config::isEnabled() || self::isInternalWrite()) {
            return;
        }
        if ($iblockId !== Config::getIblockId() || $elementId <= 0) {
            return;
        }

        $propertyCode = Config::getHitPropertyCode();

        foreach (Config::getEnabledRules() as $rule) {
            if (empty($rule['track_manual'])) {
                continue;
            }

            $xmlId = $rule['xml_id'];
            $hasSticker = HitProperty::hasSticker($iblockId, $elementId, $propertyCode, $xmlId);
            $tracked = AssignmentTracker::exists($elementId, $xmlId);

            if ($hasSticker && !$tracked) {
                AssignmentTracker::trackIfMissing(
                    $iblockId,
                    $elementId,
                    $xmlId,
                    Config::SOURCE_MANUAL
                );
            } elseif (!$hasSticker && $tracked) {
                AssignmentTracker::untrack($elementId, $xmlId);
            }
        }
    }

    /**
     * Expire overdue assignments for one sticker XML_ID.
     *
     * @return array{processed: int, removed: int, cleaned: int}
     */
    public static function expire(string $xmlId): array
    {
        $stats = ['processed' => 0, 'removed' => 0, 'cleaned' => 0];
        if (!Config::isEnabled()) {
            return $stats;
        }

        $rule = Config::getRuleByXmlId($xmlId);
        if ($rule === null || !$rule['enabled']) {
            return $stats;
        }

        $xmlId = $rule['xml_id'];
        $propertyCode = Config::getHitPropertyCode();
        $batchSize = Config::getBatchSize();

        while (true) {
            $rows = AssignmentTracker::findExpired($xmlId, $rule['lifetime_days'], $batchSize);
            if ($rows === []) {
                break;
            }

            foreach ($rows as $row) {
                ++$stats['processed'];
                $elementId = (int) ($row['ELEMENT_ID'] ?? 0);
                $iblockId = (int) ($row['IBLOCK_ID'] ?? 0);
                if ($elementId <= 0 || $iblockId <= 0) {
                    AssignmentTracker::untrack($elementId, $xmlId);
                    ++$stats['cleaned'];
                    continue;
                }

                if (HitProperty::hasSticker($iblockId, $elementId, $propertyCode, $xmlId)) {
                    if (HitProperty::removeSticker($iblockId, $elementId, $propertyCode, $xmlId)) {
                        ++$stats['removed'];
                    }
                } else {
                    ++$stats['cleaned'];
                }

                AssignmentTracker::untrack($elementId, $xmlId);
            }

            if (count($rows) < $batchSize) {
                break;
            }
        }

        return $stats;
    }

    /**
     * @return array<string, array{processed: int, removed: int, cleaned: int}>
     */
    public static function expireAll(): array
    {
        $result = [];
        foreach (Config::getEnabledRules() as $rule) {
            $result[$rule['xml_id']] = self::expire($rule['xml_id']);
        }

        return $result;
    }
}
