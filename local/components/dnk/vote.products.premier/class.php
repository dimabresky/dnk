<?php

namespace Dnk\Components;

use Aspro\Premier\VoteIgnoreTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Sale;
use CPremier as Solution;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loc::loadMessages(__FILE__);

class VoteProducts extends \CBitrixComponent
{
    protected $arOrders = [];
    protected $arItems = [];
    protected $arIgnoredItems = [];
    protected $arCommentedPosts = [];

    public function onPrepareComponentParams($arParams)
    {
        if (isset($arParams['CUSTOM_SITE_ID'])) {
            $this->setSiteId($arParams['CUSTOM_SITE_ID']);
        }

        if (isset($arParams['CUSTOM_LANGUAGE_ID'])) {
            $this->setLanguageId($arParams['CUSTOM_LANGUAGE_ID']);
        }

        $arParams['PRODUCTS_PER_PAGE'] = intval($arParams['PRODUCTS_PER_PAGE'] ?? '10');
        if ($arParams['PRODUCTS_PER_PAGE'] <= 0) {
            $arParams['PRODUCTS_PER_PAGE'] = 10;
        }

        $arParams['ORDER_STATUSES'] = (array) ($arParams['ORDER_STATUSES'] ?? ['F']);
        $arParams['BLOG_URL'] = $arParams['BLOG_URL'] ?? 'catalog_comments';
        $arParams['PATH_TO_SMILE'] = $arParams['PATH_TO_SMILE'] ?? '/bitrix/images/blog/smile/';
        $arParams['RATING_TYPE'] = $arParams['RATING_TYPE'] ?? 'like_graphic_catalog_reviews';

        return $arParams;
    }

    protected function includeModules()
    {
        if (!Loader::includeModule(Solution::moduleID)) {
            throw new SystemException(Loc::getMessage('VP_C_ERROR_MODULE_NOT_INSTALLED'));
        }

        if (!Loader::includeModule('blog')) {
            throw new SystemException(Loc::getMessage('VP_C_ERROR_MODULE_BLOG_NOT_INSTALLED'));
        }

        if (!Loader::includeModule('sale')) {
            throw new SystemException(Loc::getMessage('VP_C_ERROR_MODULE_SALE_NOT_INSTALLED'));
        }
    }

    public function executeComponent()
    {
        $this->setFrameMode(false);

        try {
            $this->includeModules();

            $siteId = $this->getSiteId();

            $signer = new \Bitrix\Main\Security\Sign\Signer();
            $signedParams = $signer->sign(base64_encode(serialize($this->arParams)), str_replace(':', '.', $this->getName()));

            $this->arResult = [
                'RAND' => $this->request['rand'] ?? \Bitrix\Main\Security\Random::getString(5, true),
                'USER_ID' => $GLOBALS['USER']->GetID(),
                'SITE_ID' => $siteId,
                'ORDERS' => [],
                'IGNORED_ITEMS' => [],
                'COMMENTED_POSTS' => [],
                'ITEMS' => [],
                'SIGNED_PARAMS' => $signedParams,
            ];

            $this->arOrders = &$this->arResult['ORDERS'];
            $this->arIgnoredItems = &$this->arResult['IGNORED_ITEMS'];
            $this->arCommentedPosts = &$this->arResult['COMMENTED_POSTS'];
            $this->arItems = &$this->arResult['ITEMS'];

            $this->collectOrders();
            $this->collectIgnoredItems();
            $this->collectCommentedPosts();
            $this->collectItems();
            $this->createPosts();

            $this->includeComponentTemplate();
        } catch (SystemException $e) {
            // echo $e->getMessage();
        }

        return count($this->arItems);
    }

