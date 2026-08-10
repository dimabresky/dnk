<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use CIBlockElement;

/**
 * Enqueue helpers for FEED_PICTURE generation from DETAIL_PICTURE.
 */
final class FeedPictureService
{
    public const PROPERTY_CODE = 'FEED_PICTURE';

    /** @var bool */
    private static $internalUpdate = false;

    public static function beginInternalUpdate(): void
    {
        self::$internalUpdate = true;
    }

    public static function endInternalUpdate(): void
    {
        self::$internalUpdate = false;
    }

    public static function isInternalUpdate(): bool
    {
        return self::$internalUpdate;
    }

    /**
     * Resolve DETAIL_PICTURE and enqueue a pending job when needed.
     *
     * @return bool true if a new pending row was inserted
     */
    public static function enqueueElement(int $iblockId, int $elementId): bool
    {
        if (self::$internalUpdate) {
            return false;
        }
        if (!defined('DNK_CATALOG_IBLOCK_ID') || $iblockId !== (int)DNK_CATALOG_IBLOCK_ID || $elementId <= 0) {
            return false;
        }
        if (!\CModule::IncludeModule('iblock')) {
            return false;
        }

        $detailFileId = self::resolveDetailPictureFileId($iblockId, $elementId);
        if ($detailFileId <= 0) {
            return false;
        }

        return self::addJob($iblockId, $elementId, $detailFileId);
    }

    /**
     * @return bool true if a new pending row was inserted
     */
    public static function addJob(int $iblockId, int $elementId, int $detailFileId): bool
    {
        if ($iblockId <= 0 || $elementId <= 0 || $detailFileId <= 0) {
            return false;
        }

        $existing = FeedPictureQueueTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=ELEMENT_ID' => $elementId,
                '=DETAIL_FILE_ID' => $detailFileId,
                '@STATUS' => [
                    FeedPictureQueueTable::STATUS_PENDING,
                    FeedPictureQueueTable::STATUS_WORKING,
                ],
            ],
            'limit' => 1,
        ])->fetch();

        if ($existing) {
            return false;
        }

        $result = FeedPictureQueueTable::add([
            'ELEMENT_ID' => $elementId,
            'IBLOCK_ID' => $iblockId,
            'DETAIL_FILE_ID' => $detailFileId,
            'STATUS' => FeedPictureQueueTable::STATUS_PENDING,
            'ATTEMPTS' => 0,
            'LAST_ERROR' => null,
            'DATE_INSERT' => new DateTime(),
            'DATE_UPDATE' => null,
        ]);

        return $result->isSuccess();
    }

    public static function resolveDetailPictureFileId(int $iblockId, int $elementId): int
    {
        $res = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $elementId],
            false,
            false,
            ['ID', 'DETAIL_PICTURE']
        );
        $row = $res ? $res->Fetch() : false;

        return is_array($row) ? (int)($row['DETAIL_PICTURE'] ?? 0) : 0;
    }
}
