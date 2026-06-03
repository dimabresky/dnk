<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
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
        $this->arResult['ITEMS'] = [];

        $rs = CIBlockElement::GetList(
            ['DATE_CREATE' => 'DESC', 'ID' => 'DESC'],
            [
                'IBLOCK_ID' => $iblockId,
                'PROPERTY_USER' => $userId,
            ],
            false,
            false
        );

        $pendingItemsJson = [];

        while ($ob = $rs->GetNextElement()) {
            if ($ob === false) {
                continue;
            }

            $fields = $ob->GetFields();
            $props = $ob->GetProperties();

            $elementId = (int)($fields['ID'] ?? 0);
            if ($elementId <= 0) {
                continue;
            }

            $statusEnumId = $this->extractListEnumId($props[CertificateRequestStatus::PROP_CODE] ?? []);
            $status = CertificateRequestStatus::formatFromEnumId($statusEnumId);
            $totalSum = round((float)($props['TOTAL_SUM']['VALUE'] ?? 0), 2);

            $itemsJsonRaw = trim((string)($props['ITEMS_JSON']['VALUE'] ?? ''));
            if ($itemsJsonRaw !== '') {
                $pendingItemsJson[$elementId] = $itemsJsonRaw;
            }

            $this->arResult['ITEMS'][] = [
                'id' => $elementId,
                'name' => trim((string)($fields['NAME'] ?? '')),
                'dateCreateFormatted' => $this->formatDate($fields['DATE_CREATE'] ?? null),
                'totalSumFormatted' => $this->formatMoney($totalSum),
                'statusLabel' => $status['label'],
                'statusCss' => $status['css'],
                'details' => [
                    'contactName' => trim((string)($props['CONTACT_NAME']['VALUE'] ?? '')),
                    'contactPhone' => trim((string)($props['CONTACT_PHONE']['VALUE'] ?? '')),
                    'contactEmail' => trim((string)($props['CONTACT_EMAIL']['VALUE'] ?? '')),
                    'deliveryLabel' => $this->extractListPropertyLabel($props['DELIVERY'] ?? []),
                    'paymentLabel' => $this->extractListPropertyLabel($props['PAYMENT'] ?? []),
                    'comment' => trim((string)($props['COMMENT']['VALUE'] ?? '')),
                    'detailTextPlain' => trim((string)($fields['DETAIL_TEXT'] ?? '')),
                    'lines' => [],
                ],
            ];
        }

        if ($pendingItemsJson !== []) {
            $this->fillOrderLinesFromItemsJson($pendingItemsJson);
        }

        $this->includeComponentTemplate();
    }

    /**
     * @param array<string, string> $itemsJsonByElementId elementId => ITEMS_JSON
     */
    private function fillOrderLinesFromItemsJson(array $itemsJsonByElementId): void
    {
        $allElementIds = [];
        $parsedByRequestId = [];

        foreach ($itemsJsonByElementId as $requestId => $json) {
            $lines = $this->parseItemsJson($json);
            $parsedByRequestId[$requestId] = $lines;
            foreach ($lines as $line) {
                $eid = (int)($line['element_id'] ?? 0);
                if ($eid > 0) {
                    $allElementIds[$eid] = true;
                }
            }
        }

        $namesByElementId = $this->loadCertificateElementNames(array_keys($allElementIds));

        foreach (array_keys($this->arResult['ITEMS']) as $index) {
            $requestId = (int)($this->arResult['ITEMS'][$index]['id'] ?? 0);
            if ($requestId <= 0 || !isset($parsedByRequestId[$requestId])) {
                continue;
            }

            $displayLines = [];
            foreach ($parsedByRequestId[$requestId] as $line) {
                $eid = (int)($line['element_id'] ?? 0);
                $qty = (int)($line['qty'] ?? 0);
                if ($qty < 1) {
                    continue;
                }
                $nominal = round((float)($line['nominal'] ?? 0), 4);
                $lineSum = round((float)($line['line_sum'] ?? 0), 2);
                $displayLines[] = [
                    'name' => $namesByElementId[$eid] ?? ('Сертификат №' . $eid),
                    'nominalFormatted' => $this->formatMoney($nominal),
                    'qty' => $qty,
                    'lineSumFormatted' => $this->formatMoney($lineSum),
                ];
            }

            if (
                isset($this->arResult['ITEMS'][$index]['details'])
                && is_array($this->arResult['ITEMS'][$index]['details'])
            ) {
                $this->arResult['ITEMS'][$index]['details']['lines'] = $displayLines;
            }
        }
    }

    /**
     * @return list<array{element_id: int, nominal: float, qty: int, line_sum: float}>
     */
    private function parseItemsJson(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        $lines = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $eid = (int)($row['element_id'] ?? 0);
            $qty = (int)($row['qty'] ?? 0);
            if ($eid <= 0 || $qty <= 0) {
                continue;
            }
            $lines[] = [
                'element_id' => $eid,
                'nominal' => round((float)($row['nominal'] ?? 0), 4),
                'qty' => $qty,
                'line_sum' => round((float)($row['line_sum'] ?? 0), 2),
            ];
        }

        return $lines;
    }

    /**
     * @param int[] $elementIds
     * @return array<int, string>
     */
    private function loadCertificateElementNames(array $elementIds): array
    {
        $certIblockId = defined('DNK_CERTIFICATE_CATALOG_IBLOCK_ID')
            ? (int)DNK_CERTIFICATE_CATALOG_IBLOCK_ID
            : 0;
        if ($certIblockId <= 0 || $elementIds === []) {
            return [];
        }

        $names = [];
        $rs = CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $certIblockId,
                'ID' => $elementIds,
                'CHECK_PERMISSIONS' => 'N',
            ],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($row = $rs->Fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id > 0) {
                $names[$id] = trim((string)($row['NAME'] ?? ''));
            }
        }

        return $names;
    }

    /**
     * @param array<string, mixed> $prop
     */
    private function extractListEnumId(array $prop): int
    {
        $enumId = (int)($prop['VALUE_ENUM_ID'] ?? 0);
        if ($enumId > 0) {
            return $enumId;
        }

        return (int)($prop['VALUE'] ?? 0);
    }

    /**
     * @param array<string, mixed> $prop
     */
    private function extractListPropertyLabel(array $prop): string
    {
        $label = trim((string)($prop['VALUE'] ?? ''));
        if ($label !== '' && !ctype_digit($label)) {
            return $label;
        }

        $enumId = $this->extractListEnumId($prop);
        if ($enumId <= 0) {
            return '';
        }

        $enum = CIBlockPropertyEnum::GetList([], ['ID' => $enumId])->Fetch();

        return is_array($enum) ? trim((string)($enum['VALUE'] ?? '')) : '';
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