    protected function collectOrders()
    {
        $this->arOrders = [];

        $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
        $orderClassName = $registry->getOrderClassName();

        $filter = $this->getOrdersFilter();
        $getListParams = [
            'order' => [
                'ID' => 'desc',
            ],
            'filter' => $filter,
            'select' => [
                'ID',
                'LID',
                'DATE_INSERT',
                'ACCOUNT_NUMBER',
                'STATUS_ID',
            ],
        ];

        if ($this->arParams['PRODUCTS_PER_PAGE'] > 0) {
            $getListParams['limit'] = $this->arParams['PRODUCTS_PER_PAGE'] * 2; // min one product in order, may has ignored or inner products
            if ($getListParams['limit'] < 10) {
                $getListParams['limit'] = 10;
            }
        }

        $ordersIds = [];
        $res = new \CDBResult($orderClassName::getList($getListParams));
        while ($arOrder = $res->GetNext()) {
            $this->arOrders[$arOrder['ID']] = $arOrder;
            $this->arOrders[$arOrder['ID']]['BASKET_ITEMS'] = [];

            $ordersIds[] = $arOrder['ID'];
        }

        if ($ordersIds) {
            $basketClassName = $registry->getBasketClassName();
            $basketItems = $basketClassName::getList([
                'order' => [
                    'SORT' => 'asc',
                    'ID' => 'asc',
                ],
                'filter' => [
                    'ORDER_ID' => $ordersIds,
                ],
                'select' => [
                    'ID',
                    'ORDER_ID',
                    'PRODUCT_ID',
                    'NAME',
                    'DETAIL_PAGE_URL',
                    'TYPE',
                    'SET_PARENT_ID',
                    'MODULE',
                ],
            ]);
            while ($basketItem = $basketItems->fetch()) {
                if (\CSaleBasketHelper::isSetItem($basketItem)) {
                    continue;
                }

                $this->arOrders[$basketItem['ORDER_ID']]['BASKET_ITEMS'][$basketItem['ID']] = $basketItem;
            }
        }

        return $this->arOrders;
    }

    protected function getOrdersFilter()
    {
        $arFilter = [
            'LID' => $this->arResult['SITE_ID'],
            'USER_ID' => $this->arResult['USER_ID'],
            'CANCELED' => 'N',
            // '>=DATE_INSERT' => trim($_REQUEST['filter_date_from']),
        ];

        if ($this->arParams['ORDER_STATUSES']) {
            $arFilter['STATUS_ID'] = $this->arParams['ORDER_STATUSES'];
        }

        return $arFilter;
    }

    protected function collectIgnoredItems()
    {
        $this->arIgnoredItems = [];

        $result = VoteIgnoreTable::getList([
            'filter' => [
                '=USER_ID' => $this->arResult['USER_ID'],
                '=SITE_ID' => $this->arResult['SITE_ID'],
            ],
            'select' => [
                'ID',
                'PRODUCT_ID',
            ],
        ]);
        while ($arItem = $result->fetch()) {
            $this->arIgnoredItems[$arItem['PRODUCT_ID']] = $arItem;
        }

        return $this->arIgnoredItems;
    }

    protected function collectCommentedPosts()
    {
        $this->arCommentedPosts = [];

        $resBlog = \CBlogComment::GetList(
            [],
            [
                'AUTHOR_ID' => $this->arResult['USER_ID'],
                'PARENT_ID' => false,
            ],
            false,
            false,
            [
                'ID',
                'BLOG_ID',
                'POST_ID',
                'UF_ASPRO_COM_RATING',
                'UF_ASPRO_COM_OFFER_ID',
            ]
        );
        while ($comment = $resBlog->Fetch()) {
            $postId = $comment['POST_ID'];

            if (!isset($this->arCommentedPosts[$postId])) {
                $this->arCommentedPosts[$postId] = [];
            }
            $this->arCommentedPosts[$postId][] = $comment;
        }

        return $this->arCommentedPosts;
    }

