<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
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
     * После записи согласия в b_user_consent — снять отзыв, если это повторное принятие.
     */
    public static function onConsentAfterAdd(Event $event): void
    {
        $fields = $event->getParameter('fields');
        if (!is_array($fields)) {
            return;
        }

        $originatorId = (string)($fields['ORIGINATOR_ID'] ?? '');
        if (in_array($originatorId, [UserConsentService::ORIGINATOR_REVOKE, UserConsentService::ORIGINATOR_ACCEPT], true)) {
            return;
        }

        $userId = (int)($fields['USER_ID'] ?? 0);
        $agreementId = (int)($fields['AGREEMENT_ID'] ?? 0);
        if ($userId <= 0 || $agreementId <= 0) {
            return;
        }

        UserConsentService::clearRevokeIfReconsented($userId, $agreementId);
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
