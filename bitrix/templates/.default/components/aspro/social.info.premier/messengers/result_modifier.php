<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Keep only Telegram and Viber from aspro:social.info.premier result.
 * Supports keyed socials and list keys used by Aspro Premier builds.
 */
$allowedCodes = [
    'TELEGRAM' => true,
    'VIBER' => true,
];

$normalizeCode = static function ($code): string {
    $code = strtoupper(trim((string) $code));

    return (string) preg_replace('/^SOCIAL_/', '', $code);
};

$isAllowedItem = static function ($key, $item) use ($allowedCodes, $normalizeCode): bool {
    $candidates = [$key];

    if (is_array($item)) {
        foreach (['CODE', 'code', 'NAME', 'name', 'ID', 'id', 'TITLE', 'title'] as $field) {
            if (!empty($item[$field]) && !is_array($item[$field])) {
                $candidates[] = $item[$field];
            }
        }

        $href = (string) ($item['LINK'] ?? $item['VALUE'] ?? $item['HREF'] ?? $item['URL'] ?? '');
        if ($href !== '') {
            if (stripos($href, 't.me') !== false || stripos($href, 'telegram') !== false) {
                return true;
            }
            if (stripos($href, 'viber') !== false) {
                return true;
            }
        }
    }

    foreach ($candidates as $candidate) {
        $code = $normalizeCode($candidate);
        if (isset($allowedCodes[$code])) {
            return true;
        }
    }

    return false;
};

$listKeys = ['SOCIAL_ITEMS', 'ITEMS', 'SOCIALS', 'SOCIAL'];

foreach ($listKeys as $listKey) {
    if (empty($arResult[$listKey]) || !is_array($arResult[$listKey])) {
        continue;
    }

    $filteredList = [];
    foreach ($arResult[$listKey] as $key => $item) {
        if ($isAllowedItem($key, $item)) {
            $filteredList[$key] = $item;
        }
    }
    $arResult[$listKey] = $filteredList;
}

$metaKeys = array_flip($listKeys);
$filteredRoot = [];

foreach ($arResult as $key => $item) {
    if (isset($metaKeys[$key])) {
        $filteredRoot[$key] = $item;
        continue;
    }

    if ($isAllowedItem($key, $item)) {
        $filteredRoot[$key] = $item;
    }
}

$arResult = $filteredRoot;
