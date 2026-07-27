<?php

/**
 * Выгрузка пользователей (ID, ФИО, телефон) в CSV в /upload/.
 *
 * CLI (из корня сайта):
 *   php local/tools/export_users_csv.php
 *
 * Браузер (только авторизованный администратор):
 *   /local/tools/export_users_csv.php?run=Y
 *   /local/tools/export_users_csv.php?run=Y&last_id=12345&file=users_export_20260715_150200.csv&exported=5000&total=52341
 */

declare(strict_types=1);

use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;

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

const DNK_EXPORT_USERS_BATCH_SIZE = 500;
const DNK_EXPORT_USERS_FILE_PATTERN = '/^users_export_\d{8}_\d{6}\.csv$/';

$dnkFinish = static function () use ($isCli): void {
    if (!$isCli && \is_string($_SERVER['DOCUMENT_ROOT'])) {
        require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
    }
};

$dnkOut = static function (string $msg) use ($isCli): void {
    if ($isCli) {
        fwrite(STDOUT, $msg);
    } else {
        echo $msg;
    }
};

$dnkFail = static function (int $code, string $msg) use ($dnkFinish, $isCli): void {
    if (!$isCli) {
        header('Content-Type: text/plain; charset=UTF-8');
        if ($code === 403) {
            header('HTTP/1.1 403 Forbidden');
        } elseif ($code === 400) {
            header('HTTP/1.1 400 Bad Request');
        } else {
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    if ($isCli) {
        fwrite(STDERR, $msg . "\n");
    } else {
        echo $msg . "\n";
    }

    $dnkFinish();
    exit($code > 0 ? 1 : 0);
};

if (!$isCli) {
    if (
        !isset($GLOBALS['USER'])
        || !\is_object($GLOBALS['USER'])
        || !$GLOBALS['USER']->IsAuthorized()
        || !$GLOBALS['USER']->IsAdmin()
    ) {
        $dnkFail(403, '403 Forbidden: войдите в систему как администратор.');
    }

    if (($_REQUEST['run'] ?? '') !== 'Y') {
        $dnkFail(400, '400: добавьте к URL параметр run=Y чтобы запустить выгрузку.');
    }
}

/**
 * @param array<string, mixed> $user
 */
$buildFio = static function (array $user): string {
    return trim(implode(' ', array_filter([
        (string)($user['LAST_NAME'] ?? ''),
        (string)($user['NAME'] ?? ''),
        (string)($user['SECOND_NAME'] ?? ''),
    ])));
};

/**
 * @param array<string, mixed> $user
 */
$resolvePhone = static function (array $user, ?string $phoneAuth): string {
    $phoneAuth = trim((string)$phoneAuth);
    if ($phoneAuth !== '') {
        return $phoneAuth;
    }

    foreach (['PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'] as $field) {
        $value = trim((string)($user[$field] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }

    return '';
};

$validateFileName = static function (string $fileName): bool {
    return $fileName !== '' && preg_match(DNK_EXPORT_USERS_FILE_PATTERN, $fileName) === 1;
};

/**
 * @return array{last_id: int, exported: int, has_more: bool}
 */
$processBatch = static function (
    string $filePath,
    int $lastId,
    int $exportedSoFar,
    bool $writeHeader
) use ($buildFio, $resolvePhone): array {
    $handle = fopen($filePath, $writeHeader ? 'wb' : 'ab');
    if ($handle === false) {
        throw new RuntimeException('Cannot open CSV file for writing: ' . $filePath);
    }

    if ($writeHeader) {
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['ID', 'ФИО', 'Телефон'], ';');
    }

    $result = UserTable::getList([
        'select' => [
            'ID',
            'NAME',
            'LAST_NAME',
            'SECOND_NAME',
            'PERSONAL_PHONE',
            'PERSONAL_MOBILE',
            'WORK_PHONE',
        ],
        'filter' => ['>ID' => $lastId],
        'order' => ['ID' => 'ASC'],
        'limit' => DNK_EXPORT_USERS_BATCH_SIZE,
    ]);

    $users = [];
    $userIds = [];
    $batchLastId = $lastId;

    while ($row = $result->fetch()) {
        $userId = (int)($row['ID'] ?? 0);
        if ($userId <= 0) {
            continue;
        }

        $users[] = $row;
        $userIds[] = $userId;
        $batchLastId = $userId;
    }

    $phoneMap = [];
    if ($userIds !== []) {
        $phoneRes = UserPhoneAuthTable::getList([
            'filter' => ['@USER_ID' => $userIds],
            'select' => ['USER_ID', 'PHONE_NUMBER'],
        ]);

        while ($phoneRow = $phoneRes->fetch()) {
            $uid = (int)($phoneRow['USER_ID'] ?? 0);
            if ($uid <= 0) {
                continue;
            }

            $phoneMap[$uid] = (string)($phoneRow['PHONE_NUMBER'] ?? '');
        }
    }

    $batchExported = 0;
    foreach ($users as $user) {
        $userId = (int)($user['ID'] ?? 0);
        fputcsv($handle, [
            (string)$userId,
            $buildFio($user),
            $resolvePhone($user, $phoneMap[$userId] ?? null),
        ], ';');
        ++$batchExported;
    }

    fclose($handle);

    $hasMore = \count($users) === DNK_EXPORT_USERS_BATCH_SIZE;

    return [
        'last_id' => $batchLastId,
        'exported' => $exportedSoFar + $batchExported,
        'has_more' => $hasMore,
    ];
};

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/upload';
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
    $dnkFail(500, 'Cannot create upload directory.');
}

set_time_limit(120);
ignore_user_abort(true);

if ($isCli) {
    $fileName = 'users_export_' . date('Ymd_His') . '.csv';
    $filePath = $uploadDir . '/' . $fileName;
    $lastId = 0;
    $exported = 0;
    $total = (int)UserTable::getCount([]);
    $writeHeader = true;

    $dnkOut("Exporting {$total} users to /upload/{$fileName}\n");

    do {
        $batch = $processBatch($filePath, $lastId, $exported, $writeHeader);
        $lastId = $batch['last_id'];
        $exported = $batch['exported'];
        $writeHeader = false;

        $dnkOut("Exported: {$exported} / {$total}\n");
    } while ($batch['has_more']);

    $dnkOut("Done. File: /upload/{$fileName}\n");
    $dnkOut("Total rows: {$exported}\n");

    $dnkFinish();
    exit(0);
}

$lastId = max(0, (int)($_REQUEST['last_id'] ?? 0));
$exportedSoFar = max(0, (int)($_REQUEST['exported'] ?? 0));
$totalCount = max(0, (int)($_REQUEST['total'] ?? 0));
$requestFile = trim((string)($_REQUEST['file'] ?? ''));

$isNewExport = $lastId === 0 && $requestFile === '';

if ($isNewExport) {
    $fileName = 'users_export_' . date('Ymd_His') . '.csv';
    $totalCount = (int)UserTable::getCount([]);
    $exportedSoFar = 0;
    $writeHeader = true;
} else {
    if (!$validateFileName($requestFile)) {
        $dnkFail(400, '400: неверное имя файла выгрузки.');
    }

    $fileName = $requestFile;
    $writeHeader = false;

    if ($totalCount <= 0) {
        $totalCount = (int)UserTable::getCount([]);
    }
}

$filePath = $uploadDir . '/' . $fileName;

try {
    $batch = $processBatch($filePath, $lastId, $exportedSoFar, $writeHeader);
} catch (RuntimeException $e) {
    $dnkFail(500, '500: ' . $e->getMessage());
}

/** @var array{last_id: int, exported: int, has_more: bool} $batch */
$exported = $batch['exported'];
$nextLastId = $batch['last_id'];

if ($batch['has_more']) {
    $nextUrl = '/local/tools/export_users_csv.php?run=Y'
        . '&last_id=' . $nextLastId
        . '&file=' . rawurlencode($fileName)
        . '&exported=' . $exported
        . '&total=' . $totalCount;

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8">';
    echo '<meta http-equiv="refresh" content="0.5;url=' . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . '">';
    echo '<title>Выгрузка пользователей</title></head><body>';
    echo '<p>Выгружено: <strong>' . (int)$exported . ' / ' . (int)$totalCount . '</strong></p>';
    echo '<p>Файл: <code>/upload/' . htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8') . '</code></p>';
    echo '<p>Следующий батч через 0.5 с…</p>';
    echo '<p><a href="' . htmlspecialchars($nextUrl, ENT_QUOTES, 'UTF-8') . '">Продолжить вручную</a></p>';
    echo '</body></html>';

    $dnkFinish();
    exit(0);
}

header('Content-Type: text/plain; charset=UTF-8');
echo "Выгрузка завершена.\n";
echo 'Файл: /upload/' . $fileName . "\n";
echo 'Строк данных: ' . $exported . "\n";
echo 'Всего пользователей в базе: ' . $totalCount . "\n";

$dnkFinish();
