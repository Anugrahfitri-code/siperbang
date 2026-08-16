<?php

namespace Tests\Feature\Security;

use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use App\Models\User;
use App\Models\Barang;
use App\Models\StockHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BulkStockIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;
    private KodePersediaan $kodeValid;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->petugas = User::factory()->create(['role' => 'Petugas Persediaan']);
        $this->kodeValid = KodePersediaan::create([
            'kode' => '1.01.03.01.001',
            'nama_barang' => 'Kertas HVS'
        ]);
    }

    public function test_bulk_route_is_removed()
    {
        // 1. /api/stocks/bulk no longer reachable
        $this->assertFalse(Route::has('api.stocks.bulk'));
        
        $response = $this->actingAs($this->petugas)->postJson('/api/stocks/bulk', []);
        $this->assertContains($response->status(), [404, 405]);
    }

    public function test_verification_is_atomic_and_rolls_back_on_failure()
    {
        // Setup batch 1 with 2 details
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_MENUNGGU_VERIFIKASI
        ]);
        $detail1 = StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Barang A',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Pending',
        ]);
        
        // Setup batch 2 with 1 detail
        $batch2 = StokUpload::create([
            'file_name_original' => 'test2.xlsx',
            'file_name_stored' => 'test2.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_MENUNGGU_VERIFIKASI
        ]);
        $detailOtherBatch = StokUploadDetail::create([
            'stok_upload_id' => $batch2->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Barang B',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Pending',
        ]);

        // Attempt verification with detail from another batch
        $response = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/verifikasi-api", [
            'items' => [
                [
                    'detail_id' => $detail1->id,
                    'action' => 'Setuju',
                    'kode_persediaan' => $this->kodeValid->kode,
                ],
                [
                    'detail_id' => $detailOtherBatch->id, // Exists, but belongs to batch 2
                    'action' => 'Setuju',
                    'kode_persediaan' => $this->kodeValid->kode,
                ]
            ]
        ]);

        $response->assertStatus(400); // Because detail validation inside transaction will fail
        
        // Assert rollback: detail 1 should NOT be updated
        $this->assertEquals('Pending', $detail1->fresh()->status_verification);
    }

    public function test_verification_after_finalization_is_rejected()
    {
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_SELESAI
        ]);
        $detail = StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Barang A',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
        ]);

        $response = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/verifikasi-api", [
            'items' => [
                [
                    'detail_id' => $detail->id,
                    'action' => 'Tolak',
                ]
            ]
        ]);

        $response->assertStatus(400);
        $this->assertEquals('Setuju', $detail->fresh()->status_verification);
    }

    public function test_exact_source_state_required_for_finalization()
    {
        // State is MENUNGGU_VERIFIKASI, not SIAP_DIFINALISASI
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_MENUNGGU_VERIFIKASI
        ]);
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Barang A',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $this->kodeValid->kode,
        ]);

        $response = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");
        
        $response->assertStatus(400);
        $this->assertEquals(StokUpload::STATUS_MENUNGGU_VERIFIKASI, $batch->fresh()->status);
        $this->assertDatabaseCount('stock_items', 0);
    }

    public function test_finalization_happy_path_and_ledger_math()
    {
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_SIAP_DIFINALISASI
        ]);
        
        // Existing product
        $existingBarang = Barang::create([
            'code' => $this->kodeValid->kode,
            'name' => 'Kertas A4',
            'category' => 'ATK',
            'qty' => 50,
            'unit' => 'Rim',
        ]);

        // Detail 1: Add to existing
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Kertas A4',
            'qty' => 20,
            'unit' => 'Rim',
            'price_unit' => 1000,
            'total_excel' => 20000,
            'total_calculated' => 20000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $existingBarang->code,
        ]);

        // Detail 2: Create new
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Kertas F4',
            'qty' => 30,
            'unit' => 'Rim',
            'price_unit' => 1000,
            'total_excel' => 30000,
            'total_calculated' => 30000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $this->kodeValid->kode,
        ]);

        $response = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");
        $response->assertOk();

        $this->assertEquals(StokUpload::STATUS_SELESAI, $batch->fresh()->status);

        // Verify existing updated
        $this->assertEquals(70, $existingBarang->fresh()->qty);

        // Verify new created
        $newBarang = Barang::where('name', 'Kertas F4')->first();
        $this->assertNotNull($newBarang);
        $this->assertEquals(30, $newBarang->qty);

        // Verify ledger math
        $histories = StockHistory::where('stok_upload_id', $batch->id)->get();
        $this->assertCount(2, $histories);

        foreach ($histories as $history) {
            $this->assertEquals($history->qty_after - $history->qty_before, $history->qty_change);
        }
    }

    public function test_sequential_duplicate_finalization_is_safe()
    {
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_SIAP_DIFINALISASI
        ]);
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'Barang Baru',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $this->kodeValid->kode,
        ]);

        // 1st call
        $response1 = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");
        $response1->assertOk();
        $this->assertEquals(10, Barang::where('name', 'Barang Baru')->first()->qty);

        // 2nd call
        $response2 = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");
        $response2->assertStatus(400); // Already Selesai
        
        // Assert qty hasn't doubled
        $this->assertEquals(10, Barang::where('name', 'Barang Baru')->first()->qty);
        $this->assertDatabaseCount('stok_histories', 1);
    }

    public function test_same_logical_item_within_batch_is_aggregated_safely()
    {
        $batch = StokUpload::create([
            'file_name_original' => 'test.xlsx',
            'file_name_stored' => 'test.xlsx',
            'user_id' => $this->petugas->id,
            'upload_date' => now(),
            'status' => StokUpload::STATUS_SIAP_DIFINALISASI
        ]);
        
        // Duplicate items in same batch
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'kertas hvs',
            'qty' => 10,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 10000,
            'total_calculated' => 10000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $this->kodeValid->kode,
        ]);
        
        StokUploadDetail::create([
            'stok_upload_id' => $batch->id,
            'sheet_name' => 'Sheet1',
            'nama_barang' => 'KERTAS HVS', // Case insensitive identity
            'qty' => 15,
            'unit' => 'Pcs',
            'price_unit' => 1000,
            'total_excel' => 15000,
            'total_calculated' => 15000,
            'status_validation' => 'Menunggu Verifikasi',
            'status_verification' => 'Setuju',
            'verified_kode_persediaan' => $this->kodeValid->kode,
        ]);

        $response = $this->actingAs($this->petugas)->postJson("/api/stok-upload/{$batch->id}/finalisasi-api");
        $response->assertOk();

        // Must create exactly one StockItem with qty = 25
        $barangs = Barang::where('name', 'like', 'kertas hvs')->get();
        $this->assertCount(1, $barangs);
        $this->assertEquals(25, $barangs->first()->qty);

        // History must have one entry with change = 25
        $this->assertDatabaseHas('stok_histories', [
            'stock_item_id' => $barangs->first()->id,
            'qty_change' => 25,
            'qty_after' => 25,
        ]);
    }
}
