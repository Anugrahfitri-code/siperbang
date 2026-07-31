<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/api/settings', [\App\Http\Controllers\Api\SiteSettingController::class, 'index']);

// Authenticated User Info
Route::get('/api/user', function (Request $request) {
    if (Auth::check()) {
        $user = Auth::user();
        if (strtolower($user->status) === 'nonaktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Akun Anda tidak aktif.'], 403);
        }
        return response()->json($user);
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
        $user = Auth::user();
        if (strtolower($user->status) === 'nonaktif') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return response()->json(['message' => 'Akun Anda tidak aktif. Silakan hubungi Administrator.'], 403);
        }

        $request->session()->regenerate();
        return response()->json(['message' => 'Login successful', 'user' => $user]);
    }

    return response()->json(['message' => 'Kredensial tidak valid'], 401);
});

Route::post('/api/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['message' => 'Logout successful']);
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

    Route::get('/stok-upload/riwayat', [\App\Http\Controllers\Api\StokUploadController::class, 'apiRiwayat'])->name('api.stok-upload.riwayat');
    Route::get('/stok-upload/stats', [\App\Http\Controllers\Api\StokUploadController::class, 'apiStats'])->name('api.stok-upload.stats');
    Route::get('/stok-upload/{id}/stepper-api', [\App\Http\Controllers\Api\StokUploadController::class, 'apiStepper']);
    Route::post('/stok-upload/{id}/verifikasi-api', [\App\Http\Controllers\Api\StokUploadController::class, 'apiSaveVerifikasi']);
    Route::post('/stok-upload/{id}/finalisasi-api', [\App\Http\Controllers\Api\StokUploadController::class, 'apiFinalisasi']);

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
        Route::post('/requests/{itemRequest}/procurements/{procurement}/complete', [\App\Http\Controllers\Api\RequestController::class, 'completeProcurement']);
        Route::post('/requests/{itemRequest}/reject', [\App\Http\Controllers\Api\RequestController::class, 'rejectItem']);
        Route::post('/requests/{itemRequest}/complete-partial', [\App\Http\Controllers\Api\RequestController::class, 'completePartial']);
        
        // Official inventory codes: only category 1.01.03
        Route::get(
            '/inventory-codes',
            [
                \App\Http\Controllers\Api\InventoryCodeController::class,
                'index',
            ]
        );

        // Receipts
        Route::get(
            '/receipts',
            [
                \App\Http\Controllers\Api\ReceiptController::class,
                'index',
            ]
        );

        Route::post(
            '/receipts',
            [
                \App\Http\Controllers\Api\ReceiptController::class,
                'store',
            ]
        );

        Route::post(
            '/receipts/export-excel',
            [
                \App\Http\Controllers\Api\ReceiptController::class,
                'exportExcel',
            ]
        );
        Route::get('/receipt-documents', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'index']);
        Route::post('/receipt-documents', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'store']);
        Route::get(
            '/receipt-documents/{receiptDocument}',
            [
                \App\Http\Controllers\Api\ReceiptDocumentController::class,
                'show',
            ]
        );

        Route::get(
            '/receipt-documents/{receiptDocument}/file',
            [
                \App\Http\Controllers\Api\ReceiptDocumentController::class,
                'file',
            ]
        );

        Route::put(
            '/receipt-documents/{receiptDocument}/draft',
            [
                \App\Http\Controllers\Api\ReceiptDocumentController::class,
                'saveDraft',
            ]
        );

        Route::put(
            '/receipt-documents/{receiptDocument}/verify',
            [
                \App\Http\Controllers\Api\ReceiptDocumentController::class,
                'verify',
            ]
        );
        Route::put('/receipts/{receipt}/unverify', [\App\Http\Controllers\Api\ReceiptController::class, 'unverify']);
        Route::put('/receipts/{receipt}/items', [\App\Http\Controllers\Api\ReceiptController::class, 'updateItems']);

        Route::post('/receipt-documents/{receiptDocument}/retry', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'retry']);
        Route::delete('/receipt-documents/{receiptDocument}', [\App\Http\Controllers\Api\ReceiptDocumentController::class, 'destroy']);
        
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
