<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;

/**
 * Агент: обработка очереди запроса бонусного баланса по телефону.
 * Зарегистрировать в админке: агенты — PHP-строка:
 * \Dnk\PhpInterface\BonusBalanceQueueAgent::runBonusBalanceQueueAgent();
 * Интервал — DNK_BONUS_BALANCE_AGENT_INTERVAL (сек), периодический.
 */
final class BonusBalanceQueueAgent
{
    public static function runBonusBalanceQueueAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\BonusBalanceQueueAgent::runBonusBalanceQueueAgent();";

        $endpoint = defined('DNK_BONUS_ENDPOINT') ? trim((string)DNK_BONUS_ENDPOINT) : '';
        if ($endpoint === '') {
            return $return;
        }

        $batch = defined('DNK_BONUS_BALANCE_QUEUE_BATCH') ? (int)DNK_BONUS_BALANCE_QUEUE_BATCH : 10;
        if ($batch < 1) {
            $batch = 10;
        }
        $maxAttempts = defined('DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS') ? (int)DNK_BONUS_BALANCE_QUEUE_MAX_ATTEMPTS : 5;
        if ($maxAttempts < 1) {
            $maxAttempts = 5;
        }

        $result = BonusBalanceQueueTable::getList([
            'select' => ['ID', 'USER_ID', 'ATTEMPTS'],
            'filter' => ['=STATUS' => BonusBalanceQueueTable::STATUS_PENDING],
            'order' => ['ID' => 'ASC'],
            'limit' => $batch,
        ]);

        while ($row = $result->fetch()) {
            $id = (int)$row['ID'];
            $userId = (int)$row['USER_ID'];
            $attempts = (int)$row['ATTEMPTS'];

            $errorDetail = '';
            if (Utils::trySyncDnkImportBonusesForUserByPhone($userId, $errorDetail)) {
                BonusBalanceQueueTable::delete($id);

                continue;
            }

            $attempts++;
            BonusBalanceQueueTable::update($id, [
                'STATUS' => $attempts >= $maxAttempts
                    ? BonusBalanceQueueTable::STATUS_ERROR
                    : BonusBalanceQueueTable::STATUS_PENDING,
                'ATTEMPTS' => $attempts,
                'LAST_ERROR' => $errorDetail !== '' ? $errorDetail : 'unknown',
                'DATE_UPDATE' => new DateTime(),
            ]);
        }

        return $return;
    }
}
