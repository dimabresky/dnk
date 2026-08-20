<?php

/**
 * Autoload map for dnk.stickers.
 */

use Bitrix\Main\Loader;
use Dnk\Stickers\Agent;
use Dnk\Stickers\AssignmentTable;
use Dnk\Stickers\AssignmentTracker;
use Dnk\Stickers\Config;
use Dnk\Stickers\Handlers;
use Dnk\Stickers\HitProperty;
use Dnk\Stickers\StickerService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::registerAutoLoadClasses(
    'dnk.stickers',
    [
        Config::class => 'lib/Config.php',
        HitProperty::class => 'lib/HitProperty.php',
        AssignmentTable::class => 'lib/AssignmentTable.php',
        AssignmentTracker::class => 'lib/AssignmentTracker.php',
        StickerService::class => 'lib/StickerService.php',
        Agent::class => 'lib/Agent.php',
        Handlers::class => 'lib/Handlers.php',
    ]
);
