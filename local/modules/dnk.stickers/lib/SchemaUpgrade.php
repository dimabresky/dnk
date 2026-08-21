<?php

namespace Dnk\Stickers;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

/**
 * Idempotent schema upgrades for dnk.stickers.
 */
final class SchemaUpgrade
{
    /**
     * Ensure EXPIRES_AT exists and is backfilled for existing rows.
     */
    public static function ensureExpiresAtColumn(): void
    {
        $connection = Application::getConnection();
        $table = AssignmentTable::getTableName();

        if (!$connection->isTableExists($table)) {
            return;
        }

        $fields = $connection->getTableFields($table);
        if (isset($fields['EXPIRES_AT'])) {
            return;
        }

        $connection->queryExecute(
            'ALTER TABLE `' . $table . '` ADD COLUMN `EXPIRES_AT` datetime NULL AFTER `ASSIGNED_AT`'
        );

        $lifetimeDays = 30.0;
        $rule = Config::getRuleByXmlId('NEW');
        if ($rule !== null) {
            $lifetimeDays = (float) $rule['lifetime_days'];
        }
        if ($lifetimeDays < 0) {
            $lifetimeDays = 0.0;
        }

        $seconds = (int) round($lifetimeDays * 86400);
        $connection->queryExecute(
            'UPDATE `' . $table . '` SET `EXPIRES_AT` = DATE_ADD(`ASSIGNED_AT`, INTERVAL ' . $seconds . ' SECOND) '
            . 'WHERE `EXPIRES_AT` IS NULL'
        );

        $connection->queryExecute(
            'ALTER TABLE `' . $table . '` MODIFY COLUMN `EXPIRES_AT` datetime NOT NULL'
        );

        // Replace expire index to use EXPIRES_AT when possible.
        try {
            $connection->queryExecute('ALTER TABLE `' . $table . '` DROP INDEX `ix_dnk_stickers_expire`');
        } catch (\Throwable $e) {
            // Index may already be absent on some installs.
        }

        try {
            $connection->queryExecute(
                'ALTER TABLE `' . $table . '` ADD INDEX `ix_dnk_stickers_expire` (`STICKER_XML_ID`, `EXPIRES_AT`)'
            );
        } catch (\Throwable $e) {
            // Index may already exist.
        }

        Option::set(Config::MODULE_ID, 'schema_version', '1.0.1');
    }
}
