<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifikasiKodePersediaanRequest;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use App\Models\AuditLog;
use App\Models\HistoryLog;
use App\Models\KodePersediaan;
use Illuminate\Http\Request;

class VerifikasiKodePersediaanController extends Controller
{
    /**
     * Show verification form.
     */
    public function verifikasi($id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::with('details')->findOrFail($id);
        $masterCodes = KodePersediaan::with('kategoriBarang')->get();
        return view('stok-upload.verifikasi', compact('batch', 'masterCodes'));
    }

    /**
     * Process verification decisions.
     */
    public function postVerifikasi(VerifikasiKodePersediaanRequest $request, $id)
    {
        $batch = StokUpload::findOrFail($id);

        foreach ($request->input('items', []) as $item) {
            $detail = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('id', $item['detail_id'])
                ->firstOrFail();
            
            $action = $item['action'];
            
            if ($action === 'Setuju') {
                $detail->update([
                    'status_verification' => 'Setuju',
                    'status_validation' => 'Menunggu Verifikasi', // Reset validation error since it is approved
                ]);
            } elseif ($action === 'Perbaiki') {
                $newCode = $item['kode_persediaan'];
                $detail->update([
                    'status_verification' => 'Setuju', // Automatically set to approved after correction
                    'verified_kode_persediaan' => $newCode,
                    'status_validation' => 'Menunggu Verifikasi',
                ]);
            } elseif ($action === 'Tolak') {
                $detail->update([
                    'status_verification' => 'Tolak',
                ]);
            }
        }

        // Re-calculate batch stats
        $details = $batch->details;
        $validCount = $details->where('status_validation', 'Menunggu Verifikasi')->count();
        $errorCount = $details->where('status_validation', 'Perlu Perbaikan')->count();
        $rejectedCount = $details->where('status_verification', 'Tolak')->count();

        $batchStatus = 'Menunggu Verifikasi';
        if ($errorCount > 0) {
            $batchStatus = ($validCount > 0) ? 'Sebagian Valid' : 'Perlu Perbaikan';
        }

        $batch->update([
            'valid_rows_count' => $validCount,
            'error_rows_count' => $errorCount,
            'rejected_rows_count' => $rejectedCount,
            'status' => $batchStatus,
        ]);

        // Audit & History logging
        $user = auth()->user();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Verifikasi Kode Persediaan',
            'description' => "Menyimpan keputusan verifikasi kode persediaan untuk batch upload #{$batch->id}.",
            'ip_address' => $request->ip(),
        ]);

        HistoryLog::create([
            'actor' => $user->name,
            'action' => 'Verifikasi Kode Persediaan',
            'details' => "Verifikasi baris barang selesai diperbarui untuk batch #{$batch->id}.",
        ]);

        return redirect()->route('stok-upload.preview', $batch->id)
            ->with('success', 'Keputusan verifikasi berhasil disimpan. Silakan lakukan finalisasi data.');
    }

    /**
     * Helper to enforce roles in controller.
     */
    protected function authorizeRole(string $role)
    {
        if (!auth()->check()) {
            abort(401, 'Silakan login terlebih dahulu.');
        }

        if (auth()->user()->role !== $role) {
            abort(403, 'Akses ditolak. Halaman ini hanya boleh diakses oleh ' . $role);
        }
    }
}
