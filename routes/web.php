<?php

use App\Http\Controllers\Auth\PhoneAuthController;
use App\Http\Controllers\Consumer\CatalogController;
use App\Http\Controllers\Consumer\CheckoutController;
use App\Http\Controllers\Consumer\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Vendor\InventoryController;
use App\Http\Controllers\Vendor\OrderController;
use App\Http\Middleware\EnsureUserIsVendor;
use Illuminate\Support\Facades\Route;

// Modal Fast Phone Authentication & Verification Routes
Route::post('/auth/phone-login', [PhoneAuthController::class, 'login'])->name('auth.phone-login');
Route::post('/auth/phone-register', [PhoneAuthController::class, 'register'])->name('auth.phone-register');
Route::post('/auth/phone-verify-otp', [PhoneAuthController::class, 'verifyOtp'])->name('auth.phone-verify-otp');
Route::post('/auth/phone-resend-otp', [PhoneAuthController::class, 'resendOtp'])->name('auth.phone-resend-otp');

use App\Http\Controllers\Consumer\DeliveryLocationController;

// Consumer Experience Routes
Route::get('/', [CatalogController::class, 'index'])->name('consumer.catalog');
Route::get('/party-bundles', [CatalogController::class, 'partyBundles'])->name('consumer.party-bundles');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('consumer.checkout');
Route::get('/orders/{order}', [CheckoutController::class, 'showOrder'])->name('consumer.orders.show');
Route::patch('/orders/{order}/cancel', [CheckoutController::class, 'cancelOrder'])->name('consumer.orders.cancel');

Route::get('/dashboard', function () {
    $user = request()->user();
    if ($user && $user->isVendor()) {
        return redirect()->route('vendor.inventory.index');
    }

    return app(DashboardController::class)->index(request());
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/orders/{order}/reorder', [CheckoutController::class, 'reorder'])->name('consumer.orders.reorder');
    Route::post('/consumer/delivery-locations', [DeliveryLocationController::class, 'store'])->name('consumer.delivery-locations.store');
    Route::delete('/consumer/delivery-locations/{deliveryLocation}', [DeliveryLocationController::class, 'destroy'])->name('consumer.delivery-locations.destroy');
    Route::patch('/consumer/delivery-locations/{deliveryLocation}/default', [DeliveryLocationController::class, 'setDefault'])->name('consumer.delivery-locations.set-default');

    Route::patch('/consumer/profile', [DashboardController::class, 'updateProfile'])->name('consumer.profile.update');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Vendor Protected Routes
Route::middleware(['auth', EnsureUserIsVendor::class])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/global', [InventoryController::class, 'storeGlobal'])->name('inventory.store-global');
        Route::post('/inventory/custom', [InventoryController::class, 'storeCustom'])->name('inventory.store-custom');
        Route::patch('/inventory/{product}/stock', [InventoryController::class, 'updateStock'])->name('inventory.update-stock');
        Route::patch('/inventory/{product}/chilled', [InventoryController::class, 'toggleChilled'])->name('inventory.toggle-chilled');
        Route::put('/inventory/{product}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{product}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

        // Orders Feed & Daily Bookkeeping Digest
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/rider', [OrderController::class, 'riderIndex'])->name('orders.rider');
        Route::get('/orders/export-csv', [OrderController::class, 'exportCsv'])->name('orders.export-csv');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
    });

require __DIR__.'/auth.php';
