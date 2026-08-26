<?php

/**
 * Export catalog product reviews (blog comments).
 * Run on the OLD site (catalog iblock 26). Copy the pack to the new site via SCP, then delete it.
 *
 * Default pack path is outside the web root (../reviews_migrate) so phones/emails are not HTTP-public.
 *
 * CLI (from site root):
 *   php local/tools/export_product_reviews.php
 *   php local/tools/export_product_reviews.php --iblock=26 --out=../reviews_migrate
 *
 * Browser (admin only):
 *   /local/tools/export_product_reviews.php?run=Y
 */

declare(strict_types=1);

use Bitrix\Main\UserPhoneAuthTable;

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
        $dnkErr("400: добавьте к URL параметр run=Y чтобы выполнить экспорт.\n");
        $dnkFinish();
        exit(1);
    }
}

if (!CModule::IncludeModule('iblock') || !CModule::IncludeModule('blog')) {
    $dnkErr("Failed to load iblock or blog module.\n");
    $dnkFinish();
    exit(1);
}

@set_time_limit(0);

/**
 * @param list<string> $argvList
 * @return array{iblock:int,out:string}
 */
$parseArgs = static function (array $argvList, bool $cli): array {
    $iblock = 26;
    $out = '../reviews_migrate';

    if ($cli) {
        foreach (array_slice($argvList, 1) as $arg) {
            if (strpos($arg, '--iblock=') === 0) {
                $iblock = (int) substr($arg, 9);
            } elseif (strpos($arg, '--out=') === 0) {
                $value = trim(substr($arg, 6));
                if ($value !== '') {
                    $out = $value;
                }
            }
        }
    } else {
        if (isset($_GET['iblock']) && filter_var((string) $_GET['iblock'], FILTER_VALIDATE_INT) !== false) {
            $iblock = (int) $_GET['iblock'];
        }
        if (isset($_GET['out']) && trim((string) $_GET['out']) !== '') {
            $out = trim((string) $_GET['out']);
        }
    }

    return ['iblock' => $iblock, 'out' => $out];
};

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

$args = $parseArgs(isset($argv) && is_array($argv) ? $argv : [], $isCli);
$iblockId = $args['iblock'];
$outRel = str_replace('\\', '/', trim($args['out']));
$docRoot = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');
$packRoot = $normalizeFsPath(
    str_starts_with($outRel, '/')
        ? $outRel
        : $docRoot . '/' . $outRel
);
$packBase = basename($packRoot);
if ($outRel === '' || $packBase === '' || $packBase === '.' || $packBase === '..' || $isSameDir($packRoot, $docRoot)) {
    $dnkErr("Refusing pack path that resolves to the site root. Use ../reviews_migrate or a dedicated subdirectory.\n");
    $dnkFinish();
    exit(1);
}
$filesDir = $packRoot . DIRECTORY_SEPARATOR . 'files';

if ($iblockId <= 0) {
    $dnkErr("Invalid iblock id.\n");
    $dnkFinish();
    exit(1);
}

$iblock = CIBlock::GetArrayByID($iblockId);
if (!is_array($iblock)) {
    $dnkErr("Iblock {$iblockId} not found.\n");
    $dnkFinish();
    exit(1);
}

if (!is_dir($filesDir) && !mkdir($filesDir, 0770, true) && !is_dir($filesDir)) {
    $dnkErr("Cannot create pack directory: {$filesDir}\n");
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
    $protectPackFromHttp($filesDir);
}

if (!$isSameDir($packRoot, $docRoot)) {
    @chmod($packRoot, 0770);
    @chmod($filesDir, 0770);
}

$copiedBySrc = [];
$copiedByFileId = [];
$fileIndex = [];
$warnings = [];

/**
 * @return array{pack_path:string,original_src:string,original_name:string}|null
 */
$copyAbsFile = static function (string $absPath, string $webSrc, string $originalName) use (
    &$copiedBySrc,
    &$fileIndex,
    $filesDir
): ?array {
    $webSrc = '/' . ltrim(str_replace('\\', '/', $webSrc), '/');
    if (isset($copiedBySrc[$webSrc])) {
        return $copiedBySrc[$webSrc];
    }
    if ($absPath === '' || !is_file($absPath)) {
        return null;
    }

    $ext = strtolower((string) pathinfo($originalName !== '' ? $originalName : $absPath, PATHINFO_EXTENSION));
    $safeExt = preg_match('/^[a-z0-9]{1,8}$/', $ext) === 1 ? '.' . $ext : '';
    $fileName = sha1($webSrc) . $safeExt;
    $destAbs = $filesDir . DIRECTORY_SEPARATOR . $fileName;
    if (!copy($absPath, $destAbs)) {
        return null;
    }

    $packPath = 'files/' . $fileName;
    $entry = [
        'pack_path' => $packPath,
        'original_src' => $webSrc,
        'original_name' => $originalName !== '' ? $originalName : basename($webSrc),
    ];
    $copiedBySrc[$webSrc] = $entry;
    $fileIndex[$packPath] = $entry;

    return $entry;
};

