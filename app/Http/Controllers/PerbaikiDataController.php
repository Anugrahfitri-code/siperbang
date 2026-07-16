<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\HistoryLog;
use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use Illuminate\Http\Request;

class PerbaikiDataController extends Controller
{
    /**
     * Show the Perbaiki Data form.
     * Only shows rows with status_validation = 'Perlu Perbaikan'.
     */
    public function index($id)
    {
        $this->authorizeRole('Petugas Persediaan');

        $batch = StokUpload::with(['details' => function ($q) {
            $q->orderBy('sheet_name')->orderBy('no_urut');
        }, 'user'])->findOrFail($id);

        // Block if batch is already finalized
        if ($batch->status === 'Selesai') {
            return redirect()->route('stok-upload.preview', $id)
                ->with('error', 'Batch ini sudah difinalisasi dan tidak dapat diubah.');
        }

        $errorRows  = $batch->details->where('status_validation', 'Perlu Perbaikan');
        $validRows  = $batch->details->where('status_validation', 'Menunggu Verifikasi');
        $masterCodes = KodePersediaan::with('kategoriBarang')->orderBy('kode')->get();

        return view('stok-upload.perbaiki', compact('batch', 'errorRows', 'validRows', 'masterCodes'));
    }

    /**
     * Save user edits to invalid rows.
     * Recalculates price_unit_taxed and total_calculated after save.
     */
    public function store(Request $request, $id)
    {
        $this->authorizeRole('Petugas Persediaan');

        $batch = StokUpload::findOrFail($id);

        if ($batch->status === 'Selesai') {
            return redirect()->route('stok-upload.preview', $id)
                ->with('error', 'Batch ini sudah difinalisasi.');
        }

        $request->validate([
            'rows'                       => 'required|array',
            'rows.*.detail_id'           => 'required|integer|exists:stok_upload_details,id',
            'rows.*.kode_persediaan'     => 'nullable|string|max:50',
            'rows.*.nama_barang'         => 'required|string|max:255',
            'rows.*.qty'                 => 'required|integer|min:1',
            'rows.*.unit'                => 'required|string|max:50',
            'rows.*.price_unit'          => 'required|numeric|min:0',
            'rows.*.is_taxed'            => 'nullable|boolean',
        ]);

        foreach ($request->input('rows', []) as $input) {
            $detail = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('id', $input['detail_id'])
                ->firstOrFail();

            $isTaxed       = isset($input['is_taxed']) && (bool) $input['is_taxed'];
            $priceUnit     = (float) $input['price_unit'];
            $qty           = (int) $input['qty'];
            $taxRate       = 0.11;
            $priceUnitTaxed = $isTaxed ? round($priceUnit * (1 + $taxRate), 2) : $priceUnit;
            $totalCalculated = round($priceUnitTaxed * $qty, 2);

            // Determine which kode to use: user may pick from master or type manually
            $kodePersediaan = $input['kode_persediaan'] ?? $detail->kode_persediaan_excel;

            $detail->update([
                'kode_persediaan_excel'   => $kodePersediaan,
                'verified_kode_persediaan'=> $kodePersediaan,
                'nama_barang'             => $input['nama_barang'],
                'qty'                     => $qty,
                'unit'                    => $input['unit'],
                'price_unit'              => $priceUnit,
                'price_unit_taxed'        => $priceUnitTaxed,
                'total_calculated'        => $totalCalculated,
                'is_taxed'                => $isTaxed,
                // Clear the error — row has been user-corrected, pending re-validation check
                'status_validation'       => 'Menunggu Verifikasi',
                'status_verification'     => 'Setuju',
                'notes_error'             => null,
            ]);
        }

        // Recalculate batch stats
        $this->recalculateBatchStats($batch);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Perbaiki Data Upload',
            'description' => "User memperbaiki baris data pada batch upload #{$batch->id}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('stok-upload.perbaiki.index', $batch->id)
            ->with('success', 'Perubahan berhasil disimpan. Periksa kembali data sebelum mengajukan ulang.');
    }

    /**
     * Submit the batch back for verification after all errors are fixed.
     * Changes status to 'Menunggu Verifikasi'.
     */
    public function ajukanUlang(Request $request, $id)
    {
        $this->authorizeRole('Petugas Persediaan');

        $batch = StokUpload::with('details')->findOrFail($id);

        // Ensure there are no remaining error rows
        $remainingErrors = $batch->details->where('status_validation', 'Perlu Perbaikan')->count();

        if ($remainingErrors > 0) {
            return redirect()->route('stok-upload.perbaiki.index', $batch->id)
                ->with('error', "Masih ada {$remainingErrors} baris yang belum diperbaiki. Selesaikan semua perbaikan sebelum mengajukan ulang.");
        }

        $batch->update(['status' => 'Menunggu Verifikasi']);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Ajukan Ulang Upload',
            'description' => "Batch upload #{$batch->id} diajukan ulang ke status Menunggu Verifikasi setelah perbaikan data.",
            'ip_address'  => $request->ip(),
        ]);

        HistoryLog::create([
            'actor'   => auth()->user()->name,
            'action'  => 'Ajukan Ulang Upload',
            'details' => "Batch #{$batch->id} ({$batch->file_name_original}) diajukan ulang setelah perbaikan data oleh user.",
        ]);

        return redirect()->route('stok-upload.riwayat')
            ->with('success', "Batch #{$batch->id} berhasil diajukan ulang. Status sekarang: Menunggu Verifikasi.");
    }

    /**
     * Recalculate and sync valid_rows_count / error_rows_count / status on the batch.
     */
    private function recalculateBatchStats(StokUpload $batch): void
    {
        $batch->refresh();
        $details = $batch->details;

        $validCount    = $details->where('status_validation', 'Menunggu Verifikasi')->count();
        $errorCount    = $details->where('status_validation', 'Perlu Perbaikan')->count();
        $rejectedCount = $details->where('status_verification', 'Tolak')->count();

        if ($errorCount === 0) {
            $batchStatus = 'Menunggu Verifikasi';
        } elseif ($validCount > 0) {
            $batchStatus = 'Sebagian Valid';
        } else {
            $batchStatus = 'Perlu Perbaikan';
        }

        $batch->update([
            'valid_rows_count'    => $validCount,
            'error_rows_count'    => $errorCount,
            'rejected_rows_count' => $rejectedCount,
            'status'              => $batchStatus,
        ]);
    }

    /**
     * Helper to enforce roles in controller.
     */
    protected function authorizeRole(string $role): void
    {
        if (! auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        if (auth()->user()->role === 'Superadmin') {
            return;
        }
        if (auth()->user()->role !== $role) {
            abort(403, "Akses ditolak. Halaman ini hanya untuk {$role}.");
        }
    }
}
