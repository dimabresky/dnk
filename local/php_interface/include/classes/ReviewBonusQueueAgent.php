<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use CBlog;
use CBlogComment;
use CBlogPost;

/**
 * Агент крона: обработка очереди начисления бонусов за отзывы.
 * Регистрация в админке: \Dnk\PhpInterface\ReviewBonusQueueAgent::runReviewBonusQueueAgent();
 * Интервал — DNK_REVIEW_BONUS_AGENT_INTERVAL (сек), периодический.
 */
final class ReviewBonusQueueAgent
{
    private const CATALOG_COMMENTS_URL = 'catalog_comments';

    public static function runReviewBonusQueueAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\ReviewBonusQueueAgent::runReviewBonusQueueAgent();";

        if (!Loader::includeModule('blog')) {
            return $return;
        }

        $endpoint = defined('DNK_REVIEW_BONUS_ENDPOINT')
            ? trim((string)DNK_REVIEW_BONUS_ENDPOINT)
            : '';
        if ($endpoint === '') {
            return $return;
        }

        $batch = defined('DNK_REVIEW_BONUS_QUEUE_BATCH')
            ? (int)DNK_REVIEW_BONUS_QUEUE_BATCH
            : 10;
        if ($batch < 1) {
            $batch = 10;
        }

        $maxAttempts = defined('DNK_REVIEW_BONUS_MAX_ATTEMPTS')
            ? (int)DNK_REVIEW_BONUS_MAX_ATTEMPTS
            : 5;
        if ($maxAttempts < 1) {
            $maxAttempts = 5;
        }

        $result = ReviewBonusQueueTable::getList([
            'select' => ['ID', 'USER_ID', 'ATTEMPTS', 'DATE_LAST_SENT'],
            'filter' => ['=STATUS' => ReviewBonusQueueTable::STATUS_PENDING],
            'order' => ['ID' => 'ASC'],
            'limit' => $batch,
        ]);

        while ($row = $result->fetch()) {
            $id = (int)$row['ID'];
            $userId = (int)$row['USER_ID'];
            $attempts = (int)$row['ATTEMPTS'];

            $phone = Utils::resolveUserPhoneDigitsForBonus($userId);
            if ($phone === null || $phone === '') {
                self::fail($id, $attempts, $maxAttempts, 'no_phone');
                continue;
            }

            $blogId = self::resolveCatalogBlogId();
            if ($blogId === null) {
                self::fail($id, $attempts, $maxAttempts, 'blog_not_found');
                continue;
            }

            $postIds = self::collectPostIds($blogId);
            if ($postIds === []) {
                self::fail($id, $attempts, $maxAttempts, 'no_blog_posts');
                continue;
            }

            $reviewsCount = self::countReviews($postIds, $userId, $row['DATE_LAST_SENT'] ?? null);
            if ($reviewsCount === 0) {
                ReviewBonusQueueTable::update($id, [
                    'STATUS' => ReviewBonusQueueTable::STATUS_SENT,
                    'DATE_UPDATE' => new DateTime(),
                ]);

                continue;
            }

            $payload = [
                'date_upload' => date('Y-m-d'),
                'clients' => [
                    [
                        'phone' => $phone,
                        'reviews_count' => $reviewsCount,
                    ],
                ],
            ];

            $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                self::fail($id, $attempts, $maxAttempts, 'json_encode_failed');
                continue;
            }

            $sendResult = self::sendPayload($endpoint, $body);
            if ($sendResult['ok']) {
                ReviewBonusQueueTable::update($id, [
                    'STATUS' => ReviewBonusQueueTable::STATUS_SENT,
                    'DATE_LAST_SENT' => new DateTime(),
                    'ATTEMPTS' => 0,
                    'LAST_ERROR' => null,
                    'DATE_UPDATE' => new DateTime(),
                ]);

                continue;
            }

            self::fail($id, $attempts, $maxAttempts, $sendResult['error']);
        }

        return $return;
    }

    private static function resolveCatalogBlogId(): ?int
    {
        $blog = CBlog::GetList([], ['URL' => self::CATALOG_COMMENTS_URL]);
        if (!$blog || !$blog = $blog->Fetch()) {
            return null;
        }

        $blogId = (int)($blog['ID'] ?? 0);

        return $blogId > 0 ? $blogId : null;
    }

    /**
     * @return int[]
     */
    private static function collectPostIds(int $blogId): array
    {
        $postIds = [];
        $posts = CBlogPost::GetList(
            ['ID' => 'ASC'],
            ['BLOG_ID' => $blogId, 'PUBLISH_STATUS' => 'P'],
            false,
            false,
            ['ID']
        );
        while ($post = $posts->Fetch()) {
            $postId = (int)($post['ID'] ?? 0);
            if ($postId > 0) {
                $postIds[] = $postId;
            }
        }

        return $postIds;
    }

    /**
     * @param int[] $postIds
     */
    private static function countReviews(array $postIds, int $userId, ?string $dateLastSent): int
    {
        $filter = [
            '@POST_ID' => $postIds,
            '=AUTHOR_ID' => $userId,
            '=PUBLISH_STATUS' => 'P',
            '=PARENT_ID' => 0,
        ];

        if ($dateLastSent !== null) {
            $filter['>DATE_CREATE'] = $dateLastSent;
        }

        $count = 0;
        $res = CBlogComment::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            false,
            ['ID']
        );
        while ($res->Fetch()) {
            ++$count;
        }

        return $count;
    }

    private static function fail(int $id, int $attempts, int $maxAttempts, string $error): void
    {
        $attempts++;
        ReviewBonusQueueTable::update($id, [
            'STATUS' => $attempts >= $maxAttempts
                ? ReviewBonusQueueTable::STATUS_ERROR
                : ReviewBonusQueueTable::STATUS_PENDING,
            'ATTEMPTS' => $attempts,
            'LAST_ERROR' => mb_substr($error, 0, 500),
            'DATE_UPDATE' => new DateTime(),
        ]);
    }

    /**
     * @return array{ok: bool, error: string}
     */
    private static function sendPayload(string $url, string $body): array
    {
        $http = new HttpClient([
            'socketTimeout' => 15,
            'streamTimeout' => 15,
        ]);
        $http->setHeader('Content-Type', 'application/json; charset=UTF-8');
        $http->setHeader('Accept', 'application/json');

        if (defined('DNK_ORDER_EXPORT_LOGIN') && defined('DNK_ORDER_EXPORT_PASSWORD')) {
            $http->setAuthorization(
                (string)DNK_ORDER_EXPORT_LOGIN,
                (string)DNK_ORDER_EXPORT_PASSWORD
            );
        }

        try {
            $response = $http->post($url, $body);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        $status = $http->getStatus();
        if ($status >= 200 && $status < 300) {
            return ['ok' => true, 'error' => ''];
        }

        $err = 'HTTP ' . $status;
        if (is_string($response) && $response !== '') {
            $err .= ': ' . mb_substr($response, 0, 500);
        }

        return ['ok' => false, 'error' => $err];
    }
}
