<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;

/**
 * Агент крона: выборка очереди и отправка JSON на endpoint.
 */
final class OrderExportQueueAgent
{
    public static function runQueueAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\OrderExportQueueAgent::runQueueAgent();";

        $endpoint = defined('DNK_ORDER_EXPORT_ENDPOINT') ? (string)DNK_ORDER_EXPORT_ENDPOINT : '';
        if ($endpoint === '') {
            return $return;
        }

        $batch = defined('DNK_ORDER_EXPORT_QUEUE_BATCH') ? (int)DNK_ORDER_EXPORT_QUEUE_BATCH : 10;
        if ($batch < 1) {
            $batch = 10;
        }
        $maxAttempts = defined('DNK_ORDER_EXPORT_MAX_ATTEMPTS') ? (int)DNK_ORDER_EXPORT_MAX_ATTEMPTS : 5;
        if ($maxAttempts < 1) {
            $maxAttempts = 5;
        }

        $result = OrderExportQueueTable::getList([
            'select' => ['ID', 'PAYLOAD', 'ATTEMPTS'],
            'filter' => ['=STATUS' => OrderExportQueueTable::STATUS_PENDING],
            'order' => ['ID' => 'ASC'],
            'limit' => $batch,
        ]);

        while ($row = $result->fetch()) {
            $id = (int)$row['ID'];
            $payload = (string)$row['PAYLOAD'];
            $attempts = (int)$row['ATTEMPTS'];

            $sendResult = self::sendPayload($endpoint, $payload);
            if ($sendResult['ok']) {
                OrderExportQueueTable::delete($id);
                continue;
            }

            $attempts++;
            OrderExportQueueTable::update($id, [
                'STATUS' => $attempts >= $maxAttempts
                    ? OrderExportQueueTable::STATUS_ERROR
                    : OrderExportQueueTable::STATUS_PENDING,
                'ATTEMPTS' => $attempts,
                'LAST_ERROR' => $sendResult['error'],
                'DATE_UPDATE' => new DateTime(),
            ]);
        }
        return $return;
    }

    /**
     * @return array{ok: bool, error: string}
     */
    private static function sendPayload(string $url, string $body): array
    {
        $http = new HttpClient([
            'socketTimeout' => 15,
            'streamTimeout' => 15,
        ]);
        $http->setHeader('Content-Type', 'application/json; charset=UTF-8');

        if (defined('DNK_ORDER_EXPORT_LOGIN') && defined('DNK_ORDER_EXPORT_PASSWORD')) {
            $http->setAuthorization((string)DNK_ORDER_EXPORT_LOGIN, (string)DNK_ORDER_EXPORT_PASSWORD);
        }

        try {
            $response = $http->post($url, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $status = $http->getStatus();
        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'error' => ''];
        }

        $err = 'HTTP ' . $status;
        if (is_string($response) && $response !== '') {
            $err .= ': ' . mb_substr($response, 0, 500);
        }

        return ['ok' => false, 'error' => $err];
    }
}
