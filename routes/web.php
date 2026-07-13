<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\StokPengadaanController;

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


Route::prefix('stok-pengadaan')->group(function () {

    Route::get('/', [StokPengadaanController::class, 'index'])
        ->name('stok-pengadaan.index');

    Route::get('/{id}', [StokPengadaanController::class, 'cekStok'])
        ->name('stok-pengadaan.cek');

    Route::post('/{id}/distribusi', [StokPengadaanController::class, 'prosesDistribusi'])
        ->name('stok-pengadaan.distribusi');

    Route::post('/{id}/pengadaan', [StokPengadaanController::class, 'prosesPengadaan'])
        ->name('stok-pengadaan.pengadaan');

});

Route::middleware('auth')->group(function () {
    // Stok Upload Module
    Route::get('/stok-upload', [\App\Http\Controllers\StokUploadController::class, 'index'])->name('stok-upload.index');
    Route::post('/stok-upload', [\App\Http\Controllers\StokUploadController::class, 'upload'])->name('stok-upload.store');
    Route::get('/stok-upload/template', [\App\Http\Controllers\StokUploadController::class, 'downloadTemplate'])->name('stok-upload.template');
    Route::get('/stok-upload/riwayat', [\App\Http\Controllers\StokUploadController::class, 'riwayat'])->name('stok-upload.riwayat');
    Route::get('/stok-upload/{id}/preview', [\App\Http\Controllers\StokUploadController::class, 'preview'])->name('stok-upload.preview');
    Route::post('/stok-upload/{id}/finalisasi', [\App\Http\Controllers\StokUploadController::class, 'finalisasi'])->name('stok-upload.finalisasi');

    // Verifikasi Kode Persediaan
    Route::get('/stok-upload/{id}/verifikasi', [\App\Http\Controllers\VerifikasiKodePersediaanController::class, 'verifikasi'])->name('stok-upload.verifikasi.index');
    Route::post('/stok-upload/{id}/verifikasi', [\App\Http\Controllers\VerifikasiKodePersediaanController::class, 'postVerifikasi'])->name('stok-upload.verifikasi.store');

    // Perbaiki Data (user edits invalid rows)
    Route::get('/stok-upload/{id}/perbaiki', [\App\Http\Controllers\PerbaikiDataController::class, 'index'])->name('stok-upload.perbaiki.index');
    Route::post('/stok-upload/{id}/perbaiki', [\App\Http\Controllers\PerbaikiDataController::class, 'store'])->name('stok-upload.perbaiki.store');
    Route::post('/stok-upload/{id}/ajukan-ulang', [\App\Http\Controllers\PerbaikiDataController::class, 'ajukanUlang'])->name('stok-upload.ajukan-ulang');

    // Master Barang
    Route::get('/master-barang', [\App\Http\Controllers\BarangController::class, 'index'])->name('master-barang.index');
    Route::get('/master-barang/search', [\App\Http\Controllers\BarangController::class, 'search'])->name('master-barang.search');
});

// Protected API Routes
Route::middleware('auth')->prefix('api')->group(function () {
    // Stocks
    Route::get('/stocks', [\App\Http\Controllers\Api\StockController::class, 'index']);
    Route::post('/stocks/bulk', [\App\Http\Controllers\Api\StockController::class, 'bulkStore']);
    
    // Requests
    Route::get('/requests', [\App\Http\Controllers\Api\RequestController::class, 'index']);
    Route::post('/requests', [\App\Http\Controllers\Api\RequestController::class, 'store']);
    Route::put('/requests/{itemRequest}/status', [\App\Http\Controllers\Api\RequestController::class, 'updateStatus']);
    Route::post('/requests/{itemRequest}/distribute', [\App\Http\Controllers\Api\RequestController::class, 'distribute']);
    Route::post('/requests/{itemRequest}/procure', [\App\Http\Controllers\Api\RequestController::class, 'procure']);
    Route::post('/requests/{itemRequest}/complete-procurement', [\App\Http\Controllers\Api\RequestController::class, 'completeProcurement']);
    
    // Receipts
    Route::get('/receipts', [\App\Http\Controllers\Api\ReceiptController::class, 'index']);
    Route::post('/receipts', [\App\Http\Controllers\Api\ReceiptController::class, 'store']);
    Route::put('/receipts/{receipt}', [\App\Http\Controllers\Api\ReceiptController::class, 'update']);
    
    // Logs
    Route::get('/logs', [\App\Http\Controllers\Api\LogController::class, 'index']);
    Route::post('/logs', [\App\Http\Controllers\Api\LogController::class, 'store']);
    
    // Users
    Route::get('/users', [\App\Http\Controllers\Api\UserController::class, 'index']);
    Route::post('/users', [\App\Http\Controllers\Api\UserController::class, 'store']);
    Route::put('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'update']);
    Route::delete('/users/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
});

// Fallback for React Router (if using client-side routing)
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
