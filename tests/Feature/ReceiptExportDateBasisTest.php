<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\LogController;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ReceiptExportDateBasisTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_uses_upload_date_when_selected(): void
    {
        $receiptFromReceiptDate = Receipt::create([
            'invoice_no' => 'INV-RECEIPT-DATE',
            'store_name' => 'Toko A',
            'date' => '2026-07-15',
            'is_taxed' => false,
            'tax_rate' => 0,
            'subtotal' => 10000,
            'tax_amount' => 0,
            'total' => 10000,
            'is_verified' => true,
            'status' => 'Dokumen Valid',
            'method' => 'cash',
        ]);

        $receiptFromUploadDate = Receipt::create([
            'invoice_no' => 'INV-UPLOAD-DATE',
            'store_name' => 'Toko B',
            'date' => '2026-08-20',
            'is_taxed' => false,
            'tax_rate' => 0,
            'subtotal' => 20000,
            'tax_amount' => 0,
            'total' => 20000,
            'is_verified' => true,
            'status' => 'Dokumen Valid',
            'method' => 'cash',
            'created_at' => '2026-08-03 10:00:00',
            'updated_at' => '2026-08-03 10:00:00',
        ]);

        ReceiptItem::create([
            'receipt_id' => $receiptFromReceiptDate->id,
            'name' => 'Barang A',
            'qty' => 1,
            'price' => 10000,
            'subtotal' => 10000,
        ]);

        ReceiptItem::create([
            'receipt_id' => $receiptFromUploadDate->id,
            'name' => 'Barang B',
            'qty' => 1,
            'price' => 20000,
            'subtotal' => 20000,
        ]);

        $controller = new LogController();
        $request = Request::create('/api/export-excel', 'GET', [
            'year' => '2026',
            'month' => '08',
            'annual' => 'false',
            'date_basis' => 'upload_date',
        ]);

        $response = $controller->exportExcel($request);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $content = $this->captureStreamedResponse($response);

        $this->assertStringContainsString('INV-UPLOAD-DATE', $content);
        $this->assertStringNotContainsString('INV-RECEIPT-DATE', $content);
    }

    private function captureStreamedResponse(StreamedResponse $response): string
    {
        ob_start();
        $response->send();
        return ob_get_clean();
    }
}