$copyByFileId = static function (int $fileId) use (
    &$copiedByFileId,
    $copyAbsFile,
    $docRoot,
    &$warnings
): ?array {
    if ($fileId <= 0) {
        return null;
    }
    if (isset($copiedByFileId[$fileId])) {
        return $copiedByFileId[$fileId];
    }

    $file = CFile::GetFileArray($fileId);
    if (!is_array($file) || empty($file['SRC'])) {
        $warnings[] = "File ID {$fileId} not found in b_file.";

        return null;
    }

    $src = (string) $file['SRC'];
    $abs = $docRoot . str_replace('/', DIRECTORY_SEPARATOR, $src);
    $name = (string) ($file['ORIGINAL_NAME'] ?? $file['FILE_NAME'] ?? basename($src));
    $entry = $copyAbsFile($abs, $src, $name);
    if ($entry === null) {
        $warnings[] = "Cannot copy file ID {$fileId} ({$src}).";

        return null;
    }

    $copiedByFileId[$fileId] = $entry;

    return $entry;
};

$decodeText = static function (string $text): string {
    if ($text === '' || !class_exists(\Bitrix\Main\Text\Emoji::class)) {
        return $text;
    }

    try {
        return (string) \Bitrix\Main\Text\Emoji::decode($text);
    } catch (Throwable $e) {
        return $text;
    }
};

/**
 * @return list<string>
 */
$collectUserPhones = static function (int $userId): array {
    if ($userId <= 0) {
        return [];
    }

    $phones = [];
    if (class_exists(UserPhoneAuthTable::class)) {
        try {
            $row = UserPhoneAuthTable::getByPrimary($userId)->fetch();
            $phoneAuth = trim((string) ($row['PHONE_NUMBER'] ?? ''));
            if ($phoneAuth !== '') {
                $phones[$phoneAuth] = true;
            }
        } catch (Throwable $e) {
        }
    }

    $user = CUser::GetByID($userId)->Fetch();
    if (is_array($user)) {
        foreach (['PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'] as $field) {
            $value = trim((string) ($user[$field] ?? ''));
            if ($value !== '') {
                $phones[$value] = true;
            }
        }
    }

    return array_keys($phones);
};

/**
 * @param mixed $value
 * @return list<int>
 */
$normalizeFileIds = static function ($value): array {
    $ids = [];
    $rows = is_array($value) ? $value : [$value];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $row = $row['VALUE'] ?? $row['ID'] ?? $row['id'] ?? 0;
        }
        $fileId = (int) $row;
        if ($fileId > 0) {
            $ids[$fileId] = true;
        }
    }

    return array_keys($ids);
};

$productsByPostId = [];
$elRes = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    [
        'IBLOCK_ID' => $iblockId,
        'CHECK_PERMISSIONS' => 'N',
        '>PROPERTY_BLOG_POST_ID' => 0,
    ],
    false,
    false,
    ['ID', 'NAME', 'PROPERTY_BLOG_POST_ID', 'PROPERTY_ML_ONLINER']
);

while ($row = $elRes->Fetch()) {
    $postId = (int) ($row['PROPERTY_BLOG_POST_ID_VALUE'] ?? $row['PROPERTY_BLOG_POST_ID'] ?? 0);
    if ($postId <= 0) {
        continue;
    }

    $mlOnliner = trim((string) (
        $row['PROPERTY_ML_ONLINER_VALUE']
        ?? $row['PROPERTY_ML_ONLINER']
        ?? ''
    ));

    $productsByPostId[$postId] = [
        'element_id' => (int) ($row['ID'] ?? 0),
        'name' => (string) ($row['NAME'] ?? ''),
        'ml_onliner' => $mlOnliner,
        'blog_post_id' => $postId,
    ];
}

$postIds = array_keys($productsByPostId);
$comments = [];
$authorCache = [];

$commentSelect = [
    'ID',
    'BLOG_ID',
    'POST_ID',
    'PARENT_ID',
    'AUTHOR_ID',
    'AUTHOR_NAME',
    'AUTHOR_EMAIL',
    'DATE_CREATE',
    'TITLE',
    'POST_TEXT',
    'PUBLISH_STATUS',
    'UF_ASPRO_COM_RATING',
    'UF_ASPRO_COM_LIKE',
    'UF_ASPRO_COM_DISLIKE',
    'UF_ASPRO_COM_APPROVE',
    'UF_BLOG_COMMENT_DOC',
];

$publishStatus = defined('BLOG_PUBLISH_STATUS_PUBLISH') ? BLOG_PUBLISH_STATUS_PUBLISH : 'P';

