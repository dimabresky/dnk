<?php

/**
 * Unpublish catalog product reviews that have rating 0 (or empty) and/or are replies
 * (PARENT_ID > 0). Then recalculate EXTENDED_REVIEWS_* for touched products.
 *
 * CLI (from site root):
 *   php local/tools/unpublish_invalid_product_reviews.php --dry-run
 *   php local/tools/unpublish_invalid_product_reviews.php --apply
 *   php local/tools/unpublish_invalid_product_reviews.php --dry-run --blog-url=catalog_comments
 *
 * Browser (admin only):
 *   /local/tools/unpublish_invalid_product_reviews.php?run=Y&mode=dry-run
 *   /local/tools/unpublish_invalid_product_reviews.php?run=Y&mode=apply
 */

declare(strict_types=1);

use Bitrix\Main\Loader;
use Dnk\PhpInterface\ProductExtendedReviewsAgent;

if (!defined('STDERR')) {
    define(
        'STDERR',
        fopen('php://stderr', 'wb') ?: fopen('php://output', 'wb')
    );
}
if (!defined('STDOUT')) {
    define(
        'STDOUT',
        fopen('php://stdout', 'wb') ?: fopen('php://output', 'wb')
    );
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

$isCli = (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg');

if ($isCli) {
    define('NO_KEEP_STATISTIC', true);
    define('NOT_CHECK_PERMISSIONS', true);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$dnkFinish = static function () use ($isCli): void {
    if (!$isCli && \is_string($_SERVER['DOCUMENT_ROOT'])) {
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    }
};

$dnkOut = static function (string $msg): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDOUT, $msg);
    } else {
        echo $msg;
    }
};

$dnkErr = static function (string $msg): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDERR, $msg);
    } else {
        echo $msg;
    }
};

if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');

    if (
        !isset($GLOBALS['USER'])
        || !\is_object($GLOBALS['USER'])
        || !$GLOBALS['USER']->IsAuthorized()
        || !$GLOBALS['USER']->IsAdmin()
    ) {
        header('HTTP/1.1 403 Forbidden');
        $dnkErr("403 Forbidden: войдите в систему как администратор.\n");
        $dnkFinish();
        exit(1);
    }

    if (($_REQUEST['run'] ?? '') !== 'Y') {
        header('HTTP/1.1 400 Bad Request');
        $dnkErr("400: добавьте к URL параметр run=Y чтобы выполнить скрипт.\n");
        $dnkFinish();
        exit(1);
    }
}

if (!Loader::includeModule('iblock') || !Loader::includeModule('blog')) {
    $dnkErr("Failed to load iblock or blog module.\n");
    $dnkFinish();
    exit(1);
}

@set_time_limit(0);

/**
 * @param list<string> $argvList
 * @return array{mode:string,iblock:int,blog_url:string,sample:int}
 */
$parseArgs = static function (array $argvList, bool $cli): array {
    $mode = 'dry-run';
    $iblock = defined('DNK_CATALOG_IBLOCK_ID') ? (int) DNK_CATALOG_IBLOCK_ID : 0;
    $blogUrl = 'catalog_comments';
    $sample = 20;

    if ($cli) {
        foreach (array_slice($argvList, 1) as $arg) {
            if ($arg === '--dry-run') {
                $mode = 'dry-run';
            } elseif ($arg === '--apply') {
                $mode = 'apply';
            } elseif (str_starts_with($arg, '--iblock=')) {
                $iblock = (int) substr($arg, 9);
            } elseif (str_starts_with($arg, '--blog-url=')) {
                $blogUrl = trim(substr($arg, 11));
            } elseif (str_starts_with($arg, '--sample=')) {
                $sample = max(0, (int) substr($arg, 9));
            }
        }
    } else {
        $rawMode = strtolower(trim((string) ($_GET['mode'] ?? 'dry-run')));
        $mode = $rawMode === 'apply' ? 'apply' : 'dry-run';
        if (isset($_GET['iblock']) && filter_var((string) $_GET['iblock'], FILTER_VALIDATE_INT) !== false) {
            $iblock = (int) $_GET['iblock'];
        }
        if (isset($_GET['blog_url']) && trim((string) $_GET['blog_url']) !== '') {
            $blogUrl = trim((string) $_GET['blog_url']);
        }
        if (isset($_GET['sample']) && filter_var((string) $_GET['sample'], FILTER_VALIDATE_INT) !== false) {
            $sample = max(0, (int) $_GET['sample']);
        }
    }

    return [
        'mode' => $mode,
        'iblock' => $iblock,
        'blog_url' => $blogUrl,
        'sample' => $sample,
    ];
};

