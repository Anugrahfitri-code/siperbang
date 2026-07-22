<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\InventoryCodeSuggestionService;
use App\Services\ReceiptExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReceiptController extends Controller
{
    public function index()
    {
        return response()->json(Receipt::with('items.inventoryCodeMaster')->orderBy('created_at', 'desc')->get());
    }

    public function store(
        Request $request,
        InventoryCodeSuggestionService $suggestionService,
    ) {
        $validated = $request->validate([
            'invoiceNo' => 'required|string|max:255',
            'storeName' => 'required|string|max:255',
            'date' => 'required|date',
            'isTaxed' => 'required|boolean',
            'taxRate' => 'required|numeric|min:0|max:100',
            'method' => 'nullable|string|max:255',
            'bastName' => 'nullable|string|max:255',
            'bastDate' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:500',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.unit' => 'required|string|max:30',
            'items.*.inventoryCode' => [
                'required',
                'string',
                'regex:/^10103\d{5}$/',
                Rule::exists('kode_persediaan', 'kode')
                    ->where(
                        fn ($query) => $query
                            ->where('kode', 'like', '10103%')
                    ),
            ],
            'items.*.price' => 'required|numeric|gt:0',
        ]);

        $items = collect($validated['items'])
            ->map(function (array $item) use ($suggestionService): array {
                $qty = (int) $item['qty'];
                $price = round((float) $item['price'], 2);

                return [
                    'name' => trim($item['name']),
                    'qty' => $qty,
                    'unit' => $suggestionService->normaliseUnit(
                        $item['unit'],
                    ),
                    'inventory_code' => preg_replace(
                        '/\D/',
                        '',
                        $item['inventoryCode'],
                    ),
                    'price' => $price,
                    'subtotal' => round($qty * $price, 2),
                ];
            })
            ->values();

        $subtotal = round((float) $items->sum('subtotal'), 2);
        $taxRate = $validated['isTaxed']
            ? round((float) $validated['taxRate'], 2)
            : 0.0;
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total = round($subtotal + $taxAmount, 2);

        $receipt = DB::transaction(function () use (
            $validated,
            $items,
            $subtotal,
            $taxRate,
            $taxAmount,
            $total,
        ): Receipt {
            $receipt = Receipt::create([
                'invoice_no' => trim($validated['invoiceNo']),
                'store_name' => trim($validated['storeName']),
                'date' => $validated['date'],
                'is_taxed' => (bool) $validated['isTaxed'],
                'tax_rate' => $taxRate,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'is_verified' => false,
                'status' => 'Menunggu Verifikasi',
                'method' => $validated['method'] ?? null,
                'bast_name' => $validated['bastName'] ?? null,
                'bast_date' => $validated['bastDate'] ?? null,
            ]);

            foreach ($items as $item) {
                $receipt->items()->create($item);
            }

            return $receipt;
        });

        return response()->json(
            $receipt->load('items.inventoryCodeMaster'),
            201,
        );
    }

    public function exportExcel(
        Request $request,
        ReceiptExcelExportService $exportService,
    ): BinaryFileResponse {
        $validated = $request->validate([
            'receipt_ids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'receipt_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('receipts', 'id'),
            ],
        ]);

        $requestedIds = collect(
            $validated['receipt_ids']
        )
            ->map(
                fn (mixed $id): int =>
                    (int) $id
            )
            ->values();

        /*
         * Ambil kuitansi dan barang dalam satu proses query.
         */
        $receiptsById = Receipt::query()
            ->with('items')
            ->whereIn(
                'id',
                $requestedIds
            )
            ->get()
            ->keyBy(
                fn (Receipt $receipt): int =>
                    (int) $receipt->id
            );

        /*
         * Susunan kuitansi pada workbook mengikuti urutan ID
         * yang dikirim oleh frontend.
         */
        $receipts = $requestedIds
            ->map(
                fn (int $id): ?Receipt =>
                    $receiptsById->get($id)
            );

        /*
         * Antisipasi apabila kuitansi terhapus setelah proses
         * validasi tetapi sebelum query selesai.
         */
        if ($receipts->contains(null)) {
            throw ValidationException::withMessages([
                'receipt_ids' => [
                    'Salah satu kuitansi sudah tidak tersedia.',
                ],
            ]);
        }

        /*
         * Kuitansi draft atau belum diverifikasi tidak boleh
         * masuk ke file pembukuan.
         */
        if (
            $receipts->contains(
                fn (Receipt $receipt): bool =>
                    ! $receipt->is_verified
            )
        ) {
            throw ValidationException::withMessages([
                'receipt_ids' => [
                    'Hanya kuitansi yang sudah valid '
                    . 'yang dapat diekspor.',
                ],
            ]);
        }

        /*
         * Jangan pernah menghasilkan file Excel dengan kolom
         * kode persediaan atau satuan yang kosong. Jika masih
         * ada data lama yang belum lengkap, pengguna diarahkan
         * untuk memperbaruinya terlebih dahulu.
         */
        $incompleteReceipt = $receipts->first(
            function (Receipt $receipt): bool {
                return $receipt->items->contains(
                    fn ($item): bool =>
                        blank($item->inventory_code)
                        || blank($item->unit)
                );
            }
        );

        if ($incompleteReceipt instanceof Receipt) {
            $receiptLabel = trim(
                (string) $incompleteReceipt->invoice_no
            );

            throw ValidationException::withMessages([
                'receipt_ids' => [
                    'Kuitansi '
                    . ($receiptLabel !== ''
                        ? $receiptLabel
                        : '#' . $incompleteReceipt->id)
                    . ' masih memiliki kode persediaan atau '
                    . 'satuan yang kosong. Buka Edit Kode, '
                    . 'lengkapi datanya, lalu ekspor kembali.',
                ],
            ]);
        }

        /**
         * @var \Illuminate\Support\Collection<int, Receipt>
         *     $receipts
         */
        $export = $exportService->create(
            $receipts
        );

        return response()
            ->download(
                $export['path'],
                $export['filename'],
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',

                    'Cache-Control' =>
                        'private, no-store, no-cache, '
                        . 'must-revalidate, max-age=0',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    public function updateItems(
        Request $request,
        Receipt $receipt,
    ) {
        $validated = $request->validate([
            'items'                    => 'required|array|min:1',
            'items.*.id'               => 'required|integer',
            'items.*.inventory_code'   => [
                'required',
                'string',
                'regex:/^10103\d{5}$/',
                Rule::exists('kode_persediaan', 'kode')
                    ->where(
                        fn ($query) => $query
                            ->where('kode', 'like', '10103%')
                    ),
            ],
            'items.*.unit' => 'required|string|max:30',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($receipt, $validated) {
            foreach ($validated['items'] as $itemData) {
                $receipt->items()
                    ->where('id', $itemData['id'])
                    ->update([
                        'inventory_code' => preg_replace('/\D/', '', $itemData['inventory_code']),
                        'unit'           => trim($itemData['unit']),
                    ]);
            }
        });

        return response()->json([
            'message' => 'Kode persediaan berhasil diperbarui.',
            'data'    => $receipt->load('items.inventoryCodeMaster'),
        ]);
    }

    public function unverify(\App\Models\Receipt $receipt)

    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($receipt) {
                // Cari ReceiptDocument yang terhubung, jika ada
                $document = \App\Models\ReceiptDocument::where('receipt_id', $receipt->id)->first();
                if ($document) {
                    $document->update([
                        'receipt_id' => null,
                        'status' => \App\Enums\ReceiptDocumentStatus::NEEDS_REVIEW,
                    ]);
                }

                $receipt->delete();

                \App\Models\HistoryLog::create([
                    'actor' => auth()->user()->name . ' (' . auth()->user()->role . ')',
                    'action' => 'Batalkan Verifikasi Kuitansi',
                    'details' => 'Petugas membatalkan verifikasi kuitansi ' . ($receipt->invoice_no ?? 'tanpa nomor') . ' dari ' . ($receipt->store_name ?? '-') . '.',
                ]);
            });

            return response()->json(['message' => 'Verifikasi kuitansi berhasil dibatalkan.']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gagal membatalkan kuitansi', ['id' => $receipt->id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan sistem saat membatalkan kuitansi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
