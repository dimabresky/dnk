<?php

declare(strict_types=1);

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\Utils;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

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

    public function onPrepareComponentParams($params): array
    {
        $params = parent::onPrepareComponentParams($params);

        $params['BALANCE_SELECTOR'] = trim((string)($params['BALANCE_SELECTOR'] ?? ''));
        if ($params['BALANCE_SELECTOR'] === '') {
            $params['BALANCE_SELECTOR'] = '.js-dnk-bonus-balance';
        }

        $params['AUTO_REFRESH'] = ($params['AUTO_REFRESH'] ?? 'Y') !== 'N' ? 'Y' : 'N';

        return $params;
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

        $userId = (int)$USER->GetID();
        if ($userId <= 0) {
            return;
        }

        $balance = Utils::getAsproBonusBalance($userId);

        $this->arResult['USER_ID'] = $userId;
        $this->arResult['BALANCE'] = $balance;
        $this->arResult['BALANCE_FORMATTED'] = $this->formatBalance($balance);
        $this->arResult['BALANCE_UNIT'] = Loc::getMessage('DNK_BONUS_BG_SYNC_BALANCE_UNIT');
        $this->arResult['BALANCE_SELECTOR'] = (string)$this->arParams['BALANCE_SELECTOR'];
        $this->arResult['AUTO_REFRESH'] = (string)$this->arParams['AUTO_REFRESH'];

        $this->includeComponentTemplate();
    }

    /**
     * Синхронизация бонусов по телефону и возврат актуального баланса.
     *
     * @return array{success: bool, balance: float, balanceFormatted: string, balanceUnit: string, errorCode?: string}
     */
    public function refreshAction(): array
    {
        $userId = (int)CurrentUser::get()->getId();
        if ($userId <= 0) {
            return [
                'success' => false,
                'balance' => 0.0,
                'balanceFormatted' => $this->formatBalance(0.0),
                'balanceUnit' => Loc::getMessage('DNK_BONUS_BG_SYNC_BALANCE_UNIT'),
                'errorCode' => 'not_authorized',
            ];
        }

        if (!$this->isAllowedPage()) {
            $balance = Utils::getAsproBonusBalance($userId);

            return [
                'success' => false,
                'balance' => $balance,
                'balanceFormatted' => $this->formatBalance($balance),
                'balanceUnit' => Loc::getMessage('DNK_BONUS_BG_SYNC_BALANCE_UNIT'),
                'errorCode' => 'page_not_allowed',
            ];
        }

        $errorDetail = '';
        $synced = Utils::trySyncDnkImportBonusesForUserByPhone($userId, $errorDetail);
        $balance = Utils::getAsproBonusBalance($userId);

        $result = [
            'success' => $synced,
            'balance' => $balance,
            'balanceFormatted' => $this->formatBalance($balance),
            'balanceUnit' => Loc::getMessage('DNK_BONUS_BG_SYNC_BALANCE_UNIT'),
        ];

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

        global $APPLICATION;

        if (!is_object($APPLICATION)) {
            return false;
        }

        $curPage = (string)$APPLICATION->GetCurPage(true);
        $curPage = '/' . ltrim($curPage, '/');

        if ($curPage === '/' || $curPage === '/index.php') {
            return true;
        }

        $siteDir = defined('SITE_DIR') ? (string)SITE_DIR : '/';
        $siteDir = rtrim($siteDir, '/') . '/';
        $indexPath = $siteDir . 'index.php';

        return $curPage === $indexPath || $curPage === rtrim($siteDir, '/');
    }

    private function formatBalance(float $balance): string
    {
        return number_format(Utils::roundMoney($balance), 2, ',', ' ');
    }
}
