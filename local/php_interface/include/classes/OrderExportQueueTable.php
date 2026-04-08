<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * ORM для таблицы b_dnk_order_export_queue (см. migrations/dnk_order_export_queue.sql).
 */
class OrderExportQueueTable extends DataManager
{
    public const STATUS_PENDING = 'P';
    public const STATUS_SENT = 'S';
    public const STATUS_ERROR = 'E';

    public static function getTableName(): string
    {
        return 'b_dnk_order_export_queue';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new IntegerField('ORDER_ID'))
                ->configureRequired(true),
            (new TextField('PAYLOAD'))
                ->configureRequired(true),
            (new StringField('STATUS'))
                ->configureSize(1)
                ->configureDefaultValue(self::STATUS_PENDING),
            (new IntegerField('ATTEMPTS'))
                ->configureDefaultValue(0),
            (new TextField('LAST_ERROR'))
                ->configureNullable(true),
            (new DatetimeField('DATE_INSERT'))
                ->configureRequired(true),
            (new DatetimeField('DATE_UPDATE'))
                ->configureNullable(true),
        ];
    }
}
