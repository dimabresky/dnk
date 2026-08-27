<?php

/**
 * Import site reviews pack (upload/site_reviews_migrate) into infoblock 7.
 *
 * New elements are created inactive (ACTIVE=N); managers activate them in the admin after review.
 * --update-existing does not change ACTIVE, so already published reviews stay published.
 *
 * Idempotency: XML_ID = dnk_old_{sourceIblock}_{oldId}. Re-run --apply does not duplicate.
 * Empty CODE is allowed; a stable code is generated only when the source CODE is empty.
 *
 * CLI (from site root):
 *   php local/tools/import_site_reviews.php --dry-run
 *   php local/tools/import_site_reviews.php --apply
 *   php local/tools/import_site_reviews.php --apply --update-existing
 *   php local/tools/import_site_reviews.php --dry-run --pack=upload/site_reviews_migrate --iblock=7
 *
 * Browser (admin only):
 *   /local/tools/import_site_reviews.php?run=Y&mode=dry-run
 *   /local/tools/import_site_reviews.php?run=Y&mode=apply
 *   /local/tools/import_site_reviews.php?run=Y&mode=apply&update_existing=Y
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
    $iblock = 7;
    $pack = 'upload/site_reviews_migrate';

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
                $value = trim(substr($arg, 7));
                if ($value !== '') {
                    $pack = $value;
                }
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
    $dnkErr("Refusing pack path that resolves to the site root. Use upload/site_reviews_migrate or a dedicated subdirectory.\n");
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

$filesDir = $packRoot . DIRECTORY_SEPARATOR . 'files';
if ($isStrictSubdirOf($packRoot, $docRoot)) {
    $protectPackFromHttp($packRoot);
    if (is_dir($filesDir)) {
        $protectPackFromHttp($filesDir);
    }
}

$manifestRaw = file_get_contents($manifestPath);
$manifest = is_string($manifestRaw) ? json_decode($manifestRaw, true) : null;
if (!is_array($manifest)) {
    $dnkErr("Cannot parse manifest.json: " . json_last_error_msg() . "\n");
    $dnkFinish();
    exit(1);
}

$sourceIblockId = (int) ($manifest['source_iblock']['ID'] ?? 19);
if ($sourceIblockId <= 0) {
    $sourceIblockId = 19;
}

$makeImportXmlId = static function (int $oldId) use ($sourceIblockId): string {
    return 'dnk_old_' . $sourceIblockId . '_' . $oldId;
};

$makeImportCode = static function (int $oldId, string $sourceCode) use ($sourceIblockId): string {
    $sourceCode = trim($sourceCode);
    if ($sourceCode !== '') {
        return $sourceCode;
    }

    return 'dnk-old-' . $sourceIblockId . '-' . $oldId;
};

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

$findSectionByXmlId = static function (int $iblockId, string $xmlId): ?int {
    if ($xmlId === '') {
        return null;
    }
    $row = CIBlockSection::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['ID']
    )->Fetch();

    return is_array($row) ? (int) $row['ID'] : null;
};

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

$findElementByXmlId = static function (int $iblockId, string $xmlId): ?int {
    if ($xmlId === '') {
        return null;
    }
    $row = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=XML_ID' => $xmlId, 'CHECK_PERMISSIONS' => 'N'],
        false,
        ['nTopCount' => 1],
        ['ID']
    )->Fetch();

    return is_array($row) ? (int) $row['ID'] : null;
};

$resolveSectionExistingId = static function (
    int $iblockId,
    int $oldId,
    string $code
) use ($sourceIblockId, $findSectionByXmlId, $findSectionByCode): ?int {
    $xmlId = 'dnk_old_' . $sourceIblockId . '_' . $oldId;
    $byXml = $findSectionByXmlId($iblockId, $xmlId);
    if ($byXml !== null) {
        return $byXml;
    }
    if ($code !== '') {
        return $findSectionByCode($iblockId, $code);
    }

    return null;
};

$absPackFile = static function (string $packPath) use ($packRoot): string {
    $packPath = str_replace(['\\', '..'], ['/', ''], $packPath);
    $packPath = ltrim($packPath, '/');

    return $packRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $packPath);
};

$htmlSrcCache = [];

$uploadHtmlFile = static function (string $packPath) use (&$htmlSrcCache, $absPackFile): ?string {
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

    // Replace scheme+host+/upload/... before path-only keys, otherwise
    // str_replace('/upload/...') leaves the old hostname in front of the new path.
    // Longest-first + trailing URL boundary avoid prefix hits (e.g. .jpg vs .jpg.webp).
    foreach ($pairs as $from => $to) {
        if (!str_starts_with($from, '/upload/')) {
            continue;
        }
        $html = preg_replace(
            '#https?://[^/\s"\']+' . preg_quote($from, '#') . '(?=[?#\s"\'<>)]|$)#i',
            $to,
            $html
        ) ?? $html;
    }

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

/**
 * @param array<string, mixed> $values
 */
