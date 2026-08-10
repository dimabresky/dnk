<?php

/**
 * Manual run of FEED_PICTURE queue worker.
 * From site root: php local/tools/run_feed_picture_agent.php
 */

declare(strict_types=1);

use Dnk\PhpInterface\FeedPictureAgent;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$stats = FeedPictureAgent::processQueue();

if (!empty($stats['skipped_lock'])) {
    fwrite(STDOUT, "skipped_lock=1 (busy or lock setup failed; see AddMessage2Log channel dnk.feed_picture)\n");
    exit(0);
}

fwrite(STDOUT, sprintf(
    "processed=%d success=%d failed=%d stale=%d\n",
    $stats['processed'],
    $stats['success'],
    $stats['failed'],
    $stats['stale']
));

exit($stats['failed'] > 0 ? 1 : 0);
