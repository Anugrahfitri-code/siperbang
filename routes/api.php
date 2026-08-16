<?php

use App\Http\Controllers\Api\BonRecapController;
use App\Http\Controllers\Api\InventoryCodeController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\ReceiptDocumentController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StokUploadController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/api/settings', [SiteSettingController::class, 'index']);

// Auth Routes
Route::post('/api/login', function (Request $request) {
    $credentials = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $throttleKey = Str::transliterate(Str::lower($credentials['username']).'|'.$request->ip());

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);

        return response()->json([
            'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.$seconds.' detik.',
        ], 429);
    }

    $credentials['status'] = 'Aktif';

    if (Auth::attempt($credentials)) {
        RateLimiter::clear($throttleKey);

        $request->session()->regenerate();

        return response()->json(['message' => 'Login successful', 'user' => Auth::user()]);
    }

    RateLimiter::hit($throttleKey, 60);

    return response()->json(['message' => 'Password atau username salah, mohon coba lagi.'], 401);
});

Route::post('/api/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Logout successful']);
});

// Protected API Routes
Route::middleware(['auth', 'active'])->prefix('api')->group(function () {
    // ---- Semua Authenticated User ----
    // Authenticated User Info
    Route::get('/user', function (Request $request) {
        return response()->json($request->user());
    });

    // Requests
    Route::get('/requests', [RequestController::class, 'index']);
    Route::get('/requests/bon', [RequestController::class, 'indexBons']);
    Route::get('/requests/bon/{id}', [RequestController::class, 'showBon']);

    // Logs
    Route::get('/logs', [LogController::class, 'index']);
    Route::post('/logs', [LogController::class, 'store']);

    // Stock search — read-only, accessible by all authenticated roles
    Route::get('/stocks/search', [StockController::class, 'search']);

    Route::get('/stok-upload/riwayat', [StokUploadController::class, 'apiRiwayat'])->name('api.stok-upload.riwayat');
    Route::get('/stok-upload/stats', [StokUploadController::class, 'apiStats'])->name('api.stok-upload.stats');
    Route::get('/stok-upload/{id}/stepper-api', [StokUploadController::class, 'apiStepper']);
    Route::post('/stok-upload/{id}/verifikasi-api', [StokUploadController::class, 'apiSaveVerifikasi'])->middleware('throttle:stock-import');
    Route::post('/stok-upload/{id}/finalisasi-api', [StokUploadController::class, 'apiFinalisasi'])->middleware('throttle:stock-import');

    // ---- Ketua Tim & Superadmin ----
    Route::middleware('role:Ketua Tim,Ketua Tim Kerja,Superadmin')->group(function () {
        Route::post('/requests', [RequestController::class, 'store']);
        Route::put('/requests/bon/{id}', [RequestController::class, 'updateDraft']);
        Route::delete('/requests/bon/{id}', [RequestController::class, 'destroyDraft']);
    });

    // ---- Petugas Persediaan & Superadmin ----
    Route::middleware('role:Petugas Persediaan,Superadmin')->group(function () {
        // Stocks
        Route::get('/stocks', [StockController::class, 'index']);
        Route::post('/stocks/bulk', [StockController::class, 'bulkStore'])->middleware('throttle:stock-upload');

        // Preview rekap pengadaan
        Route::get(
            '/requests/recap/procurement',
            [
                BonRecapController::class,
                'procurementPreview',
            ]
        )->middleware('throttle:pdf-export');

        // Request Actions
        Route::put('/requests/{itemRequest}/status', [RequestController::class, 'updateStatus']);
        Route::post('/requests/{itemRequest}/distribute', [RequestController::class, 'distribute']);
        Route::post('/requests/{itemRequest}/procure', [RequestController::class, 'procure']);
        Route::post('/requests/{itemRequest}/procurements/{procurement}/complete', [RequestController::class, 'completeProcurement']);
        Route::post('/requests/{itemRequest}/reject', [RequestController::class, 'rejectItem']);
        Route::post('/requests/{itemRequest}/complete-partial', [RequestController::class, 'completePartial']);

        // Official inventory codes: only category 1.01.03
        Route::get(
            '/inventory-codes',
            [
                InventoryCodeController::class,
                'index',
            ]
        );

        // Receipts
        Route::get(
            '/receipts',
            [
                ReceiptController::class,
                'index',
            ]
        );

        Route::post(
            '/receipts',
            [
                ReceiptController::class,
                'store',
            ]
        );

        Route::post(
            '/receipts/export-excel',
            [
                ReceiptController::class,
                'exportExcel',
            ]
        )->middleware('throttle:excel-export');
        Route::get('/receipt-documents', [ReceiptDocumentController::class, 'index']);
        Route::post('/receipt-documents', [ReceiptDocumentController::class, 'store'])->middleware('throttle:receipt-ocr');
        Route::get(
            '/receipt-documents/{receiptDocument}',
            [
                ReceiptDocumentController::class,
                'show',
            ]
        );

        Route::get(
            '/receipt-documents/{receiptDocument}/file',
            [
                ReceiptDocumentController::class,
                'file',
            ]
        );

        Route::put(
            '/receipt-documents/{receiptDocument}/draft',
            [
                ReceiptDocumentController::class,
                'saveDraft',
            ]
        );

        Route::put(
            '/receipt-documents/{receiptDocument}/verify',
            [
                ReceiptDocumentController::class,
                'verify',
            ]
        );
        Route::put('/receipts/{receipt}/unverify', [ReceiptController::class, 'unverify']);
        Route::put('/receipts/{receipt}/items', [ReceiptController::class, 'updateItems']);

        Route::post('/receipt-documents/{receiptDocument}/retry', [ReceiptDocumentController::class, 'retry'])->middleware('throttle:receipt-ocr');
        Route::delete('/receipt-documents/{receiptDocument}', [ReceiptDocumentController::class, 'destroy']);

        // Export
        Route::get('/export-excel', [LogController::class, 'exportExcel']);
    });

    // ---- Superadmin Only ----
    Route::middleware('role:Superadmin')->group(function () {
        // Site identity and versioned branding
        Route::post('/settings', [SiteSettingController::class, 'update']);
        Route::get('/settings/versions', [SiteSettingController::class, 'versions']);
        Route::post('/settings/versions', [SiteSettingController::class, 'store']);
        Route::post('/settings/versions/{brandingVersion}', [SiteSettingController::class, 'updateVersion']);
        Route::post('/settings/versions/{brandingVersion}/publish', [SiteSettingController::class, 'publish']);
        Route::post('/settings/versions/{brandingVersion}/rollback', [SiteSettingController::class, 'rollback']);
        Route::delete('/settings/versions/{brandingVersion}', [SiteSettingController::class, 'destroy']);

        // BON recap PDF - Superadmin only
        Route::get(
            '/requests/recap/pdf',
            [
                BonRecapController::class,
                'exportPdf',
            ]
        )->middleware('throttle:pdf-export');

        // Users
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
