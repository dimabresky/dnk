<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Aspro\Bonus\Events\SaleOrderAjax;
use Aspro\Bonus\History\Order as BonusOrder;
use Aspro\Bonus\History\User as BonusUser;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketBase;
use Bitrix\Sale\Discount;
use Bitrix\Sale\Fuser;
use Bitrix\Sale\Order;
use Bitrix\Sale\PersonType;

/**
 * Применение бонусов Aspro к FUSER-корзине на этапе корзины (только стоимость товаров).
 */
final class BasketBonusService
{
    public const SESSION_KEY = 'DNK_BASKET_BONUS_STATE';

  /** @var float|null Сумма списания, восстанавливаемая после обхода BeforeOrderSave Aspro */
    public static ?float $pendingOrderPayed = null;

    /**
     * Текущее состояние применения бонусов из сессии.
     *
     * @return array{applied: bool, payed: float, basket_hash: string, site_id: string, fuser_id: int, user_id: int}
     */
    public static function getState(): array
    {
        $default = [
            'applied' => false,
            'payed' => 0.0,
            'basket_hash' => '',
            'site_id' => '',
            'fuser_id' => 0,
            'user_id' => 0,
        ];

        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            return $default;
        }

        $state = $_SESSION[self::SESSION_KEY];

        return [
            'applied' => !empty($state['applied']),
            'payed' => (float)($state['payed'] ?? 0),
            'basket_hash' => (string)($state['basket_hash'] ?? ''),
            'site_id' => (string)($state['site_id'] ?? ''),
            'fuser_id' => (int)($state['fuser_id'] ?? 0),
            'user_id' => (int)($state['user_id'] ?? 0),
        ];
    }

    public static function isApplied(): bool
    {
        $state = self::getState();

        return $state['applied'] && $state['payed'] > 0 && self::isStateValidForCurrentContext($state);
    }

    public static function getAppliedAmount(): float
    {
        return self::isApplied() ? self::getState()['payed'] : 0.0;
    }

    /**
     * Данные для UI блока бонусов на корзине.
     *
     * @return array{
     *     available: bool,
     *     balance: float,
     *     balance_formatted: string,
     *     max_pay: float,
     *     max_pay_formatted: string,
     *     min_pay: float,
     *     min_pay_formatted: string,
     *     applied: float,
     *     applied_formatted: string,
     *     error_min: bool,
     *     message: string
     * }
     */
    public static function getUiData(): array
    {
        $empty = [
            'available' => false,
            'balance' => 0.0,
            'balance_formatted' => '0',
            'max_pay' => 0.0,
            'max_pay_formatted' => '0',
            'min_pay' => 0.0,
            'min_pay_formatted' => '0',
            'applied' => 0.0,
            'applied_formatted' => '0',
            'error_min' => false,
            'message' => '',
        ];

        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized() || !self::ensureModules()) {
            return $empty;
        }

        $userId = (int)$USER->GetID();
        if ($userId <= 0 || BonusUser::getBalance($userId) <= 0) {
            self::clearState();

            return $empty;
        }

        $basket = self::loadBasket();
        if ($basket && self::isApplied()) {
            self::resetBasketCustomPrices($basket);
        }

        $appliedAmount = self::getAppliedAmount();
        $calc = self::calculatePayBonus($userId, $appliedAmount, $basket);
        if ($calc === null) {
            self::clearState();

            return $empty;
        }

        $appliedFormatted = $appliedAmount > 0
            ? (string)($calc['PAYED_FORMATTED'] ?? $appliedAmount)
            : '0';

        return [
            'available' => true,
            'balance' => (float)$calc['USER_BALANCE'],
            'balance_formatted' => (string)$calc['USER_BALANCE_FORMATTED'],
            'max_pay' => (float)$calc['MAX_ORDER_PAY'],
            'max_pay_formatted' => (string)$calc['MAX_ORDER_PAY_FORMATTED'],
            'min_pay' => (float)$calc['MIN_ORDER_PAY'],
            'min_pay_formatted' => (string)($calc['MIN_ORDER_PAY_FORMATTED'] ?? $calc['MIN_ORDER_PAY']),
            'applied' => $appliedAmount,
            'applied_formatted' => $appliedFormatted,
            'error_min' => !empty($calc['ERROR_MIN_ORDER_PAY']),
            'message' => '',
        ];
    }

    /**
     * Применить бонусы к корзине.
     *
     * @return array{success: bool, message?: string, ui?: array}
     */
    public static function apply(float $requestedAmount): array
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized() || !self::ensureModules()) {
            return ['success' => false, 'message' => 'not_authorized'];
        }

        $userId = (int)$USER->GetID();
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'not_authorized'];
        }

        $basket = self::loadBasket();
        if ($basket === null || $basket->isEmpty()) {
            return ['success' => false, 'message' => 'empty_basket'];
        }

        self::resetBasketCustomPrices($basket);

        $calc = self::calculatePayBonus($userId, $requestedAmount, $basket);
        if ($calc === null || empty($calc['PAYED'])) {
            return ['success' => false, 'message' => 'bonus_not_applicable'];
        }

        if (!self::applyPricesToBasket($basket, $calc)) {
            return ['success' => false, 'message' => 'apply_failed'];
        }

        self::persistState((float)$calc['PAYED'], $basket);

        $saveResult = $basket->save();
        if (!$saveResult->isSuccess()) {
            self::clearState();

            return ['success' => false, 'message' => 'basket_save_failed'];
        }

        return [
            'success' => true,
            'ui' => self::getUiData(),
        ];
    }

    /**
     * Сбросить применённые бонусы.
     *
     * @return array{success: bool, message?: string, ui?: array}
     */
    public static function reset(): array
    {
        self::clearAppliedBonusesFromBasket();

        return [
            'success' => true,
            'ui' => self::getUiData(),
        ];
    }

    /**
     * Синхронизация после изменения корзины: пересчёт или сброс.
     */
    public static function syncAfterBasketChange(): void
    {
        if (!self::isApplied() || !self::ensureModules()) {
            return;
        }

        global $USER;
        $userId = is_object($USER) ? (int)$USER->GetID() : 0;
        if ($userId <= 0) {
            self::reset();

            return;
        }

        $basket = self::loadBasket();
        if ($basket === null || $basket->isEmpty()) {
            self::clearState();

            return;
        }

        $state = self::getState();
        $hash = self::buildBasketHash($basket);
        if ($hash === $state['basket_hash']) {
            return;
        }

        $previousPayed = $state['payed'];
        self::resetBasketCustomPrices($basket);

        $calc = self::calculatePayBonus($userId, $previousPayed, $basket);
        if ($calc === null || empty($calc['PAYED'])) {
            self::reset();

            return;
        }

        if (!self::applyPricesToBasket($basket, $calc)) {
            self::reset();

            return;
        }

        self::persistState((float)$calc['PAYED'], $basket);
        $basket->save();
    }

    /**
     * Установить свойство заказа ASPRO_BONUS_PAYED из сессии.
     */
    public static function syncOrderBonusProperty(Order $order): void
    {
        if (!self::isApplied() || !self::ensureModules()) {
            return;
        }

        $props = BonusOrder::getGruppedPropsByCode($order->getPropertyCollection());
        if (empty($props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID'])) {
            return;
        }

        $prop = $order->getPropertyCollection()->getItemByOrderPropertyId(
            (int)$props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID']
        );
        $prop?->setValue(self::getAppliedAmount());
    }

    /**
     * Временно обнулить свойство оплаты бонусами до обработчика Aspro BeforeOrderSave.
     */
    public static function suppressAsproBeforeSavePriceApply(Order $order): void
    {
        if (!self::isApplied()) {
            return;
        }

        $props = BonusOrder::getGruppedPropsByCode($order->getPropertyCollection());
        if (empty($props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID'])) {
            return;
        }

        self::$pendingOrderPayed = self::getAppliedAmount();

        $prop = $order->getPropertyCollection()->getItemByOrderPropertyId(
            (int)$props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID']
        );
        $prop?->setValue(0);
    }

    /**
     * Восстановить свойство оплаты бонусами после обработчика Aspro BeforeOrderSave.
     */
    public static function restoreOrderBonusPropertyAfterAspro(Order $order): void
    {
        if (self::$pendingOrderPayed === null || self::$pendingOrderPayed <= 0) {
            return;
        }

        $payed = self::$pendingOrderPayed;
        self::$pendingOrderPayed = null;

        $props = BonusOrder::getGruppedPropsByCode($order->getPropertyCollection());
        if (empty($props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID'])) {
            return;
        }

        $prop = $order->getPropertyCollection()->getItemByOrderPropertyId(
            (int)$props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID']
        );
        $prop?->setValue($payed);
    }

    private static function ensureModules(): bool
    {
        return Loader::includeModule('sale')
            && Loader::includeModule('catalog')
            && Loader::includeModule('aspro.bonus');
    }

    private static function loadBasket(): ?BasketBase
    {
        if (!self::ensureModules()) {
            return null;
        }

        $siteId = Context::getCurrent()->getSite();

        return Basket::loadItemsForFUser(Fuser::getId(), $siteId);
    }

    private static function isStateValidForCurrentContext(array $state): bool
    {
        if (!self::ensureModules()) {
            return false;
        }

        $siteId = Context::getCurrent()->getSite();
        global $USER;
        $userId = is_object($USER) ? (int)$USER->GetID() : 0;

        return $state['site_id'] === $siteId
            && $state['fuser_id'] === (int)Fuser::getId()
            && $state['user_id'] === $userId;
    }

    /**
     * OnPageStart может выполниться до инициализации $USER при валидной сессии бонусов.
     *
     * @param array{applied: bool, payed: float, basket_hash: string, site_id: string, fuser_id: int, user_id: int} $state
     */
    private static function shouldDeferOrphanReconcile(array $state): bool
    {
        if ($state['user_id'] <= 0) {
            return false;
        }

        global $USER;
        $currentUserId = is_object($USER) ? (int)$USER->GetID() : 0;

        if ($currentUserId !== 0) {
            return false;
        }

        if (!self::ensureModules()) {
            return false;
        }

        return $state['site_id'] === Context::getCurrent()->getSite()
            && $state['fuser_id'] === (int)Fuser::getId();
    }

    private static function persistState(float $payed, BasketBase $basket): void
    {
        global $USER;

        $_SESSION[self::SESSION_KEY] = [
            'applied' => true,
            'payed' => $payed,
            'basket_hash' => self::buildBasketHash($basket),
            'site_id' => Context::getCurrent()->getSite(),
            'fuser_id' => (int)Fuser::getId(),
            'user_id' => is_object($USER) ? (int)$USER->GetID() : 0,
        ];
    }

    public static function clearStateAfterOrder(): void
    {
        self::clearState();

        if (!self::ensureModules()) {
            return;
        }

        $basket = self::loadBasket();
        if ($basket !== null && self::hasBonusCustomPrices($basket)) {
            self::resetBasketCustomPrices($basket);
            $basket->save();
        }
    }

    /**
     * Сбросить скидки в корзине, если сессия бонусов уже недействительна.
     */
    public static function reconcileOrphanedBonusDiscounts(): void
    {
        if (!self::ensureModules()) {
            return;
        }

        if (self::isApplied()) {
            return;
        }

        $state = self::getState();
        if ($state['applied'] && $state['payed'] > 0 && self::shouldDeferOrphanReconcile($state)) {
            return;
        }

        $basket = self::loadBasket();
        if ($basket === null || !self::hasBonusCustomPrices($basket)) {
            return;
        }

        self::resetBasketCustomPrices($basket);
        $basket->save();
        self::clearState();
    }

    /**
     * Синхронизировать корзину заказа checkout с актуальной FUSER-корзиной после сброса бонусов.
     */
    public static function syncCheckoutOrderBasketFromFuser(Order $order): void
    {
        if (self::isApplied() || !self::ensureModules()) {
            return;
        }

        $orderBasket = $order->getBasket();
        if ($orderBasket === null || $orderBasket->isEmpty()) {
            return;
        }

        $fuserBasket = self::loadBasket();
        if ($fuserBasket === null || $fuserBasket->isEmpty()) {
            return;
        }

        $fuserItems = [];
        foreach ($fuserBasket as $fuserItem) {
            $fuserItems[(int)$fuserItem->getId()] = $fuserItem;
        }

        foreach ($orderBasket as $orderItem) {
            $basketItemId = (int)$orderItem->getId();
            if ($basketItemId <= 0 || !isset($fuserItems[$basketItemId])) {
                continue;
            }

            $fuserItem = $fuserItems[$basketItemId];
            $orderItem->setField('CUSTOM_PRICE', $fuserItem->getField('CUSTOM_PRICE'));
            $orderItem->setField('PRICE', $fuserItem->getPrice());
            $orderItem->setField('BASE_PRICE', $fuserItem->getBasePrice());
            $orderItem->setField('DISCOUNT_PRICE', $fuserItem->getDiscountPrice());
        }
    }

    private static function invalidateAppliedBonusesIfNeeded(): void
    {
        if (!self::ensureModules()) {
            return;
        }

        if (!self::isApplied() && !self::hasBonusCustomPrices(self::loadBasket())) {
            return;
        }

        self::clearAppliedBonusesFromBasket();
    }

    private static function clearAppliedBonusesFromBasket(): void
    {
        if (!self::ensureModules()) {
            return;
        }

        $basket = self::loadBasket();
        self::clearState();

        if ($basket !== null && !$basket->isEmpty()) {
            self::resetBasketCustomPrices($basket);
            $basket->save();
        }
    }

    private static function hasBonusCustomPrices(?BasketBase $basket): bool
    {
        if ($basket === null || $basket->isEmpty()) {
            return false;
        }

        foreach ($basket as $item) {
            if ($item->getField('CUSTOM_PRICE') === 'Y') {
                return true;
            }
        }

        return false;
    }

    private static function clearState(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
        self::$pendingOrderPayed = null;
    }

    private static function buildBasketHash(BasketBase $basket): string
    {
        $parts = [];
        foreach ($basket as $item) {
            if (!$item->canBuy() || $item->isDelay()) {
                continue;
            }
            $parts[] = $item->getId() . ':' . $item->getProductId() . ':' . $item->getQuantity() . ':' . $item->getPrice();
        }

        sort($parts);

        return md5(implode('|', $parts));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function calculatePayBonus(int $userId, float $requestedPayed, ?BasketBase $basket = null): ?array
    {
        global $APPLICATION;

        $basket ??= self::loadBasket();
        if ($basket === null || $basket->isEmpty()) {
            return null;
        }

        $siteId = Context::getCurrent()->getSite();
        $cartSum = self::getBasketProductsSum($basket);
        if ($cartSum <= 0) {
            return null;
        }

        $order = Order::create($siteId, $userId);
        $order->setPersonTypeId(self::resolvePersonTypeId($siteId, $userId));
        $order->setBasket($basket);

        $props = BonusOrder::getGruppedPropsByCode($order->getPropertyCollection());
        if (!empty($props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID'])) {
            $prop = $order->getPropertyCollection()->getItemByOrderPropertyId(
                (int)$props[BonusOrder::PROPERTY_BONUS_PAYMENT]['ORDER_PROPS_ID']
            );
            $prop?->setValue($requestedPayed > 0 ? $requestedPayed : 0);
        }

        $orderParams = [
            'SITE_ID' => $siteId,
            'USER_ID' => $userId,
            'ORDER_SUM' => $cartSum,
            'CART_SUM' => $cartSum,
            'DELIVERY_SUM' => 0,
            'PERSON_TYPE_ID' => $order->getPersonTypeId(),
            'CURRENCY' => $order->getCurrency(),
            'DISCOUNT' => 0,
            'PAYMENTS' => [],
            'DELIVERY' => [],
        ];

        $payBonus = $APPLICATION->IncludeComponent(
            'aspro:bonus.uses',
            '',
            [
                'BASKET_ORDER' => $order->getBasket(),
            ] + $orderParams,
            null,
            ['HIDE_ICONS' => 'Y']
        );

        if (!is_array($payBonus) || empty($payBonus['ITEMS'])) {
            return null;
        }

        $payBonus['PAY_DELIVERY'] = 0;
        $payBonus['NEW_DELIVERY_PRICE'] = 0;

        return $payBonus;
    }

    /**
     * @param array<string, mixed> $payBonus
     */
    private static function applyPricesToBasket(BasketBase $basket, array $payBonus): bool
    {
        if (empty($payBonus['PAYED']) || empty($payBonus['PAY_CART'])) {
            return false;
        }

        $itemsByBasketId = self::indexBonusItemsByBasketId($payBonus);
        if ($itemsByBasketId === []) {
            return false;
        }

        $updatedItems = 0;

        foreach ($basket as $item) {
            $basketItemId = (int)$item->getId();
            if ($basketItemId <= 0 || !isset($itemsByBasketId[$basketItemId])) {
                continue;
            }

            $bonusItem = $itemsByBasketId[$basketItemId];
            if (
                empty($bonusItem['DISPLAYED_BONUSES_WITH_QUANTITY'])
                && !empty($payBonus['PROFILE']['CONDITIONS_USES'])
            ) {
                continue;
            }

            $prices = SaleOrderAjax::getPrices(
                bonusItem: $bonusItem,
                basketItem: $item->getFieldValues(),
                payBonus: $payBonus
            );

            if (
                !isset($prices['PRICE'], $prices['BASE_PRICE'], $prices['DISCOUNT_PRICE'])
            ) {
                continue;
            }

            $item->setField('CUSTOM_PRICE', 'Y');
            $item->setField('PRICE', $prices['PRICE']);
            $item->setField('BASE_PRICE', $prices['BASE_PRICE']);
            $item->setField('DISCOUNT_PRICE', $prices['DISCOUNT_PRICE']);
            ++$updatedItems;
        }

        return $updatedItems > 0;
    }

    /**
     * @param array<string, mixed> $payBonus
     *
     * @return array<int, array<string, mixed>>
     */
    private static function indexBonusItemsByBasketId(array $payBonus): array
    {
        $indexed = [];

        foreach ($payBonus['ITEMS'] ?? [] as $bonusItem) {
            if (!is_array($bonusItem)) {
                continue;
            }

            $basketItemId = (int)($bonusItem['BASKET_ITEM_ID'] ?? 0);
            if ($basketItemId > 0) {
                $indexed[$basketItemId] = $bonusItem;
            }
        }

        return $indexed;
    }

    private static function resetBasketCustomPrices(BasketBase $basket): void
    {
        $resetItemIds = [];

        foreach ($basket as $item) {
            if ($item->getField('CUSTOM_PRICE') === 'Y') {
                $item->setField('CUSTOM_PRICE', 'N');
                $resetItemIds[(int)$item->getId()] = true;
            }
        }

        if ($resetItemIds === []) {
            return;
        }

        $updatedIds = [];
        $discounts = Discount::buildFromBasket(
            $basket,
            new Discount\Context\Fuser($basket->getFUserId(true))
        );
        if ($discounts) {
            $discounts->calculate();
            $applyResult = $discounts->getApplyResult(true);
            if (!empty($applyResult['PRICES']['BASKET'])) {
                foreach ($basket as $item) {
                    $basketId = (int)$item->getId();
                    if (!isset($applyResult['PRICES']['BASKET'][$basketId])) {
                        continue;
                    }
                    $priceData = $applyResult['PRICES']['BASKET'][$basketId];
                    $item->setField('PRICE', $priceData['PRICE']);
                    $item->setField('BASE_PRICE', $priceData['BASE_PRICE']);
                    $item->setField('DISCOUNT_PRICE', $priceData['DISCOUNT_PRICE']);
                    $updatedIds[$basketId] = true;
                }
            }
        }

        foreach ($basket as $item) {
            $basketId = (int)$item->getId();
            if (!isset($resetItemIds[$basketId]) || isset($updatedIds[$basketId])) {
                continue;
            }

            self::restoreBasketItemCatalogPrice($item);
        }
    }

    /**
     * @param \Bitrix\Sale\BasketItem|\Bitrix\Sale\BasketItemBase $item
     */
    private static function restoreBasketItemCatalogPrice($item): void
    {
        global $USER;

        if (!Loader::includeModule('catalog')) {
            return;
        }

        $siteId = Context::getCurrent()->getSite();
        $userGroups = is_object($USER) ? $USER->GetUserGroupArray() : [2];

        $optimalPrice = \CCatalogProduct::GetOptimalPrice(
            (int)$item->getProductId(),
            (float)$item->getQuantity(),
            $userGroups,
            'N',
            [],
            $siteId
        );

        if (empty($optimalPrice['RESULT_PRICE'])) {
            return;
        }

        $resultPrice = $optimalPrice['RESULT_PRICE'];
        $item->setField('PRICE', $resultPrice['DISCOUNT_PRICE']);
        $item->setField('BASE_PRICE', $resultPrice['BASE_PRICE']);
        $item->setField('DISCOUNT_PRICE', $resultPrice['DISCOUNT']);
    }

    private static function getBasketProductsSum(BasketBase $basket): float
    {
        $sum = 0.0;
        foreach ($basket as $item) {
            if ($item->canBuy() && !$item->isDelay()) {
                $sum += $item->getFinalPrice();
            }
        }

        return $sum;
    }

    private static function resolvePersonTypeId(string $siteId, int $userId = 0): int
    {
        if ($userId > 0) {
            $lastOrder = Order::getList([
                'filter' => [
                    '=USER_ID' => $userId,
                    '=LID' => $siteId,
                ],
                'order' => ['DATE_INSERT' => 'DESC'],
                'select' => ['PERSON_TYPE_ID'],
                'limit' => 1,
            ])->fetch();

            if (!empty($lastOrder['PERSON_TYPE_ID'])) {
                return (int)$lastOrder['PERSON_TYPE_ID'];
            }
        }

        $row = PersonType::getList([
            'filter' => ['ACTIVE' => 'Y', '=LID' => $siteId],
            'order' => ['SORT' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        return (int)($row['ID'] ?? 1);
    }
}
