<?php

/**
 * Import blog articles pack (upload/article_migrate) into infoblock 17.
 *
 * CLI (from site root):
 *   php local/tools/import_articles.php --dry-run
 *   php local/tools/import_articles.php --apply
 *   php local/tools/import_articles.php --apply --update-existing
 *   php local/tools/import_articles.php --dry-run --pack=upload/article_migrate --iblock=17
 *
 * Browser (admin only):
 *   /local/tools/import_articles.php?run=Y&mode=dry-run
 *   /local/tools/import_articles.php?run=Y&mode=apply
 *   /local/tools/import_articles.php?run=Y&mode=apply&update_existing=Y
 */

declare(strict_types=1);

use Bitrix\Iblock\InheritedProperty\ElementTemplates;
use Bitrix\Iblock\InheritedProperty\SectionTemplates;
use Bitrix\Main\Loader;

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
        $dnkErr("400: добавьте к URL параметр run=Y чтобы выполнить импорт.\n");
        $dnkFinish();
        exit(1);
    }
}

if (!Loader::includeModule('iblock')) {
    $dnkErr("Failed to load iblock module.\n");
    $dnkFinish();
    exit(1);
}

@set_time_limit(0);

/**
 * @param list<string> $argvList
 * @return array{mode:string,update_existing:bool,iblock:int,pack:string}
 */
$parseArgs = static function (array $argvList, bool $cli): array {
    $mode = 'dry-run';
    $updateExisting = false;
    $iblock = 17;
    $pack = 'upload/article_migrate';

    if ($cli) {
        foreach (array_slice($argvList, 1) as $arg) {
            if ($arg === '--dry-run') {
                $mode = 'dry-run';
            } elseif ($arg === '--apply') {
                $mode = 'apply';
            } elseif ($arg === '--update-existing') {
                $updateExisting = true;
            } elseif (str_starts_with($arg, '--iblock=')) {
                $iblock = (int) substr($arg, 9);
            } elseif (str_starts_with($arg, '--pack=')) {
                $pack = trim(substr($arg, 7));
            }
        }
    } else {
        $rawMode = strtolower(trim((string) ($_GET['mode'] ?? 'dry-run')));
        $mode = $rawMode === 'apply' ? 'apply' : 'dry-run';
        $updateExisting = (string) ($_GET['update_existing'] ?? '') === 'Y';
        if (isset($_GET['iblock']) && filter_var((string) $_GET['iblock'], FILTER_VALIDATE_INT) !== false) {
            $iblock = (int) $_GET['iblock'];
        }
        if (isset($_GET['pack']) && trim((string) $_GET['pack']) !== '') {
            $pack = trim((string) $_GET['pack']);
        }
    }

    return [
        'mode' => $mode,
        'update_existing' => $updateExisting,
        'iblock' => $iblock,
        'pack' => $pack,
    ];
};

$args = $parseArgs(isset($argv) && is_array($argv) ? $argv : [], $isCli);

if ($args['iblock'] <= 0) {
    $dnkErr("Invalid iblock id.\n");
    $dnkFinish();
    exit(1);
}

$iblock = CIBlock::GetArrayByID($args['iblock']);
if (!is_array($iblock)) {
    $dnkErr("Iblock {$args['iblock']} not found.\n");
    $dnkFinish();
    exit(1);
}

$docRoot = rtrim((string) $_SERVER['DOCUMENT_ROOT'], '/\\');
$packRel = str_replace('\\', '/', ltrim($args['pack'], '/'));
$packRoot = $docRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $packRel);
$manifestPath = $packRoot . DIRECTORY_SEPARATOR . 'manifest.json';

if (!is_file($manifestPath)) {
    $dnkErr("manifest.json not found: {$manifestPath}\n");
    $dnkErr("Copy the export pack from the old site to /{$packRel}/\n");
    $dnkFinish();
    exit(1);
}

$manifestRaw = file_get_contents($manifestPath);
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
if (!is_array($manifest)) {
    $dnkErr("Cannot parse manifest.json: " . json_last_error_msg() . "\n");
    $dnkFinish();
    exit(1);
}

