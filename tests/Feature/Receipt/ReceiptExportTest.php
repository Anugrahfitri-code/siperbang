<?php

namespace Tests\Feature\Receipt;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ReceiptExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_csv_format(): void
    {
        $user = User::factory()->create([
            'role' => 'Petugas Persediaan',
            'status' => 'Aktif',
        ]);

        $receipt = Receipt::create([
            'invoice_no' => 'INV-CSV',
            'store_name' => 'Toko CSV',
            'date' => '2026-08-01',
            'is_taxed' => false,
            'tax_rate' => 0,
            'subtotal' => 10000,
            'tax_amount' => 0,
            'total' => 10000,
            'is_verified' => true,
            'status' => 'Dokumen Valid',
            'method' => 'cash',
        ]);

        \DB::table('kode_persediaan')->insert([
            'kode' => '1010300001',
            'nama_barang' => 'Barang CSV',
            'kategori_barang_id' => 1,
        ]);

        ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Barang CSV',
            'qty' => 1,
            'unit' => 'PCS',
            'inventory_code' => '1010300001',
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        $response = $this->actingAs($user)->get(
            '/api/export-excel?year=2026&month=08&annual=false'
        );

        $response->assertOk();
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
        $this->assertStringContainsString(
            'SIPERBANG_REKAP_BULANAN_08_2026.csv',
            (string) $response->headers->get('Content-Disposition')
        );
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

        // Assert the content contains the invoice number
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('INV-CSV', $content);
        $this->assertStringContainsString('Toko CSV', $content);
        $this->assertStringContainsString('Barang CSV', $content);
    }
}
