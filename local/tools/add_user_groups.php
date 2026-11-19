<?php

/**
 * Всем пользователям дописать группы 3 и 4, если их ещё нет. Остальные группы не трогает.
 * Запуск из корня сайта: php local/tools/add_user_groups.php
 */

declare(strict_types=1);

use Bitrix\Main\GroupTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\UserTable;

const DNK_ADD_USER_GROUPS = [3, 4];
const DNK_ADD_USER_GROUPS_BATCH = 500;

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    header('HTTP/1.1 403 Forbidden');
    echo "CLI only.\n";
    exit(1);
}

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
if ($_SERVER['DOCUMENT_ROOT'] === false) {
    fwrite(STDERR, "Cannot resolve DOCUMENT_ROOT.\n");
    exit(1);
}

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$foundGroupIds = [];
$groupRes = GroupTable::getList([
    'filter' => ['@ID' => DNK_ADD_USER_GROUPS],
    'select' => ['ID'],
]);
while ($group = $groupRes->fetch()) {
    $foundGroupIds[] = (int)$group['ID'];
}

$missingGroupIds = array_values(array_diff(DNK_ADD_USER_GROUPS, $foundGroupIds));
if ($missingGroupIds !== []) {
    fwrite(STDERR, 'Groups not found: ' . implode(', ', $missingGroupIds) . "\n");
    exit(1);
}

$processed = 0;
$added = 0;
$already = 0;
$lastId = 0;

while (true) {
    $userRes = UserTable::getList([
        'select' => ['ID'],
        'filter' => ['>ID' => $lastId],
        'order' => ['ID' => 'ASC'],
        'limit' => DNK_ADD_USER_GROUPS_BATCH,
    ]);

    $userIds = [];
    while ($user = $userRes->fetch()) {
        $userId = (int)($user['ID'] ?? 0);
        if ($userId <= 0) {
            continue;
        }
        $userIds[] = $userId;
        $lastId = $userId;
    }

    if ($userIds === []) {
        break;
    }

    $processed += count($userIds);

    $have = [];
    $linkRes = UserGroupTable::getList([
        'filter' => [
            '@USER_ID' => $userIds,
            '@GROUP_ID' => DNK_ADD_USER_GROUPS,
        ],
        'select' => ['USER_ID', 'GROUP_ID'],
    ]);
    while ($link = $linkRes->fetch()) {
        $have[(int)$link['USER_ID']][(int)$link['GROUP_ID']] = true;
    }

    foreach ($userIds as $userId) {
        foreach (DNK_ADD_USER_GROUPS as $groupId) {
            if (isset($have[$userId][$groupId])) {
                ++$already;
                continue;
            }

            $addResult = UserGroupTable::add([
                'USER_ID' => $userId,
                'GROUP_ID' => $groupId,
            ]);
            if (!$addResult->isSuccess()) {
                fwrite(
                    STDERR,
                    "Failed user {$userId} group {$groupId}: "
                    . implode('; ', $addResult->getErrorMessages())
                    . "\n"
                );
                exit(1);
            }
            ++$added;
        }
    }
}

echo "Processed users: {$processed}\n";
echo "Links added: {$added}\n";
echo "Links already present: {$already}\n";
