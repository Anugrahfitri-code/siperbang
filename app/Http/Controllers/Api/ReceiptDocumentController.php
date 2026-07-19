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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ReceiptDocumentController extends Controller
{
    public function index(
        Request $request,
    ): JsonResponse {
        $query = ReceiptDocument::query()
            ->with('uploader:id,name')
            ->orderByDesc('created_at');

        if (
            $request->query('scope')
            === 'pending'
        ) {
            $query
                ->whereNull('receipt_id')
                ->whereIn(
                    'status',
                    [
                        ReceiptDocumentStatus
                            ::NEEDS_REVIEW
                            ->value,

                        ReceiptDocumentStatus
                            ::DRAFT
                            ->value,

                        /*
                         * Kompatibilitas untuk hasil OCR lama
                         * yang berstatus verified tetapi belum
                         * mempunyai receipt_id.
                         */
                        ReceiptDocumentStatus
                            ::VERIFIED
                            ->value,
                    ],
                );
        }

        $documents = $query
            ->get()
            ->map(
                function (
                    ReceiptDocument $document
                ) {
                    $manualDraft = is_array(
                        $document->manual_draft
                    )
                        ? $document->manual_draft
                        : [];

                    $parsedResult = is_array(
                        $document->parsed_result
                    )
                        ? $document->parsed_result
                        : [];

                    $fieldValue = static function (
                        array $source,
                        string $key,
                        mixed $fallback = null,
                    ): mixed {
                        $field = (
                            $source[$key]
                            ?? null
                        );

                        if (
                            is_array($field)
                            && array_key_exists(
                                'value',
                                $field,
                            )
                        ) {
                            return $field['value'];
                        }

                        return $fallback;
                    };

                    return [
                        'id' =>
                            $document->id,

                        'receipt_id' =>
                            $document->receipt_id,

                        'original_filename' =>
                            $document
                                ->original_filename,

                        'mime_type' =>
                            $document->mime_type,

                        'size_bytes' =>
                            $document->size_bytes,

                        'status' =>
                            $document
                                ->status
                                ->value,

                        'manual_draft' =>
                            $document
                                ->manual_draft,

                        'summary' => [
                            'invoiceNo' =>
                                $manualDraft[
                                    'invoiceNo'
                                ]
                                ?? $fieldValue(
                                    $parsedResult,
                                    'invoice_no',
                                    '',
                                ),

                            'storeName' =>
                                $manualDraft[
                                    'storeName'
                                ]
                                ?? $fieldValue(
                                    $parsedResult,
                                    'store_name',
                                    '',
                                ),

                            'date' =>
                                $manualDraft[
                                    'date'
                                ]
                                ?? $fieldValue(
                                    $parsedResult,
                                    'date',
                                    '',
                                ),

                            'method' =>
                                $manualDraft[
                                    'method'
                                ]
                                ?? null,

                            'isTaxed' =>
                                (bool) (
                                    $manualDraft[
                                        'isTaxed'
                                    ]
                                    ?? false
                                ),

                            'taxRate' =>
                                (float) (
                                    $manualDraft[
                                        'taxRate'
                                    ]
                                    ?? 0
                                ),

                            'total' =>
                                (float) (
                                    $manualDraft[
                                        'total'
                                    ]
                                    ?? $fieldValue(
                                        $parsedResult,
                                        'total',
                                        0,
                                    )
                                ),
                        ],

                        'draft_saved_at' =>
                            $document
                                ->draft_saved_at
                                ?->toISOString(),

                        'processed_at' =>
                            $document
                                ->processed_at
                                ?->toISOString(),

                        'created_at' =>
                            $document
                                ->created_at
                                ?->toISOString(),

                        'updated_at' =>
                            $document
                                ->updated_at
                                ?->toISOString(),
                    ];
                }
            );

        return response()->json(
            $documents
        );
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

    public function file(
        ReceiptDocument $receiptDocument,
    ) {
        $disk = Storage::disk('local');

        if (
            ! $disk->exists(
                $receiptDocument->storage_path
            )
        ) {
            abort(
                404,
                'File dokumen tidak ditemukan.'
            );
        }

        return response()->file(
            $disk->path(
                $receiptDocument->storage_path
            ),
            [
                'Content-Type' =>
                    $receiptDocument->mime_type,

                'Cache-Control' =>
                    'private, no-store, max-age=0',
            ],
        );
    }

    public function saveDraft(
        Request $request,
        ReceiptDocument $receiptDocument,
    ): JsonResponse {
        if (
            $receiptDocument->receipt_id
            !== null
        ) {
            return response()->json([
                'message' =>
                    'Dokumen ini sudah selesai '
                    . 'diverifikasi dan tidak dapat '
                    . 'disimpan kembali sebagai draft.',
            ], 409);
        }

        if (
            ! in_array(
                $receiptDocument->status,
                [
                    ReceiptDocumentStatus
                        ::NEEDS_REVIEW,

                    ReceiptDocumentStatus
                        ::DRAFT,

                    ReceiptDocumentStatus
                        ::VERIFIED,
                ],
                true,
            )
        ) {
            return response()->json([
                'message' =>
                    'Dokumen belum siap untuk '
                    . 'disimpan sebagai draft.',
            ], 422);
        }

        $validated = $request->validate([
            'invoiceNo' =>
                'nullable|string|max:255',

            'storeName' =>
                'nullable|string|max:255',

            'date' =>
                'nullable|date',

            'isTaxed' =>
                'required|boolean',

            'taxRate' =>
                'required|numeric|min:0|max:100',

            'method' =>
                'nullable|string|max:255',

            'bastName' =>
                'nullable|string|max:255',

            'bastDate' =>
                'nullable|date',

            'items' =>
                'present|array',

            'items.*.name' =>
                'nullable|string|max:500',

            'items.*.qty' =>
                'nullable|numeric|min:0',

            'items.*.price' =>
                'nullable|numeric|min:0',
        ]);

        $draftItems = collect(
            $validated['items']
            ?? []
        )
            ->map(
                function (
                    array $item
                ) {
                    $qty = is_numeric(
                        $item['qty']
                        ?? null
                    )
                        ? max(
                            0,
                            (float) $item['qty'],
                        )
                        : 0;

                    $price = is_numeric(
                        $item['price']
                        ?? null
                    )
                        ? max(
                            0,
                            round(
                                (float) $item[
                                    'price'
                                ],
                                2,
                            ),
                        )
                        : 0;

                    return [
                        'name' =>
                            trim(
                                (string) (
                                    $item['name']
                                    ?? ''
                                )
                            ),

                        'qty' =>
                            $qty,

                        'price' =>
                            $price,

                        'subtotal' =>
                            round(
                                $qty * $price,
                                2,
                            ),
                    ];
                }
            )
            ->values()
            ->all();

        $subtotal = round(
            array_sum(
                array_column(
                    $draftItems,
                    'subtotal',
                )
            ),
            2,
        );

        $isTaxed = (bool) (
            $validated['isTaxed']
        );

        $taxRate = $isTaxed
            ? round(
                (float) $validated[
                    'taxRate'
                ],
                2,
            )
            : 0;

        $taxAmount = $isTaxed
            ? round(
                $subtotal
                * (
                    $taxRate
                    / 100
                ),
                2,
            )
            : 0;

        $draft = [
            'invoiceNo' =>
                trim(
                    (string) (
                        $validated[
                            'invoiceNo'
                        ]
                        ?? ''
                    )
                ),

            'storeName' =>
                trim(
                    (string) (
                        $validated[
                            'storeName'
                        ]
                        ?? ''
                    )
                ),

            'date' =>
                $validated['date']
                ?? null,

            'isTaxed' =>
                $isTaxed,

            'taxRate' =>
                $taxRate,

            'subtotal' =>
                $subtotal,

            'taxAmount' =>
                $taxAmount,

            'total' =>
                round(
                    $subtotal
                    + $taxAmount,
                    2,
                ),

            'method' =>
                $validated['method']
                ?? null,

            'bastName' =>
                trim(
                    (string) (
                        $validated[
                            'bastName'
                        ]
                        ?? ''
                    )
                ),

            'bastDate' =>
                $validated[
                    'bastDate'
                ]
                ?? null,

            'items' =>
                $draftItems,
        ];

        $receiptDocument->update([
            'manual_draft' =>
                $draft,

            'draft_saved_by' =>
                $request->user()?->id,

            'draft_saved_at' =>
                now(),

            'status' =>
                ReceiptDocumentStatus
                    ::DRAFT,
        ]);

        return response()->json([
            'message' =>
                'Draft verifikasi berhasil disimpan.',

            'data' => [
                'document_id' =>
                    $receiptDocument->id,

                'status' =>
                    ReceiptDocumentStatus
                        ::DRAFT
                        ->value,

                'manual_draft' =>
                    $draft,
            ],
        ]);
    }

    public function verify(
        Request $request,
        ReceiptDocument $receiptDocument,
    ): JsonResponse {
        /*
         * Idempotensi: klik dua kali tidak membuat
         * kuitansi ganda.
         */
        if (
            $receiptDocument->receipt_id
            !== null
        ) {
            return response()->json([
                'message' =>
                    'Dokumen ini sudah pernah '
                    . 'diverifikasi.',

                'data' => [
                    'receipt' =>
                        $receiptDocument
                            ->receipt()
                            ->with('items')
                            ->first(),

                    'document' =>
                        $receiptDocument,

                    'reused' =>
                        true,
                ],
            ]);
        }

        if (
            ! in_array(
                $receiptDocument->status,
                [
                    ReceiptDocumentStatus
                        ::NEEDS_REVIEW,

                    ReceiptDocumentStatus
                        ::DRAFT,

                    ReceiptDocumentStatus
                        ::VERIFIED,
                ],
                true,
            )
        ) {
            return response()->json([
                'message' =>
                    'Dokumen harus selesai '
                    . 'diproses OCR sebelum '
                    . 'dapat diverifikasi.',
            ], 422);
        }

        $validated = $request->validate(
            [
                /*
                 * Beberapa kuitansi memang tidak
                 * mempunyai nomor nota.
                 */
                'invoiceNo' =>
                    'nullable|string|max:255',

                'storeName' =>
                    'required|string|max:255',

                'date' =>
                    'required|date',

                'isTaxed' =>
                    'required|boolean',

                'taxRate' =>
                    'required|numeric|min:0|max:100',

                'method' =>
                    'nullable|string|max:255',

                'bastName' =>
                    'nullable|string|max:255',

                'bastDate' =>
                    'nullable|date',

                'items' =>
                    'required|array|min:1',

                'items.*.name' =>
                    'required|string|max:500',

                'items.*.qty' =>
                    'required|integer|min:1',

                'items.*.price' =>
                    'required|numeric|gt:0',
            ],
            [
                'storeName.required' =>
                    'Nama toko/penyedia wajib diisi.',

                'date.required' =>
                    'Tanggal kuitansi wajib diisi.',

                'date.date' =>
                    'Tanggal kuitansi tidak valid.',

                'items.required' =>
                    'Minimal satu barang wajib diisi.',

                'items.min' =>
                    'Minimal satu barang wajib diisi.',

                'items.*.name.required' =>
                    'Nama setiap barang wajib diisi.',

                'items.*.qty.integer' =>
                    'Jumlah barang harus berupa '
                    . 'bilangan bulat.',

                'items.*.qty.min' =>
                    'Jumlah barang minimal 1.',

                'items.*.price.gt' =>
                    'Harga setiap barang harus '
                    . 'lebih besar dari 0.',
            ],
        );

        $normalisedItems = collect(
            $validated['items']
        )
            ->map(
                function (
                    array $item
                ) {
                    return [
                        'name' =>
                            trim(
                                $item['name']
                            ),

                        'qty' =>
                            (int) $item['qty'],

                        'price' =>
                            round(
                                (float) $item[
                                    'price'
                                ],
                                2,
                            ),
                    ];
                }
            )
            ->values()
            ->all();

        $subtotal = round(
            array_reduce(
                $normalisedItems,
                function (
                    float $total,
                    array $item,
                ): float {
                    return (
                        $total
                        + (
                            $item['qty']
                            * $item['price']
                        )
                    );
                },
                0,
            ),
            2,
        );

        $taxRate = (
            $validated['isTaxed']
        )
            ? round(
                (float) $validated[
                    'taxRate'
                ],
                2,
            )
            : 0;

        $taxAmount = (
            $validated['isTaxed']
        )
            ? round(
                $subtotal
                * (
                    $taxRate
                    / 100
                ),
                2,
            )
            : 0;

        $total = round(
            $subtotal
            + $taxAmount,
            2,
        );

        $storeName = trim(
            $validated['storeName']
        );

        $invoiceNo = trim(
            (string) (
                $validated['invoiceNo']
                ?? ''
            )
        );

        /*
         * Kuitansi tanpa nomor tetap dapat disimpan.
         */
        if ($invoiceNo === '') {
            $invoiceNo = (
                'DOC-'
                . $receiptDocument->id
            );
        }

        try {
            $result = DB::transaction(
                function () use (
                    $receiptDocument,
                    $validated,
                    $normalisedItems,
                    $subtotal,
                    $taxRate,
                    $taxAmount,
                    $total,
                    $storeName,
                    $invoiceNo,
                ) {
                    $lockedDocument = (
                        ReceiptDocument::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $receiptDocument->id
                            )
                    );

                    if (
                        $lockedDocument
                            ->receipt_id
                        !== null
                    ) {
                        return [
                            'receipt' =>
                                $lockedDocument
                                    ->receipt()
                                    ->with('items')
                                    ->first(),

                            'document' =>
                                $lockedDocument,

                            'reused' =>
                                true,
                        ];
                    }

                    /*
                     * Jika file yang sama diunggah ulang,
                     * jangan membuat transaksi ganda.
                     */
                    $duplicate = (
                        Receipt::query()
                            ->with('items')
                            ->where(
                                'invoice_no',
                                $invoiceNo,
                            )
                            ->whereRaw(
                                'LOWER(store_name) = ?',
                                [
                                    mb_strtolower(
                                        $storeName
                                    ),
                                ],
                            )
                            ->first()
                    );

                    if ($duplicate !== null) {
                        $sameDate = (
                            $duplicate
                                ->date
                                ?->format('Y-m-d')
                            === $validated['date']
                        );

                        $sameTotal = (
                            abs(
                                (float) $duplicate
                                    ->total
                                - $total
                            )
                            <= 0.01
                        );

                        if (
                            ! $sameDate
                            || ! $sameTotal
                        ) {
                            throw ValidationException
                                ::withMessages([
                                    'invoiceNo' => [
                                        'Nomor invoice ini sudah '
                                        . 'digunakan untuk toko '
                                        . 'yang sama, tetapi tanggal '
                                        . 'atau totalnya berbeda.',
                                    ],
                                ]);
                        }

                        $lockedDocument->update([
                            'receipt_id' =>
                                $duplicate->id,

                            'status' =>
                                ReceiptDocumentStatus
                                    ::VERIFIED,

                            'verified_at' =>
                                now(),
                        ]);

                        return [
                            'receipt' =>
                                $duplicate,

                            'document' =>
                                $lockedDocument
                                    ->fresh(),

                            'reused' =>
                                true,
                        ];
                    }

                    $receipt = Receipt::create([
                        'invoice_no' =>
                            $invoiceNo,

                        'store_name' =>
                            $storeName,

                        'date' =>
                            $validated['date'],

                        'is_taxed' =>
                            $validated['isTaxed'],

                        'tax_rate' =>
                            $taxRate,

                        'subtotal' =>
                            $subtotal,

                        'tax_amount' =>
                            $taxAmount,

                        'total' =>
                            $total,

                        'is_verified' =>
                            true,

                        'status' =>
                            'Dokumen Valid',

                        'method' =>
                            $validated['method']
                            ?? null,

                        'bast_name' =>
                            $validated['bastName']
                            ?? null,

                        'bast_date' =>
                            $validated['bastDate']
                            ?? null,
                    ]);

                    foreach (
                        $normalisedItems
                        as $item
                    ) {
                        $receipt
                            ->items()
                            ->create([
                                'name' =>
                                    $item['name'],

                                'qty' =>
                                    $item['qty'],

                                'price' =>
                                    $item['price'],

                                'subtotal' =>
                                    round(
                                        $item['qty']
                                        * $item['price'],
                                        2,
                                    ),
                            ]);
                    }

                    $lockedDocument->update([
                        'receipt_id' =>
                            $receipt->id,

                        'status' =>
                            ReceiptDocumentStatus
                                ::VERIFIED,

                        'verified_at' =>
                            now(),
                    ]);

                    return [
                        'receipt' =>
                            $receipt
                                ->load('items'),

                        'document' =>
                            $lockedDocument
                                ->fresh(),

                        'reused' =>
                            false,
                    ];
                },
                3,
            );

            return response()->json([
                'message' =>
                    $result['reused']
                        ? (
                            'Invoice sudah pernah '
                            . 'diverifikasi. Dokumen '
                            . 'dihubungkan ke data '
                            . 'yang sudah ada.'
                        )
                        : (
                            'Dokumen berhasil '
                            . 'diverifikasi.'
                        ),

                'data' =>
                    $result,
            ]);
        } catch (
            ValidationException $exception
        ) {
            throw $exception;
        } catch (
            QueryException $exception
        ) {
            Log::error(
                'Gagal menyimpan verifikasi kuitansi.',
                [
                    'receipt_document_id' =>
                        $receiptDocument->id,

                    'sql_state' =>
                        $exception->errorInfo[0]
                        ?? null,

                    'driver_code' =>
                        $exception->errorInfo[1]
                        ?? null,

                    'message' =>
                        $exception->getMessage(),
                ],
            );

            $isDuplicate = (
                (
                    $exception->errorInfo[0]
                    ?? null
                )
                === '23000'
            );

            return response()->json([
                'message' =>
                    $isDuplicate
                        ? (
                            'Nomor invoice tersebut '
                            . 'sudah digunakan. Periksa '
                            . 'daftar kuitansi valid.'
                        )
                        : (
                            'Database gagal menyimpan '
                            . 'hasil verifikasi.'
                        ),

                'code' =>
                    $isDuplicate
                        ? 'duplicate_invoice'
                        : 'database_error',
            ], $isDuplicate ? 409 : 500);
        } catch (
            \Throwable $exception
        ) {
            Log::error(
                'Gagal menyimpan verifikasi kuitansi.',
                [
                    'receipt_document_id' =>
                        $receiptDocument->id,

                    'exception' =>
                        $exception,
                ],
            );

            return response()->json([
                'message' =>
                    'Verifikasi gagal disimpan. '
                    . 'Periksa log Laravel untuk '
                    . 'detail teknis.',

                'code' =>
                    'verification_failed',

                'error' =>
                    config('app.debug')
                        ? $exception->getMessage()
                        : null,
            ], 500);
        }
    }

    public function unverify(
        ReceiptDocument $receiptDocument,
    ): JsonResponse {
        if ($receiptDocument->status !== ReceiptDocumentStatus::VERIFIED || !$receiptDocument->receipt_id) {
            return response()->json([
                'message' => 'Dokumen ini belum diverifikasi atau tidak memiliki kuitansi.',
            ], 400);
        }

        try {
            DB::transaction(function () use ($receiptDocument) {
                $receiptId = $receiptDocument->receipt_id;
                
                // Hapus kuitansi (cascade akan menghapus receipt_items jika diset di DB)
                \App\Models\Receipt::where('id', $receiptId)->delete();

                // Kembalikan status dokumen
                $receiptDocument->update([
                    'receipt_id' => null,
                    'status' => ReceiptDocumentStatus::NEEDS_REVIEW,
                ]);

                \App\Models\HistoryLog::create([
                    'actor' => auth()->user()->name . ' (' . auth()->user()->role . ')',
                    'action' => 'Batalkan Verifikasi Kuitansi',
                    'details' => 'Petugas membatalkan verifikasi dokumen OCR.',
                ]);
            });

            return response()->json([
                'message' => 'Verifikasi kuitansi berhasil dibatalkan. Dokumen dikembalikan ke status draft.',
                'data' => [
                    'document' => $receiptDocument,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membatalkan verifikasi kuitansi', ['id' => $receiptDocument->id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Terjadi kesalahan sistem saat membatalkan verifikasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
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
    public function destroy(ReceiptDocument $receiptDocument)
    {
        if ($receiptDocument->status === ReceiptDocumentStatus::VERIFIED) {
            return response()->json([
                'message' => 'Dokumen yang sudah diverifikasi tidak dapat dihapus.',
            ], 403);
        }

        // Delete physical file if exists
        if ($receiptDocument->storage_path && Storage::disk('local')->exists($receiptDocument->storage_path)) {
            Storage::disk('local')->delete($receiptDocument->storage_path);
        }

        $receiptDocument->delete();

        return response()->json([
            'message' => 'Draft dokumen berhasil dihapus.',
        ]);
    }
}

