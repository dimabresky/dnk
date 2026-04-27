<?php

namespace Dnk\PhpInterface;

use Aspro\Bonus\Enums\HistoryOperations as BonusHistoryOperationsEnum;
use Aspro\Bonus\Helper as BonusHelper;
use Aspro\Bonus\History\User as BonusUser;
use Aspro\Bonus\ORM\HistoryOperationsTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Sale\BasketItemBase;

/**
 * Общие вспомогательные методы для php_interface.
 */
final class Utils
{
    private const DNK_BONUS_IMPORT_DETAIL_MARKER = '[DNK_BONUS_IMPORT]';

    /**
     * Безопасное значение для CSS font-size (px, rem, em, % или число — трактуется как px).
     */
    public static function sanitizeCssFontSize(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?(px|rem|em|%)$/i', $value)) {
            return strtolower($value);
        }
        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            return $value . 'px';
        }

        return '';
    }

    public static function roundMoney(float $value): float
    {
        return round($value, 2);
    }

    /**
     * @return float|int
     */
    public static function normalizeQuantity(float $quantity)
    {
        if (abs($quantity - round($quantity)) < 1e-6) {
            return (int)round($quantity);
        }

        return round($quantity, 3);
    }

    public static function resolveDiscountName(): string
    {
        return 'Скидка';
    }

    /**
     * Нормализация телефона до цифр (сравнение с полем из выгрузки бонусов).
     */
    public static function normalizeBonusPhoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Парсинг суммы «НачисленоОстаток» из ответа импорта бонусов.
     */
    public static function parseBonusImportAmount(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $raw = str_replace(',', '.', (string)$value);

        return is_numeric($raw) ? max(0.0, (float)$raw) : 0.0;
    }

    /**
     * GET DNK_BONUS_ENDPOINT: полный JSON-список бонусов или null при ошибке.
     *
     * @return array<int, mixed>|null
     */
    public static function fetchBonusEndpointJsonList(): ?array
    {
        $url = defined('DNK_BONUS_ENDPOINT') ? trim((string)DNK_BONUS_ENDPOINT) : '';
        if ($url === '') {
            return null;
        }

        $body = self::requestBonusEndpoint($url);
        if ($body === null) {
            return null;
        }

        $list = json_decode($body, true);

        return is_array($list) ? $list : null;
    }

    /**
     * GET {DNK_BONUS_ENDPOINT}/{номер} — ответ по одному телефону, разбор как у агента (программа, НачисленоОстаток).
     */
    public static function fetchBonusBalanceByPhoneDigits(string $phoneDigits): ?float
    {
        $phoneDigits = self::normalizeBonusPhoneDigits($phoneDigits);
        if ($phoneDigits === '') {
            return null;
        }

        $base = defined('DNK_BONUS_ENDPOINT') ? trim((string)DNK_BONUS_ENDPOINT) : '';
        if ($base === '') {
            return null;
        }

        $url = rtrim($base, '/') . '/' . $phoneDigits;
        $body = self::requestBonusEndpoint($url);
        if ($body === null) {
            return null;
        }

        $decoded = json_decode($body, true);

        return self::resolveBalanceFromBonusJsonDecoded($decoded);
    }

    /**
     * Запрос {DNK_BONUS_ENDPOINT}/{телефон}, начисление остатка как у агента импорта.
     * Если ответ не удалось разобрать — баланс не меняется.
     */
    public static function syncDnkImportBonusesForUserByPhone(int $userId): void
    {
        self::trySyncDnkImportBonusesForUserByPhone($userId);
    }

    /**
     * Синхронизация бонусов по телефону. false — не удалось (сеть, нет телефона, парсинг и т.д.).
     *
     * @param string|null $errorDetail Краткая причина неудачи (для лога/очереди).
     */
    public static function trySyncDnkImportBonusesForUserByPhone(int $userId, ?string &$errorDetail = null): bool
    {
        $errorDetail = null;

        if ($userId <= 0) {
            $errorDetail = 'invalid_user_id';

            return false;
        }

        $base = defined('DNK_BONUS_ENDPOINT') ? trim((string)DNK_BONUS_ENDPOINT) : '';
        if ($base === '') {
            $errorDetail = 'DNK_BONUS_ENDPOINT_empty';

            return false;
        }

        if (!Loader::includeModule('aspro.bonus')) {
            $errorDetail = 'aspro.bonus_not_loaded';

            return false;
        }

        $phoneDigits = self::resolveUserPhoneDigitsForBonus($userId);
        if ($phoneDigits === null || $phoneDigits === '') {
            $errorDetail = 'no_phone';

            return false;
        }

        $resolved = self::fetchBonusBalanceByPhoneDigits($phoneDigits);
        if ($resolved === null) {
            $errorDetail = 'bonus_api_or_parse_failed';

            return false;
        }

        self::replaceDnkImportBonusesForUser($userId, $resolved);

        return true;
    }

    /**
     * Поставить пользователя в очередь на получение бонусов по телефону (без дубля при уже ожидающей задаче).
     * Запись в статусе ошибки снова переводится в ожидание.
     */
    public static function enqueueBonusBalanceSyncIfNotPending(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $endpoint = defined('DNK_BONUS_ENDPOINT') ? trim((string)DNK_BONUS_ENDPOINT) : '';
        if ($endpoint === '') {
            return;
        }

        $row = BonusBalanceQueueTable::getList([
            'filter' => ['=USER_ID' => $userId],
            'select' => ['ID', 'STATUS'],
            'limit' => 1,
        ])->fetch();

        $now = new DateTime();

        if ($row !== false) {
            if ($row['STATUS'] === BonusBalanceQueueTable::STATUS_PENDING) {
                return;
            }
            if ($row['STATUS'] === BonusBalanceQueueTable::STATUS_ERROR) {
                BonusBalanceQueueTable::update((int)$row['ID'], [
                    'STATUS' => BonusBalanceQueueTable::STATUS_PENDING,
                    'ATTEMPTS' => 0,
                    'LAST_ERROR' => null,
                    'DATE_INSERT' => $now,
                    'DATE_UPDATE' => $now,
                ]);
            }

            return;
        }

        BonusBalanceQueueTable::add([
            'USER_ID' => $userId,
            'STATUS' => BonusBalanceQueueTable::STATUS_PENDING,
            'ATTEMPTS' => 0,
            'DATE_INSERT' => $now,
        ]);
    }

    /**
     * Поставить пользователя в очередь на POST профиля после регистрации (без дубля при уже ожидающей задаче).
     * Запись в статусе ошибки снова переводится в ожидание.
     */
    public static function enqueueUserRegisterExportIfNotPending(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $endpoint = defined('DNK_USER_REGISTER_EXPORT_ENDPOINT') ? trim((string)DNK_USER_REGISTER_EXPORT_ENDPOINT) : '';
        if ($endpoint === '') {
            return;
        }

        $row = UserRegisterExportQueueTable::getList([
            'filter' => ['=USER_ID' => $userId],
            'select' => ['ID', 'STATUS'],
            'limit' => 1,
        ])->fetch();

        $now = new DateTime();

        if ($row !== false) {
            if ($row['STATUS'] === UserRegisterExportQueueTable::STATUS_PENDING) {
                return;
            }
            if ($row['STATUS'] === UserRegisterExportQueueTable::STATUS_ERROR) {
                UserRegisterExportQueueTable::update((int)$row['ID'], [
                    'STATUS' => UserRegisterExportQueueTable::STATUS_PENDING,
                    'ATTEMPTS' => 0,
                    'LAST_ERROR' => null,
                    'DATE_INSERT' => $now,
                    'DATE_UPDATE' => $now,
                ]);
            }

            return;
        }

        UserRegisterExportQueueTable::add([
            'USER_ID' => $userId,
            'STATUS' => UserRegisterExportQueueTable::STATUS_PENDING,
            'ATTEMPTS' => 0,
            'DATE_INSERT' => $now,
        ]);
    }

    /**
     * POST профиля на DNK_USER_REGISTER_EXPORT_ENDPOINT; при успехе — XML_ID = первый UUID из КонтрагентыUUID (если XML_ID был пуст).
     *
     * @param string|null $errorDetail Краткая причина неудачи (для очереди).
     */
    public static function tryPostUserRegisterExportAndUpdateXmlId(int $userId, ?string &$errorDetail = null): bool
    {
        $errorDetail = null;

        if ($userId <= 0) {
            $errorDetail = 'invalid_user_id';

            return false;
        }

        $url = defined('DNK_USER_REGISTER_EXPORT_ENDPOINT') ? trim((string)DNK_USER_REGISTER_EXPORT_ENDPOINT) : '';
        if ($url === '') {
            $errorDetail = 'DNK_USER_REGISTER_EXPORT_ENDPOINT_empty';

            return false;
        }

        $payload = self::buildUserRegisterExportPayload($userId);
        if ($payload === null) {
            $errorDetail = 'no_phone_or_user';

            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $errorDetail = 'json_encode_failed';

            return false;
        }

        $httpResult = self::requestUserRegisterExportPost($url, $json);
        
        if (!$httpResult['ok']) {
            $errorDetail = $httpResult['error'];

            return false;
        }

        $decoded = json_decode($httpResult['body'], true);
        $uuid = self::extractFirstCounterpartyUuidFromRegisterResponse($decoded);
        if ($uuid === null || $uuid === '') {
            $errorDetail = 'no_counterparty_uuid_in_response';

            return false;
        }

        $userRow = UserTable::getList([
            'filter' => ['=ID' => $userId],
            'select' => ['ID', 'XML_ID'],
            'limit' => 1,
        ])->fetch();

        if ($userRow === false) {
            $errorDetail = 'user_not_found';

            return false;
        }

        $currentXml = trim((string)($userRow['XML_ID'] ?? ''));
        if ($currentXml === '') {
            $result = (new \CUser)->Update($userId, ['XML_ID' => $uuid]);
            if (!$result) {
                $errorDetail = 'user_xml_id_update_failed';

                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildUserRegisterExportPayload(int $userId): ?array
    {
        $phoneDigits = self::resolveUserPhoneDigitsForBonus($userId);
        if ($phoneDigits === null || $phoneDigits === '') {
            return null;
        }

        $user = UserTable::getList([
            'filter' => ['=ID' => $userId],
            'select' => ['NAME', 'LAST_NAME', 'EMAIL', 'PERSONAL_BIRTHDAY', 'PERSONAL_GENDER', 'LOGIN'],
            'limit' => 1,
        ])->fetch();

        if ($user === false) {
            return null;
        }

        $first = trim((string)($user['NAME'] ?? ''));
        $last = trim((string)($user['LAST_NAME'] ?? ''));
        $client = trim($first . ' ' . $last);
        if ($client === '') {
            $email = trim((string)($user['EMAIL'] ?? ''));
            $client = $email !== '' ? $email : trim((string)($user['LOGIN'] ?? ''));
        }

        $payload = [
            'client' => $client,
            'phone' => (int)$phoneDigits,
            'email' => trim((string)($user['EMAIL'] ?? '')),
        ];

        $birth = self::formatUserBirthDateForRegisterExport($user['PERSONAL_BIRTHDAY'] ?? null);
        if ($birth !== null) {
            $payload['birth_date'] = $birth;
        }

        $gender = self::mapPersonalGenderToRegisterApi($user['PERSONAL_GENDER'] ?? null);
        if ($gender !== null) {
            $payload['gender'] = $gender;
        }

        return $payload;
    }

    /**
     * @return array{ok: bool, body: string, error: string}
     */
    private static function requestUserRegisterExportPost(string $url, string $body): array
    {
        $http = new HttpClient([
            'socketTimeout' => 15,
            'streamTimeout' => 15,
        ]);
        $http->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $http->setHeader('Accept', 'application/json');

        if (defined('DNK_ORDER_EXPORT_LOGIN') && defined('DNK_ORDER_EXPORT_PASSWORD')) {
            $http->setAuthorization((string)DNK_ORDER_EXPORT_LOGIN, (string)DNK_ORDER_EXPORT_PASSWORD);
        }

        try {
            $response = $http->post($url, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => '', 'error' => $e->getMessage()];
        }

        $status = $http->getStatus();
        $responseStr = is_string($response) ? $response : '';

        if ($status < 200 || $status >= 300) {
            $err = 'HTTP ' . $status;
            if ($responseStr !== '') {
                $err .= ': ' . mb_substr($responseStr, 0, 500);
            }

            return ['ok' => false, 'body' => $responseStr, 'error' => $err];
        }

        return ['ok' => true, 'body' => $responseStr, 'error' => ''];
    }

    /**
     * @param mixed $decoded Результат json_decode ответа регистрации клиента.
     */
    private static function extractFirstCounterpartyUuidFromRegisterResponse(mixed $decoded): ?string
    {
        if (!is_array($decoded) || $decoded === []) {
            return null;
        }

        $firstRow = null;
        if (array_is_list($decoded)) {
            $firstRow = $decoded[0] ?? null;
        } else {
            $firstRow = $decoded;
        }

        if (!is_array($firstRow)) {
            return null;
        }

        $key = DNK_BONUS_JSON_KEY_COUNTERPARTY_UUIDS;
        $list = $firstRow[$key] ?? null;
        if (!is_array($list)) {
            return null;
        }

        foreach ($list as $item) {
            $s = trim((string)$item);
            if ($s !== '') {
                return $s;
            }
        }

        return null;
    }

    private static function formatUserBirthDateForRegisterExport(mixed $birthday): ?string
    {
        if ($birthday === null || $birthday === '') {
            return null;
        }

        if ($birthday instanceof Date) {
            return $birthday->format('Y-m-d');
        }

        if ($birthday instanceof \DateTimeInterface) {
            return $birthday->format('Y-m-d');
        }

        $s = trim((string)$birthday);
        if ($s === '') {
            return null;
        }

        $ts = strtotime($s);

        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    private static function mapPersonalGenderToRegisterApi(mixed $personalGender): ?string
    {
        $g = strtoupper(trim((string)$personalGender));
        if ($g === 'M') {
            return 'm';
        }
        if ($g === 'F') {
            return 'f';
        }

        return null;
    }

    private static function requestBonusEndpoint(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $http = new HttpClient([
            'socketTimeout' => 15,
            'streamTimeout' => 15,
        ]);
        $http->setHeader('Accept', 'application/json');

        if (defined('DNK_ORDER_EXPORT_LOGIN') && defined('DNK_ORDER_EXPORT_PASSWORD')) {
            $http->setAuthorization((string)DNK_ORDER_EXPORT_LOGIN, (string)DNK_ORDER_EXPORT_PASSWORD);
        }

        try {
            $body = $http->get($url);
        } catch (\Throwable $e) {
            return null;
        }

        $status = $http->getStatus();
        if ($status < 200 || $status >= 300) {
            return null;
        }

        if (!is_string($body) || $body === '') {
            return null;
        }

        return $body;
    }

    /**
     * @param mixed $decoded Результат json_decode для ответа по телефону.
     */
    private static function resolveBalanceFromBonusJsonDecoded(mixed $decoded): ?float
    {
        if (!is_array($decoded)) {
            return null;
        }
        if ($decoded === []) {
            return null;
        }

        if (array_is_list($decoded)) {
            $resolved = null;
            foreach ($decoded as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $v = self::resolveBalanceFromBonusJsonRow($item);
                if ($v !== null) {
                    $resolved = $v;
                }
            }

            return $resolved;
        }

        return self::resolveBalanceFromBonusJsonRow($decoded);
    }

    /**
     * Одна строка ответа: фильтр программы и поле НачисленоОстаток (как в агенте).
     *
     * @param array<string, mixed> $row
     */
    private static function resolveBalanceFromBonusJsonRow(array $row): ?float
    {
        $keyBalance = DNK_BONUS_JSON_KEY_BALANCE;
        if (!array_key_exists($keyBalance, $row)) {
            return null;
        }

        $codeDnk = strtolower((string)DNK_BONUS_IMPORT_PROGRAM_CODE);
        $keyProgram = DNK_BONUS_JSON_KEY_PROGRAM;
        if (isset($row[$keyProgram])) {
            $prog = strtolower(trim((string)$row[$keyProgram]));
            if ($prog !== '' && $prog !== $codeDnk) {
                return null;
            }
        }

        return self::parseBonusImportAmount($row[$keyBalance]);
    }

    private static function resolveUserPhoneDigitsForBonus(int $userId): ?string
    {
        $row = UserPhoneAuthTable::getByPrimary($userId)->fetch();
        if ($row !== false && isset($row['PHONE_NUMBER'])) {
            $p = trim((string)$row['PHONE_NUMBER']);
            if ($p !== '') {
                $d = self::normalizeBonusPhoneDigits($p);

                return $d !== '' ? $d : null;
            }
        }

        $user = UserTable::getList([
            'filter' => ['=ID' => $userId],
            'select' => ['PERSONAL_PHONE', 'WORK_PHONE'],
            'limit' => 1,
        ])->fetch();

        if ($user === false) {
            return null;
        }

        foreach (['PERSONAL_PHONE', 'WORK_PHONE'] as $field) {
            $p = trim((string)($user[$field] ?? ''));
            if ($p !== '') {
                $d = self::normalizeBonusPhoneDigits($p);

                return $d !== '' ? $d : null;
            }
        }

        return null;
    }

    public static function resolveProductXmlId(BasketItemBase $basketItem): string
    {
        $xmlId = (string)$basketItem->getField('PRODUCT_XML_ID');
        if ($xmlId !== '') {
            return $xmlId;
        }

        $productId = (int)$basketItem->getProductId();
        if ($productId <= 0 || !Loader::includeModule('iblock')) {
            return '';
        }

        $row = ElementTable::getList([
            'select' => ['XML_ID'],
            'filter' => ['=ID' => $productId],
            'limit' => 1,
        ])->fetch();

        return $row && isset($row['XML_ID']) ? (string)$row['XML_ID'] : '';
    }

    /**
     * Поиск ID пользователя по внешнему UUID (поле XML_ID пользователя).
     */
    public static function findUserIdByExternalUuid(string $uuid): ?int
    {
        $uuid = trim($uuid);
        if ($uuid === '') {
            return null;
        }

        $map = self::findUserIdsByExternalUuids([$uuid]);

        return $map[$uuid] ?? null;
    }

    /**
     * Одна выборка: соответствие внешнего UUID → ID пользователя (поле XML_ID).
     *
     * @param list<string> $uuids
     * @return array<string, int> uuid (как в БД) => user id
     */
    public static function findUserIdsByExternalUuids(array $uuids): array
    {
        $normalized = [];
        foreach ($uuids as $u) {
            $s = trim((string)$u);
            if ($s !== '') {
                $normalized[$s] = true;
            }
        }
        $unique = array_keys($normalized);
        if ($unique === []) {
            return [];
        }

        $out = [];
        $res = UserTable::getList([
            'filter' => ['@XML_ID' => $unique],
            'select' => ['ID', 'XML_ID'],
        ]);
        while ($row = $res->fetch()) {
            $key = (string)($row['XML_ID'] ?? '');
            if ($key === '') {
                continue;
            }
            $out[$key] = (int)$row['ID'];
        }

        return $out;
    }

    /**
     * Синхронизация импортированного остатка бонусов Aspro: снимает предыдущие начисления импорта и начисляет новую сумму.
     * Записи импорта помечаются в DETAIL_INFO маркером; срок жизни — DNK_BONUS_IMPORT_ACTIVE_YEARS.
     *
     * @param float $amount Сумма из внешней системы (НачисленоОстаток), неотрицательная.
     */
    public static function replaceDnkImportBonusesForUser(int $userId, float $amount): void
    {
        if ($userId <= 0) {
            return;
        }

        if (!Loader::includeModule('aspro.bonus')) {
            return;
        }

        $amount = max(0.0, $amount);
        self::removeDnkImportAccrualOperations($userId);

        if ($amount <= 0.0) {
            return;
        }

        self::addDnkImportAccrualOperation($userId, $amount);
    }

    private static function removeDnkImportAccrualOperations(int $userId): void
    {
        $typeAdd = BonusHelper::getString(BonusHistoryOperationsEnum::ADD_BY_ORDER);
        $marker = self::DNK_BONUS_IMPORT_DETAIL_MARKER;

        $res = HistoryOperationsTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=TYPE' => $typeAdd,
                '=ACTIVE' => 'Y',
            ],
            'select' => ['ID', 'BALANCE', 'DETAIL_INFO'],
        ]);

        while ($row = $res->fetch()) {
            $detail = (string)($row['DETAIL_INFO'] ?? '');
            if (strpos($detail, $marker) === false) {
                continue;
            }

            $balance = (float)($row['BALANCE'] ?? 0);
            if ($balance > 0) {
                BonusUser::minusBonuses($userId, $balance);
            }

            HistoryOperationsTable::update((int)$row['ID'], ['ACTIVE' => 'N']);
        }
    }

    private static function addDnkImportAccrualOperation(int $userId, float $amount): void
    {
        $years = defined('DNK_BONUS_IMPORT_ACTIVE_YEARS') ? (int)DNK_BONUS_IMPORT_ACTIVE_YEARS : 2;
        if ($years < 1) {
            $years = 2;
        }

        $typeAdd = BonusHelper::getString(BonusHistoryOperationsEnum::ADD_BY_ORDER);
        $userBalance = BonusUser::getBalance($userId);
        $detailInfo = self::DNK_BONUS_IMPORT_DETAIL_MARKER . ' Импорт остатка бонусов';

        $activeTo = Date::createFromTimestamp(strtotime('+' . $years . ' years'));

        $fields = [
            'NAME' => 'Начисление',
            'TYPE' => $typeAdd,
            'USER_ID' => $userId,
            'ORDER_ID' => 0,
            'SUMM_BONUSES' => $amount,
            'BONUSES_BEFORE' => $userBalance,
            'BONUSES_AFTER' => $userBalance + $amount,
            'BALANCE' => $amount,
            'DETAIL_INFO' => $detailInfo,
            'ACTIVE_TO' => $activeTo,
        ];

        $result = HistoryOperationsTable::add($fields);
        if (!$result->isSuccess()) {
            return;
        }

        BonusUser::addBonuses($userId, ['TOTAL_BONUSES' => $amount]);
    }

    /**
     * ID варианта списка (свойство типа L) по коду свойства и XML_ID в инфоблоке.
     */
    public static function getIblockListPropertyEnumIdByXmlId(int $iblockId, string $propertyCode, string $xmlId): ?int
    {
        $xmlId = trim($xmlId);
        if ($iblockId <= 0 || $propertyCode === '' || $xmlId === '') {
            return null;
        }
        if (!\CModule::IncludeModule('iblock')) {
            return null;
        }
        $rsProp = \CIBlockProperty::GetList(
            [],
            [
                'IBLOCK_ID' => $iblockId,
                'CODE' => $propertyCode,
            ]
        );
        $arProp = $rsProp->Fetch();
        if (!$arProp || (string)($arProp['PROPERTY_TYPE'] ?? '') !== 'L') {
            return null;
        }
        $rsEnum = \CIBlockPropertyEnum::GetList(
            ['SORT' => 'ASC'],
            [
                'PROPERTY_ID' => (int)$arProp['ID'],
                'XML_ID' => $xmlId,
            ]
        );
        $arEnum = $rsEnum->Fetch();
        if (!$arEnum) {
            return null;
        }

        return (int)$arEnum['ID'];
    }

    /**
     * ID элемента инфоблока по полю XML_ID.
     */
    public static function getIblockElementIdByXmlId(int $iblockId, string $xmlId): ?int
    {
        $xmlId = trim($xmlId);
        if ($iblockId <= 0 || $xmlId === '') {
            return null;
        }
        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $row = ElementTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=XML_ID' => $xmlId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        if ($row === false) {
            return null;
        }

        $id = (int)($row['ID'] ?? 0);

        return $id > 0 ? $id : null;
    }
}
