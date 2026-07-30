<?php

use App\Support\Inventory\OfficeInventoryCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kategori_barang')) {
            return;
        }

        $categoryIds = [];

        foreach (OfficeInventoryCatalog::groups() as $group => $name) {
            $row = DB::table('kategori_barang')->where('nama', $name)->first();

            if ($row === null) {
                $id = DB::table('kategori_barang')->insertGetId([
                    'nama' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $id = (int) $row->id;
            }

            $categoryIds[$group] = $id;

            if (Schema::hasTable('kode_persediaan')) {
                DB::table('kode_persediaan')
                    ->where('kode', 'like', OfficeInventoryCatalog::codePrefix() . $group . '%')
                    ->update([
                        'kategori_barang_id' => $id,
                        'updated_at' => now(),
                    ]);
            }

            if (Schema::hasTable('stock_items')) {
                DB::table('stock_items')
                    ->where(function ($query) use ($group): void {
                        $query->where(
                            'code',
                            'like',
                            OfficeInventoryCatalog::codePrefix() . $group . '%',
                        )->orWhere('code', 'like', '1.01.03.' . $group . '%');
                    })
                    ->update([
                        'category' => $name,
                        'updated_at' => now(),
                    ]);
            }
        }

        $this->mergeLegacyRows($categoryIds);
    }

    /**
     * @param array<string, int> $categoryIds
     */
    private function mergeLegacyRows(array $categoryIds): void
    {
        $categories = DB::table('kategori_barang')->orderBy('id')->get();

        foreach ($categories as $category) {
            $canonicalName = OfficeInventoryCatalog::canonicalCategory(
                (string) $category->nama,
            );

            if ($canonicalName === null || $canonicalName === $category->nama) {
                continue;
            }

            $group = OfficeInventoryCatalog::groupForCategory($canonicalName);

            if ($group === null || ! isset($categoryIds[$group])) {
                continue;
            }

            if (Schema::hasTable('kode_persediaan')) {
                DB::table('kode_persediaan')
                    ->where('kategori_barang_id', $category->id)
                    ->where('kode', 'like', OfficeInventoryCatalog::codePrefix() . '%')
                    ->update([
                        'kategori_barang_id' => $categoryIds[$group],
                        'updated_at' => now(),
                    ]);
            }

            if (Schema::hasTable('stock_items')) {
                DB::table('stock_items')
                    ->where('category', $category->nama)
                    ->update([
                        'category' => $canonicalName,
                        'updated_at' => now(),
                    ]);
            }

            $stillReferenced = Schema::hasTable('kode_persediaan')
                && DB::table('kode_persediaan')
                    ->where('kategori_barang_id', $category->id)
                    ->exists();

            if (! $stillReferenced) {
                DB::table('kategori_barang')->where('id', $category->id)->delete();
            }
        }
    }

    public function down(): void
    {
        // Normalisasi data tidak dikembalikan agar kategori ganda tidak muncul lagi.
    }
};
