<?php

/**
 * Import product reviews pack into catalog blog comments as published (PUBLISH),
 * with PATH pointing to the product page for admin «Сервисы → Блоги».
 * Re-run --apply updates already imported comments (files are not re-uploaded).
 *
 * Default pack path is upload/reviews_migrate (HTTP denied via .htaccess / web.config).
 *
 * CLI (from site root):
 *   php local/tools/import_product_reviews.php --dry-run
 *   php local/tools/import_product_reviews.php --apply
 *   php local/tools/import_product_reviews.php --dry-run --pack=upload/reviews_migrate
 *
 * Browser (admin only):
 *   /local/tools/import_product_reviews.php?run=Y&mode=dry-run
 *   /local/tools/import_product_reviews.php?run=Y&mode=apply
 */

declare(strict_types=1);

use Bitrix\Main\Loader;
use Dnk\PhpInterface\ProductExtendedReviewsAgent;
use Dnk\PhpInterface\Utils;

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

const DNK_REVIEWS_IMPORT_OLD_ID_UF = 'UF_DNK_OLD_COMMENT_ID';
const DNK_REVIEWS_IMPORT_GUEST_NAME = 'Покупатель';

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
        $dnkErr("400: добавьте к URL параметр run=Y чтобы выполнить импорт.\n");
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
 * @return array{mode:string,iblock:int,pack:string,blog_url:string}
 */
