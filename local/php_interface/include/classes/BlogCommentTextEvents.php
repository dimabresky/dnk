<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Context;
use CBlog;
use CBlogComment;

/**
 * Отклоняет отзывы каталога, в которых нет живого текста (в том числе заглушку <uniqid>).
 */
final class BlogCommentTextEvents
{
    private const CATALOG_COMMENTS_URL = 'catalog_comments';

    private const MIN_PLAIN_LENGTH = 5;

    private const MESSAGE = 'Введите текст отзыва — минимум 5 символов.';

    /**
     * @param array<string, mixed> $arFields
     */
    public static function onBeforeCommentAdd(array &$arFields): bool
    {
        if (!self::rejects($arFields)) {
            return true;
        }

        $GLOBALS['APPLICATION']->ThrowException(self::MESSAGE);

        return false;
    }

    /**
     * @param int|string $id
     * @param array<string, mixed> $arFields
     */
    public static function onBeforeCommentUpdate($id, array &$arFields): bool
    {
        // Only a posted form rating may change the UF. Callers that set it in $arFields keep their value.
        $rating = self::postedRating();
        if ($rating !== null) {
            if ($rating >= 1) {
                $arFields['UF_ASPRO_COM_RATING'] = $rating;
            } else {
                unset($arFields['UF_ASPRO_COM_RATING']);
            }
        }

        if (!array_key_exists('POST_TEXT', $arFields)) {
            return true;
        }

        $blogId = (int)($arFields['BLOG_ID'] ?? 0);
        if ($blogId <= 0) {
            $comment = CBlogComment::GetByID((int)$id);
            if (is_array($comment)) {
                $blogId = (int)($comment['BLOG_ID'] ?? 0);
            }
        }

        if (!self::rejects($arFields, $blogId)) {
            return true;
        }

        $GLOBALS['APPLICATION']->ThrowException(self::MESSAGE);

        return false;
    }

    /**
     * @param int|string $id
     * @param array<string, mixed> $arFields
     */
    public static function onCommentUpdate($id, array &$arFields): void
    {
        $rating = self::postedRating();
        if ($rating === null || $rating < 1) {
            return;
        }

        // CBlogComment::Update writes user fields from $arFields and can put the old score back.
        $GLOBALS['USER_FIELD_MANAGER']->Update('BLOG_COMMENT', (int) $id, [
            'UF_ASPRO_COM_RATING' => $rating,
        ]);
    }

    public static function isTextTooShort(string $postText): bool
    {
        return self::plainLength($postText) < self::MIN_PLAIN_LENGTH;
    }

    private static function postedRating(): ?int
    {
        $rating = Context::getCurrent()->getRequest()->getPost('rating');
        if ($rating === null) {
            return null;
        }
        if (is_array($rating)) {
            $rating = reset($rating);
        }
        if (!is_scalar($rating)) {
            return 0;
        }

        $rating = (int) trim((string) $rating);

        return ($rating >= 1 && $rating <= 5) ? $rating : 0;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private static function rejects(array $fields, int $blogId = 0): bool
    {
        if (!array_key_exists('POST_TEXT', $fields)) {
            return false;
        }

        if ($blogId <= 0) {
            $blogId = (int)($fields['BLOG_ID'] ?? 0);
        }

        if (!self::isCatalogCommentsBlog($blogId)) {
            return false;
        }

        return self::plainLength((string)$fields['POST_TEXT']) < self::MIN_PLAIN_LENGTH;
    }

    private static function isCatalogCommentsBlog(int $blogId): bool
    {
        if ($blogId <= 0) {
            return false;
        }

        $blog = CBlog::GetByID($blogId);

        return is_array($blog) && ($blog['URL'] ?? '') === self::CATALOG_COMMENTS_URL;
    }

    private static function plainLength(string $postText): int
    {
        $hasStructured = preg_match('/<(virtues|limitations|comment)\b/i', $postText) === 1;
        if ($hasStructured) {
            $length = 0;
            foreach (['virtues', 'limitations', 'comment'] as $tag) {
                if (preg_match('/<' . $tag . '\b[^>]*>(.*?)<\/' . $tag . '>/si', $postText, $matches) === 1) {
                    $length += mb_strlen(self::plainText($matches[1]));
                }
            }

            return $length;
        }

        $withoutUniqid = preg_replace('/<uniqid\b[^>]*>.*?<\/uniqid>/si', '', $postText);

        return mb_strlen(self::plainText(is_string($withoutUniqid) ? $withoutUniqid : $postText));
    }

    private static function plainText(string $value): string
    {
        $text = preg_replace('/<[^>]*>/', '', $value);
        if (!is_string($text)) {
            $text = $value;
        }

        $text = str_ireplace(['&nbsp;', '&#160;'], ' ', $text);
        $text = str_replace("\u{00a0}", ' ', $text);

        return trim($text);
    }
}
