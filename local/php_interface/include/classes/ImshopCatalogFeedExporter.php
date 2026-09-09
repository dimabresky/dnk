<?php

namespace Dnk\PhpInterface;

use Bitrix\Currency\CurrencyManager;
use Bitrix\Main\Loader;
use CIBlockElement;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Генерация YML-фида каталога для IMSHOP.
 *
 * @see https://docs.imshop.io/sinkhronizaciya-kataloga/sozdanie-fida/yml-fid-kataloga-tovarov
 */
final class ImshopCatalogFeedExporter extends CatalogYmlFeedExporter
{
    private const DEFAULT_CURRENCY = 'BYN';

    private const HIT_BADGE_LABELS = [
        'NEW' => 'НОВИНКА',
        'HIT' => 'Хит',
        'RECOMMEND' => 'Спецпредложение',
        'STOCK' => 'Скидка',
    ];

    private string $currency = self::DEFAULT_CURRENCY;

    /**
     * Имя бренда => URL картинки (может быть пустым).
     *
     * @var array<string, string>
     */
    private array $vendors = [];

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
        $this->vendors = [];
    }

    protected function formatYmlCatalogDate(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get() ?: 'Europe/Minsk')))
            ->format('Y-m-d H:i');
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
            'XML_ID',
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

    protected function categoryLinkAttributeName(): string
    {
        return 'universalLink';
    }

    /**
     * @return list<string>
     */
    protected function extraCategorySelectFields(): array
    {
        return ['PICTURE'];
    }

    /**
     * @param array<string, mixed> $section
     */
    protected function extraCategoryAttributes(array $section, string $siteUrl): string
    {
        $picture = $section['PICTURE'] ?? null;
        $url = '';
        if (is_array($picture)) {
            $url = $this->fileIdToUrl((int) ($picture['ID'] ?? 0), $siteUrl);
            if ($url === '') {
                $src = trim((string) ($picture['SRC'] ?? ''));
                if ($src !== '') {
                    $url = preg_match('#^https?://#i', $src) === 1 ? $src : $siteUrl . $src;
                }
            }
        } elseif (is_numeric($picture)) {
            $url = $this->fileIdToUrl((int) $picture, $siteUrl);
        } elseif (is_string($picture) && $picture !== '') {
            $url = preg_match('#^https?://#i', $picture) === 1 ? $picture : $siteUrl . $picture;
        }

        if ($url === '') {
            return '';
        }

        return ' picture="' . self::escapeXml($url) . '"';
    }

    /**
     * @param resource $fp
     */
    protected function writeAfterCategories($fp, string $siteUrl): void
    {
        if ($this->vendors === []) {
            return;
        }

        fwrite($fp, "    <vendors>\n");
        foreach ($this->vendors as $name => $imageUrl) {
            $attrs = '';
            if ($imageUrl !== '') {
                $attrs = ' mainImageUrl="' . self::escapeXml($imageUrl) . '"';
            }
            fwrite($fp, '      <vendor' . $attrs . '>' . self::escapeXml($name) . "</vendor>\n");
        }
        fwrite($fp, "    </vendors>\n");
    }

    protected function shouldWriteGroupId(string $groupId, int $productId): bool
    {
        return $groupId !== (string) $productId;
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     */
    protected function extraOfferOpenAttributes(array $fields, array $props): string
    {
        $productId = (string) (int) ($fields['ID'] ?? 0);
        $uuid = trim((string) ($fields['XML_ID'] ?? $fields['EXTERNAL_ID'] ?? ''));
        if ($uuid === '' || $uuid === $productId) {
            return '';
        }

        return ' uuid="' . self::escapeXml($uuid) . '"';
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
        $this->rememberVendor($props, $siteUrl);

        $barcode = $this->resolveBarcode($props);
        if ($barcode !== '') {
            $lines[] = '        <barcode>' . self::escapeXml($barcode) . '</barcode>';
        }

        $description = $this->resolveDescription($fields);
        if ($description !== '') {
            $lines[] = '        <description><![CDATA[' . self::sanitizeCdata($description) . ']]></description>';
        }

        foreach ($this->buildBadgeTags($props) as $badgeLine) {
            $lines[] = '        ' . $badgeLine;
        }

        $video = $this->resolveVideoFileUrl($props);
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
     * Прямой URL видеофайла H.264; YouTube не пишем.
     *
     * @param array<string, mixed> $props
     */
    private function resolveVideoFileUrl(array $props): string
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

        if (preg_match('#src=["\\\']([^"\\\']+)#i', $raw, $matches) === 1) {
            $raw = $matches[1];
        }

        if (preg_match('#^https?://#i', $raw) !== 1) {
            return '';
        }
        if (preg_match('#\\.(mp4|mov|webm)(\\?|$)#i', $raw) !== 1) {
            return '';
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $props
     * @return list<string>
     */
    private function buildBadgeTags(array $props): array
    {
        $labels = [];

        if ($this->propertyValues($props['IS_NEW'] ?? null) !== []) {
            $labels['НОВИНКА'] = true;
        }

        $xmlIds = $this->hitXmlIds($props['HIT'] ?? null);
        foreach ($xmlIds as $xmlId) {
            $key = strtoupper($xmlId);
            $label = self::HIT_BADGE_LABELS[$key] ?? '';
            if ($label === '') {
                continue;
            }
            $labels[$label] = true;
        }

        $tags = [];
        foreach (array_keys($labels) as $label) {
            $tags[] = '<badge>' . self::escapeXml($label) . '</badge>';
        }

        return $tags;
    }

    /**
     * @param array<string, mixed>|null $property
     * @return list<string>
     */
    private function hitXmlIds(?array $property): array
    {
        if ($property === null) {
            return [];
        }

        $xmlId = $property['VALUE_XML_ID'] ?? null;
        if ($xmlId === null || $xmlId === false || $xmlId === '') {
            return [];
        }

        $rawList = is_array($xmlId) ? $xmlId : [$xmlId];
        $result = [];
        foreach ($rawList as $item) {
            $text = strtoupper(trim((string) $item));
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return array_values(array_unique($result));
    }

    /**
     * @param array<string, mixed> $props
     */
    private function rememberVendor(array $props, string $siteUrl): void
    {
        $name = $this->resolveBrandName($props['BRAND'] ?? null);
        if ($name === '') {
            $name = $this->firstPropertyValue($props['BREND'] ?? null);
        }
        if ($name === '' || isset($this->vendors[$name])) {
            return;
        }

        $this->vendors[$name] = $this->resolveBrandImageUrl($props['BRAND'] ?? null, $siteUrl);
    }

    /**
     * @param array<string, mixed>|null $brandProperty
     */
    private function resolveBrandImageUrl(?array $brandProperty, string $siteUrl): string
    {
        if ($brandProperty === null) {
            return '';
        }

        $linkedId = $brandProperty['VALUE'] ?? 0;
        if (is_array($linkedId)) {
            $linkedId = $linkedId[0] ?? 0;
        }
        $linkedId = (int) $linkedId;
        if ($linkedId <= 0) {
            return '';
        }

        $row = CIBlockElement::GetList(
            [],
            ['ID' => $linkedId],
            false,
            ['nTopCount' => 1],
            ['PREVIEW_PICTURE', 'DETAIL_PICTURE']
        )->Fetch();
        if (!is_array($row)) {
            return '';
        }

        $previewId = (int) ($row['PREVIEW_PICTURE'] ?? 0);
        if ($previewId > 0) {
            $url = $this->fileIdToUrl($previewId, $siteUrl);
            if ($url !== '') {
                return $url;
            }
        }

        $detailId = (int) ($row['DETAIL_PICTURE'] ?? 0);
        if ($detailId > 0) {
            return $this->fileIdToUrl($detailId, $siteUrl);
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
