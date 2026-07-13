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

class ReceiptDocumentController extends Controller
{
    public function index()
    {
        $docs = ReceiptDocument::with('uploader')->orderBy('created_at', 'desc')->get();
        return response()->json($docs);
    }

    public function show(ReceiptDocument $receiptDocument)
    {
        return response()->json($receiptDocument);
    }

    public function store(Request $request)
    {
        $maxSize = config('services.ocr.max_upload_size', 10240); // 10MB default

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

        $existing = ReceiptDocument::where('sha256', $sha256)->first();
        if ($existing) {
            return response()->json([
                'message' => 'Document already uploaded',
                'data' => [
                    'id' => $existing->id,
                    'status' => $existing->status->value
                ]
            ], 200);
        }

        $document = ReceiptDocument::create([
            'uploaded_by' => $request->user()?->id,
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => $sha256,
            'status' => ReceiptDocumentStatus::QUEUED,
        ]);

        ProcessReceiptOcr::dispatch($document)->onQueue('ocr');

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

    public function retry(ReceiptDocument $receiptDocument)
    {
        if (!in_array($receiptDocument->status, [ReceiptDocumentStatus::FAILED, ReceiptDocumentStatus::UPLOADED])) {
            return response()->json(['message' => 'Only failed or uploaded documents can be retried'], 400);
        }

        $receiptDocument->update([
            'status' => ReceiptDocumentStatus::QUEUED,
            'error_message' => null,
            'attempts' => $receiptDocument->attempts + 1,
        ]);

        ProcessReceiptOcr::dispatch($receiptDocument)->onQueue('ocr');

        return response()->json([
            'message' => 'Document queued for retry',
            'data' => [
                'id' => $receiptDocument->id,
                'status' => $receiptDocument->status->value
            ]
        ], 202);
    }
}
