<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;

/**
 * ORM для таблицы b_dnk_user_consent_revoke (см. migrations/dnk_user_consent_revoke.sql).
 */
class UserConsentRevokeTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'b_dnk_user_consent_revoke';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new IntegerField('USER_ID'))
                ->configureRequired(true),
            (new IntegerField('AGREEMENT_ID'))
                ->configureRequired(true),
            (new DatetimeField('DATE_REVOKE'))
                ->configureRequired(true),
        ];
    }
}
