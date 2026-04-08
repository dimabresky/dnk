<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;

/**
 * Агент: один HTTP GET на DNK_BONUS_ENDPOINT — полный список данных по бонусам и синхронизация с Aspro Bonus.
 */
final class BonusFetchAgent
{
    public static function runBonusAgent(): string
    {
        $return = "\\Dnk\\PhpInterface\\BonusFetchAgent::runBonusAgent();";

        if (!Loader::includeModule('aspro.bonus')) {
            return $return;
        }

        $bonusesList = Utils::fetchBonusEndpointJsonList();
        if ($bonusesList === null) {
            return $return;
        }

        $balanceByUserId = self::buildUserIdBalanceMap($bonusesList);
        foreach ($balanceByUserId as $userId => $amount) {
            Utils::replaceDnkImportBonusesForUser($userId, $amount);
        }

        return $return;
    }

    /**
     * По каждому UUID — значение НачисленоОстаток из последней подходящей строки (без суммирования по строкам).
     * Внутри строки UUID в КонтрагентыUUID уникализируются; одному пользователю — последний остаток по его UUID.
     *
     * @param array<int, mixed> $bonusesList
     * @return array<int, float> userId => остаток
     */
    private static function buildUserIdBalanceMap(array $bonusesList): array
    {
        $balanceByUuid = [];
        $codeDnk = strtolower((string)DNK_BONUS_IMPORT_PROGRAM_CODE);

        foreach ($bonusesList as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (isset($row[DNK_BONUS_JSON_KEY_PROGRAM])) {
                $prog = strtolower(trim((string)$row[DNK_BONUS_JSON_KEY_PROGRAM]));
                if ($prog !== '' && $prog !== $codeDnk) {
                    continue;
                }
            }

            $amount = Utils::parseBonusImportAmount($row[DNK_BONUS_JSON_KEY_BALANCE] ?? null);

            $uuids = self::uniqueCounterpartyUuidsFromRow($row);
            if ($uuids === []) {
                continue;
            }

            foreach ($uuids as $uuid) {
                $balanceByUuid[$uuid] = $amount;
            }
        }

        if ($balanceByUuid === []) {
            return [];
        }

        $uuidToUserId = Utils::findUserIdsByExternalUuids(array_keys($balanceByUuid));
        $byUser = [];
        foreach ($balanceByUuid as $uuid => $amount) {
            $userId = $uuidToUserId[$uuid] ?? null;
            if ($userId === null || $userId <= 0) {
                continue;
            }
            $byUser[$userId] = $amount;
        }

        return $byUser;
    }

    /**
     * Уникальные непустые UUID из КонтрагентыUUID (порядок сохраняется).
     *
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private static function uniqueCounterpartyUuidsFromRow(array $row): array
    {
        $seen = [];
        $out = [];

        $counterparties = $row[DNK_BONUS_JSON_KEY_COUNTERPARTY_UUIDS] ?? null;
        if (!is_array($counterparties)) {
            return $out;
        }

        foreach ($counterparties as $u) {
            $s = trim((string)$u);
            if ($s === '' || isset($seen[$s])) {
                continue;
            }
            $seen[$s] = true;
            $out[] = $s;
        }

        return $out;
    }
}