$parseArgs = static function (array $argvList, bool $cli): array {
    $mode = 'dry-run';
    $iblock = defined('DNK_CATALOG_IBLOCK_ID') ? (int) DNK_CATALOG_IBLOCK_ID : 42;
    $pack = 'upload/reviews_migrate';
    $blogUrl = 'catalog_comments';

    if ($cli) {
        foreach (array_slice($argvList, 1) as $arg) {
            if ($arg === '--dry-run') {
                $mode = 'dry-run';
            } elseif ($arg === '--apply') {
                $mode = 'apply';
            } elseif (str_starts_with($arg, '--iblock=')) {
                $iblock = (int) substr($arg, 9);
            } elseif (str_starts_with($arg, '--pack=')) {
                $value = trim(substr($arg, 7));
                if ($value !== '') {
                    $pack = $value;
                }
            } elseif (str_starts_with($arg, '--blog-url=')) {
                $blogUrl = trim(substr($arg, 11));
            }
        }
    } else {
        $rawMode = strtolower(trim((string) ($_GET['mode'] ?? 'dry-run')));
        $mode = $rawMode === 'apply' ? 'apply' : 'dry-run';
        if (isset($_GET['iblock']) && filter_var((string) $_GET['iblock'], FILTER_VALIDATE_INT) !== false) {
            $iblock = (int) $_GET['iblock'];
        }
        if (isset($_GET['pack']) && trim((string) $_GET['pack']) !== '') {
            $pack = trim((string) $_GET['pack']);
        }
        if (isset($_GET['blog_url']) && trim((string) $_GET['blog_url']) !== '') {
            $blogUrl = trim((string) $_GET['blog_url']);
        }
    }

    return [
        'mode' => $mode,
        'iblock' => $iblock,
        'pack' => $pack,
        'blog_url' => $blogUrl,
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

$normalizeFsPath = static function (string $path): string {
    $path = str_replace('\\', '/', $path);
    $absolute = str_starts_with($path, '/');
    $parts = [];
    foreach (explode('/', $path) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $seg;
    }

    $normalized = implode('/', $parts);

    return $absolute ? '/' . $normalized : $normalized;
};

$isSameDir = static function (string $left, string $right): bool {
    return rtrim(str_replace('\\', '/', $left), '/') === rtrim(str_replace('\\', '/', $right), '/');
};

$isStrictSubdirOf = static function (string $dir, string $root): bool {
    $dirPath = rtrim(str_replace('\\', '/', $dir), '/') . '/';
    $rootPath = rtrim(str_replace('\\', '/', $root), '/') . '/';

    return $dirPath !== $rootPath && str_starts_with($dirPath, $rootPath);
};

$docRoot = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');
$packRel = str_replace('\\', '/', trim($args['pack']));
$packRoot = $normalizeFsPath(
    str_starts_with($packRel, '/')
        ? $packRel
        : $docRoot . '/' . $packRel
);
$packBase = basename($packRoot);
if ($packRel === '' || $packBase === '' || $packBase === '.' || $packBase === '..' || $isSameDir($packRoot, $docRoot)) {
    $dnkErr("Refusing pack path that resolves to the site root. Use upload/reviews_migrate or a dedicated subdirectory.\n");
    $dnkFinish();
    exit(1);
}
$manifestPath = $packRoot . DIRECTORY_SEPARATOR . 'manifest.json';

if (!is_file($manifestPath)) {
    $dnkErr("manifest.json not found: {$manifestPath}\n");
    $dnkErr("Copy the export pack from the old site to {$packRoot}\n");
    $dnkFinish();
    exit(1);
}

$protectPackFromHttp = static function (string $directory): void {
    if ($directory === '' || !is_dir($directory)) {
        return;
    }

    @file_put_contents(
        $directory . DIRECTORY_SEPARATOR . '.htaccess',
        "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n"
    );
    @file_put_contents(
        $directory . DIRECTORY_SEPARATOR . 'web.config',
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer>"
        . "<security><authorization><remove users=\"*\" roles=\"\" verbs=\"\" />"
        . "<add accessType=\"Deny\" users=\"*\" /></authorization></security>"
        . "</system.webServer></configuration>\n"
    );
};

if ($isStrictSubdirOf($packRoot, $docRoot)) {
    $protectPackFromHttp($packRoot);
    $protectPackFromHttp($packRoot . DIRECTORY_SEPARATOR . 'files');
}

$manifestRaw = file_get_contents($manifestPath);
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
if (!is_array($manifest)) {
    $dnkErr("Cannot parse manifest.json: " . json_last_error_msg() . "\n");
    $dnkFinish();
    exit(1);
}

$comments = is_array($manifest['comments'] ?? null) ? $manifest['comments'] : [];
if ($comments === []) {
    $dnkOut("No comments in pack.\n");
    $dnkFinish();
    exit(0);
}

$ensureOldIdUf = static function () use ($dnkErr, $dnkFinish): void {
    $existing = CUserTypeEntity::GetList(
        [],
        [
            'ENTITY_ID' => 'BLOG_COMMENT',
            'FIELD_NAME' => DNK_REVIEWS_IMPORT_OLD_ID_UF,
        ]
    )->Fetch();
    if (is_array($existing)) {
        return;
    }

    $entity = new CUserTypeEntity();
    $fieldId = (int) $entity->Add([
        'ENTITY_ID' => 'BLOG_COMMENT',
        'FIELD_NAME' => DNK_REVIEWS_IMPORT_OLD_ID_UF,
        'USER_TYPE_ID' => 'integer',
        'XML_ID' => DNK_REVIEWS_IMPORT_OLD_ID_UF,
        'SORT' => 200,
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'I',
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'EDIT_FORM_LABEL' => [
            'ru' => 'ID отзыва на старом сайте',
            'en' => 'Old site review ID',
        ],
        'LIST_COLUMN_LABEL' => [
            'ru' => 'ID отзыва на старом сайте',
            'en' => 'Old site review ID',
        ],
        'LIST_FILTER_LABEL' => [
            'ru' => 'ID отзыва на старом сайте',
            'en' => 'Old site review ID',
        ],
    ]);

    if ($fieldId <= 0) {
        global $APPLICATION;
        $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
        $error = is_object($exception) && method_exists($exception, 'GetString')
            ? (string) $exception->GetString()
            : 'unknown error';
        $dnkErr("Failed to create " . DNK_REVIEWS_IMPORT_OLD_ID_UF . ": {$error}\n");
        $dnkFinish();
        exit(1);
    }
};

$ensureOldIdUf();

/**
 * @return array<int, int> oldCommentId => newCommentId
 */
$loadImportedOldIds = static function (int $blogId): array {
    $map = [];
    if ($blogId <= 0) {
        return $map;
    }

    $res = CBlogComment::GetList(
        ['ID' => 'ASC'],
        ['BLOG_ID' => $blogId],
        false,
        false,
        ['ID', DNK_REVIEWS_IMPORT_OLD_ID_UF]
    );
    while ($row = $res->Fetch()) {
        $oldId = (int) ($row[DNK_REVIEWS_IMPORT_OLD_ID_UF] ?? 0);
        $newId = (int) ($row['ID'] ?? 0);
        if ($oldId > 0 && $newId > 0) {
            $map[$oldId] = $newId;
        }
    }

    return $map;
};

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
$blogOwnerId = (int) ($blog['OWNER_ID'] ?? 0);
if ($blogOwnerId <= 0) {
    $blogOwnerId = 1;
}

$codeMap = Utils::buildCatalogElementIdMapByImportCode($args['iblock']);

/**
 * @return array<int, int> elementId => blogPostId
 */
$loadExistingBlogPosts = static function (int $iblockId): array {
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
            $map[$elementId] = $postId;
        }
    }

    return $map;
};

