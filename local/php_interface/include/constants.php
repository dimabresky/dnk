<?php

define('DNK_CATALOG_IBLOCK_ID', 1);

/** Инфоблок узкого промо-баннера в шапке (CODE dnk_header_promo). */
define('DNK_HEADER_PROMO_IBLOCK_ID', 44);

/** URL (GET): полный список данных по бонусам. */
define('DNK_BONUS_ENDPOINT', 'http://37.17.18.54:9111/human_ut_test/hs/bonus/bonus/');

/** URL для POST JSON заказа; пустая строка — отправка отключена (задачи остаются в очереди). */
define('DNK_ORDER_EXPORT_ENDPOINT', 'http://192.168.0.2/human_ut_test/hs/bonus/offs/');

/** Логин для авторизации на сервере */
define('DNK_ORDER_EXPORT_LOGIN', 'Odata');

/** Пароль для авторизации на сервере */
define('DNK_ORDER_EXPORT_PASSWORD', '123456');

/** Сколько записей обрабатывать за один запуск агента. */
define('DNK_ORDER_EXPORT_QUEUE_BATCH', 10);

/** После стольких неудачных попыток статус E. */
define('DNK_ORDER_EXPORT_MAX_ATTEMPTS', 5);

/** Интервал агента в секундах (при PERIOD=Y). */
define('DNK_ORDER_EXPORT_AGENT_INTERVAL', 300);

/** Сколько задач очереди бонусов по телефону за один запуск агента. */
define('DNK_BONUS_BALANCE_QUEUE_BATCH', 10);

/** После стольких неудачных попыток статус E. */
define('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS', 5);

/** Интервал агента очереди бонусов (секунды, для регистрации в админке). */
define('DNK_BONUS_BALANCE_AGENT_INTERVAL', 120);

/** Срок жизни начислений при импорте бонусов (лет). */
define('DNK_BONUS_IMPORT_ACTIVE_YEARS', 2);

/** Код бонусной программы в ответе импорта (поле БонуснаяПрограммаЛояльности). */
define('DNK_BONUS_IMPORT_PROGRAM_CODE', 'dnk');

/** Ключ JSON: бонусная программа лояльности. */
define('DNK_BONUS_JSON_KEY_PROGRAM', 'БонуснаяПрограммаЛояльности');

/** Ключ JSON: UUID контрагентов. */
define('DNK_BONUS_JSON_KEY_COUNTERPARTY_UUIDS', 'КонтрагентыUUID');

/** Ключ JSON: начисленный остаток бонусов. */
define('DNK_BONUS_JSON_KEY_BALANCE', 'НачисленоОстаток');

/** Ключ JSON: номер телефона партнёра (сопоставление при синхронизации по телефону). */
define('DNK_BONUS_JSON_KEY_PARTNER_PHONE', 'ПартнерНомерТелефона');

/** URL (POST JSON): регистрация клиента после OnAfterUserRegister; пустая строка — отправка отключена. */
define('DNK_USER_REGISTER_EXPORT_ENDPOINT', 'http://37.17.18.54:9111/human_ut/hs/bonus/client');

/** Сколько задач очереди регистрации за один запуск агента. */
define('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH', 10);

/** После стольких неудачных попыток статус E. */
define('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS', 5);