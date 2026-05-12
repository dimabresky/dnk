<?php

namespace Dnk\PhpInterface;

use Aspro\Bonus\Enums\HistoryOperations as BonusHistoryOperationsEnum;
use Aspro\Bonus\Helper as BonusHelper;
use Bitrix\Main\Event;
use Bitrix\Main\SystemException;

final class BonusAccrualEvents
{
    public static function onBeforeCreateAddByOrder(Event $event): void
    {
        $fields = (array)$event->getParameter('FIELDS');
        $orderParams = (array)$event->getParameter('ORDER_PARAMS');

        $orderId = (int)($orderParams['ORDER_ID'] ?? $fields['ORDER_ID'] ?? 0);
        if ($orderId <= 0) {
            return;
        }

        $type = (string)($orderParams['TYPE'] ?? $fields['TYPE'] ?? '');
        if ($type !== BonusHelper::getString(BonusHistoryOperationsEnum::ADD_BY_ORDER)) {
            return;
        }

        throw new SystemException('Order bonus accrual is disabled for order ' . $orderId);
    }
}
