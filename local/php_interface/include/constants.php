<?php

$dnkEnvPath = dirname(__DIR__, 3) . '/.env';
if (is_file($dnkEnvPath) && is_readable($dnkEnvPath)) {
    $dnkEnvValues = parse_ini_file($dnkEnvPath, false, INI_SCANNER_RAW);

    if (is_array($dnkEnvValues)) {
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

    if (isset($_ENV[$name])) {
        return (string)$_ENV[$name];
    }

    return isset($_SERVER[$name]) ? (string)$_SERVER[$name] : '';
};

define('DNK_CATALOG_IBLOCK_ID', (int)$dnkEnv('DNK_CATALOG_IBLOCK_ID'));

/** Инфоблок узкого промо-баннера в шапке (CODE dnk_header_promo). */
define('DNK_HEADER_PROMO_IBLOCK_ID', (int)$dnkEnv('DNK_HEADER_PROMO_IBLOCK_ID'));

/** URL (GET): полный список данных по бонусам. */
define('DNK_BONUS_ENDPOINT', $dnkEnv('DNK_BONUS_ENDPOINT'));

/** URL для POST JSON заказа; пустая строка — отправка отключена (задачи остаются в очереди). */
define('DNK_ORDER_EXPORT_ENDPOINT', $dnkEnv('DNK_ORDER_EXPORT_ENDPOINT'));

/** Логин для авторизации на сервере */
define('DNK_ORDER_EXPORT_LOGIN', $dnkEnv('DNK_ORDER_EXPORT_LOGIN'));

/** Пароль для авторизации на сервере */
define('DNK_ORDER_EXPORT_PASSWORD', $dnkEnv('DNK_ORDER_EXPORT_PASSWORD'));

/** Сколько записей обрабатывать за один запуск агента. */
define('DNK_ORDER_EXPORT_QUEUE_BATCH', (int)$dnkEnv('DNK_ORDER_EXPORT_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_ORDER_EXPORT_MAX_ATTEMPTS', (int)$dnkEnv('DNK_ORDER_EXPORT_MAX_ATTEMPTS'));

/** Интервал агента в секундах (при PERIOD=Y). */
define('DNK_ORDER_EXPORT_AGENT_INTERVAL', (int)$dnkEnv('DNK_ORDER_EXPORT_AGENT_INTERVAL'));

/** Сколько задач очереди бонусов по телефону за один запуск агента. */
define('DNK_BONUS_BALANCE_QUEUE_BATCH', (int)$dnkEnv('DNK_BONUS_BALANCE_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS', (int)$dnkEnv('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS'));

/** Интервал агента очереди бонусов (секунды, для регистрации в админке). */
define('DNK_BONUS_BALANCE_AGENT_INTERVAL', (int)$dnkEnv('DNK_BONUS_BALANCE_AGENT_INTERVAL'));

/** Срок жизни начислений при импорте бонусов (лет). */
define('DNK_BONUS_IMPORT_ACTIVE_YEARS', (int)$dnkEnv('DNK_BONUS_IMPORT_ACTIVE_YEARS'));

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

/** URL (POST JSON): регистрация клиента после OnAfterUserRegister; пустая строка — отправка отключена. */
define('DNK_USER_REGISTER_EXPORT_ENDPOINT', $dnkEnv('DNK_USER_REGISTER_EXPORT_ENDPOINT'));

/** Сколько задач очереди регистрации за один запуск агента. */
define('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH', (int)$dnkEnv('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH'));

/** После стольких неудачных попыток статус E. */
define('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS', (int)$dnkEnv('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS'));