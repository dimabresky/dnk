<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Горизонтальный блок логотипов платёжных систем и бэйджей 3-D Secure.
 */
class DnkPaymentLogosComponent extends CBitrixComponent
{
    private const IMAGES_SUBDIR = '/local/images/payments/';

    public function executeComponent()
    {
        $this->arResult['STRIP_SRC'] = null;
        $this->arResult['ITEMS'] = [];
        $this->arResult['BADGES'] = [];

        $docRoot = \Bitrix\Main\Application::getDocumentRoot();
        $baseUrl = self::IMAGES_SUBDIR;
        $baseFs = $docRoot . $baseUrl;

        $useStrip = ($this->arParams['USE_STRIP_IMAGE'] ?? 'N') === 'Y';
        $stripFile = $baseFs . 'payment-methods-strip.png';
        if ($useStrip && is_file($stripFile)) {
            $this->arResult['STRIP_SRC'] = $baseUrl . 'payment-methods-strip.png';
            $this->arResult['STRIP_ALT'] = (string) ($this->arParams['STRIP_ALT'] ?? 'Способы оплаты');
            $this->includeComponentTemplate();
            return;
        }

        $mainDefs = [
            ['file' => 'visa.png', 'alt' => 'Visa'],
            ['file' => 'mastercard.png', 'alt' => 'Mastercard'],
            ['file' => 'belkart.png', 'alt' => 'Белкарт'],
            ['file' => 'bepaid.png', 'alt' => 'bePaid'],
        ];

        $badgeDefs = [
            ['file' => 'visa-secure.png', 'alt' => 'Visa Secure'],
            ['file' => 'mastercard-id-check.png', 'alt' => 'Mastercard ID Check'],
            ['file' => 'belkart-internet-password.png', 'alt' => 'Белкарт интернет пароль'],
        ];

        foreach ($mainDefs as $row) {
            $pathFs = $baseFs . $row['file'];
            if (is_file($pathFs)) {
                $this->arResult['ITEMS'][] = [
                    'src' => $baseUrl . $row['file'],
                    'alt' => $row['alt'],
                ];
            }
        }

        if (($this->arParams['SHOW_BADGES'] ?? 'Y') === 'Y') {
            foreach ($badgeDefs as $row) {
                $pathFs = $baseFs . $row['file'];
                if (is_file($pathFs)) {
                    $this->arResult['BADGES'][] = [
                        'src' => $baseUrl . $row['file'],
                        'alt' => $row['alt'],
                    ];
                }
            }
        }

        $this->includeComponentTemplate();
    }
}
