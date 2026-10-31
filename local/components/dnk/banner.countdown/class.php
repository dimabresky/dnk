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
        $arParams['SUBTITLE'] = trim((string) ($arParams['SUBTITLE'] ?? ''));
        $arParams['PICTURE'] = trim((string) ($arParams['PICTURE'] ?? ''));
        $arParams['COLOR'] = trim((string) ($arParams['COLOR'] ?? ''));
        $arParams['TIMER_COLOR'] = trim((string) ($arParams['TIMER_COLOR'] ?? ''));
        $arParams['TIMER_BG'] = trim((string) ($arParams['TIMER_BG'] ?? ''));
        $arParams['TIMER_SHADOW'] = trim((string) ($arParams['TIMER_SHADOW'] ?? ''));
        $arParams['CACHE_TYPE'] = 'N';

        return $arParams;
    }

    public function executeComponent()
    {
        $datetime = $this->arParams['DATETIME'];
        $endTs = $datetime !== '' ? (int) MakeTimeStamp($datetime) : 0;
        if ($endTs <= time()) {
            return;
        }

        $picture = $this->arParams['PICTURE'];
        if (!preg_match('#^/(?!/)[^\s]*$#', $picture)) {
            $picture = '';
        }

        $this->arResult = [
            'DATETIME' => $datetime,
            'END_TS' => $endTs * 1000,
            'TITLE' => $this->arParams['TITLE'],
            'SUBTITLE' => $this->arParams['SUBTITLE'],
            'PICTURE' => $picture,
            'COLOR' => $this->arParams['COLOR'],
            'TIMER_COLOR' => $this->arParams['TIMER_COLOR'],
            'TIMER_BG' => $this->arParams['TIMER_BG'],
            'TIMER_SHADOW' => $this->arParams['TIMER_SHADOW'],
        ];

        $this->includeComponentTemplate();
    }
}
