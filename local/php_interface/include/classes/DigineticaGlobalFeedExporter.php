<?php

namespace Dnk\PhpInterface;

/**
 * Генерация глобального YML-фида AnyQuery / Diginetica.
 *
 * @see https://merchrules.diginetica.net/backend-v2/api/v1/public/kb/embeds/anyquery-developer-docs/files/feeds/global-feed.html
 */
final class DigineticaGlobalFeedExporter extends CatalogYmlFeedExporter
{
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
        $this->appendCategoryIds($lines, $categoryIds);
        $this->appendVendorTags($lines, $props);

        $pictures = $this->resolveProductPictures($fields, $props, $siteUrl);
        if ($pictures['detailUrl'] !== '') {
            $lines[] = '        <picture>' . self::escapeXml($pictures['detailUrl']) . '</picture>';
        }

        foreach ($pictures['extraUrls'] as $index => $extraUrl) {
            $n = $index + 2;
            $lines[] = '        <param name="' . self::escapeXml('Изображение ' . $n) . '">'
                . self::escapeXml($extraUrl)
                . '</param>';
        }

        foreach ($this->buildParamTags($props) as $paramLine) {
            $lines[] = '        ' . $paramLine;
        }

        $lines[] = '      </offer>';

        return implode("\n", $lines) . "\n";
    }
}
