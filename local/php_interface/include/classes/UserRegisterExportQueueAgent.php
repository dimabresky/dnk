<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;

/**
 * Агент: POST данных пользователя после регистрации на DNK_USER_REGISTER_EXPORT_ENDPOINT.
 * Зарегистрировать в админке: агенты — PHP-строка:
 * \Dnk\PhpInterface\UserRegisterExportQueueAgent::runUserRegisterExportQueueAgent();
 * Интервал — DNK_USER_REGISTER_EXPORT_AGENT_INTERVAL (сек), периодический.
 */
final class UserRegisterExportQueueAgent
{
    public static function runUserRegisterExportQueueAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\UserRegisterExportQueueAgent::runUserRegisterExportQueueAgent();";

        $endpoint = defined('DNK_USER_REGISTER_EXPORT_ENDPOINT') ? trim((string)DNK_USER_REGISTER_EXPORT_ENDPOINT) : '';
        if ($endpoint === '') {
            return $return;
        }

        $batch = defined('DNK_USER_REGISTER_EXPORT_QUEUE_BATCH') ? (int)DNK_USER_REGISTER_EXPORT_QUEUE_BATCH : 10;
        if ($batch < 1) {
            $batch = 10;
        }
        $maxAttempts = defined('DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS') ? (int)DNK_USER_REGISTER_EXPORT_MAX_ATTEMPTS : 5;
        if ($maxAttempts < 1) {
            $maxAttempts = 5;
        }

        $result = UserRegisterExportQueueTable::getList([
            'select' => ['ID', 'USER_ID', 'ATTEMPTS'],
            'filter' => ['=STATUS' => UserRegisterExportQueueTable::STATUS_PENDING],
            'order' => ['ID' => 'ASC'],
            'limit' => $batch,
        ]);

        while ($row = $result->fetch()) {
            $id = (int)$row['ID'];
            $userId = (int)$row['USER_ID'];
            $attempts = (int)$row['ATTEMPTS'];

            $errorDetail = '';
            if (Utils::tryPostUserRegisterExportAndUpdateXmlId($userId, $errorDetail)) {
                UserRegisterExportQueueTable::delete($id);

                continue;
            }

            $attempts++;
            UserRegisterExportQueueTable::update($id, [
                'STATUS' => $attempts >= $maxAttempts
                    ? UserRegisterExportQueueTable::STATUS_ERROR
                    : UserRegisterExportQueueTable::STATUS_PENDING,
                'ATTEMPTS' => $attempts,
                'LAST_ERROR' => $errorDetail !== '' ? $errorDetail : 'unknown',
                'DATE_UPDATE' => new DateTime(),
            ]);
        }

        return $return;
    }
}
