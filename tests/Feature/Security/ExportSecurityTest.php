<?php

namespace Tests\Feature\Security;

use App\Models\KodePersediaan;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use App\Services\Receipt\ReceiptExcelExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExportSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'Superadmin',
        ]);

        KodePersediaan::create(['kode' => '1010301001', 'nama_barang' => 'Test Barang']);
    }

    public function test_csv_formula_injection_is_neutralized()
    {
        $receipt = Receipt::create([
            'is_verified' => true,
            'store_name' => '=1+1',
            'invoice_no' => '@SUM(A1:A2)',
            'method' => '-10+20',
            'bast_name' => '+CMD',
            'date' => '2026-08-15',
            'is_taxed' => false,
            'tax_rate' => 0,
            'subtotal' => 500,
            'tax_amount' => 0,
            'total' => 500,
            'status' => 'Valid',
        ]);

        ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => '=HYPERLINK("http://evil.com","Click")',
            'unit' => '@DANGER',
            'inventory_code' => '1010301001',
            'qty' => 5,
            'price' => 100,
            'subtotal' => 500,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/export-excel?year=All&month=All&annual=false');

        $response->assertStatus(200);

        $content = $response->streamedContent();

        $lines = explode("\n", trim($content));
        $this->assertGreaterThan(1, count($lines));

        // Parse header
        $header = str_getcsv($lines[0]);
        $this->assertEquals('No Nota', $header[0]);
        $this->assertEquals('Metode Pengadaan', $header[11]);

        // Parse first data row
        $row = str_getcsv($lines[1]);

        $this->assertEquals("'".'=1+1', $row[2]); // store_name
        $this->assertEquals("'".'@SUM(A1:A2)', $row[0]); // invoice_no
        $this->assertEquals("'".'-10+20', $row[11]); // method
        $this->assertEquals("'".'+CMD', $row[12]); // bast_name
        $this->assertEquals("'".'=HYPERLINK("http://evil.com","Click")', $row[4]); // name
        $this->assertEquals("'".'@DANGER', $row[6]); // unit

        // Ensure numeric isn't prefixed if not starting with dangerous char
        $this->assertEquals('1010301001', $row[3]); // inventory_code
        $this->assertEquals('5', $row[5]); // qty
        $this->assertEquals('100.00', $row[7]); // price
    }

    public function test_xlsx_formula_injection_is_neutralized()
    {
        $receipt = Receipt::create([
            'is_verified' => true,
            'store_name' => '=SUM(A1:B1)',
            'is_taxed' => false,
            'date' => '2026-08-15',
            'invoice_no' => 'INV-001',
            'tax_rate' => 0,
            'subtotal' => 500,
            'tax_amount' => 0,
            'total' => 500,
            'status' => 'Valid',
        ]);

        ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => '+CMD()',
            'unit' => '@Pcs',
            'inventory_code' => '1010301001',
            'qty' => 5,
            'price' => 100,
            'subtotal' => 500,
        ]);

        $exportService = app(ReceiptExcelExportService::class);
        $export = $exportService->create(collect([$receipt]));

        $this->assertFileExists($export['path']);

        $spreadsheet = IOFactory::load($export['path']);
        $sheet = $spreadsheet->getActiveSheet();

        // Check store name (starts with SUPPLIER : so it doesn't start with = anyway, but let's check it's a string)
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('A2')->getDataType());
        $this->assertStringContainsString('SUPPLIER : =SUM(A1:B1)', $sheet->getCell('A2')->getValue());

        // Check item name
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('C5')->getDataType());
        $this->assertEquals('+CMD()', $sheet->getCell('C5')->getValue());

        // Check unit
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('E5')->getDataType());
        $this->assertEquals('@Pcs', $sheet->getCell('E5')->getValue());

        // Check inventory code
        $this->assertEquals(DataType::TYPE_STRING, $sheet->getCell('B5')->getDataType());
        $this->assertEquals('1010301001', $sheet->getCell('B5')->getValue());

        // Check numeric values are still numbers
        $this->assertEquals(DataType::TYPE_NUMERIC, $sheet->getCell('D5')->getDataType());
        $this->assertEquals(5, $sheet->getCell('D5')->getValue());

        $this->assertEquals(DataType::TYPE_NUMERIC, $sheet->getCell('F5')->getDataType());
        $this->assertEquals(100, $sheet->getCell('F5')->getValue());

        // Check application-owned formula is still formula
        $this->assertEquals(DataType::TYPE_FORMULA, $sheet->getCell('G5')->getDataType());
        $this->assertEquals('=D5*F5', $sheet->getCell('G5')->getValue());

        @unlink($export['path']);
    }

    public function test_export_filename_is_sanitized()
    {
        $receipt = Receipt::create([
            'is_verified' => true,
            'store_name' => "Malicious\nName\r\n|Test",
            'is_taxed' => false,
            'date' => '2026-08-15',
            'invoice_no' => 'INV-001',
            'tax_rate' => 0,
            'subtotal' => 500,
            'tax_amount' => 0,
            'total' => 500,
            'status' => 'Valid',
        ]);

        ReceiptItem::create([
            'receipt_id' => $receipt->id,
            'name' => 'Test',
            'unit' => 'Pcs',
            'inventory_code' => '1010301001',
            'qty' => 1,
            'price' => 100,
            'subtotal' => 100,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/receipts/export-excel', [
            'receipt_ids' => [$receipt->id],
        ]);

        $response->assertStatus(200);
        $disposition = $response->headers->get('Content-Disposition');

        $this->assertStringNotContainsString("\n", $disposition);
        $this->assertStringNotContainsString("\r", $disposition);
        $this->assertStringNotContainsString('|', $disposition);
        $this->assertStringContainsString('Malicious_Name_Test', $disposition);
    }
}
