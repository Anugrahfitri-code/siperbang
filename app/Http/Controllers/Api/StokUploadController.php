<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\KodePersediaan;
use App\Models\StokUpload;
use App\Models\StokUploadDetail;
use App\Services\Inventory\StokFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StokUploadController extends Controller
{
    public function __construct(
        private StokFinalizationService $finalizationService,
    ) {}

    public function apiStepper(int $id, Request $request)
    {
        $this->authorizeRole('Petugas Persediaan');

        $batch = StokUpload::with([
            'details' => fn ($query) => $query
                ->orderBy('sheet_name')
                ->orderBy('no_urut'),
            'user',
        ])->findOrFail($id);

        if ($batch->status === StokUpload::STATUS_DIBATALKAN) {
            return response()->json(['error' => 'Batch sudah dibatalkan'], 400);
        }

        $step = $request->integer('step', $batch->resolveNextStep());
        $step = max(1, min(4, $step));

        $masterCodes = KodePersediaan::with('kategoriBarang')->orderBy('kode')->get();
        $allDetails = $batch->details;

        $errorRows = $allDetails->where('status_validation', 'Perlu Perbaikan')->values();
        $validRows = $allDetails->where('status_validation', 'Menunggu Verifikasi')->values();
        $pendingRows = $allDetails->where('status_verification', 'Pending')->values();
        $approvedRows = $allDetails->where('status_verification', 'Setuju')->values();
        $rejectedRows = $allDetails->where('status_verification', 'Tolak')->values();

        $totalApproved = $approvedRows->count();
        $totalValue = (float) $approvedRows->sum(fn ($d) => (float) ($d->total_calculated ?? 0));

        $canFinalize = $totalApproved > 0 && $pendingRows->isEmpty() && ! in_array($batch->status, [StokUpload::STATUS_SELESAI, StokUpload::STATUS_DIBATALKAN], true);

        return response()->json([
            'batch' => $batch,
            'step' => $step,
            'masterCodes' => $masterCodes,
            'stats' => [
                'total_rows' => $allDetails->count(),
                'error_count' => $errorRows->count(),
                'valid_count' => $validRows->count(),
                'pending_count' => $pendingRows->count(),
                'approved_count' => $approvedRows->count(),
                'rejected_count' => $rejectedRows->count(),
                'total_value' => $totalValue,
                'can_finalize' => $canFinalize,
            ],
            'details' => [
                'error' => $errorRows,
                'valid' => $validRows,
                'pending' => $pendingRows,
                'approved' => $approvedRows,
                'rejected' => $rejectedRows,
                'all' => $allDetails,
            ],
        ]);
    }

    public function apiSaveVerifikasi(Request $request, int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        $request->validate([
            'items' => 'required|array',
            'items.*.detail_id' => 'required|integer|exists:stok_upload_details,id',
            'items.*.action' => 'required|string|in:Setuju,Perbaiki,Tolak',
            'items.*.kode_persediaan' => 'nullable|string|max:50',
        ]);

        foreach ($request->input('items', []) as $item) {
            $detail = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('id', $item['detail_id'])
                ->firstOrFail();

            match ($item['action']) {
                'Setuju' => $detail->update([
                    'status_verification' => 'Setuju',
                    'status_validation' => 'Menunggu Verifikasi',
                ]),
                'Perbaiki' => $detail->update([
                    'status_verification' => 'Setuju',
                    'verified_kode_persediaan' => $item['kode_persediaan'] ?? $detail->kode_persediaan_excel,
                    'status_validation' => 'Menunggu Verifikasi',
                ]),
                'Tolak' => $detail->update([
                    'status_verification' => 'Tolak',
                ]),
            };
        }

        $this->syncBatchStats($batch);

        $pendingCount = $batch->details()->where('status_verification', 'Pending')->count();
        if ($pendingCount === 0) {
            $batch->update([
                'status' => StokUpload::STATUS_SIAP_DIFINALISASI,
                'current_step' => StokUpload::STEP_REVIEW,
            ]);
        }

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'Verifikasi Kode Persediaan (API)',
            'description' => "Menyimpan verifikasi kode untuk batch #{$batch->id}.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['success' => true, 'message' => 'Verifikasi berhasil disimpan']);
    }

    public function apiFinalisasi(int $id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        try {
            $results = $this->finalizationService->finalize($batch);

            return response()->json(['success' => true, 'message' => "Finalisasi berhasil! {$results['inserted']} barang baru ditambahkan, {$results['updated']} diperbarui."]);
        } catch (\Exception $e) {
            Log::error('Error StokUpload API', ['exception' => $e]);
            $msg = $e instanceof \DomainException ? $e->getMessage() : 'Terjadi kesalahan sistem saat memproses data.';

            return response()->json(['success' => false, 'error' => $msg], 400);
        }
    }

    public function apiRiwayat()
    {
        $this->authorizeRole('Petugas Persediaan');
        $batches = StokUpload::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($batches);
    }

    public function apiStats()
    {
        $this->authorizeRole('Petugas Persediaan');

        $finalisedBatches = StokUpload::where('status', StokUpload::STATUS_SELESAI)->get();

        $totalBelanja = 0;
        $totalPajak = 0;

        foreach ($finalisedBatches as $batch) {
            $details = StokUploadDetail::where('stok_upload_id', $batch->id)
                ->where('status_verification', 'Setuju')
                ->get();

            foreach ($details as $detail) {
                $totalBelanja += (float) $detail->total_calculated;
                if ($detail->is_taxed) {
                    $basePrice = (float) $detail->qty * (float) $detail->price_unit;
                    $totalPajak += ((float) $detail->total_calculated - $basePrice);
                }
            }
        }

        $pendingVerify = StokUpload::whereIn('status', [StokUpload::STATUS_MENUNGGU_VERIFIKASI, StokUpload::STATUS_SIAP_DIFINALISASI])->count();

        return response()->json([
            'total_belanja' => $totalBelanja,
            'total_pajak' => $totalPajak,
            'kuitansi_valid' => $finalisedBatches->count(),
            'pending_verify' => $pendingVerify,
        ]);
    }

    private function syncBatchStats(StokUpload $batch): void
    {
        $batch->refresh();
        $details = $batch->details;

        $validCount = $details->where('status_validation', 'Menunggu Verifikasi')->count();
        $errorCount = $details->where('status_validation', 'Perlu Perbaikan')->count();
        $rejectedCount = $details->where('status_verification', 'Tolak')->count();

        $newStatus = $errorCount === 0
            ? StokUpload::STATUS_MENUNGGU_VERIFIKASI
            : StokUpload::STATUS_PERLU_PERBAIKAN;

        $batch->update([
            'valid_rows_count' => $validCount,
            'error_rows_count' => $errorCount,
            'rejected_rows_count' => $rejectedCount,
            'status' => $newStatus,
        ]);
    }

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
