<?php

namespace App\Services;

use App\Models\KodePersediaan;
use App\Support\OfficeInventoryCatalog;

class KodePersediaanService
{
    /**
     * Suggest an inventory code based on category and item name.
     */
    public function suggestCode(string $categoryName, string $itemName): ?string
    {
        $canonicalCategory = OfficeInventoryCatalog::canonicalCategory(
            $categoryName,
        );
        $group = OfficeInventoryCatalog::groupForCategory($canonicalCategory);

        $query = KodePersediaan::query()
            ->where('kode', 'like', OfficeInventoryCatalog::codePrefix() . '%');

        if ($group !== null) {
            $query->where(
                'kode',
                'like',
                OfficeInventoryCatalog::codePrefix() . $group . '%',
            );
        }

        $codes = $query->orderBy('kode')->get();
        $bestMatchCode = null;
        $highestScore = 0;
        $itemNameLower = mb_strtolower(trim($itemName));

        foreach ($codes as $codeItem) {
            $databaseNameLower = mb_strtolower($codeItem->nama_barang);
            $databaseWords = explode(
                ' ',
                preg_replace('/[^\pL\pN\s]/u', '', $databaseNameLower) ?? '',
            );

            $matchCount = 0;

            foreach ($databaseWords as $word) {
                if (mb_strlen($word) > 2 && str_contains($itemNameLower, $word)) {
                    $matchCount += 2;
                }
            }

            if (
                str_contains($itemNameLower, $databaseNameLower)
                || str_contains($databaseNameLower, $itemNameLower)
            ) {
                $matchCount += 5;
            }

            if ($matchCount > $highestScore) {
                $highestScore = $matchCount;
                $bestMatchCode = $codeItem->kode;
            }
        }

        if ($bestMatchCode !== null) {
            return $bestMatchCode;
        }

        if ($group !== null) {
            $fallback = $codes->first(
                fn (KodePersediaan $code): bool => str_ends_with($code->kode, '999'),
            ) ?? $codes->first();

            return $fallback?->kode;
        }

        return KodePersediaan::query()
            ->where('kode', '1010399999')
            ->value('kode');
    }

    /**
     * Map code to the official 1.01.03 category.
     */
    public function getCategoryByCode(string $code): string
    {
        $normalizedCode = OfficeInventoryCatalog::normalizeCode($code);
        $category = OfficeInventoryCatalog::categoryForCode($normalizedCode);

        if ($category !== null) {
            return $category;
        }

        return OfficeInventoryCatalog::groups()['99'];
    }
}
