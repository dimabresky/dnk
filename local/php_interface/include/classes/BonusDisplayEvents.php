<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Кастомизация отображения будущих начисляемых бонусов Aspro.
 */
final class BonusDisplayEvents
{
    private const BONUS_INFO_URL = '/bonus/';

    /**
     * Подменяет HTML-паттерн aspro:bonus.show на ссылку с текстом «+N бонусов на счёт».
     */
    public static function onGetPatternBonusShow(Event $event): EventResult
    {
        $path = (string)($event->getParameter('PATH') ?? '');
        $iconPath = htmlspecialcharsbx($path . '/images/bonuses.svg');
        $bonusUrl = htmlspecialcharsbx(self::BONUS_INFO_URL);

        $pattern = <<<HTML
            <a href="{$bonusUrl}" class="aspro-bonus__link">
                <img src="{$iconPath}" data-src="" class="aspro-bonus__icon" alt=""/>
                <span class="aspro-bonus__text">+#BONUSES# бонусов на счёт</span>
            </a>
        HTML;

        return new EventResult(EventResult::SUCCESS, ['PATTERN' => $pattern]);
    }
}
