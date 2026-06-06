<?php

declare(strict_types=1);

namespace Dnk\PhpInterface;

use Bitrix\Main\Loader;

/**
 * Статусы заявок на покупку сертификатов (свойство STATUS инфоблока dnk_certificate_requests).
 */
final class CertificateRequestStatus
{
    public const PROP_CODE = 'STATUS';

    public const XML_ACCEPTED = 'accepted';
    public const XML_IN_PROGRESS = 'in_progress';
    public const XML_READY = 'ready';

    public static function defaultXml(): string
    {
        return self::XML_ACCEPTED;
    }

    /**
     * @return array<string, string> xml_id => label
     */
    public static function allLabels(): array
    {
        return [
            self::XML_ACCEPTED => 'Принят',
            self::XML_IN_PROGRESS => 'В обработке',
            self::XML_READY => 'Готов',
        ];
    }

    public static function getLabelByXml(string $xmlId): string
    {
        $labels = self::allLabels();

        return $labels[$xmlId] ?? $xmlId;
    }

    public static function getCssModifier(string $xmlId): string
    {
        $allowed = [
            self::XML_ACCEPTED,
            self::XML_IN_PROGRESS,
            self::XML_READY,
        ];

        return in_array($xmlId, $allowed, true) ? $xmlId : self::XML_ACCEPTED;
    }

    public static function resolveEnumId(int $iblockId, string $xmlId): ?int
    {
        if ($iblockId <= 0 || $xmlId === '') {
            return null;
        }

        if (!Loader::includeModule('iblock')) {
            return null;
        }

        $prop = \CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => self::PROP_CODE])->Fetch();
        if (!is_array($prop)) {
            return null;
        }

        $propId = (int)($prop['ID'] ?? 0);
        if ($propId <= 0) {
            return null;
        }

        $enum = \CIBlockPropertyEnum::GetList([], ['PROPERTY_ID' => $propId, 'XML_ID' => $xmlId])->Fetch();
        if (!is_array($enum)) {
            return null;
        }

        $enumId = (int)($enum['ID'] ?? 0);

        return $enumId > 0 ? $enumId : null;
    }

    /**
     * @return array{label: string, xmlId: string, css: string}
     */
    public static function formatFromEnumId(int $enumId): array
    {
        if ($enumId <= 0 || !Loader::includeModule('iblock')) {
            return [
                'label' => self::getLabelByXml(self::defaultXml()),
                'xmlId' => self::defaultXml(),
                'css' => self::getCssModifier(self::defaultXml()),
            ];
        }

        $row = \CIBlockPropertyEnum::GetList([], ['ID' => $enumId])->Fetch();
        if (!is_array($row)) {
            return [
                'label' => self::getLabelByXml(self::defaultXml()),
                'xmlId' => self::defaultXml(),
                'css' => self::getCssModifier(self::defaultXml()),
            ];
        }

        $xmlId = trim((string)($row['XML_ID'] ?? ''));
        if ($xmlId === '') {
            $xmlId = self::defaultXml();
        }

        $label = trim((string)($row['VALUE'] ?? ''));
        if ($label === '') {
            $label = self::getLabelByXml($xmlId);
        }

        return [
            'label' => $label,
            'xmlId' => $xmlId,
            'css' => self::getCssModifier($xmlId),
        ];
    }
}
