<?php

declare(strict_types=1);

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Фоновое обновление бонусного баланса авторизованного пользователя по телефону (DNK_BONUS_ENDPOINT).
 */
class DnkUserBonusBackgroundSyncComponent extends CBitrixComponent implements Controllerable
{
    private const ALLOWED_DIRS = [
        '/personal/',
        '/basket/',
    ];

    /** @inheritdoc */
    public function configureActions(): array
    {
        return [
            'refresh' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    public function executeComponent(): void
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized()) {
            return;
        }

        if (!$this->isAllowedPage()) {
            return;
        }

        if ((int)$USER->GetID() <= 0) {
            return;
        }

        $this->includeComponentTemplate();
    }

    /**
     * Синхронизация бонусов по телефону на сервере (Aspro Bonus).
     *
     * @return array{success: bool, errorCode?: string}
     */
    public function refreshAction(): array
    {
        $userId = (int)CurrentUser::get()->getId();
        if ($userId <= 0) {
            return [
                'success' => false,
                'errorCode' => 'not_authorized',
            ];
        }

        $errorDetail = '';
        $synced = Utils::trySyncDnkImportBonusesForUserByPhone($userId, $errorDetail);

        $result = ['success' => $synced];

        if (!$synced && $errorDetail !== '') {
            $result['errorCode'] = $errorDetail;
        }

        return $result;
    }

    /**
     * Разрешённые страницы: /index.php (главная), /personal/, /basket/.
     */
    private function isAllowedPage(): bool
    {
        foreach (self::ALLOWED_DIRS as $dir) {
            if (\CSite::InDir($dir)) {
                return true;
            }
        }

        if (\CSite::InDir('/index.php')) {
            return true;
        }

        return false;
    }
}
