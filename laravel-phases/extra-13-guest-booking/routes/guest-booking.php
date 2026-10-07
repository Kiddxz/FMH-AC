<?php

use App\Http\Controllers\Guest\GuestBookingController;
use App\Http\Controllers\SlotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Booking without an account (loaded by bootstrap/app.php)
|--------------------------------------------------------------------------
| For pet owners who do not want to register. Their information is saved like a walk-in
| customer. A logged-in user who opens these pages is sent to their own dashboard.
*/

Route::middleware('guest')->group(function () {
    Route::get('/book', [GuestBookingController::class, 'create'])->name('guest.book');
    Route::post('/book', [GuestBookingController::class, 'store'])->middleware('throttle:10,1')->name('guest.book.store');
    Route::get('/book/slots', SlotController::class)->middleware('throttle:60,1')->name('guest.book.slots');
    Route::get('/book/done', [GuestBookingController::class, 'done'])->name('guest.book.done');

    // Check or cancel a booking with the reference number and the mobile number
    Route::get('/book/check', [GuestBookingController::class, 'lookupForm'])->name('guest.lookup');
    Route::post('/book/check', [GuestBookingController::class, 'lookup'])->middleware('throttle:10,1')->name('guest.lookup.check');
    Route::get('/book/my-booking', [GuestBookingController::class, 'show'])->name('guest.lookup.show');
    Route::post('/book/my-booking/cancel', [GuestBookingController::class, 'cancel'])->middleware('throttle:10,1')->name('guest.lookup.cancel');
});
