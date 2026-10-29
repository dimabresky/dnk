<?php

/**
 * Проставляет LINK_REGION каталога по складам регионов с остатком больше нуля.
 * Запуск из корня сайта: php local/tools/sync_catalog_link_region.php
 */

declare(strict_types=1);

use Dnk\PhpInterface\CatalogLinkRegionAgent;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

try {
    $stats = CatalogLinkRegionAgent::sync();
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "Processed elements: {$stats['processed']}\n";
echo "LINK_REGION updated: {$stats['updated']}\n";
echo "LINK_REGION cleared: {$stats['cleared']}\n";
