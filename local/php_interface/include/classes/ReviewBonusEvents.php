<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use CBlog;
use CBlogComment;
use CBlogPost;

/**
 * Обработчик OnCommentAdd: при добавлении товарного отзыва ставит пользователя
 * в очередь на POST начисления бонусов (ReviewBonusQueueTable).
 */
final class ReviewBonusEvents
{
    private const CATALOG_COMMENTS_URL = 'catalog_comments';

    /**
     * @param int|string $commentId
     * @param array<string, mixed> $fields
     */
    public static function onCommentAdd($commentId, array $fields = []): void
    {
        $commentId = (int)$commentId;
        if ($commentId <= 0) {
            return;
        }

        $comment = CBlogComment::GetByID($commentId);
        if (!is_array($comment)) {
            return;
        }

        $postId = (int)($comment['POST_ID'] ?? 0);
        if ($postId <= 0) {
            return;
        }

        $blogPost = CBlogPost::GetByID($postId);
        if (!is_array($blogPost)) {
            return;
        }

        $blogId = (int)($blogPost['BLOG_ID'] ?? 0);
        if ($blogId <= 0) {
            return;
        }

        $blog = CBlog::GetByID($blogId);
        if (!is_array($blog)) {
            return;
        }

        if (($blog['URL'] ?? '') !== self::CATALOG_COMMENTS_URL) {
            return;
        }

        $authorId = (int)($comment['AUTHOR_ID'] ?? 0);
        if ($authorId <= 0) {
            $authorId = (int)($fields['AUTHOR_ID'] ?? 0);
        }
        if ($authorId <= 0) {
            return;
        }

        $existing = ReviewBonusQueueTable::getList([
            'select' => ['ID', 'STATUS'],
            'filter' => ['=USER_ID' => $authorId],
            'limit' => 1,
        ])->fetch();

        $now = new DateTime();

        if ($existing !== false) {
            if ($existing['STATUS'] === ReviewBonusQueueTable::STATUS_PENDING) {
                return;
            }

            // LAST_COMMENT_ID не сбрасываем: уже переданные отзывы в новый POST не попадают.
            ReviewBonusQueueTable::update((int)$existing['ID'], [
                'STATUS' => ReviewBonusQueueTable::STATUS_PENDING,
                'ATTEMPTS' => 0,
                'LAST_ERROR' => null,
                'DATE_UPDATE' => $now,
            ]);

            return;
        }

        ReviewBonusQueueTable::add([
            'USER_ID' => $authorId,
            'STATUS' => ReviewBonusQueueTable::STATUS_PENDING,
            'ATTEMPTS' => 0,
            'DATE_INSERT' => $now,
        ]);
    }
}
