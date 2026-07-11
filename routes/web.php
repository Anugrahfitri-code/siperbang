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

// Protected API Routes
Route::middleware('auth')->prefix('api')->group(function () {
    // Stocks
    Route::get('/stocks', [\App\Http\Controllers\Api\StockController::class, 'index']);
    Route::post('/stocks/bulk', [\App\Http\Controllers\Api\StockController::class, 'bulkStore']);
    
    // Requests
    Route::get('/requests', [\App\Http\Controllers\Api\RequestController::class, 'index']);
    Route::post('/requests', [\App\Http\Controllers\Api\RequestController::class, 'store']);
    Route::put('/requests/{itemRequest}/status', [\App\Http\Controllers\Api\RequestController::class, 'updateStatus']);
    
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
