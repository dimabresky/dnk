<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Controller\PhoneAuth as PhoneAuthController;
use Bitrix\Main\Loader;
use Bitrix\Main\Sms\Event as SmsEvent;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;
use CUser;

/**
 * SMS-авторизация и регистрация по телефону для оформления заявки на сертификаты.
 */
final class CertificateBuyPhoneAuth
{
    public const SCENARIO_LOGIN = 'login';
    public const SCENARIO_REGISTER = 'register';

    private const SMS_LOGIN = 'SMS_USER_AUTH_CODE';
    private const SMS_REGISTER = 'SMS_USER_CONFIRM_NUMBER';

    public static function isEnabled(): bool
    {
        if (!Loader::includeModule('messageservice')) {
            return false;
        }

        if (Option::get('main', 'new_user_phone_auth', 'N') !== 'Y') {
            return false;
        }

        if (class_exists(\Aspro\Premier\PhoneAuth::class)) {
            [, , , $use] = \Aspro\Premier\PhoneAuth::getOptions();

            return (bool)$use;
        }

        return true;
    }

    public static function normalizePhone(string $phone): string
    {
        return UserPhoneAuthTable::normalizePhoneNumber($phone);
    }

    /**
     * @return array{ok: bool, userId?: int, error?: string, ambiguous?: bool}
     */
    public static function resolveUserIdByPhone(string $phone): array
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === '') {
            return ['ok' => false, 'error' => 'invalid_phone'];
        }

        $row = UserPhoneAuthTable::getList([
            'select' => ['USER_ID'],
            'filter' => ['=PHONE_NUMBER' => $normalized],
            'limit' => 1,
        ])->fetch();

        if (is_array($row) && (int)($row['USER_ID'] ?? 0) > 0) {
            return ['ok' => true, 'userId' => (int)$row['USER_ID']];
        }

        $digits = Utils::normalizeBonusPhoneDigits($phone);
        if ($digits === '') {
            return ['ok' => true, 'userId' => null];
        }

        $userIds = self::findUserIdsByProfilePhoneDigits($digits);
        if (count($userIds) > 1) {
            return ['ok' => false, 'ambiguous' => true, 'error' => 'ambiguous_phone'];
        }

        if (count($userIds) === 1) {
            $userId = $userIds[0];
            self::ensurePhoneAuthRow($userId, $normalized);

            return ['ok' => true, 'userId' => $userId];
        }

        return ['ok' => true, 'userId' => null];
    }

    public static function ensurePhoneAuthRow(int $userId, string $normalizedPhone): void
    {
        if ($userId <= 0 || $normalizedPhone === '') {
            return;
        }

        if (UserPhoneAuthTable::getByPrimary($userId)->fetch()) {
            return;
        }

        UserPhoneAuthTable::add([
            'USER_ID' => $userId,
            'PHONE_NUMBER' => $normalizedPhone,
        ]);
    }

    /**
     * @return array{
     *     ok: bool,
     *     signedData?: string,
     *     resendInterval?: int,
     *     phoneMasked?: string,
     *     error?: string,
     *     alreadySent?: bool
     * }
     */
    public static function sendLoginCode(int $userId): array
    {
        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'user_not_found'];
        }

        $row = UserPhoneAuthTable::getRowById($userId);
        if (!$row || (string)($row['PHONE_NUMBER'] ?? '') === '') {
            return ['ok' => false, 'error' => 'phone_not_configured'];
        }

        $phoneNumber = (string)$row['PHONE_NUMBER'];
        $resendInterval = (int)\CUser::PHONE_CODE_RESEND_INTERVAL;
        $bGenerate = true;

        if ($row['DATE_SENT'] instanceof DateTime) {
            $now = new DateTime();
            if ($row['DATE_SENT']->getTimestamp() + $resendInterval > $now->getTimestamp()) {
                $bGenerate = false;
            }
        }

        $smsCode = '';
        if ($bGenerate) {
            $generated = \CUser::GeneratePhoneCode($userId);
            if (!is_array($generated) || count($generated) < 2) {
                return ['ok' => false, 'error' => 'code_generate_failed'];
            }
            [$smsCode, $phoneNumber] = $generated;
        }

        if ($bGenerate) {
            $sms = new SmsEvent(self::SMS_LOGIN, [
                'USER_PHONE' => $phoneNumber,
                'CODE' => $smsCode,
            ]);
            $siteId = defined('SITE_ID') && is_string(SITE_ID) && SITE_ID !== '' ? SITE_ID : 's1';
            $sms->setSite($siteId);
            $smsResult = $sms->send(true);

            if (!$smsResult->isSuccess()) {
                $messages = [];
                foreach ($smsResult->getErrorMessages() as $message) {
                    $messages[] = (string)$message;
                }

                return [
                    'ok' => false,
                    'error' => 'sms_send_failed',
                    'smsErrors' => $messages,
                ];
            }
        }

        return [
            'ok' => true,
            'signedData' => PhoneAuthController::signData([
                'phoneNumber' => $phoneNumber,
                'smsTemplate' => self::SMS_LOGIN,
            ]),
            'resendInterval' => $resendInterval,
            'phoneMasked' => self::maskPhone($phoneNumber),
            'alreadySent' => !$bGenerate,
        ];
    }

    /**
     * @return array{
     *     ok: bool,
     *     signedData?: string,
     *     userId?: int,
     *     resendInterval?: int,
     *     phoneMasked?: string,
     *     error?: string,
     *     registerMessage?: string
     * }
     */
    public static function registerAndSendCode(string $phone, string $name): array
    {
        $normalized = self::normalizePhone($phone);
        if ($normalized === '') {
            return ['ok' => false, 'error' => 'invalid_phone'];
        }

        $existing = self::resolveUserIdByPhone($phone);
        if (!($existing['ok'] ?? false)) {
            return ['ok' => false, 'error' => (string)($existing['error'] ?? 'lookup_failed')];
        }
        if (!empty($existing['userId'])) {
            return self::sendLoginCode((int)$existing['userId']);
        }

        $digits = preg_replace('/\D+/', '', $normalized) ?: '';
        if (mb_strlen($digits) < 9) {
            return ['ok' => false, 'error' => 'invalid_phone'];
        }

        [$firstName, $lastName] = self::splitContactName($name);
        $password = \CUser::GeneratePasswordByPolicy([]);

        $siteId = defined('SITE_ID') && is_string(SITE_ID) && SITE_ID !== '' ? SITE_ID : 's1';

        $user = new CUser();
        $registerResult = $user->Register(
            $digits,
            $firstName,
            $lastName,
            $password,
            $password,
            '',
            $siteId,
            '',
            0,
            true,
            $normalized
        );

        if (!is_array($registerResult)) {
            return ['ok' => false, 'error' => 'register_failed'];
        }

        if (($registerResult['TYPE'] ?? '') !== 'OK') {
            $message = trim(strip_tags((string)($registerResult['MESSAGE'] ?? '')));

            return [
                'ok' => false,
                'error' => 'register_failed',
                'registerMessage' => $message,
            ];
        }

        $userId = (int)($registerResult['ID'] ?? 0);
        $signedData = (string)($registerResult['SIGNED_DATA'] ?? '');
        if ($signedData === '' && $userId > 0) {
            $signedData = PhoneAuthController::signData(['phoneNumber' => $normalized]);
        }

        return [
            'ok' => true,
            'signedData' => $signedData,
            'userId' => $userId,
            'resendInterval' => (int)\CUser::PHONE_CODE_RESEND_INTERVAL,
            'phoneMasked' => self::maskPhone($normalized),
        ];
    }

    /**
     * @return array{ok: bool, userId?: int, error?: string}
     */
    public static function verifyAndAuthorize(string $phone, string $code, string $scenario): array
    {
        global $USER;

        $normalized = self::normalizePhone($phone);
        $smsCode = trim($code);

        if ($normalized === '' || $smsCode === '') {
            return ['ok' => false, 'error' => 'invalid_params'];
        }

        $userId = \CUser::VerifyPhoneCode($normalized, $smsCode);
        if (!$userId) {
            return ['ok' => false, 'error' => 'invalid_code'];
        }

        $userRow = UserTable::getRow([
            'select' => ['ID', 'ACTIVE'],
            'filter' => ['=ID' => $userId],
        ]);

        if (!is_array($userRow)) {
            return ['ok' => false, 'error' => 'user_not_found'];
        }

        if ((string)($userRow['ACTIVE'] ?? 'N') !== 'Y') {
            $cUser = new CUser();
            $cUser->Update($userId, ['ACTIVE' => 'Y']);
        }

        if (!is_object($USER)) {
            return ['ok' => false, 'error' => 'auth_unavailable'];
        }

        $USER->Authorize($userId, true);

        return ['ok' => true, 'userId' => (int)$userId];
    }

    public static function maskPhone(string $normalizedPhone): string
    {
        $digits = preg_replace('/\D+/', '', $normalizedPhone) ?: '';
        if (mb_strlen($digits) < 4) {
            return $normalizedPhone;
        }

        $tail = mb_substr($digits, -2);

        return preg_replace('/\d{2}$/', '**-' . $tail, $normalizedPhone, 1) ?? $normalizedPhone;
    }

    /**
     * @return list<int>
     */
    private static function findUserIdsByProfilePhoneDigits(string $digits): array
    {
        if ($digits === '') {
            return [];
        }

        $candidates = [$digits, '+' . $digits];
        try {
            $normalized = UserPhoneAuthTable::normalizePhoneNumber('+' . $digits);
            if ($normalized !== '') {
                $candidates[] = $normalized;
            }
        } catch (\Throwable) {
        }

        $candidates = array_values(array_unique($candidates));
        $filter = ['LOGIC' => 'OR'];
        foreach ($candidates as $candidate) {
            $filter[] = ['=PERSONAL_PHONE' => $candidate];
            $filter[] = ['=PERSONAL_MOBILE' => $candidate];
            $filter[] = ['=WORK_PHONE' => $candidate];
        }

        $matched = [];
        $res = UserTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'],
        ]);

        while ($row = $res->fetch()) {
            $userId = (int)($row['ID'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            foreach (['PERSONAL_PHONE', 'PERSONAL_MOBILE', 'WORK_PHONE'] as $field) {
                $fieldDigits = Utils::normalizeBonusPhoneDigits((string)($row[$field] ?? ''));
                if ($fieldDigits === $digits) {
                    $matched[$userId] = true;
                }
            }
        }

        return array_keys($matched);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function splitContactName(string $name): array
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            return ['', ''];
        }

        $parts = explode(' ', $name);
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $lastName = array_shift($parts);

        return [implode(' ', $parts), $lastName];
    }
}
