<?php

/**
 * Одноразовая установка: инфоблок заявок на покупку подарочных сертификатов (CODE dnk_certificate_requests).
 * IBLOCK_TYPE_ID берётся от инфоблока номинальных сертификатов.
 *
 * CLI (из корня сайта):
 *   php local/tools/install_certificate_requests_iblock.php [CERT_IBLOCK_ID]
 * Если аргумент не передан, берётся DNK_CERTIFICATE_CATALOG_IBLOCK_ID из .env.
 *
 * Браузер (только авторизованный администратор):
 *   /local/tools/install_certificate_requests_iblock.php?run=Y[&cert_iblock_id=NN]
 * Параметр cert_iblock_id необязателен, если в .env задан DNK_CERTIFICATE_CATALOG_IBLOCK_ID.
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

$dnkCiFinish = static function () use ($isCli): void {
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

        $dnkCiFinish();

        exit(1);
    }

    if (($_REQUEST['run'] ?? '') !== 'Y') {
        header('HTTP/1.1 400 Bad Request');
        echo "400: добавьте к URL параметр run=Y чтобы выполнить установку.\n";

        $dnkCiFinish();

        exit(1);
    }
}

$dnkCiOut = static function (string $msg): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDOUT, $msg);
    } else {
        echo $msg;
    }
};

$dnkCiErr = static function (string $msg): void {
    if (\PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg') {
        fwrite(STDERR, $msg);
    } else {
        echo $msg;
    }
};

$catalogIblockArg = 0;

if ($isCli && isset($GLOBALS['argv'][1])) {
    $catalogIblockArg = (int)\trim((string)$GLOBALS['argv'][1]);
}

if (!$isCli && isset($_GET['cert_iblock_id'])) {
    $rawGet = \trim((string)$_GET['cert_iblock_id']);
    if ($rawGet !== '' && filter_var($rawGet, FILTER_VALIDATE_INT) !== false) {
        $catalogIblockArg = (int)$rawGet;
    }
}

if ($catalogIblockArg <= 0 && defined('DNK_CERTIFICATE_CATALOG_IBLOCK_ID')) {
    $catalogIblockArg = (int)DNK_CERTIFICATE_CATALOG_IBLOCK_ID;
}

if ($catalogIblockArg <= 0) {
    $raw = \getenv('DNK_CERTIFICATE_CATALOG_IBLOCK_ID');
    if (($raw === false || $raw === '') && isset($_ENV['DNK_CERTIFICATE_CATALOG_IBLOCK_ID'])) {
        $raw = (string)$_ENV['DNK_CERTIFICATE_CATALOG_IBLOCK_ID'];
    }
    $raw = \is_string($raw) ? \trim($raw) : '';
    if ($raw !== '' && filter_var($raw, FILTER_VALIDATE_INT) !== false) {
        $catalogIblockArg = (int)$raw;
    }
}

if ($catalogIblockArg <= 0) {
    $dnkCiErr("Укажите ID инфоблока номинальных сертификатов:\n");
    $dnkCiErr('- CLI: php local/tools/install_certificate_requests_iblock.php [CERT_IBLOCK_ID]' . "\n");
    $dnkCiErr('- браузер (только админ): ?run=Y[&cert_iblock_id=<ID>] либо DNK_CERTIFICATE_CATALOG_IBLOCK_ID в .env' . "\n");
    $dnkCiFinish();

    exit(1);
}

if (!\CModule::IncludeModule('iblock')) {
    $dnkCiErr("Не удалось подключить модуль iblock.\n");
    $dnkCiFinish();

    exit(1);
}

$iblockCode = 'dnk_certificate_requests';

$exists = CIBlock::GetList(
    [],
    [
        '=CODE' => $iblockCode,
        'CHECK_PERMISSIONS' => 'N',
    ]
)->Fetch();
if ($exists) {
    $iblockId = (int)$exists['ID'];
    $dnkCiOut("Iblock already exists: ID={$iblockId}\n");
    $dnkCiOut("Set .env DNK_CERTIFICATE_REQUEST_IBLOCK_ID={$iblockId}\n");
    $dnkCiOut("Ensure .env DNK_CERTIFICATE_CATALOG_IBLOCK_ID={$catalogIblockArg}\n");

    $deliveryProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'DELIVERY'])->Fetch();
    if (is_array($deliveryProp) && (int)$deliveryProp['ID'] > 0) {
        $propId = (int)$deliveryProp['ID'];
        $pickupEnum = CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propId, 'XML_ID' => 'pickup'])->Fetch();
        if (!is_array($pickupEnum)) {
            $enum = new CIBlockPropertyEnum();
            $enumId = $enum->Add([
                'PROPERTY_ID' => $propId,
                'VALUE' => 'Самовывоз',
                'XML_ID' => 'pickup',
                'DEF' => 'N',
                'SORT' => 200,
            ]);
            if ($enumId) {
                $dnkCiOut("Added DELIVERY enum pickup (ID={$enumId}).\n");
            } else {
                $dnkCiErr("Failed to add DELIVERY enum pickup. Add manually in admin.\n");
            }
        } else {
            $dnkCiOut("DELIVERY enum pickup already exists.\n");
        }
    }

    $statusEnums = [
        'accepted' => 'Принят',
        'in_progress' => 'В обработке',
        'ready' => 'Готов',
    ];

    $statusProp = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => 'STATUS'])->Fetch();
    $statusPropId = is_array($statusProp) ? (int)($statusProp['ID'] ?? 0) : 0;

    if ($statusPropId <= 0) {
        $statusPropId = (int)(new CIBlockProperty())->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => 'Статус заявки',
            'ACTIVE' => 'Y',
            'SORT' => 220,
            'CODE' => 'STATUS',
            'PROPERTY_TYPE' => 'L',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'FILTRABLE' => 'Y',
        ]);
        if ($statusPropId <= 0) {
            $dnkCiErr("Failed to add STATUS property.\n");
        } else {
            $dnkCiOut("Added STATUS property (ID={$statusPropId}).\n");
        }
    } else {
        $dnkCiOut("STATUS property already exists (ID={$statusPropId}).\n");
    }

    if ($statusPropId > 0) {
        $enumInstaller = new CIBlockPropertyEnum();
        $sort = 100;
        $isFirstEnum = true;
        foreach ($statusEnums as $xmlId => $caption) {
            $existingEnum = CIBlockPropertyEnum::GetList(
                [],
                ['PROPERTY_ID' => $statusPropId, 'XML_ID' => $xmlId]
            )->Fetch();
            if (!is_array($existingEnum)) {
                $enumId = $enumInstaller->Add([
                    'PROPERTY_ID' => $statusPropId,
                    'VALUE' => $caption,
                    'XML_ID' => $xmlId,
                    'DEF' => $isFirstEnum ? 'Y' : 'N',
                    'SORT' => $sort,
                ]);
                if ($enumId) {
                    $dnkCiOut("Added STATUS enum {$xmlId} (ID={$enumId}).\n");
                } else {
                    $dnkCiErr("Failed to add STATUS enum {$xmlId}.\n");
                }
            }
            $isFirstEnum = false;
            $sort += 100;
        }

        $acceptedEnum = CIBlockPropertyEnum::GetList(
            [],
            ['PROPERTY_ID' => $statusPropId, 'XML_ID' => 'accepted']
        )->Fetch();
        $acceptedEnumId = is_array($acceptedEnum) ? (int)($acceptedEnum['ID'] ?? 0) : 0;

        if ($acceptedEnumId > 0) {
            $backfillCount = 0;
            $rsElements = CIBlockElement::GetList(
                ['ID' => 'ASC'],
                ['IBLOCK_ID' => $iblockId],
                false,
                false,
                ['ID']
            );
            while ($row = $rsElements->Fetch()) {
                $elementId = (int)($row['ID'] ?? 0);
                if ($elementId <= 0) {
                    continue;
                }

                $hasStatus = false;
                $propRs = CIBlockElement::GetProperty($iblockId, $elementId, [], ['CODE' => 'STATUS']);
                while ($propRow = $propRs->Fetch()) {
                    if ((int)($propRow['VALUE_ENUM_ID'] ?? 0) > 0 || trim((string)($propRow['VALUE'] ?? '')) !== '') {
                        $hasStatus = true;
                        break;
                    }
                }

                if (!$hasStatus) {
                    CIBlockElement::SetPropertyValuesEx(
                        $elementId,
                        $iblockId,
                        ['STATUS' => $acceptedEnumId]
                    );
                    $backfillCount++;
                }
            }

            if ($backfillCount > 0) {
                $dnkCiOut("Backfilled STATUS=accepted for {$backfillCount} element(s).\n");
            } else {
                $dnkCiOut("All elements already have STATUS.\n");
            }
        }
    }

    $dnkCiFinish();

    exit(0);
}

$catalogArr = CIBlock::GetArrayByID($catalogIblockArg);
if (!\is_array($catalogArr) || (int)$catalogArr['ID'] !== $catalogIblockArg) {
    $dnkCiErr("CERT_IBLOCK_ID={$catalogIblockArg} was not found.\n");
    $dnkCiFinish();

    exit(1);
}
$typeId = (string)$catalogArr['IBLOCK_TYPE_ID'];

$siteIds = [];
$siteRes = CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
while ($s = $siteRes->Fetch()) {
    $siteIds[] = (string)$s['LID'];
}
if (!$siteIds) {
    $dnkCiErr("Не найдено ни одного активного сайта.\n");
    $dnkCiFinish();

    exit(1);
}

$newIblock = new CIBlock();
$fields = [
    'ACTIVE' => 'Y',
    'NAME' => 'Заявки на покупку сертификатов (DNK)',
    'CODE' => $iblockCode,
    'IBLOCK_TYPE_ID' => $typeId,
    'SITE_ID' => $siteIds,
    'SORT' => 500,
    'LIST_PAGE_URL' => '',
    'DETAIL_PAGE_URL' => '',
    'RSS_FILE_ACTIVE' => 'N',
];

$iblockId = (int)$newIblock->Add($fields);
if ($iblockId <= 0) {
    $dnkCiErr('CIBlock::Add failed: ' . $newIblock->LAST_ERROR . "\n");
    $dnkCiFinish();

    exit(1);
}

$newProp = new CIBlockProperty();

$pTotal = [
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Сумма, BYN',
    'ACTIVE' => 'Y',
    'SORT' => 100,
    'CODE' => 'TOTAL_SUM',
    'PROPERTY_TYPE' => 'N',
    'MULTIPLE' => 'N',
    'FILTRABLE' => 'Y',
    'SEARCHABLE' => 'N',
];
$rTotal = $newProp->Add($pTotal);
if (!$rTotal) {
    $dnkCiErr('Не удалось создать TOTAL_SUM.' . "\n");
    $dnkCiFinish();

    exit(1);
}

$pJson = [
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Состав (JSON)',
    'ACTIVE' => 'Y',
    'SORT' => 110,
    'CODE' => 'ITEMS_JSON',
    'PROPERTY_TYPE' => 'S',
    'MULTIPLE' => 'N',
];
if (!$newProp->Add($pJson)) {
    $dnkCiErr('Не удалось создать ITEMS_JSON.' . "\n");
    $dnkCiFinish();

    exit(1);
}

$addList = static function (
    CIBlockProperty $installer,
    int $bid,
    string $code,
    string $name,
    int $sort,
    array $enumsXml,
    callable $onFail
): int {
    $pid = $installer->Add([
        'IBLOCK_ID' => $bid,
        'NAME' => $name,
        'ACTIVE' => 'Y',
        'SORT' => $sort,
        'CODE' => $code,
        'PROPERTY_TYPE' => 'L',
        'LIST_TYPE' => 'L',
        'MULTIPLE' => 'N',
        'FILTRABLE' => 'Y',
    ]);
    $pid = (int)$pid;
    if ($pid <= 0) {
        $onFail("Add list property {$code} failed.\n");

        return 0;
    }

    $enum = new CIBlockPropertyEnum();
    $s = 100;
    $isFirst = true;
    foreach ($enumsXml as $xml => $caption) {
        $rid = $enum->Add([
            'PROPERTY_ID' => $pid,
            'VALUE' => $caption,
            'XML_ID' => $xml,
            'DEF' => $isFirst ? 'Y' : 'N',
            'SORT' => $s,
        ]);
        $isFirst = false;
        $s += 100;
        if (!$rid) {
            $onFail("Add enum for {$code} {$xml} failed.\n");

            return 0;
        }
    }

    return $pid;
};

$fatal = static function (string $msg) use ($dnkCiErr, $dnkCiFinish): void {
    $dnkCiErr($msg);
    $dnkCiFinish();

    exit(1);
};

$installer = new CIBlockProperty();
$addList($installer, $iblockId, 'DELIVERY', 'Способ доставки', 200, [
    'courier' => 'Доставка курьером',
    'pickup' => 'Самовывоз',
], $fatal);
$addList($installer, $iblockId, 'PAYMENT', 'Способ оплаты', 210, ['cash_on_delivery' => 'Оплата при получении'], $fatal);
$addList($installer, $iblockId, 'STATUS', 'Статус заявки', 220, [
    'accepted' => 'Принят',
    'in_progress' => 'В обработке',
    'ready' => 'Готов',
], $fatal);

$pUserNum = [
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Пользователь (ID)',
    'ACTIVE' => 'Y',
    'SORT' => 280,
    'CODE' => 'USER',
    'PROPERTY_TYPE' => 'N',
    'MULTIPLE' => 'N',
    'SEARCHABLE' => 'N',
    'FILTRABLE' => 'Y',
];
if (!$newProp->Add($pUserNum)) {
    $fatal('Не удалось создать свойство USER.' . "\n");
}

$stringProps = [
    ['CONTACT_NAME', 'Контакт: имя', 300],
    ['CONTACT_PHONE', 'Контакт: телефон', 310],
    ['CONTACT_EMAIL', 'E-mail (из профиля)', 320],
];

foreach ($stringProps as [$code, $name, $sort]) {
    if (!$newProp->Add([
        'IBLOCK_ID' => $iblockId,
        'NAME' => $name,
        'ACTIVE' => 'Y',
        'SORT' => $sort,
        'CODE' => $code,
        'PROPERTY_TYPE' => 'S',
        'MULTIPLE' => 'N',
    ])) {
        $fatal('Не удалось создать свойство ' . $code . ".\n");
    }
}

if (!$newProp->Add([
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Комментарий',
    'ACTIVE' => 'Y',
    'SORT' => 330,
    'CODE' => 'COMMENT',
    'PROPERTY_TYPE' => 'S',
    'MULTIPLE' => 'N',
])) {
    $fatal('Не удалось создать COMMENT.' . "\n");
}

$dnkCiOut("Created iblock dnk_certificate_requests ID={$iblockId}\n");
$dnkCiOut("Set .env variable: DNK_CERTIFICATE_REQUEST_IBLOCK_ID={$iblockId}\n");
$dnkCiOut("Ensure .env has DNK_CERTIFICATE_CATALOG_IBLOCK_ID={$catalogIblockArg}.\n");
$dnkCiOut('Grant ADD rights on this iblock to guests/anonymous users if anonymous certificate requests are required.' . "\n");
$dnkCiOut('On existing installs: ensure DELIVERY list has enum XML_ID=pickup (caption: Samovyvoz).' . "\n");
$dnkCiOut('On existing installs: re-run this script to add STATUS property and backfill accepted.' . "\n");

$dnkCiFinish();

exit(0);
