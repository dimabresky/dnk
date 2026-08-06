<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Type\DateTime;
use CFile;
use CIBlockElement;

/**
 * Агент: генерация свойства FEED_PICTURE из DETAIL_PICTURE с фоном #F8F8FC.
 *
 * Зарегистрировать в админке: Настройки → Инструменты → Агенты — PHP-строка:
 * \Dnk\PhpInterface\FeedPictureAgent::runFeedPictureAgent();
 * Интервал — DNK_FEED_PICTURE_AGENT_INTERVAL (сек).
 */
final class FeedPictureAgent
{
    public static function runFeedPictureAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\FeedPictureAgent::runFeedPictureAgent();";

        self::processQueue();

        return $return;
    }

    /**
     * @return array{processed:int,success:int,failed:int,stale:int,skipped_lock:bool}
     */
    public static function processQueue(): array
    {
        $stats = [
            'processed' => 0,
            'success' => 0,
            'failed' => 0,
            'stale' => 0,
            'skipped_lock' => false,
        ];

        if (!\CModule::IncludeModule('iblock')) {
            return $stats;
        }

        $lock = self::acquireWorkerLock();
        if ($lock['handle'] === null) {
            $stats['skipped_lock'] = true;
            if ($lock['reason'] !== 'busy') {
                AddMessage2Log(
                    'FeedPictureAgent lock unavailable: ' . $lock['reason'],
                    'dnk.feed_picture'
                );
            }

            return $stats;
        }
        $lockFh = $lock['handle'];

        try {
            $batch = defined('DNK_FEED_PICTURE_QUEUE_BATCH') ? (int)DNK_FEED_PICTURE_QUEUE_BATCH : 5;
            if ($batch < 1) {
                $batch = 5;
            }

            $maxAttempts = defined('DNK_FEED_PICTURE_MAX_ATTEMPTS') ? (int)DNK_FEED_PICTURE_MAX_ATTEMPTS : 5;
            if ($maxAttempts < 1) {
                $maxAttempts = 5;
            }

            self::reclaimStaleWorkingJobs();

            $result = FeedPictureQueueTable::getList([
                'select' => [
                    'ID',
                    'ELEMENT_ID',
                    'IBLOCK_ID',
                    'DETAIL_FILE_ID',
                    'ATTEMPTS',
                ],
                'filter' => ['=STATUS' => FeedPictureQueueTable::STATUS_PENDING],
                'order' => ['ID' => 'ASC'],
                'limit' => $batch,
            ]);

            while ($row = $result->fetch()) {
                $stats['processed']++;
                $id = (int)$row['ID'];
                $attempts = (int)$row['ATTEMPTS'];

                FeedPictureQueueTable::update($id, [
                    'STATUS' => FeedPictureQueueTable::STATUS_WORKING,
                    'DATE_UPDATE' => new DateTime(),
                ]);

                try {
                    $outcome = self::processJob($row);
                    FeedPictureQueueTable::delete($id);
                    if ($outcome === 'stale') {
                        $stats['stale']++;
                    } else {
                        $stats['success']++;
                    }
                } catch (\Throwable $e) {
                    $attempts++;
                    FeedPictureQueueTable::update($id, [
                        'STATUS' => $attempts >= $maxAttempts
                            ? FeedPictureQueueTable::STATUS_ERROR
                            : FeedPictureQueueTable::STATUS_PENDING,
                        'ATTEMPTS' => $attempts,
                        'LAST_ERROR' => $e->getMessage(),
                        'DATE_UPDATE' => new DateTime(),
                    ]);
                    $stats['failed']++;
                    AddMessage2Log(
                        sprintf(
                            'FeedPictureAgent job #%d element=%d file=%d: %s',
                            $id,
                            (int)$row['ELEMENT_ID'],
                            (int)$row['DETAIL_FILE_ID'],
                            $e->getMessage()
                        ),
                        'dnk.feed_picture'
                    );
                }
            }
        } finally {
            flock($lockFh, LOCK_UN);
            fclose($lockFh);
        }

        return $stats;
    }

    /**
     * @return array{handle:resource|null,reason:string}
     */
    private static function acquireWorkerLock(): array
    {
        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
        if ($docRoot === '') {
            return ['handle' => null, 'reason' => 'empty DOCUMENT_ROOT'];
        }

        $dir = $docRoot . '/upload/dnk_feed_picture';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return ['handle' => null, 'reason' => 'cannot create ' . $dir];
        }

        $lockPath = $dir . '/worker.lock';
        $lockFh = fopen($lockPath, 'c+');
        if ($lockFh === false) {
            return ['handle' => null, 'reason' => 'cannot open ' . $lockPath];
        }

        if (!flock($lockFh, LOCK_EX | LOCK_NB)) {
            fclose($lockFh);

            return ['handle' => null, 'reason' => 'busy'];
        }

        return ['handle' => $lockFh, 'reason' => ''];
    }

    /**
     * Return stuck WORKING rows to PENDING after a timeout (crashed worker).
     */
    private static function reclaimStaleWorkingJobs(): void
    {
        $timeoutSeconds = 900;
        $threshold = DateTime::createFromTimestamp(time() - $timeoutSeconds);

        $stale = FeedPictureQueueTable::getList([
            'select' => ['ID'],
            'filter' => [
                '=STATUS' => FeedPictureQueueTable::STATUS_WORKING,
                '<=DATE_UPDATE' => $threshold,
            ],
            'limit' => 100,
        ]);

        while ($row = $stale->fetch()) {
            FeedPictureQueueTable::update((int)$row['ID'], [
                'STATUS' => FeedPictureQueueTable::STATUS_PENDING,
                'DATE_UPDATE' => new DateTime(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return 'ok'|'stale'
     */
    private static function processJob(array $row): string
    {
        $iblockId = (int)$row['IBLOCK_ID'];
        $elementId = (int)$row['ELEMENT_ID'];
        $queuedFileId = (int)$row['DETAIL_FILE_ID'];

        $currentFileId = FeedPictureService::resolveDetailPictureFileId($iblockId, $elementId);
        if ($currentFileId <= 0 || $currentFileId !== $queuedFileId) {
            return 'stale';
        }

        $composed = FeedPictureComposer::composeFromFileId($queuedFileId);
        $fileArray = CFile::MakeFileArray($composed['path']);
        if (!is_array($fileArray) || empty($fileArray['tmp_name'])) {
            @unlink($composed['path']);
            throw new \RuntimeException('MakeFileArray failed for ' . $composed['path']);
        }

        $fileArray['name'] = $composed['name'];
        $fileArray['MODULE_ID'] = 'iblock';
        if (empty($fileArray['type'])) {
            $fileArray['type'] = 'image/jpeg';
        }

        $prop = Utils::getIblockPropertyByCode($iblockId, FeedPictureService::PROPERTY_CODE);
        if ($prop === null || (string)($prop['PROPERTY_TYPE'] ?? '') !== 'F') {
            @unlink($composed['path']);
            throw new \RuntimeException('Property FEED_PICTURE is missing on iblock ' . $iblockId);
        }

        FeedPictureService::beginInternalUpdate();
        try {
            CIBlockElement::SetPropertyValuesEx(
                $elementId,
                $iblockId,
                [
                    FeedPictureService::PROPERTY_CODE => [
                        'VALUE' => $fileArray,
                        'DESCRIPTION' => (string)$queuedFileId,
                    ],
                ]
            );
        } finally {
            FeedPictureService::endInternalUpdate();
            @unlink($composed['path']);
        }

        return 'ok';
    }
}
