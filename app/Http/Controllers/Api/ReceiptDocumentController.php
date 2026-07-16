<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReceiptDocument;
use App\Models\Receipt;
use App\Jobs\ProcessReceiptOcr;
use App\Enums\ReceiptDocumentStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class ReceiptDocumentController extends Controller
{
    public function index()
    {
        $docs = ReceiptDocument::with('uploader')->orderBy('created_at', 'desc')->get();
        return response()->json($docs);
    }

    public function show(
        ReceiptDocument $receiptDocument,
    ): JsonResponse {
        $data = $receiptDocument->toArray();

        $rawResult = is_array(
            $receiptDocument->raw_result,
        )
            ? $receiptDocument->raw_result
            : [];

        $parsedResult = is_array(
            $receiptDocument->parsed_result,
        )
            ? $receiptDocument->parsed_result
            : [];

        if (
            ! is_array(
                $parsedResult['items']
                ?? null,
            )
        ) {
            $parsedResult['items'] = is_array(
                $rawResult['items'] ?? null,
            )
                ? $rawResult['items']
                : [];
        }

        if (
            ! is_array(
                $parsedResult['warnings']
                ?? null,
            )
        ) {
            $parsedResult['warnings'] = is_array(
                $rawResult['warnings']
                ?? null,
            )
                ? $rawResult['warnings']
                : [];
        }

        /*
         * Pages hanya ditambahkan saat endpoint detail dipanggil.
         * Index tidak perlu mengirim seluruh bounding box.
         */
        $parsedResult['pages'] = is_array(
            $rawResult['pages'] ?? null,
        )
            ? $rawResult['pages']
            : [];

        $data['parsed_result'] =
            $parsedResult;

        return response()->json(
            $data
        );
    }

    public function store(Request $request)
    {
        $maxSize = (int) config('services.ocr.max_upload_size_kb', 10240); // 10MB default

        $request->validate([
            'document' => [
                'required',
                'file',
                'mimetypes:image/jpeg,image/png,application/pdf,image/tiff',
                'mimes:jpg,jpeg,png,pdf,tif,tiff',
                'max:' . $maxSize,
            ],
        ]);

        $file = $request->file('document');
        
        $path = $file->store('receipts', 'local');
        
        $fullPath = Storage::disk('local')->path($path);
        $sha256 = hash_file('sha256', $fullPath);

        // $existing = ReceiptDocument::where('sha256', $sha256)->first();
        // if ($existing) {
        //     return response()->json([
        //         'message' => 'Document already uploaded',
        //         'data' => [
        //             'id' => $existing->id,
        //             'status' => $existing->status->value
        //         ]
        //     ], 200);
        // }

        $document = ReceiptDocument::create([
            'uploaded_by' => $request->user()?->id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => $sha256,
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        ProcessReceiptOcr::dispatch(
            $document->id,
        );

        return response()->json([
            'message' => 'Dokumen diterima dan sedang diproses.',
            'data' => [
                'id' => $document->id,
                'status' => $document->status->value
            ]
        ], 202);
    }

    public function verify(Request $request, ReceiptDocument $receiptDocument)
    {
        if ($receiptDocument->status !== ReceiptDocumentStatus::NEEDS_REVIEW) {
            return response()->json(['message' => 'Document must be in needs_review status'], 422);
        }

        $validated = $request->validate([
            'invoiceNo' => 'required|string',
            'storeName' => 'required|string',
            'date' => 'required|date',
            'isTaxed' => 'required|boolean',
            'taxRate' => 'required|numeric|min:0|max:100',
            'method' => 'nullable|string',
            'bastName' => 'nullable|string',
            'bastDate' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['qty'] * $item['price']);
            }

            $taxAmount = $validated['isTaxed'] ? round($subtotal * ($validated['taxRate'] / 100)) : 0;
            $total = $subtotal + $taxAmount;

            $receipt = Receipt::create([
                'invoice_no' => $validated['invoiceNo'],
                'store_name' => $validated['storeName'],
                'date' => $validated['date'],
                'is_taxed' => $validated['isTaxed'],
                'tax_rate' => $validated['isTaxed'] ? $validated['taxRate'] : 0,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'is_verified' => true,
                'status' => 'Dokumen Valid',
                'method' => $validated['method'] ?? null,
                'bast_name' => $validated['bastName'] ?? null,
                'bast_date' => $validated['bastDate'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $receipt->items()->create([
                    'name' => $item['name'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'subtotal' => $item['qty'] * $item['price'],
                ]);
            }

            $receiptDocument->update([
                'receipt_id' => $receipt->id,
                'status' => ReceiptDocumentStatus::VERIFIED,
                'verified_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Verified',
                'data' => $receipt->load('items')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Verification failed', 'error' => $e->getMessage()], 500);
        }
    }

    public function retry(
        ReceiptDocument $receiptDocument,
    ) {
        $updated = ReceiptDocument::query()
            ->whereKey(
                $receiptDocument->id,
            )
            ->whereIn(
                'status',
                [
                    ReceiptDocumentStatus::FAILED->value,
                    ReceiptDocumentStatus::UPLOADED->value,
                ],
            )
            ->update([
                'status' =>
                    ReceiptDocumentStatus::QUEUED->value,

                'ocr_engine' => null,
                'ocr_engine_version' => null,
                'raw_text' => null,
                'raw_result' => null,
                'parsed_result' => null,
                'overall_confidence' => null,
                'error_message' => null,
                'processed_at' => null,
            ]);

        if ($updated === 0) {
            return response()->json([
                'message' => (
                    'Hanya dokumen berstatus failed '
                    . 'atau uploaded yang dapat diproses ulang.'
                ),
            ], 409);
        }

        ProcessReceiptOcr::dispatch(
            $receiptDocument->id,
        );

        $receiptDocument->refresh();

        return response()->json([
            'message' => (
                'Dokumen dimasukkan kembali '
                . 'ke antrean OCR.'
            ),
            'data' => [
                'id' =>
                    $receiptDocument->id,

                'status' =>
                    $receiptDocument
                        ->status
                        ->value,

                'attempts' =>
                    $receiptDocument
                        ->attempts,
            ],
        ], 202);
    }
}
