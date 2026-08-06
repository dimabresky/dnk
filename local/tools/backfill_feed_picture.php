<?php

/**
 * Enqueue catalog elements with DETAIL_PICTURE for FEED_PICTURE generation.
 *
 * Usage (from site root):
 *   php local/tools/backfill_feed_picture.php
 *   php local/tools/backfill_feed_picture.php -- --limit=200 --from-id=0
 */

declare(strict_types=1);

use Dnk\PhpInterface\FeedPictureService;

$limit = 200;
$fromId = 0;

foreach ($argv ?? [] as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string)$arg, $m)) {
        $limit = max(1, (int)$m[1]);
    }
    if (preg_match('/^--from-id=(\d+)$/', (string)$arg, $m)) {
        $fromId = max(0, (int)$m[1]);
    }
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_NO_ACCELERATOR_RESET', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('DNK_CATALOG_IBLOCK_ID') || (int)DNK_CATALOG_IBLOCK_ID <= 0) {
    fwrite(STDERR, "DNK_CATALOG_IBLOCK_ID is not defined.\n");
    exit(1);
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module is not available.\n");
    exit(1);
}

$iblockId = (int)DNK_CATALOG_IBLOCK_ID;
$elements = 0;
$jobs = 0;
$lastId = $fromId;

$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    [
        'IBLOCK_ID' => $iblockId,
        '>ID' => $fromId,
        '!DETAIL_PICTURE' => false,
    ],
    false,
    ['nTopCount' => $limit],
    ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE']
);

while ($row = $res->Fetch()) {
    $elementId = (int)$row['ID'];
    $lastId = $elementId;
    $elements++;
    if (FeedPictureService::enqueueElement($iblockId, $elementId)) {
        $jobs++;
    }
}

fwrite(STDOUT, sprintf(
    "backfill iblock=%d elements=%d jobs_added=%d limit=%d from_id=%d last_id=%d\n",
    $iblockId,
    $elements,
    $jobs,
    $limit,
    $fromId,
    $lastId
));

exit(0);
