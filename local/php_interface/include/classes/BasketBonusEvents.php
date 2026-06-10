<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Sale\Order;

/**
 * События Sale для бонусов корзины и checkout.
 */
final class BasketBonusEvents
{
    public static function register(): void
    {
        $em = EventManager::getInstance();

        $em->addEventHandler('sale', 'OnSaleBasketSaved', [self::class, 'onSaleBasketSaved']);
        $em->addEventHandler('sale', 'OnSaleOrderBeforeSaved', [self::class, 'onSaleOrderBeforeSavedEarly'], false, 1);
        $em->addEventHandler('sale', 'OnSaleOrderBeforeSaved', [self::class, 'onSaleOrderBeforeSavedLate'], false, 200);
        $em->addEventHandler('sale', 'OnSaleComponentOrderResultPrepared', [self::class, 'onSaleComponentOrderResultPreparedEarly'], false, 1);
        $em->addEventHandler('sale', 'OnSaleComponentOrderResultPrepared', [self::class, 'onSaleComponentOrderResultPreparedLate'], false, 200);
        $em->addEventHandler('sale', 'OnSaleOrderSaved', [self::class, 'onSaleOrderSaved']);
    }

  /** @param \Bitrix\Main\Event $event */
    public static function onSaleOrderSaved($event): void
    {
        if (!BasketBonusService::isApplied()) {
            return;
        }

        $isNew = (bool)$event->getParameter('IS_NEW');
        if (!$isNew) {
            return;
        }

        BasketBonusService::clearStateAfterOrder();
    }

  /** @param mixed $basket */
    public static function onSaleBasketSaved($basket): void
    {
        BasketBonusService::reconcileOrphanedBonusDiscounts();

        if (!BasketBonusService::isApplied()) {
            return;
        }

        BasketBonusService::syncAfterBasketChange();
    }

  /** @param mixed $event */
    public static function onSaleOrderBeforeSavedEarly($event): void
    {
        $order = self::extractOrderFromEvent($event);
        if ($order === null || !$order->isNew()) {
            return;
        }

        BasketBonusService::suppressAsproBeforeSavePriceApply($order);
    }

  /** @param mixed $event */
    public static function onSaleOrderBeforeSavedLate($event): void
    {
        $order = self::extractOrderFromEvent($event);
        if ($order === null || !$order->isNew()) {
            return;
        }

        BasketBonusService::restoreOrderBonusPropertyAfterAspro($order);
    }

  /** @param mixed $event */
    private static function extractOrderFromEvent($event): ?Order
    {
        if ($event instanceof Event) {
            $entity = $event->getParameter('ENTITY');

            return $entity instanceof Order ? $entity : null;
        }

        return $event instanceof Order ? $event : null;
    }

  /**
   * @param mixed $order
   * @param mixed $arUserResult
   * @param mixed $request
   * @param mixed $arParams
   * @param mixed $arResult
   */
    public static function onSaleComponentOrderResultPreparedEarly($order, &$arUserResult, $request, &$arParams, &$arResult): void
    {
        BasketBonusService::reconcileOrphanedBonusDiscounts();

        if ($order instanceof Order) {
            BasketBonusService::syncCheckoutOrderBasketFromFuser($order);
        }

        if (!$order instanceof Order || !BasketBonusService::isApplied()) {
            return;
        }

        BasketBonusService::syncOrderBonusProperty($order);

        if (is_array($arUserResult)) {
            $props = \Aspro\Bonus\History\Order::getGruppedPropsByCode($order->getPropertyCollection());
            $propId = (int)($props[\Aspro\Bonus\History\Order::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID'] ?? 0);
            if ($propId > 0) {
                $arUserResult['ORDER_PROP_' . $propId] = BasketBonusService::getAppliedAmount();
            }
        }
    }

  /**
   * @param mixed $order
   * @param mixed $arUserResult
   * @param mixed $request
   * @param mixed $arParams
   * @param mixed $arResult
   */
    public static function onSaleComponentOrderResultPreparedLate($order, &$arUserResult, $request, &$arParams, &$arResult): void
    {
        if (!$order instanceof Order || !BasketBonusService::isApplied() || !is_array($arResult)) {
            return;
        }

        if (!isset($arResult['JS_DATA']['ASPRO_BONUS']) || !is_array($arResult['JS_DATA']['ASPRO_BONUS'])) {
            $arResult['JS_DATA']['ASPRO_BONUS'] = [];
        }

        $arResult['JS_DATA']['ASPRO_BONUS']['USES'] = [
            'PAYED' => BasketBonusService::getAppliedAmount(),
            'PAYED_FORMATTED' => (string)BasketBonusService::getAppliedAmount(),
            'NOT_SHOW_USED' => 'Y',
            'TEMPLATE' => '',
        ];

        $currency = $order->getCurrency();
        $cartSum = $order->getPrice() - $order->getDeliveryPrice();

        if (!empty($arResult['JS_DATA']['TOTAL'])) {
            $arResult['JS_DATA']['TOTAL']['ORDER_PRICE'] = $cartSum;
            $arResult['JS_DATA']['TOTAL']['ORDER_PRICE_FORMATED'] = \SaleFormatCurrency($cartSum, $currency);
            $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] = $order->getPrice();
            $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = \SaleFormatCurrency($order->getPrice(), $currency);
        }
    }
}
