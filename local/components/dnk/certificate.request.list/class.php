<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Dnk\PhpInterface\CertificateRequestStatus;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loc::loadMessages(__FILE__);

/**
 * ЛК: список заявок на покупку сертификатов текущего пользователя.
 */
class DnkCertificateRequestListComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams): array
    {
        $arParams['REQUESTS_PER_PAGE'] = max(1, (int)($arParams['REQUESTS_PER_PAGE'] ?? 10));

        return $arParams;
    }

    public function executeComponent()
    {
        global $USER;

        if (!is_object($USER) || !$USER->IsAuthorized()) {
            ShowError(Loc::getMessage('DNK_CERT_REQ_LIST_ERR_AUTH'));

            return;
        }

        if (!Loader::includeModule('iblock')) {
            ShowError(Loc::getMessage('DNK_CERT_REQ_LIST_ERR_IBLOCK'));

            return;
        }

        $iblockId = defined('DNK_CERTIFICATE_REQUEST_IBLOCK_ID') ? (int)DNK_CERTIFICATE_REQUEST_IBLOCK_ID : 0;
        if ($iblockId <= 0) {
            ShowError(Loc::getMessage('DNK_CERT_REQ_LIST_ERR_CONFIG'));

            return;
        }

        $userId = (int)$USER->GetID();
        $pageSize = (int)$this->arParams['REQUESTS_PER_PAGE'];
        $navId = 'dnk_cert_req_list';
        $page = max(1, (int)($_REQUEST[$navId] ?? 1));

        $this->arResult['ITEMS'] = [];
        $this->arResult['NAV_STRING'] = '';

        $rs = CIBlockElement::GetList(
            ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'],
            [
                'IBLOCK_ID' => $iblockId,
                'PROPERTY_USER' => $userId,
            ],
            false,
            [
                'nPageSize' => $pageSize,
                'bShowAll' => false,
                'iNumPage' => $page,
            ],
            [
                'ID',
                'NAME',
                'DATE_CREATE',
                'PROPERTY_TOTAL_SUM',
                'PROPERTY_STATUS',
            ]
        );

        while ($row = $rs->GetNext()) {
            $statusEnumId = (int)($row['PROPERTY_STATUS_ENUM_ID'] ?? $row['PROPERTY_STATUS_VALUE'] ?? 0);
            $status = CertificateRequestStatus::formatFromEnumId($statusEnumId);
            $totalSum = round((float)($row['PROPERTY_TOTAL_SUM_VALUE'] ?? 0), 2);

            $this->arResult['ITEMS'][] = [
                'id' => (int)($row['ID'] ?? 0),
                'name' => trim((string)($row['NAME'] ?? '')),
                'dateCreateFormatted' => $this->formatDate((string)($row['DATE_CREATE'] ?? '')),
                'totalSumFormatted' => $this->formatMoney($totalSum),
                'statusLabel' => $status['label'],
                'statusCss' => $status['css'],
            ];
        }

        $this->arResult['NAV_STRING'] = $rs->GetPageNavStringEx(
            $navId,
            Loc::getMessage('DNK_CERT_REQ_LIST_NAV_TITLE'),
            '',
            false,
            $this,
            ['NAV_RESULT' => $rs]
        );

        $this->includeComponentTemplate();
    }

    private function formatMoney(float $amount): string
    {
        if (Loader::includeModule('currency')) {
            return (string)\CCurrencyLang::CurrencyFormat($amount, 'BYN', true);
        }

        return number_format($amount, 2, ',', '') . ' BYN';
    }

    private function formatDate(string $dateTime): string
    {
        if ($dateTime === '') {
            return '';
        }

        $ts = MakeTimeStamp($dateTime);
        if ($ts <= 0) {
            return $dateTime;
        }

        return FormatDate('d.m.Y H:i', $ts);
    }
}
