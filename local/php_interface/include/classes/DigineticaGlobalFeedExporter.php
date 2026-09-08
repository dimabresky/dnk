<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use CCatalogDiscount;
use CCatalogProduct;
use CFile;
use CIBlock;
use CIBlockElement;
use CIBlockSection;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Генерация глобального YML-фида AnyQuery / Diginetica.
 *
 * @see https://merchrules.diginetica.net/backend-v2/api/v1/public/kb/embeds/anyquery-developer-docs/files/feeds/global-feed.html
 */
final class DigineticaGlobalFeedExporter
{
    private const DISCOUNT_CACHE_FLUSH_EVERY = 100;

    private const GROUPING_PROPERTY_CODE = 'GRUPPIROVKATOVAROVNASAYTE';

    private const BARCODE_PROPERTY_CODES = ['CML2_BAR_CODE', 'SHTRIKHKOD'];

    /**
     * Код свойства => имя param (и опциональный unit, если значение чисто числовое).
     *
     * @var array<string, array{name: string, unit?: string, yes?: bool}>
     */
    private const PARAM_PROPERTIES = [
        'CML2_ARTICLE' => ['name' => 'Артикул'],
        'ARTIKUL_DOPOLNITELNYY' => ['name' => 'Артикул2'],
        'HIT' => ['name' => 'Хит'],
        'IS_NEW' => ['name' => 'Новинка', 'yes' => true],
        'TIP_KOZHI' => ['name' => 'Тип кожи'],
        'DEYSTVIE' => ['name' => 'Действие'],
        'TIP_VOLOS' => ['name' => 'Тип волос'],
        'FAKTOR_SPF' => ['name' => 'Фактор SPF'],
        'STRANA_IZGOTOVLENIYA' => ['name' => 'Страна изготовления'],
        'OTTENOK' => ['name' => 'Оттенок'],
        'NOMINALNYY_OBEM' => ['name' => 'Объем', 'unit' => 'мл'],
        'LINEYKA' => ['name' => 'Линейка'],
        'KATEGORIYA_UKHODA' => ['name' => 'Категория ухода'],
        'PODKHODIT_DLYA' => ['name' => 'Подходит для'],
        'OBEM_FLAKONA' => ['name' => 'Объем флакона'],
    ];

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
        if (!Loader::includeModule('iblock') || !Loader::includeModule('catalog')) {
            throw new RuntimeException('Modules iblock and catalog are required.');
        }

        $iblockIds = array_values(array_unique(array_filter(
            array_map(static fn($id) => (int) $id, $iblockIds),
            static fn(int $id) => $id > 0
        )));
        if ($iblockIds === []) {
            throw new RuntimeException('No catalog infoblocks selected.');
        }

        $absoluteFilePath = str_replace('\\', '/', $absoluteFilePath);
        $siteUrl = rtrim($siteUrl, '/');
        $shopName = trim($shopName);
        if ($shopName === '') {
            $shopName = 'shop';
        }

        $outputDir = dirname($absoluteFilePath);
        if (!is_dir($outputDir) && !CheckDirPath($outputDir . '/')) {
            throw new RuntimeException('Cannot create export directory: ' . $outputDir);
        }

        $offersTempPath = $absoluteFilePath . '.offers.tmp';
        $targetTempPath = $absoluteFilePath . '.tmp';

        $offersFp = @fopen($offersTempPath, 'wb');
        if ($offersFp === false) {
            throw new RuntimeException('Cannot write temporary offers file: ' . $offersTempPath);
        }

        $usedSectionIds = [];
        $navChainCache = [];
        $offerCount = 0;

        try {
            foreach ($iblockIds as $iblockId) {
                $offerCount += self::writeIblockOffers(
                    $iblockId,
                    $offersFp,
                    $siteUrl,
                    $usedSectionIds,
                    $navChainCache
                );
            }
        } finally {
            fclose($offersFp);
        }