$elementBlogPosts = $loadExistingBlogPosts($args['iblock']);

$allPhones = [];
foreach ($comments as $comment) {
    if (!is_array($comment)) {
        continue;
    }
    foreach ((array) ($comment['author_phones'] ?? []) as $phone) {
        $phone = trim((string) $phone);
        if ($phone !== '') {
            $allPhones[] = $phone;
        }
    }
}

$importedOldIds = $loadImportedOldIds($blogId);
$phoneResolved = Utils::resolveUserIdsByBonusImportPhones($allPhones);

$resolveAuthorUserId = static function (array $comment) use ($phoneResolved): ?int {
    foreach ((array) ($comment['author_phones'] ?? []) as $phone) {
        $digits = Utils::normalizeBonusPhoneDigits((string) $phone);
        if ($digits === '') {
            continue;
        }
        if (in_array($digits, $phoneResolved['ambiguous'], true)) {
            continue;
        }
        $userId = (int) ($phoneResolved['found'][$digits] ?? 0);
        if ($userId > 0) {
            return $userId;
        }
    }

    return null;
};

$formatDateCreate = static function (string $raw): string {
    $raw = trim($raw);
    if ($raw === '') {
        return ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
    }

    $ts = MakeTimeStamp($raw);
    if ($ts > 0) {
        return ConvertTimeStamp($ts, 'FULL');
    }

    return ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL');
};

$elementDetailUrls = [];
$getElementDetailUrl = static function (int $elementId) use (&$elementDetailUrls, $args): string {
    if ($elementId <= 0) {
        return '';
    }
    if (array_key_exists($elementId, $elementDetailUrls)) {
        return $elementDetailUrls[$elementId];
    }

    $element = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $args['iblock'], 'ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'DETAIL_PAGE_URL']
    )->GetNext();

    $url = is_array($element) ? trim((string) ($element['DETAIL_PAGE_URL'] ?? '')) : '';
    $elementDetailUrls[$elementId] = $url;

    return $url;
};

$buildCommentPath = static function (string $detailPageUrl): string {
    $detailPageUrl = trim($detailPageUrl);
    if ($detailPageUrl === '') {
        return '';
    }

    $separator = str_contains($detailPageUrl, '?') ? '&' : '?';

    return $detailPageUrl . $separator . 'commentId=#comment_id##com#comment_id#';
};

