<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\UserPhoneAuthTable;
use Bitrix\Main\UserTable;

/**
 * После добавления или обновления пользователя: если нет телефона для авторизации, подставить из WORK_PHONE.
 * После успешной авторизации или регистрации — постановка в очередь бонусов (Utils::enqueueBonusBalanceSyncIfNotPending, агент BonusBalanceQueueAgent).
 * После регистрации — очередь POST профиля на внешний endpoint (Utils::enqueueUserRegisterExportIfNotPending, агент UserRegisterExportQueueAgent).
 */
final class UserAddEvents
{
    /**
     * @param array<string, mixed> $arFields
     */
    public static function onAfterUserAdd(array &$arFields): void
    {
        self::ensurePhoneAuthFromWorkPhone((int)($arFields['ID'] ?? 0));
    }

    /**
     * @param array<string, mixed> $arFields
     */
    public static function onAfterUserUpdate(array &$arFields): void
    {
        if (empty($arFields['RESULT'])) {
            return;
        }
        self::ensurePhoneAuthFromWorkPhone((int)($arFields['ID'] ?? 0));
    }

    /**
     * @param array<string, mixed> $arUser
     */
    public static function onAfterUserAuthorize(array &$arUser): void
    {
        self::enqueueBonusBalanceSyncIfPossible((int)($arUser['ID'] ?? $arUser['USER_ID'] ?? 0));
    }

    /**
     * @param array<string, mixed> $arFields
     */
    public static function onAfterUserRegister(array &$arFields): void
    {
        $userId = (int)($arFields['USER_ID'] ?? $arFields['ID'] ?? 0);
        self::enqueueBonusBalanceSyncIfPossible($userId);
        if ($userId > 0) {
            Utils::enqueueUserRegisterExportIfNotPending($userId);
        }
    }

    private static function enqueueBonusBalanceSyncIfPossible(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        Utils::enqueueBonusBalanceSyncIfNotPending($userId);
    }

    private static function ensurePhoneAuthFromWorkPhone(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        if (UserPhoneAuthTable::getByPrimary($userId)->fetch()) {
            return;
        }

        $row = UserTable::getList([
            'select' => ['WORK_PHONE'],
            'filter' => ['=ID' => $userId],
            'limit' => 1,
        ])->fetch();

        if ($row === false) {
            return;
        }

        $workPhone = trim((string)($row['WORK_PHONE']));
        if ($workPhone === '') {
            return;
        }

        UserPhoneAuthTable::add([
            'USER_ID' => $userId,
            'PHONE_NUMBER' => $workPhone,
        ]);
    }
}
