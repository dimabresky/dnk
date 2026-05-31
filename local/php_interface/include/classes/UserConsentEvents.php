<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UserTable;

/**
 * Обработчики событий модуля main для журнала согласий Bitrix.
 */
final class UserConsentEvents
{
    /**
     * @param array<int, mixed> $data
     * @return EventResult
     */
    public static function onUserConsentProviderList(array $data): EventResult
    {
        Loc::loadMessages(__FILE__);

        $providers = [
            [
                'CODE' => UserConsentService::ORIGINATOR_REVOKE,
                'NAME' => Loc::getMessage('DNK_UC_PROVIDER_REVOKE'),
                'DATA' => static function ($originId) {
                    return self::buildUserOriginData($originId);
                },
            ],
            [
                'CODE' => UserConsentService::ORIGINATOR_ACCEPT,
                'NAME' => Loc::getMessage('DNK_UC_PROVIDER_ACCEPT'),
                'DATA' => static function ($originId) {
                    return self::buildUserOriginData($originId);
                },
            ],
        ];

        return new EventResult(EventResult::SUCCESS, $providers);
    }

    /**
     * @param string|int|null $originId
     * @return array<string, string|null>|null
     */
    private static function buildUserOriginData($originId): ?array
    {
        $userId = (int)$originId;
        if ($userId <= 0) {
            return null;
        }

        $user = UserTable::getByPrimary($userId, [
            'select' => ['ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME'],
        ])->fetch();

        if (!$user) {
            return null;
        }

        $nameParts = array_filter([
            trim((string)($user['LAST_NAME'] ?? '')),
            trim((string)($user['NAME'] ?? '')),
        ]);
        $name = $nameParts !== [] ? implode(' ', $nameParts) : (string)($user['LOGIN'] ?? $userId);

        return [
            'NAME' => $name,
            'URL' => null,
        ];
    }
}
