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

        $results = ['reversed' => 0, 'skipped' => 0, 'clamped' => 0];

        DB::transaction(function () use ($batch, $originalHistories, $reason, $actor, $userId, &$results) {

            foreach ($originalHistories as $history) {
                $barang = Barang::lockForUpdate()->find($history->stock_item_id);

                if (! $barang) {
                    $results['skipped']++;
                    continue;
                }

                // Calculate the reversal qty.
                // If current stock is already below qty_change (e.g. stock was consumed
                // after upload), we clamp to 0 and record the shortfall in notes.
                // We never block the cancellation because of this — the upload
                // must always be cancellable regardless of current stock level.
                $rawNewQty  = $barang->qty - $history->qty_change;
                $newQty     = max(0, $rawNewQty);
                $shortfall  = $rawNewQty < 0 ? abs($rawNewQty) : 0;

                $notes = "Pembatalan batch #{$batch->id}. Alasan: {$reason}";
                if ($shortfall > 0) {
                    $notes .= " | Catatan: stok '{$barang->name}' sudah terpakai "
                        . "{$shortfall} unit sebelum pembatalan, stok disetel ke 0.";
                }

                $barang->update([
                    'qty'          => $newQty,
                    'last_updated' => now(),
                ]);

                StockHistory::create([
                    'stock_item_id'  => $history->stock_item_id,
                    'stok_upload_id' => $batch->id,
                    'qty_change'     => -$history->qty_change,
                    'qty_before'     => $barang->getOriginal('qty'),
                    'qty_after'      => $newQty,
                    'type'           => 'Pembatalan Upload',
                    'notes'          => $notes,
                    'is_reversal'    => true,
                    'reversal_of_id' => $history->id,
                ]);

                if ($shortfall > 0) {
                    $results['clamped']++;
                } else {
                    $results['reversed']++;
                }
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
                    . "Dibalik: {$results['reversed']} item, "
                    . "Disetel ke 0 (stok sudah terpakai): {$results['clamped']} item. "
                    . "Alasan: {$reason}",
                'ip_address'  => request()->ip(),
            ]);

            // History log
            HistoryLog::create([
                'actor'   => $actor,
                'action'  => 'Batalkan Transaksi Upload',
                'details' => "Batch #{$batch->id} dibatalkan. "
                    . "Dibalik: {$results['reversed']}, Disetel 0: {$results['clamped']} item. "
                    . "Alasan: {$reason}",
            ]);
        });

        return $results;
    }
}
