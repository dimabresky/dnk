<?php

/**
 * Ручной запуск YML-фида IMSHOP.
 * Запуск из корня сайта: php local/tools/run_imshop_feed.php [output.xml]
 *
 * По умолчанию пишет bitrix/catalog_export/imshop.xml.
 */

declare(strict_types=1);

use Dnk\PhpInterface\ImshopCatalogFeedExporter;

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

$relativeOutput = '/bitrix/catalog_export/imshop.xml';
if (isset($argv[1]) && is_string($argv[1]) && $argv[1] !== '') {
    $relativeOutput = '/' . ltrim(str_replace('\\', '/', $argv[1]), '/');
}

$documentRoot = rtrim(str_replace('\\', '/', (string) $_SERVER['DOCUMENT_ROOT']), '/');
$absolutePath = $documentRoot . $relativeOutput;
$shopName = defined('DNK_PRODUCT_FEED_CHANNEL_TITLE')
    ? (string) DNK_PRODUCT_FEED_CHANNEL_TITLE
    : 'DNK.BY';

try {
    $count = ImshopCatalogFeedExporter::export(
        [(int) DNK_CATALOG_IBLOCK_ID],
        $absolutePath,
        rtrim((string) DNK_SITE_URL, '/'),
        $shopName
    );
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

fwrite(STDOUT, "Feed generated: {$absolutePath}\n");
fwrite(STDOUT, "Offers: {$count}\n");
