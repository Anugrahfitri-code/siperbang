<?php

namespace App\Support\Inventory;

use Illuminate\Support\Str;

final class OfficeInventoryCatalog
{
    public static function codePrefix(): string
    {
        return (string) config('office_inventory.code_prefix', '10103');
    }

    /**
     * @return array<string, string>
     */
    public static function groups(): array
    {
        return config('office_inventory.groups', []);
    }

    /**
     * @return list<array{group:string, code:string, code_prefix:string, name:string}>
     */
    public static function categoryOptions(): array
    {
        $formattedPrefix = (string) config(
            'office_inventory.formatted_prefix',
            '1.01.03',
        );

        $options = [];

        foreach (self::groups() as $group => $name) {
            $options[] = [
                'group' => (string) $group,
                'code' => $formattedPrefix.'.'.$group,
                'code_prefix' => self::codePrefix().$group,
                'name' => $name,
            ];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public static function categoryNames(): array
    {
        return array_values(self::groups());
    }

    public static function normalizeCode(?string $code): string
    {
        return preg_replace('/\D+/', '', (string) $code) ?? '';
    }

    public static function isOfficeCode(?string $code): bool
    {
        $normalized = self::normalizeCode($code);

        return strlen($normalized) === 10
            && str_starts_with($normalized, self::codePrefix());
    }

    public static function groupForCode(?string $code): ?string
    {
        if (! self::isOfficeCode($code)) {
            return null;
        }

        $group = substr(self::normalizeCode($code), 5, 2);

        return array_key_exists($group, self::groups()) ? $group : null;
    }

    public static function categoryForCode(?string $code): ?string
    {
        $group = self::groupForCode($code);

        return $group === null ? null : self::groups()[$group];
    }

    public static function canonicalCategory(?string $category): ?string
    {
        $normalized = self::normalizeName($category);

        if ($normalized === '') {
            return null;
        }

        foreach (self::groups() as $name) {
            if (self::normalizeName($name) === $normalized) {
                return $name;
            }
        }

        foreach (config('office_inventory.aliases', []) as $alias => $group) {
            if (self::normalizeName($alias) === $normalized) {
                return self::groups()[(string) $group] ?? null;
            }
        }

        return null;
    }

    public static function groupForCategory(?string $category): ?string
    {
        $canonical = self::canonicalCategory($category);

        if ($canonical === null) {
            return null;
        }

        $group = array_search($canonical, self::groups(), true);

        return $group === false ? null : (string) $group;
    }

    private static function normalizeName(?string $name): string
    {
        $value = Str::upper(trim((string) $name));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s*\/\s*/', '/', $value) ?? $value;

        return $value;
    }
}
