<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * ORM для таблицы b_dnk_user_reauthorize_queue (см. migrations/dnk_user_reauthorize_queue.sql).
 */
class UserReauthorizeQueueTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_dnk_user_reauthorize_queue';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new IntegerField('USER_ID'))
                ->configureRequired(true),
        ];
    }
}
