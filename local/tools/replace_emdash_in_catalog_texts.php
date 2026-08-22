<?php

/**
 * Replace long dashes (em/en dash and HTML entities) with hyphen-minus
 * in PREVIEW_TEXT / DETAIL_TEXT of catalog products (DNK_CATALOG_IBLOCK_ID).
 *
 * Usage (from site root):
 *   php local/tools/replace_emdash_in_catalog_texts.php
 *   php local/tools/replace_emdash_in_catalog_texts.php --apply
 *   php local/tools/replace_emdash_in_catalog_texts.php --apply --limit=500 --from-id=0
 *
 * Default is dry-run (no Update). Pass --apply to write changes.
 */

declare(strict_types=1);

$apply = false;
$limit = 0;
$fromId = 0;
$sampleLimit = 20;

foreach ($argv ?? [] as $arg) {
    $arg = (string) $arg;
    if ($arg === '--apply') {
        $apply = true;
        continue;
    }
    if (preg_match('/^--limit=(\d+)$/', $arg, $m)) {
        $limit = max(0, (int) $m[1]);
        continue;
    }
    if (preg_match('/^--from-id=(\d+)$/', $arg, $m)) {
        $fromId = max(0, (int) $m[1]);
        continue;
    }
    if (preg_match('/^--sample=(\d+)$/', $arg, $m)) {
        $sampleLimit = max(0, (int) $m[1]);
    }
}

/**
 * Replace em/en dashes and common HTML entities with ASCII hyphen-minus.
 */
function dnkReplaceLongDashes(string $text): string
{
    if ($text === '') {
        return $text;
    }

    $replacements = [
        "\u{2014}" => '-', // em dash —
        "\u{2013}" => '-', // en dash –
        '&mdash;' => '-',
        '&#8212;' => '-',
        '&#x2014;' => '-',
        '&#X2014;' => '-',
        '&ndash;' => '-',
        '&#8211;' => '-',
        '&#x2013;' => '-',
        '&#X2013;' => '-',
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
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

if (!defined('DNK_CATALOG_IBLOCK_ID') || (int) DNK_CATALOG_IBLOCK_ID <= 0) {
    fwrite(STDERR, "DNK_CATALOG_IBLOCK_ID is not defined.\n");
    exit(1);
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module is not available.\n");
    exit(1);
}

$iblockId = (int) DNK_CATALOG_IBLOCK_ID;
$processed = 0;
$changed = 0;
$updated = 0;
$errors = 0;
$lastId = $fromId;
$samplesPrinted = 0;

$filter = [
    'IBLOCK_ID' => $iblockId,
    '>ID' => $fromId,
];

$nav = false;
if ($limit > 0) {
    $nav = ['nTopCount' => $limit];
}

$res = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    $filter,
    false,
    $nav,
    [
        'ID',
        'PREVIEW_TEXT',
        'PREVIEW_TEXT_TYPE',
        'DETAIL_TEXT',
        'DETAIL_TEXT_TYPE',
    ]
);

$el = new CIBlockElement();

while ($row = $res->Fetch()) {
    $elementId = (int) ($row['ID'] ?? 0);
    if ($elementId <= 0) {
        continue;
    }

    $lastId = $elementId;
    ++$processed;

    $preview = (string) ($row['PREVIEW_TEXT'] ?? '');
    $detail = (string) ($row['DETAIL_TEXT'] ?? '');
    $newPreview = dnkReplaceLongDashes($preview);
    $newDetail = dnkReplaceLongDashes($detail);

    $fields = [];
    if ($newPreview !== $preview) {
        $fields['PREVIEW_TEXT'] = $newPreview;
        $fields['PREVIEW_TEXT_TYPE'] = (string) ($row['PREVIEW_TEXT_TYPE'] ?? 'text');
    }
    if ($newDetail !== $detail) {
        $fields['DETAIL_TEXT'] = $newDetail;
        $fields['DETAIL_TEXT_TYPE'] = (string) ($row['DETAIL_TEXT_TYPE'] ?? 'text');
    }

    if ($fields === []) {
        continue;
    }

    ++$changed;

    if ($samplesPrinted < $sampleLimit) {
        $changedKeys = array_keys(array_diff_key($fields, ['PREVIEW_TEXT_TYPE' => true, 'DETAIL_TEXT_TYPE' => true]));
        fwrite(STDOUT, sprintf(
            "id=%d fields=%s\n",
            $elementId,
            implode(',', $changedKeys)
        ));
        ++$samplesPrinted;
    }

    if (!$apply) {
        continue;
    }

    if ($el->Update($elementId, $fields)) {
        ++$updated;
    } else {
        ++$errors;
        fwrite(STDERR, sprintf(
            "Update failed id=%d: %s\n",
            $elementId,
            (string) $el->LAST_ERROR
        ));
    }
}

fwrite(STDOUT, sprintf(
    "mode=%s iblock=%d processed=%d changed=%d updated=%d errors=%d from_id=%d last_id=%d limit=%s\n",
    $apply ? 'apply' : 'dry-run',
    $iblockId,
    $processed,
    $changed,
    $updated,
    $errors,
    $fromId,
    $lastId,
    $limit > 0 ? (string) $limit : 'all'
));

exit($errors > 0 ? 1 : 0);
