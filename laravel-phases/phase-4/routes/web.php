<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FMH Animal Clinic - Web Routes (PHASE 4: role-based access)
|--------------------------------------------------------------------------
|
| Each line connects a web address to a Blade page.
| Example:  Route::view('/portal/pets', 'customer.pets.index')
|           -> opening /portal/pets shows resources/views/customer/pets/index.blade.php
|
| ->name('...') gives the address a nickname. The pages use route('nickname')
| to build links, so if an address changes we only edit it here.
|
| ACCESS RULES (Phase 4):
|   'auth'               = must be logged in
|   'role:staff'         = only that role may open the pages of the area
|   'can:inventory.view' = the role must also have that permission
|                          (Super Admin can change permissions in Phase 8)
| Most pages still show sample data; real data comes in the next phases.
|
*/


// ======================================================================
// PUBLIC PAGES
// ======================================================================

Route::view('/', 'home')->name('home');  // home.html

// The old separate login pages now go to the single login page (decision P15).
Route::redirect('/admin/login', '/login');       // adminlogin.html
Route::redirect('/staff/login', '/login');       // assistantlogin.html
Route::redirect('/superadmin/login', '/login');  // superadminlogin.html

// ======================================================================
// LOGIN, REGISTER, FORGOT PASSWORD  (PHASE 3)
// 'guest' = only for people who are NOT logged in.
// 'throttle:login' etc. = limits how many times per minute a form can be sent
//                          (stops spam and guessing; the limits are in AppServiceProvider).
// ======================================================================

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');  // login.html
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:login')->name('login.attempt');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');  // register.html
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:auth-forms')->name('register.store');
    Route::get('/register/verify', [RegisterController::class, 'showVerify'])->name('register.verify');
    Route::post('/register/verify', [RegisterController::class, 'verify'])->middleware('throttle:auth-forms')->name('register.verify.check');
    Route::post('/register/resend', [RegisterController::class, 'resend'])->middleware('throttle:send-code')->name('register.resend');

    Route::get('/forgot-password', [PasswordResetController::class, 'show'])->name('password.request');  // forgotpass.html
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:send-code')->name('password.email');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:auth-forms')->name('password.update');
});

// Logging out is a POST form (with @csrf), so another website cannot log you out with a link.
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

// ======================================================================
// EVERYTHING BELOW NEEDS A LOGIN ('auth') AND THE RIGHT ROLE ('role:...')
// A wrong role gets the 403 "Access Denied" page (resources/views/errors/403.blade.php).
// ======================================================================

