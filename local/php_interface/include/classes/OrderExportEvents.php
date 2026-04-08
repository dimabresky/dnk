<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Order;

/**
 * Обработчик сохранения заказа: постановка JSON в очередь экспорта.
 */
final class OrderExportEvents
{
    public static function onSaleOrderSaved(Event $event): void
    {
        if ($event->getParameter('IS_NEW') !== true) {
            return;
        }

        $order = $event->getParameter('ENTITY');
        if (!$order instanceof Order) {
            return;
        }

        if (!Loader::includeModule('sale')) {
            return;
        }

        self::enqueueFromOrder($order);
    }

    /**
     * Сумма списанных бонузов из свойства заказа 
     * @param Order $order Заказ
     * @return float Сумма списанных бонузов
     */
    private static function getOrderBonusPayedAmount(Order $order): float
    {
        foreach ($order->getPropertyCollection() as $prop) {
            $values = $prop->getFields()->getValues();
            if (($values['CODE'] ?? '') !== \Aspro\Bonus\History\Order::PROPERTY_BONUS_PAYMENT) {
                continue;
            }
            $raw = str_replace(',', '.', (string)($values['VALUE'] ?? ''));
            if (!is_numeric($raw)) {
                return 0.0;
            }

            return max(0.0, (float)$raw);
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed> Пустой массив, если модуль aspro.bonus не подключён или списания бонусов не было.
     */
    public static function buildOrderPayloadArray(Order $order): array
    {
        if (!Loader::includeModule('aspro.bonus')) {
            return [];
        }

        $bonusesApplied = self::getOrderBonusPayedAmount($order);
        if ($bonusesApplied <= 0) {
            return [];
        }

        $accountNumber = (string)$order->getField('ACCOUNT_NUMBER');
        if ($accountNumber !== '' && ctype_digit($accountNumber)) {
            $number = (int)$accountNumber;
        } elseif ($accountNumber !== '') {
            $number = $accountNumber;
        } else {
            $number = (int)$order->getId();
        }

        $basket = $order->getBasket();
        $basketItems = $basket ? $basket->getBasketItems() : [];
        $basketOut = [];
        $discounts = [];

        $index = 0;
        foreach ($basketItems as $basketItem) {
            $name = (string)$basketItem->getField('NAME');
            $quantity = (float)$basketItem->getQuantity();
            $currency = (string)$basketItem->getCurrency();

            $basePrice = (float)$basketItem->getBasePrice();
            $finalUnit = (float)$basketItem->getPrice();
            if ($basePrice <= 0 && $finalUnit > 0) {
                $basePrice = $finalUnit;
            }

            $xmlId = Utils::resolveProductXmlId($basketItem);

            $basketOut[] = [
                'name' => $name,
                'xml_id' => $xmlId,
                'currency' => $currency,
                'price' => Utils::roundMoney($basePrice),
                'discountPrice' => Utils::roundMoney($finalUnit),
                'quantity' => Utils::normalizeQuantity($quantity),
            ];

            // $lineDiscount = ($basePrice - $finalUnit) * $quantity;
            // if (Utils::roundMoney($lineDiscount) > 0) {
            //     $discounts[] = [
            //         'productIndex' => $index,
            //         'name' => Utils::resolveDiscountName(),
            //         'value' => Utils::roundMoney($lineDiscount),
            //     ];
            // }

            $index++;
        }

        return [
            'number' => $number,
            'totalCost' => Utils::roundMoney((float)$order->getPrice()),
            'bonusesApplyed' => Utils::roundMoney($bonusesApplied),
            'basket' => $basketOut,
            'discounts' => $discounts,
        ];
    }

    public static function enqueueFromOrder(Order $order): void
    {
        $orderId = (int)$order->getId();
        if ($orderId <= 0) {
            return;
        }

        $payload = self::buildOrderPayloadArray($order);
        if ($payload === []) {
            return;
        }
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        OrderExportQueueTable::add([
            'ORDER_ID' => $orderId,
            'PAYLOAD' => $json,
            'STATUS' => OrderExportQueueTable::STATUS_PENDING,
            'ATTEMPTS' => 0,
            'DATE_INSERT' => new DateTime(),
        ]);
    }
}
