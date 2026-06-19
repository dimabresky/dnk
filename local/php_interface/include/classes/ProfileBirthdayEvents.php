<?php

namespace Dnk\PhpInterface;

use Bitrix\Main\UserTable;

/**
 * Блокировка повторного изменения дня рождения через профиль.
 */
final class ProfileBirthdayEvents
{
    /**
     * @param array<string, mixed> $arFields
     */
    public static function onBeforeUserUpdate(array &$arFields): bool
    {
        if (!array_key_exists('PERSONAL_BIRTHDAY', $arFields)) {
            return true;
        }

        $userId = (int)($arFields['ID'] ?? 0);
        if ($userId <= 0) {
            return true;
        }

        $row = UserTable::getRow([
            'select' => ['PERSONAL_BIRTHDAY'],
            'filter' => ['=ID' => $userId],
        ]);

        $currentBirthday = $row['PERSONAL_BIRTHDAY'] ?? null;
        $currentDisplay = Utils::formatUserBirthDateForDisplay($currentBirthday);

        if ($currentDisplay !== '') {
            unset($arFields['PERSONAL_BIRTHDAY']);

            return true;
        }

        $newInput = trim((string)$arFields['PERSONAL_BIRTHDAY']);
        if ($newInput === '') {
            unset($arFields['PERSONAL_BIRTHDAY']);

            return true;
        }

        $parsed = Utils::parseProfileBirthDateInput($newInput);
        if ($parsed === null) {
            global $APPLICATION;
            $APPLICATION->ThrowException('Укажите корректную дату рождения в формате дд.мм.гггг.');

            return false;
        }

        $arFields['PERSONAL_BIRTHDAY'] = $parsed->format('d.m.Y');

        return true;
    }
}