$saveElementProperties = static function (
    int $elementId,
    int $iblockId,
    array $values,
    bool $replaceExistingFiles
) use ($targetProps): void {
    if ($values === []) {
        return;
    }

    $toSet = $values;
    if ($replaceExistingFiles) {
        foreach ($toSet as $code => $val) {
            $code = (string) $code;
            if (($targetProps[$code]['PROPERTY_TYPE'] ?? '') !== 'F') {
                continue;
            }

            $merged = [];
            $res = CIBlockElement::GetProperty($iblockId, $elementId, 'sort', 'asc', ['CODE' => $code]);
            while ($p = $res->Fetch()) {
                $valueId = (int) ($p['PROPERTY_VALUE_ID'] ?? 0);
                if ($valueId > 0) {
                    $merged[$valueId] = ['VALUE' => ['del' => 'Y']];
                }
            }

            $files = [];
            if (is_array($val) && isset($val['tmp_name'])) {
                $files[] = $val;
            } elseif (is_array($val)) {
                foreach ($val as $item) {
                    if (is_array($item) && isset($item['tmp_name'])) {
                        $files[] = $item;
                    }
                }
            }

            $isMultiple = ($targetProps[$code]['MULTIPLE'] ?? 'N') === 'Y';
            if (!$isMultiple) {
                $toSet[$code] = isset($files[0])
                    ? ['VALUE' => $files[0], 'DESCRIPTION' => '']
                    : ['VALUE' => ['del' => 'Y']];
                continue;
            }

            $i = 0;
            foreach ($files as $fileArray) {
                $merged['n' . $i] = ['VALUE' => $fileArray, 'DESCRIPTION' => ''];
                ++$i;
            }

            $toSet[$code] = $merged;
        }
    }

    CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $toSet);
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
];
foreach ($sourceSections as $section) {
    if (!is_array($section)) {
        continue;
    }
    $oldId = (int) ($section['ID'] ?? 0);
    $code = trim((string) ($section['CODE'] ?? ''));
    $name = (string) ($section['NAME'] ?? '');
    $xmlId = $makeImportXmlId($oldId);
    $existing = $resolveSectionExistingId($args['iblock'], $oldId, $code);
    $row = [
        'OLD_ID' => $oldId,
        'XML_ID' => $xmlId,
        'CODE' => $code,
        'NAME' => $name,
        'existing_id' => $existing,
    ];
    if ($existing !== null) {
        $sectionPlan['exists'][] = $row;
    } else {
        $sectionPlan['create'][] = $row;
    }
}