    protected function createPosts()
    {
        if ($this->arItems) {
            $productsIdsWithoutPost = [];

            foreach ($this->arItems as $arItem) {
                $postId = $arItem['POST_ID'];
                if (!$postId) {
                    $elementId = (int) ($arItem['PRODUCT_ID'] ?: $arItem['ID']);
                    if ($elementId > 0) {
                        $productsIdsWithoutPost[$elementId] = $elementId;
                    }
                }
            }

            if ($productsIdsWithoutPost) {
                $blogId = $this->getBlogId();

                if (!$blogId) {
                    return;
                }

                $dbRes = \CIBlockElement::GetList(
                    [],
                    ['ID' => array_values($productsIdsWithoutPost)],
                    false,
                    false,
                    [
                        'ID',
                        'IBLOCK_ID',
                        'IBLOCK_SECTION_ID',
                        'CODE',
                        'EXTERNAL_ID',
                        'IBLOCK_CODE',
                        'IBLOCK_EXTERNAL_ID',
                        'IBLOCK_TYPE_ID',
                        'CREATED_BY',
                        'NAME',
                        'PREVIEW_TEXT',
                        'DETAIL_PAGE_URL',
                    ]
                );
                while ($arItem = $dbRes->GetNext()) {
                    $ownerId = 1;

                    if ($arItem['CREATED_BY']) {
                        $ownersIterator = \Bitrix\Main\UserTable::getList([
                            'filter' => ['=ID' => $arItem['CREATED_BY']],
                            'select' => ['ID'],
                        ]);
                        if ($owner = $ownersIterator->fetch()) {
                            $ownerId = $owner['ID'];
                        }
                        unset($owner, $ownersIterator);
                    }

                    $detailUrl = (string) ($arItem['~DETAIL_PAGE_URL'] ?? '');
                    $detailLink = ($detailUrl !== '' && !str_contains($detailUrl, '#'))
                        ? '[URL=http://'.$_SERVER['HTTP_HOST'].$detailUrl.']'.$arItem['~NAME']."[/URL]\n"
                        : $arItem['~NAME']."\n";

                    $arFields = [
                        'TITLE' => $arItem['~NAME'],
                        'DETAIL_TEXT' => $detailLink.
                            ($arItem['~PREVIEW_TEXT'] != '' ? $arItem['~PREVIEW_TEXT'] : '')."\n",
                        'PUBLISH_STATUS' => BLOG_PUBLISH_STATUS_PUBLISH,
                        'PERMS_POST' => [],
                        'PERMS_COMMENT' => [],
                        '=DATE_CREATE' => $GLOBALS['DB']->GetNowFunction(),
                        '=DATE_PUBLISH' => $GLOBALS['DB']->GetNowFunction(),
                        'AUTHOR_ID' => $ownerId,
                        'BLOG_ID' => $blogId,
                        'ENABLE_TRACKBACK' => 'N',
                    ];

                    $postId = (int) \CBlogPost::Add($arFields);
                    if ($postId) {
                        \CIBlockElement::SetPropertyValues(
                            $arItem['ID'],
                            $arItem['IBLOCK_ID'],
                            [
                                \CIBlockPropertyTools::CODE_BLOG_POST => $postId,
                            ]
                        );

                        foreach ($this->arItems as $itemKey => $item) {
                            $elementId = (int) ($item['PRODUCT_ID'] ?: $item['ID']);
                            if ($elementId === (int) $arItem['ID']) {
                                $this->arItems[$itemKey]['POST_ID'] = $postId;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function getBlogId()
    {
        $blogIterator = \CBlog::GetList(
            [],
            [
                'URL' => $this->arParams['BLOG_URL'],
            ],
            false,
            false,
            [
                'ID',
            ]
        );
        $arBlog = $blogIterator->Fetch();

        return $arBlog ? $arBlog['ID'] : false;
    }

    protected function collectItems()
    {
        $this->arItems = [];

        if ($this->arOrders) {
            $arOffersIblocks = [];

            if (Solution::isSaleMode()) {
                if (Loader::includeModule('catalog')) {
                    $rsCatalog = \CCatalog::GetList(['sort' => 'asc']);
                    while ($ar = $rsCatalog->Fetch()) {
                        if ($ar['OFFERS_IBLOCK_ID']) {
                            $arOffersIblocks[] = $ar['OFFERS_IBLOCK_ID'];
                        }
                    }
                }
            }

            $arProductsIDs = $arProducts = $arOffersIds = $arOffers = $arProductIdByOfferId = [];
            foreach ($this->arOrders as $arOrder) {
                if ($arOrder['BASKET_ITEMS']) {
                    $arProductsIDs = array_merge($arProductsIDs, array_column($arOrder['BASKET_ITEMS'], 'PRODUCT_ID'));
                }
            }
            $arProductsIDs = array_unique($arProductsIDs);

            if ($arProductsIDs) {
                $dbRes = \CIBlockElement::GetList(
                    [],
                    ['ID' => $arProductsIDs],
                    false,
                    false,
                    [
                        'ID',
                        'IBLOCK_ID',
                        'IBLOCK_SECTION_ID',
                        'CODE',
                        'EXTERNAL_ID',
                        'IBLOCK_CODE',
                        'IBLOCK_EXTERNAL_ID',
                        'IBLOCK_TYPE_ID',
                        'NAME',
                        'PREVIEW_PICTURE',
                        'DETAIL_PICTURE',
                        'DETAIL_PAGE_URL',
                    ]
                );
                while ($arItem = $dbRes->GetNext()) {
                    if (in_array($arItem['IBLOCK_ID'], $arOffersIblocks)) {
                        if (!isset($arOffersIds[$arItem['IBLOCK_ID']])) {
                            $arOffersIds[$arItem['IBLOCK_ID']] = [];
                        }
                        $arOffersIds[$arItem['IBLOCK_ID']][] = $arItem['ID'];

                        $arOffers[$arItem['ID']] = $arItem;
                    } else {
                        $arProducts[$arItem['ID']] = $arItem;
                    }
                }

                if ($arOffersIds) {
                    $arOffersIdsByProductId = [];
                    foreach ($arOffersIds as $offerIblockId => $arOffersIds) {
                        $arProductsList = \CCatalogSKU::getProductList($arOffersIds, $offerIblockId);
                        if ($arProductsList) {
                            foreach ($arProductsList as $offerId => $arOfferInfo) {
                                if (!isset($arOffersIdsByProductId[$arOfferInfo['ID']])) {
                                    $arOffersIdsByProductId[$arOfferInfo['ID']] = [];
                                }

                                $arOffersIdsByProductId[$arOfferInfo['ID']][] = $offerId;
                                $arProductIdByOfferId[$offerId] = $arOfferInfo['ID'];
                            }
                        }
                    }

                    if ($arOffersIdsByProductId) {
                        $dbRes = \CIBlockElement::GetList(
                            [],
                            ['ID' => array_keys($arOffersIdsByProductId)],
                            false,
                            false,
                            [
                                'ID',
                                'IBLOCK_ID',
                                'IBLOCK_SECTION_ID',
                                'CODE',
                                'EXTERNAL_ID',
                                'IBLOCK_CODE',
                                'IBLOCK_EXTERNAL_ID',
                                'IBLOCK_TYPE_ID',
                                'NAME',
                                'PREVIEW_PICTURE',
                                'DETAIL_PICTURE',
                                'DETAIL_PAGE_URL',
                            ]
                        );
                        while ($arItem = $dbRes->GetNext()) {
                            if (
                                !$arItem['PREVIEW_PICTURE']
                                && !$arItem['DETAIL_PICTURE']
                            ) {
                                foreach ($arOffersIdsByProductId[$arItem['ID']] as $offerId) {
                                    $arOffer = $arOffers[$offerId] ?? null;
                                    if (
                                        is_array($arOffer)
                                        && (
                                            $arOffer['PREVIEW_PICTURE']
                                            || $arOffer['DETAIL_PICTURE']
                                        )
                                    ) {
                                        $arItem['PREVIEW_PICTURE'] = $arOffer['PREVIEW_PICTURE'];
                                        $arItem['DETAIL_PICTURE'] = $arOffer['DETAIL_PICTURE'];
                                        break;
                                    }
                                }
                            }

                            $arProducts[$arItem['ID']] = $arItem;
                        }
                    }
                }

                $arProductsIDsByIblockId = [];

                foreach ($arProducts as $arItem) {
                    $iblockId = $arItem['IBLOCK_ID'];

                    if (!isset($arProductsIDsByIblockId[$iblockId])) {
                        $arProductsIDsByIblockId[$iblockId] = [];
                    }

                    $arProductsIDsByIblockId[$iblockId][] = $arItem['ID'];
                }

                if ($arProductsIDsByIblockId) {
                    foreach ($arProductsIDsByIblockId as $iblockId => $arProductsIds) {
                        $dbRes = \CIBlockElement::GetList(
                            [],
                            [
                                'ID' => $arProductsIds,
                                'IBLOCK_ID' => $iblockId,
                            ],
                            false,
                            false,
                            [
                                'ID',
                                'IBLOCK_ID',
                                'PROPERTY_'.\CIBlockPropertyTools::CODE_BLOG_POST,
                            ]
                        );
                        while ($arItem = $dbRes->Fetch()) {
                            $postId = $arItem['PROPERTY_'.\CIBlockPropertyTools::CODE_BLOG_POST.'_VALUE'];

                            if (isset($arProducts[$arItem['ID']])) {
                                $arProducts[$arItem['ID']]['POST_ID'] = $postId;
                            }
                        }
                    }
                }
            }

            foreach ($this->arOrders as $arOrder) {
                if ($arOrder['BASKET_ITEMS']) {
                    foreach ($arOrder['BASKET_ITEMS'] as $arBasketItem) {
                        $isOffer = isset($arProductIdByOfferId[$arBasketItem['PRODUCT_ID']]);
                        $productId = $isOffer ? $arProductIdByOfferId[$arBasketItem['PRODUCT_ID']] : $arBasketItem['PRODUCT_ID'];

                        $arItem = [
                            'ID' => $arBasketItem['PRODUCT_ID'],
                            'PRODUCT_ID' => $productId,
                            'NAME' => $arBasketItem['NAME'],
                            'DETAIL_PAGE_URL' => $arBasketItem['DETAIL_PAGE_URL'],
                            'TYPE' => $arBasketItem['TYPE'],
                            'SET_PARENT_ID' => $arBasketItem['SET_PARENT_ID'],
                            'POST_ID' => false,
                        ];

                        if (isset($arProducts[$productId])) {
                            $arItem = array_merge(
                                $arItem,
                                [
                                    'IBLOCK_ID' => $arProducts[$productId]['IBLOCK_ID'],
                                    'PREVIEW_PICTURE' => $arOffers[$arBasketItem['PRODUCT_ID']]['PREVIEW_PICTURE'] ?? $arProducts[$productId]['PREVIEW_PICTURE'],
                                    'DETAIL_PICTURE' => $arOffers[$arBasketItem['PRODUCT_ID']]['DETAIL_PICTURE'] ?? $arProducts[$productId]['DETAIL_PICTURE'],
                                    'POST_ID' => $arProducts[$productId]['POST_ID'],
                                ]
                            );

                            $detailUrl = (string) ($isOffer
                                ? ($arOffers[$arBasketItem['PRODUCT_ID']]['DETAIL_PAGE_URL'] ?? '')
                                : ($arProducts[$productId]['DETAIL_PAGE_URL'] ?? ''));
                            if ($detailUrl !== '' && !str_contains($detailUrl, '#')) {
                                $arItem['DETAIL_PAGE_URL'] = $detailUrl;
                            }
                        } else {
                            if ($arBasketItem['MODULE'] == 'sale') {
                                // inner payment
                                continue;
                            }
                        }

                        $postId = $arItem['POST_ID'];

                        if (
                            !isset($this->arItems[$arBasketItem['PRODUCT_ID']])
                            && !isset($this->arIgnoredItems[$arBasketItem['PRODUCT_ID']])
                            && (
                                !isset($this->arCommentedPosts[$postId])
                                || (
                                    $productId != $arBasketItem['PRODUCT_ID']
                                    && !$this->isPostHasOfferID($postId, $arBasketItem['PRODUCT_ID'])
                                )
                            )
                        ) {
                            $this->arItems[$arBasketItem['PRODUCT_ID']] = $arItem;

                            if ($this->arParams['PRODUCTS_PER_PAGE'] > 0) {
                                if (count($this->arItems) >= $this->arParams['PRODUCTS_PER_PAGE']) {
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $this->arItems;
    }

    protected function isPostHasOfferID($postId, $oid): bool
    {
        return array_search($oid, array_column($this->arCommentedPosts[$postId], 'UF_ASPRO_COM_OFFER_ID')) !== false;
    }
}
