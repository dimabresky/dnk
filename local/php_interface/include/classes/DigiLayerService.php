<?php

namespace Dnk\PhpInterface;

use Aspro\Premier\Itemaction\Compare;
use Aspro\Premier\Itemaction\Favorite;
use Bitrix\Catalog\Product\Basket as CatalogBasket;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use CIBlockElement;

/**
 * Diginetica AnyQuery digiLayer: cart / favorites / compare state and mutations.
 */
final class DigiLayerService
{
    /**
     * Returns cart state as {productId: quantity} with string keys.
     *
     * @return array<string, float|int>
     */
    public static function cartState(): array
    {
        self::assertSaleModule();

        $result = [];
        $basket = Basket::loadItemsForFUser(self::getFUserId(), SITE_ID);

        foreach ($basket as $basketItem) {
            if (!$basketItem->canBuy() || $basketItem->isDelay()) {
                continue;
            }

            $productId = (string) $basketItem->getProductId();
            $result[$productId] = ($result[$productId] ?? 0) + $basketItem->getQuantity();
        }

        return $result;
    }

    /**
     * Adds amount to cart quantity (sums if product already present).
     *
     * @throws SystemException
     */
    public static function addToCart(int $id, float $amount = 1.0): bool
    {
        self::assertCatalogProduct($id);
        self::assertSaleModule();

        if ($amount <= 0) {
            $amount = 1.0;
        }

        $basket = Basket::loadItemsForFUser(self::getFUserId(), SITE_ID);
        $basketItem = $basket->getExistsItem('catalog', $id);

        if ($basketItem) {
            $basketItem->setField('QUANTITY', $basketItem->getQuantity() + $amount);
            $basketItem->setField('DELAY', 'N');
        } else {
            $providerClass = 'CCatalogProductProvider';
            if (
                Loader::includeModule('catalog')
                && class_exists(CatalogBasket::class)
                && method_exists(CatalogBasket::class, 'getDefaultProviderName')
            ) {
                $providerClass = CatalogBasket::getDefaultProviderName();
            }

            $basketItem = $basket->createItem('catalog', $id);
            $basketItem->setFields([
                'QUANTITY' => $amount,
                'CURRENCY' => CurrencyManager::getBaseCurrency(),
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => $providerClass,
            ]);
        }

        $saveResult = $basket->save();
        if (!$saveResult->isSuccess()) {
            throw new SystemException(implode('. ', $saveResult->getErrorMessages()));
        }

        return true;
    }

    /**
     * Subtracts amount from cart quantity. amount < 1 removes the item fully.
     *
     * @throws SystemException
     */
    public static function removeFromCart(int $id, float $amount = 1.0): bool
    {
        self::assertCatalogProduct($id);
        self::assertSaleModule();

        $basket = Basket::loadItemsForFUser(self::getFUserId(), SITE_ID);
        $basketItem = $basket->getExistsItem('catalog', $id);

        if (!$basketItem) {
            return true;
        }

        if ($amount < 1) {
            $basketItem->delete();
        } else {
            $newQuantity = $basketItem->getQuantity() - $amount;
            if ($newQuantity <= 0) {
                $basketItem->delete();
            } else {
                $basketItem->setField('QUANTITY', $newQuantity);
            }
        }

        $saveResult = $basket->save();
        if (!$saveResult->isSuccess()) {
            throw new SystemException(implode('. ', $saveResult->getErrorMessages()));
        }

        return true;
    }

    /**
     * Returns favorite product IDs as a list of strings.
     *
     * @return list<string>
     */
    public static function favoritesState(): array
    {
        self::assertAsproModule();

        return self::idsToStringList(Favorite::getItems());
    }

    /**
     * Adds product to favorites.
     *
     * @throws SystemException
     */
    public static function addToFavorites(int $id): bool
    {
        self::assertCatalogProduct($id);
        self::assertAsproModule();

        Favorite::addItem($id);

        return true;
    }

    /**
     * Removes product from favorites.
     *
     * @throws SystemException
     */
    public static function removeFromFavorites(int $id): bool
    {
        self::assertCatalogProduct($id);
        self::assertAsproModule();

        Favorite::removeItem($id);

        return true;
    }

    /**
     * Returns compare product IDs as a list of strings.
     *
     * @return list<string>
     */
    public static function comparesState(): array
    {
        self::assertAsproModule();

        return self::idsToStringList(Compare::getItems());
    }

    /**
     * Adds product to compare list.
     *
     * @throws SystemException
     */
    public static function addToCompare(int $id): bool
    {
        self::assertCatalogProduct($id);
        self::assertAsproModule();

        Compare::addItem($id);

        return true;
    }

    /**
     * Removes product from compare list.
     *
     * @throws SystemException
     */
    public static function removeFromCompare(int $id): bool
    {
        self::assertCatalogProduct($id);
        self::assertAsproModule();

        Compare::removeItem($id);

        return true;
    }

    /**
     * Snapshot of cart / favorites / compare for digiLayer JSON responses.
     *
     * @return array{cart: array<string, float|int>, favorites: list<string>, compares: list<string>}
     */
    public static function snapshot(): array
    {
        return [
            'cart' => self::cartState(),
            'favorites' => self::favoritesState(),
            'compares' => self::comparesState(),
        ];
    }

    /**
     * @param array<int|string, mixed> $items
     * @return list<string>
     */
    private static function idsToStringList(array $items): array
    {
        $ids = [];
        foreach ($items as $key => $value) {
            $id = is_array($value) ? (string) $key : (string) $value;
            if ($id === '' || $id === '0') {
                continue;
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @throws SystemException
     */
    private static function assertCatalogProduct(int $id): void
    {
        if ($id <= 0) {
            throw new SystemException('Invalid product id');
        }

        if (!defined('DNK_CATALOG_IBLOCK_ID') || (int) DNK_CATALOG_IBLOCK_ID <= 0) {
            throw new SystemException('Catalog iblock is not configured');
        }

        if (!Loader::includeModule('iblock')) {
            throw new SystemException('Module iblock is not installed');
        }

        $element = CIBlockElement::GetList(
            [],
            [
                'ID' => $id,
                'IBLOCK_ID' => (int) DNK_CATALOG_IBLOCK_ID,
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID']
        )->Fetch();

        if (!$element) {
            throw new SystemException('Product not found in catalog');
        }
    }

    /**
     * @throws SystemException
     */
    private static function assertSaleModule(): void
    {
        if (!Loader::includeModule('sale')) {
            throw new SystemException('Module sale is not installed');
        }
    }

    /**
     * @throws SystemException
     */
    private static function assertAsproModule(): void
    {
        if (!Loader::includeModule('aspro.premier')) {
            throw new SystemException('Module aspro.premier is not installed');
        }
    }

    private static function getFUserId(): int
    {
        global $USER;

        $userId = (is_object($USER) && $USER->IsAuthorized()) ? (int) $USER->GetID() : 0;

        return $userId > 0 ? (int) Fuser::getIdByUserId($userId) : (int) Fuser::getId();
    }
}
