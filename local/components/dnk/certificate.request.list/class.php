<?php

declare(strict_types=1);

use Bitrix\Iblock\ElementPropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UI\PageNavigation;
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
        $propertyIds = $this->loadPropertyIds($iblockId, [
            'USER',
            'TOTAL_SUM',
            CertificateRequestStatus::PROP_CODE,
        ]);
        if (
            empty($propertyIds['USER'])
            || empty($propertyIds['TOTAL_SUM'])
            || empty($propertyIds[CertificateRequestStatus::PROP_CODE])
        ) {
            ShowError(Loc::getMessage('DNK_CERT_REQ_LIST_ERR_CONFIG'));

            return;
        }

        $nav = new PageNavigation('dnk_cert_req_list');
        $nav->allowAllRecords(false);
        $nav->setPageSize($pageSize);
        $nav->initFromUri();

        $this->arResult['ITEMS'] = [];
        $this->arResult['NAV_OBJECT'] = $nav;

        $result = ElementTable::getList([
            'select' => [
                'ID',
                'NAME',
                'DATE_CREATE',
                'TOTAL_SUM_VALUE' => 'TOTAL_PROP.VALUE',
                'STATUS_ENUM_ID' => 'STATUS_PROP.VALUE_ENUM',
            ],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=USER_PROP.VALUE' => (string)$userId,
            ],
            'order' => [
                'DATE_CREATE' => 'DESC',
                'ID' => 'DESC',
            ],
            'runtime' => [
                new Reference(
                    'USER_PROP',
                    ElementPropertyTable::class,
                    Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
                        ->where('ref.IBLOCK_PROPERTY_ID', (int)$propertyIds['USER'])
                ),
                new Reference(
                    'TOTAL_PROP',
                    ElementPropertyTable::class,
                    Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
                        ->where('ref.IBLOCK_PROPERTY_ID', (int)$propertyIds['TOTAL_SUM'])
                ),
                new Reference(
                    'STATUS_PROP',
                    ElementPropertyTable::class,
                    Join::on('this.ID', 'ref.IBLOCK_ELEMENT_ID')
                        ->where('ref.IBLOCK_PROPERTY_ID', (int)$propertyIds[CertificateRequestStatus::PROP_CODE])
                ),
            ],
            'limit' => $nav->getLimit(),
            'offset' => $nav->getOffset(),
            'count_total' => true,
        ]);

        $nav->setRecordCount((int)$result->getCount());

        while ($row = $result->fetch()) {
            $statusEnumId = (int)($row['STATUS_ENUM_ID'] ?? 0);
            $status = CertificateRequestStatus::formatFromEnumId($statusEnumId);
            $totalSum = round((float)($row['TOTAL_SUM_VALUE'] ?? 0), 2);

            $this->arResult['ITEMS'][] = [
                'id' => (int)($row['ID'] ?? 0),
                'name' => trim((string)($row['NAME'] ?? '')),
                'dateCreateFormatted' => $this->formatDate($row['DATE_CREATE'] ?? null),
                'totalSumFormatted' => $this->formatMoney($totalSum),
                'statusLabel' => $status['label'],
                'statusCss' => $status['css'],
            ];
        }

        $this->includeComponentTemplate();
    }

    /**
     * @param list<string> $codes
     * @return array<string, int>
     */
    private function loadPropertyIds(int $iblockId, array $codes): array
    {
        if ($iblockId <= 0 || $codes === []) {
            return [];
        }

        $ids = [];
        $result = PropertyTable::getList([
            'select' => ['ID', 'CODE'],
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '@CODE' => $codes,
            ],
        ]);

        while ($row = $result->fetch()) {
            $code = (string)($row['CODE'] ?? '');
            $id = (int)($row['ID'] ?? 0);
            if ($code !== '' && $id > 0) {
                $ids[$code] = $id;
            }
        }

        return $ids;
    }

    private function formatMoney(float $amount): string
    {
        if (Loader::includeModule('currency')) {
            return (string)\CCurrencyLang::CurrencyFormat($amount, 'BYN', true);
        }

        return number_format($amount, 2, ',', '') . ' BYN';
    }

    /**
     * @param DateTime|string|null $dateTime
     */
    private function formatDate($dateTime): string
    {
        if ($dateTime instanceof DateTime) {
            return FormatDate('d.m.Y H:i', $dateTime->getTimestamp());
        }

        $dateTimeString = (string)$dateTime;
        if ($dateTimeString === '') {
            return '';
        }

        $ts = MakeTimeStamp($dateTimeString);
        if ($ts <= 0) {
            return $dateTimeString;
        }

        return FormatDate('d.m.Y H:i', $ts);
    }
}
