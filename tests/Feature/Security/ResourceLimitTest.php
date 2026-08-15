<?php

namespace Tests\Feature\Security;

use App\Jobs\Receipt\ProcessReceiptOcr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResourceLimitTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userA = User::factory()->create(['role' => 'Petugas Persediaan']);
        $this->userB = User::factory()->create(['role' => 'Petugas Persediaan']);
        $this->superAdmin = User::factory()->create(['role' => 'Superadmin']);

        Storage::fake('local');
        Queue::fake();
    }

    protected function tearDown(): void
    {
        RateLimiter::clear('receipt-ocr:'.$this->userA->id);
        RateLimiter::clear('receipt-ocr:'.$this->userB->id);
        RateLimiter::clear('receipt-ocr:'.$this->superAdmin->id);
        RateLimiter::clear('stock-upload:'.$this->userA->id);
        RateLimiter::clear('stock-import:'.$this->userA->id);
        RateLimiter::clear('pdf-export:'.$this->userA->id);
        RateLimiter::clear('excel-export:'.$this->userA->id);
        parent::tearDown();
    }

    public function test_ocr_upload_rate_limited()
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        // Allow 10 requests per minute
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($this->userA)
                ->postJson('/api/receipt-documents', ['document' => $file]);
            $response->assertStatus(202);
        }

        // 11th request should be rate limited
        $response = $this->actingAs($this->userA)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $response->assertStatus(429);
    }

    public function test_rate_limits_are_isolated_by_user()
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->userA)
                ->postJson('/api/receipt-documents', ['document' => $file]);
        }

        // User A is limited
        $responseA = $this->actingAs($this->userA)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $responseA->assertStatus(429);

        // User B from same IP should not be limited
        $responseB = $this->actingAs($this->userB)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $responseB->assertStatus(202);
    }

    public function test_superadmin_is_also_rate_limited()
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->superAdmin)
                ->postJson('/api/receipt-documents', ['document' => $file]);
        }

        $response = $this->actingAs($this->superAdmin)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $response->assertStatus(429);
    }

    public function test_ocr_limit_does_not_affect_other_endpoints()
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->userA)
                ->postJson('/api/receipt-documents', ['document' => $file]);
        }

        // OCR is limited
        $responseOCR = $this->actingAs($this->userA)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $responseOCR->assertStatus(429);

        // Stock upload should still work (different bucket)
        $stockFile = UploadedFile::fake()->create('stocks.xlsx', 100);
        $responseStock = $this->actingAs($this->userA)
            ->postJson('/api/stocks/bulk', ['file' => $stockFile]);

        // As long as it is not 429
        $this->assertNotEquals(429, $responseStock->getStatusCode());
    }

    public function test_decay_allows_requests_after_time()
    {
        $file = UploadedFile::fake()->image('receipt.jpg');

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->userA)
                ->postJson('/api/receipt-documents', ['document' => $file]);
        }

        $response = $this->actingAs($this->userA)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $response->assertStatus(429);

        $this->travel(61)->seconds();

        $response = $this->actingAs($this->userA)
            ->postJson('/api/receipt-documents', ['document' => $file]);
        $response->assertStatus(202);
    }

    public function test_stock_upload_rate_limited()
    {
        $file = UploadedFile::fake()->create('stocks.xlsx', 100);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->userA)
                ->post('/stok-upload', ['file_excel' => $file]);
        }

        $response = $this->actingAs($this->userA)
            ->post('/stok-upload', ['file_excel' => $file]);
        $response->assertStatus(429);
    }

    public function test_stock_import_rate_limited()
    {
        for ($i = 0; $i < 15; $i++) {
            $this->actingAs($this->userA)
                ->postJson('/stok-upload/1/verifikasi');
        }

        $response = $this->actingAs($this->userA)
            ->postJson('/stok-upload/1/verifikasi');
        $response->assertStatus(429);
    }

    public function test_pdf_export_rate_limited()
    {
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($this->userA)
                ->getJson('/api/requests/recap/pdf');
        }

        $response = $this->actingAs($this->userA)
            ->getJson('/api/requests/recap/pdf');
        $response->assertStatus(429);
    }

    public function test_excel_export_rate_limited()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($this->userA)
                ->postJson('/api/receipts/export-excel');
        }

        $response = $this->actingAs($this->userA)
            ->postJson('/api/receipts/export-excel');
        $response->assertStatus(429);
    }

    public function test_ocr_job_overlap_lock_configuration()
    {
        $jobA = new ProcessReceiptOcr(999);
        $jobB = new ProcessReceiptOcr(1000);

        $middlewaresA = $jobA->middleware();
        $this->assertCount(1, $middlewaresA);

        $lockA = $middlewaresA[0];
        $this->assertInstanceOf(WithoutOverlapping::class, $lockA);

        $middlewaresB = $jobB->middleware();
        $lockB = $middlewaresB[0];

        // Proof of key separation
        $this->assertNotEquals($lockA->key, $lockB->key);
        $this->assertEquals(999, $lockA->key);
        $this->assertEquals(1000, $lockB->key);

        // Proof of dontRelease behavior
        $this->assertFalse($lockA->releaseAfter > 0);

        // Proof of explicit expireAfter
        $this->assertEquals(160, $lockA->expiresAfter);

        // Prove invariant: JOB < EXPIRY < RETRY_AFTER
        $jobTimeout = $jobA->timeout;
        $queueRetryAfter = config('queue.connections.database.retry_after', 180);

        $this->assertGreaterThan($jobTimeout, $lockA->expiresAfter, 'Lock expiry must be longer than job timeout to protect running job');
        $this->assertLessThan($queueRetryAfter, $lockA->expiresAfter, 'Lock expiry must be shorter than queue retry_after to avoid stale locks');
    }
}
