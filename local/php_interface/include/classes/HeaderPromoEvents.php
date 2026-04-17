<?php

namespace Dnk\PhpInterface;

/**
 * Вставка компонента узкого промо-баннера сразу после открывающего тега body (шаблон header в репозитории не выгружен).
 */
class HeaderPromoEvents
{
    /**
     * @param string $content
     * @return void
     */
    public static function onEndBufferContent(&$content)
    {
        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return;
        }
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (strpos($content, '</body>') === false) {
            return;
        }
        if (strpos($content, 'dnk-header-promo-bar') !== false) {
            return;
        }
        global $APPLICATION;
        if (!is_object($APPLICATION) || !($APPLICATION instanceof \CMain)) {
            return;
        }
        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            return;
        }
        if (!defined('DNK_HEADER_PROMO_IBLOCK_ID') || (int) DNK_HEADER_PROMO_IBLOCK_ID <= 0) {
            return;
        }

        ob_start();
        $APPLICATION->IncludeComponent(
            'dnk:header.promo.bar',
            '.default',
            [
                'IBLOCK_ID' => (int) DNK_HEADER_PROMO_IBLOCK_ID,
                'CACHE_TIME' => 120,
                'MOBILE_BREAKPOINT' => 768,
                'HIDE_ON_EXPIRE' => 'Y',
            ],
            false,
            ['HIDE_ICONS' => 'Y']
        );
        $html = ob_get_clean();
        if ($html === '' || trim($html) === '') {
            return;
        }

        $content = preg_replace_callback(
            '/(<body[^>]*>)/i',
            static function (array $m) use ($html) {
                return $m[1] . $html;
            },
            $content,
            1
        );
    }
}