$sourceSections = is_array($manifest['sections'] ?? null) ? $manifest['sections'] : [];
$sourceElements = is_array($manifest['elements'] ?? null) ? $manifest['elements'] : [];
$sourceProperties = is_array($manifest['properties'] ?? null) ? $manifest['properties'] : [];
$htmlPathMap = is_array($manifest['html_path_map'] ?? null) ? $manifest['html_path_map'] : [];
$packFiles = is_array($manifest['files'] ?? null) ? $manifest['files'] : [];

$targetProps = [];
$propRes = CIBlockProperty::GetList(
    ['SORT' => 'ASC', 'ID' => 'ASC'],
    ['IBLOCK_ID' => $args['iblock']]
);
while ($prop = $propRes->Fetch()) {
    $code = (string) ($prop['CODE'] ?? '');
    if ($code === '') {
        continue;
    }
    $enums = [];
    if (($prop['PROPERTY_TYPE'] ?? '') === 'L') {
        $enumRes = CIBlockPropertyEnum::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['PROPERTY_ID' => (int) $prop['ID']]
        );
        while ($enum = $enumRes->Fetch()) {
            $enums[] = [
                'ID' => (int) $enum['ID'],
                'VALUE' => (string) ($enum['VALUE'] ?? ''),
                'XML_ID' => (string) ($enum['XML_ID'] ?? ''),
            ];
        }
    }
    $targetProps[$code] = [
        'ID' => (int) $prop['ID'],
        'CODE' => $code,
        'PROPERTY_TYPE' => (string) ($prop['PROPERTY_TYPE'] ?? 'S'),
        'MULTIPLE' => (string) ($prop['MULTIPLE'] ?? 'N'),
        'USER_TYPE' => (string) ($prop['USER_TYPE'] ?? ''),
        'ENUM' => $enums,
    ];
}

$sourcePropCodes = [];
foreach ($sourceProperties as $srcProp) {
    if (is_array($srcProp) && ($srcProp['CODE'] ?? '') !== '') {
        $sourcePropCodes[(string) $srcProp['CODE']] = $srcProp;
    }
}

$missingOnTarget = array_values(array_diff(array_keys($sourcePropCodes), array_keys($targetProps)));
$missingOnSource = array_values(array_diff(array_keys($targetProps), array_keys($sourcePropCodes)));

$findSectionByCode = static function (int $iblockId, string $code): ?int {
    if ($code === '') {
        return null;
    }
    $row = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID']
    )->Fetch();

    return is_array($row) ? (int) $row['ID'] : null;
};

$findElementByCode = static function (int $iblockId, string $code): ?int {
    if ($code === '') {
        return null;
    }
    $row = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $code, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    )->Fetch();

    return is_array($row) ? (int) $row['ID'] : null;
};

$absPackFile = static function (string $packPath) use ($packRoot): string {
    $packPath = str_replace(['\\', '..'], ['/', ''], $packPath);
    $packPath = ltrim($packPath, '/');

    return $packRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $packPath);
};

$htmlSrcCache = [];

$uploadHtmlFile = static function (string $packPath) use (&$htmlSrcCache, $absPackFile, &$dnkErr): ?string {
    if (isset($htmlSrcCache[$packPath])) {
        return $htmlSrcCache[$packPath];
    }
    $abs = $absPackFile($packPath);
    if (!is_file($abs)) {
        return null;
    }
    $fileArray = CFile::MakeFileArray($abs);
    if (!is_array($fileArray) || empty($fileArray['tmp_name'])) {
        return null;
    }
    $fileArray['MODULE_ID'] = 'iblock';
    $fileId = CFile::SaveFile($fileArray, 'iblock');
    if (!$fileId) {
        return null;
    }
    $src = CFile::GetPath((int) $fileId);
    if (!is_string($src) || $src === '') {
        return null;
    }
    $htmlSrcCache[$packPath] = $src;

    return $src;
};

