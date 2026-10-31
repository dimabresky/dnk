<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Крупный обратный отсчёт по центру слайда. Данные только из параметров.
 */
class DnkBannerCountdownComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams['DATETIME'] = trim((string) ($arParams['DATETIME'] ?? ''));
        $arParams['TITLE'] = trim((string) ($arParams['TITLE'] ?? ''));
        $arParams['PICTURE'] = trim((string) ($arParams['PICTURE'] ?? ''));
        $arParams['COLOR'] = trim((string) ($arParams['COLOR'] ?? ''));
        $arParams['CACHE_TYPE'] = 'N';

        return $arParams;
    }

    public function executeComponent()
    {
        $datetime = $this->arParams['DATETIME'];
        if ($datetime === '' || !MakeTimeStamp($datetime)) {
            return;
        }

        $color = ltrim($this->arParams['COLOR'], '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            $color = 'ffffff';
        }

        $picture = $this->arParams['PICTURE'];
        if (!preg_match('#^/(?!/)[^\s]*$#', $picture)) {
            $picture = '';
        }

        $this->arResult = [
            'DATETIME' => $datetime,
            'TITLE' => $this->arParams['TITLE'],
            'PICTURE' => $picture,
            'COLOR' => '#' . strtolower($color),
        ];

        $this->includeComponentTemplate();
    }
}
