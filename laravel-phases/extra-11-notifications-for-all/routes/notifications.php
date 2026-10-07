<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The notification bell (loaded by bootstrap/app.php)
|--------------------------------------------------------------------------
| The list inside the bell, for Staff, Vet/Admin and Super Admin.
*/

Route::get('/notifications', NotificationController::class)
    ->middleware(['auth', 'throttle:60,1'])
    ->name('notifications');