$elementPlan = [
    'create' => [],
    'exists' => [],
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
    $oldId = (int) ($element['ID'] ?? 0);
    $xmlId = $makeImportXmlId($oldId);
    $code = $makeImportCode($oldId, (string) ($element['CODE'] ?? ''));
    $name = (string) ($element['NAME'] ?? '');
    $existing = $findElementByXmlId($args['iblock'], $xmlId);
    $row = [
        'OLD_ID' => $oldId,
        'XML_ID' => $xmlId,
        'CODE' => $code,
        'NAME' => $name,
        'existing_id' => $existing,
        'ACTIVE' => (string) ($element['ACTIVE'] ?? 'Y'),
        'DATE_CREATE' => (string) ($element['DATE_CREATE'] ?? ''),
    ];
    if ($existing !== null) {
        $elementPlan['exists'][] = $row;
    } else {
        $elementPlan['create'][] = $row;
    }

    $label = $xmlId;
    foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $picField) {
        $packPath = (string) ($element[$picField] ?? '');
        if ($packPath !== '' && !is_file($absPackFile($packPath))) {
            $elementFileMissing[] = "{$label} {$picField} → {$packPath}";
        }
    }

    $props = is_array($element['PROPERTIES'] ?? null) ? $element['PROPERTIES'] : [];
    $built = $buildPropertyValues($props, false);
    foreach ($built['skipped'] as $note) {
        $linkedPropertyNotes[] = "{$label}: {$note}";
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

$resolvedPack = realpath($packRoot) ?: $packRoot;
$dnkOut("Target iblock: {$args['iblock']} ({$iblock['CODE']})\n");
$dnkOut('Source iblock: ' . $sourceIblockId . "\n");
$dnkOut('Mode: ' . $args['mode'] . ($args['update_existing'] ? ' + update-existing' : '') . "\n");
$dnkOut("Pack: {$resolvedPack}\n");
$dnkOut('Source sections: ' . count($sourceSections) . "\n");
$dnkOut('Source elements: ' . count($sourceElements) . "\n");
$dnkOut("\nSections:\n");
$dnkOut('  create: ' . count($sectionPlan['create']) . "\n");
$dnkOut('  already exist (XML_ID/CODE): ' . count($sectionPlan['exists']) . "\n");
$dnkOut("\nElements:\n");
$dnkOut('  create: ' . count($elementPlan['create']) . " (ACTIVE=N, managers activate after review)\n");
$dnkOut('  already exist (XML_ID): ' . count($elementPlan['exists']) . "\n");
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
        $dnkOut("  - {$row['XML_ID']} (ID {$row['existing_id']}) {$row['NAME']}\n");
    }
}
if ($elementPlan['exists'] !== []) {
    $dnkOut("\nAlready imported elements:\n");
    foreach ($elementPlan['exists'] as $row) {
        $dnkOut("  - {$row['XML_ID']} (ID {$row['existing_id']}) {$row['NAME']}\n");
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
    $dnkOut("Re-run with --apply to create missing sections/elements as inactive (duplicates skipped).\n");
    $dnkOut("Use --update-existing together with --apply to overwrite duplicates (ACTIVE is not changed).\n");
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
    $code = $makeImportCode($oldId, (string) ($section['CODE'] ?? ''));
    $xmlId = $makeImportXmlId($oldId);

    $parentOld = (int) ($section['IBLOCK_SECTION_ID'] ?? 0);
    $parentNew = $parentOld > 0 ? ($oldToNewSection[$parentOld] ?? 0) : 0;
    $existingId = $resolveSectionExistingId($args['iblock'], $oldId, trim((string) ($section['CODE'] ?? '')));

    $fields = [
        'IBLOCK_ID' => $args['iblock'],
        'IBLOCK_SECTION_ID' => $parentNew > 0 ? $parentNew : false,
        'NAME' => (string) ($section['NAME'] ?? $code),
        'CODE' => $code,
        'ACTIVE' => (string) ($section['ACTIVE'] ?? 'Y'),
        'SORT' => (int) ($section['SORT'] ?? 500),
        'DESCRIPTION' => $rewriteHtml((string) ($section['DESCRIPTION'] ?? '')),
        'DESCRIPTION_TYPE' => (string) ($section['DESCRIPTION_TYPE'] ?? 'text'),
        'XML_ID' => $xmlId,
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
            $sectionErrors[] = "{$xmlId}: " . $sectionApi->LAST_ERROR;
            continue;
        }
        $applySeo(SectionTemplates::class, $args['iblock'], $existingId, (array) ($section['IPROPERTY_TEMPLATES'] ?? []));
        $sectionUpdated++;
        continue;
    }

    $newId = (int) $sectionApi->Add($fields);
    if ($newId <= 0) {
        $sectionErrors[] = "{$xmlId}: " . $sectionApi->LAST_ERROR;
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
    $oldId = (int) ($element['ID'] ?? 0);
    if ($oldId <= 0) {
        $elementSkipped++;
        continue;
    }

    $xmlId = $makeImportXmlId($oldId);
    $code = $makeImportCode($oldId, (string) ($element['CODE'] ?? ''));
    $existingId = $findElementByXmlId($args['iblock'], $xmlId);
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
        'ACTIVE' => 'N',
        'SORT' => (int) ($element['SORT'] ?? 500),
        'XML_ID' => $xmlId,
        'TAGS' => (string) ($element['TAGS'] ?? ''),
        'DATE_CREATE' => (string) ($element['DATE_CREATE'] ?? ''),
        'DATE_ACTIVE_FROM' => (string) ($element['DATE_ACTIVE_FROM'] ?? ''),
        'DATE_ACTIVE_TO' => (string) ($element['DATE_ACTIVE_TO'] ?? ''),
        'PREVIEW_TEXT' => $rewriteHtml((string) ($element['PREVIEW_TEXT'] ?? '')),
        'PREVIEW_TEXT_TYPE' => (string) ($element['PREVIEW_TEXT_TYPE'] ?? 'text'),
        'DETAIL_TEXT' => $rewriteHtml((string) ($element['DETAIL_TEXT'] ?? '')),
        'DETAIL_TEXT_TYPE' => (string) ($element['DETAIL_TEXT_TYPE'] ?? 'text'),
        'IBLOCK_SECTION_ID' => $sectionIds[0] ?? false,
    ];
    if ($fields['DATE_CREATE'] === '') {
        unset($fields['DATE_CREATE']);
    }
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
    $propertyValues = $built['values'];

    if ($existingId !== null) {
        unset($fields['IBLOCK_ID'], $fields['ACTIVE']);
        if (!$elementApi->Update($existingId, $fields)) {
            $elementErrors[] = "{$xmlId}: " . $elementApi->LAST_ERROR;
            continue;
        }
        $newId = $existingId;
        $saveElementProperties($newId, $args['iblock'], $propertyValues, true);
        $elementUpdated++;
    } else {
        $fields['PROPERTY_VALUES'] = $propertyValues;
        $newId = (int) $elementApi->Add($fields, false, true, true);
        if ($newId <= 0) {
            $elementErrors[] = "{$xmlId}: " . $elementApi->LAST_ERROR;
            continue;
        }
        $elementCreated++;
        if (isset($fields['DATE_CREATE']) && $fields['DATE_CREATE'] !== '') {
            $elementApi->Update($newId, ['DATE_CREATE' => $fields['DATE_CREATE']]);
        }
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
$dnkOut("Elements created={$elementCreated} (ACTIVE=N) updated={$elementUpdated} skipped={$elementSkipped}\n");
$dnkOut("Contains personal data. Delete the pack directory after a successful import.\n");

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