$rewriteHtml = static function (string $html) use ($htmlPathMap, $uploadHtmlFile): string {
    if ($html === '') {
        return $html;
    }
    $pairs = [];
    foreach ($htmlPathMap as $oldSrc => $packPath) {
        $oldSrc = (string) $oldSrc;
        $packPath = (string) $packPath;
        $newSrc = $uploadHtmlFile($packPath);
        if ($newSrc === null || $oldSrc === '') {
            continue;
        }
        $pairs[$oldSrc] = $newSrc;
    }
    uksort($pairs, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
    foreach ($pairs as $from => $to) {
        $html = str_replace($from, $to, $html);
    }

    return $html;
};

$makeFileArray = static function (string $packPath) use ($absPackFile): ?array {
    $abs = $absPackFile($packPath);
    if (!is_file($abs)) {
        return null;
    }
    $fileArray = CFile::MakeFileArray($abs);

    return is_array($fileArray) && !empty($fileArray['tmp_name']) ? $fileArray : null;
};

$mapEnumId = static function (array $targetProp, array $value): ?int {
    $xmlId = (string) ($value['XML_ID'] ?? '');
    $label = (string) ($value['VALUE'] ?? '');
    foreach ($targetProp['ENUM'] as $enum) {
        if ($xmlId !== '' && $xmlId === (string) $enum['XML_ID']) {
            return (int) $enum['ID'];
        }
    }
    foreach ($targetProp['ENUM'] as $enum) {
        if ($label !== '' && $label === (string) $enum['VALUE']) {
            return (int) $enum['ID'];
        }
    }

    return null;
};

/**
 * @return array{values:array,skipped:list<string>}
 */
$buildPropertyValues = static function (
    array $sourceProperties,
    bool $applyHtmlRewrite
) use ($targetProps, $mapEnumId, $makeFileArray, $rewriteHtml): array {
    $values = [];
    $skipped = [];

    foreach ($sourceProperties as $code => $prop) {
        $code = (string) $code;
        if (!isset($targetProps[$code]) || !is_array($prop)) {
            if (!isset($targetProps[$code])) {
                $skipped[] = "{$code}: property missing on target iblock";
            }
            continue;
        }

        $target = $targetProps[$code];
        $type = (string) ($prop['PROPERTY_TYPE'] ?? $target['PROPERTY_TYPE']);
        $userType = (string) ($prop['USER_TYPE'] ?? '');
        $rows = is_array($prop['VALUES'] ?? null) ? $prop['VALUES'] : [];

        if ($type === 'E' || $type === 'G') {
            $skipped[] = "{$code}: skip linked " . ($type === 'E' ? 'elements' : 'sections') . ' (old IDs)';
            continue;
        }

        if ($type === 'F') {
            $files = [];
            foreach ($rows as $row) {
                $packPath = is_array($row) ? (string) ($row['pack_path'] ?? '') : '';
                if ($packPath === '') {
                    continue;
                }
                $fileArray = $makeFileArray($packPath);
                if ($fileArray === null) {
                    $skipped[] = "{$code}: file not found in pack ({$packPath})";
                    continue;
                }
                $files[] = $fileArray;
            }
            if ($files === []) {
                continue;
            }
            $values[$code] = $target['MULTIPLE'] === 'Y' ? $files : $files[0];
            continue;
        }

        if ($type === 'L') {
            $enumIds = [];
            foreach ($rows as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $enumId = $mapEnumId($target, $row);
                if ($enumId === null) {
                    $skipped[] = "{$code}: enum not mapped (XML_ID="
                        . ($row['XML_ID'] ?? '') . ', VALUE=' . ($row['VALUE'] ?? '') . ')';
                    continue;
                }
                $enumIds[] = $enumId;
            }
            if ($enumIds === []) {
                continue;
            }
            $values[$code] = $target['MULTIPLE'] === 'Y' ? $enumIds : $enumIds[0];
            continue;
        }

        if ($userType === 'HTML') {
            $htmlValues = [];
            foreach ($rows as $row) {
                $text = is_array($row) ? (string) ($row['TEXT'] ?? '') : (string) $row;
                $typeHtml = is_array($row) ? (string) ($row['TYPE'] ?? 'HTML') : 'HTML';
                if ($applyHtmlRewrite) {
                    $text = $rewriteHtml($text);
                }
                $htmlValues[] = ['VALUE' => ['TEXT' => $text, 'TYPE' => $typeHtml]];
            }
            if ($htmlValues === []) {
                continue;
            }
            $values[$code] = $target['MULTIPLE'] === 'Y' ? $htmlValues : $htmlValues[0];
            continue;
        }

        $scalars = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                continue;
            }
            $scalars[] = (string) $row;
        }
        if ($scalars === []) {
            continue;
        }
        $values[$code] = $target['MULTIPLE'] === 'Y' ? $scalars : $scalars[0];
    }

    return ['values' => $values, 'skipped' => $skipped];
};

