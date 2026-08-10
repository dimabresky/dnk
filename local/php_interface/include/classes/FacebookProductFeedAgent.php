<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use CFile;
use CIBlockElement;
use CIBlockSection;

/**
 * Агент: генерация Facebook/Meta Commerce RSS feed upload/dnk_facebook_products_feed.xml.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\FacebookProductFeedAgent::runFacebookProductFeedAgent();
 * Интервал — DNK_FACEBOOK_PRODUCT_FEED_AGENT_INTERVAL (сек).
 *
 * @see https://www.facebook.com/business/help/120325381656392?id=725943027795860
 */
final class FacebookProductFeedAgent
{
    private const FEED_FILENAME = 'dnk_facebook_products_feed.xml';
    private const FEED_OUTPUT_DIR = 'upload';
    private const GOOGLE_NS = 'http://base.google.com/ns/1.0';
    private const DEFAULT_CURRENCY = 'BYN';
    private const DEFAULT_CONDITION = 'new';
    private const TITLE_MAX_LENGTH = 200;
    private const DESCRIPTION_MAX_LENGTH = 9999;
    private const BRAND_MAX_LENGTH = 100;

    public static function runFacebookProductFeedAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\FacebookProductFeedAgent::runFacebookProductFeedAgent();";

        self::generateFeed();

