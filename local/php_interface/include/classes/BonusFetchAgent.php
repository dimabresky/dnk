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

        $content = str_replace(["\r\n", "\r", "\n"], '', $content);
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            Utils::logClientBonusImportLine($logDir, $basename, '[error] invalid_json file=' . $basename);

            return;
        }

        $importByDigits = self::buildImportDataByPhoneDigitsMap($decoded, $logDir, $basename);
        if ($importByDigits === []) {
            Utils::logClientBonusImportLine($logDir, $basename, '[info] empty_or_no_valid_rows file=' . $basename);
            @unlink($filePath);

            return;
        }

        $resolved = Utils::resolveUserIdsByBonusImportPhones(array_keys($importByDigits));

        foreach ($resolved['not_found'] as $digits) {
            $row = $importByDigits[$digits];
            Utils::logClientBonusImportLine(
                $logDir,
                $basename,
                '[not_found] phone=' . $digits . ' balance=' . $row['balance']
            );
        }

        foreach ($resolved['ambiguous'] as $digits) {
            $row = $importByDigits[$digits];
            Utils::logClientBonusImportLine(
                $logDir,
                $basename,
                '[ambiguous_phone] phone=' . $digits . ' balance=' . $row['balance']
            );
        }

        foreach ($resolved['found'] as $digits => $userId) {
            $row = $importByDigits[$digits];
            Utils::replaceDnkImportBonusesForUser($userId, $row['balance']);
            Utils::syncDnkBonusImportUserLevelFromFile(
                $userId,
                $row['client_level'],
                $row['next_level_cost'],
                $row['has_client_level'],
                $row['has_next_level_cost']
            );
            Utils::syncDnkBonusImportExpirationFromFile(
                $userId,
                $row['expire_date'],
                $row['expire_amount'],
                $row['has_expire_date'],
                $row['has_expire_amount']
            );
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
     * По каждому телефону — данные из последней подходящей строки (без суммирования).
     *
     * @param array<int, mixed> $rows
     * @return array<string, array{
     *     balance: float,
     *     client_level: int|null,
     *     next_level_cost: float|null,
     *     expire_date: \Bitrix\Main\Type\Date|null,
     *     expire_amount: float|null,
     *     has_client_level: bool,
     *     has_next_level_cost: bool,
     *     has_expire_date: bool,
     *     has_expire_amount: bool
     * }>
     */
    private static function buildImportDataByPhoneDigitsMap(array $rows, string $logDir, string $basename): array
    {
        $importByDigits = [];
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

            // Последняя подходящая строка по телефону — полная замена, без переноса полей с предыдущих строк.
            $entry = [
                'balance' => Utils::parseBonusImportAmount($row[DNK_BONUS_JSON_KEY_BALANCE] ?? null),
                'client_level' => null,
                'next_level_cost' => null,
                'expire_date' => null,
                'expire_amount' => null,
                'has_client_level' => false,
                'has_next_level_cost' => false,
                'has_expire_date' => false,
                'has_expire_amount' => false,
            ];

            if (array_key_exists(DNK_BONUS_JSON_KEY_CLIENT_LEVEL, $row)) {
                $parsedLevel = Utils::parseBonusImportClientLevel($row[DNK_BONUS_JSON_KEY_CLIENT_LEVEL]);
                if ($parsedLevel === null) {
                    Utils::logClientBonusImportLine(
                        $logDir,
                        $basename,
                        '[invalid_client_level] phone=' . $digits . ' raw=' . (string)$row[DNK_BONUS_JSON_KEY_CLIENT_LEVEL]
                    );
                } else {
                    $entry['client_level'] = $parsedLevel;
                    $entry['has_client_level'] = true;
                }
            }

            if (array_key_exists(DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST, $row)) {
                $entry['next_level_cost'] = Utils::parseBonusImportAmount($row[DNK_BONUS_JSON_KEY_NEXT_LEVEL_COST] ?? null);
                $entry['has_next_level_cost'] = true;
            }

            if (array_key_exists(DNK_BONUS_JSON_KEY_EXPIRE_DATE, $row)) {
                $parsedDate = Utils::parseBonusImportExpireDate($row[DNK_BONUS_JSON_KEY_EXPIRE_DATE] ?? null);
                if ($parsedDate === null && !empty($row[DNK_BONUS_JSON_KEY_EXPIRE_DATE])) {
                    Utils::logClientBonusImportLine(
                        $logDir,
                        $basename,
                        '[invalid_expire_date] phone=' . $digits . ' raw=' . (string)$row[DNK_BONUS_JSON_KEY_EXPIRE_DATE]
                    );
                }

                $entry['expire_date'] = $parsedDate;
                $entry['has_expire_date'] = true;
            }

            if (array_key_exists(DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT, $row)) {
                $entry['expire_amount'] = Utils::parseBonusImportAmount($row[DNK_BONUS_JSON_KEY_EXPIRE_AMOUNT] ?? null);
                $entry['has_expire_amount'] = true;
            }

            $importByDigits[$digits] = $entry;
        }

        return $importByDigits;
    }
}
