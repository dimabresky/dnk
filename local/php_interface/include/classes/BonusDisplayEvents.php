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

    private static bool $replaceBonusesPatchInjected = false;

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
        if (self::$replaceBonusesPatchInjected) {
            return;
        }

        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return;
        }

        if (PHP_SAPI === 'cli') {
            return;
        }

        self::$replaceBonusesPatchInjected = true;

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

    const removeBonusWrapper = (node) => {
        const wrapper = node?.closest?.('.aspro-bonus-wrapper');

        if (wrapper) {
            wrapper.remove();
            return;
        }

        node?.remove?.();
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
    };

    const patchReplaceBonuses = () => {
        if (typeof replaceBonuses !== 'function') {
            return false;
        }

        if (replaceBonuses.__dnkZeroBonusPatched) {
            cleanupBonusBlocks();
            return true;
        }

        replaceBonuses = (selector, bonuses) => {
            const amount = parseBonusAmount(bonuses);

            document.querySelectorAll(selector)?.forEach((node) => {
                if (amount <= 0) {
                    removeBonusWrapper(node);
                    return;
                }

                node.innerHTML = node.innerHTML.replace('#BONUSES#', `<label>${bonuses}</label>`);
                node.querySelector('.aspro-bonus')?.removeAttribute('hidden');
                node.querySelector('.aspro-bonus')?.removeAttribute('aria-hidden');
            });
        };

        replaceBonuses.__dnkZeroBonusPatched = true;
        cleanupBonusBlocks();

        return true;
    };

    const scheduleCleanup = () => {
        setTimeout(cleanupBonusBlocks, 0);
        setTimeout(cleanupBonusBlocks, 100);
    };

    const bootstrap = () => {
        if (patchReplaceBonuses()) {
            scheduleCleanup();
            return;
        }

        if (typeof BX === 'undefined' || typeof BX.loadExt !== 'function') {
            setTimeout(bootstrap, 50);
            return;
        }

        BX.loadExt(['aspro.bonus.component']).then(() => {
            patchReplaceBonuses();
            scheduleCleanup();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bootstrap);
    } else {
        bootstrap();
    }
})();
</script>
HTML
        , true);
    }
}
