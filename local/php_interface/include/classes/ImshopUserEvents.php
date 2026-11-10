<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Дополняет профиль IMSHOP номером карты и уровнем из пользовательских полей.
 */
final class ImshopUserEvents
{
    public static function onUserProfileBuild(Event $event): ?EventResult
    {
        $user = $event->getParameter('USER');
        if (!is_array($user)) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', (string) ($user['phone'] ?? '')) ?: '';
        if ($phone !== '') {
            $user['cardNumber'] = $phone;
        }

        $loyalty = Utils::getUserUfLoyaltyDisplayData((int) $event->getParameter('USER_ID'));
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
            $user['loyaltyProgram'] = $program;
        }

        return new EventResult(EventResult::SUCCESS, [
            'USER' => $user,
        ]);
    }
}
