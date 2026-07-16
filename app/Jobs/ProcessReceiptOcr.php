<?php

namespace App\Jobs;

use App\Enums\ReceiptDocumentStatus;
use App\Exceptions\OcrServiceException;
use App\Models\ReceiptDocument;
use App\Services\Ocr\OcrServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class ProcessReceiptOcr implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 115;

    public bool $failOnTimeout = true;

    public function __construct(
        public readonly int $receiptDocumentId,
    ) {
        $this->onQueue('ocr');
    }

    public function backoff(): array
    {
        return [
            15,
            60,
            180,
        ];
    }

    public function handle(
        OcrServiceClient $client,
    ): void {
        /*
         * Klaim dokumen secara atomik.
         *
         * Jika dua job untuk dokumen yang sama berjalan bersamaan,
         * hanya satu job yang dapat mengubah queued menjadi processing.
         */
        $claimed = ReceiptDocument::query()
            ->whereKey(
                $this->receiptDocumentId,
            )
            ->whereIn(
                'status',
                [
                    ReceiptDocumentStatus::UPLOADED->value,
                    ReceiptDocumentStatus::QUEUED->value,
                ],
            )
            ->update([
                'status' =>
                    ReceiptDocumentStatus::PROCESSING->value,

                'attempts' =>
                    DB::raw('attempts + 1'),

                'error_message' => null,
                'processed_at' => null,
            ]);

        if ($claimed === 0) {
            Log::info(
                'OCR job dilewati karena dokumen tidak lagi berada pada status queued.',
                [
                    'receipt_document_id' =>
                        $this->receiptDocumentId,
                ],
            );

            return;
        }

        $document = ReceiptDocument::query()
            ->findOrFail(
                $this->receiptDocumentId,
            );

        try {
            $filePath = Storage::disk('local')
                ->path(
                    $document->storage_path,
                );

            $result = $client->processReceipt(
                filePath: $filePath,
                originalFilename:
                    $document->original_filename,
            );

            /*
             * Pemeriksaan tambahan.
             *
             * OcrServiceClient sudah melakukan validasi yang sama,
             * tetapi job tidak boleh mengandalkan response tanpa
             * pemeriksaan dasar.
             */
            if (($result['success'] ?? false) !== true) {
                throw new OcrServiceException(
                    message: 'OCR tidak menghasilkan status sukses.',
                    httpStatus: 502,
                    retryable: false,
                );
            }

            $rawText = $result['raw_text'] ?? null;

            if (
                ! is_string($rawText)
                || trim($rawText) === ''
            ) {
                throw new OcrServiceException(
                    message: 'OCR tidak menghasilkan teks.',
                    httpStatus: 422,
                    retryable: false,
                );
            }

            $pages = $result['pages'] ?? null;

            if (
                ! is_array($pages)
                || $pages === []
            ) {
                throw new OcrServiceException(
                    message: 'OCR tidak menghasilkan data halaman.',
                    httpStatus: 502,
                    retryable: false,
                );
            }

            $parsedResult = is_array(
                $result['document'] ?? null,
            )
                ? $result['document']
                : [];

            $parsedItems = is_array(
                $result['items'] ?? null,
            )
                ? $result['items']
                : (
                    is_array(
                        $parsedResult['items']
                        ?? null,
                    )
                        ? $parsedResult['items']
                        : []
                );

            $warnings = is_array(
                $result['warnings'] ?? null,
            )
                ? $result['warnings']
                : [];

            $parsedResult['items'] =
                $parsedItems;

            $parsedResult['warnings'] =
                $warnings;

            $isNeedsReview = false;

            if (empty($parsedItems)) {
                $isNeedsReview = true;
            }

            if (empty($parsedResult['total']['value'])) {
                $isNeedsReview = true;
            }

            if (empty($parsedResult['date']['value'])) {
                $isNeedsReview = true;
            }

            if (empty($parsedResult['store_name']['value'])) {
                $isNeedsReview = true;
            }
            
            if (empty($parsedResult['invoice_no']['value'])) {
                $isNeedsReview = true;
            }

            $overallConfidence = is_numeric($result['overall_confidence'] ?? null)
                ? (float) $result['overall_confidence']
                : 0.0;

            if ($overallConfidence < 0.65) {
                $isNeedsReview = true;
            }

            $totalValue = (float) ($parsedResult['total']['value'] ?? 0);
            $subtotalValue = (float) ($parsedResult['subtotal']['value'] ?? 0);
            $taxValue = (float) ($parsedResult['tax_amount']['value'] ?? 0);

            if ($totalValue > 0) {
                $calculatedTotal = $subtotalValue + $taxValue;
                $diffTotal = abs($totalValue - $calculatedTotal);
                if ($diffTotal > max(1.0, $totalValue * 0.05)) {
                    $isNeedsReview = true;
                }
            }

            $calculatedItemsSubtotal = 0;

            $itemCount = count($parsedItems);

            $itemAnchor = $subtotalValue > 0
                ? $subtotalValue
                : ($taxValue <= 0 ? $totalValue : 0);

            foreach ($parsedItems as $index => &$item) {
                $qty = (float) (
                    $item['qty']['value'] ?? 0
                );

                $price = (float) (
                    $item['price']['value'] ?? 0
                );

                $itemSubtotal = (float) (
                    $item['subtotal']['value'] ?? 0
                );

                $expected = (
                    $qty > 0
                    && $price > 0
                )
                    ? $qty * $price
                    : 0;

                /*
                 * Validasi dengan total dokumen sebagai sumber
                 * independen. Qty × harga dapat terlihat konsisten,
                 * tetapi tetap salah jika qty berasal dari barcode.
                 */
                $implausibleAgainstDocument = (
                    $itemAnchor > 0
                    && $expected > $itemAnchor * 5
                );

                $implausibleQuantity = (
                    $qty > 10000
                    && (
                        $itemAnchor <= 0
                        || $expected > $itemAnchor * 1.5
                    )
                );

                if (
                    $implausibleAgainstDocument
                    || $implausibleQuantity
                ) {
                    $isNeedsReview = true;

                    $warnings[] = [
                        'code' =>
                            'item_value_rejected_plausibility',

                        'field' =>
                            "items.{$index}",

                        'message' =>
                            'Qty atau harga item ditolak karena ' .
                            'menghasilkan nilai yang tidak masuk akal ' .
                            'dibanding total dokumen.',

                        'severity' =>
                            'error',
                    ];

                    /*
                     * Jika dokumen hanya memiliki satu item,
                     * total dapat digunakan sebagai anchor.
                     */
                    if (
                        $itemCount === 1
                        && $itemAnchor > 0
                    ) {
                        $item['qty'] = [
                            'value' => 1,
                            'confidence' => null,
                            'source' =>
                                'server_reconciled_total',
                        ];

                        $item['price'] = [
                            'value' => $itemAnchor,
                            'confidence' => null,
                            'source' =>
                                'server_reconciled_total',
                        ];

                        $item['subtotal'] = [
                            'value' => $itemAnchor,
                            'confidence' => null,
                            'source' =>
                                'server_reconciled_total',
                        ];

                        $calculatedItemsSubtotal +=
                            $itemAnchor;
                    } else {
                        foreach (
                            ['qty', 'price', 'subtotal']
                            as $field
                        ) {
                            $item[$field] = [
                                'value' => null,
                                'confidence' => null,
                                'source' =>
                                    'rejected_plausibility',
                            ];
                        }
                    }

                    continue;
                }

                if ($expected > 0) {
                    $diff = abs(
                        $itemSubtotal - $expected
                    );

                    if (
                        $diff >
                        max(
                            1.0,
                            $expected * 0.05
                        )
                    ) {
                        $isNeedsReview = true;
                    }
                }

                $calculatedItemsSubtotal +=
                    $itemSubtotal;
            }

            unset($item);

            /*
             * Pastikan hasil yang sudah dibersihkan
             * benar-benar disimpan ke database.
             */
            $parsedResult['items'] =
                $parsedItems;

            $parsedResult['warnings'] =
                $warnings;

            if ($subtotalValue > 0) {
                $diffItems = abs($subtotalValue - $calculatedItemsSubtotal);
                if ($diffItems > max(1.0, $subtotalValue * 0.05)) {
                    $isNeedsReview = true;
                }
            }

            $status = $isNeedsReview
                ? ReceiptDocumentStatus::NEEDS_REVIEW
                : ReceiptDocumentStatus::VERIFIED;

            $document->update([
                'status' => $status,

                'raw_text' => $rawText,

                'raw_result' => $result,

                'parsed_result' => $parsedResult,

                'overall_confidence' => $overallConfidence,

                'ocr_engine' =>
                    (string) (
                        $result['engine']
                        ?? 'paddleocr'
                    ),

                'ocr_engine_version' =>
                    isset($result['engine_version'])
                    ? (string) $result[
                        'engine_version'
                    ]
                    : null,

                'processed_at' => now(),
                'error_message' => null,
            ]);

            Log::info(
                'Dokumen berhasil diproses oleh OCR.',
                [
                    'receipt_document_id' =>
                        $document->id,

                    'attempt' =>
                        $this->attempts(),

                    'engine' =>
                        $document->ocr_engine,
                ],
            );
        } catch (Throwable $exception) {
            $this->handleProcessingFailure(
                document: $document,
                exception: $exception,
            );
        }
    }

    private function handleProcessingFailure(
        ReceiptDocument $document,
        Throwable $exception,
    ): void {
        $retryable = $exception
            instanceof OcrServiceException
            ? $exception->isRetryable()
            : true;

        $willRetry = $retryable
            && $this->attempts() < $this->tries;

        $safeMessage = $this->safeErrorMessage(
            $exception,
        );

        $document->update([
            'status' => $willRetry
                ? ReceiptDocumentStatus::QUEUED
                : ReceiptDocumentStatus::FAILED,

            'error_message' => $safeMessage,

            'processed_at' => $willRetry
                ? null
                : now(),
        ]);

        Log::warning(
            'Pemrosesan OCR gagal.',
            [
                'receipt_document_id' =>
                    $document->id,

                'attempt' =>
                    $this->attempts(),

                'maximum_attempts' =>
                    $this->tries,

                'retryable' =>
                    $retryable,

                'will_retry' =>
                    $willRetry,

                'exception_class' =>
                    $exception::class,

                'message' =>
                    $safeMessage,

                'context' =>
                    $exception instanceof OcrServiceException
                        ? $exception->getContextData()
                        : [],

                'previous_message' =>
                    $exception->getPrevious() !== null
                        ? Str::limit(
                            $exception->getPrevious()->getMessage(),
                            500,
                        )
                        : null,
            ],
        );

        if ($willRetry) {
            throw $exception;
        }

        /*
         * Error 4xx seperti file tidak terbaca tidak perlu dicoba
         * kembali sampai tiga kali.
         */
        $this->fail(
            $exception,
        );
    }

    public function failed(
        ?Throwable $exception,
    ): void {
        $document = ReceiptDocument::query()
            ->find(
                $this->receiptDocumentId,
            );

        if ($document === null) {
            return;
        }

        if (
            in_array(
                $document->status,
                [
                    ReceiptDocumentStatus::NEEDS_REVIEW,
                    ReceiptDocumentStatus::VERIFIED,
                ],
                true,
            )
        ) {
            return;
        }

        $document->update([
            'status' =>
                ReceiptDocumentStatus::FAILED,

            'error_message' =>
                $document->error_message
                ?: $this->safeErrorMessage(
                    $exception,
                ),

            'processed_at' =>
                $document->processed_at
                ?: now(),
        ]);
    }

    private function safeErrorMessage(
        ?Throwable $exception,
    ): string {
        if ($exception === null) {
            return 'Pemrosesan OCR gagal.';
        }

        $message = preg_replace(
            '/\s+/u',
            ' ',
            trim(
                $exception->getMessage(),
            ),
        );

        return Str::limit(
            $message
                ?: 'Pemrosesan OCR gagal.',
            500,
        );
    }
}