foreach (array_chunk($postIds, 200) as $postChunk) {
    if ($postChunk === []) {
        continue;
    }

    $commentRes = CBlogComment::GetList(
        ['ID' => 'ASC'],
        [
            'POST_ID' => $postChunk,
            'PUBLISH_STATUS' => $publishStatus,
        ],
        false,
        false,
        $commentSelect
    );

    while ($comment = $commentRes->Fetch()) {
        $postId = (int) ($comment['POST_ID'] ?? 0);
        $product = $productsByPostId[$postId] ?? null;
        if (!is_array($product)) {
            continue;
        }

        $authorId = (int) ($comment['AUTHOR_ID'] ?? 0);
        if ($authorId > 0 && !isset($authorCache[$authorId])) {
            $authorCache[$authorId] = $collectUserPhones($authorId);
        }

        $files = [];
        foreach ($normalizeFileIds($comment['UF_BLOG_COMMENT_DOC'] ?? null) as $fileId) {
            $entry = $copyByFileId($fileId);
            if ($entry !== null) {
                $files[] = $entry;
            }
        }

        $parentId = (int) ($comment['PARENT_ID'] ?? 0);
        $comments[] = [
            'id' => (int) ($comment['ID'] ?? 0),
            'blog_id' => (int) ($comment['BLOG_ID'] ?? 0),
            'post_id' => $postId,
            'parent_id' => $parentId > 0 ? $parentId : 0,
            'date_create' => (string) ($comment['DATE_CREATE'] ?? ''),
            'title' => $decodeText((string) ($comment['TITLE'] ?? '')),
            'post_text' => $decodeText((string) ($comment['POST_TEXT'] ?? '')),
            'publish_status' => (string) ($comment['PUBLISH_STATUS'] ?? $publishStatus),
            'rating' => (int) ($comment['UF_ASPRO_COM_RATING'] ?? 0),
            'like' => (int) ($comment['UF_ASPRO_COM_LIKE'] ?? 0),
            'dislike' => (int) ($comment['UF_ASPRO_COM_DISLIKE'] ?? 0),
            'approve' => !empty($comment['UF_ASPRO_COM_APPROVE']) ? 1 : 0,
            'author_id' => $authorId,
            'author_name' => trim((string) ($comment['AUTHOR_NAME'] ?? '')),
            'author_email' => trim((string) ($comment['AUTHOR_EMAIL'] ?? '')),
            'author_phones' => $authorId > 0 ? ($authorCache[$authorId] ?? []) : [],
            'files' => $files,
            'product' => $product,
        ];
    }
}

$manifest = [
    'version' => 1,
    'exported_at' => date('c'),
    'source_iblock' => [
        'ID' => $iblockId,
        'CODE' => (string) ($iblock['CODE'] ?? ''),
        'NAME' => (string) ($iblock['NAME'] ?? ''),
        'IBLOCK_TYPE_ID' => (string) ($iblock['IBLOCK_TYPE_ID'] ?? ''),
    ],
    'site_url' => defined('SITE_SERVER_NAME') ? (string) SITE_SERVER_NAME : '',
    'files' => $fileIndex,
    'comments' => $comments,
    'warnings' => $warnings,
];

$manifestPath = $packRoot . DIRECTORY_SEPARATOR . 'manifest.json';
$json = json_encode(
    $manifest,
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
);
if ($json === false) {
    $dnkErr("Failed to encode manifest.json: " . json_last_error_msg() . "\n");
    $dnkFinish();
    exit(1);
}

if (file_put_contents($manifestPath, $json) === false) {
    $dnkErr("Failed to write {$manifestPath}\n");
    $dnkFinish();
    exit(1);
}

$withOnliner = 0;
foreach ($comments as $comment) {
    if (trim((string) ($comment['product']['ml_onliner'] ?? '')) !== '') {
        ++$withOnliner;
    }
}

$dnkOut("Exported iblock {$iblockId} ({$iblock['CODE']})\n");
$dnkOut('Products with blog posts: ' . count($productsByPostId) . "\n");
$dnkOut('Comments: ' . count($comments) . "\n");
$dnkOut("Comments with ML_ONLINER: {$withOnliner}\n");
$dnkOut('Files: ' . count($fileIndex) . "\n");
$resolvedPack = realpath($packRoot) ?: $packRoot;
$dnkOut("Pack: {$resolvedPack}\n");
$dnkOut("Contains personal data (names, emails, phones, photos). Copy via SCP/SFTP, then delete this directory.\n");
$normalizedDoc = rtrim(str_replace('\\', '/', $docRoot), '/') . '/';
$normalizedPack = rtrim(str_replace('\\', '/', $resolvedPack), '/') . '/';
if (str_starts_with($normalizedPack, $normalizedDoc)) {
    $dnkOut("WARNING: pack is inside the web root. HTTP deny files were written; delete the pack after copy.\n");
}
if ($warnings !== []) {
    $dnkOut('Warnings: ' . count($warnings) . "\n");
    foreach ($warnings as $warning) {
        $dnkOut('  - ' . $warning . "\n");
    }
}

$dnkFinish();
