<?php

/**
 * Проставить одну оценку отзывам и пересчитать рейтинг их товаров.
 * Запуск из корня сайта: php local/tools/set_product_review_rating.php 101,205,309 4
 */

declare(strict_types=1);

use Bitrix\Main\Loader;
use Dnk\PhpInterface\ProductExtendedReviewsAgent;

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    header('HTTP/1.1 403 Forbidden');
    echo "CLI only.\n";
    exit(1);
}

$commentIdsArg = $argv[1] ?? '';
$ratingArg = trim((string) ($argv[2] ?? ''));

$commentIds = [];
foreach (explode(',', $commentIdsArg) as $part) {
    $part = trim($part);
    if ($part === '' || preg_match('/^[1-9]\d*$/', $part) !== 1) {
        fwrite(STDERR, "Invalid comment id list. Usage: php local/tools/set_product_review_rating.php 101,205 4\n");
        exit(1);
    }
    $commentIds[] = (int) $part;
}
$commentIds = array_values(array_unique($commentIds));

if (preg_match('/^[1-5]$/', $ratingArg) !== 1) {
    fwrite(STDERR, "Rating must be an integer from 1 to 5.\n");
    exit(1);
}
$rating = (int) $ratingArg;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('DNK_CATALOG_IBLOCK_ID') || (int) DNK_CATALOG_IBLOCK_ID <= 0) {
    fwrite(STDERR, "DNK_CATALOG_IBLOCK_ID is not defined.\n");
    exit(1);
}
if (!Loader::includeModule('iblock') || !Loader::includeModule('blog')) {
    fwrite(STDERR, "Failed to load iblock or blog module.\n");
    exit(1);
}

// Aspro OnCommentUpdate iterates this key.
if (!isset($_FILES['comment_images']) || !is_array($_FILES['comment_images'])) {
    $_FILES['comment_images'] = [];
}

$updated = 0;
$errors = 0;
$postIds = [];

foreach ($commentIds as $commentId) {
    $comment = CBlogComment::GetByID($commentId);
    if (!is_array($comment)) {
        fwrite(STDERR, "Comment {$commentId} not found.\n");
        ++$errors;
        continue;
    }

    $postId = (int) ($comment['POST_ID'] ?? 0);
    if ($postId <= 0) {
        fwrite(STDERR, "Comment {$commentId} has no post.\n");
        ++$errors;
        continue;
    }

    // UF-only Update is a no-op: PrepareUpdate skips columns missing from b_blog_comment.
    $updatedId = (int) CBlogComment::Update($commentId, [
        'POST_ID' => $postId,
        'UF_ASPRO_COM_RATING' => $rating,
    ], false);
    if ($updatedId <= 0) {
        global $APPLICATION;
        $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
        $error = is_object($exception) && method_exists($exception, 'GetString')
            ? (string) $exception->GetString()
            : 'CBlogComment::Update failed';
        fwrite(STDERR, "Failed to update comment {$commentId}: {$error}\n");
        ++$errors;
        continue;
    }

    ++$updated;
    $postIds[$postId] = true;
}

$elementByPost = [];
if ($postIds !== []) {
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => (int) DNK_CATALOG_IBLOCK_ID,
            'CHECK_PERMISSIONS' => 'N',
            'PROPERTY_BLOG_POST_ID' => array_keys($postIds),
        ],
        false,
        false,
        ['ID', 'PROPERTY_BLOG_POST_ID']
    );
    while ($row = $res->Fetch()) {
        $elementId = (int) ($row['ID'] ?? 0);
        $postId = (int) ($row['PROPERTY_BLOG_POST_ID_VALUE'] ?? $row['PROPERTY_BLOG_POST_ID'] ?? 0);
        if ($elementId > 0 && $postId > 0) {
            $elementByPost[$postId] = $elementId;
        }
    }
}

$recalculated = 0;
$seenElements = [];
$iblockId = (int) DNK_CATALOG_IBLOCK_ID;
foreach (array_keys($postIds) as $postId) {
    $elementId = (int) ($elementByPost[$postId] ?? 0);
    if ($elementId <= 0) {
        fwrite(STDERR, "No catalog product for post {$postId}.\n");
        ++$errors;
        continue;
    }
    if (isset($seenElements[$elementId])) {
        continue;
    }
    $seenElements[$elementId] = true;

    if (!ProductExtendedReviewsAgent::syncExtendedReviewsForElement($iblockId, $elementId, false)) {
        fwrite(STDERR, "Failed to recalculate product {$elementId}.\n");
        ++$errors;
        continue;
    }
    ++$recalculated;
}

if ($seenElements !== []) {
    CIBlock::clearIblockTagCache($iblockId);
}

echo "Comments updated: {$updated}\n";
echo "Errors: {$errors}\n";
echo "Products recalculated: {$recalculated}\n";

exit($errors > 0 ? 1 : 0);
