<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('config', [MenuController::class, 'config']);
    Route::get('branches', [MenuController::class, 'branches']);
    Route::get('menu', [MenuController::class, 'menu']);
    Route::post('orders', [OrderController::class, 'store'])->middleware(['throttle:10,1', 'customer:optional']);
    Route::get('orders/{order}', [OrderController::class, 'show']);

    // customer accounts: sign in with a mobile number and a one-time code
    Route::post('auth/otp', [AuthController::class, 'requestCode'])->middleware('throttle:10,1');
    Route::post('auth/verify', [AuthController::class, 'verify'])->middleware('throttle:20,1');

    Route::middleware('customer')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AccountController::class, 'show']);
        Route::patch('me', [AccountController::class, 'update']);
        Route::delete('me', [AccountController::class, 'destroy']);
        Route::get('me/orders', [AccountController::class, 'orders']);
    });
});
