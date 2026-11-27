<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('config', [MenuController::class, 'config']);
    Route::get('branches', [MenuController::class, 'branches']);
    Route::get('menu', [MenuController::class, 'menu']);
    Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
    Route::get('orders/{order}', [OrderController::class, 'show']);
});