$ensureBlogPost = static function (
    int $elementId,
    bool $apply
) use (
    &$elementBlogPosts,
    $args,
    $blogId,
    $blogOwnerId,
    $dnkErr
): int {
    if (isset($elementBlogPosts[$elementId]) && $elementBlogPosts[$elementId] > 0) {
        return $elementBlogPosts[$elementId];
    }

    if (!$apply) {
        return 0;
    }

    $element = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $args['iblock'], 'ID' => $elementId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID', 'NAME', 'DETAIL_PAGE_URL']
    )->GetNext();

    $name = is_array($element) ? (string) ($element['~NAME'] ?? $element['NAME'] ?? 'Product') : 'Product';
    $url = is_array($element) ? (string) ($element['DETAIL_PAGE_URL'] ?? '') : '';
    $detailText = $url !== ''
        ? '[URL=' . $url . ']' . $name . '[/URL]'
        : $name;

    $postId = (int) CBlogPost::Add([
        'TITLE' => $name,
        'DETAIL_TEXT' => $detailText,
        'DETAIL_TEXT_TYPE' => 'text',
        'BLOG_ID' => $blogId,
        'AUTHOR_ID' => $blogOwnerId,
        'DATE_PUBLISH' => ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL'),
        'PUBLISH_STATUS' => defined('BLOG_PUBLISH_STATUS_PUBLISH') ? BLOG_PUBLISH_STATUS_PUBLISH : 'P',
        'ENABLE_TRACKBACK' => 'N',
        'ENABLE_COMMENTS' => 'Y',
    ]);

    if ($postId <= 0) {
        global $APPLICATION;
        $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
        $error = is_object($exception) && method_exists($exception, 'GetString')
            ? (string) $exception->GetString()
            : 'CBlogPost::Add failed';
        $dnkErr("Failed to create blog post for element {$elementId}: {$error}\n");

        return 0;
    }

    CIBlockElement::SetPropertyValuesEx($elementId, $args['iblock'], [
        'BLOG_POST_ID' => $postId,
    ]);
    $elementBlogPosts[$elementId] = $postId;

    return $postId;
};

$warnings = [];

$makeFileArrays = static function (array $files, string $packRoot) use (&$warnings): array {
    $result = [];
    foreach ($files as $file) {
        if (!is_array($file)) {
            continue;
        }
        $packPath = str_replace('\\', '/', ltrim((string) ($file['pack_path'] ?? ''), '/'));
        if ($packPath === '' || str_contains($packPath, '..')) {
            continue;
        }
        $abs = $packRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $packPath);
        if (!is_file($abs)) {
            $warnings[] = "Pack file missing: {$packPath}";
            continue;
        }
        $fileArray = CFile::MakeFileArray($abs);
        if (!is_array($fileArray)) {
            $warnings[] = "Cannot make file array: {$packPath}";
            continue;
        }
        $originalName = trim((string) ($file['original_name'] ?? ''));
        if ($originalName !== '') {
            $fileArray['name'] = $originalName;
        }
        $fileArray['MODULE_ID'] = 'blog';
        $result[] = $fileArray;
    }

    return $result;
};

$apply = $args['mode'] === 'apply';
$publishStatus = defined('BLOG_PUBLISH_STATUS_PUBLISH') ? BLOG_PUBLISH_STATUS_PUBLISH : 'P';

