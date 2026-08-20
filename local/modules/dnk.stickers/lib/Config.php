<?php

namespace Dnk\Stickers;

use Bitrix\Main\Config\Option;

/**
 * Module options and sticker rules.
 */
final class Config
{
    public const MODULE_ID = 'dnk.stickers';

    public const SOURCE_REMEMBER = 'remember';
    public const SOURCE_CREATE = 'create';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_FILTER = 'filter';

    /**
     * Default v1 rule for NEW sticker.
     *
     * @return array{xml_id: string, enabled: bool, lifetime_days: float, auto_on_create: bool, track_manual: bool, assign_filter: array<string, mixed>}
     */
    public static function defaultNewRule(): array
    {
        return [
            'xml_id' => 'NEW',
            'enabled' => true,
            'lifetime_days' => 30.0,
            'auto_on_create' => true,
            'track_manual' => true,
            'assign_filter' => [],
        ];
    }

    public static function isEnabled(): bool
    {
        return Option::get(self::MODULE_ID, 'enabled', 'Y') === 'Y';
    }

    public static function getIblockId(): int
    {
        return max(0, (int) Option::get(self::MODULE_ID, 'iblock_id', '42'));
    }

    public static function getHitPropertyCode(): string
    {
        $code = trim((string) Option::get(self::MODULE_ID, 'hit_property_code', 'HIT'));

        return $code !== '' ? $code : 'HIT';
    }

    public static function getBatchSize(): int
    {
        return max(1, (int) Option::get(self::MODULE_ID, 'batch_size', '100'));
    }

    public static function getAgentInterval(): int
    {
        return max(60, (int) Option::get(self::MODULE_ID, 'agent_interval', '3600'));
    }

    /**
     * @return list<array{xml_id: string, enabled: bool, lifetime_days: float, auto_on_create: bool, track_manual: bool, assign_filter: array<string, mixed>}>
     */
    public static function getRules(): array
    {
        $raw = (string) Option::get(self::MODULE_ID, 'rules', '');
        $decoded = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
        }

        if (!is_array($decoded) || $decoded === []) {
            return [self::defaultNewRule()];
        }

        $rules = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $normalized = self::normalizeRule($row);
            if ($normalized !== null) {
                $rules[] = $normalized;
            }
        }

        return $rules !== [] ? $rules : [self::defaultNewRule()];
    }

    /**
     * @param list<array<string, mixed>> $rules
     */
    public static function setRules(array $rules): void
    {
        $normalized = [];
        foreach ($rules as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rule = self::normalizeRule($row);
            if ($rule !== null) {
                $normalized[] = $rule;
            }
        }

        if ($normalized === []) {
            $normalized = [self::defaultNewRule()];
        }

        Option::set(self::MODULE_ID, 'rules', (string) json_encode($normalized, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array{xml_id: string, enabled: bool, lifetime_days: float, auto_on_create: bool, track_manual: bool, assign_filter: array<string, mixed>}|null
     */
    public static function getRuleByXmlId(string $xmlId): ?array
    {
        $xmlId = strtoupper(trim($xmlId));
        foreach (self::getRules() as $rule) {
            if (strcasecmp($rule['xml_id'], $xmlId) === 0) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return list<array{xml_id: string, enabled: bool, lifetime_days: float, auto_on_create: bool, track_manual: bool, assign_filter: array<string, mixed>}>
     */
    public static function getEnabledRules(): array
    {
        return array_values(array_filter(
            self::getRules(),
            static fn (array $rule): bool => $rule['enabled'] === true
        ));
    }

    /**
     * Lifetime threshold as DateTime for SQL compare (assigned_at <= now - lifetime).
     */
    public static function lifetimeThreshold(\DateTimeInterface $now, float $lifetimeDays): \Bitrix\Main\Type\DateTime
    {
        $seconds = (int) round($lifetimeDays * 86400);
        if ($seconds < 0) {
            $seconds = 0;
        }

        $ts = $now->getTimestamp() - $seconds;

        return \Bitrix\Main\Type\DateTime::createFromTimestamp($ts);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{xml_id: string, enabled: bool, lifetime_days: float, auto_on_create: bool, track_manual: bool, assign_filter: array<string, mixed>}|null
     */
    private static function normalizeRule(array $row): ?array
    {
        $xmlId = strtoupper(trim((string) ($row['xml_id'] ?? '')));
        if ($xmlId === '') {
            return null;
        }

        $lifetime = (float) ($row['lifetime_days'] ?? 30);
        if ($lifetime < 0) {
            $lifetime = 0.0;
        }

        $assignFilter = [];
        if (isset($row['assign_filter']) && is_array($row['assign_filter'])) {
            $assignFilter = self::normalizeAssignFilter($row['assign_filter']);
        }

        return [
            'xml_id' => $xmlId,
            'enabled' => self::toBool($row['enabled'] ?? true),
            'lifetime_days' => $lifetime,
            'auto_on_create' => self::toBool($row['auto_on_create'] ?? true),
            'track_manual' => self::toBool($row['track_manual'] ?? true),
            'assign_filter' => $assignFilter,
        ];
    }

    /**
     * Keep GetList-compatible keys: string keys and numeric keys with nested groups.
     *
     * @param array<mixed, mixed> $filter
     * @return array<string|int, mixed>
     */
    public static function normalizeAssignFilter(array $filter): array
    {
        $result = [];
        foreach ($filter as $key => $value) {
            if (is_string($key) && $key !== '') {
                $result[$key] = is_array($value) ? self::normalizeAssignFilter($value) : $value;
                continue;
            }
            // Bitrix OR/AND subgroups: [ 'LOGIC' => 'OR', [ ... ], [ ... ] ]
            if (is_int($key) && is_array($value)) {
                $result[$key] = self::normalizeAssignFilter($value);
            }
        }

        return $result;
    }

    /**
     * Parse JSON filter from admin textarea. Returns null on invalid JSON.
     *
     * @return array<string, mixed>|null
     */
    public static function parseAssignFilterJson(string $json): ?array
    {
        $json = trim($json);
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        // Reject list arrays like [1,2] — GetList filter must be associative.
        if ($decoded !== [] && self::isListArray($decoded)) {
            return null;
        }

        return self::normalizeAssignFilter($decoded);
    }

    /**
     * @param array<mixed, mixed> $arr
     */
    private static function isListArray(array $arr): bool
    {
        $i = 0;
        foreach ($arr as $key => $_) {
            if ($key !== $i) {
                return false;
            }
            ++$i;
        }

        return true;
    }

    /**
     * @param mixed $value
     */
    private static function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtoupper($value), ['Y', '1', 'TRUE'], true);
        }

        return (bool) $value;
    }
}
