<?php

use Illuminate\Support\Facades\Route;

// Web Entry Point for React
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/login', function () {
    return view('welcome');
})->name('login');

Route::middleware('auth')->group(function () {
    $ctrl = \App\Http\Controllers\Web\StokUploadController::class;

    // ── Stok Upload — Stepper Workflow ──────────────────────────
    Route::get('/stok-upload',                   [$ctrl, 'index']          )->name('stok-upload.index');
    Route::post('/stok-upload',                  [$ctrl, 'upload']         )->name('stok-upload.store');
    Route::get('/stok-upload/template',          [$ctrl, 'downloadTemplate'])->name('stok-upload.template');
    Route::get('/stok-upload/riwayat',           [$ctrl, 'riwayat']        )->name('stok-upload.riwayat');
    Route::get('/stok-upload/sampah',            [$ctrl, 'trash']          )->name('stok-upload.trash');

    // Unified stepper (replaces preview + verifikasi + perbaiki pages)
    Route::get('/stok-upload/{id}/stepper',      [$ctrl, 'stepper']        )->name('stok-upload.stepper');

    // Step 2 — Pemeriksaan Data (read-only, no inline editing)
    // errors are shown on the upload page (index), not the stepper

    // Step 3 — Verifikasi Kode
    Route::post('/stok-upload/{id}/verifikasi',  [$ctrl, 'saveVerifikasi'] )->name('stok-upload.verifikasi.store');

    // Step 4 — Finalisasi & Pembatalan
    Route::post('/stok-upload/{id}/finalisasi',  [$ctrl, 'finalisasi']     )->name('stok-upload.finalisasi');
    Route::post('/stok-upload/{id}/batalkan',    [$ctrl, 'batalkan']       )->name('stok-upload.batalkan');

    // Soft delete management
    Route::delete('/stok-upload/{id}',           [$ctrl, 'destroy']        )->name('stok-upload.destroy');
    Route::post('/stok-upload/{id}/restore',     [$ctrl, 'restore']        )->name('stok-upload.restore');
    Route::delete('/stok-upload/{id}/force',     [$ctrl, 'forceDelete']    )->name('stok-upload.force-delete');

    // Backward-compat aliases (redirect old URLs to stepper)
    Route::get('/stok-upload/{id}/preview',     fn ($id) => redirect()->route('stok-upload.stepper', $id))->name('stok-upload.preview');
    Route::get('/stok-upload/{id}/verifikasi',  fn ($id) => redirect()->route('stok-upload.stepper', ['id' => $id, 'step' => 3]))->name('stok-upload.verifikasi.index');
    Route::get('/stok-upload/{id}/perbaiki',    fn ($id) => redirect()->route('stok-upload.stepper', ['id' => $id, 'step' => 2]))->name('stok-upload.perbaiki.index');

    // Master Barang
    Route::get('/master-barang',                [\App\Http\Controllers\Web\BarangController::class, 'index'] )->name('master-barang.index');
    Route::get('/master-barang/search',         [\App\Http\Controllers\Web\BarangController::class, 'search'])->name('master-barang.search');
    Route::post('/master-barang/{id}/update',   [\App\Http\Controllers\Web\BarangController::class, 'update'])->name('master-barang.update');
    Route::post('/master-barang/{id}/delete',   [\App\Http\Controllers\Web\BarangController::class, 'destroy'])->name('master-barang.destroy');
});

// API routes use the web middleware stack because authentication is session-based.
require __DIR__.'/api.php';

// Fallback for React Router (if using client-side routing)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
