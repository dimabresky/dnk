<?php

namespace Dnk\Stickers;

/**
 * Periodic agent: expire overdue sticker assignments.
 */
final class Agent
{
    /**
     * Bitrix agent entry point.
     */
    public static function run(): string
    {
        if (Config::isEnabled()) {
            StickerService::expireAll();
        }

        return '\\Dnk\\Stickers\\Agent::run();';
    }
}
