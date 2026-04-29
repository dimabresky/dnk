<?php

/**
 * Массовая синхронизация свойства HIT из MARKER_DLYA_SAYTA для каталога DNK_CATALOG_IBLOCK_ID.
 * Запуск из корня сайта: php local/tools/sync_marker_to_hit.php
 */

declare(strict_types=1);

use Dnk\PhpInterface\IblockProductMarkerHitEvents;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('DNK_CATALOG_IBLOCK_ID')) {
    fwrite(STDERR, "DNK_CATALOG_IBLOCK_ID is not defined.\n");
    exit(1);
}

if (!CModule::IncludeModule('iblock')) {
    fwrite(STDERR, "Failed to load iblock module.\n");
    exit(1);
}

$processed = 0;
$updated = 0;

$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => (int) DNK_CATALOG_IBLOCK_ID],
    false,
    false,
    ['ID']
);

while ($row = $res->Fetch()) {
    $id = (int) ($row['ID'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    ++$processed;
    if (IblockProductMarkerHitEvents::syncHitFromMarkerForElement((int) DNK_CATALOG_IBLOCK_ID, $id)) {
        ++$updated;
    }
}

echo "Processed elements: {$processed}\n";
echo "HIT synced: {$updated}\n";
