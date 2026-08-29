<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\SaleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [LoginController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/sales', [SaleController::class, 'store']);
        Route::get('/sales', [SaleController::class, 'index']);
        Route::get('/sales/{sale}/receipt.pdf', [SaleController::class, 'receiptPdf']);
        Route::post('/sales/{sale}/sms-stub', [SaleController::class, 'smsStub']);
    });
});
