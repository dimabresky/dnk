<?php

declare(strict_types=1);

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\BasketBonusService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * Блок применения бонусов на странице корзины.
 */
class DnkBasketBonusApplyComponent extends CBitrixComponent implements Controllerable
{
    /** @inheritdoc */
    public function configureActions(): array
    {
        return [
            'apply' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\Authentication(),
                ],
            ],
            'reset' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    public function executeComponent(): void
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized()) {
            return;
        }

        \CJSCore::Init(['ajax']);

        $this->arResult = BasketBonusService::getUiData();
        $this->includeComponentTemplate();
    }

    /**
     * Применить бонусы к корзине.
     *
     * @param float $amount Сумма списания
     * @return array{success: bool, message?: string, ui?: array}
     */
    public function applyAction(float $amount = 0): array
    {
        if ((int)CurrentUser::get()->getId() <= 0) {
            return ['success' => false, 'message' => 'not_authorized'];
        }

        return BasketBonusService::apply($amount);
    }

    /**
     * Сбросить применённые бонусы.
     *
     * @return array{success: bool, message?: string, ui?: array}
     */
    public function resetAction(): array
    {
        if ((int)CurrentUser::get()->getId() <= 0) {
            return ['success' => false, 'message' => 'not_authorized'];
        }

        return BasketBonusService::reset();
    }
}