$applySeo = static function (string $className, int $iblockId, int $entityId, array $templates): void {
    if ($templates === [] || !class_exists($className)) {
        return;
    }
    try {
        $object = new $className($iblockId, $entityId);
        if (method_exists($object, 'set')) {
            $object->set($templates);
        }
    } catch (Throwable $e) {
        // SEO is optional; keep import going.
    }
};

$sectionPlan = [
    'create' => [],
    'exists' => [],
    'empty_code' => [],
];
foreach ($sourceSections as $section) {
    if (!is_array($section)) {
        continue;
    }
    $code = trim((string) ($section['CODE'] ?? ''));
    $name = (string) ($section['NAME'] ?? '');
    if ($code === '') {
        $sectionPlan['empty_code'][] = $name !== '' ? $name : ('ID ' . ($section['ID'] ?? '?'));
        continue;
    }
    $existing = $findSectionByCode($args['iblock'], $code);
    $row = ['CODE' => $code, 'NAME' => $name, 'existing_id' => $existing];
    if ($existing !== null) {
        $sectionPlan['exists'][] = $row;
    } else {
        $sectionPlan['create'][] = $row;
    }
}

$elementPlan = [
    'create' => [],
    'exists' => [],
    'empty_code' => [],
];
$elementFileMissing = [];
$elementHtmlImages = 0;
$linkedPropertyNotes = [];

foreach ($htmlPathMap as $oldSrc => $packPath) {
    if (!is_file($absPackFile((string) $packPath))) {
        $elementFileMissing[] = 'HTML ' . $oldSrc . ' → ' . $packPath;
    }
}

foreach ($sourceElements as $element) {
    if (!is_array($element)) {
        continue;
    }
    $code = trim((string) ($element['CODE'] ?? ''));
    $name = (string) ($element['NAME'] ?? '');
    if ($code === '') {
        $elementPlan['empty_code'][] = $name !== '' ? $name : ('ID ' . ($element['ID'] ?? '?'));
        continue;
    }
    $existing = $findElementByCode($args['iblock'], $code);
    $row = ['CODE' => $code, 'NAME' => $name, 'existing_id' => $existing];
    if ($existing !== null) {
        $elementPlan['exists'][] = $row;
    } else {
        $elementPlan['create'][] = $row;
    }

    foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $picField) {
        $packPath = (string) ($element[$picField] ?? '');
        if ($packPath !== '' && !is_file($absPackFile($packPath))) {
            $elementFileMissing[] = "{$code} {$picField} → {$packPath}";
        }
    }

    $props = is_array($element['PROPERTIES'] ?? null) ? $element['PROPERTIES'] : [];
    $built = $buildPropertyValues($props, false);
    foreach ($built['skipped'] as $note) {
        $linkedPropertyNotes[] = "{$code}: {$note}";
    }

    $preview = (string) ($element['PREVIEW_TEXT'] ?? '');
    $detail = (string) ($element['DETAIL_TEXT'] ?? '');
    foreach ($htmlPathMap as $oldSrc => $_pack) {
        $oldSrc = (string) $oldSrc;
        if ($oldSrc !== '' && (str_contains($preview, $oldSrc) || str_contains($detail, $oldSrc))) {
            $elementHtmlImages++;
        }
    }
}

