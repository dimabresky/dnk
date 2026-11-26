<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Grid\Declension;

/**
 * Дополняет профиль IMSHOP номером карты, уровнем и согласием на обработку данных.
 */
final class ImshopUserEvents
{
    /** Сумма покупок для перехода: уровень 1 → 2 и 2 → 3. */
    private const LOYALTY_LEVEL_THRESHOLDS = [
        1 => 300.0,
        2 => 800.0,
    ];

    public static function onUserProfileBuild(Event $event): ?EventResult
    {
        $user = $event->getParameter('USER');
        if (!is_array($user)) {
            return null;
        }

        $userId = (int) $event->getParameter('USER_ID');
        $phone = preg_replace('/\D+/', '', (string) ($user['phone'] ?? '')) ?: '';
        if ($phone !== '') {
            $user['cardNumber'] = $phone;
        }

        $keptProgram = $user['loyaltyProgram'] ?? null;
        unset($user['loyaltyProgram']);
        $user['units'] = (new Declension('бонус', 'бонуса', 'бонусов'))
            ->get((int) ($user['bonuses'] ?? 0));

        $loyalty = Utils::getUserUfLoyaltyDisplayData($userId);
        if (is_array($loyalty)) {
            $program = [
                'currentLevelTitle' => (string) $loyalty['name'],
            ];
            $nextName = trim((string) ($loyalty['next_name'] ?? ''));
            $nextCost = $loyalty['next_level_cost'] ?? null;
            if ($nextName !== '' && is_numeric($nextCost) && (float) $nextCost > 0.0) {
                $program['nextLevelDescription'] = 'Ещё '
                    . number_format(Utils::roundMoney((float) $nextCost), 2, ',', ' ')
                    . ' BYN покупок до '
                    . $nextName;
            }
            $threshold = self::LOYALTY_LEVEL_THRESHOLDS[(int) $loyalty['level']] ?? null;
            if ($threshold !== null && is_numeric($nextCost) && (float) $nextCost > 0.0) {
                $program['progress'] = self::loyaltyProgress((float) $nextCost, $threshold);
            }
            $benefits = Utils::getLoyaltyBenefitLines((int) $loyalty['level']);
            if ($benefits !== []) {
                $program['benefits'] = $benefits;
            }
            $user['loyaltyProgram'] = $program;
        } elseif (is_array($keptProgram)) {
            $user['loyaltyProgram'] = $keptProgram;
        }

        $user['agreements'] = [
            [
                'id' => '1',
                'title' => 'Согласие на обработку персональных данных',
                'purpose' => 'Стандартное соглашение на обработку персональных данных',
                'dataUsed' => [],
                'duration' => '',
                'requiredFields' => [],
                'checked' => UserConsentService::hasActiveConsent($userId, 1),
                'dependencies' => [],
                'fullTextUrl' => 'https://dnk.by/info/politika-konfidentsialnosti/',
                'needReconsent' => false,
            ],
        ];

        return new EventResult(EventResult::SUCCESS, [
            'USER' => $user,
        ]);
    }

    /**
     * Процент уже пройденного порога. Целое, если десятых нет.
     */
    private static function loyaltyProgress(float $remaining, float $threshold): int|float
    {
        if ($threshold <= 0.0) {
            return 0;
        }

        $percent = ($threshold - $remaining) / $threshold * 100;
        $percent = max(0.0, min(100.0, $percent));
        $text = number_format(round($percent, 1), 1, '.', '');
        if (str_ends_with($text, '.0')) {
            return (int) $text;
        }

        return (float) $text;
    }
}
