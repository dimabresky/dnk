<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use CBlog;
use CBlogComment;
use CBlogPost;

/**
 * Обработчик OnAfterCommentAdd: при публикации товарного отзыва ставит пользователя
 * в очередь на POST начисления бонусов (ReviewBonusQueueTable).
 */
final class ReviewBonusEvents
{
    private const CATALOG_COMMENTS_URL = 'catalog_comments';

    /**
     * @param int|string $commentId
     */
    public static function onAfterCommentAdd($commentId): void
    {
        $commentId = (int)$commentId;
        if ($commentId <= 0) {
            return;
        }

        $comment = CBlogComment::GetByID($commentId);
        if (!$comment || !$comment = $comment->Fetch()) {
            return;
        }

        $postId = (int)($comment['POST_ID'] ?? 0);
        if ($postId <= 0) {
            return;
        }

        $blogPost = CBlogPost::GetByID($postId);
        if (!$blogPost || !$blogPost = $blogPost->Fetch()) {
            return;
        }

        $blogId = (int)($blogPost['BLOG_ID'] ?? 0);
        if ($blogId <= 0) {
            return;
        }

        $blog = CBlog::GetByID($blogId);
        if (!$blog || !$blog = $blog->Fetch()) {
            return;
        }

        if (($blog['URL'] ?? '') !== self::CATALOG_COMMENTS_URL) {
            return;
        }

        $authorId = (int)($comment['AUTHOR_ID'] ?? 0);
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
