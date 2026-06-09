<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use CBlogComment;
use CIBlock;
use CIBlockElement;

/**
 * Пересчёт EXTENDED_REVIEWS_COUNT и EXTENDED_REVIEWS_RAITING по опубликованным отзывам блога.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\ProductExtendedReviewsAgent::runProductExtendedReviewsAgent();
 */
final class ProductExtendedReviewsAgent
{
    public static function runProductExtendedReviewsAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\ProductExtendedReviewsAgent::runProductExtendedReviewsAgent();";

        if (!defined('DNK_CATALOG_IBLOCK_ID')) {
            return $return;
        }
        if (!Loader::includeModule('iblock') || !Loader::includeModule('blog')) {
            return $return;
        }

        $catalogIblockId = (int) DNK_CATALOG_IBLOCK_ID;
        $res = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            ['IBLOCK_ID' => $catalogIblockId, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID']
        );

        while ($row = $res->Fetch()) {
            $elementId = (int) ($row['ID'] ?? 0);
            if ($elementId > 0) {
                self::syncExtendedReviewsForElement($catalogIblockId, $elementId, false);
            }
        }

        CIBlock::clearIblockTagCache($catalogIblockId);

        return $return;
    }

    /**
     * Пересчитывает свойства отзывов для одного товара каталога.
     */
    public static function syncExtendedReviewsForElement(int $iblockId, int $elementId, bool $clearIblockCache = true): bool
    {
        if (!defined('DNK_CATALOG_IBLOCK_ID') || $iblockId !== (int) DNK_CATALOG_IBLOCK_ID || $elementId <= 0) {
            return false;
        }
        if (!Loader::includeModule('iblock') || !Loader::includeModule('blog')) {
            return false;
        }

        $blogPostId = self::getBlogPostId($iblockId, $elementId);
        if ($blogPostId <= 0) {
            self::saveExtendedReviewsProps($elementId, $iblockId, 0, 0);

            if ($clearIblockCache) {
                CIBlock::clearIblockTagCache($iblockId);
            }

            return true;
        }

        $commentsCount = 0;
        $commentsRating = 0;
        $commentsCountRating = 0;

        $resBlog = CBlogComment::GetList(
            ['ID' => 'DESC'],
            ['POST_ID' => $blogPostId, 'PARENT_ID' => false, 'PUBLISH_STATUS' => 'P'],
            false,
            false,
            ['ID', 'UF_ASPRO_COM_RATING']
        );
        while ($comment = $resBlog->Fetch()) {
            ++$commentsCount;

            if (!empty($comment['UF_ASPRO_COM_RATING'])) {
                ++$commentsCountRating;
                $commentsRating += (int) $comment['UF_ASPRO_COM_RATING'];
            }
        }

        $rating = $commentsRating > 0
            ? round($commentsRating / $commentsCountRating, 1)
            : 0;

        self::saveExtendedReviewsProps($elementId, $iblockId, $commentsCount, $rating);

        if ($clearIblockCache) {
            CIBlock::clearIblockTagCache($iblockId);
        }

        return true;
    }

    private static function getBlogPostId(int $iblockId, int $elementId): int
    {
        $row = CIBlockElement::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $elementId],
            false,
            ['nTopCount' => 1],
            ['ID', 'PROPERTY_BLOG_POST_ID']
        )->Fetch();

        if (!is_array($row)) {
            return 0;
        }

        return (int) ($row['PROPERTY_BLOG_POST_ID_VALUE'] ?? $row['PROPERTY_BLOG_POST_ID'] ?? 0);
    }

    private static function saveExtendedReviewsProps(int $elementId, int $iblockId, int $count, float $rating): void
    {
        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [
            'EXTENDED_REVIEWS_COUNT' => $count,
            'EXTENDED_REVIEWS_RAITING' => $rating,
        ]);
    }
}
