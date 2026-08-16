<?php

namespace App\Services\Inventory;

use App\Exceptions\SafeBusinessException;
use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\HistoryLog;
use App\Models\StockHistory;
use App\Models\StokUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StokFinalizationService
{
    protected $kodeService;

    public function __construct(KodePersediaanService $kodeService)
    {
        $this->kodeService = $kodeService;
    }

    /**
     * Finalize approved stock items from a batch into master stock table.
     */
    public function finalize(StokUpload $batch): array
    {
        $user = Auth::user();
        $actorName = $user ? $user->name : 'Petugas Persediaan';
        $userId = $user ? $user->id : 1;

        $results = [
            'inserted' => 0,
            'updated' => 0,
            'details' => [],
        ];

        DB::transaction(function () use ($batch, $actorName, $userId, &$results) {
            // 1. Lock the parent batch to serialize with apiSaveVerifikasi and other finalizations
            $lockedBatch = StokUpload::lockForUpdate()->findOrFail($batch->id);

            // 2. Exact source state verification
            if ($lockedBatch->status !== StokUpload::STATUS_SIAP_DIFINALISASI) {
                if ($lockedBatch->status === StokUpload::STATUS_SELESAI) {
                    throw new SafeBusinessException('Batch upload ini sudah pernah difinalisasi.');
                }
                throw new SafeBusinessException('Batch tidak dapat difinalisasi karena status saat ini adalah: ' . $lockedBatch->status);
            }

            // 3. Load approved rows AFTER lock to ensure accurate state
            $approvedRows = $lockedBatch->details()->where('status_verification', 'Setuju')->get();

            if ($approvedRows->isEmpty()) {
                throw new SafeBusinessException('Tidak ada data yang disetujui untuk difinalisasi. Silakan lakukan verifikasi terlebih dahulu.');
            }

            // 4. Deterministic coordination: group and sort by code and lowercase name
            $grouped = [];
            foreach ($approvedRows as $row) {
                $code = $row->verified_kode_persediaan;
                $normalizedName = strtolower(trim($row->nama_barang));
                $key = $code . '|' . $normalizedName;

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'code' => $code,
                        'name' => trim($row->nama_barang),
                        'qty' => 0,
                        'unit' => $row->unit,
                        'storage_location' => $row->storage_location,
                        'sheet_name' => $row->sheet_name,
                    ];
                }
                $grouped[$key]['qty'] += $row->qty;
            }

            ksort($grouped);

            // 5. Process grouped items with correct deterministic locks
            foreach ($grouped as $logicalItem) {
                $code = $logicalItem['code'];

                // Master row coordination for missing-row creation
                DB::table('kode_persediaan')->where('kode', $code)->lockForUpdate()->first();

                // Existing stock row coordination
                $barang = Barang::where('code', $code)
                    ->whereRaw('LOWER(name) = LOWER(?)', [$logicalItem['name']])
                    ->lockForUpdate()
                    ->first();

                if ($barang) {
                    $qtyBefore = $barang->qty;
                    $qtyAfter = $qtyBefore + $logicalItem['qty'];

                    $barang->update([
                        'qty' => $qtyAfter,
                        'last_updated' => now(),
                        'last_upload_id' => $lockedBatch->id,
                        'storage_location' => $logicalItem['storage_location'] ?? $barang->storage_location,
                    ]);

                    StockHistory::create([
                        'stock_item_id' => $barang->id,
                        'stok_upload_id' => $lockedBatch->id,
                        'qty_change' => $logicalItem['qty'],
                        'qty_before' => $qtyBefore,
                        'qty_after' => $qtyAfter,
                        'type' => 'Upload Excel',
                        'notes' => "Penambahan stok dari batch #{$lockedBatch->id} (Sheet: {$logicalItem['sheet_name']})",
                    ]);

                    $results['updated']++;
                    $results['details'][] = [
                        'action' => 'update',
                        'name' => $barang->name,
                        'code' => $code,
                        'unit' => $barang->unit,
                        'qty_before' => $qtyBefore,
                        'qty_added' => $logicalItem['qty'],
                        'qty_after' => $qtyAfter,
                    ];
                } else {
                    $category = $this->kodeService->getCategoryByCode($code);

                    $newBarang = Barang::create([
                        'code' => $code,
                        'name' => $logicalItem['name'],
                        'category' => $category,
                        'qty' => $logicalItem['qty'],
                        'unit' => $logicalItem['unit'],
                        'storage_location' => $logicalItem['storage_location'],
                        'last_updated' => now(),
                        'is_active' => true,
                        'last_upload_id' => $lockedBatch->id,
                    ]);

                    StockHistory::create([
                        'stock_item_id' => $newBarang->id,
                        'stok_upload_id' => $lockedBatch->id,
                        'qty_change' => $logicalItem['qty'],
                        'qty_before' => 0,
                        'qty_after' => $logicalItem['qty'],
                        'type' => 'Upload Excel',
                        'notes' => "Stok awal dari batch #{$lockedBatch->id} (Sheet: {$logicalItem['sheet_name']})",
                    ]);

                    $results['inserted']++;
                    $results['details'][] = [
                        'action' => 'insert',
                        'name' => $newBarang->name,
                        'code' => $code,
                        'unit' => $newBarang->unit,
                        'qty' => $logicalItem['qty'],
                    ];
                }
            }

            // 6. Update batch state
            $rejectedCount = $lockedBatch->details()->where('status_verification', 'Tolak')->count();

            $lockedBatch->update([
                'rejected_rows_count' => $rejectedCount,
                'status' => StokUpload::STATUS_SELESAI,
            ]);

            // Build per-item detail for log
            $itemDetails = collect($results['details'])->map(function ($d) {
                $label = $d['action'] === 'insert'
                    ? "[BARU] {$d['name']} (Kode: {$d['code']}) → Stok: {$d['qty']} {$d['unit']}"
                    : "[UPDATE] {$d['name']} (Kode: {$d['code']}) → Stok: {$d['qty_before']} + {$d['qty_added']} = {$d['qty_after']} {$d['unit']}";

                return $label;
            })->implode("\n");

            $summary = "Finalisasi Batch #{$lockedBatch->id} oleh {$actorName}. "
                ."Barang baru: {$results['inserted']}, Stok diperbarui: {$results['updated']}.\n"
                ."Detail:\n{$itemDetails}";

            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();

            AuditLog::create([
                'user_id' => $userId,
                'action' => 'FINALISASI_STOK',
                'description' => $summary,
                'ip_address' => $ipAddress,
            ]);

            HistoryLog::create([
                'actor' => $actorName,
                'user_id' => $userId,
                'action' => 'Finalisasi Stok Excel',
                'details' => $summary,
                'ip_address' => $ipAddress,
                'metadata' => [
                    'batch_id' => $lockedBatch->id,
                    'inserted' => $results['inserted'],
                    'updated' => $results['updated'],
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    'items' => $results['details'],
                    'waktu_aksi' => now()->toIso8601String(),
                ],
            ]);
        });

        return $results;
    }
}
