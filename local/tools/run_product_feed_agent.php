<?php

/**
 * Ручной запуск генерации upload/dnk_products_feed.xml.
 * Запуск из корня сайта: php local/tools/run_product_feed_agent.php
 */

declare(strict_types=1);

use Dnk\PhpInterface\ProductFeedAgent;

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

if (!defined('DNK_SITE_URL')) {
    fwrite(STDERR, "DNK_SITE_URL is not defined.\n");
    exit(1);
}

$count = ProductFeedAgent::generateFeed();
$outputPath = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') . '/upload/dnk_products_feed.xml';

fwrite(STDOUT, "Feed generated: {$outputPath}\n");
fwrite(STDOUT, "Entries: {$count}\n");