$args = $parseArgs(isset($argv) && is_array($argv) ? $argv : [], $isCli);

if ($args['iblock'] <= 0) {
    $dnkErr("Invalid iblock id. Set DNK_CATALOG_IBLOCK_ID or --iblock.\n");
    $dnkFinish();
    exit(1);
}

$iblock = CIBlock::GetArrayByID($args['iblock']);
if (!is_array($iblock)) {
    $dnkErr("Iblock {$args['iblock']} not found.\n");
    $dnkFinish();
    exit(1);
}

$resolveBlog = static function (int $iblockId, string $blogUrl) use ($dnkErr, $dnkFinish): array {
    $row = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
            '>PROPERTY_BLOG_POST_ID' => 0,
        ],
        false,
        ['nTopCount' => 1],
        ['ID', 'PROPERTY_BLOG_POST_ID']
    )->Fetch();

    $postId = is_array($row)
        ? (int) ($row['PROPERTY_BLOG_POST_ID_VALUE'] ?? $row['PROPERTY_BLOG_POST_ID'] ?? 0)
        : 0;
    if ($postId > 0) {
        $post = CBlogPost::GetByID($postId);
        if (is_array($post) && (int) ($post['BLOG_ID'] ?? 0) > 0) {
            $blog = CBlog::GetByID((int) $post['BLOG_ID']);
            if (is_array($blog)) {
                return $blog;
            }
        }
    }

    if ($blogUrl !== '') {
        $blog = CBlog::GetByUrl($blogUrl);
        if (is_array($blog)) {
            return $blog;
        }
    }

    $dnkErr("Catalog reviews blog not found. Pass --blog-url= (typical: catalog_comments).\n");
    $dnkFinish();
    exit(1);
};

$blog = $resolveBlog($args['iblock'], $args['blog_url']);
$blogId = (int) ($blog['ID'] ?? 0);
if ($blogId <= 0) {
    $dnkErr("Catalog reviews blog id is empty.\n");
    $dnkFinish();
    exit(1);
}

$publishStatus = defined('BLOG_PUBLISH_STATUS_PUBLISH') ? BLOG_PUBLISH_STATUS_PUBLISH : 'P';
$readyStatus = defined('BLOG_PUBLISH_STATUS_READY') ? BLOG_PUBLISH_STATUS_READY : 'K';

/**
 * @return array<int, int> postId => elementId
 */
$loadPostToElementMap = static function (int $iblockId): array {
    $map = [];
    $res = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
            '>PROPERTY_BLOG_POST_ID' => 0,
        ],
        false,
        false,
        ['ID', 'PROPERTY_BLOG_POST_ID']
    );
    while ($row = $res->Fetch()) {
        $elementId = (int) ($row['ID'] ?? 0);
        $postId = (int) ($row['PROPERTY_BLOG_POST_ID_VALUE'] ?? $row['PROPERTY_BLOG_POST_ID'] ?? 0);
        if ($elementId > 0 && $postId > 0) {
            $map[$postId] = $elementId;
        }
    }

    return $map;
};

$postToElement = $loadPostToElementMap($args['iblock']);

$candidates = [];
$stats = [
    'scanned' => 0,
    'zero_rating' => 0,
    'replies' => 0,
    'both' => 0,
    'unpublished' => 0,
    'errors' => 0,
    'products_recalc' => 0,
];
$touchedElementIds = [];
$sampleIds = [];

$res = CBlogComment::GetList(
    ['ID' => 'ASC'],
    [
        'BLOG_ID' => $blogId,
        'PUBLISH_STATUS' => $publishStatus,
    ],
    false,
    false,
    ['ID', 'POST_ID', 'PARENT_ID', 'UF_ASPRO_COM_RATING', 'PUBLISH_STATUS']
);

