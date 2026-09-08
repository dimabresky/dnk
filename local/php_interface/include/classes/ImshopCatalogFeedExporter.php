<?php

namespace Dnk\PhpInterface;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;

/**
 * Генерация YML-фида каталога для IMSHOP.
 *
 * @see https://docs.imshop.io/sinkhronizaciya-kataloga/sozdanie-fida/yml-fid-kataloga-tovarov
 */
final class ImshopCatalogFeedExporter extends CatalogYmlFeedExporter
{
    private const DEFAULT_CURRENCY = 'BYN';

    private string $currency = self::DEFAULT_CURRENCY;

    /**
     * Пишет статический UTF-8 YML в $absoluteFilePath и возвращает число офферов.
     *
     * @param list<int> $iblockIds
     */
    public static function export(
        array $iblockIds,
        string $absoluteFilePath,
        string $siteUrl,
        string $shopName
    ): int {
        return (new self())->run($iblockIds, $absoluteFilePath, $siteUrl, $shopName);
    }

    protected function prepareContext(): void
    {
        $this->currency = $this->resolveBaseCurrency();
    }

    /**
     * @return list<string>
     */
    protected function extraElementSelectFields(): array
    {
        return [
            'DETAIL_TEXT',
            'PREVIEW_TEXT',
            'DETAIL_TEXT_TYPE',
            'PREVIEW_TEXT_TYPE',
            'CATALOG_QUANTITY',
        ];
    }

    /**
     * @param resource $fp
     */
    protected function writeFeedHeaderExtras($fp): void
    {
        fwrite($fp, "    <currencies>\n");
        fwrite($fp, '      <currency id="' . self::escapeXml($this->currency) . '" rate="1"/>' . "\n");
        fwrite($fp, "    </currencies>\n");
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     * @param array{base: float, discount: float} $priceData
     * @param list<int> $categoryIds
     */
    protected function buildOfferXml(
        array $fields,
        array $props,
        string $siteUrl,
        array $priceData,
        array $categoryIds
    ): string {
        $lines = $this->startOfferLines($fields, $props);
        if ($lines === []) {
            return '';
        }

        $this->appendUrlAndPrices($lines, $fields, $siteUrl, $priceData);
        $lines[] = '        <currencyId>' . self::escapeXml($this->currency) . '</currencyId>';
        $this->appendCategoryIds($lines, $categoryIds);

        $pictures = $this->resolveProductPictures($fields, $props, $siteUrl);
        if ($pictures['detailUrl'] !== '') {
            $lines[] = '        <picture>' . self::escapeXml($pictures['detailUrl']) . '</picture>';
        }
        foreach ($pictures['extraUrls'] as $picture) {
            $lines[] = '        <picture>' . self::escapeXml($picture) . '</picture>';
        }

        $this->appendVendorTags($lines, $props);

        $barcode = $this->resolveBarcode($props);
        if ($barcode !== '') {
            $lines[] = '        <barcode>' . self::escapeXml($barcode) . '</barcode>';
        }

        $description = $this->resolveDescription($fields);
        if ($description !== '') {
            $lines[] = '        <description><![CDATA[' . self::sanitizeCdata($description) . ']]></description>';
        }

        $country = $this->firstPropertyValue($props['STRANA_IZGOTOVLENIYA'] ?? null);
        if ($country !== '') {
            $lines[] = '        <country_of_origin>' . self::escapeXml($country) . '</country_of_origin>';
        }

        if (isset($fields['CATALOG_QUANTITY']) && $fields['CATALOG_QUANTITY'] !== '' && $fields['CATALOG_QUANTITY'] !== null) {
            $quantity = (int) $fields['CATALOG_QUANTITY'];
            if ($quantity >= 0) {
                $lines[] = '        <count>' . $quantity . '</count>';
            }
        }

        $video = $this->resolveVideoUrl($props);
        if ($video !== '') {
            $lines[] = '        <video>' . self::escapeXml($video) . '</video>';
        }

        foreach ($this->buildParamTags($props) as $paramLine) {
            $lines[] = '        ' . $paramLine;
        }

        $lines[] = '      </offer>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function resolveDescription(array $fields): string
    {
        $detail = trim((string) ($fields['~DETAIL_TEXT'] ?? $fields['DETAIL_TEXT'] ?? ''));
        if ($detail !== '') {
            return $detail;
        }

        return trim((string) ($fields['~PREVIEW_TEXT'] ?? $fields['PREVIEW_TEXT'] ?? ''));
    }

    /**
     * @param array<string, mixed> $props
     */
    private function resolveVideoUrl(array $props): string
    {
        $property = $props['VIDEO_YOUTUBE'] ?? null;
        if (!is_array($property)) {
            return '';
        }

        $value = $property['~VALUE'] ?? $property['VALUE'] ?? '';
        if (is_array($value)) {
            $value = $value[0] ?? '';
        }
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('#(?:youtube\\.com/watch\\?v=|youtu\\.be/|youtube\\.com/embed/)([a-zA-Z0-9_-]{11})#', $raw, $matches) === 1) {
            return 'https://www.youtube.com/watch?v=' . $matches[1];
        }
        if (preg_match('#src=["\\\']([^"\\\']+)#i', $raw, $matches) === 1) {
            return $matches[1];
        }
        if (preg_match('#^https?://#i', $raw) === 1) {
            return $raw;
        }
        if (preg_match('#^[a-zA-Z0-9_-]{11}$#', $raw) === 1) {
            return 'https://www.youtube.com/watch?v=' . $raw;
        }

        return '';
    }

    private function resolveBaseCurrency(): string
    {
        if (Loader::includeModule('currency')) {
            $base = (string) CurrencyManager::getBaseCurrency();
            if ($base !== '') {
                return $base;
            }
        }

        $option = (string) \COption::GetOptionString('sale', 'default_currency', '');
        if ($option !== '') {
            return $option;
        }

        return self::DEFAULT_CURRENCY;
    }
}
