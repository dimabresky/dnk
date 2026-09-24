<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Sale\Order;

/**
 * Возвращает служебное свойство OPERATOR_CALL в данные оформления заказа.
 */
final class CheckoutOrderPropEvents
{
    private const PROPERTY_CODE = 'OPERATOR_CALL';

    /**
     * @param mixed $order
     * @param mixed $arUserResult
     * @param mixed $request
     * @param mixed $arParams
     * @param mixed $arResult
     */
    public static function onSaleComponentOrderResultPrepared($order, &$arUserResult, $request, &$arParams, &$arResult): void
    {
        if (!$order instanceof Order || !is_array($arResult)) {
            return;
        }

        if (!isset($arResult['JS_DATA']['ORDER_PROP']['properties']) || !is_array($arResult['JS_DATA']['ORDER_PROP']['properties'])) {
            return;
        }

        $orderProps = $order->getPropertyCollection()->getArray();
        if (empty($orderProps['properties']) || !is_array($orderProps['properties'])) {
            return;
        }

        $operatorCall = null;
        foreach ($orderProps['properties'] as $property) {
            if (($property['CODE'] ?? '') === self::PROPERTY_CODE) {
                $operatorCall = $property;
                break;
            }
        }

        if (!is_array($operatorCall)) {
            return;
        }

        $propertyId = (int)($operatorCall['ID'] ?? 0);
        foreach ($arResult['JS_DATA']['ORDER_PROP']['properties'] as $existing) {
            if ((int)($existing['ID'] ?? 0) === $propertyId && $propertyId > 0) {
                return;
            }
        }

        $arResult['JS_DATA']['ORDER_PROP']['properties'][] = $operatorCall;
    }
}
