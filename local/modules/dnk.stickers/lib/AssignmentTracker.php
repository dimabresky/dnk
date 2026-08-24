<?php

namespace Dnk\Stickers;

use Bitrix\Main\Type\DateTime;

/**
 * Persistence for sticker assignments (element + sticker_xml_id + assigned_at + expires_at).
 */
final class AssignmentTracker
{
    public static function find(int $elementId, string $stickerXmlId): ?array
    {
        $stickerXmlId = strtoupper(trim($stickerXmlId));
        if ($elementId <= 0 || $stickerXmlId === '') {
            return null;
        }

        $row = AssignmentTable::getList([
            'filter' => [
                '=ELEMENT_ID' => $elementId,
                '=STICKER_XML_ID' => $stickerXmlId,
            ],
            'limit' => 1,
        ])->fetch();

        return is_array($row) ? $row : null;
    }

    public static function exists(int $elementId, string $stickerXmlId): bool
    {
        return self::find($elementId, $stickerXmlId) !== null;
    }

    /**
     * Creates assignment if missing. Does not overwrite existing ASSIGNED_AT / EXPIRES_AT.
     * EXPIRES_AT is fixed at insert time from lifetimeDays (current rule setting).
     *
     * @return bool true when a new row was inserted
     */
    public static function trackIfMissing(
        int $iblockId,
        int $elementId,
        string $stickerXmlId,
        string $source,
        float $lifetimeDays = 30.0,
        ?DateTime $assignedAt = null
    ): bool {
        $stickerXmlId = strtoupper(trim($stickerXmlId));
        if ($iblockId <= 0 || $elementId <= 0 || $stickerXmlId === '') {
            return false;
        }

        if (self::exists($elementId, $stickerXmlId)) {
            return false;
        }

        $assigned = $assignedAt ?? new DateTime();
        $expires = Config::computeExpiresAt($assigned, $lifetimeDays);

        $result = AssignmentTable::add([
            'IBLOCK_ID' => $iblockId,
            'ELEMENT_ID' => $elementId,
            'STICKER_XML_ID' => $stickerXmlId,
            'ASSIGNED_AT' => $assigned,
            'EXPIRES_AT' => $expires,
            'SOURCE' => substr($source, 0, 20),
        ]);

        return $result->isSuccess();
    }

    public static function untrack(int $elementId, string $stickerXmlId): bool
    {
        $row = self::find($elementId, $stickerXmlId);
        if ($row === null) {
            return false;
        }

        $result = AssignmentTable::delete((int) $row['ID']);

        return $result->isSuccess();
    }

    /**
     * Rows whose stored EXPIRES_AT has passed (independent of current lifetime_days setting).
     *
     * @return list<array<string, mixed>>
     */
    public static function findExpired(string $stickerXmlId, int $limit = 100): array
    {
        $stickerXmlId = strtoupper(trim($stickerXmlId));
        if ($stickerXmlId === '' || $limit <= 0) {
            return [];
        }

        $rows = AssignmentTable::getList([
            'filter' => [
                '=STICKER_XML_ID' => $stickerXmlId,
                '<=EXPIRES_AT' => new DateTime(),
            ],
            'order' => ['ID' => 'ASC'],
            'limit' => $limit,
        ])->fetchAll();

        return is_array($rows) ? $rows : [];
    }
}
