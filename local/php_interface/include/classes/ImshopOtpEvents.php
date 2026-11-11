<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Добавляет согласия SMS и Email в ответ регистрации IMSHOP.
 */
final class ImshopOtpEvents
{
    public static function onOtpRegistrationDataBuild(Event $event): ?EventResult
    {
        $response = $event->getParameter('RESPONSE');
        if (!is_array($response) || !is_array($response['dataRequired'] ?? null)) {
            return null;
        }

        foreach (['allowSms', 'allowEmail'] as $field) {
            if (!in_array($field, $response['dataRequired'], true)) {
                $response['dataRequired'][] = $field;
            }
        }

        $response['availableNotifications'] = [
            [
                'id' => 'email',
                'title' => 'Я согласен получать Email рассылку [см. текст](https://dnk.by/info/politika-konfidentsialnosti/)',
            ],
            [
                'id' => 'sms',
                'title' => 'Я согласен получать SMS [см. текст](https://dnk.by/info/politika-konfidentsialnosti/)',
            ],
        ];

        return new EventResult(EventResult::SUCCESS, [
            'RESPONSE' => $response,
        ]);
    }
}
