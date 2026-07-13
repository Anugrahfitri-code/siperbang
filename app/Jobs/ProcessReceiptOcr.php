<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ReceiptDocument;
use App\Enums\ReceiptDocumentStatus;
use App\Services\Ocr\OcrServiceClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessReceiptOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $document;

    // Retry configuration
    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 300; // 5 minutes, larger than HTTP timeout (180s)

    /**
     * Create a new job instance.
     */
    public function __construct(ReceiptDocument $document)
    {
        $this->document = $document;
        $this->onQueue('ocr');
    }

    /**
     * Execute the job.
     */
    public function handle(OcrServiceClient $client): void
    {
        // Refresh model to get latest status
        $this->document->refresh();

        // Idempotency / Double processing check
        if (!in_array($this->document->status, [ReceiptDocumentStatus::QUEUED, ReceiptDocumentStatus::UPLOADED])) {
            Log::info("ProcessReceiptOcr: Skipping document {$this->document->id} because status is {$this->document->status->value}");
            return;
        }

        // Mark as processing and increment attempts
        $this->document->update([
            'status' => ReceiptDocumentStatus::PROCESSING,
        ]);

        $filePath = Storage::disk('local')->path($this->document->storage_path);

        try {
            // Process via OcrServiceClient
            $result = $client->processReceipt($filePath, $this->document->original_filename);

            // Update on success
            $this->document->update([
                'status' => ReceiptDocumentStatus::NEEDS_REVIEW,
                'raw_text' => $result['raw_text'] ?? null,
                'raw_result' => $result,
                'parsed_result' => $result['document'] ?? null,
                'overall_confidence' => $result['overall_confidence'] ?? null,
                'ocr_engine' => $result['engine'] ?? 'PaddleOCR',
                'ocr_engine_version' => $result['engine_version'] ?? null,
                'processed_at' => now(),
                'error_message' => null,
            ]);

        } catch (\Throwable $e) {
            // Log full error for internal tracking
            Log::error("ProcessReceiptOcr failed for document {$this->document->id}: " . $e->getMessage(), [
                'exception' => $e
            ]);

            // Save concise error to database (no stack trace)
            $conciseError = "OCR Error: " . $e->getMessage();
            if ($e instanceof \App\Exceptions\OcrServiceException) {
                $conciseError .= " (Code: " . $e->getCode() . ")";
            }

            $this->document->update([
                'status' => ReceiptDocumentStatus::FAILED,
                'error_message' => $conciseError,
                'processed_at' => now(),
            ]);

            // Rethrow so the queue worker knows it failed (triggers retry if attempts < tries)
            // But we already updated status to FAILED.
            // If we rethrow, the queue will retry it automatically and increment job attempts.
            // When job runs again, it will see FAILED and skip unless we allow FAILED in idempotency check.
            // Since we use 'queued' for active jobs, if we want Laravel's native retry to work,
            // we should set it back to QUEUED before failing, OR just not rethrow if we handle retries manually.
            // The instructions say "attempts bertambah" (which we did manually in controller, but let's rely on job retries too).
            
            // To make Laravel queue retry work nicely with our custom status:
            if ($this->attempts() < $this->tries) {
                $this->document->update(['status' => ReceiptDocumentStatus::QUEUED]);
            }
            
            throw $e;
        }
    }
}