$dnkOut("Target iblock: {$args['iblock']} ({$iblock['CODE']})\n");
$dnkOut('Mode: ' . $args['mode'] . ($args['update_existing'] ? ' + update-existing' : '') . "\n");
$dnkOut("Pack: /{$packRel}/\n");
$dnkOut('Source sections: ' . count($sourceSections) . "\n");
$dnkOut('Source elements: ' . count($sourceElements) . "\n");
$dnkOut("\nSections:\n");
$dnkOut('  create: ' . count($sectionPlan['create']) . "\n");
$dnkOut('  already exist (CODE): ' . count($sectionPlan['exists']) . "\n");
$dnkOut('  empty CODE (skip): ' . count($sectionPlan['empty_code']) . "\n");
$dnkOut("\nElements:\n");
$dnkOut('  create: ' . count($elementPlan['create']) . "\n");
$dnkOut('  already exist (CODE): ' . count($elementPlan['exists']) . "\n");
$dnkOut('  empty CODE (skip): ' . count($elementPlan['empty_code']) . "\n");
$dnkOut('  elements with inline /upload/ images: ' . $elementHtmlImages . "\n");
$dnkOut('  html_path_map entries: ' . count($htmlPathMap) . "\n");
$dnkOut('  pack files: ' . count($packFiles) . "\n");

if ($missingOnTarget !== []) {
    $dnkOut("\nProperties on source missing on target (will skip):\n");
    foreach ($missingOnTarget as $code) {
        $dnkOut('  - ' . $code . "\n");
    }
}
if ($missingOnSource !== []) {
    $dnkOut("\nProperties on target missing on source (stay empty):\n");
    foreach ($missingOnSource as $code) {
        $dnkOut('  - ' . $code . "\n");
    }
}
if ($sectionPlan['exists'] !== []) {
    $dnkOut("\nDuplicate sections:\n");
    foreach ($sectionPlan['exists'] as $row) {
        $dnkOut("  - {$row['CODE']} (ID {$row['existing_id']}) {$row['NAME']}\n");
    }
}
if ($elementPlan['exists'] !== []) {
    $dnkOut("\nDuplicate elements:\n");
    foreach ($elementPlan['exists'] as $row) {
        $dnkOut("  - {$row['CODE']} (ID {$row['existing_id']}) {$row['NAME']}\n");
    }
}
if ($sectionPlan['empty_code'] !== []) {
    $dnkOut("\nSections with empty CODE:\n");
    foreach ($sectionPlan['empty_code'] as $label) {
        $dnkOut('  - ' . $label . "\n");
    }
}
if ($elementPlan['empty_code'] !== []) {
    $dnkOut("\nElements with empty CODE:\n");
    foreach ($elementPlan['empty_code'] as $label) {
        $dnkOut('  - ' . $label . "\n");
    }
}
if ($elementFileMissing !== []) {
    $dnkOut("\nMissing pack files:\n");
    foreach ($elementFileMissing as $line) {
        $dnkOut('  - ' . $line . "\n");
    }
}
if ($linkedPropertyNotes !== []) {
    $uniqueNotes = array_values(array_unique($linkedPropertyNotes));
    $dnkOut("\nProperty skip notes (" . count($uniqueNotes) . "):\n");
    foreach (array_slice($uniqueNotes, 0, 50) as $note) {
        $dnkOut('  - ' . $note . "\n");
    }
    if (count($uniqueNotes) > 50) {
        $dnkOut('  ... ' . (count($uniqueNotes) - 50) . " more\n");
    }
}

if ($args['mode'] !== 'apply') {
    $dnkOut("\nDry-run only. Nothing was written.\n");
    $dnkOut("Re-run with --apply to create missing sections/elements (duplicates skipped).\n");
    $dnkOut("Use --update-existing together with --apply to overwrite duplicates.\n");
    $dnkFinish();
    exit(0);
}

