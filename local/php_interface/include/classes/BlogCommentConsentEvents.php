<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Context;

/**
 * Подстановка поля согласия в POST при отправке отзыва авторизованным пользователем,
 * у которого уже есть действующее согласие (чекбокс скрыт в шаблоне).
 */
final class BlogCommentConsentEvents
{
    private const AGREEMENT_OPTION_CODE = 'AGREEMENT_COMMENT';

    /**
     * @param array<string, mixed> $arFields
     */
    public static function onBeforeCommentAdd(array &$arFields): void
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized()) {
            return;
        }

        if (!class_exists(\TSolution\Validation::class)) {
            return;
        }

        $agreementId = UserConsentService::resolveAgreementIdByOption(self::AGREEMENT_OPTION_CODE);
        if ($agreementId === null) {
            return;
        }

        $userId = (int)$USER->GetID();
        if (!UserConsentService::hasActiveConsent($userId, $agreementId)) {
            return;
        }

        $inputName = \TSolution\Validation::LICENSE_INPUT_NAME;
        $request = Context::getCurrent()->getRequest();
        if (!empty($request->get($inputName))) {
            return;
        }

        $value = (string)$agreementId;
        $_REQUEST[$inputName] = $value;
        $_POST[$inputName] = $value;
    }
}
