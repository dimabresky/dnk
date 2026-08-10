<?php

namespace Dnk\PhpInterface;

/**
 * Puts catalog elements into FEED_PICTURE generation queue after save.
 */
final class FeedPictureEvents
{
    public static function onAfterIBlockElementAdd(array &$arFields): void
    {
        if (isset($arFields['RESULT']) && $arFields['RESULT'] === false) {
            return;
        }

        self::enqueueFromFields($arFields);
    }

    public static function onAfterIBlockElementUpdate(array &$arFields): void
    {
        if (empty($arFields['RESULT'])) {
            return;
        }

        self::enqueueFromFields($arFields);
    }

    /**
     * @param array<string, mixed> $arFields
     */
    private static function enqueueFromFields(array $arFields): void
    {
        if (FeedPictureService::isInternalUpdate()) {
            return;
        }

        $iblockId = (int)($arFields['IBLOCK_ID'] ?? 0);
        $elementId = (int)($arFields['ID'] ?? 0);
        if ($iblockId <= 0 || $elementId <= 0) {
            return;
        }

        FeedPictureService::enqueueElement($iblockId, $elementId);
    }
}