        $targetFp = @fopen($targetTempPath, 'wb');
        if ($targetFp === false) {
            @unlink($offersTempPath);
            throw new RuntimeException('Cannot write temporary feed file: ' . $targetTempPath);
        }

        try {
            self::writeFeedHeader($targetFp, $siteUrl, $shopName);
            self::writeCategories($targetFp, $usedSectionIds, $siteUrl);
            fwrite($targetFp, "    <offers>\n");
            self::appendFile($targetFp, $offersTempPath);
            fwrite($targetFp, "    </offers>\n");
            fwrite($targetFp, "  </shop>\n");
            fwrite($targetFp, "</yml_catalog>\n");
        } finally {
            fclose($targetFp);
            @unlink($offersTempPath);
        }

        if (!@rename($targetTempPath, $absoluteFilePath)) {
            @unlink($targetTempPath);
            throw new RuntimeException('Cannot replace feed file: ' . $absoluteFilePath);
        }

        return $offerCount;
    }

    /**
     * @param resource $offersFp
     * @param array<int, true> $usedSectionIds
     * @param array<int, list<int>> $navChainCache
     */
    private static function writeIblockOffers(
        int $iblockId,
        $offersFp,
        string $siteUrl,
        array &$usedSectionIds,
        array &$navChainCache
    ): int {
        $iblock = CIBlock::GetArrayByID($iblockId);
        if (!is_array($iblock) || ($iblock['ACTIVE'] ?? '') !== 'Y') {
            return 0;
        }

        $siteId = self::resolveSiteId($iblock);
        $count = 0;
        $sinceCacheFlush = 0;

        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
                'ACTIVE_DATE' => 'Y',
            ],
            false,
            false,
            [
                'ID',
                'IBLOCK_ID',
                'NAME',
                'DETAIL_PAGE_URL',
                'DETAIL_PICTURE',
                'PREVIEW_PICTURE',
                'IBLOCK_SECTION_ID',
            ]
        );

        while ($ob = $res->GetNextElement()) {
            $fields = $ob->GetFields();
            $productId = (int) ($fields['ID'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $priceData = self::resolveProductPrice($productId, $siteId);
            if ($priceData === null) {
                continue;
            }

            $categoryIds = self::collectProductCategoryIds(
                $productId,
                $iblockId,
                $usedSectionIds,
                $navChainCache
            );
            if ($categoryIds === []) {
                continue;
            }

            $props = $ob->GetProperties();
            $offerXml = self::buildOfferXml(
                $fields,
                $props,
                $siteUrl,
                $priceData,
                $categoryIds
            );
            if ($offerXml === '') {
                continue;
            }

            fwrite($offersFp, $offerXml);
            $count++;
            $sinceCacheFlush++;

            if ($sinceCacheFlush >= self::DISCOUNT_CACHE_FLUSH_EVERY) {
                $sinceCacheFlush = 0;
                if (class_exists(CCatalogDiscount::class)) {
                    CCatalogDiscount::ClearDiscountCache([
                        'PRODUCT' => true,
                        'SECTIONS' => true,
                        'PROPERTIES' => true,
                    ]);
                }
            }
        }

        return $count;
    }

    /**
     * @param array<string, mixed> $iblock
     */
    private static function resolveSiteId(array $iblock): string
    {
        $lid = trim((string) ($iblock['LID'] ?? ''));
        if ($lid !== '') {
            return $lid;
        }

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
     * @return array{base: float, discount: float}|null
     */
    private static function resolveProductPrice(int $productId, string $siteId): ?array
    {
        $optimalPrice = CCatalogProduct::GetOptimalPrice(
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

        return [
            'base' => $basePrice > 0 ? $basePrice : $discountPrice,
            'discount' => $discountPrice > 0 ? $discountPrice : $basePrice,
        ];
    }

    /**
     * @param array<int, true> $usedSectionIds
     * @param array<int, list<int>> $navChainCache
     * @return list<int>
     */
    private static function collectProductCategoryIds(
        int $productId,
        int $iblockId,
        array &$usedSectionIds,
        array &$navChainCache
    ): array {
        $categoryIds = [];
        $groups = CIBlockElement::GetElementGroups(
            $productId,
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'ADDITIONAL_PROPERTY_ID', 'ACTIVE', 'IBLOCK_ID']
        );

        while ($group = $groups->Fetch()) {
            if ((int) ($group['ADDITIONAL_PROPERTY_ID'] ?? 0) > 0) {
                continue;
            }
            if (($group['ACTIVE'] ?? '') !== 'Y') {
                continue;
            }

            $sectionId = (int) ($group['ID'] ?? 0);
            if ($sectionId <= 0) {
                continue;
            }

            $categoryIds[] = $sectionId;
            self::markSectionTree($iblockId, $sectionId, $usedSectionIds, $navChainCache);
        }

        return array_values(array_unique($categoryIds));
    }

    /**
     * @param array<int, true> $usedSectionIds
     * @param array<int, list<int>> $navChainCache
     */
    private static function markSectionTree(
        int $iblockId,
        int $sectionId,
        array &$usedSectionIds,
        array &$navChainCache
    ): void {
        if (!isset($navChainCache[$sectionId])) {
            $chain = [];
            $nav = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID']);
            while ($row = $nav->Fetch()) {
                $id = (int) ($row['ID'] ?? 0);
                if ($id > 0) {
                    $chain[] = $id;
                }
            }
            $navChainCache[$sectionId] = $chain;
        }

        foreach ($navChainCache[$sectionId] as $id) {
            $usedSectionIds[$id] = true;
        }
    }

    /**
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $props
     * @param array{base: float, discount: float} $priceData
     * @param list<int> $categoryIds
     */
    private static function buildOfferXml(
        array $fields,
        array $props,
        string $siteUrl,
        array $priceData,
        array $categoryIds
    ): string {
        $productId = (int) ($fields['ID'] ?? 0);
        $name = trim((string) ($fields['~NAME'] ?? $fields['NAME'] ?? ''));
        if ($productId <= 0 || $name === '') {
            return '';
        }

        $available = self::isProductAvailable($productId) ? 'true' : 'false';
        $groupId = self::resolveGroupId($props[self::GROUPING_PROPERTY_CODE] ?? null);

        $open = '      <offer id="' . self::escapeXml((string) $productId) . '" available="' . $available . '"';
        if ($groupId !== '') {
            $open .= ' group_id="' . self::escapeXml($groupId) . '"';
        }
        $open .= '>';

        $lines = [
            $open,
            '        <name><![CDATA[' . self::sanitizeCdata($name) . ']]></name>',
        ];

        $url = self::resolveProductUrl($fields, $siteUrl);
        if ($url !== '') {
            $lines[] = '        <url>' . self::escapeXml($url) . '</url>';
        }

        $lines[] = '        <price>' . self::formatPrice($priceData['discount']) . '</price>';
        if ($priceData['base'] > $priceData['discount']) {
            $lines[] = '        <oldprice>' . self::formatPrice($priceData['base']) . '</oldprice>';
        }

        foreach ($categoryIds as $categoryId) {
            $lines[] = '        <categoryId>' . $categoryId . '</categoryId>';
        }

        $vendor = self::resolveBrandName($props['BRAND'] ?? null);
        if ($vendor === '') {
            $vendor = self::firstPropertyValue($props['BREND'] ?? null);
        }
        if ($vendor !== '') {
            $lines[] = '        <vendor>' . self::escapeXml($vendor) . '</vendor>';
        }

        $vendorCode = self::firstPropertyValue($props['CML2_ARTICLE'] ?? null);
        if ($vendorCode !== '') {
            $lines[] = '        <vendorCode>' . self::escapeXml($vendorCode) . '</vendorCode>';
        }

        $picture = self::resolveImageUrl($fields, $props, $siteUrl);
        if ($picture !== '') {
            $lines[] = '        <picture>' . self::escapeXml($picture) . '</picture>';
        }

        foreach (self::buildParamTags($props) as $paramLine) {
            $lines[] = '        ' . $paramLine;
        }

        $lines[] = '      </offer>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function resolveProductUrl(array $fields, string $siteUrl): string
    {
        $detailUrl = (string) ($fields['~DETAIL_PAGE_URL'] ?? $fields['DETAIL_PAGE_URL'] ?? '');
        $detailUrl = str_replace(' ', '%20', $detailUrl);
        if ($detailUrl === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $detailUrl) === 1) {
            return $detailUrl;
        }

        return $siteUrl . $detailUrl;
    }

    private static function isProductAvailable(int $productId): bool
    {
        $catalogProduct = CCatalogProduct::GetByID($productId);
        if (!is_array($catalogProduct)) {
            return false;
        }

        $quantity = (float) ($catalogProduct['QUANTITY'] ?? 0);
        $canBuyZero = (string) ($catalogProduct['CAN_BUY_ZERO'] ?? 'N');

        return $quantity > 0 || $canBuyZero === 'Y';
    }

    /**
     * @param array<string, mixed>|null $property
     */
    private static function resolveGroupId(?array $property): string
    {
        $value = self::firstPropertyValue($property);
        if ($value === '') {
            return '';
        }

        return preg_replace('/\s+/u', '', $value) ?? '';
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
        $name = trim(html_entity_decode(strip_tags((string) $displayValue), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
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

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        return $siteUrl . $path;
    }

    /**
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
     * @param array<string, mixed> $props
     * @return list<string>
     */
    private static function buildParamTags(array $props): array
    {
        $tags = [];

        foreach (self::PARAM_PROPERTIES as $code => $config) {
            $values = self::propertyValues($props[$code] ?? null);
            if ($values === []) {
                continue;
            }

            $asYes = ($config['yes'] ?? false) === true;
            $unit = (string) ($config['unit'] ?? '');

            if ($asYes) {
                $tags[] = '<param name="' . self::escapeXml($config['name']) . '">да</param>';
                continue;
            }

            foreach ($values as $value) {
                $unitAttr = '';
                if ($unit !== '' && self::isNumericValue($value)) {
                    $unitAttr = ' unit="' . self::escapeXml($unit) . '"';
                }

                $tags[] = '<param name="' . self::escapeXml($config['name']) . '"' . $unitAttr . '>'
                    . self::escapeXml($value)
                    . '</param>';
            }
        }

        $barcode = '';
        foreach (self::BARCODE_PROPERTY_CODES as $code) {
            $barcode = self::firstPropertyValue($props[$code] ?? null);
            if ($barcode !== '') {
                break;
            }
        }
        if ($barcode !== '') {
            $tags[] = '<param name="Штрихкод">' . self::escapeXml($barcode) . '</param>';
        }

        return $tags;
    }

    /**
     * @param array<string, mixed>|null $property
     */
    private static function firstPropertyValue(?array $property): string
    {
        $values = self::propertyValues($property);

        return $values[0] ?? '';
    }

    /**
     * @param array<string, mixed>|null $property
     * @return list<string>
     */
    private static function propertyValues(?array $property): array
    {
        if ($property === null) {
            return [];
        }

        $display = $property['DISPLAY_VALUE'] ?? null;
        if ($display !== null && $display !== false && $display !== '') {
            $rawList = is_array($display) ? $display : [$display];
        } else {
            $enum = $property['VALUE_ENUM'] ?? null;
            if ($enum !== null && $enum !== false && $enum !== '') {
                $rawList = is_array($enum) ? $enum : [$enum];
            } else {
                $value = $property['VALUE'] ?? null;
                if ($value === null || $value === false || $value === '') {
                    return [];
                }
                $rawList = is_array($value) ? $value : [$value];
            }
        }

        $result = [];
        foreach ($rawList as $item) {
            if (is_array($item)) {
                $item = $item['TEXT'] ?? $item['VALUE'] ?? $item['name'] ?? reset($item);
            }
            $text = trim(html_entity_decode(strip_tags((string) $item), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') {
                $result[] = $text;
            }
        }

        return array_values(array_unique($result));
    }

    private static function isNumericValue(string $value): bool
    {
        return preg_match('/^\d+([.,]\d+)?$/u', $value) === 1;
    }

    /**
     * @param resource $fp
     */
    private static function writeFeedHeader($fp, string $siteUrl, string $shopName): void
    {
        $date = (new DateTimeImmutable('now', new DateTimeZone(date_default_timezone_get() ?: 'Europe/Minsk')))
            ->format('Y-m-d\TH:i:sP');

        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($fp, '<yml_catalog date="' . self::escapeXml($date) . '">' . "\n");
        fwrite($fp, "  <shop>\n");
        fwrite($fp, '    <name>' . self::escapeXml($shopName) . "</name>\n");
        fwrite($fp, '    <company>' . self::escapeXml($shopName) . "</company>\n");
        fwrite($fp, '    <url>' . self::escapeXml($siteUrl) . "</url>\n");
    }

    /**
     * @param resource $fp
     * @param array<int, true> $usedSectionIds
     */
    private static function writeCategories($fp, array $usedSectionIds, string $siteUrl): void
    {
        fwrite($fp, "    <categories>\n");

        $ids = array_keys($usedSectionIds);
        if ($ids === []) {
            fwrite($fp, "    </categories>\n");

            return;
        }

        $sections = [];
        $res = CIBlockSection::GetList(
            ['LEFT_MARGIN' => 'ASC'],
            [
                'ID' => $ids,
                'ACTIVE' => 'Y',
                'GLOBAL_ACTIVE' => 'Y',
            ],
            false,
            ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'SECTION_PAGE_URL']
        );
        while ($section = $res->GetNext()) {
            $id = (int) ($section['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $sections[$id] = $section;
        }

        foreach ($sections as $id => $section) {
            $parentId = (int) ($section['IBLOCK_SECTION_ID'] ?? 0);
            $name = trim((string) ($section['~NAME'] ?? $section['NAME'] ?? ''));
            if ($name === '') {
                continue;
            }

            $attrs = 'id="' . $id . '"';
            if ($parentId > 0 && isset($sections[$parentId])) {
                $attrs .= ' parentId="' . $parentId . '"';
            }

            $sectionUrl = (string) ($section['~SECTION_PAGE_URL'] ?? $section['SECTION_PAGE_URL'] ?? '');
            $sectionUrl = str_replace(' ', '%20', $sectionUrl);
            if ($sectionUrl !== '') {
                if (preg_match('#^https?://#i', $sectionUrl) !== 1) {
                    $sectionUrl = $siteUrl . $sectionUrl;
                }
                $attrs .= ' url="' . self::escapeXml($sectionUrl) . '"';
            }

            fwrite(
                $fp,
                '      <category ' . $attrs . '>' . self::escapeXml($name) . "</category>\n"
            );
        }

        fwrite($fp, "    </categories>\n");
    }

    /**
     * @param resource $fp
     */
    private static function appendFile($fp, string $path): void
    {
        $src = @fopen($path, 'rb');
        if ($src === false) {
            return;
        }

        try {
            while (!feof($src)) {
                $chunk = fread($src, 8192);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                fwrite($fp, $chunk);
            }
        } finally {
            fclose($src);
        }
    }

    private static function formatPrice(float $price): string
    {
        $formatted = number_format($price, 2, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function sanitizeCdata(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return str_replace(']]>', ']]]]><![CDATA[>', $value);
    }
}
