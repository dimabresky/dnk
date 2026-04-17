<?php

use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Узкий промо-баннер в шапке: инфоблок, таймер, dismiss, картинки desktop/mobile.
 */
class DnkHeaderPromoBarComponent extends CBitrixComponent
{
    private const BAR_HEIGHT = 45;

    public function executeComponent()
    {
        
        if (!CModule::IncludeModule('iblock')) {
            ShowError(GetMessage('DNK_HEADER_PROMO_BAR_ERR_IBLOCK'));
            return;
        }

        $iblockId = (int) ($this->arParams['IBLOCK_ID'] ?? 0);
        if ($iblockId <= 0 && defined('DNK_HEADER_PROMO_IBLOCK_ID')) {
            $iblockId = (int) DNK_HEADER_PROMO_IBLOCK_ID;
        }

        $cacheTime = (int) ($this->arParams['CACHE_TIME'] ?? 120);
        $cachePath = '/dnk/header.promo.bar';
        $cacheId = md5(implode('|', [SITE_ID, LANGUAGE_ID, $iblockId]));

        $this->arResult['ITEM'] = null;

        if ($iblockId <= 0) {
            $this->includeComponentTemplate();
            return;
        }

        if ($this->startResultCache($cacheTime, $cacheId, $cachePath)) {
            global $CACHE_MANAGER;
            $CACHE_MANAGER->StartTagCache($cachePath);
            $CACHE_MANAGER->RegisterTag('iblock_id_' . $iblockId);
            $CACHE_MANAGER->EndTagCache();

            $this->arResult['ITEM'] = $this->loadItem($iblockId);
        }

        $this->includeComponentTemplate();
    }

    /**
     * @param int $iblockId
     * @return array|null
     */
    private function loadItem(int $iblockId)
    {
        $rs = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'DESC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_TEXT_TYPE', 'IBLOCK_ID']
        );

        if (!($ob = $rs->GetNextElement())) {
            return null;
        }

        $fields = $ob->GetFields();
        $props = $ob->GetProperties();

        $link = trim((string) ($props['LINK']['VALUE'] ?? ''));
        $linkTargetXml = (string) ($props['LINK_TARGET']['VALUE_XML_ID'] ?? '_self');
        if ($linkTargetXml !== '_blank') {
            $linkTargetXml = '_self';
        }

        $bg = trim((string) ($props['BG_COLOR']['VALUE'] ?? ''));
        $fg = trim((string) ($props['TEXT_COLOR']['VALUE'] ?? ''));

        $showTimer = ($props['SHOW_TIMER']['VALUE_XML_ID'] ?? '') === 'Y';
        $timerEndTs = 0;
        if (!empty($props['TIMER_END']['VALUE'])) {
            $timerEndTs = $this->parseDateTimeToTs((string) $props['TIMER_END']['VALUE']);
        }
        if ($timerEndTs <= time()) {
            $showTimer = false;
        }

        $timerLabel = trim((string) ($props['TIMER_LABEL']['VALUE'] ?? ''));

        $allowDismiss = ($props['ALLOW_DISMISS']['VALUE_XML_ID'] ?? '') === 'Y';
        $dismissKey = trim((string) ($props['DISMISS_KEY']['VALUE'] ?? ''));
        if ($dismissKey === '') {
            $dismissKey = 'dnk_hp_' . (int) $fields['ID'];
        }

        $alt = trim((string) ($props['IMAGE_ALT']['VALUE'] ?? ''));
        if ($alt === '') {
            $alt = (string) $fields['NAME'];
        }

        $imgDesktop = $this->resizeFileProperty($props['IMAGE_DESKTOP']['VALUE'] ?? null);
        $imgMobile = $this->resizeFileProperty($props['IMAGE_MOBILE']['VALUE'] ?? null);

        $fontSizeProp = Utils::sanitizeCssFontSize(trim((string) ($props['FONT_SIZE']['VALUE'] ?? '')));

        $text = trim((string) ($fields['PREVIEW_TEXT'] ?? ''));

        $hasTimer = $showTimer && $timerEndTs > time();
        $hasVisual = $text !== '' || $imgDesktop['SRC'] !== '' || $imgMobile['SRC'] !== '';
        if (!$hasVisual && !$hasTimer) {
            return null;
        }

        return [
            'ID' => (int) $fields['ID'],
            'NAME' => (string) $fields['NAME'],
            'TEXT' => $text,
            'PREVIEW_TEXT_TYPE' => (string) ($fields['PREVIEW_TEXT_TYPE'] ?? 'text'),
            'LINK' => $link,
            'LINK_TARGET' => $linkTargetXml,
            'BG_COLOR' => $bg,
            'TEXT_COLOR' => $fg,
            'SHOW_TIMER' => $showTimer,
            'TIMER_END_TS' => $timerEndTs,
            'TIMER_LABEL' => $timerLabel,
            'ALLOW_DISMISS' => $allowDismiss,
            'DISMISS_KEY' => $dismissKey,
            'IMAGE_ALT' => $alt,
            'IMAGE_DESKTOP' => $imgDesktop,
            'IMAGE_MOBILE' => $imgMobile,
            'HAS_IMAGE_DESKTOP' => $imgDesktop['SRC'] !== '',
            'HAS_IMAGE_MOBILE' => $imgMobile['SRC'] !== '',
            'FONT_SIZE' => $fontSizeProp,
        ];
    }

    /**
     * @param mixed $fileId
     * @return array{SRC: string, WIDTH: int, HEIGHT: int}
     */
    private function resizeFileProperty($fileId): array
    {
        if (is_array($fileId)) {
            $fileId = (int) reset($fileId);
        } else {
            $fileId = (int) $fileId;
        }
        if ($fileId <= 0) {
            return ['SRC' => '', 'WIDTH' => 0, 'HEIGHT' => 0];
        }

        $file = CFile::GetFileArray($fileId);
        if (!$file || !is_array($file)) {
            return ['SRC' => '', 'WIDTH' => 0, 'HEIGHT' => 0];
        }

        $ext = strtolower((string) pathinfo($file['FILE_NAME'] ?? $file['ORIGINAL_NAME'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            $src = (string) CFile::GetPath($fileId);
            return ['SRC' => $src, 'WIDTH' => (int) self::BAR_HEIGHT, 'HEIGHT' => (int) self::BAR_HEIGHT];
        }

        $res = CFile::ResizeImageGet(
            $fileId,
            ['width' => 2400, 'height' => self::BAR_HEIGHT],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true
        );

        if (!empty($res['src'])) {
            return [
                'SRC' => (string) $res['src'],
                'WIDTH' => (int) ($res['width'] ?? 0),
                'HEIGHT' => (int) ($res['height'] ?? self::BAR_HEIGHT),
            ];
        }

        $src = (string) CFile::GetPath($fileId);
        return ['SRC' => $src, 'WIDTH' => 0, 'HEIGHT' => self::BAR_HEIGHT];
    }

    /**
     * @param string $value
     * @return int
     */
    private function parseDateTimeToTs(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }
        $ts = MakeTimeStamp($value);
        if ($ts > 0) {
            return (int) $ts;
        }
        $t2 = strtotime($value);
        return $t2 > 0 ? (int) $t2 : 0;
    }
}
