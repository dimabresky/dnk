<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Sale\BasketItem;
use Bitrix\Sale\Order;
use Bitrix\Sale\PriceMaths;

/**
 * Подарок, который расчёт уже оценил в 0.01, остаётся обычной позицией корзины.
 * Повторная нулевая строка того же подарка из ответа убирается.
 */
final class ImshopBasketEvents
{
    public static function onBasketCalculated(Event $event): ?EventResult
    {
        $order = $event->getParameter('ORDER');
        $gifts = $event->getParameter('GIFTS');
        if (!$order instanceof Order || !is_array($gifts)) {
            return null;
        }

        $lines = $gifts['lines'] ?? null;
        $groups = $gifts['groups'] ?? null;
        if (!is_array($lines) || !is_array($groups) || $lines === []) {
            return null;
        }

        $giftIds = [];
        foreach ($lines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $productId = (int) ($line['id'] ?? 0);
            if ($productId > 0) {
                $giftIds[$productId] = true;
            }
        }
        if ($giftIds === []) {
            return null;
        }

        $basket = $order->getBasket();
        if ($basket === null) {
            return null;
        }

        $kopeck = [];
        $zeroRows = [];
        foreach ($basket as $item) {
            if (!$item instanceof BasketItem || $item->isBundleChild()) {
                continue;
            }
            $productId = (int) $item->getProductId();
            if (!isset($giftIds[$productId])) {
                continue;
            }
            $price = PriceMaths::roundPrecision((float) $item->getPrice());
            if ($price == 0.01) {
                $kopeck[$productId] = true;
                continue;
            }
            if ($price == 0.0) {
                $zeroRows[$productId][] = $item;
            }
        }

        $kept = false;
        foreach (array_keys($giftIds) as $productId) {
            if (!isset($kopeck[$productId])) {
                continue;
            }
            $kept = true;
            foreach ($zeroRows[$productId] ?? [] as $row) {
                $row->delete();
            }
        }
        if (!$kept) {
            return null;
        }

        $gifts['lines'] = [];

        return new EventResult(EventResult::SUCCESS, ['GIFTS' => $gifts]);
    }
}