$buildCommentFields = static function (
    array $comment,
    int $postId,
    int $elementId,
    ?int $userId,
    int $parentNewId,
    bool $includeFiles
) use (
    $blogId,
    $publishStatus,
    $formatDateCreate,
    $getElementDetailUrl,
    $buildCommentPath,
    $makeFileArrays,
    $packRoot
): array {
    $oldId = (int) ($comment['id'] ?? 0);

    $authorName = trim((string) ($comment['author_name'] ?? ''));
    if ($authorName === '') {
        $authorName = DNK_REVIEWS_IMPORT_GUEST_NAME;
    }

    $postText = trim((string) ($comment['post_text'] ?? ''));
    if ($postText === '') {
        $postText = '<comment></comment>';
    }

    $fields = [
        'BLOG_ID' => $blogId,
        'POST_ID' => $postId,
        'POST_TEXT' => $postText,
        'DATE_CREATE' => $formatDateCreate((string) ($comment['date_create'] ?? '')),
        'PUBLISH_STATUS' => $publishStatus,
        DNK_REVIEWS_IMPORT_OLD_ID_UF => $oldId,
    ];

    $path = $buildCommentPath($getElementDetailUrl($elementId));
    if ($path !== '') {
        $fields['PATH'] = $path;
    }

    if ($parentNewId > 0) {
        $fields['PARENT_ID'] = $parentNewId;
    }

    $rating = (int) ($comment['rating'] ?? 0);
    if ($rating > 0) {
        $fields['UF_ASPRO_COM_RATING'] = $rating;
    }
    $like = (int) ($comment['like'] ?? 0);
    if ($like > 0) {
        $fields['UF_ASPRO_COM_LIKE'] = $like;
    }
    $dislike = (int) ($comment['dislike'] ?? 0);
    if ($dislike > 0) {
        $fields['UF_ASPRO_COM_DISLIKE'] = $dislike;
    }
    if (!empty($comment['approve'])) {
        $fields['UF_ASPRO_COM_APPROVE'] = 1;
    }

    $title = trim((string) ($comment['title'] ?? ''));
    if ($title !== '') {
        $fields['TITLE'] = $title;
    }

    if ($userId !== null) {
        $fields['AUTHOR_ID'] = $userId;
    } else {
        $fields['AUTHOR_NAME'] = $authorName;
        $email = trim((string) ($comment['author_email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fields['AUTHOR_EMAIL'] = $email;
        }
    }

    if ($includeFiles) {
        $fileArrays = $makeFileArrays((array) ($comment['files'] ?? []), $packRoot);
        if ($fileArrays !== []) {
            $fields['UF_BLOG_COMMENT_DOC'] = $fileArrays;
        }
    }

    return $fields;
};

$stats = [
    'matched' => 0,
    'guest' => 0,
    'created' => 0,
    'updated' => 0,
    'skipped_no_onliner' => 0,
    'skipped_not_found' => 0,
    'skipped_ambiguous' => 0,
    'skipped_orphan_reply' => 0,
    'skipped_error' => 0,
];
$touchedElementIds = [];
$oldToNew = $importedOldIds;
$pending = [];

foreach ($comments as $comment) {
    if (is_array($comment)) {
        $pending[] = $comment;
    }
}

usort($pending, static function (array $a, array $b): int {
    return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
});

$progress = true;
while ($pending !== [] && $progress) {
    $progress = false;
    $next = [];

    foreach ($pending as $comment) {
        $oldId = (int) ($comment['id'] ?? 0);
        $parentOldId = (int) ($comment['parent_id'] ?? 0);

        if ($parentOldId > 0 && !isset($oldToNew[$parentOldId])) {
            $next[] = $comment;
            continue;
        }

        $progress = true;

        $mlOnliner = (string) ($comment['product']['ml_onliner'] ?? '');
        $match = Utils::findCatalogElementIdByImportCode($args['iblock'], $mlOnliner, $codeMap);

        if ($match['status'] === 'empty') {
            ++$stats['skipped_no_onliner'];
            $warnings[] = "Skip comment {$oldId}: empty ML_ONLINER";
            continue;
        }
        if ($match['status'] === 'not_found') {
            ++$stats['skipped_not_found'];
            $warnings[] = "Skip comment {$oldId}: product not found for ML_ONLINER={$mlOnliner}";
            continue;
        }
        if ($match['status'] === 'ambiguous') {
            ++$stats['skipped_ambiguous'];
            $warnings[] = "Skip comment {$oldId}: ambiguous ML_ONLINER={$mlOnliner}";
            continue;
        }

        $elementId = (int) $match['id'];
        $touchedElementIds[$elementId] = true;
        ++$stats['matched'];

        $userId = $resolveAuthorUserId($comment);
        if ($userId === null) {
            ++$stats['guest'];
        }

        $existingId = ($oldId > 0) ? (int) ($importedOldIds[$oldId] ?? 0) : 0;
        $parentNewId = ($parentOldId > 0) ? (int) ($oldToNew[$parentOldId] ?? 0) : 0;

        if (!$apply) {
            if ($existingId > 0) {
                $oldToNew[$oldId] = $existingId;
                ++$stats['updated'];
            } else {
                if ($oldId > 0) {
                    $oldToNew[$oldId] = $oldId;
                }
                ++$stats['created'];
            }
            continue;
        }

        $postId = $ensureBlogPost($elementId, true);
        if ($postId <= 0) {
            ++$stats['skipped_error'];
            continue;
        }

        $fields = $buildCommentFields(
            $comment,
            $postId,
            $elementId,
            $userId,
            $parentNewId,
            $existingId <= 0
        );

        if ($existingId > 0) {
            $updatedId = (int) CBlogComment::Update($existingId, $fields, false);
            if ($updatedId <= 0) {
                global $APPLICATION;
                $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
                $error = is_object($exception) && method_exists($exception, 'GetString')
                    ? (string) $exception->GetString()
                    : 'CBlogComment::Update failed';
                ++$stats['skipped_error'];
                $warnings[] = "Failed to update comment {$oldId} (id {$existingId}): {$error}";
                continue;
            }

            $oldToNew[$oldId] = $existingId;
            ++$stats['updated'];
            continue;
        }

        $newId = (int) CBlogComment::Add($fields, false);
        if ($newId <= 0) {
            global $APPLICATION;
            $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
            $error = is_object($exception) && method_exists($exception, 'GetString')
                ? (string) $exception->GetString()
                : 'CBlogComment::Add failed';
            ++$stats['skipped_error'];
            $warnings[] = "Failed to add comment {$oldId}: {$error}";
            continue;
        }

        $oldToNew[$oldId] = $newId;
        ++$stats['created'];
    }

    $pending = $next;
}

foreach ($pending as $comment) {
    $oldId = (int) ($comment['id'] ?? 0);
    ++$stats['skipped_orphan_reply'];
    $warnings[] = "Skip comment {$oldId}: parent review was not imported";
}

if ($apply && $touchedElementIds !== []) {
    foreach (array_keys($touchedElementIds) as $elementId) {
        ProductExtendedReviewsAgent::syncExtendedReviewsForElement($args['iblock'], $elementId, false);
    }
    CIBlock::clearIblockTagCache($args['iblock']);
}

$dnkOut(($apply ? 'APPLY' : 'DRY-RUN') . " iblock {$args['iblock']} ({$iblock['CODE']})\n");
$dnkOut('Pack comments: ' . count($comments) . "\n");
$dnkOut('Matched products: ' . $stats['matched'] . "\n");
$dnkOut('Guest authors: ' . $stats['guest'] . "\n");
$dnkOut('Created: ' . $stats['created'] . "\n");
$dnkOut('Updated: ' . $stats['updated'] . "\n");
$dnkOut('Skipped empty ML_ONLINER: ' . $stats['skipped_no_onliner'] . "\n");
$dnkOut('Skipped product not found: ' . $stats['skipped_not_found'] . "\n");
$dnkOut('Skipped ambiguous code: ' . $stats['skipped_ambiguous'] . "\n");
$dnkOut('Skipped orphan replies: ' . $stats['skipped_orphan_reply'] . "\n");
$dnkOut('Skipped errors: ' . $stats['skipped_error'] . "\n");
$dnkOut('Catalog import codes found: ' . count($codeMap['found']) . "\n");
$dnkOut('Catalog import codes ambiguous: ' . count($codeMap['ambiguous']) . "\n");

if ($warnings !== []) {
    $dnkOut('Warnings: ' . count($warnings) . "\n");
    $shown = 0;
    foreach ($warnings as $warning) {
        if ($shown >= 50) {
            $dnkOut('  ... ' . (count($warnings) - 50) . " more\n");
            break;
        }
        $dnkOut('  - ' . $warning . "\n");
        ++$shown;
    }
}

$dnkOut("Delete the pack after import; it contains personal data.\n");

$dnkFinish();