Route::middleware('auth')->group(function () {

    // ======================================================================
    // CUSTOMER / PET OWNER PORTAL  (old: dashboard.html, mypets.html, ...)
    // ======================================================================

    Route::middleware('role:customer')->group(function () {
        Route::view('/portal', 'customer.dashboard')->name('portal.dashboard');  // dashboard.html
        Route::view('/portal/profile', 'customer.profile')->name('portal.profile');  // profile.html
        Route::view('/portal/pets', 'customer.pets.index')->name('portal.pets.index');  // mypets.html
        Route::view('/portal/pets/create', 'customer.pets.create')->name('portal.pets.create');  // addpet.html
        Route::view('/portal/appointments/create', 'customer.appointments.create')->middleware('can:appointments.book')->name('portal.appointments.create');  // appointment.html
        Route::view('/portal/appointments', 'customer.appointments.index')->name('portal.appointments.index');  // history.html

        // Temporary form handlers (replaced in Phase 5 and Phase 9).
        Route::post('/portal/pets', fn () => back()->with('phase_notice', 'Saving pets will work in Phase 5 (Customer module).'))->name('portal.pets.store');
        Route::post('/portal/appointments', fn () => back()->with('phase_notice', 'Booking will be saved in Phase 9 (Appointments).'))->middleware('can:appointments.book')->name('portal.appointments.store');
    });

    // ======================================================================
    // STAFF / RECEPTIONIST  (old: assistant*.html)
    // ======================================================================

    Route::middleware('role:staff')->group(function () {
        Route::view('/staff', 'staff.dashboard')->name('staff.dashboard');  // assistantdashboard.html
        Route::view('/staff/appointments', 'staff.appointments.index')->middleware('can:appointments.view')->name('staff.appointments.index');  // assistantappointments.html
        Route::view('/staff/appointments/{appointment}', 'staff.appointments.show')->middleware('can:appointments.view')->name('staff.appointments.show');  // assistantappointmentdetails.html
        Route::view('/staff/pets', 'staff.pets.index')->middleware('can:pets.view')->name('staff.pets.index');  // assistantpets.html
        Route::view('/staff/pets/{pet}', 'staff.pets.show')->middleware('can:pets.view')->name('staff.pets.show');  // assistantpetrecords.html
        Route::view('/staff/profile', 'staff.profile')->name('staff.profile');  // assistantprofile.html
        Route::view('/staff/logout', 'staff.logout')->name('staff.logout');  // assistantlogout.html
    });

    // ======================================================================
    // VETERINARIAN / ADMIN  (old: admin*.html)
    // ======================================================================

    Route::middleware('role:vet_admin')->group(function () {
        Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');  // admindashboard.html
        Route::view('/admin/appointments', 'admin.appointments.index')->middleware('can:appointments.view')->name('admin.appointments.index');  // adminappointments.html
        Route::view('/admin/appointments/create', 'admin.appointments.create')->middleware('can:appointments.manage')->name('admin.appointments.create');  // newappointment.html
        Route::view('/admin/appointments/{appointment}', 'admin.appointments.show')->middleware('can:appointments.view')->name('admin.appointments.show');  // adminappointmentdetails.html
        Route::view('/admin/appointments/{appointment}/edit', 'admin.appointments.edit')->middleware('can:appointments.manage')->name('admin.appointments.edit');  // editappointment.html
        Route::view('/admin/pets', 'admin.pets.index')->middleware('can:pets.view')->name('admin.pets.index');  // adminpets.html
        Route::view('/admin/pet-records', 'admin.records.index')->middleware('can:records.view')->name('admin.records.index');  // adminpetrecords.html
        Route::view('/admin/customers', 'admin.customers.index')->middleware('can:customers.view')->name('admin.customers.index');  // adminusers.html
        Route::view('/admin/services', 'admin.services.index')->middleware('can:services.manage')->name('admin.services.index');  // adminservices.html
        Route::view('/admin/transactions', 'admin.transactions.index')->middleware('can:transactions.view')->name('admin.transactions.index');  // adminpayments.html
        Route::view('/admin/inventory', 'admin.inventory.index')->middleware('can:inventory.view')->name('admin.inventory.index');  // admininventory.html
        Route::view('/admin/reports', 'admin.reports.index')->middleware('can:reports.view')->name('admin.reports.index');  // adminreports.html
        Route::view('/admin/profile', 'admin.profile')->name('admin.profile');  // adminprofile.html
        Route::view('/admin/logout', 'admin.logout')->name('admin.logout');  // adminlogout.html
    });

    // ======================================================================
    // SUPER ADMIN  (old: superadmin*.html)
    // Clinic pages here are READ-ONLY oversight (decision P1).
    // ======================================================================

    Route::middleware('role:super_admin')->group(function () {
        Route::view('/superadmin', 'superadmin.dashboard')->name('superadmin.dashboard');  // superadmindashboard.html
        Route::view('/superadmin/users', 'superadmin.users.index')->middleware('can:users.manage')->name('superadmin.users.index');  // superadminusers.html
        Route::view('/superadmin/appointments', 'superadmin.appointments')->middleware('can:appointments.view')->name('superadmin.appointments');  // superadminappointments.html
        Route::view('/superadmin/pet-records', 'superadmin.pet-records')->middleware('can:pets.view')->name('superadmin.pet-records');  // superadminpetrecords.html
        Route::view('/superadmin/waivers', 'superadmin.waivers')->middleware('can:waivers.view')->name('superadmin.waivers');  // superadminwaivers.html
        Route::view('/superadmin/sales', 'superadmin.sales')->middleware('can:transactions.view')->name('superadmin.sales');  // superadminpos.html
        Route::view('/superadmin/transactions', 'superadmin.transactions')->middleware('can:transactions.view')->name('superadmin.transactions');  // superadmintransactions.html
        Route::view('/superadmin/inventory', 'superadmin.inventory')->middleware('can:inventory.view')->name('superadmin.inventory');  // superadmininventory.html
        Route::view('/superadmin/reports', 'superadmin.reports')->middleware('can:reports.view')->name('superadmin.reports');  // superadminreports.html
        Route::view('/superadmin/activity-logs', 'superadmin.activity-logs')->middleware('can:activity_logs.view')->name('superadmin.activity-logs');  // superadminactivity.html
        Route::view('/superadmin/backups', 'superadmin.backups')->middleware('can:backups.manage')->name('superadmin.backups');  // superadminbackup.html
        Route::view('/superadmin/settings', 'superadmin.settings')->middleware('can:settings.manage')->name('superadmin.settings');  // superadminsettings.html
        Route::view('/superadmin/logout', 'superadmin.logout')->name('superadmin.logout');  // superadminlogout.html
    });

});
