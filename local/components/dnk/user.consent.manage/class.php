<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\UserConsentService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * ЛК: список согласий пользователя и отзыв.
 */
class DnkUserConsentManageComponent extends CBitrixComponent implements Controllerable
{
    /** @inheritdoc */
    public function configureActions()
    {
        return [
            'revoke' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    /**
     * @param int $agreementId
     * @return array{success: bool, error?: string}
     */
    public function revokeAction(int $agreementId): array
    {
        $userId = (int)CurrentUser::get()->getId();
        if ($userId <= 0 || $agreementId <= 0) {
            return ['success' => false, 'error' => Loc::getMessage('DNK_UC_MANAGE_ERR_PARAMS')];
        }

        if (!UserConsentService::revoke($userId, $agreementId)) {
            return ['success' => false, 'error' => Loc::getMessage('DNK_UC_MANAGE_ERR_REVOKE')];
        }

        return ['success' => true];
    }

    public function executeComponent()
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized()) {
            ShowError(Loc::getMessage('DNK_UC_MANAGE_ERR_AUTH'));
            return;
        }

        $userId = (int)$USER->GetID();
        $this->arResult['AGREEMENTS'] = UserConsentService::getManageableAgreements($userId);
        $this->arResult['AJAX_URL'] = '/local/ajax/user_consent.php';

        $this->includeComponentTemplate();
    }
}
