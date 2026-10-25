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

    private const GROUP_ID_LINKS_PARAM_NAMES = [
        Utils::SKU_VARIANT_MODE_SHADE => 'Оттенок',
        Utils::SKU_VARIANT_MODE_VOLUME => 'Объем',
    ];

    private const COLLAPSIBLE_TEXT_SECTIONS = [
        'SPOSOB_PRIMENENIYA' => 'Примечание',
        'SOSTAV_1' => 'Состав',
    ];

    private const CHARACTERISTIC_PROPERTY_CODES = [
        'BREND',
        'CML2_ARTICLE',
        'OBEM_FLAKONA',
        'CML2_MANUFACTURER',
        'STRANA_IZGOTOVLENIYA',
        'NOMINALNYY_OBEM',
        'IMPORTER',
        'OTTENOK',
        'PODKHODIT_DLYA',
    ];

    private string $currency = self::DEFAULT_CURRENCY;

    /**
     * Имя бренда => URL картинки (может быть пустым).
     *
     * @var array<string, string>
     */
    private array $vendors = [];

    /**
     * Кэш вариантов SKU на время экспорта: iblockId:groupingValue => payload.
     *
     * @var array<string, array{mode: string, items: list<array{id: int, value: string, imageFileId: int}>}>
     */
    private array $skuGroupVariantItemsCache = [];

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
        $this->skuGroupVariantItemsCache = [];
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
        return false;
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

        $collapsibleDescription = $this->buildCollapsibleDescription($fields, $props);
        if ($collapsibleDescription !== '') {
            $lines[] = $collapsibleDescription;
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

        $this->appendGroupIdLinks($lines, $fields, $props, $siteUrl);

        $lines[] = '      </offer>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<string> $lines
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     */
    private function appendGroupIdLinks(array &$lines, array $fields, array $props, string $siteUrl): void
    {
        $productId = (int) ($fields['ID'] ?? 0);
        $iblockId = (int) ($fields['IBLOCK_ID'] ?? 0);
        $groupingValue = $this->resolveSkuGroupingValue($props[self::GROUPING_PROPERTY_CODE] ?? null);
        if ($productId <= 0 || $iblockId <= 0 || $groupingValue === '') {
            return;
        }

        $payload = $this->getCachedSkuGroupVariantItems($iblockId, $groupingValue);
        $items = $payload['items'];
        if (count($items) < 2) {
            return;
        }

        $currentInGroup = false;
        foreach ($items as $item) {
            if ($item['id'] === $productId) {
                $currentInGroup = true;
                break;
            }
        }
        if (!$currentInGroup) {
            return;
        }

        $items = $this->uniqueGroupIdLinkItemsByValue($items);
        if (count($items) < 2) {
            return;
        }

        $paramName = self::GROUP_ID_LINKS_PARAM_NAMES[$payload['mode']] ?? '';
        if ($paramName === '') {
            return;
        }

        $lines[] = '        <groupIdLinks>';
        $lines[] = '          <groupIdLinksParam name="' . self::escapeXml($paramName) . '">';
        foreach ($items as $item) {
            $attrs = ' value="' . self::escapeXml($item['value']) . '"'
                . ' groupId="' . self::escapeXml((string) $item['id']) . '"';
            $imageUrl = $this->fileIdToUrl((int) ($item['imageFileId'] ?? 0), $siteUrl);
            if (preg_match('#^https://#i', $imageUrl) === 1) {
                $attrs .= ' imageUrl="' . self::escapeXml($imageUrl) . '"';
            }
            $lines[] = '            <groupIdLink' . $attrs . '/>';
        }
        $lines[] = '          </groupIdLinksParam>';
        $lines[] = '        </groupIdLinks>';
    }

    /**
     * @return array{mode: string, items: list<array{id: int, value: string, imageFileId: int}>}
     */
    private function getCachedSkuGroupVariantItems(int $iblockId, string $groupingValue): array
    {
        $cacheKey = $iblockId . ':' . md5($groupingValue);
        if (!isset($this->skuGroupVariantItemsCache[$cacheKey])) {
            $this->skuGroupVariantItemsCache[$cacheKey] = Utils::getSkuGroupVariantItems(
                $iblockId,
                $groupingValue,
                defined('DNK_SHADES_IBLOCK_ID') ? (int) DNK_SHADES_IBLOCK_ID : 0
            );
        }

        return $this->skuGroupVariantItemsCache[$cacheKey];
    }

    /**
     * Сырое значение GRUPPIROVKATOVAROVNASAYTE для фильтра SKU, без DISPLAY_VALUE и сжатия пробелов.
     *
     * @param array<string, mixed>|null $property
     */
    private function resolveSkuGroupingValue(?array $property): string
    {
        if ($property === null) {
            return '';
        }

        $value = $property['~VALUE'] ?? $property['VALUE'] ?? null;
        if (is_array($value)) {
            $value = !empty($value) ? reset($value) : null;
            if (is_array($value)) {
                $value = $value['TEXT'] ?? $value['VALUE'] ?? reset($value);
            }
        }
        if ($value === null || $value === '' || $value === false) {
            return '';
        }

        return (string) $value;
    }

    /**
     * @param list<array{id: int, value: string, imageFileId: int}> $items
     * @return list<array{id: int, value: string, imageFileId: int}>
     */
    private function uniqueGroupIdLinkItemsByValue(array $items): array
    {
        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $value = (string) ($item['value'] ?? '');
            if ($value === '' || isset($seen[$value])) {
                continue;
            }
            $seen[$value] = true;
            $unique[] = $item;
        }

        return $unique;
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     */
    private function buildCollapsibleDescription(array $fields, array $props): string
    {
        $sections = [];

        $description = $this->htmlToMarkdown((string) ($fields['~DETAIL_TEXT'] ?? $fields['DETAIL_TEXT'] ?? ''));
        if ($description === '') {
            $description = $this->htmlToMarkdown((string) ($fields['~PREVIEW_TEXT'] ?? $fields['PREVIEW_TEXT'] ?? ''));
        }
        if ($description !== '') {
            $sections[] = ['title' => 'Описание', 'text' => $description];
        }

        foreach (self::COLLAPSIBLE_TEXT_SECTIONS as $code => $title) {
            $text = implode(', ', $this->propertyValues(is_array($props[$code] ?? null) ? $props[$code] : null));
            if ($text !== '') {
                $sections[] = ['title' => $title, 'text' => $text];
            }
        }

        $characteristics = $this->buildCharacteristicsMarkdown($props);
        if ($characteristics !== '') {
            $sections[] = ['title' => 'Характеристики', 'text' => $characteristics];
        }

        if ($sections === []) {
            return '';
        }

        $lines = ['        <collapsibleDescription>'];
        foreach ($sections as $section) {
            $lines[] = '          <section>';
            $lines[] = '            <title>' . self::escapeXml($section['title']) . '</title>';
            $lines[] = '            <text><![CDATA[' . self::sanitizeCdata($section['text']) . ']]></text>';
            $lines[] = '          </section>';
        }
        $lines[] = '        </collapsibleDescription>';

        return implode("\n", $lines);
    }

    /**
     * h/p/ul/li/strong/b/bold → markdown, остальные теги снимаются.
     * br → перевод строки, иначе соседние строки слипаются.
     */
    private function htmlToMarkdown(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $raw = preg_replace('#<br\b[^>]*>#i', "\n", $raw) ?? $raw;
        $raw = $this->replaceMatchedTags(
            $raw,
            '#<(strong|b|bold)\b[^>]*>((?:(?!</?\1\b).)*)</\1>#isu',
            static function (array $m): string {
                $text = trim(strip_tags($m[2]));

                return $text === '' ? '' : '**' . $text . '**';
            }
        );
        $raw = $this->replaceMatchedTags(
            $raw,
            '#<li\b[^>]*>((?:(?!</?li\b).)*)</li>#isu',
            static function (array $m): string {
                $item = trim(preg_replace('/\s+/u', ' ', strip_tags($m[1])) ?? '');

                return $item === '' ? '' : "\n- " . $item;
            }
        );
        $raw = preg_replace('#</?ul\b[^>]*>#i', "\n\n", $raw) ?? $raw;
        $raw = preg_replace_callback(
            '#<h([1-6])\b[^>]*>(.*?)</h\1>#isu',
            static function (array $m): string {
                $text = trim(preg_replace('/\s+/u', ' ', strip_tags($m[2])) ?? '');
                if ($text === '') {
                    return "\n";
                }

                return "\n" . str_repeat('#', (int) $m[1]) . ' ' . $text . "\n\n";
            },
            $raw
        ) ?? $raw;
        $raw = preg_replace('#</p>#i', "\n\n", $raw) ?? $raw;
        $raw = preg_replace('#<p\b[^>]*>#i', '', $raw) ?? $raw;

        $plain = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = str_replace("\u{00A0}", ' ', $plain);
        $normalized = [];
        foreach (preg_split('/\R/u', $plain) ?: [] as $line) {
            $normalized[] = trim(preg_replace('/[ \t]+/u', ' ', $line) ?? $line);
        }
        $plain = implode("\n", $normalized);
        $plain = preg_replace("/\n{3,}/", "\n\n", $plain) ?? $plain;
        $plain = preg_replace("/\n\n(?=- )/", "\n", $plain) ?? $plain;

        return trim($plain);
    }

    /**
     * @param callable(array<int, string>): string $replace
     */
    private function replaceMatchedTags(string $raw, string $pattern, callable $replace): string
    {
        $previous = null;
        while ($previous !== $raw) {
            $previous = $raw;
            $replaced = preg_replace_callback($pattern, $replace, $raw);
            if (!is_string($replaced)) {
                break;
            }
            $raw = $replaced;
        }

        return $raw;
    }

    /**
     * @param array<string, mixed> $props
     */
    private function buildCharacteristicsMarkdown(array $props): string
    {
        $lines = [];
        foreach (self::CHARACTERISTIC_PROPERTY_CODES as $code) {
            $property = $props[$code] ?? null;
            if (!is_array($property)) {
                continue;
            }

            $name = trim((string) ($property['NAME'] ?? ''));
            $value = trim(preg_replace('/\s+/u', ' ', implode(', ', $this->propertyValues($property))) ?? '');
            if ($name === '' || $value === '') {
                continue;
            }

            $lines[] = '- ' . $name . ': **' . str_replace('*', '\\*', $value) . '**';
        }

        return implode("\n", $lines);
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
