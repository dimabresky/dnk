<?php

namespace Bx\ImageWebp;

use CFile;
use CIBlockElement;

/**
 * Replaces an element field or file property value with a new WebP file.
 */
final class ElementImageReplacer
{
    /**
     * @param array{path:string,name:string} $webp
     *
     * @throws \RuntimeException
     */
    public static function replace(
        int $iblockId,
        int $elementId,
        string $targetType,
        string $targetCode,
        ?int $propertyValueId,
        int $oldFileId,
        array $webp
    ): void {
        if (!\CModule::IncludeModule('iblock')) {
            throw new \RuntimeException('iblock module is not available');
        }

        $fileArray = CFile::MakeFileArray($webp['path']);
        if (!is_array($fileArray) || empty($fileArray['tmp_name'])) {
            throw new \RuntimeException('MakeFileArray failed for ' . $webp['path']);
        }

        $fileArray['name'] = $webp['name'];
        $fileArray['MODULE_ID'] = 'iblock';
        if (empty($fileArray['type'])) {
            $fileArray['type'] = 'image/webp';
        }

        EnqueueService::beginInternalUpdate();
        try {
            if ($targetType === Config::TARGET_FIELD) {
                self::replaceField($elementId, $targetCode, $fileArray, $oldFileId);
            } elseif ($targetType === Config::TARGET_PROPERTY) {
                if ($propertyValueId === null || $propertyValueId <= 0) {
                    throw new \RuntimeException('PROPERTY_VALUE_ID is required for property target');
                }
                self::replaceProperty($iblockId, $elementId, $targetCode, $propertyValueId, $fileArray, $oldFileId);
            } else {
                throw new \RuntimeException('Unknown TARGET_TYPE: ' . $targetType);
            }
        } finally {
            EnqueueService::endInternalUpdate();
            @unlink($webp['path']);
        }
    }

    /**
     * @param array<string,mixed> $fileArray
     */
    private static function replaceField(int $elementId, string $fieldCode, array $fileArray, int $oldFileId): void
    {
        if (!in_array($fieldCode, Config::ALLOWED_ELEMENT_FIELDS, true)) {
            throw new \RuntimeException('Field not allowed: ' . $fieldCode);
        }

        $el = new CIBlockElement();
        $ok = $el->Update(
            $elementId,
            [$fieldCode => $fileArray],
            false,
            false,
            false
        );
        if (!$ok) {
            throw new \RuntimeException('CIBlockElement::Update failed: ' . (string)$el->LAST_ERROR);
        }

        if (Config::isDeleteOriginal() && $oldFileId > 0) {
            // Bitrix usually removes the previous picture on Update; delete leftovers if still present.
            $still = CFile::GetFileArray($oldFileId);
            if (is_array($still)) {
                CFile::Delete($oldFileId);
            }
        }
    }

    /**
     * @param array<string,mixed> $fileArray
     */
    private static function replaceProperty(
        int $iblockId,
        int $elementId,
        string $propCode,
        int $propertyValueId,
        array $fileArray,
        int $oldFileId
    ): void {
        $values = [];
        $found = false;
        $res = CIBlockElement::GetProperty($iblockId, $elementId, ['sort' => 'asc'], ['CODE' => $propCode]);
        while ($row = $res->Fetch()) {
            $valueId = (int)($row['PROPERTY_VALUE_ID'] ?? 0);
            if ($valueId <= 0) {
                continue;
            }
            if ($valueId === $propertyValueId) {
                $values[$valueId] = [
                    'VALUE' => $fileArray,
                    'DESCRIPTION' => (string)($row['DESCRIPTION'] ?? ''),
                ];
                $found = true;
            } else {
                $values[$valueId] = [
                    'VALUE' => (int)($row['VALUE'] ?? 0),
                    'DESCRIPTION' => (string)($row['DESCRIPTION'] ?? ''),
                ];
            }
        }

        if (!$found) {
            throw new \RuntimeException(
                "Property value {$propertyValueId} not found for {$propCode} on element {$elementId}"
            );
        }

        CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, [$propCode => $values]);

        if (Config::isDeleteOriginal() && $oldFileId > 0) {
            $still = CFile::GetFileArray($oldFileId);
            if (is_array($still)) {
                CFile::Delete($oldFileId);
            }
        }
    }
}
