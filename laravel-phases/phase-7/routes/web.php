<?php

use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\PetController as AdminPetController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\PetController as CustomerPetController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Staff\CustomerController as StaffCustomerController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\PetController as StaffPetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FMH Animal Clinic - Web Routes (PHASE 7: veterinarian / admin)
|--------------------------------------------------------------------------
|
| Each line connects a web address to a page or to a controller.
| Example:  Route::get('/portal/pets', [CustomerPetController::class, 'index'])
|           -> opening /portal/pets runs the index() code in app/Http/Controllers/Customer/PetController.php
| Route::view(...) lines still show a page with sample data (made real in later phases).
|
| ->name('...') gives the address a nickname. The pages use route('nickname')
| to build links, so if an address changes we only edit it here.
|
| ACCESS RULES (Phase 4):
|   'auth'               = must be logged in
|   'role:staff'         = only that role may open the pages of the area
|   'can:inventory.view' = the role must also have that permission
|                          (Super Admin can change permissions in Phase 8)
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
        Route::get('/portal', CustomerDashboardController::class)->name('portal.dashboard');  // dashboard.html

        // Profile (Phase 5)
        Route::get('/portal/profile', [CustomerProfileController::class, 'show'])->name('portal.profile');  // profile.html
        Route::get('/portal/profile/edit', [CustomerProfileController::class, 'edit'])->name('portal.profile.edit');
        Route::put('/portal/profile', [CustomerProfileController::class, 'update'])->name('portal.profile.update');
        Route::put('/portal/profile/password', [CustomerProfileController::class, 'updatePassword'])->name('portal.password.update');

        // My Pets (Phase 5). {pet} = the pet's id number; PetPolicy checks the owner.
        Route::get('/portal/pets', [CustomerPetController::class, 'index'])->name('portal.pets.index');  // mypets.html
        Route::get('/portal/pets/create', [CustomerPetController::class, 'create'])->name('portal.pets.create');  // addpet.html
        Route::post('/portal/pets', [CustomerPetController::class, 'store'])->name('portal.pets.store');
        Route::get('/portal/pets/{pet}', [CustomerPetController::class, 'show'])->whereNumber('pet')->name('portal.pets.show');
        Route::get('/portal/pets/{pet}/edit', [CustomerPetController::class, 'edit'])->whereNumber('pet')->name('portal.pets.edit');
        Route::put('/portal/pets/{pet}', [CustomerPetController::class, 'update'])->whereNumber('pet')->name('portal.pets.update');

        // Appointments (real booking comes in Phase 9)
        Route::view('/portal/appointments/create', 'customer.appointments.create')->middleware('can:appointments.book')->name('portal.appointments.create');  // appointment.html
        Route::view('/portal/appointments', 'customer.appointments.index')->name('portal.appointments.index');  // history.html
        Route::post('/portal/appointments', fn () => back()->with('phase_notice', 'Booking will be saved in Phase 9 (Appointments).'))->middleware('can:appointments.book')->name('portal.appointments.store');
    });

    // ======================================================================
    // STAFF / RECEPTIONIST  (old: assistant*.html; "Assistant" is now "Staff")
    // ======================================================================

    Route::middleware('role:staff')->group(function () {
        Route::get('/staff', StaffDashboardController::class)->name('staff.dashboard');  // assistantdashboard.html

        // Appointments (made real in Phase 9)
        Route::view('/staff/appointments', 'staff.appointments.index')->middleware('can:appointments.view')->name('staff.appointments.index');  // assistantappointments.html
        Route::view('/staff/appointments/{appointment}', 'staff.appointments.show')->middleware('can:appointments.view')->name('staff.appointments.show');  // assistantappointmentdetails.html

        // Customer directory (Phase 6)
        Route::get('/staff/customers', [StaffCustomerController::class, 'index'])->middleware('can:customers.view')->name('staff.customers.index');
        Route::get('/staff/customers/{customer}', [StaffCustomerController::class, 'show'])->whereNumber('customer')->middleware('can:customers.view')->name('staff.customers.show');
        Route::get('/staff/customers/{customer}/edit', [StaffCustomerController::class, 'edit'])->whereNumber('customer')->middleware('can:customers.manage')->name('staff.customers.edit');
        Route::put('/staff/customers/{customer}', [StaffCustomerController::class, 'update'])->whereNumber('customer')->middleware('can:customers.manage')->name('staff.customers.update');

        // Pets (Phase 6). PetPolicy checks pets.view / pets.manage.
        Route::get('/staff/pets', [StaffPetController::class, 'index'])->middleware('can:pets.view')->name('staff.pets.index');  // assistantpets.html
        Route::get('/staff/pets/{pet}', [StaffPetController::class, 'show'])->whereNumber('pet')->name('staff.pets.show');  // assistantpetrecords.html
        Route::get('/staff/pets/{pet}/edit', [StaffPetController::class, 'edit'])->whereNumber('pet')->name('staff.pets.edit');
        Route::put('/staff/pets/{pet}', [StaffPetController::class, 'update'])->whereNumber('pet')->name('staff.pets.update');

        // My Profile (Phase 6)
        Route::get('/staff/profile', [ProfileController::class, 'show'])->name('staff.profile');  // assistantprofile.html
        Route::get('/staff/profile/edit', [ProfileController::class, 'edit'])->name('staff.profile.edit');
        Route::put('/staff/profile', [ProfileController::class, 'update'])->name('staff.profile.update');
        Route::put('/staff/profile/password', [ProfileController::class, 'updatePassword'])->name('staff.password.update');

        Route::view('/staff/logout', 'staff.logout')->name('staff.logout');  // assistantlogout.html
    });

    // ======================================================================
    // VETERINARIAN / ADMIN  (old: admin*.html)
    // ======================================================================

    Route::middleware('role:vet_admin')->group(function () {
        Route::get('/admin', AdminDashboardController::class)->name('admin.dashboard');  // admindashboard.html

        // Appointments (made real in Phase 9)
        Route::view('/admin/appointments', 'admin.appointments.index')->middleware('can:appointments.view')->name('admin.appointments.index');  // adminappointments.html
        Route::view('/admin/appointments/create', 'admin.appointments.create')->middleware('can:appointments.manage')->name('admin.appointments.create');  // newappointment.html
        Route::view('/admin/appointments/{appointment}', 'admin.appointments.show')->middleware('can:appointments.view')->name('admin.appointments.show');  // adminappointmentdetails.html
        Route::view('/admin/appointments/{appointment}/edit', 'admin.appointments.edit')->middleware('can:appointments.manage')->name('admin.appointments.edit');  // editappointment.html

        // Pets (Phase 7) and pet records (made real in Phase 10)
        Route::get('/admin/pets', [AdminPetController::class, 'index'])->middleware('can:pets.view')->name('admin.pets.index');  // adminpets.html
        Route::get('/admin/pets/{pet}', [AdminPetController::class, 'show'])->whereNumber('pet')->name('admin.pets.show');
        Route::view('/admin/pet-records', 'admin.records.index')->middleware('can:records.view')->name('admin.records.index');  // adminpetrecords.html

        // Customers, read-only (Phase 7)
        Route::get('/admin/customers', [AdminCustomerController::class, 'index'])->middleware('can:customers.view')->name('admin.customers.index');  // adminusers.html
        Route::get('/admin/customers/{customer}', [AdminCustomerController::class, 'show'])->whereNumber('customer')->middleware('can:customers.view')->name('admin.customers.show');

        // Services and prices (Phase 7)
        Route::middleware('can:services.manage')->group(function () {
            Route::get('/admin/services', [ServiceController::class, 'index'])->name('admin.services.index');  // adminservices.html
            Route::get('/admin/services/create', [ServiceController::class, 'create'])->name('admin.services.create');
            Route::post('/admin/services', [ServiceController::class, 'store'])->name('admin.services.store');
            Route::get('/admin/services/{service}/edit', [ServiceController::class, 'edit'])->whereNumber('service')->name('admin.services.edit');
            Route::put('/admin/services/{service}', [ServiceController::class, 'update'])->whereNumber('service')->name('admin.services.update');
            Route::patch('/admin/services/{service}/toggle', [ServiceController::class, 'toggle'])->whereNumber('service')->name('admin.services.toggle');
            Route::delete('/admin/services/{service}', [ServiceController::class, 'destroy'])->whereNumber('service')->name('admin.services.destroy');
        });

        // Made real in later phases
        Route::view('/admin/transactions', 'admin.transactions.index')->middleware('can:transactions.view')->name('admin.transactions.index');  // adminpayments.html
        Route::view('/admin/inventory', 'admin.inventory.index')->middleware('can:inventory.view')->name('admin.inventory.index');  // admininventory.html
        Route::view('/admin/reports', 'admin.reports.index')->middleware('can:reports.view')->name('admin.reports.index');  // adminreports.html

        // My Profile (Phase 7)
        Route::get('/admin/profile', [ProfileController::class, 'show'])->name('admin.profile');  // adminprofile.html
        Route::get('/admin/profile/edit', [ProfileController::class, 'edit'])->name('admin.profile.edit');
        Route::put('/admin/profile', [ProfileController::class, 'update'])->name('admin.profile.update');
        Route::put('/admin/profile/password', [ProfileController::class, 'updatePassword'])->name('admin.password.update');

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
