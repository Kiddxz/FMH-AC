<?php

use App\Http\Controllers\OnlinePaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Online payment with PayMongo (loaded by bootstrap/app.php)
|--------------------------------------------------------------------------
| Staff make a "pay link" for a saved bill. The customer opens it (no account needed),
| sees the services and the amount, and pays online with GCash, Maya or card.
*/

// Staff (cashier): make the pay link, ask PayMongo if it was paid
Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::post('/staff/transactions/{transaction}/pay-link', [OnlinePaymentController::class, 'createLink'])
        ->whereNumber('transaction')->middleware('can:pos.manage')->name('staff.transactions.pay-link');
    Route::post('/staff/transactions/{transaction}/pay-check', [OnlinePaymentController::class, 'check'])
        ->whereNumber('transaction')->middleware(['can:pos.manage', 'throttle:20,1'])->name('staff.transactions.pay-check');
});

// Customer (no login): the bill page and the PayMongo button
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/pay/{token}', [OnlinePaymentController::class, 'show'])->name('pay.show');
    Route::post('/pay/{token}', [OnlinePaymentController::class, 'checkout'])->name('pay.checkout');
    Route::get('/pay/{token}/done', [OnlinePaymentController::class, 'done'])->name('pay.done');
});
