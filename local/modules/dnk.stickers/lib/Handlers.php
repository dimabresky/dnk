<?php

namespace Dnk\Stickers;

/**
 * Iblock element event handlers for sticker assign/track.
 */
final class Handlers
{
    /**
     * @param array<string, mixed> $arFields
     */
    public static function onAfterIBlockElementAdd(array &$arFields): void
    {
        if (!Config::isEnabled()) {
            return;
        }
        if (isset($arFields['RESULT']) && $arFields['RESULT'] === false) {
            return;
        }

        $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);
        $elementId = (int) ($arFields['ID'] ?? 0);
        if ($iblockId !== Config::getIblockId() || $elementId <= 0) {
            return;
        }

        foreach (Config::getEnabledRules() as $rule) {
            if (!empty($rule['auto_on_create'])) {
                StickerService::assignOnCreate($elementId, $rule);
            }
        }

        // Track stickers already present on create (e.g. set in the form when auto_on_create is off).
        StickerService::syncManualTracking($iblockId, $elementId);
    }

    /**
     * @param array<string, mixed> $arFields
     */
    public static function onAfterIBlockElementUpdate(array &$arFields): void
    {
        if (!Config::isEnabled() || StickerService::isInternalWrite()) {
            return;
        }
        if (empty($arFields['RESULT'])) {
            return;
        }

        $iblockId = (int) ($arFields['IBLOCK_ID'] ?? 0);
        $elementId = (int) ($arFields['ID'] ?? 0);
        if ($iblockId !== Config::getIblockId() || $elementId <= 0) {
            return;
        }

        StickerService::syncManualTracking($iblockId, $elementId);
    }
}
