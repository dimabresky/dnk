<?php

/**
 * Одноразовая установка: тип инфоблока «Программа лояльности» (dnk_loyalty)
 * и инфоблок «Бенефиты» (CODE dnk_benefits) со свойством LEVEL.
 * Повторный запуск не дублирует.
 *
 * CLI (из корня сайта):
 *   php local/tools/install_loyalty_benefits_iblock.php
 *
 * Браузер (только авторизованный администратор):
 *   /local/tools/install_loyalty_benefits_iblock.php?run=Y
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

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    $out("Module iblock is not installed.\n");
    $finish();
    exit(1);
}

$typeId = 'dnk_loyalty';
$iblockCode = 'dnk_benefits';

$existingType = CIBlockType::GetByID($typeId)->Fetch();
if (is_array($existingType)) {
    $out("[skip] iblock type {$typeId} already exists\n");
} else {
    $lang = [];
    $langRes = CLanguage::GetList('sort', 'asc');
    while ($row = $langRes->Fetch()) {
        $lid = (string) ($row['LID'] ?? '');
        if ($lid === '') {
            continue;
        }
        $lang[$lid] = ['NAME' => 'Программа лояльности'];
    }
    if ($lang === []) {
        $lang['ru'] = ['NAME' => 'Программа лояльности'];
    }

    $type = new CIBlockType();
    if (!$type->Add([
        'ID' => $typeId,
        'SECTIONS' => 'N',
        'IN_RSS' => 'N',
        'SORT' => 500,
        'LANG' => $lang,
    ])) {
        $out('[error] CIBlockType::Add failed: ' . $type->LAST_ERROR . "\n");
        $finish();
        exit(1);
    }

    $out("[ok] created iblock type {$typeId}\n");
}

$existingIblock = CIBlock::GetList(
    [],
    ['CODE' => $iblockCode, 'CHECK_PERMISSIONS' => 'N']
)->Fetch();
if (is_array($existingIblock)) {
    $iblockId = (int) ($existingIblock['ID'] ?? 0);
    $out("[skip] iblock {$iblockCode} already exists ID={$iblockId}\n");
} else {
    $siteIds = [];
    $siteRes = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
    while ($site = $siteRes->Fetch()) {
        $lid = (string) ($site['LID'] ?? '');
        if ($lid !== '') {
            $siteIds[] = $lid;
        }
    }
    if ($siteIds === []) {
        $out("[error] no active sites\n");
        $finish();
        exit(1);
    }

    $iblock = new CIBlock();
    $iblockId = (int) $iblock->Add([
        'ACTIVE' => 'Y',
        'NAME' => 'Бенефиты',
        'CODE' => $iblockCode,
        'IBLOCK_TYPE_ID' => $typeId,
        'SITE_ID' => $siteIds,
        'SORT' => 500,
        'LIST_PAGE_URL' => '',
        'DETAIL_PAGE_URL' => '',
        'INDEX_ELEMENT' => 'N',
        'VERSION' => 2,
    ]);
    if ($iblockId <= 0) {
        $out('[error] CIBlock::Add failed: ' . $iblock->LAST_ERROR . "\n");
        $finish();
        exit(1);
    }

    $out("[ok] created iblock {$iblockCode} ID={$iblockId}\n");
}

$existingProperty = CIBlockProperty::GetList([], [
    'IBLOCK_ID' => $iblockId,
    'CODE' => 'LEVEL',
])->Fetch();
if (is_array($existingProperty)) {
    $out(sprintf("[skip] property LEVEL already exists ID=%d\n", (int) ($existingProperty['ID'] ?? 0)));
} else {
    $property = new CIBlockProperty();
    // shortcut: у инфоблока нет типа «Целое число», N — «Число», заменить если появится integer user type
    $propertyId = (int) $property->Add([
        'IBLOCK_ID' => $iblockId,
        'NAME' => 'Уровень',
        'ACTIVE' => 'Y',
        'SORT' => 100,
        'CODE' => 'LEVEL',
        'PROPERTY_TYPE' => 'N',
        'MULTIPLE' => 'N',
    ]);
    if ($propertyId <= 0) {
        $out('[error] CIBlockProperty::Add LEVEL failed: ' . $property->LAST_ERROR . "\n");
        $finish();
        exit(1);
    }

    $out("[ok] created property LEVEL ID={$propertyId}\n");
}

$out("Set .env DNK_BENEFITS_IBLOCK_ID={$iblockId}\n");
$finish();
exit(0);