$dnkOut("\nApplying import...\n");

$oldToNewSection = [];
$sectionErrors = [];
$sectionCreated = 0;
$sectionUpdated = 0;
$sectionSkipped = 0;

usort(
    $sourceSections,
    static function (array $a, array $b): int {
        return ((int) ($a['DEPTH_LEVEL'] ?? 0)) <=> ((int) ($b['DEPTH_LEVEL'] ?? 0));
    }
);

$sectionApi = new CIBlockSection();

foreach ($sourceSections as $section) {
    if (!is_array($section)) {
        continue;
    }
    $oldId = (int) ($section['ID'] ?? 0);
    $code = trim((string) ($section['CODE'] ?? ''));
    if ($code === '') {
        $sectionSkipped++;
        continue;
    }

    $parentOld = (int) ($section['IBLOCK_SECTION_ID'] ?? 0);
    $parentNew = $parentOld > 0 ? ($oldToNewSection[$parentOld] ?? 0) : 0;
    $existingId = $findSectionByCode($args['iblock'], $code);

    $fields = [
        'IBLOCK_ID' => $args['iblock'],
        'IBLOCK_SECTION_ID' => $parentNew > 0 ? $parentNew : false,
        'NAME' => (string) ($section['NAME'] ?? $code),
        'CODE' => $code,
        'ACTIVE' => (string) ($section['ACTIVE'] ?? 'Y'),
        'SORT' => (int) ($section['SORT'] ?? 500),
        'DESCRIPTION' => $rewriteHtml((string) ($section['DESCRIPTION'] ?? '')),
        'DESCRIPTION_TYPE' => (string) ($section['DESCRIPTION_TYPE'] ?? 'text'),
        'XML_ID' => (string) ($section['XML_ID'] ?? ''),
    ];

    foreach (['PICTURE', 'DETAIL_PICTURE'] as $picField) {
        $packPath = (string) ($section[$picField] ?? '');
        if ($packPath !== '') {
            $fileArray = $makeFileArray($packPath);
            if ($fileArray !== null) {
                $fields[$picField] = $fileArray;
            }
        }
    }

    if ($existingId !== null) {
        $oldToNewSection[$oldId] = $existingId;
        if (!$args['update_existing']) {
            $sectionSkipped++;
            continue;
        }
        if (!$sectionApi->Update($existingId, $fields)) {
            $sectionErrors[] = "{$code}: " . $sectionApi->LAST_ERROR;
            continue;
        }
        $applySeo(SectionTemplates::class, $args['iblock'], $existingId, (array) ($section['IPROPERTY_TEMPLATES'] ?? []));
        $sectionUpdated++;
        continue;
    }

    $newId = (int) $sectionApi->Add($fields);
    if ($newId <= 0) {
        $sectionErrors[] = "{$code}: " . $sectionApi->LAST_ERROR;
        continue;
    }
    $oldToNewSection[$oldId] = $newId;
    $applySeo(SectionTemplates::class, $args['iblock'], $newId, (array) ($section['IPROPERTY_TEMPLATES'] ?? []));
    $sectionCreated++;
}

$elementErrors = [];
$elementCreated = 0;
$elementUpdated = 0;
$elementSkipped = 0;
$elementApi = new CIBlockElement();

