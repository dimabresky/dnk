<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\HttpClient;
use CBlog;
use CBlogComment;
use CBlogPost;

/**
 * Агент крона: обработка очереди начисления бонусов за отзывы.
 * Регистрация в админке: \Dnk\PhpInterface\ReviewBonusQueueAgent::runReviewBonusQueueAgent();
 * Интервал — DNK_REVIEW_BONUS_AGENT_INTERVAL (сек), периодический.
 *
 * В POST попадают только комментарии с ID больше LAST_COMMENT_ID.
 * Метка сдвигается только после success: true, неуспешный запрос эти отзывы не закрывает.
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
            'select' => ['ID', 'USER_ID', 'ATTEMPTS', 'LAST_COMMENT_ID'],
            'filter' => ['=STATUS' => ReviewBonusQueueTable::STATUS_PENDING],
            'order' => ['ID' => 'ASC'],
            'limit' => $batch,
        ]);

        $rows = [];
        while ($row = $result->fetch()) {
            $rows[] = $row;
        }
        if ($rows === []) {
            return $return;
        }

        $blogId = self::resolveCatalogBlogId();
        if ($blogId === null) {
            foreach ($rows as $row) {
                self::fail((int)$row['ID'], (int)$row['ATTEMPTS'], $maxAttempts, 'blog_not_found');
            }

            return $return;
        }

        $postIds = self::collectPostIds($blogId);
        if ($postIds === []) {
            foreach ($rows as $row) {
                self::fail((int)$row['ID'], (int)$row['ATTEMPTS'], $maxAttempts, 'no_blog_posts');
            }

            return $return;
        }

        /** @var list<array{id: int, attempts: int, phone: string, reviewsCount: int, maxCommentId: int}> $clients */
        $clients = [];
        foreach ($rows as $row) {
            $id = (int)$row['ID'];
            $userId = (int)$row['USER_ID'];
            $attempts = (int)$row['ATTEMPTS'];
            $lastCommentId = (int)($row['LAST_COMMENT_ID'] ?? 0);

            $phone = self::resolvePhone($userId);
            if ($phone === '') {
                self::fail($id, $attempts, $maxAttempts, 'no_phone');
                continue;
            }

            $reviews = self::collectNewReviews($postIds, $userId, $lastCommentId);
            if ($reviews['count'] === 0) {
                ReviewBonusQueueTable::update($id, [
                    'STATUS' => ReviewBonusQueueTable::STATUS_SENT,
                    'DATE_UPDATE' => new DateTime(),
                ]);
                continue;
            }

            $clients[] = [
                'id' => $id,
                'attempts' => $attempts,
                'phone' => $phone,
                'reviewsCount' => $reviews['count'],
                'maxCommentId' => $reviews['maxId'],
            ];
        }

        if ($clients === []) {
            return $return;
        }

        $payloadClients = [];
        foreach ($clients as $client) {
            $payloadClients[] = [
                'phone' => $client['phone'],
                'reviews_count' => $client['reviewsCount'],
            ];
        }

        $body = json_encode(
            [
                'date_upload' => date('Y-m-d'),
                'clients' => $payloadClients,
            ],
            JSON_UNESCAPED_UNICODE
        );
        if ($body === false) {
            foreach ($clients as $client) {
                self::fail($client['id'], $client['attempts'], $maxAttempts, 'json_encode_failed');
            }

            return $return;
        }

        $sendResult = self::sendPayload($endpoint, $body);
        $now = new DateTime();
        foreach ($clients as $client) {
            if (!$sendResult['ok']) {
                self::fail($client['id'], $client['attempts'], $maxAttempts, $sendResult['error']);
                continue;
            }

            ReviewBonusQueueTable::update($client['id'], [
                'LAST_COMMENT_ID' => $client['maxCommentId'],
                'STATUS' => ReviewBonusQueueTable::STATUS_SENT,
                'DATE_LAST_SENT' => $now,
                'ATTEMPTS' => 0,
                'LAST_ERROR' => null,
                'DATE_UPDATE' => $now,
            ]);
        }

        return $return;
    }

    private static function resolvePhone(int $userId): string
    {
        $client = Utils::buildOrderExportClientBlock($userId);

        return trim((string)($client['phone'] ?? ''));
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
     * Опубликованные корневые отзывы пользователя, которые ещё не входили в POST.
     *
     * @param int[] $postIds
     *
     * @return array{count: int, maxId: int}
     */
    private static function collectNewReviews(array $postIds, int $userId, int $lastCommentId): array
    {
        $filter = [
            '@POST_ID' => $postIds,
            '=AUTHOR_ID' => $userId,
            '=PUBLISH_STATUS' => 'P',
            '=PARENT_ID' => 0,
        ];
        if ($lastCommentId > 0) {
            $filter['>ID'] = $lastCommentId;
        }

        $count = 0;
        $maxId = $lastCommentId;
        $res = CBlogComment::GetList(
            ['ID' => 'ASC'],
            $filter,
            false,
            false,
            ['ID']
        );
        while ($comment = $res->Fetch()) {
            $commentId = (int)($comment['ID'] ?? 0);
            if ($commentId <= 0) {
                continue;
            }

            ++$count;
            if ($commentId > $maxId) {
                $maxId = $commentId;
            }
        }

        return ['count' => $count, 'maxId' => $maxId];
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
        $responseStr = is_string($response) ? $response : '';
        if ($status < 200 || $status >= 300) {
            $err = 'HTTP ' . $status;
            if ($responseStr !== '') {
                $err .= ': ' . mb_substr($responseStr, 0, 500);
            }

            return ['ok' => false, 'error' => $err];
        }

        $decoded = json_decode($responseStr, true);
        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'invalid_json'];
        }

        if (($decoded['success'] ?? false) !== true) {
            return ['ok' => false, 'error' => self::formatApiError($decoded)];
        }

        return ['ok' => true, 'error' => ''];
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private static function formatApiError(array $decoded): string
    {
        $error = trim((string)($decoded['error'] ?? ''));
        if ($error === '') {
            $error = 'success_false';
        }

        $details = $decoded['details'] ?? null;
        if (!is_array($details)) {
            return $error;
        }

        $parts = [];
        foreach ($details as $detail) {
            $text = trim((string)$detail);
            if ($text !== '') {
                $parts[] = $text;
            }
        }

        if ($parts === []) {
            return $error;
        }

        return $error . ': ' . implode('; ', $parts);
    }
}