while ($comment = $res->Fetch()) {
    ++$stats['scanned'];

    $commentId = (int) ($comment['ID'] ?? 0);
    if ($commentId <= 0) {
        continue;
    }

    $parentId = (int) ($comment['PARENT_ID'] ?? 0);
    $rating = (int) ($comment['UF_ASPRO_COM_RATING'] ?? 0);
    $isReply = $parentId > 0;
    $isZeroRating = $rating === 0;

    if (!$isReply && !$isZeroRating) {
        continue;
    }

    if ($isReply && $isZeroRating) {
        ++$stats['both'];
    }
    if ($isZeroRating) {
        ++$stats['zero_rating'];
    }
    if ($isReply) {
        ++$stats['replies'];
    }

    $postId = (int) ($comment['POST_ID'] ?? 0);
    $elementId = $postId > 0 ? (int) ($postToElement[$postId] ?? 0) : 0;
    if ($elementId > 0) {
        $touchedElementIds[$elementId] = true;
    }

    $candidates[] = [
        'id' => $commentId,
        'post_id' => $postId,
        'element_id' => $elementId,
        'parent_id' => $parentId,
        'rating' => $rating,
        'is_reply' => $isReply,
        'is_zero_rating' => $isZeroRating,
    ];

    if (count($sampleIds) < $args['sample']) {
        $reasons = [];
        if ($isZeroRating) {
            $reasons[] = 'rating=0';
        }
        if ($isReply) {
            $reasons[] = 'reply';
        }
        $sampleIds[] = $commentId . ' (' . implode(',', $reasons) . ')';
    }
}

$apply = $args['mode'] === 'apply';

if ($apply && $candidates !== []) {
    // Prevent Aspro OnCommentUpdateHandler image attach from iterating missing $_FILES key.
    if (!isset($_FILES['comment_images']) || !is_array($_FILES['comment_images'])) {
        $_FILES['comment_images'] = [];
    }

    foreach ($candidates as $item) {
        $updatedId = (int) CBlogComment::Update(
            $item['id'],
            ['PUBLISH_STATUS' => $readyStatus],
            false
        );
        if ($updatedId <= 0) {
            global $APPLICATION;
            $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
            $error = is_object($exception) && method_exists($exception, 'GetString')
                ? (string) $exception->GetString()
                : 'CBlogComment::Update failed';
            ++$stats['errors'];
            $dnkErr("Failed to unpublish comment {$item['id']}: {$error}\n");
            continue;
        }
        ++$stats['unpublished'];
    }

    foreach (array_keys($touchedElementIds) as $elementId) {
        if (ProductExtendedReviewsAgent::syncExtendedReviewsForElement($args['iblock'], (int) $elementId, false)) {
            ++$stats['products_recalc'];
        }
    }

    if ($touchedElementIds !== []) {
        CIBlock::clearIblockTagCache($args['iblock']);
    }
}

$dnkOut(($apply ? 'APPLY' : 'DRY-RUN') . " iblock {$args['iblock']} ({$iblock['CODE']})\n");
$dnkOut("Blog ID: {$blogId} ({$args['blog_url']})\n");
$dnkOut('Published comments scanned: ' . $stats['scanned'] . "\n");
$dnkOut('Candidates: ' . count($candidates) . "\n");
$dnkOut('  zero rating (incl. empty): ' . $stats['zero_rating'] . "\n");
$dnkOut('  replies (PARENT_ID > 0): ' . $stats['replies'] . "\n");
$dnkOut('  both reasons: ' . $stats['both'] . "\n");
$dnkOut('Unique products touched: ' . count($touchedElementIds) . "\n");

if ($apply) {
    $dnkOut('Unpublished: ' . $stats['unpublished'] . "\n");
    $dnkOut('Update errors: ' . $stats['errors'] . "\n");
    $dnkOut('Products rating recalculated: ' . $stats['products_recalc'] . "\n");
} else {
    $dnkOut("No changes written (dry-run). Pass --apply to unpublish.\n");
}

if ($sampleIds !== []) {
    $dnkOut('Sample comment IDs: ' . implode(', ', $sampleIds) . "\n");
}

$dnkFinish();
