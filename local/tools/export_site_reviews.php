<?php

/**
 * Export site reviews infoblock (Aspro "Оставить свой отзыв").
 * Run on the OLD site (default iblock 19). Copy the pack to the new site, then delete it.
 *
 * Default pack path is upload/site_reviews_migrate (HTTP denied via .htaccess / web.config).
 *
 * CLI (from site root):
 *   php local/tools/export_site_reviews.php
 *   php local/tools/export_site_reviews.php --iblock=19 --out=upload/site_reviews_migrate
 *
 * Browser (admin only):
 *   /local/tools/export_site_reviews.php?run=Y
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

if (!CModule::IncludeModule('iblock')) {
    $dnkErr("Failed to load iblock module.\n");
    $dnkFinish();
    exit(1);
}

@set_time_limit(0);

/**
 * @param list<string> $argvList
 * @return array{iblock:int,out:string}
 */
$parseArgs = static function (array $argvList, bool $cli): array {
    $iblock = 19;
    $out = 'upload/site_reviews_migrate';

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
    $dnkErr("Refusing pack path that resolves to the site root. Use upload/site_reviews_migrate or a dedicated subdirectory.\n");
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
    $base = sha1($webSrc);
    $fileName = $base . $safeExt;
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

$normalizeUploadSrc = static function (string $url): ?string {
    $url = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($url === '' || stripos($url, 'data:') === 0) {
        return null;
    }
    $url = (string) preg_replace('/[?#].*$/', '', $url);
    if (preg_match('#^https?://#i', $url) === 1) {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if ($path === '' || strpos($path, '/upload/') !== 0) {
            return null;
        }

        return $path;
    }
    if (strpos($url, '/upload/') === 0) {
        return $url;
    }

    return null;
};

/**
 * Original HTML src strings that point at /upload/ (absolute or root-relative).
 *
 * @return list<string>
 */
$extractSrcs = static function (string $html): array {
    if ($html === '') {
        return [];
    }
    $found = [];
    if (preg_match_all('/(?:src|href)=["\']([^"\']+)["\']/i', $html, $m) > 0) {
        foreach ($m[1] as $raw) {
            $found[trim((string) $raw)] = true;
        }
    }
    if (preg_match_all('/srcset=["\']([^"\']+)["\']/i', $html, $sm)) {
        foreach ($sm[1] as $srcset) {
            foreach (preg_split('/\s*,\s*/', (string) $srcset) ?: [] as $part) {
                $url = trim(explode(' ', trim($part))[0] ?? '');
                if ($url !== '') {
                    $found[$url] = true;
                }
            }
        }
    }
    if (preg_match_all('/url\((["\']?)([^"\')]+)\1\)/i', $html, $um)) {
        foreach ($um[2] as $raw) {
            $url = trim((string) $raw);
            if ($url !== '') {
                $found[$url] = true;
            }
        }
    }

    return array_keys($found);
};

/**
 * @return list<string>
 */
$htmlSrcVariants = static function (string $raw, string $norm): array {
    $variants = [$raw, $norm];
    $decoded = html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if ($decoded !== '') {
        $variants[] = $decoded;
        $stripped = (string) preg_replace('/[?#].*$/', '', $decoded);
        if ($stripped !== '') {
            $variants[] = $stripped;
        }
    }
    $strippedRaw = (string) preg_replace('/[?#].*$/', '', trim($raw));
    if ($strippedRaw !== '') {
        $variants[] = $strippedRaw;
    }

    return array_values(array_unique(array_filter($variants, static fn (string $v): bool => $v !== '')));
};

$htmlPathMap = [];

$harvestHtml = static function (string $html) use (
    $extractSrcs,
    $htmlSrcVariants,
    $normalizeUploadSrc,
    $copyAbsFile,
    $docRoot,
    &$htmlPathMap,
    &$warnings
): string {
    foreach ($extractSrcs($html) as $raw) {
        $norm = $normalizeUploadSrc($raw);
        if ($norm === null) {
            continue;
        }
        if (!isset($htmlPathMap[$norm])) {
            $abs = $docRoot . str_replace('/', DIRECTORY_SEPARATOR, $norm);
            $entry = $copyAbsFile($abs, $norm, basename($norm));
            if ($entry === null) {
                $warnings[] = "HTML image not found on disk: {$norm}";
                continue;
            }
            foreach ($htmlSrcVariants($raw, $norm) as $variant) {
                $htmlPathMap[$variant] = $entry['pack_path'];
            }
            continue;
        }
        $packPath = $htmlPathMap[$norm];
        foreach ($htmlSrcVariants($raw, $norm) as $variant) {
            if (!isset($htmlPathMap[$variant])) {
                $htmlPathMap[$variant] = $packPath;
            }
        }
    }

    return $html;
};

$exportSeoTemplates = static function (string $className, int $iblockId, int $entityId): array {
    if (!class_exists($className)) {
        return [];
    }
    try {
        /** @var object $templates */
        $templates = new $className($iblockId, $entityId);
        if (!method_exists($templates, 'findTemplates')) {
            return [];
        }
        $found = $templates->findTemplates();
        if (!is_array($found)) {
            return [];
        }
        $out = [];
        foreach ($found as $code => $row) {
            if (!is_array($row)) {
                continue;
            }
            $entityType = (string) ($row['ENTITY_TYPE'] ?? '');
            $rowEntityId = (int) ($row['ENTITY_ID'] ?? 0);
            if ($rowEntityId !== $entityId) {
                continue;
            }
            if ($entityType !== '' && strpos($entityType, 'IBLOCK_') !== 0) {
                continue;
            }
            $out[(string) $code] = (string) ($row['TEMPLATE'] ?? '');
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
};

$propertiesSchema = [];
$propRes = CIBlockProperty::GetList(
    ['SORT' => 'ASC', 'ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId]
);
while ($prop = $propRes->Fetch()) {
    $code = (string) ($prop['CODE'] ?? '');
    if ($code === '') {
        continue;
    }
    $entry = [
        'ID' => (int) $prop['ID'],
        'CODE' => $code,
        'NAME' => (string) ($prop['NAME'] ?? ''),
        'PROPERTY_TYPE' => (string) ($prop['PROPERTY_TYPE'] ?? 'S'),
        'MULTIPLE' => (string) ($prop['MULTIPLE'] ?? 'N'),
        'USER_TYPE' => (string) ($prop['USER_TYPE'] ?? ''),
        'LINK_IBLOCK_ID' => (int) ($prop['LINK_IBLOCK_ID'] ?? 0),
        'ENUM' => [],
    ];
    if ($entry['PROPERTY_TYPE'] === 'L') {
        $enumRes = CIBlockPropertyEnum::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['PROPERTY_ID' => (int) $prop['ID']]
        );
        while ($enum = $enumRes->Fetch()) {
            $entry['ENUM'][] = [
                'ID' => (int) $enum['ID'],
                'VALUE' => (string) ($enum['VALUE'] ?? ''),
                'XML_ID' => (string) ($enum['XML_ID'] ?? ''),
                'SORT' => (int) ($enum['SORT'] ?? 0),
            ];
        }
    }
    $propertiesSchema[] = $entry;
}

$sections = [];
$secRes = CIBlockSection::GetList(
    ['LEFT_MARGIN' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
    false,
    [
        'ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', 'ACTIVE', 'SORT', 'DEPTH_LEVEL',
        'DESCRIPTION', 'DESCRIPTION_TYPE', 'PICTURE', 'DETAIL_PICTURE', 'XML_ID',
    ]
);
while ($section = $secRes->GetNext()) {
    $description = (string) ($section['~DESCRIPTION'] ?? $section['DESCRIPTION'] ?? '');
    $harvestHtml($description);
    $picture = $copyByFileId((int) ($section['PICTURE'] ?? 0));
    $detailPicture = $copyByFileId((int) ($section['DETAIL_PICTURE'] ?? 0));
    $sections[] = [
        'ID' => (int) $section['ID'],
        'IBLOCK_SECTION_ID' => (int) ($section['IBLOCK_SECTION_ID'] ?? 0),
        'NAME' => (string) ($section['~NAME'] ?? $section['NAME'] ?? ''),
        'CODE' => (string) ($section['~CODE'] ?? $section['CODE'] ?? ''),
        'ACTIVE' => (string) ($section['ACTIVE'] ?? 'Y'),
        'SORT' => (int) ($section['SORT'] ?? 500),
        'DEPTH_LEVEL' => (int) ($section['DEPTH_LEVEL'] ?? 1),
        'DESCRIPTION' => $description,
        'DESCRIPTION_TYPE' => (string) ($section['DESCRIPTION_TYPE'] ?? 'text'),
        'XML_ID' => (string) ($section['~XML_ID'] ?? $section['XML_ID'] ?? ''),
        'PICTURE' => $picture['pack_path'] ?? null,
        'DETAIL_PICTURE' => $detailPicture['pack_path'] ?? null,
        'IPROPERTY_TEMPLATES' => $exportSeoTemplates(
            \Bitrix\Iblock\InheritedProperty\SectionTemplates::class,
            $iblockId,
            (int) $section['ID']
        ),
    ];
}

$elements = [];
$elRes = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'CHECK_PERMISSIONS' => 'N'],
    false,
    false,
    [
        'ID', 'NAME', 'CODE', 'ACTIVE', 'SORT', 'XML_ID', 'TAGS',
        'DATE_CREATE', 'DATE_ACTIVE_FROM', 'DATE_ACTIVE_TO',
        'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE',
        'DETAIL_TEXT', 'DETAIL_TEXT_TYPE',
        'PREVIEW_PICTURE', 'DETAIL_PICTURE',
        'IBLOCK_SECTION_ID',
    ]
);

while ($ob = $elRes->GetNextElement()) {
    $fields = $ob->GetFields();
    $elementId = (int) $fields['ID'];
    $previewText = (string) ($fields['~PREVIEW_TEXT'] ?? $fields['PREVIEW_TEXT'] ?? '');
    $detailText = (string) ($fields['~DETAIL_TEXT'] ?? $fields['DETAIL_TEXT'] ?? '');
    $harvestHtml($previewText);
    $harvestHtml($detailText);

    $sectionIds = [];
    $grp = CIBlockElement::GetElementGroups($elementId, true);
    while ($grpRow = $grp->Fetch()) {
        $sectionIds[] = (int) $grpRow['ID'];
    }

    $propsOut = [];
    $props = $ob->GetProperties();
    foreach ($props as $code => $prop) {
        if (!is_array($prop)) {
            continue;
        }
        $type = (string) ($prop['PROPERTY_TYPE'] ?? 'S');
        $multiple = (string) ($prop['MULTIPLE'] ?? 'N') === 'Y';
        $userType = (string) ($prop['USER_TYPE'] ?? '');
        $rawValue = $prop['~VALUE'] ?? $prop['VALUE'] ?? null;
        $xmlId = $prop['VALUE_XML_ID'] ?? null;

        $normalized = [
            'PROPERTY_TYPE' => $type,
            'MULTIPLE' => $multiple ? 'Y' : 'N',
            'USER_TYPE' => $userType,
            'VALUES' => [],
        ];

        if ($type === 'F') {
            $ids = $multiple ? (array) ($prop['VALUE'] ?? []) : [($prop['VALUE'] ?? 0)];
            foreach ($ids as $fid) {
                $entry = $copyByFileId((int) $fid);
                if ($entry !== null) {
                    $normalized['VALUES'][] = $entry;
                }
            }
        } elseif ($type === 'L') {
            $values = $multiple ? (array) ($prop['VALUE'] ?? []) : [($prop['VALUE'] ?? '')];
            $xmlIds = $multiple ? (array) ($xmlId ?? []) : [($xmlId ?? '')];
            $enumIds = $multiple
                ? (array) ($prop['VALUE_ENUM_ID'] ?? [])
                : [($prop['VALUE_ENUM_ID'] ?? 0)];
            $count = max(count($values), count($xmlIds), count($enumIds));
            for ($i = 0; $i < $count; $i++) {
                $val = (string) ($values[$i] ?? '');
                if ($val === '' && (int) ($enumIds[$i] ?? 0) <= 0) {
                    continue;
                }
                $normalized['VALUES'][] = [
                    'VALUE' => $val,
                    'XML_ID' => (string) ($xmlIds[$i] ?? ''),
                    'ENUM_ID' => (int) ($enumIds[$i] ?? 0),
                ];
            }
        } elseif ($type === 'E' || $type === 'G') {
            $ids = $multiple ? (array) ($prop['VALUE'] ?? []) : [($prop['VALUE'] ?? 0)];
            foreach ($ids as $linkedId) {
                $linkedId = (int) $linkedId;
                if ($linkedId <= 0) {
                    continue;
                }
                $item = ['ID' => $linkedId, 'CODE' => '', 'XML_ID' => '', 'NAME' => ''];
                if ($type === 'E') {
                    $linked = CIBlockElement::GetList(
                        [],
                        ['ID' => $linkedId],
                        false,
                        false,
                        ['ID', 'CODE', 'XML_ID', 'NAME', 'IBLOCK_ID']
                    )->Fetch();
                    if (is_array($linked)) {
                        $item['CODE'] = (string) ($linked['CODE'] ?? '');
                        $item['XML_ID'] = (string) ($linked['XML_ID'] ?? '');
                        $item['NAME'] = (string) ($linked['NAME'] ?? '');
                        $item['IBLOCK_ID'] = (int) ($linked['IBLOCK_ID'] ?? 0);
                    }
                }
                $normalized['VALUES'][] = $item;
            }
        } elseif ($userType === 'HTML') {
            $rows = $multiple ? (array) $rawValue : [$rawValue];
            foreach ($rows as $row) {
                if (is_array($row)) {
                    $text = (string) ($row['TEXT'] ?? $row['VALUE']['TEXT'] ?? '');
                    $harvestHtml($text);
                    $normalized['VALUES'][] = [
                        'TEXT' => $text,
                        'TYPE' => (string) ($row['TYPE'] ?? $row['VALUE']['TYPE'] ?? 'HTML'),
                    ];
                } elseif (is_string($row) && $row !== '') {
                    $harvestHtml($row);
                    $normalized['VALUES'][] = ['TEXT' => $row, 'TYPE' => 'HTML'];
                }
            }
        } else {
            $rows = $multiple ? (array) $rawValue : [$rawValue];
            foreach ($rows as $row) {
                if ($row === null || $row === '' || $row === false) {
                    continue;
                }
                if (is_array($row)) {
                    $normalized['VALUES'][] = $row;
                } else {
                    $normalized['VALUES'][] = (string) $row;
                }
            }
        }

        $propsOut[$code] = $normalized;
    }

    $elements[] = [
        'ID' => $elementId,
        'NAME' => (string) ($fields['~NAME'] ?? $fields['NAME'] ?? ''),
        'CODE' => (string) ($fields['~CODE'] ?? $fields['CODE'] ?? ''),
        'ACTIVE' => (string) ($fields['ACTIVE'] ?? 'Y'),
        'SORT' => (int) ($fields['SORT'] ?? 500),
        'XML_ID' => (string) ($fields['~XML_ID'] ?? $fields['XML_ID'] ?? ''),
        'TAGS' => (string) ($fields['~TAGS'] ?? $fields['TAGS'] ?? ''),
        'DATE_CREATE' => (string) ($fields['DATE_CREATE'] ?? ''),
        'DATE_ACTIVE_FROM' => (string) ($fields['DATE_ACTIVE_FROM'] ?? ''),
        'DATE_ACTIVE_TO' => (string) ($fields['DATE_ACTIVE_TO'] ?? ''),
        'PREVIEW_TEXT' => $previewText,
        'PREVIEW_TEXT_TYPE' => (string) ($fields['PREVIEW_TEXT_TYPE'] ?? 'text'),
        'DETAIL_TEXT' => $detailText,
        'DETAIL_TEXT_TYPE' => (string) ($fields['DETAIL_TEXT_TYPE'] ?? 'text'),
        'PREVIEW_PICTURE' => ($copyByFileId((int) ($fields['PREVIEW_PICTURE'] ?? 0))['pack_path'] ?? null),
        'DETAIL_PICTURE' => ($copyByFileId((int) ($fields['DETAIL_PICTURE'] ?? 0))['pack_path'] ?? null),
        'IBLOCK_SECTION_ID' => (int) ($fields['IBLOCK_SECTION_ID'] ?? 0),
        'SECTIONS' => $sectionIds,
        'IPROPERTY_TEMPLATES' => $exportSeoTemplates(
            \Bitrix\Iblock\InheritedProperty\ElementTemplates::class,
            $iblockId,
            $elementId
        ),
        'PROPERTIES' => $propsOut,
    ];
}

$manifest = [
    'version' => 1,
    'kind' => 'site_reviews',
    'exported_at' => date('c'),
    'source_iblock' => [
        'ID' => $iblockId,
        'CODE' => (string) ($iblock['CODE'] ?? ''),
        'NAME' => (string) ($iblock['NAME'] ?? ''),
        'IBLOCK_TYPE_ID' => (string) ($iblock['IBLOCK_TYPE_ID'] ?? ''),
    ],
    'site_url' => (defined('SITE_SERVER_NAME') ? (string) SITE_SERVER_NAME : ''),
    'properties' => $propertiesSchema,
    'html_path_map' => $htmlPathMap,
    'files' => $fileIndex,
    'sections' => $sections,
    'elements' => $elements,
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

$resolvedPack = realpath($packRoot) ?: $packRoot;
$dnkOut("Exported iblock {$iblockId} ({$iblock['CODE']})\n");
$dnkOut('Sections: ' . count($sections) . "\n");
$dnkOut('Elements: ' . count($elements) . "\n");
$dnkOut('Files: ' . count($fileIndex) . "\n");
$dnkOut('HTML images mapped: ' . count($htmlPathMap) . "\n");
$dnkOut("Pack: {$resolvedPack}\n");
$dnkOut("Contains personal data (names, emails, photos). Copy via SCP/SFTP, then delete this directory.\n");
if ($isStrictSubdirOf($resolvedPack, $docRoot)) {
    $dnkOut("HTTP deny files (.htaccess / web.config) were written in the pack directory.\n");
}
if ($warnings !== []) {
    $dnkOut('Warnings: ' . count($warnings) . "\n");
    foreach ($warnings as $warning) {
        $dnkOut('  - ' . $warning . "\n");
    }
}

$dnkFinish();
