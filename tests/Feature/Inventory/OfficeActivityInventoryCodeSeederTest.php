<?php

namespace Tests\Feature\Inventory;

use App\Models\KategoriBarang;
use App\Models\KodePersediaan;
use App\Models\StockItem;
use App\Support\Inventory\OfficeInventoryCatalog;
use Database\Seeders\Inventory\OfficeActivityInventoryCodeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeActivityInventoryCodeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent_and_removes_legacy_duplicates(): void
    {
        $legacy = KategoriBarang::create([
            'nama' => 'Alat Tulis Kantor (ATK)',
        ]);

        KodePersediaan::create([
            'kategori_barang_id' => $legacy->id,
            'kode' => '1010301001',
            'nama_barang' => 'Pulpen',
        ]);

        StockItem::create([
            'category' => 'Alat Tulis Kantor (ATK)',
            'code' => '1010301001',
            'name' => 'Pulpen Uji',
            'qty' => 1,
            'unit' => 'BUAH',
            'is_active' => true,
        ]);

        $this->seed(OfficeActivityInventoryCodeSeeder::class);
        $this->seed(OfficeActivityInventoryCodeSeeder::class);

        $this->assertSame(
            111,
            KodePersediaan::query()
                ->where('kode', 'like', OfficeInventoryCatalog::codePrefix() . '%')
                ->count(),
        );

        $this->assertDatabaseMissing('kategori_barang', [
            'nama' => 'Alat Tulis Kantor (ATK)',
        ]);

        $this->assertDatabaseHas('stock_items', [
            'code' => '1010301001',
            'category' => 'ALAT TULIS KANTOR',
        ]);

        $this->assertDatabaseHas('kode_persediaan', [
            'kode' => '1010309001',
            'kategori_barang_id' => KategoriBarang::query()
                ->where('nama', 'PERLENGKAPAN PENUNJANG KEGAITAN KANTOR')
                ->value('id'),
        ]);
    }
}
