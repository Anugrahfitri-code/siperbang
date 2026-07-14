<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadStokExcelRequest;
use App\Models\AuditLog;
use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use App\Services\ExcelPersediaanImportService;
use App\Services\StokCancellationService;
use App\Services\StokFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StokUploadController extends Controller
{
    public function __construct(
        private ExcelPersediaanImportService $importService,
        private StokFinalizationService      $finalizationService,
        private StokCancellationService      $cancellationService,
    ) {}

    // ──────────────────────────────────────────────────────────────
    // STEP 1 — Upload form
    // ──────────────────────────────────────────────────────────────

    public function index()
    {
        $this->authorizeRole('Petugas Persediaan');
        return view('stok-upload.index');
    }

    public function upload(UploadStokExcelRequest $request)
    {
        $file         = $request->file('file_excel');
        $originalName = $file->getClientOriginalName();
        $storedName   = time() . '_' . $originalName;
        $path         = $file->storeAs('private/uploads', $storedName);
        $fullPath     = Storage::path($path);

        try {
            $batch = $this->importService->import($fullPath, $originalName, $storedName);
            return redirect()->route('stok-upload.stepper', $batch->id)
                ->with('success', 'File Excel berhasil diunggah. Periksa hasil validasi di bawah.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['file_excel' => 'Gagal memproses file: ' . $e->getMessage()]);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // STEPPER — unified 4-step view for a batch
    // ──────────────────────────────────────────────────────────────

    public function stepper(int $id, Request $request)
    {
        $this->authorizeRole('Petugas Persediaan');

        $batch = StokUpload::with(['details' => fn ($q) => $q->orderBy('sheet_name')->orderBy('no_urut'), 'user'])
            ->findOrFail($id);

        // Determine which step to display; URL param ?step overrides
        $step = $request->integer('step', $batch->resolveNextStep());
        $step = max(1, min(4, $step));

        $masterCodes  = KodePersediaan::with('kategoriBarang')->orderBy('kode')->get();
        $errorRows    = $batch->details->where('status_validation', 'Perlu Perbaikan');
        $validRows    = $batch->details->where('status_validation', 'Menunggu Verifikasi');

        return view('stok-upload.stepper', compact(
            'batch', 'step', 'masterCodes', 'errorRows', 'validRows'
        ));
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 2 — Save inline data fixes
    // ──────────────────────────────────────────────────────────────

    public function saveFixes(Request $request, int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        if ($batch->status === StokUpload::STATUS_SELESAI) {
            return redirect()->route('stok-upload.stepper', $id)
                ->with('error', 'Batch sudah difinalisasi dan tidak dapat diubah.');
        }

        $request->validate([
            'rows'                   => 'required|array',
            'rows.*.detail_id'       => 'required|integer|exists:stok_upload_details,id',
            'rows.*.kode_persediaan' => 'nullable|string|max:50',
            'rows.*.nama_barang'     => 'required|string|max:255',
            'rows.*.qty'             => 'required|integer|min:1',
            'rows.*.unit'            => 'required|string|max:50',
            'rows.*.price_unit'      => 'required|numeric|min:0',
            'rows.*.is_taxed'        => 'nullable|boolean',
        ]);

        foreach ($request->input('rows', []) as $input) {
            $detail = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('id', $input['detail_id'])
                ->firstOrFail();

            $isTaxed         = ! empty($input['is_taxed']);
            $priceUnit       = (float) $input['price_unit'];
            $qty             = (int)   $input['qty'];
            $priceUnitTaxed  = $isTaxed ? round($priceUnit * 1.11, 2) : $priceUnit;
            $totalCalculated = round($priceUnitTaxed * $qty, 2);
            $kode            = $input['kode_persediaan'] ?? $detail->kode_persediaan_excel;

            $detail->update([
                'kode_persediaan_excel'    => $kode,
                'verified_kode_persediaan' => $kode,
                'nama_barang'              => $input['nama_barang'],
                'qty'                      => $qty,
                'unit'                     => $input['unit'],
                'price_unit'               => $priceUnit,
                'price_unit_taxed'         => $priceUnitTaxed,
                'total_calculated'         => $totalCalculated,
                'is_taxed'                 => $isTaxed,
                'status_validation'        => 'Menunggu Verifikasi',
                'status_verification'      => 'Setuju',
                'notes_error'              => null,
                'error_column'             => null,
            ]);
        }

        $this->syncBatchStats($batch);

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Perbaiki Data Upload',
            'description' => "Memperbaiki data pada batch #{$batch->id}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()
            ->route('stok-upload.stepper', ['id' => $batch->id, 'step' => 2])
            ->with('success', 'Perbaikan berhasil disimpan.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 2 — Re-submit after fixes (Ajukan Ulang)
    // ──────────────────────────────────────────────────────────────

    public function ajukanUlang(Request $request, int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::with('details')->findOrFail($id);

        $remaining = $batch->details->where('status_validation', 'Perlu Perbaikan')->count();
        if ($remaining > 0) {
            return redirect()
                ->route('stok-upload.stepper', ['id' => $id, 'step' => 2])
                ->with('error', "Masih ada {$remaining} baris yang belum diperbaiki.");
        }

        $batch->update([
            'status'       => StokUpload::STATUS_MENUNGGU_VERIFIKASI,
            'current_step' => StokUpload::STEP_VERIFIKASI,
        ]);

        return redirect()
            ->route('stok-upload.stepper', ['id' => $id, 'step' => 3])
            ->with('success', 'Data diajukan ke tahap Verifikasi Kode.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 2 — Download error CSV
    // ──────────────────────────────────────────────────────────────

    public function downloadErrors(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::with('details')->findOrFail($id);

        $errorRows = $batch->details->where('status_validation', 'Perlu Perbaikan');

        $csv = "Sheet,No. Baris,Kolom,Nama Barang,Kode Excel,Penyebab Error,Saran Perbaikan\n";
        foreach ($errorRows as $row) {
            $csv .= implode(',', [
                $row->sheet_name,
                $row->no_urut,
                $row->errorColumnLabel() ?? $row->error_column ?? '-',
                '"' . str_replace('"', '""', $row->nama_barang) . '"',
                $row->kode_persediaan_excel ?? '-',
                '"' . str_replace('"', '""', $row->notes_error ?? '-') . '"',
                '"' . str_replace('"', '""', $row->fixSuggestion() ?? '-') . '"',
            ]) . "\n";
        }

        $filename = "errors_batch_{$batch->id}_{$batch->upload_date->format('Ymd')}.csv";

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 3 — Save verifikasi decisions
    // ──────────────────────────────────────────────────────────────

    public function saveVerifikasi(Request $request, int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        $request->validate([
            'items'               => 'required|array',
            'items.*.detail_id'   => 'required|integer|exists:stok_upload_details,id',
            'items.*.action'      => 'required|string|in:Setuju,Perbaiki,Tolak',
            'items.*.kode_persediaan' => 'nullable|string|max:50',
        ]);

        foreach ($request->input('items', []) as $item) {
            $detail = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('id', $item['detail_id'])
                ->firstOrFail();

            match ($item['action']) {
                'Setuju'  => $detail->update([
                    'status_verification'      => 'Setuju',
                    'status_validation'        => 'Menunggu Verifikasi',
                ]),
                'Perbaiki' => $detail->update([
                    'status_verification'      => 'Setuju',
                    'verified_kode_persediaan' => $item['kode_persediaan'] ?? $detail->kode_persediaan_excel,
                    'status_validation'        => 'Menunggu Verifikasi',
                ]),
                'Tolak' => $detail->update([
                    'status_verification' => 'Tolak',
                ]),
            };
        }

        $this->syncBatchStats($batch);

        // Determine if all verified rows are approved → Siap Difinalisasi
        $pendingCount = $batch->details()->where('status_verification', 'Pending')->count();
        if ($pendingCount === 0) {
            $batch->update([
                'status'       => StokUpload::STATUS_SIAP_DIFINALISASI,
                'current_step' => StokUpload::STEP_REVIEW,
            ]);
        }

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Verifikasi Kode Persediaan',
            'description' => "Menyimpan verifikasi kode untuk batch #{$batch->id}.",
            'ip_address'  => $request->ip(),
        ]);

        return redirect()
            ->route('stok-upload.stepper', ['id' => $id, 'step' => 4])
            ->with('success', 'Verifikasi kode berhasil disimpan. Silakan lanjutkan ke Review & Finalisasi.');
    }

    // ──────────────────────────────────────────────────────────────
    // STEP 4 — Finalisasi
    // ──────────────────────────────────────────────────────────────

    public function finalisasi(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        try {
            $results = $this->finalizationService->finalize($batch);
            return redirect()->route('stok-upload.riwayat')
                ->with('success', "Finalisasi berhasil! {$results['inserted']} barang baru ditambahkan, {$results['updated']} diperbarui.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // BATALKAN TRANSAKSI (only for Selesai batches)
    // ──────────────────────────────────────────────────────────────

    public function batalkan(Request $request, int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        $request->validate([
            'cancellation_reason' => 'required|string|min:10|max:500',
        ]);

        try {
            $results = $this->cancellationService->cancel($batch, $request->cancellation_reason);
            return redirect()->route('stok-upload.riwayat')
                ->with('success', "Batch #{$batch->id} berhasil dibatalkan. {$results['reversed']} item stok dibalik.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // RIWAYAT — list active batches
    // ──────────────────────────────────────────────────────────────

    public function riwayat()
    {
        $this->authorizeRole('Petugas Persediaan');
        $batches = StokUpload::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('stok-upload.riwayat', compact('batches'));
    }

    // ──────────────────────────────────────────────────────────────
    // TRASH — soft-deleted batches (30-day holding bin)
    // ──────────────────────────────────────────────────────────────

    public function trash()
    {
        $this->authorizeRole('Petugas Persediaan');
        $batches = StokUpload::onlyTrashed()
            ->with('user')
            ->orderBy('deleted_at', 'desc')
            ->paginate(15);

        return view('stok-upload.trash', compact('batches'));
    }

    // ──────────────────────────────────────────────────────────────
    // HAPUS (soft delete — non-finalised only)
    // ──────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        if (! $batch->isDeletable()) {
            return redirect()->back()
                ->with('error', 'Upload yang sudah difinalisasi atau dibatalkan tidak dapat dihapus langsung. Gunakan Batalkan Transaksi terlebih dahulu.');
        }

        // Remove stored file
        Storage::delete('private/uploads/' . $batch->file_name_stored);

        $batch->delete(); // soft delete

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Hapus Upload (Sampah)',
            'description' => "Memindahkan batch #{$batch->id} ({$batch->file_name_original}) ke sampah.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->route('stok-upload.riwayat')
            ->with('success', "Upload \"{$batch->file_name_original}\" dipindahkan ke sampah. Akan dihapus permanen dalam 30 hari.");
    }

    // ──────────────────────────────────────────────────────────────
    // RESTORE dari sampah
    // ──────────────────────────────────────────────────────────────

    public function restore(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::onlyTrashed()->findOrFail($id);
        $batch->restore();

        return redirect()->route('stok-upload.riwayat')
            ->with('success', "Upload \"{$batch->file_name_original}\" berhasil dipulihkan dari sampah.");
    }

    // ──────────────────────────────────────────────────────────────
    // HAPUS PERMANEN
    // ──────────────────────────────────────────────────────────────

    public function forceDelete(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::onlyTrashed()->findOrFail($id);

        // Remove stored file permanently
        Storage::delete('private/uploads/' . $batch->file_name_stored);

        $batch->forceDelete();

        AuditLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'Hapus Permanen Upload',
            'description' => "Menghapus permanen batch #{$batch->id} ({$batch->file_name_original}) dari sampah.",
            'ip_address'  => request()->ip(),
        ]);

        return redirect()->route('stok-upload.trash')
            ->with('success', 'Upload dihapus secara permanen.');
    }

    // ──────────────────────────────────────────────────────────────
    // DOWNLOAD TEMPLATE
    // ──────────────────────────────────────────────────────────────

    public function downloadTemplate()
    {
        $this->authorizeRole('Petugas Persediaan');
        $sourcePath = 'D:/Belanja Persediaan 2026.xlsx';
        $destDir    = public_path('templates');
        $destPath   = $destDir . '/Belanja Persediaan 2026.xlsx';

        if (! File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $destPath);
            return response()->download($destPath, 'Belanja Persediaan 2026.xlsx');
        }

        return redirect()->back()
            ->with('error', 'Template tidak ditemukan di D:/. Hubungi administrator.');
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function syncBatchStats(StokUpload $batch): void
    {
        $batch->refresh();
        $details = $batch->details;

        $validCount    = $details->where('status_validation', 'Menunggu Verifikasi')->count();
        $errorCount    = $details->where('status_validation', 'Perlu Perbaikan')->count();
        $rejectedCount = $details->where('status_verification', 'Tolak')->count();

        $newStatus = $errorCount === 0
            ? StokUpload::STATUS_MENUNGGU_VERIFIKASI
            : StokUpload::STATUS_PERLU_PERBAIKAN;

        $batch->update([
            'valid_rows_count'    => $validCount,
            'error_rows_count'    => $errorCount,
            'rejected_rows_count' => $rejectedCount,
            'status'              => $newStatus,
        ]);
    }

    protected function authorizeRole(string $role): void
    {
        if (! auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }
        if (auth()->user()->role !== $role) {
            abort(403, "Akses ditolak. Halaman ini hanya untuk {$role}.");
        }
    }
}
