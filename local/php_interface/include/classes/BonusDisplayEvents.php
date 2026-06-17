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

    const getWrapperBonusAmount = (wrapper) => {
        if (wrapper.textContent.includes('#BONUSES#')) {
            return null;
        }

        const label = wrapper.querySelector('label');
        if (label) {
            return parseBonusAmount(label.textContent);
        }

        const textEl = wrapper.querySelector('.aspro-bonus__text');
        if (textEl) {
            return parseBonusAmount(textEl.textContent);
        }

        return parseBonusAmount(wrapper.textContent);
    };

    const revealValidBonusBlocks = () => {
        document.querySelectorAll('.aspro-bonus-wrapper').forEach((wrapper) => {
            const amount = getWrapperBonusAmount(wrapper);
            if (amount === null || amount <= 0) {
                return;
            }

            const bonusNode = wrapper.querySelector('.aspro-bonus');
            bonusNode?.removeAttribute('hidden');
            bonusNode?.removeAttribute('aria-hidden');
        });
    };

    const cleanupBonusBlocks = ({ removeUnreplaced = false } = {}) => {
        document.querySelectorAll('.aspro-bonus-wrapper').forEach((wrapper) => {
            const amount = getWrapperBonusAmount(wrapper);

            if (amount === null) {
                if (removeUnreplaced) {
                    wrapper.remove();
                }
                return;
            }

            if (amount <= 0) {
                wrapper.remove();
            }
        });

        revealValidBonusBlocks();
    };

    let componentReadyHandled = false;
    let cleanupPollId = null;

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

    const hasUnreplacedBonusPlaceholders = () => {
        return [...document.querySelectorAll('.aspro-bonus-wrapper')].some((wrapper) => {
            return wrapper.textContent.includes('#BONUSES#');
        });
    };

    const scheduleCleanup = () => {
        cleanupBonusBlocks();

        if (cleanupPollId !== null) {
            return;
        }

        let attempts = 0;
        const maxAttempts = 60;
        cleanupPollId = setInterval(() => {
            attempts += 1;
            cleanupBonusBlocks();

            if (!hasUnreplacedBonusPlaceholders() || attempts >= maxAttempts) {
                clearInterval(cleanupPollId);
                cleanupPollId = null;
                cleanupBonusBlocks({ removeUnreplaced: true });
            }
        }, 50);
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
    }
})();
</script>
HTML
        , true);
    }
}
