<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Grid\Declension;
use Bitrix\Main\Loader;

/**
 * Дополняет профиль IMSHOP номером карты, уровнем и согласием на обработку данных.
 */
final class ImshopUserEvents
{
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
            $benefits = self::loyaltyBenefits((int) $loyalty['level']);
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
     * Строки DETAIL_TEXT элементов инфоблока бенефитов с этим уровнем.
     *
     * @return list<string>
     */
    private static function loyaltyBenefits(int $level): array
    {
        if (
            $level <= 0
            || !defined('DNK_BENEFITS_IBLOCK_ID')
            || (int) DNK_BENEFITS_IBLOCK_ID <= 0
            || !Loader::includeModule('iblock')
        ) {
            return [];
        }

        $lines = [];
        $res = \CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => (int) DNK_BENEFITS_IBLOCK_ID,
                'ACTIVE' => 'Y',
                'PROPERTY_LEVEL' => $level,
            ],
            false,
            false,
            ['ID', 'DETAIL_TEXT']
        );
        while ($row = $res->Fetch()) {
            $text = (string) ($row['DETAIL_TEXT'] ?? '');
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $lines[] = $line;
                }
            }
        }

        return $lines;
    }
}
