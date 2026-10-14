<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The notification bell (loaded by bootstrap/app.php)
|--------------------------------------------------------------------------
| The list inside the bell, for Staff, Vet/Admin and Super Admin,
| plus "Mark as read" (also when a notification is clicked) and "Mark all as read".
*/

Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/notifications', NotificationController::class)->name('notifications');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});
