<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;
use App\Models\ReceiptDocument;
use App\Enums\ReceiptDocumentStatus;
use App\Jobs\ProcessReceiptOcr;
use App\Services\Ocr\OcrServiceClient;

class ReceiptDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        // create a basic user
        $this->user = User::factory()->create([
            'role' => 'Petugas Persediaan'
        ]);
    }

    public function test_guest_cannot_upload()
    {
        $response = $this->postJson('/api/receipt-documents', []);
        $response->assertStatus(401);
    }

    public function test_valid_file_returns_202_and_dispatches_job()
    {
        Queue::fake();

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->actingAs($this->user)->postJson('/api/receipt-documents', [
            'document' => $file
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(ProcessReceiptOcr::class);
    }

    public function test_invalid_format_is_rejected()
    {
        $file = UploadedFile::fake()->create('receipt.exe', 100, 'application/x-msdownload');

        $response = $this->actingAs($this->user)->postJson('/api/receipt-documents', [
            'document' => $file
        ]);

        $response->assertStatus(422);
    }

    public function test_ocr_client_uses_http_fake_and_updates_status()
    {
        Http::fake([
            '*/internal/v1/receipt-ocr' => Http::response([
                'success' => true,
                'raw_text' => 'test',
                'document' => [
                    'store_name' => ['value' => 'Toko A']
                ]
            ], 200)
        ]);

        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'test.jpg',
            'storage_path' => 'receipts/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => 'dummy',
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        // Mock storage file
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('receipts/test.jpg', 'fake content');

        $job = new ProcessReceiptOcr($doc);
        $job->handle(new OcrServiceClient());

        $doc->refresh();
        $this->assertEquals(ReceiptDocumentStatus::NEEDS_REVIEW, $doc->status);
        $this->assertEquals('Toko A', $doc->parsed_result['store_name']['value']);
    }
    
    public function test_verify_transaction_and_calculations()
    {
        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'test.jpg',
            'storage_path' => 'receipts/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => 'dummy2',
            'status' => ReceiptDocumentStatus::NEEDS_REVIEW,
        ]);

        $payload = [
            'invoiceNo' => 'INV-1',
            'storeName' => 'Toko B',
            'date' => '2026-07-13',
            'isTaxed' => true,
            'taxRate' => 11,
            'items' => [
                ['name' => 'Item 1', 'qty' => 2, 'price' => 50000]
            ]
        ];

        $response = $this->actingAs($this->user)->putJson("/api/receipt-documents/{$doc->id}/verify", $payload);
        
        $response->assertStatus(200);
        $doc->refresh();
        $this->assertEquals(ReceiptDocumentStatus::VERIFIED, $doc->status);
        
        // Assert receipt created
        $this->assertNotNull($doc->receipt_id);
        $receipt = $doc->receipt;
        $this->assertEquals(100000, $receipt->subtotal);
        $this->assertEquals(11000, $receipt->tax_amount);
        $this->assertEquals(111000, $receipt->total);
    }
    
    public function test_double_verification_rejected()
    {
        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'test.jpg',
            'storage_path' => 'receipts/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => 'dummy3',
            'status' => ReceiptDocumentStatus::VERIFIED,
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/receipt-documents/{$doc->id}/verify", []);
        $response->assertStatus(422);
    }
}
