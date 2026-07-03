<?php

$dnkEnvPath = dirname(__DIR__, 3) . '/.env';
if (is_file($dnkEnvPath) && is_readable($dnkEnvPath)) {
    $dnkEnvValues = [];
    $dnkEnvLines = file($dnkEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (is_array($dnkEnvLines)) {
        foreach ($dnkEnvLines as $dnkEnvLine) {
            $dnkEnvLine = trim($dnkEnvLine);

            if ($dnkEnvLine === '' || $dnkEnvLine[0] === '#' || $dnkEnvLine[0] === ';') {
                continue;
            }

            $dnkEnvEqualsPosition = strpos($dnkEnvLine, '=');
            if ($dnkEnvEqualsPosition === false) {
                continue;
            }

            $dnkEnvName = trim(substr($dnkEnvLine, 0, $dnkEnvEqualsPosition));
            if ($dnkEnvName === '') {
                continue;
            }

            $dnkEnvValue = trim(substr($dnkEnvLine, $dnkEnvEqualsPosition + 1));
            $dnkEnvQuote = $dnkEnvValue[0] ?? '';

            if (
                $dnkEnvQuote !== ''
                && ($dnkEnvQuote === '"' || $dnkEnvQuote === "'")
                && substr($dnkEnvValue, -1) === $dnkEnvQuote
            ) {
                $dnkEnvValue = substr($dnkEnvValue, 1, -1);
            }

            $dnkEnvValues[$dnkEnvName] = $dnkEnvValue;
        }

        foreach ($dnkEnvValues as $dnkEnvName => $dnkEnvValue) {
            $_ENV[$dnkEnvName] = $dnkEnvValue;
            $_SERVER[$dnkEnvName] = $dnkEnvValue;
            putenv($dnkEnvName . '=' . $dnkEnvValue);
        }
    }
}

$dnkEnv = static function (string $name): string {
    $value = getenv($name);

    if ($value !== false) {
        return $value;
    }

    if (array_key_exists($name, $_ENV)) {
        return (string)$_ENV[$name];
    }

    if (array_key_exists($name, $_SERVER)) {
        return (string)$_SERVER[$name];
    }

    throw new RuntimeException(sprintf('Required environment variable "%s" is not set.', $name));
};

$dnkEnvInt = static function (string $name) use ($dnkEnv): int {
    $value = trim($dnkEnv($name));

    if ($value === '' || filter_var($value, FILTER_VALIDATE_INT) === false) {
        throw new RuntimeException(sprintf('Required environment variable "%s" must be an integer.', $name));
    }

    return (int)$value;
};

$dnkEnvDefault = static function (string $name, string $default) use ($dnkEnv): string {
    try {
        $value = $dnkEnv($name);
    } catch (RuntimeException $e) {
        return $default;
    }

    return trim($value) !== '' ? $value : $default;
};

define('DNK_CATALOG_IBLOCK_ID', $dnkEnvInt('DNK_CATALOG_IBLOCK_ID'));

/** Инфоблок узкого промо-баннера в шапке (CODE dnk_header_promo). */
define('DNK_HEADER_PROMO_IBLOCK_ID', $dnkEnvInt('DNK_HEADER_PROMO_IBLOCK_ID'));

/** Заявки на покупку подарочных сертификатов (CODE dnk_certificate_requests). */
define('DNK_CERTIFICATE_REQUEST_IBLOCK_ID', $dnkEnvInt('DNK_CERTIFICATE_REQUEST_IBLOCK_ID'));

/** Инфоблок номинальных сертификатов (DETAIL_PICTURE, свойство NOMINAL). */
define('DNK_CERTIFICATE_CATALOG_IBLOCK_ID', $dnkEnvInt('DNK_CERTIFICATE_CATALOG_IBLOCK_ID'));

/** Инфоблок точек самовывоза (магазины: ADDRESS, PHONE, SCHEDULE, MAP). */
define('DNK_PICKUP_STORES_IBLOCK_ID', $dnkEnvInt('DNK_PICKUP_STORES_IBLOCK_ID'));

/** ID почтового шаблона (событие CUSTOM_MAIL): уведомление менеджера о новой заявке на сертификаты. */
define('DNK_CERTIFICATE_REQUEST_MAIL_TEMPLATE_ID', $dnkEnvInt('DNK_CERTIFICATE_REQUEST_MAIL_TEMPLATE_ID'));

/** ID почтового шаблона (событие CUSTOM_MAIL): запрос пользователя на смену дня рождения. */
define('DNK_BIRTHDAY_CHANGE_REQUEST_MAIL_TEMPLATE_ID', $dnkEnvInt('DNK_BIRTHDAY_CHANGE_REQUEST_MAIL_TEMPLATE_ID'));

/** URL (GET): полный список данных по бонусам. */
define('DNK_BONUS_ENDPOINT', $dnkEnv('DNK_BONUS_ENDPOINT'));

/** URL для POST JSON заказа; пустая строка — отправка отключена (задачи остаются в очереди). */
define('DNK_ORDER_EXPORT_ENDPOINT', $dnkEnv('DNK_ORDER_EXPORT_ENDPOINT'));

/** Логин для авторизации на сервере */
define('DNK_ORDER_EXPORT_LOGIN', $dnkEnv('DNK_ORDER_EXPORT_LOGIN'));

/** Пароль для авторизации на сервере */
define('DNK_ORDER_EXPORT_PASSWORD', $dnkEnv('DNK_ORDER_EXPORT_PASSWORD'));

/** Сколько записей обрабатывать за один запуск агента. */
define('DNK_ORDER_EXPORT_QUEUE_BATCH', $dnkEnvInt('DNK_ORDER_EXPORT_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_ORDER_EXPORT_MAX_ATTEMPTS', $dnkEnvInt('DNK_ORDER_EXPORT_MAX_ATTEMPTS'));

/** Интервал агента в секундах (при PERIOD=Y). */
define('DNK_ORDER_EXPORT_AGENT_INTERVAL', $dnkEnvInt('DNK_ORDER_EXPORT_AGENT_INTERVAL'));

/** Сколько задач очереди бонусов по телефону за один запуск агента. */
define('DNK_BONUS_BALANCE_QUEUE_BATCH', $dnkEnvInt('DNK_BONUS_BALANCE_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS', $dnkEnvInt('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS'));

/** Интервал агента очереди бонусов (секунды, для регистрации в админке). */
define('DNK_BONUS_BALANCE_AGENT_INTERVAL', $dnkEnvInt('DNK_BONUS_BALANCE_AGENT_INTERVAL'));

/** Срок жизни начислений при импорте бонусов (лет). */
define('DNK_BONUS_IMPORT_ACTIVE_YEARS', $dnkEnvInt('DNK_BONUS_IMPORT_ACTIVE_YEARS'));

/** Код бонусной программы в ответе импорта (поле БонуснаяПрограммаЛояльности). */
define('DNK_BONUS_IMPORT_PROGRAM_CODE', $dnkEnv('DNK_BONUS_IMPORT_PROGRAM_CODE'));

/** Ключ JSON: бонусная программа лояльности. */
define('DNK_BONUS_JSON_KEY_PROGRAM', $dnkEnv('DNK_BONUS_JSON_KEY_PROGRAM'));

/** Ключ JSON: UUID контрагентов. */
define('DNK_BONUS_JSON_KEY_COUNTERPARTY_UUIDS', $dnkEnv('DNK_BONUS_JSON_KEY_COUNTERPARTY_UUIDS'));

/** Ключ JSON: начисленный остаток бонусов. */
define('DNK_BONUS_JSON_KEY_BALANCE', $dnkEnv('DNK_BONUS_JSON_KEY_BALANCE'));

/** Ключ JSON: номер телефона партнёра (сопоставление при синхронизации по телефону). */
define('DNK_BONUS_JSON_KEY_PARTNER_PHONE', $dnkEnv('DNK_BONUS_JSON_KEY_PARTNER_PHONE'));

/** Ключ JSON: уровень клиента (UF_LEVEL). */
define('DNK_BONUS_JSON_KEY_CLIENT_LEVEL', $dnkEnv('DNK_BONUS_JSON_KEY_CLIENT_LEVEL'));

/** Ключ JSON: сумма для перехода на следующий уровень (UF_NEXT_LEVEL_COST). */
define('DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST', $dnkEnv('DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST'));

/** Ключ JSON: дата ближайшего списания бонусов (UF_BONUS_EXPIRE_DATE). */
define('DNK_BONUS_JSON_KEY_EXPIRE_DATE', $dnkEnvDefault('DNK_BONUS_JSON_KEY_EXPIRE_DATE', 'ДатаСписание'));

/** Ключ JSON: сумма ближайшего списания бонусов (UF_BONUS_EXPIRE_AMOUNT). */
define('DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT', $dnkEnvDefault('DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT', 'БлижайшееСписание'));

/** Каталог JSON-выгрузок остатков бонусов (относительно DOCUMENT_ROOT). */
define('DNK_BONUS_CLIENT_IMPORT_DIR', $dnkEnv('DNK_BONUS_CLIENT_IMPORT_DIR'));

/** Каталог логов импорта бонусов из файлов (относительно DOCUMENT_ROOT). */
define('DNK_BONUS_CLIENT_IMPORT_LOG_DIR', $dnkEnv('DNK_BONUS_CLIENT_IMPORT_LOG_DIR'));

/** URL (POST JSON): регистрация клиента после OnAfterUserRegister; пустая строка — отправка отключена. */
define('DNK_USER_REGISTER_EXPORT_ENDPOINT', $dnkEnv('DNK_USER_REGISTER_EXPORT_ENDPOINT'));

/** Сколько задач очереди регистрации за один запуск агента. */
define('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH', $dnkEnvInt('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS', $dnkEnvInt('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS'));

/** Базовый URL сайта для абсолютных ссылок в product feed (без завершающего /). */
define('DNK_SITE_URL', rtrim($dnkEnv('DNK_SITE_URL'), '/'));

/** Заголовок channel в dnk_products_feed.xml. */
define('DNK_PRODUCT_FEED_CHANNEL_TITLE', $dnkEnvDefault('DNK_PRODUCT_FEED_CHANNEL_TITLE', 'DNK.BY'));

/** Интервал агента product feed (секунды, для регистрации в админке). */
define('DNK_PRODUCT_FEED_AGENT_INTERVAL', $dnkEnvInt('DNK_PRODUCT_FEED_AGENT_INTERVAL'));

unset(
    $dnkEnvPath,
    $dnkEnvValues,
    $dnkEnvLines,
    $dnkEnvLine,
    $dnkEnvEqualsPosition,
    $dnkEnvName,
    $dnkEnvValue,
    $dnkEnvQuote,
    $dnkEnv,
    $dnkEnvInt
);