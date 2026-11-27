<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/menu', [SiteController::class, 'menu'])->name('menu');
Route::get('/branches', [SiteController::class, 'branches'])->name('branches');
Route::get('/checkout', [SiteController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [SiteController::class, 'placeOrder'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/order/{order}', [SiteController::class, 'order'])->name('order.show');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [Admin\AuthController::class, 'show'])->name('login');
    Route::post('login', [Admin\AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('logout', [Admin\AuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [Admin\OrderController::class, 'index'])->name('orders');
        Route::get('orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
        Route::get('menu', [Admin\MenuController::class, 'index'])->name('menu');
        Route::get('items/create', [Admin\MenuController::class, 'create'])->name('items.create');
        Route::post('items', [Admin\MenuController::class, 'store'])->name('items.store');
        Route::get('items/{item}/edit', [Admin\MenuController::class, 'edit'])->name('items.edit');
        Route::put('items/{item}', [Admin\MenuController::class, 'update'])->name('items.update');
        Route::patch('items/{item}/toggle', [Admin\MenuController::class, 'toggle'])->name('items.toggle');
        Route::get('branches', [Admin\BranchController::class, 'index'])->name('branches');
        Route::put('branches/{branch}', [Admin\BranchController::class, 'update'])->name('branches.update');
    });
});
