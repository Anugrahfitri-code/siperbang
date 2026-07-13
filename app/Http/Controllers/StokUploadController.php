<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadStokExcelRequest;
use App\Models\StokUpload;
use App\Services\ExcelPersediaanImportService;
use App\Services\StokFinalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class StokUploadController extends Controller
{
    protected $importService;
    protected $finalizationService;

    public function __construct(
        ExcelPersediaanImportService $importService,
        StokFinalizationService $finalizationService
    ) {
        $this->importService = $importService;
        $this->finalizationService = $finalizationService;
    }

    /**
     * Show upload form.
     */
    public function index()
    {
        $this->authorizeRole('Petugas Persediaan');
        return view('stok-upload.index');
    }

    /**
     * Handle the uploaded Excel file.
     */
    public function upload(UploadStokExcelRequest $request)
    {
        $file = $request->file('file_excel');
        
        $originalName = $file->getClientOriginalName();
        $storedName = time() . '_' . $file->getClientOriginalName();
        
        // Save file in storage/app/private/uploads (or according to local disk config)
        $path = $file->storeAs('private/uploads', $storedName);
        $fullPath = Storage::path($path);

        try {
            $batch = $this->importService->import($fullPath, $originalName, $storedName);
            return redirect()->route('stok-upload.preview', $batch->id)
                ->with('success', 'File Excel berhasil diunggah dan diuraikan.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['file_excel' => 'Gagal memproses file Excel: ' . $e->getMessage()]);
        }
    }

    /**
     * Show preview table.
     */
    public function preview($id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::with('details')->findOrFail($id);
        return view('stok-upload.preview', compact('batch'));
    }

    /**
     * Finalize the batch.
     */
    public function finalisasi($id)
    {
        $this->authorizeRole('Petugas Persediaan');
        $batch = StokUpload::findOrFail($id);

        try {
            $results = $this->finalizationService->finalize($batch);
            return redirect()->route('stok-upload.riwayat')
                ->with('success', "Finalisasi berhasil! Menambahkan {$results['inserted']} barang baru dan mengupdate {$results['updated']} barang.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show upload batch histories.
     */
    public function riwayat()
    {
        $this->authorizeRole('Petugas Persediaan');
        $batches = StokUpload::with('user')->orderBy('created_at', 'desc')->paginate(15);
        return view('stok-upload.riwayat', compact('batches'));
    }

    /**
     * Download the template Excel file.
     */
    public function downloadTemplate()
    {
        $this->authorizeRole('Petugas Persediaan');
        $sourcePath = 'D:/Belanja Persediaan 2026.xlsx';
        $destDir = public_path('templates');
        $destPath = $destDir . '/Belanja Persediaan 2026.xlsx';

        if (!File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $destPath);
            return response()->download($destPath, 'Belanja Persediaan 2026.xlsx');
        }

        return redirect()->back()->with('error', 'Template file "Belanja Persediaan 2026.xlsx" tidak ditemukan di path target D:/.');
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
