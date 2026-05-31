<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserConsent\Agreement;
use Bitrix\Main\UserConsent\Consent;
use Bitrix\Main\UserConsent\Internals\ConsentTable;
use CSubscription;

/**
 * Активное согласие: есть запись в b_user_consent и нет активного отзыва в b_dnk_user_consent_revoke.
 */
final class UserConsentService
{
    public const ORIGINATOR_REVOKE = 'dnk/revoke';
    public const ORIGINATOR_ACCEPT = 'dnk/accept';

    /** Коды опций темы Aspro → соглашения для страницы «Мои согласия». */
    private const MANAGEABLE_THEME_OPTIONS = [
        'AGREEMENT_REGISTRATION' => 'registration',
        'AGREEMENT_SUBSCRIBE' => 'subscribe',
        'AGREEMENT_PUBLIC_OFFER' => 'public_offer',
        'AGREEMENT_THIRD_PARTIES' => 'third_parties',
        'AGREEMENT_REVIEW' => 'review',
    ];

    /**
     * Есть ли у пользователя действующее (не отозванное) согласие на соглашение.
     */
    public static function hasActiveConsent(int $userId, int $agreementId): bool
    {
        if ($userId <= 0 || $agreementId <= 0) {
            return false;
        }

        if (!self::hasConsentRecord($userId, $agreementId)) {
            return false;
        }

        return !self::isRevoked($userId, $agreementId);
    }

    /**
     * ID соглашений, чекбоксы которых можно скрыть для пользователя.
     *
     * @return int[]
     */
    public static function getHiddenAgreementIdsForUser(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $ids = [];
        $rows = ConsentTable::getList([
            'filter' => ['=USER_ID' => $userId],
            'select' => ['AGREEMENT_ID'],
            'group' => ['AGREEMENT_ID'],
        ]);

        while ($row = $rows->fetch()) {
            $agreementId = (int)($row['AGREEMENT_ID'] ?? 0);
            if ($agreementId > 0 && self::hasActiveConsent($userId, $agreementId)) {
                $ids[] = $agreementId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Отзыв согласия: запись в реестре отзывов + аудит в b_user_consent.
     */
    public static function revoke(int $userId, int $agreementId): bool
    {
        if ($userId <= 0 || $agreementId <= 0) {
            return false;
        }

        if (!self::hasConsentRecord($userId, $agreementId)) {
            return false;
        }

        if (self::isRevoked($userId, $agreementId)) {
            return true;
        }

        $addResult = UserConsentRevokeTable::add([
            'USER_ID' => $userId,
            'AGREEMENT_ID' => $agreementId,
            'DATE_REVOKE' => new DateTime(),
        ]);

        if (!$addResult->isSuccess()) {
            return false;
        }

        Consent::addByContext(
            $agreementId,
            self::ORIGINATOR_REVOKE,
            (string)$userId,
            ['USER_ID' => $userId]
        );

        self::afterRevokeByOption($userId, $agreementId);

        return true;
    }

    /**
     * Снять отзыв, если после него зафиксировано новое согласие в b_user_consent.
     */
    public static function clearRevokeIfReconsented(int $userId, int $agreementId): bool
    {
        if ($userId <= 0 || $agreementId <= 0) {
            return false;
        }

        $revokeRow = UserConsentRevokeTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=AGREEMENT_ID' => $agreementId,
            ],
            'select' => ['ID', 'DATE_REVOKE'],
            'limit' => 1,
        ])->fetch();

        if (!$revokeRow) {
            return true;
        }

        $latestConsent = ConsentTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=AGREEMENT_ID' => $agreementId,
            ],
            'select' => ['ID', 'DATE_INSERT'],
            'order' => ['DATE_INSERT' => 'DESC'],
            'limit' => 1,
        ])->fetch();

        if (
            !$latestConsent
            || $latestConsent['DATE_INSERT'] <= $revokeRow['DATE_REVOKE']
        ) {
            return false;
        }

        return UserConsentRevokeTable::delete((int)$revokeRow['ID'])->isSuccess();
    }

    /**
     * Снять отзыв после повторного принятия согласия (AJAX restore).
     */
    public static function restoreAfterAccept(int $userId, int $agreementId): bool
    {
        if ($userId <= 0 || $agreementId <= 0) {
            return false;
        }

        $hadRevoke = self::isRevoked($userId, $agreementId);
        if (!$hadRevoke) {
            return true;
        }

        if (!self::clearRevokeIfReconsented($userId, $agreementId)) {
            return false;
        }

        Consent::addByContext(
            $agreementId,
            self::ORIGINATOR_ACCEPT,
            (string)$userId,
            ['USER_ID' => $userId]
        );

        return true;
    }

    /**
     * Список соглашений для ЛК с признаком активного согласия.
     *
     * @return array<int, array{id: int, name: string, active: bool, option_code: string}>
     */
    public static function getManageableAgreements(int $userId): array
    {
        $result = [];
        $seenIds = [];

        foreach (self::MANAGEABLE_THEME_OPTIONS as $optionCode => $type) {
            $agreementId = self::resolveAgreementIdByOption($optionCode);
            if ($agreementId === null || isset($seenIds[$agreementId])) {
                continue;
            }

            $seenIds[$agreementId] = true;
            $agreement = new Agreement($agreementId);
            if (!$agreement->isExist() || !$agreement->isActive()) {
                continue;
            }

            if (!self::hasConsentRecord($userId, $agreementId)) {
                continue;
            }

            $result[] = [
                'id' => $agreementId,
                'name' => (string)($agreement->getData()['NAME'] ?? $optionCode),
                'active' => self::hasActiveConsent($userId, $agreementId),
                'option_code' => $optionCode,
                'type' => $type,
            ];
        }

        return $result;
    }

    public static function resolveAgreementIdByOption(string $optionCode): ?int
    {
        if ($optionCode === '') {
            return null;
        }

        if (class_exists(\TSolution::class)) {
            if (in_array($optionCode, ['AGREEMENT_REGISTRATION', 'AGREEMENT_SUBSCRIBE'], true)) {
                $id = (int)\TSolution::getAgreementIdByOption($optionCode);

                return $id > 0 ? $id : null;
            }

            $value = (int)\TSolution::getFrontParametrValue($optionCode);

            return $value > 0 ? $value : null;
        }

        return null;
    }

    private static function hasConsentRecord(int $userId, int $agreementId): bool
    {
        $row = ConsentTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=AGREEMENT_ID' => $agreementId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return (bool)$row;
    }

    private static function isRevoked(int $userId, int $agreementId): bool
    {
        $row = UserConsentRevokeTable::getList([
            'filter' => [
                '=USER_ID' => $userId,
                '=AGREEMENT_ID' => $agreementId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ])->fetch();

        return (bool)$row;
    }

    private static function afterRevokeByOption(int $userId, int $agreementId): void
    {
        $subscribeId = self::resolveAgreementIdByOption('AGREEMENT_SUBSCRIBE');
        if ($subscribeId !== null && $subscribeId === $agreementId) {
            self::unsubscribeUser($userId);
        }
    }

    private static function unsubscribeUser(int $userId): void
    {
        if (!Loader::includeModule('subscribe')) {
            return;
        }

        $rs = CSubscription::GetList([], ['USER_ID' => $userId]);
        while ($subscription = $rs->Fetch()) {
            CSubscription::Delete((int)$subscription['ID']);
        }
    }
}
