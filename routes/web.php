<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Web Entry Point for React
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Authenticated User Info
Route::get('/api/user', function (Request $request) {
    if (Auth::check()) {
        return response()->json(Auth::user());
    }
    return response()->json(['message' => 'Unauthenticated'], 401);
}); 

// Auth Routes
Route::post('/api/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return response()->json(['message' => 'Login successful', 'user' => Auth::user()]);
    }

    return response()->json(['message' => 'Kredensial tidak valid'], 401);
});

Route::post('/api/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['message' => 'Logout successful']);
});

Route::middleware('auth')->group(function () {
    $ctrl = \App\Http\Controllers\StokUploadController::class;

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
    Route::post('/stok-upload/{id}/perbaiki',   [$ctrl, 'saveFixes'])->name('stok-upload.perbaiki.store');

    // Master Barang
    Route::get('/master-barang',                [\App\Http\Controllers\BarangController::class, 'index'] )->name('master-barang.index');
    Route::get('/master-barang/search',         [\App\Http\Controllers\BarangController::class, 'search'])->name('master-barang.search');
});

// Protected API Routes
Route::middleware('auth')->prefix('api')->group(function () {
    // ---- Semua Authenticated User ----
    // Requests
    Route::get('/requests', [\App\Http\Controllers\Api\RequestController::class, 'index']);
    Route::get('/requests/bon', [\App\Http\Controllers\Api\RequestController::class, 'indexBons']);
    Route::get('/requests/bon/{id}', [\App\Http\Controllers\Api\RequestController::class, 'showBon']);
    
    // Logs
    Route::get('/logs', [\App\Http\Controllers\Api\LogController::class, 'index']);
    Route::post('/logs', [\App\Http\Controllers\Api\LogController::class, 'store']);

    // Stock search — read-only, accessible by all authenticated roles
    Route::get('/stocks/search', [\App\Http\Controllers\Api\StockController::class, 'search']);

    // ---- Ketua Tim & Superadmin ----
    Route::middleware('role:Ketua Tim,Ketua Tim Kerja,Superadmin')->group(function () {
        Route::post('/requests', [\App\Http\Controllers\Api\RequestController::class, 'store']);
        Route::put('/requests/bon/{id}', [\App\Http\Controllers\Api\RequestController::class, 'updateDraft']);
        Route::delete('/requests/bon/{id}', [\App\Http\Controllers\Api\RequestController::class, 'destroyDraft']);
    });

    // ---- Petugas Persediaan & Superadmin ----
    Route::middleware('role:Petugas Persediaan,Superadmin')->group(function () {
        // Stocks
        Route::get('/stocks', [\App\Http\Controllers\Api\StockController::class, 'index']);
        Route::post('/stocks/bulk', [\App\Http\Controllers\Api\StockController::class, 'bulkStore']);
        
        // Request Actions
        Route::put('/requests/{itemRequest}/status', [\App\Http\Controllers\Api\RequestController::class, 'updateStatus']);
        Route::post('/requests/{itemRequest}/distribute', [\App\Http\Controllers\Api\RequestController::class, 'distribute']);
        Route::post('/requests/{itemRequest}/procure', [\App\Http\Controllers\Api\RequestController::class, 'procure']);
        Route::post('/requests/{itemRequest}/complete-procurement', [\App\Http\Controllers\Api\RequestController::class, 'completeProcurement']);
        Route::post('/requests/{itemRequest}/reject', [\App\Http\Controllers\Api\RequestController::class, 'rejectItem']);
        
        // Receipts
        Route::get('/receipts', [\App\Http\Controllers\Api\ReceiptController::class, 'index']);
        Route::post('/receipts', [\App\Http\Controllers\Api\ReceiptController::class, 'store']);
        Route::put('/receipts/{receipt}', [\App\Http\Controllers\Api\ReceiptController::class, 'update']);
        
        // Receipt Documents (OCR)
        Route::get('/receipt-documents', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'index']);
        Route::post('/receipt-documents', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'store']);
        Route::get('/receipt-documents/{receiptDocument}', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'show']);
        Route::put('/receipt-documents/{receiptDocument}/verify', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'verify']);
        Route::post('/receipt-documents/{receiptDocument}/retry', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'retry']);
        
        // Export
        Route::get('/export-excel', [\App\Http\Controllers\Api\LogController::class, 'exportExcel']);
    });

    // ---- Superadmin Only ----
    Route::middleware('role:Superadmin')->group(function () {
        // Users
        Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
        Route::put('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::delete('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
    });
});

// Fallback for React Router (if using client-side routing)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
