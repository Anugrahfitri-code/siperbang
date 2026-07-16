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
        config([
            'services.ocr.url' =>
                'http://ocr.test',

            'services.ocr.token' =>
                'test-service-token',

            'services.ocr.timeout' =>
                10,

            'services.ocr.connect_timeout' =>
                2,
        ]);

        Http::fake([
            'http://ocr.test/internal/v1/receipt-ocr' =>
                Http::response([
                    'success' => true,
                    'engine' => 'paddleocr',
                    'engine_version' => '3.7.0',
                    'paddle_version' => '3.3.1',
                    'overall_confidence' => 0.98,
                    'raw_text' => "TOKO A\nTOTAL\n80.000",

                    'pages' => [
                        [
                            'page' => 1,
                            'width' => 1000,
                            'height' => 1400,
                            'lines' => [
                                [
                                    'text' => 'TOKO A',
                                    'confidence' => 0.99,
                                    'box' => [
                                        [10, 10],
                                        [100, 10],
                                        [100, 40],
                                        [10, 40],
                                    ],
                                ],
                                [
                                    'text' => 'TOTAL',
                                    'confidence' => 0.99,
                                    'box' => [
                                        [10, 100],
                                        [100, 100],
                                        [100, 130],
                                        [10, 130],
                                    ],
                                ],
                                [
                                    'text' => '80.000',
                                    'confidence' => 0.96,
                                    'box' => [
                                        [200, 100],
                                        [300, 100],
                                        [300, 130],
                                        [200, 130],
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'document' => [
                        'store_name' => [
                            'value' => 'TOKO A',
                            'confidence' => 0.99,
                            'source' => 'ocr',
                        ],
                        'invoice_no' => [
                            'value' => null,
                            'confidence' => null,
                            'source' => null,
                        ],
                        'date' => [
                            'value' => null,
                            'confidence' => null,
                            'source' => null,
                        ],
                        'subtotal' => [
                            'value' => 80000,
                            'confidence' => 0.96,
                            'source' => 'ocr',
                        ],
                        'tax_rate' => [
                            'value' => null,
                            'confidence' => null,
                            'source' => null,
                        ],
                        'tax_amount' => [
                            'value' => null,
                            'confidence' => null,
                            'source' => null,
                        ],
                        'total' => [
                            'value' => 80000,
                            'confidence' => 0.96,
                            'source' => 'ocr',
                        ],
                        'items' => [],
                    ],

                    'items' => [],
                    'warnings' => [],
                ], 200),
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'receipts/test.jpg',
            'fake image content',
        );

        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'test.jpg',
            'storage_path' => 'receipts/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => 'dummy',
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        $job = new ProcessReceiptOcr(
            $doc->id,
        );

        $job->handle(
            new OcrServiceClient(),
        );

        $doc->refresh();

        $this->assertSame(
            ReceiptDocumentStatus::NEEDS_REVIEW,
            $doc->status,
        );

        $this->assertSame(
            'TOKO A',
            $doc->parsed_result[
                'store_name'
            ]['value'],
        );

        $this->assertSame(
            'paddleocr',
            $doc->ocr_engine,
        );

        $this->assertSame(
            '3.7.0',
            $doc->ocr_engine_version,
        );

        $this->assertSame(
            1,
            $doc->attempts,
        );

        Http::assertSent(function ($request) {
            return $request->url()
                === 'http://ocr.test/internal/v1/receipt-ocr'
                && $request->hasHeader(
                    'X-Service-Token',
                    'test-service-token',
                );
        });

        $this->assertSame(
            [],
            $doc->parsed_result['items'],
        );

        $this->assertSame(
            [],
            $doc->parsed_result['warnings'],
        );
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

    public function test_ocr_422_response_marks_document_as_failed()
    {
        config([
            'services.ocr.url' =>
                'http://ocr.test',

            'services.ocr.token' =>
                'test-service-token',

            'services.ocr.timeout' =>
                10,

            'services.ocr.connect_timeout' =>
                2,
        ]);

        Http::fake([
            'http://ocr.test/internal/v1/receipt-ocr' =>
                Http::response([
                    'detail' => (
                        'No readable text was detected '
                        . 'in the document.'
                    ),
                ], 422),
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'receipts/blank.pdf',
            '%PDF-1.4 blank',
        );

        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'blank.pdf',
            'storage_path' => 'receipts/blank.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'sha256' => 'blank-document',
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        $job = new ProcessReceiptOcr(
            $doc->id,
        );

        $job->handle(
            new OcrServiceClient(),
        );

        $doc->refresh();

        $this->assertSame(
            ReceiptDocumentStatus::FAILED,
            $doc->status,
        );

        $this->assertSame(
            1,
            $doc->attempts,
        );

        $this->assertNull(
            $doc->raw_text,
        );

        $this->assertNull(
            $doc->parsed_result,
        );

        $this->assertNotNull(
            $doc->error_message,
        );
    }

    public function test_http_200_with_success_false_is_rejected()
    {
        config([
            'services.ocr.url' =>
                'http://ocr.test',

            'services.ocr.token' =>
                'test-service-token',

            'services.ocr.timeout' =>
                10,

            'services.ocr.connect_timeout' =>
                2,
        ]);

        Http::fake([
            'http://ocr.test/internal/v1/receipt-ocr' =>
                Http::response([
                    'success' => false,
                    'raw_text' => null,
                    'pages' => [],
                ], 200),
        ]);

        \Illuminate\Support\Facades\Storage::fake('local');

        \Illuminate\Support\Facades\Storage::disk('local')->put(
            'receipts/test.jpg',
            'fake image',
        );

        $doc = ReceiptDocument::create([
            'uploaded_by' => $this->user->id,
            'original_filename' => 'test.jpg',
            'storage_path' => 'receipts/test.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sha256' => 'success-false',
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        $job = new ProcessReceiptOcr(
            $doc->id,
        );

        $job->handle(
            new OcrServiceClient(),
        );

        $doc->refresh();

        $this->assertSame(
            ReceiptDocumentStatus::FAILED,
            $doc->status,
        );

        $this->assertNull(
            $doc->raw_text,
        );
    }
}
