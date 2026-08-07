<?php

/**
 * One-time setup: FEED_PICTURE file property on catalog iblock + queue table.
 *
 * CLI (from site root):
 *   php local/tools/install_feed_picture_property.php
 *
 * Browser (authorized administrator only):
 *   /local/tools/install_feed_picture_property.php?run=Y
 */

declare(strict_types=1);

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

$finish = static function () use ($isCli): void {
    if (!$isCli && \is_string($_SERVER['DOCUMENT_ROOT'])) {
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    }
};

$out = static function (string $message): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDOUT, $message);
    } else {
        echo $message;
    }
};

$err = static function (string $message): void {
    fwrite(STDERR, $message);
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
        echo "403 Forbidden: войдите в систему как администратор.\n";
        $finish();
        exit(1);
    }

    if (($_REQUEST['run'] ?? '') !== 'Y') {
        header('HTTP/1.1 400 Bad Request');
        echo "400: добавьте к URL параметр run=Y чтобы выполнить установку.\n";
        $finish();
        exit(1);
    }
}

if (!defined('DNK_CATALOG_IBLOCK_ID') || (int)DNK_CATALOG_IBLOCK_ID <= 0) {
    $err("DNK_CATALOG_IBLOCK_ID is not defined.\n");
    $finish();
    exit(1);
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    $err("iblock module is not available.\n");
    $finish();
    exit(1);
}

$iblockId = (int)DNK_CATALOG_IBLOCK_ID;
$propertyCode = 'FEED_PICTURE';

$existing = \CIBlockProperty::GetList([], [
    'IBLOCK_ID' => $iblockId,
    'CODE' => $propertyCode,
])->Fetch();

if (is_array($existing)) {
    $out(sprintf(
        "Property %s already exists (ID=%d) on iblock %d.\n",
        $propertyCode,
        (int)$existing['ID'],
        $iblockId
    ));
} else {
    $propId = (new \CIBlockProperty())->Add([
        'IBLOCK_ID' => $iblockId,
        'NAME' => 'Картинка для фида',
        'ACTIVE' => 'Y',
        'SORT' => 900,
        'CODE' => $propertyCode,
        'PROPERTY_TYPE' => 'F',
        'FILE_TYPE' => 'webp, jpg, jpeg, png, gif',
        'MULTIPLE' => 'N',
        'FILTRABLE' => 'N',
        'SEARCHABLE' => 'N',
        'WITH_DESCRIPTION' => 'Y',
    ]);

    if (!$propId) {
        $err("Failed to create property {$propertyCode}.\n");
        $finish();
        exit(1);
    }

    $out(sprintf("Created property %s (ID=%d) on iblock %d.\n", $propertyCode, (int)$propId, $iblockId));
}

$connection = \Bitrix\Main\Application::getConnection();
$tableName = 'b_dnk_feed_picture_queue';
if ($connection->isTableExists($tableName)) {
    $out("Table {$tableName} already exists.\n");
} else {
    $sqlPath = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/migrations/dnk_feed_picture_queue.sql';
    if (!is_file($sqlPath)) {
        $err("Migration file not found: {$sqlPath}\n");
        $finish();
        exit(1);
    }

    $sql = (string)file_get_contents($sqlPath);
    $sql = trim(preg_replace('/^--.*$/m', '', $sql) ?? $sql);
    if ($sql === '') {
        $err("Migration SQL is empty.\n");
        $finish();
        exit(1);
    }

    $connection->queryExecute($sql);
    $out("Created table {$tableName}.\n");
}

$out("Done.\n");
$finish();
exit(0);