foreach ($sourceElements as $element) {
    if (!is_array($element)) {
        continue;
    }
    $code = trim((string) ($element['CODE'] ?? ''));
    if ($code === '') {
        $elementSkipped++;
        continue;
    }

    $existingId = $findElementByCode($args['iblock'], $code);
    if ($existingId !== null && !$args['update_existing']) {
        $elementSkipped++;
        continue;
    }

    $sectionIds = [];
    foreach ((array) ($element['SECTIONS'] ?? []) as $oldSectionId) {
        $newSectionId = $oldToNewSection[(int) $oldSectionId] ?? null;
        if ($newSectionId) {
            $sectionIds[] = (int) $newSectionId;
        }
    }
    $mainSectionOld = (int) ($element['IBLOCK_SECTION_ID'] ?? 0);
    if ($sectionIds === [] && $mainSectionOld > 0 && isset($oldToNewSection[$mainSectionOld])) {
        $sectionIds[] = (int) $oldToNewSection[$mainSectionOld];
    }

    $fields = [
        'IBLOCK_ID' => $args['iblock'],
        'NAME' => (string) ($element['NAME'] ?? $code),
        'CODE' => $code,
        'ACTIVE' => (string) ($element['ACTIVE'] ?? 'Y'),
        'SORT' => (int) ($element['SORT'] ?? 500),
        'XML_ID' => (string) ($element['XML_ID'] ?? ''),
        'TAGS' => (string) ($element['TAGS'] ?? ''),
        'DATE_ACTIVE_FROM' => (string) ($element['DATE_ACTIVE_FROM'] ?? ''),
        'DATE_ACTIVE_TO' => (string) ($element['DATE_ACTIVE_TO'] ?? ''),
        'PREVIEW_TEXT' => $rewriteHtml((string) ($element['PREVIEW_TEXT'] ?? '')),
        'PREVIEW_TEXT_TYPE' => (string) ($element['PREVIEW_TEXT_TYPE'] ?? 'text'),
        'DETAIL_TEXT' => $rewriteHtml((string) ($element['DETAIL_TEXT'] ?? '')),
        'DETAIL_TEXT_TYPE' => (string) ($element['DETAIL_TEXT_TYPE'] ?? 'text'),
        'IBLOCK_SECTION_ID' => $sectionIds[0] ?? false,
    ];
    if ($fields['DATE_ACTIVE_FROM'] === '') {
        unset($fields['DATE_ACTIVE_FROM']);
    }
    if ($fields['DATE_ACTIVE_TO'] === '') {
        unset($fields['DATE_ACTIVE_TO']);
    }

    foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $picField) {
        $packPath = (string) ($element[$picField] ?? '');
        if ($packPath !== '') {
            $fileArray = $makeFileArray($packPath);
            if ($fileArray !== null) {
                $fields[$picField] = $fileArray;
            }
        }
    }

    $built = $buildPropertyValues(
        is_array($element['PROPERTIES'] ?? null) ? $element['PROPERTIES'] : [],
        true
    );
    $fields['PROPERTY_VALUES'] = $built['values'];

    if ($existingId !== null) {
        unset($fields['IBLOCK_ID']);
        if (!$elementApi->Update($existingId, $fields)) {
            $elementErrors[] = "{$code}: " . $elementApi->LAST_ERROR;
            continue;
        }
        $newId = $existingId;
        $elementUpdated++;
    } else {
        $newId = (int) $elementApi->Add($fields, false, true, true);
        if ($newId <= 0) {
            $elementErrors[] = "{$code}: " . $elementApi->LAST_ERROR;
            continue;
        }
        $elementCreated++;
    }

    if ($sectionIds !== []) {
        CIBlockElement::SetElementSection($newId, $sectionIds);
    }

    $applySeo(
        ElementTemplates::class,
        $args['iblock'],
        $newId,
        is_array($element['IPROPERTY_TEMPLATES'] ?? null) ? $element['IPROPERTY_TEMPLATES'] : []
    );
}

$dnkOut("\nDone.\n");
$dnkOut("Sections created={$sectionCreated} updated={$sectionUpdated} skipped={$sectionSkipped}\n");
$dnkOut("Elements created={$elementCreated} updated={$elementUpdated} skipped={$elementSkipped}\n");

if ($sectionErrors !== []) {
    $dnkOut("\nSection errors:\n");
    foreach ($sectionErrors as $error) {
        $dnkOut('  - ' . $error . "\n");
    }
}
if ($elementErrors !== []) {
    $dnkOut("\nElement errors:\n");
    foreach ($elementErrors as $error) {
        $dnkOut('  - ' . $error . "\n");
    }
}

$dnkFinish();
exit($sectionErrors !== [] || $elementErrors !== [] ? 1 : 0);
