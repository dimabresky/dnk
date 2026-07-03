<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use CFile;
use CIBlockElement;
use CIBlockPropertyEnum;
use CIBlockSection;

/**
 * Агент: генерация Google Merchant RSS feed upload/dnk_products_feed.xml.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\ProductFeedAgent::runProductFeedAgent();
 * Интервал — DNK_PRODUCT_FEED_AGENT_INTERVAL (сек).
 */
final class ProductFeedAgent
{
    private const FEED_FILENAME = 'dnk_products_feed.xml';
    private const FEED_OUTPUT_DIR = 'upload';
    private const GOOGLE_NS = 'http://base.google.com/ns/1.0';
    private const DEFAULT_CURRENCY = 'BYN';

    public static function runProductFeedAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\ProductFeedAgent::runProductFeedAgent();";

        self::generateFeed();

        return $return;
    }

    /**
     * Генерирует feed и возвращает количество записей <entry>.
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
        $siteId = self::resolveSiteId();
        $hitEnumXmlById = self::buildHitEnumXmlIdMap($catalogIblockId);
        $sectionPathCache = [];

        $entries = [];
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
            $props = $ob->GetProperties(false, ['CODE' => ['BRAND', 'HIT']]);

            $productId = (int) ($fields['ID'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $priceData = self::resolveProductPrice($productId, $siteId);
            if ($priceData === null) {
                continue;
            }

            $sectionId = (int) ($fields['IBLOCK_SECTION_ID'] ?? 0);
            if (!array_key_exists($sectionId, $sectionPathCache)) {
                $sectionPathCache[$sectionId] = self::buildSectionPath($catalogIblockId, $sectionId);
            }

            $entries[] = self::buildEntryXml(
                $fields,
                $props,
                $siteUrl,
                $sectionPathCache[$sectionId],
                $priceData,
                self::resolveAvailability($productId),
                self::resolveBrandName($props['BRAND'] ?? null),
                self::resolveConditionFromHit($props['HIT'] ?? null, $hitEnumXmlById)
            );
        }

        $xml = self::buildFeedXml($channelTitle, $siteUrl, $entries);
        self::writeFeedFile($xml);

        return count($entries);
    }

    private static function resolveSiteId(): string
    {
        $siteId = Context::getCurrent()->getSite();
        
        if (is_null($siteId) || $siteId !== '') {
            return $siteId;
        }

        $siteRes = \CSite::GetList('sort', 'asc', ['ACTIVE' => 'Y']);
        if ($site = $siteRes->Fetch()) {
            return (string) ($site['LID'] ?? 's1');
        }

        return 's1';
    }

    /**
     * @return array<int, string>
     */
    private static function buildHitEnumXmlIdMap(int $iblockId): array
    {
        $map = [];
        $propHit = Utils::getIblockPropertyByCode($iblockId, 'HIT');
        if ($propHit === null) {
            return $map;
        }

        $propertyId = (int) ($propHit['ID'] ?? 0);
        if ($propertyId <= 0) {
            return $map;
        }

        $enumRes = CIBlockPropertyEnum::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['PROPERTY_ID' => $propertyId]
        );
        while ($enum = $enumRes->Fetch()) {
            $enumId = (int) ($enum['ID'] ?? 0);
            if ($enumId > 0) {
                $map[$enumId] = (string) ($enum['XML_ID'] ?? '');
            }
        }

        return $map;
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
            return 'out_of_stock';
        }

        $quantity = (float) ($catalogProduct['QUANTITY'] ?? 0);
        $canBuyZero = (string) ($catalogProduct['CAN_BUY_ZERO'] ?? 'N');

        return ($quantity > 0 || $canBuyZero === 'Y') ? 'in_stock' : 'out_of_stock';
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
     * @param array<string, mixed>|null $hitProperty
     * @param array<int, string> $hitEnumXmlById
     */
    private static function resolveConditionFromHit(?array $hitProperty, array $hitEnumXmlById): string
    {
        if ($hitProperty === null || $hitEnumXmlById === []) {
            return '';
        }

        $values = $hitProperty['VALUE'] ?? null;
        if ($values === null || $values === '' || $values === false) {
            return '';
        }

        if (!is_array($values)) {
            $values = [$values];
        }

        foreach ($values as $value) {
            $enumId = Utils::coerceIblockListEnumId($value);
            if ($enumId === null) {
                continue;
            }

            $xmlId = trim((string) ($hitEnumXmlById[$enumId] ?? ''));
            if ($xmlId !== '') {
                return mb_strtolower($xmlId, 'UTF-8');
            }
        }

        return '';
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     * @param array{base: float, discount: float, currency: string} $priceData
     */
    private static function buildEntryXml(
        array $fields,
        array $props,
        string $siteUrl,
        string $productType,
        array $priceData,
        string $availability,
        string $brand,
        string $condition
    ): string {
        $productId = (int) ($fields['ID'] ?? 0);
        $title = trim((string) ($fields['NAME'] ?? ''));
        $detailUrl = (string) ($fields['DETAIL_PAGE_URL'] ?? '');
        $description = self::resolveDescription($fields);
        $imageLink = self::resolveImageUrl($fields, $siteUrl);

        $lines = [
            '    <entry>',
            '      <g:id>' . self::escapeXml((string) $productId) . '</g:id>',
            '      <g:title>' . self::escapeXml($title) . '</g:title>',
            '      <g:description><![CDATA[' . self::sanitizeCdata($description) . ']]></g:description>',
            '      <g:product_type>' . self::escapeXml($productType) . '</g:product_type>',
            '      <g:link>' . self::escapeXml($siteUrl . $detailUrl) . '</g:link>',
            '      <g:image_link>' . self::escapeXml($imageLink) . '</g:image_link>',
            '      <g:identifier_exists>no</g:identifier_exists>',
        ];

        if ($condition !== '') {
            $lines[] = '      <g:condition>' . self::escapeXml($condition) . '</g:condition>';
        } else {
            $lines[] = '      <g:condition/>';
        }

        $lines[] = '      <g:price>' . self::escapeXml(
            self::formatPrice($priceData['base'], $priceData['currency'])
        ) . '</g:price>';

        if ($priceData['discount'] < $priceData['base']) {
            $lines[] = '      <g:sale_price>' . self::escapeXml(
                self::formatPrice($priceData['discount'], $priceData['currency'])
            ) . '</g:sale_price>';
        }

        if ($brand !== '') {
            $lines[] = '      <g:brand>' . self::escapeXml($brand) . '</g:brand>';
        } else {
            $lines[] = '      <g:brand/>';
        }

        $lines[] = '      <g:availability>' . self::escapeXml($availability) . '</g:availability>';
        $lines[] = '    </entry>';

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function resolveDescription(array $fields): string
    {
        $detailText = trim((string) ($fields['DETAIL_TEXT'] ?? ''));
        if ($detailText !== '') {
            return $detailText;
        }

        return trim((string) ($fields['PREVIEW_TEXT'] ?? ''));
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function resolveImageUrl(array $fields, string $siteUrl): string
    {
        $pictureId = (int) ($fields['DETAIL_PICTURE'] ?? 0);
        if ($pictureId <= 0) {
            $pictureId = (int) ($fields['PREVIEW_PICTURE'] ?? 0);
        }
        if ($pictureId <= 0) {
            return '';
        }

        $path = (string) CFile::GetPath($pictureId);

        return $path !== '' ? $siteUrl . $path : '';
    }

    /**
     * @param list<string> $entries
     */
    private static function buildFeedXml(string $channelTitle, string $siteUrl, array $entries): string
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<rss xmlns:g="' . self::GOOGLE_NS . '" version="2.0">',
            '  <channel>',
            '    <title>' . self::escapeXml($channelTitle) . '</title>',
            '    <link>' . self::escapeXml($siteUrl . '/') . '</link>',
            '    <description>' . self::escapeXml($channelTitle) . '</description>',
        ];

        foreach ($entries as $entry) {
            $lines[] = $entry;
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

    private static function sanitizeCdata(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
