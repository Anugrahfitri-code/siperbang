<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Barang;
use App\Models\HistoryLog;
use App\Models\StockHistory;
use App\Models\StokUpload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * StokCancellationService
 *
 * Handles the "Batalkan Transaksi" flow for a finalised upload batch.
 * Creates a reversal transaction that:
 *   1. Decrements qty back on each affected stock_item
 *   2. Writes reversal stok_histories rows (is_reversal = true)
 *   3. Updates the batch status to 'Dibatalkan'
 *   4. Records an AuditLog and HistoryLog entry
 *
 * Constraints:
 *   - Only batches with status = 'Selesai' can be cancelled.
 *   - If a reversal would make a stock qty go below 0, the cancellation
 *     is blocked to preserve data integrity.
 */
class StokCancellationService
{
    /**
     * Cancel a finalised upload batch.
     *
     * @param  StokUpload  $batch
     * @param  string      $reason  Cancellation reason provided by the user
     * @return array       ['reversed' => int, 'skipped' => int]
     *
     * @throws \Exception  If batch is not cancellable or stock would go negative
     */
    public function cancel(StokUpload $batch, string $reason): array
    {
        if (! $batch->isCancellable()) {
            throw new \Exception("Hanya upload dengan status 'Selesai' yang dapat dibatalkan. Status saat ini: {$batch->status}");
        }

        $actor  = Auth::user()?->name ?? 'Petugas Persediaan';
        $userId = Auth::id() ?? 1;

        // Collect the original StockHistory entries for this batch
        $originalHistories = StockHistory::where('stok_upload_id', $batch->id)
            ->where('is_reversal', false)
            ->get();

        if ($originalHistories->isEmpty()) {
            throw new \Exception("Tidak ada histori stok yang dapat dibalik untuk batch ini. Mungkin finalisasi tidak menghasilkan perubahan stok.");
        }

        $results = ['reversed' => 0, 'skipped' => 0];

        DB::transaction(function () use ($batch, $originalHistories, $reason, $actor, $userId, &$results) {

            foreach ($originalHistories as $history) {
                $barang = Barang::lockForUpdate()->find($history->stock_item_id);

                if (! $barang) {
                    $results['skipped']++;
                    continue;
                }

                // Safety check: reversing would subtract qty_change from current qty
                $newQty = $barang->qty - $history->qty_change;

                if ($newQty < 0) {
                    throw new \Exception(
                        "Pembatalan akan membuat stok '{$barang->name}' (kode: {$barang->code}) " .
                        "menjadi negatif ({$newQty}). Stok saat ini: {$barang->qty}, " .
                        "Qty yang akan dibalik: {$history->qty_change}. " .
                        "Pastikan stok tidak sudah terpakai sebelum membatalkan upload ini."
                    );
                }

                // Apply the reversal
                $barang->update([
                    'qty'          => $newQty,
                    'last_updated' => now(),
                ]);

                // Write a reversal stok_history row
                StockHistory::create([
                    'stock_item_id'  => $history->stock_item_id,
                    'stok_upload_id' => $batch->id,
                    'qty_change'     => -$history->qty_change,
                    'qty_before'     => $history->qty_after,   // reversed perspective
                    'qty_after'      => $newQty,
                    'type'           => 'Pembatalan Upload',
                    'notes'          => "Pembatalan batch #{$batch->id}. Alasan: {$reason}",
                    'is_reversal'    => true,
                    'reversal_of_id' => $history->id,
                ]);

                $results['reversed']++;
            }

            // Mark batch as cancelled
            $batch->update([
                'status'              => StokUpload::STATUS_DIBATALKAN,
                'cancelled_at'        => now(),
                'cancelled_by'        => $actor,
                'cancellation_reason' => $reason,
            ]);

            // Audit log
            AuditLog::create([
                'user_id'     => $userId,
                'action'      => 'Batalkan Transaksi Upload',
                'description' => "Membatalkan batch upload #{$batch->id} ({$batch->file_name_original}). "
                    . "Membalik {$results['reversed']} item stok. Alasan: {$reason}",
                'ip_address'  => request()->ip(),
            ]);

            // History log
            HistoryLog::create([
                'actor'   => $actor,
                'action'  => 'Batalkan Transaksi Upload',
                'details' => "Batch #{$batch->id} dibatalkan. {$results['reversed']} item stok dibalik. Alasan: {$reason}",
            ]);
        });

        return $results;
    }
}
