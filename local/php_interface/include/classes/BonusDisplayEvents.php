<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\Page\Asset;

/**
 * Кастомизация отображения будущих начисляемых бонусов Aspro.
 */
final class BonusDisplayEvents
{
    private const BONUS_INFO_URL = '/bonus/';

    private static bool $bonusCleanupScriptInjected = false;

    public static function register(): void
    {
        $em = EventManager::getInstance();

        $em->addEventHandler(
            'aspro.bonus',
            'getPatternBonusShow',
            [self::class, 'onGetPatternBonusShow']
        );

        $em->addEventHandler('main', 'OnEpilog', [self::class, 'onEpilogInjectReplaceBonusesPatch']);
    }

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

    public static function onEpilogInjectReplaceBonusesPatch(): void
    {
        if (self::$bonusCleanupScriptInjected) {
            return;
        }

        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        self::$bonusCleanupScriptInjected = true;

        Asset::getInstance()->addString(<<<'HTML'
<script data-skip-moving="true">
(function () {
    const parseBonusAmount = (value) => {
        const normalized = String(value ?? '')
            .replace(/\u00a0/g, ' ')
            .replace(/[^\d.,-]/g, '')
            .replace(',', '.');

        const amount = parseFloat(normalized);

        return Number.isFinite(amount) ? amount : 0;
    };

    const revealValidBonusBlocks = () => {
        document.querySelectorAll('.aspro-bonus-wrapper label').forEach((label) => {
            if (parseBonusAmount(label.textContent) <= 0) {
                return;
            }

            const bonusNode = label.closest('.aspro-bonus');
            bonusNode?.removeAttribute('hidden');
            bonusNode?.removeAttribute('aria-hidden');
        });
    };

    const cleanupBonusBlocks = () => {
        document.querySelectorAll('.aspro-bonus-wrapper').forEach((wrapper) => {
            if (wrapper.textContent.includes('#BONUSES#')) {
                wrapper.remove();
                return;
            }

            const label = wrapper.querySelector('label');
            if (label && parseBonusAmount(label.textContent) <= 0) {
                wrapper.remove();
            }
        });

        revealValidBonusBlocks();
    };

    let componentReadyHandled = false;

    const onBonusComponentReady = () => {
        if (typeof replaceBonuses !== 'function') {
            return false;
        }

        if (!componentReadyHandled) {
            componentReadyHandled = true;
            scheduleCleanup();
        }

        return true;
    };

    const scheduleCleanup = () => {
        cleanupBonusBlocks();
        setTimeout(cleanupBonusBlocks, 0);
        setTimeout(cleanupBonusBlocks, 100);
        setTimeout(cleanupBonusBlocks, 300);
    };

    const bootstrap = () => {
        if (onBonusComponentReady()) {
            return;
        }

        if (typeof BX === 'undefined' || typeof BX.loadExt !== 'function') {
            setTimeout(bootstrap, 10);
            return;
        }

        BX.loadExt(['aspro.bonus.component']).then(scheduleCleanup);
    };

    bootstrap();

    const readyInterval = setInterval(() => {
        if (onBonusComponentReady()) {
            clearInterval(readyInterval);
        }
    }, 10);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleCleanup);
    } else {
        scheduleCleanup();
    }
})();
</script>
HTML
        , true);
    }
}
