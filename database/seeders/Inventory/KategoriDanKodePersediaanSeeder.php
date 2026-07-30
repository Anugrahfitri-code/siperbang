<?php

namespace Database\Seeders\Inventory;

use Illuminate\Database\Seeder;

/**
 * Alias kompatibilitas untuk dokumentasi/perintah lama.
 *
 * Master proyek sekarang hanya menggunakan kelompok resmi 1.01.03.
 */
class KategoriDanKodePersediaanSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OfficeActivityInventoryCodeSeeder::class);
    }
}
