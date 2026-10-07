<?php

use App\Http\Controllers\Auth\EmailCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Register page helpers (loaded by bootstrap/app.php)
|--------------------------------------------------------------------------
| The register page asks if the typed email is real while the person types.
*/

Route::get('/register/check-email', EmailCheckController::class)
    ->middleware(['guest', 'throttle:30,1'])
    ->name('register.check-email');
