<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FMH Animal Clinic - Web Routes (PHASE 1: project structure)
|--------------------------------------------------------------------------
|
| Each line connects a web address to a Blade page.
| Example:  Route::view('/portal/pets', 'customer.pets.index')
|           -> opening /portal/pets shows resources/views/customer/pets/index.blade.php
|
| ->name('...') gives the address a nickname. The pages use route('nickname')
| to build links, so if an address changes we only edit it here.
|
| PHASE 1 NOTE: pages still show sample data and anyone can open them.
| Login protection is added in Phase 3 & 4, real data in Phase 2 & later.
|
*/


// ======================================================================
// PUBLIC PAGES & LOGIN
// ======================================================================

Route::view('/', 'public.home')->name('home');  // home.html
Route::view('/login', 'auth.login')->name('login');  // login.html
Route::view('/register', 'auth.register')->name('register');  // register.html
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');  // forgotpass.html

// The old separate login pages now go to the single login page (decision P15).
Route::redirect('/admin/login', '/login');       // adminlogin.html
Route::redirect('/staff/login', '/login');       // assistantlogin.html
Route::redirect('/superadmin/login', '/login');  // superadminlogin.html

// Temporary: these forms are not connected to the database yet.
// They show a message instead of an error. Replaced in Phase 3 / Phase 5 / Phase 9.
Route::post('/register', fn () => back()->with('phase_notice', 'Registration will be saved to the database in Phase 3 (Authentication).'))->name('register.store');
Route::post('/forgot-password', fn () => back()->with('phase_notice', 'Password reset codes will be sent in Phase 3 (Authentication).'))->name('password.email');

// ======================================================================
// CUSTOMER / PET OWNER PORTAL  (old: dashboard.html, mypets.html, ...)
// ======================================================================

Route::view('/portal', 'customer.dashboard')->name('portal.dashboard');  // dashboard.html
Route::view('/portal/profile', 'customer.profile')->name('portal.profile');  // profile.html
Route::view('/portal/pets', 'customer.pets.index')->name('portal.pets.index');  // mypets.html
Route::view('/portal/pets/create', 'customer.pets.create')->name('portal.pets.create');  // addpet.html
Route::view('/portal/appointments/create', 'customer.appointments.create')->name('portal.appointments.create');  // appointment.html
Route::view('/portal/appointments', 'customer.appointments.index')->name('portal.appointments.index');  // history.html

// Temporary form handlers (replaced in Phase 5 and Phase 9).
Route::post('/portal/pets', fn () => back()->with('phase_notice', 'Saving pets will work in Phase 5 (Customer module).'))->name('portal.pets.store');
Route::post('/portal/appointments', fn () => back()->with('phase_notice', 'Booking will be saved in Phase 9 (Appointments).'))->name('portal.appointments.store');

// ======================================================================
// STAFF / RECEPTIONIST  (old: assistant*.html)
// ======================================================================

Route::view('/staff', 'staff.dashboard')->name('staff.dashboard');  // assistantdashboard.html
Route::view('/staff/appointments', 'staff.appointments.index')->name('staff.appointments.index');  // assistantappointments.html
Route::view('/staff/appointments/{appointment}', 'staff.appointments.show')->name('staff.appointments.show');  // assistantappointmentdetails.html
Route::view('/staff/pets', 'staff.pets.index')->name('staff.pets.index');  // assistantpets.html
Route::view('/staff/pets/{pet}', 'staff.pets.show')->name('staff.pets.show');  // assistantpetrecords.html
Route::view('/staff/profile', 'staff.profile')->name('staff.profile');  // assistantprofile.html
Route::view('/staff/logout', 'staff.logout')->name('staff.logout');  // assistantlogout.html

// ======================================================================
// VETERINARIAN / ADMIN  (old: admin*.html)
// ======================================================================

Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');  // admindashboard.html
Route::view('/admin/appointments', 'admin.appointments.index')->name('admin.appointments.index');  // adminappointments.html
Route::view('/admin/appointments/create', 'admin.appointments.create')->name('admin.appointments.create');  // newappointment.html
Route::view('/admin/appointments/{appointment}', 'admin.appointments.show')->name('admin.appointments.show');  // adminappointmentdetails.html
Route::view('/admin/appointments/{appointment}/edit', 'admin.appointments.edit')->name('admin.appointments.edit');  // editappointment.html
Route::view('/admin/pets', 'admin.pets.index')->name('admin.pets.index');  // adminpets.html
Route::view('/admin/pet-records', 'admin.records.index')->name('admin.records.index');  // adminpetrecords.html
Route::view('/admin/customers', 'admin.customers.index')->name('admin.customers.index');  // adminusers.html
Route::view('/admin/services', 'admin.services.index')->name('admin.services.index');  // adminservices.html
Route::view('/admin/transactions', 'admin.transactions.index')->name('admin.transactions.index');  // adminpayments.html
Route::view('/admin/inventory', 'admin.inventory.index')->name('admin.inventory.index');  // admininventory.html
Route::view('/admin/reports', 'admin.reports.index')->name('admin.reports.index');  // adminreports.html
Route::view('/admin/profile', 'admin.profile')->name('admin.profile');  // adminprofile.html
Route::view('/admin/logout', 'admin.logout')->name('admin.logout');  // adminlogout.html

// ======================================================================
// SUPER ADMIN  (old: superadmin*.html)
// ======================================================================

Route::view('/superadmin', 'superadmin.dashboard')->name('superadmin.dashboard');  // superadmindashboard.html
Route::view('/superadmin/users', 'superadmin.users.index')->name('superadmin.users.index');  // superadminusers.html
Route::view('/superadmin/appointments', 'superadmin.appointments')->name('superadmin.appointments');  // superadminappointments.html
Route::view('/superadmin/pet-records', 'superadmin.pet-records')->name('superadmin.pet-records');  // superadminpetrecords.html
Route::view('/superadmin/waivers', 'superadmin.waivers')->name('superadmin.waivers');  // superadminwaivers.html
Route::view('/superadmin/sales', 'superadmin.sales')->name('superadmin.sales');  // superadminpos.html
Route::view('/superadmin/transactions', 'superadmin.transactions')->name('superadmin.transactions');  // superadmintransactions.html
Route::view('/superadmin/inventory', 'superadmin.inventory')->name('superadmin.inventory');  // superadmininventory.html
Route::view('/superadmin/reports', 'superadmin.reports')->name('superadmin.reports');  // superadminreports.html
Route::view('/superadmin/activity-logs', 'superadmin.activity-logs')->name('superadmin.activity-logs');  // superadminactivity.html
Route::view('/superadmin/backups', 'superadmin.backups')->name('superadmin.backups');  // superadminbackup.html
Route::view('/superadmin/settings', 'superadmin.settings')->name('superadmin.settings');  // superadminsettings.html
Route::view('/superadmin/logout', 'superadmin.logout')->name('superadmin.logout');  // superadminlogout.html
