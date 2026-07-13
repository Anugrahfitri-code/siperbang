<?php

namespace App\Services;

use App\Models\KategoriBarang;
use App\Models\KodePersediaan;

class KodePersediaanService
{
    /**
     * Suggest an inventory code based on category and item name.
     */
    public function suggestCode(string $categoryName, string $itemName): ?string
    {
        // 1. Try to find the category in database
        $category = KategoriBarang::where('nama', 'like', '%' . trim($categoryName) . '%')
            ->orWhere('nama', 'like', '%' . explode('(', trim($categoryName))[0] . '%')
            ->first();

        // 2. Query codes
        $query = KodePersediaan::query();
        if ($category) {
            $query->where('kategori_barang_id', $category->id);
        }

        $codes = $query->get();

        // 3. Find the best match using keyword matching
        $bestMatchCode = null;
        $highestScore = 0;

        $itemNameLower = strtolower(trim($itemName));

        foreach ($codes as $codeItem) {
            $dbNameLower = strtolower($codeItem->nama_barang);
            
            // Clean up name for comparison
            $dbWords = explode(' ', preg_replace('/[^\w\s]/', '', $dbNameLower));
            $itemWords = explode(' ', preg_replace('/[^\w\s]/', '', $itemNameLower));

            // Count overlapping words
            $matchCount = 0;
            foreach ($dbWords as $word) {
                if (strlen($word) > 2 && str_contains($itemNameLower, $word)) {
                    $matchCount += 2; // Exact word match gets more weight
                }
            }

            // check substring match
            if (str_contains($itemNameLower, $dbNameLower) || str_contains($dbNameLower, $itemNameLower)) {
                $matchCount += 5;
            }

            if ($matchCount > $highestScore) {
                $highestScore = $matchCount;
                $bestMatchCode = $codeItem->kode;
            }
        }

        // 4. Fallback if no match
        if ($bestMatchCode === null) {
            // Check if there is a category fallback
            if ($category) {
                $fallback = KodePersediaan::where('kategori_barang_id', $category->id)->first();
                if ($fallback) {
                    return $fallback->kode;
                }
            }
            // Absolute fallback (Lain-lain)
            return '1010399999';
        }

        return $bestMatchCode;
    }

    /**
     * Map category string to standard database category.
     */
    public function getCategoryByCode(string $code): string
    {
        $codeRule = KodePersediaan::with('kategoriBarang')->where('kode', $code)->first();
        if ($codeRule && $codeRule->kategoriBarang) {
            return $codeRule->kategoriBarang->nama;
        }

        // Guess from code prefix
        if (str_starts_with($code, '1010301') || str_starts_with($code, '1010302')) {
            return 'Alat Tulis Kantor (ATK)';
        } elseif (str_starts_with($code, '1010305')) {
            return 'Alat/Bahan Kebersihan';
        } elseif (str_starts_with($code, '1010304') || str_starts_with($code, '1010306')) {
            return 'Peralatan Komputer / Elektronik';
        }

        return 'Lain-lain';
    }
}
