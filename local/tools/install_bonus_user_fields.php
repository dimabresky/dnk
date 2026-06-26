<?php

/**
 * One-time setup: user fields for imported bonus expiration data.
 *
 * CLI (from site root):
 *   php local/tools/install_bonus_user_fields.php
 *
 * Browser (authorized administrator only):
 *   /local/tools/install_bonus_user_fields.php?run=Y
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

$out = static function (string $message): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDOUT, $message);
    } else {
        echo $message;
    }
};

$fields = [
    [
        'ENTITY_ID' => 'USER',
        'FIELD_NAME' => 'UF_BONUS_EXPIRE_AMOUNT',
        'USER_TYPE_ID' => 'double',
        'XML_ID' => 'UF_BONUS_EXPIRE_AMOUNT',
        'SORT' => 530,
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'N',
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'SETTINGS' => [
            'PRECISION' => 2,
            'SIZE' => 20,
            'MIN_VALUE' => 0,
        ],
        'EDIT_FORM_LABEL' => [
            'ru' => 'Бонусы к ближайшему списанию',
            'en' => 'Bonuses to expire soon',
        ],
        'LIST_COLUMN_LABEL' => [
            'ru' => 'Бонусы к ближайшему списанию',
            'en' => 'Bonuses to expire soon',
        ],
        'LIST_FILTER_LABEL' => [
            'ru' => 'Бонусы к ближайшему списанию',
            'en' => 'Bonuses to expire soon',
        ],
    ],
    [
        'ENTITY_ID' => 'USER',
        'FIELD_NAME' => 'UF_BONUS_EXPIRE_DATE',
        'USER_TYPE_ID' => 'date',
        'XML_ID' => 'UF_BONUS_EXPIRE_DATE',
        'SORT' => 540,
        'MULTIPLE' => 'N',
        'MANDATORY' => 'N',
        'SHOW_FILTER' => 'N',
        'SHOW_IN_LIST' => 'Y',
        'EDIT_IN_LIST' => 'Y',
        'IS_SEARCHABLE' => 'N',
        'EDIT_FORM_LABEL' => [
            'ru' => 'Дата ближайшего списания бонусов',
            'en' => 'Nearest bonus expiration date',
        ],
        'LIST_COLUMN_LABEL' => [
            'ru' => 'Дата ближайшего списания бонусов',
            'en' => 'Nearest bonus expiration date',
        ],
        'LIST_FILTER_LABEL' => [
            'ru' => 'Дата ближайшего списания бонусов',
            'en' => 'Nearest bonus expiration date',
        ],
    ],
];

$entity = new \CUserTypeEntity();
$hasErrors = false;

foreach ($fields as $field) {
    $existing = \CUserTypeEntity::GetList(
        [],
        [
            'ENTITY_ID' => $field['ENTITY_ID'],
            'FIELD_NAME' => $field['FIELD_NAME'],
        ]
    )->Fetch();

    if (is_array($existing)) {
        $out(sprintf("[skip] %s already exists\n", $field['FIELD_NAME']));
        continue;
    }

    $id = (int)$entity->Add($field);
    if ($id <= 0) {
        global $APPLICATION;
        $exception = is_object($APPLICATION) ? $APPLICATION->GetException() : null;
        $error = is_object($exception) && method_exists($exception, 'GetString')
            ? (string)$exception->GetString()
            : '';
        $out(sprintf("[error] failed to create %s %s\n", $field['FIELD_NAME'], $error));
        $hasErrors = true;
        continue;
    }

    $out(sprintf("[ok] created %s ID=%d\n", $field['FIELD_NAME'], $id));
}

$finish();

exit($hasErrors ? 1 : 0);
