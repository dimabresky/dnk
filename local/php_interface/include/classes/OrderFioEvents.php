<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Sale\Order;
use Bitrix\Sale\PropertyValue;

/**
 * При создании заказа физлица заполняет служебное свойство FIO: фамилия, затем имя.
 */
final class OrderFioEvents
{
    private const INDIVIDUAL_PERSON_TYPE_ID = 1;

    private const CODE_NAME = 'NAME';

    private const CODE_LAST_NAME = 'LAST_NAME';

    private const CODE_FIO = 'FIO';

    public static function onSaleOrderBeforeSaved(Event $event): void
    {
        $order = $event->getParameter('ENTITY');
        if (!$order instanceof Order || !$order->isNew()) {
            return;
        }

        if ((int)$order->getPersonTypeId() !== self::INDIVIDUAL_PERSON_TYPE_ID) {
            return;
        }

        $properties = $order->getPropertyCollection();
        $fioProperty = $properties->getItemByOrderPropertyCode(self::CODE_FIO);
        if (!$fioProperty instanceof PropertyValue) {
            return;
        }

        $parts = array_filter(
            [
                self::propertyText($properties->getItemByOrderPropertyCode(self::CODE_LAST_NAME)),
                self::propertyText($properties->getItemByOrderPropertyCode(self::CODE_NAME)),
            ],
            static fn (string $part): bool => $part !== ''
        );
        if ($parts === []) {
            return;
        }

        $fio = implode(' ', $parts);
        if (self::propertyText($fioProperty) === $fio) {
            return;
        }

        $fioProperty->setValue($fio);
    }

    private static function propertyText(mixed $property): string
    {
        if (!$property instanceof PropertyValue) {
            return '';
        }

        $value = $property->getValue();
        if (is_array($value)) {
            $value = reset($value);
        }

        return trim((string)$value);
    }
}
