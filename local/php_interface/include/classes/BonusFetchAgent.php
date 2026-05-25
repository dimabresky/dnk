<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;

/**
 * Агент: JSON-файлы в DNK_BONUS_CLIENT_IMPORT_DIR — импорт остатков бонусов и синхронизация с Aspro Bonus.
 */
final class BonusFetchAgent
{
    public static function runBonusAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\BonusFetchAgent::runBonusAgent();";

        if (!Loader::includeModule('aspro.bonus')) {
            return $return;
        }

        $importDir = defined('DNK_BONUS_CLIENT_IMPORT_DIR')
            ? trim((string)DNK_BONUS_CLIENT_IMPORT_DIR)
            : 'upload/clientbonus';
        $logDir = defined('DNK_BONUS_CLIENT_IMPORT_LOG_DIR')
            ? trim((string)DNK_BONUS_CLIENT_IMPORT_LOG_DIR)
            : 'upload/clientbonus_logs';

        $importPath = Utils::resolveDocumentRootSubdir($importDir);
        if (!is_dir($importPath)) {
            return $return;
        }

        $files = glob($importPath . '/*.json') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $filePath) {
            self::processImportFile($filePath, $logDir);
        }

        return $return;
    }

    private static function processImportFile(string $filePath, string $logDir): void
    {
        $basename = basename($filePath);
        $content = @file_get_contents($filePath);
        if ($content === false) {
            Utils::logClientBonusImportLine($logDir, $basename, '[error] read_failed file=' . $basename);

            return;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            Utils::logClientBonusImportLine($logDir, $basename, '[error] invalid_json file=' . $basename);

            return;
        }

        $balanceByDigits = self::buildBalanceByPhoneDigitsMap($decoded, $logDir, $basename);
        if ($balanceByDigits === []) {
            Utils::logClientBonusImportLine($logDir, $basename, '[info] empty_or_no_valid_rows file=' . $basename);
            @unlink($filePath);

            return;
        }

        $resolved = Utils::resolveUserIdsByBonusImportPhones(array_keys($balanceByDigits));

        foreach ($resolved['not_found'] as $digits) {
            Utils::logClientBonusImportLine(
                $logDir,
                $basename,
                '[not_found] phone=' . $digits . ' balance=' . $balanceByDigits[$digits]
            );
        }

        foreach ($resolved['ambiguous'] as $digits) {
            Utils::logClientBonusImportLine(
                $logDir,
                $basename,
                '[ambiguous_phone] phone=' . $digits . ' balance=' . $balanceByDigits[$digits]
            );
        }

        foreach ($resolved['found'] as $digits => $userId) {
            Utils::replaceDnkImportBonusesForUser($userId, $balanceByDigits[$digits]);
        }

        Utils::logClientBonusImportLine(
            $logDir,
            $basename,
            sprintf(
                '[done] file=%s processed=%d not_found=%d ambiguous=%d',
                $basename,
                count($resolved['found']),
                count($resolved['not_found']),
                count($resolved['ambiguous'])
            )
        );

        @unlink($filePath);
    }

    /**
     * По каждому телефону — значение НачисленоОстаток из последней подходящей строки (без суммирования).
     *
     * @param array<int, mixed> $rows
     * @return array<string, float> normalized phone digits => остаток
     */
    private static function buildBalanceByPhoneDigitsMap(array $rows, string $logDir, string $basename): array
    {
        $balanceByDigits = [];
        $codeDnk = strtolower((string)DNK_BONUS_IMPORT_PROGRAM_CODE);

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (isset($row[DNK_BONUS_JSON_KEY_PROGRAM])) {
                $prog = strtolower(trim((string)$row[DNK_BONUS_JSON_KEY_PROGRAM]));
                if ($prog !== '' && $prog !== $codeDnk) {
                    continue;
                }
            }

            $rawPhone = trim((string)($row[DNK_BONUS_JSON_KEY_PARTNER_PHONE] ?? ''));
            $digits = Utils::normalizeBonusPhoneDigits($rawPhone);
            if ($digits === '') {
                if ($rawPhone !== '') {
                    Utils::logClientBonusImportLine(
                        $logDir,
                        $basename,
                        '[invalid_phone] raw=' . $rawPhone
                    );
                }
                continue;
            }

            $balanceByDigits[$digits] = Utils::parseBonusImportAmount($row[DNK_BONUS_JSON_KEY_BALANCE] ?? null);
        }

        return $balanceByDigits;
    }
}