        return $return;
    }

    /**
     * Генерирует Facebook feed и возвращает количество записей <item>.
     */
    public static function generateFeed(): int
    {
        if (!defined('DNK_CATALOG_IBLOCK_ID') || !defined('DNK_SITE_URL')) {
            return 0;
        }
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            return 0;
        }

        $catalogIblockId = (int) DNK_CATALOG_IBLOCK_ID;
        $siteUrl = rtrim((string) DNK_SITE_URL, '/');
        $channelTitle = defined('DNK_PRODUCT_FEED_CHANNEL_TITLE')
            ? (string) DNK_PRODUCT_FEED_CHANNEL_TITLE
            : 'DNK.BY';
        if ($channelTitle === '') {
            $channelTitle = 'DNK.BY';
        }
        $siteId = self::resolveSiteId();
        $defaultBrand = self::truncate($channelTitle, self::BRAND_MAX_LENGTH);
        $sectionPathCache = [];

        $items = [];
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $catalogIblockId,
                'ACTIVE' => 'Y',
                'CATALOG_AVAILABLE' => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'DETAIL_PAGE_URL',
                'DETAIL_TEXT',
                'PREVIEW_TEXT',
                'DETAIL_TEXT_TYPE',
                'PREVIEW_TEXT_TYPE',
                'DETAIL_PICTURE',
                'PREVIEW_PICTURE',
                'IBLOCK_SECTION_ID',
            ]
        );

        while ($ob = $res->GetNextElement()) {
            $fields = $ob->GetFields();
            $props = $ob->GetProperties();

            $productId = (int) ($fields['ID'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $priceData = self::resolveProductPrice($productId, $siteId);
            if ($priceData === null) {
                continue;
            }

            $title = self::truncate(trim((string) ($fields['NAME'] ?? '')), self::TITLE_MAX_LENGTH);
            if ($title === '') {
                continue;
            }

            $description = self::resolvePlainDescription($fields);
            if ($description === '') {
                continue;
            }

            $imageLink = self::resolveImageUrl($fields, $props, $siteUrl);
            if ($imageLink === '') {
                continue;
            }

            $detailUrl = (string) ($fields['DETAIL_PAGE_URL'] ?? '');
            if ($detailUrl === '') {
                continue;
            }

            $sectionId = (int) ($fields['IBLOCK_SECTION_ID'] ?? 0);
            if (!array_key_exists($sectionId, $sectionPathCache)) {
                $sectionPathCache[$sectionId] = self::buildSectionPath($catalogIblockId, $sectionId);
            }

            $brand = self::resolveBrandName($props['BRAND'] ?? null);
            if ($brand === '') {
                $brand = $defaultBrand;
            } else {
                $brand = self::truncate($brand, self::BRAND_MAX_LENGTH);
            }

            $items[] = self::buildItemXml(
                $productId,
                $title,
                $description,
                $siteUrl . $detailUrl,
                $imageLink,
                $sectionPathCache[$sectionId],
                $priceData,
                self::resolveAvailability($productId),
                $brand
            );
        }

        $xml = self::buildFeedXml($channelTitle, $siteUrl, $items);
        self::writeFeedFile($xml);

        return count($items);
    }

    private static function resolveSiteId(): string
    {
        $siteId = Context::getCurrent()->getSite();

        if (!is_null($siteId) && $siteId !== '') {
            return $siteId;
        }

        $siteRes = \CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
        if ($site = $siteRes->Fetch()) {
            return (string) ($site['LID'] ?? 's1');
        }

        return 's1';
    }

    /**
     * @return array{base: float, discount: float, currency: string}|null
     */
    private static function resolveProductPrice(int $productId, string $siteId): ?array
    {
        $optimalPrice = \CCatalogProduct::GetOptimalPrice(
            $productId,
            1,
            [2],
            'N',
            [],
            $siteId
        );

        if (empty($optimalPrice['RESULT_PRICE'])) {
            return null;
        }

        $resultPrice = $optimalPrice['RESULT_PRICE'];
        $basePrice = (float) ($resultPrice['BASE_PRICE'] ?? 0);
        $discountPrice = (float) ($resultPrice['DISCOUNT_PRICE'] ?? $basePrice);

        if ($basePrice <= 0 && $discountPrice <= 0) {
            return null;
        }

        $currency = (string) ($resultPrice['CURRENCY'] ?? self::DEFAULT_CURRENCY);
        if ($currency === '') {
            $currency = self::DEFAULT_CURRENCY;
        }

        return [
            'base' => $basePrice > 0 ? $basePrice : $discountPrice,
            'discount' => $discountPrice,
            'currency' => $currency,
        ];
    }

    private static function resolveAvailability(int $productId): string
    {
        $catalogProduct = \CCatalogProduct::GetByID($productId);
        if (!is_array($catalogProduct)) {
            return 'out of stock';
        }

        $quantity = (float) ($catalogProduct['QUANTITY'] ?? 0);
        $canBuyZero = (string) ($catalogProduct['CAN_BUY_ZERO'] ?? 'N');

        return ($quantity > 0 || $canBuyZero === 'Y') ? 'in stock' : 'out of stock';
    }

    private static function buildSectionPath(int $iblockId, int $sectionId): string
    {
        if ($sectionId <= 0) {
            return '';
        }

        $names = [];
        $navChain = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME']);
        while ($section = $navChain->Fetch()) {
            $name = trim((string) ($section['NAME'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }

        return implode(' > ', $names);
    }

    /**
     * @param array<string, mixed>|null $brandProperty
     */
    private static function resolveBrandName(?array $brandProperty): string
    {
        if ($brandProperty === null) {
            return '';
        }

        $displayValue = $brandProperty['DISPLAY_VALUE'] ?? null;
        if (is_array($displayValue)) {
            $displayValue = $displayValue[0] ?? '';
        }
        $name = trim((string) $displayValue);
        if ($name !== '') {
            return $name;
        }

        $linkedId = (int) ($brandProperty['VALUE'] ?? 0);
        if ($linkedId <= 0) {
            return '';
        }

        $row = CIBlockElement::GetList(
            [],
            ['ID' => $linkedId],
            false,
            ['nTopCount' => 1],
            ['NAME']
        )->Fetch();

        return is_array($row) ? trim((string) ($row['NAME'] ?? '')) : '';
    }

    /**
     * @param array{base: float, discount: float, currency: string} $priceData
     */
    private static function buildItemXml(
        int $productId,
        string $title,
        string $description,
        string $link,
        string $imageLink,
        string $productType,
        array $priceData,
        string $availability,
        string $brand
    ): string {
        $lines = [
            '    <item>',
            '      <g:id>' . self::escapeXml((string) $productId) . '</g:id>',
            '      <g:title>' . self::escapeXml($title) . '</g:title>',
            '      <g:description>' . self::escapeXml($description) . '</g:description>',
            '      <g:availability>' . self::escapeXml($availability) . '</g:availability>',
            '      <g:condition>' . self::escapeXml(self::DEFAULT_CONDITION) . '</g:condition>',
            '      <g:price>' . self::escapeXml(
                self::formatPrice($priceData['base'], $priceData['currency'])
            ) . '</g:price>',
        ];

        if ($priceData['discount'] < $priceData['base']) {
            $lines[] = '      <g:sale_price>' . self::escapeXml(
                self::formatPrice($priceData['discount'], $priceData['currency'])
            ) . '</g:sale_price>';
        }

        $lines[] = '      <g:link>' . self::escapeXml($link) . '</g:link>';
        $lines[] = '      <g:image_link>' . self::escapeXml($imageLink) . '</g:image_link>';
        $lines[] = '      <g:brand>' . self::escapeXml($brand) . '</g:brand>';

        if ($productType !== '') {
            $lines[] = '      <g:product_type>' . self::escapeXml($productType) . '</g:product_type>';
        }

        $lines[] = '    </item>';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function resolvePlainDescription(array $fields): string
    {
        $detailText = trim((string) ($fields['DETAIL_TEXT'] ?? ''));
        $raw = $detailText !== '' ? $detailText : trim((string) ($fields['PREVIEW_TEXT'] ?? ''));
        if ($raw === '') {
            return '';
        }

        $plain = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = preg_replace('/[ \t]+/u', ' ', $plain) ?? $plain;
        $plain = preg_replace('/\R{3,}/u', "\n\n", $plain) ?? $plain;
        $plain = trim($plain);

        return self::truncate($plain, self::DESCRIPTION_MAX_LENGTH);
    }

    /**
     * Meta accepts JPEG/PNG only. Prefer fresh FEED_PICTURE (opaque PNG with background),
     * then DETAIL_PICTURE / PREVIEW_PICTURE.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     */
    private static function resolveImageUrl(array $fields, array $props, string $siteUrl): string
    {
        $detailPictureId = (int) ($fields['DETAIL_PICTURE'] ?? 0);
        $pictureId = self::resolveFreshFeedPictureFileId($props, $detailPictureId);
        if ($pictureId <= 0) {
            $pictureId = $detailPictureId;
        }
        if ($pictureId <= 0) {
            $pictureId = (int) ($fields['PREVIEW_PICTURE'] ?? 0);
        }
        if ($pictureId <= 0) {
            return '';
        }

        $path = (string) CFile::GetPath($pictureId);
        if ($path === '') {
            return '';
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return '';
        }

        return $siteUrl . $path;
    }

    /**
     * Use FEED_PICTURE only when it was generated from the current DETAIL_PICTURE
     * (source file id is stored in property DESCRIPTION by FeedPictureAgent).
     *
     * @param array<string, mixed> $props
     */
    private static function resolveFreshFeedPictureFileId(array $props, int $detailPictureId): int
    {
        if ($detailPictureId <= 0) {
            return 0;
        }

        $feed = $props['FEED_PICTURE'] ?? null;
        if (!is_array($feed)) {
            return 0;
        }

        $value = $feed['VALUE'] ?? null;
        if (is_array($value)) {
            $value = $value['ID'] ?? $value[0] ?? reset($value);
        }
        $feedFileId = (int) $value;
        if ($feedFileId <= 0) {
            return 0;
        }

        $sourceFileId = (int) ($feed['DESCRIPTION'] ?? 0);
        if ($sourceFileId !== $detailPictureId) {
            return 0;
        }

        return $feedFileId;
    }

    /**
     * @param list<string> $items
     */
    private static function buildFeedXml(string $channelTitle, string $siteUrl, array $items): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss xmlns:g="' . self::GOOGLE_NS . '" version="2.0">',
            '  <channel>',
            '    <title>' . self::escapeXml($channelTitle) . '</title>',
            '    <link>' . self::escapeXml($siteUrl . '/') . '</link>',
            '    <description>' . self::escapeXml($channelTitle) . '</description>',
        ];

        foreach ($items as $item) {
            $lines[] = $item;
        }

        $lines[] = '  </channel>';
        $lines[] = '</rss>';

        return implode("\n", $lines) . "\n";
    }

    private static function writeFeedFile(string $xml): void
    {
        $outputDir = Utils::resolveDocumentRootSubdir(self::FEED_OUTPUT_DIR);
        if (!is_dir($outputDir) && !CheckDirPath($outputDir . '/')) {
            return;
        }

        $targetPath = $outputDir . '/' . self::FEED_FILENAME;
        $tempPath = $targetPath . '.tmp';

        if (file_put_contents($tempPath, $xml) === false) {
            return;
        }

        rename($tempPath, $targetPath);
    }

    private static function formatPrice(float $price, string $currency): string
    {
        $formatted = rtrim(rtrim(number_format($price, 2, '.', ''), '0'), '.');

        return $formatted . ' ' . $currency;
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function truncate(string $value, int $maxLength): string
    {
        if ($value === '' || $maxLength <= 0) {
            return '';
        }

        if (mb_strlen($value, 'UTF-8') <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength, 'UTF-8'));
    }
}
