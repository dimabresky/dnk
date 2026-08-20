<?php

namespace Dnk\Stickers;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * ORM for b_dnk_stickers_assignment.
 */
class AssignmentTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_dnk_stickers_assignment';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new IntegerField('IBLOCK_ID'))
                ->configureRequired(true),
            (new IntegerField('ELEMENT_ID'))
                ->configureRequired(true),
            (new StringField('STICKER_XML_ID'))
                ->configureRequired(true)
                ->configureSize(50)
                ->addValidator(new LengthValidator(1, 50)),
            (new DatetimeField('ASSIGNED_AT'))
                ->configureRequired(true),
            (new StringField('SOURCE'))
                ->configureRequired(true)
                ->configureSize(20)
                ->configureDefaultValue('')
                ->addValidator(new LengthValidator(null, 20)),
        ];
    }
}
