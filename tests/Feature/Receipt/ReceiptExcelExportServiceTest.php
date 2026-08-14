<?php

namespace Tests\Feature\Receipt;

use App\Models\KodePersediaan;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Services\Receipt\ReceiptExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReceiptExcelExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $templatePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templatePath = resource_path('templates/belanja-persediaan.xlsx');
    }

    public function test_export_service_can_load_internal_sanitized_template(): void
    {
        $this->assertFileExists($this->templatePath);
        $workbook = IOFactory::load($this->templatePath);

        $this->assertEquals(2, $workbook->getSheetCount());
        $this->assertNotNull($workbook->getSheetByName('130126 SS'));
        $this->assertNotNull($workbook->getSheetByName('170626 NA'));
    }

    public function test_exported_xlsx_contains_no_historical_operational_records(): void
    {
        $workbook = IOFactory::load($this->templatePath);

        foreach (['130126 SS', '170626 NA'] as $sheetName) {
            $sheet = $workbook->getSheetByName($sheetName);

            // highestRow should be <= 5 (headers and the single style template row)
            $this->assertLessThanOrEqual(5, $sheet->getHighestRow());

            // A2 should be blank
            $this->assertNull($sheet->getCell('A2')->getValue());

            // Row 5 dynamic cells should be blank
            foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
                $this->assertNull($sheet->getCell($col.'5')->getValue());
            }
        }
    }

    public function test_export_generates_valid_xlsx_and_proper_sheet_names(): void
    {
        /** @var ReceiptExcelExportService $service */
        $service = app(ReceiptExcelExportService::class);

        $receipt = Receipt::create([
            'invoice_no' => 'INV-SYNTHETIC-001',
            'store_name' => 'Toko Sintetis',
            'date' => '2026-08-15',
            'is_taxed' => false,
            'tax_rate' => 0,
            'subtotal' => 100000,
            'tax_amount' => 0,
            'total' => 100000,
            'is_verified' => true,
            'status' => 'Selesai',
        ]);

        KodePersediaan::create([
            'kode' => '1010301001',
            'nama_barang' => 'Master Kertas HVS',
        ]);

        ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'inventory_code' => '1010301001',
            'name' => 'Kertas HVS Synthetic',
            'qty' => 10,
            'unit' => 'RIM',
            'price' => 10000,
            'subtotal' => 100000,
        ]);

        $receipts = collect([$receipt]);
        $result = $service->create($receipts);

        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertFileExists($result['path']);

        $outputWorkbook = IOFactory::load($result['path']);

        // Should only have the generated sheet, old sheets removed
        $this->assertEquals(1, $outputWorkbook->getSheetCount());

        $generatedSheet = $outputWorkbook->getSheet(0);
        $title = $generatedSheet->getTitle();

        // Expected format: dmy TS (TS = initials for Toko Sintetis -> SI because Toko is ignored)
        $this->assertStringContainsString('150826 SI', $title);

        // Supplier cell check
        $this->assertEquals('SUPPLIER : SINTETIS', $generatedSheet->getCell('A2')->getValue());

        // Item check
        $this->assertEquals('Kertas HVS Synthetic', $generatedSheet->getCell('C5')->getValue());
        $this->assertEquals(10, $generatedSheet->getCell('D5')->getValue());

        // Clean up
        @unlink($result['path']);
    }
}
