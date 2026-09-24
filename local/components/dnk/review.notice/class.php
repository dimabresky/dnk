<?php

declare(strict_types=1);

use Aspro\Premier\Functions\Extensions;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * Всплывающая просьба оставить отзыв о случайном товаре из завершённых заказов.
 */
class DnkReviewNoticeComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $hours = (int) ($arParams['INTERVAL_HOURS'] ?? 12);
        if ($hours < 1) {
            $hours = 12;
        }

        $arParams['INTERVAL_HOURS'] = $hours;

        return $arParams;
    }

    public function executeComponent(): void
    {
        $this->arResult = [];

        global $USER;

        if (is_object($USER) && $USER->IsAuthorized()) {
            $userId = (int) $USER->GetID();
            if ($userId > 0 && Loader::includeModule('aspro.premier')) {
                $products = Utils::getProductsAwaitingReview($userId, defined('SITE_ID') ? (string) SITE_ID : '');
                $product = $this->pickProductWithPicture($products);
                if ($product !== null) {
                    Extensions::init('notice');
                    $this->arResult = [
                        'INTERVAL_HOURS' => (int) $this->arParams['INTERVAL_HOURS'],
                        'TITLE' => (string) Loc::getMessage('DNK_REVIEW_NOTICE_TITLE'),
                        'DETAIL' => (string) Loc::getMessage('DNK_REVIEW_NOTICE_DETAIL'),
                        'IMAGE' => $product['picture'],
                        'LINK' => $product['url'],
                    ];
                }
            }
        }

        $this->includeComponentTemplate();
    }

    /**
     * @param list<array{id: int, name: string, picture: string, url: string}> $products
     * @return array{id: int, name: string, picture: string, url: string}|null
     */
    private function pickProductWithPicture(array $products): ?array
    {
        $withPicture = [];
        foreach ($products as $product) {
            if (trim((string) ($product['picture'] ?? '')) === '') {
                continue;
            }

            $withPicture[] = $product;
        }

        if ($withPicture === []) {
            return null;
        }

        return $withPicture[array_rand($withPicture)];
    }
}
