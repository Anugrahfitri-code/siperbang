<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\StokUpload;
use App\Models\StockHistory;
use App\Models\AuditLog;
use App\Models\HistoryLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        if ($batch->status === 'Selesai') {
            throw new \Exception("Batch upload ini sudah pernah difinalisasi.");
        }

        $approvedRows = $batch->details()->where('status_verification', 'Setuju')->get();
        
        if ($approvedRows->isEmpty()) {
            throw new \Exception("Tidak ada data yang disetujui untuk difinalisasi. Silakan lakukan verifikasi terlebih dahulu.");
        }

        $user = Auth::user();
        $actorName = $user ? $user->name : 'Petugas Persediaan';
        $userId = $user ? $user->id : 1;

        $results = [
            'inserted' => 0,
            'updated' => 0,
            'details' => [],
        ];

        DB::transaction(function () use ($batch, $approvedRows, $actorName, $userId, &$results) {
            foreach ($approvedRows as $row) {
                $code = $row->verified_kode_persediaan;
                
                // Lookup item by code in stock_items table
                $barang = Barang::where('code', $code)->first();

                if ($barang) {
                    // Update existing item stock
                    $qtyBefore = $barang->qty;
                    $qtyAfter = $qtyBefore + $row->qty;

                    $barang->update([
                        'qty' => $qtyAfter,
                        'last_updated' => now(),
                        'last_upload_id' => $batch->id,
                    ]);

                    // Record history log
                    StockHistory::create([
                        'stock_item_id' => $barang->id,
                        'stok_upload_id' => $batch->id,
                        'qty_change' => $row->qty,
                        'qty_before' => $qtyBefore,
                        'qty_after' => $qtyAfter,
                        'type' => 'Upload Excel',
                        'notes' => "Penambahan stok dari batch #{$batch->id} (Sheet: {$row->sheet_name})",
                    ]);

                    $results['updated']++;
                } else {
                    // Create new item in stock_items
                    $category = $this->kodeService->getCategoryByCode($code);

                    $newBarang = Barang::create([
                        'code' => $code,
                        'name' => $row->nama_barang,
                        'category' => $category,
                        'qty' => $row->qty,
                        'unit' => $row->unit,
                        'last_updated' => now(),
                        'is_active' => true,
                        'last_upload_id' => $batch->id,
                    ]);

                    // Record history log
                    StockHistory::create([
                        'stock_item_id' => $newBarang->id,
                        'stok_upload_id' => $batch->id,
                        'qty_change' => $row->qty,
                        'qty_before' => 0,
                        'qty_after' => $row->qty,
                        'type' => 'Upload Excel',
                        'notes' => "Stok awal dari batch #{$batch->id} (Sheet: {$row->sheet_name})",
                    ]);

                    $results['inserted']++;
                }
            }

            // Update batch stats
            $rejectedCount = $batch->details()->where('status_verification', 'Tolak')->count();
            
            $batch->update([
                'rejected_rows_count' => $rejectedCount,
                'status' => 'Selesai',
            ]);

            // Save Audit Log
            AuditLog::create([
                'user_id' => $userId,
                'action' => 'Finalisasi Stok Excel',
                'description' => "Memfinalisasi batch upload #{$batch->id}. Menambahkan {$results['inserted']} barang baru dan mengupdate {$results['updated']} barang.",
                'ip_address' => request()->ip(),
            ]);

            // Sync with existing prototype history log
            HistoryLog::create([
                'actor' => $actorName,
                'action' => 'Finalisasi Stok Excel',
                'details' => "Finalisasi batch upload #{$batch->id} selesai. Total barang ditambahkan/diupdate: " . ($results['inserted'] + $results['updated']),
            ]);
        });

        return $results;
    }
}
