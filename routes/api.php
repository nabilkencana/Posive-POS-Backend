<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ShiftController;
use App\Http\Controllers\Api\V1\TableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes - Posive POS System
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Public Authentication Endpoint
    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected API Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth Management
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Shift Management
        Route::prefix('shifts')->group(function () {
            Route::post('open', [ShiftController::class, 'open']);
            Route::post('close', [ShiftController::class, 'close']);
            Route::get('current', [ShiftController::class, 'current']);
        });

        // Orders & Checkout Processing
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::post('/', [OrderController::class, 'store']);
            Route::get('{id}', [OrderController::class, 'show']);
            Route::post('{id}/refund', [OrderController::class, 'refund']);
        });

        // Products & Inventory Management
        Route::get('products', [ProductController::class, 'index']);
        Route::post('products', [ProductController::class, 'store']);
        Route::get('categories', [ProductController::class, 'categories']);
        Route::patch('products/{id}/stock', [ProductController::class, 'updateStock']);

        // Table Management
        Route::get('tables', [TableController::class, 'index']);
        Route::patch('tables/{id}/status', [TableController::class, 'updateStatus']);
    });
});
