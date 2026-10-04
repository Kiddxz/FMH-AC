<?php

use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MedicalRecordController;
use App\Http\Controllers\Admin\PetController as AdminPetController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\WaiverTemplateController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Clinic\AppointmentController as ClinicAppointmentController;
use App\Http\Controllers\Clinic\InventoryController;
use App\Http\Controllers\Clinic\PatientFlowController;
use App\Http\Controllers\Clinic\ReportController;
use App\Http\Controllers\Clinic\TransactionController;
use App\Http\Controllers\Clinic\WaiverController;
use App\Http\Controllers\Customer\AppointmentController as CustomerAppointmentController;
use App\Http\Controllers\Customer\CareInstructionController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\PetController as CustomerPetController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\WaiverController as CustomerWaiverController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\Staff\CustomerController as StaffCustomerController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use App\Http\Controllers\Staff\PetController as StaffPetController;
use App\Http\Controllers\Staff\PosController;
use App\Http\Controllers\Staff\SupplierController;
use App\Http\Controllers\Staff\WalkInController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\OversightController;
use App\Http\Controllers\SuperAdmin\RoleController;
use App\Http\Controllers\SuperAdmin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FMH Animal Clinic - Web Routes (PHASE 15: reports and dashboards)
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

// Free time slots of one day, for the booking forms (any logged-in user). Phase 9.
Route::get('/appointments/slots', SlotController::class)->middleware('auth')->name('appointments.slots');

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

        // Appointments (Phase 9): booking, history, download, cancel
        Route::get('/portal/appointments', [CustomerAppointmentController::class, 'index'])->name('portal.appointments.index');  // history.html
        Route::get('/portal/appointments/create', [CustomerAppointmentController::class, 'create'])->middleware('can:appointments.book')->name('portal.appointments.create');  // appointment.html
        Route::post('/portal/appointments', [CustomerAppointmentController::class, 'store'])->middleware('can:appointments.book')->name('portal.appointments.store');
        Route::get('/portal/appointments/download', [CustomerAppointmentController::class, 'download'])->name('portal.appointments.download');
        Route::get('/portal/appointments/{appointment}', [CustomerAppointmentController::class, 'show'])->whereNumber('appointment')->name('portal.appointments.show');
        Route::patch('/portal/appointments/{appointment}/cancel', [CustomerAppointmentController::class, 'cancel'])->whereNumber('appointment')->name('portal.appointments.cancel');

        // Care instructions released by the vet (Phase 10)
        Route::get('/portal/care-instructions/{care}/download', [CareInstructionController::class, 'download'])->whereNumber('care')->name('portal.care.download');

        // Waivers and consent forms (Phase 13): read, sign online, print
        Route::get('/portal/waivers', [CustomerWaiverController::class, 'index'])->name('portal.waivers.index');
        Route::get('/portal/waivers/{waiver}', [CustomerWaiverController::class, 'show'])->whereNumber('waiver')->name('portal.waivers.show');
        Route::post('/portal/waivers/{waiver}/sign', [CustomerWaiverController::class, 'sign'])->whereNumber('waiver')->name('portal.waivers.sign');
        Route::get('/portal/waivers/{waiver}/print', [WaiverController::class, 'print'])->whereNumber('waiver')->name('portal.waivers.print');
    });

    // ======================================================================
    // STAFF / RECEPTIONIST  (old: assistant*.html; "Assistant" is now "Staff")
    // ======================================================================

    Route::middleware('role:staff')->group(function () {
        Route::get('/staff', StaffDashboardController::class)->name('staff.dashboard');  // assistantdashboard.html

        // Appointments (Phase 9). Same controller for Staff and Vet/Admin.
        Route::get('/staff/appointments', [ClinicAppointmentController::class, 'index'])->middleware('can:appointments.view')->name('staff.appointments.index');
        Route::middleware('can:appointments.manage')->group(function () {
            Route::get('/staff/appointments/create', [ClinicAppointmentController::class, 'create'])->name('staff.appointments.create');
            Route::post('/staff/appointments', [ClinicAppointmentController::class, 'store'])->name('staff.appointments.store');
            Route::get('/staff/appointments/{appointment}/edit', [ClinicAppointmentController::class, 'edit'])->whereNumber('appointment')->name('staff.appointments.edit');
            Route::put('/staff/appointments/{appointment}', [ClinicAppointmentController::class, 'update'])->whereNumber('appointment')->name('staff.appointments.update');
            Route::patch('/staff/appointments/{appointment}/confirm', [ClinicAppointmentController::class, 'confirm'])->whereNumber('appointment')->name('staff.appointments.confirm');
            Route::patch('/staff/appointments/{appointment}/complete', [ClinicAppointmentController::class, 'complete'])->whereNumber('appointment')->name('staff.appointments.complete');
            Route::patch('/staff/appointments/{appointment}/cancel', [ClinicAppointmentController::class, 'cancel'])->whereNumber('appointment')->name('staff.appointments.cancel');
        });
        Route::get('/staff/appointments/{appointment}', [ClinicAppointmentController::class, 'show'])->whereNumber('appointment')->middleware('can:appointments.view')->name('staff.appointments.show');

        // Walk-in and patient flow (Phase 11). Internal board only: nothing is sent to customers.
        Route::get('/staff/patient-flow', [PatientFlowController::class, 'index'])->middleware('can:patient_flow.view')->name('staff.flow.index');
        Route::middleware('can:patient_flow.manage')->group(function () {
            Route::patch('/staff/visits/{visit}/status', [PatientFlowController::class, 'updateStatus'])->whereNumber('visit')->name('staff.flow.status');
            Route::post('/staff/appointments/{appointment}/check-in', [PatientFlowController::class, 'checkInAppointment'])->whereNumber('appointment')->name('staff.flow.check-in');
            Route::get('/staff/walk-ins/create', [WalkInController::class, 'create'])->name('staff.walk-ins.create');
            Route::post('/staff/walk-ins', [WalkInController::class, 'store'])->name('staff.walk-ins.store');
            Route::get('/staff/customers/{customer}/check-in', [WalkInController::class, 'createForCustomer'])->whereNumber('customer')->name('staff.walk-ins.customer');
            Route::post('/staff/customers/{customer}/check-in', [WalkInController::class, 'storeForCustomer'])->whereNumber('customer')->name('staff.walk-ins.customer.store');
        });

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

        // Inventory (Phase 12). Staff manage items and stock; suppliers too (decision P3).
        Route::get('/staff/inventory', [InventoryController::class, 'index'])->middleware('can:inventory.view')->name('staff.inventory.index');
        Route::get('/staff/inventory/log', [InventoryController::class, 'log'])->middleware('can:inventory.view')->name('staff.inventory.log');
        Route::middleware('can:inventory.manage')->group(function () {
            Route::get('/staff/inventory/create', [InventoryController::class, 'create'])->name('staff.inventory.create');
            Route::post('/staff/inventory', [InventoryController::class, 'store'])->name('staff.inventory.store');
            Route::get('/staff/inventory/{item}/edit', [InventoryController::class, 'edit'])->whereNumber('item')->name('staff.inventory.edit');
            Route::put('/staff/inventory/{item}', [InventoryController::class, 'update'])->whereNumber('item')->name('staff.inventory.update');
            Route::post('/staff/inventory/{item}/stock-in', [InventoryController::class, 'stockIn'])->whereNumber('item')->name('staff.inventory.stock-in');
            Route::patch('/staff/inventory/batches/{batch}/count', [InventoryController::class, 'correctCount'])->whereNumber('batch')->name('staff.inventory.count');
            Route::patch('/staff/inventory/batches/{batch}/dispose', [InventoryController::class, 'disposeExpired'])->whereNumber('batch')->name('staff.inventory.dispose');
        });
        Route::post('/staff/inventory/{item}/usage', [InventoryController::class, 'recordUsage'])->whereNumber('item')->middleware('can:inventory.record_usage')->name('staff.inventory.usage');
        Route::get('/staff/inventory/{item}', [InventoryController::class, 'show'])->whereNumber('item')->middleware('can:inventory.view')->name('staff.inventory.show');

        Route::middleware('can:suppliers.manage')->group(function () {
            Route::get('/staff/suppliers', [SupplierController::class, 'index'])->name('staff.suppliers.index');
            Route::get('/staff/suppliers/create', [SupplierController::class, 'create'])->name('staff.suppliers.create');
            Route::post('/staff/suppliers', [SupplierController::class, 'store'])->name('staff.suppliers.store');
            Route::get('/staff/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->whereNumber('supplier')->name('staff.suppliers.edit');
            Route::put('/staff/suppliers/{supplier}', [SupplierController::class, 'update'])->whereNumber('supplier')->name('staff.suppliers.update');
            Route::patch('/staff/suppliers/{supplier}/toggle', [SupplierController::class, 'toggle'])->whereNumber('supplier')->name('staff.suppliers.toggle');
        });

        // POS and transactions (Phase 14). Staff are the cashiers (decision P2).
        // Payments are recorded by hand (cash, GCash, Maya, card): there is no online payment.
        Route::middleware('can:pos.manage')->group(function () {
            Route::get('/staff/pos', [PosController::class, 'create'])->name('staff.pos.create');
            Route::post('/staff/pos', [PosController::class, 'store'])->name('staff.pos.store');
            Route::post('/staff/transactions/{transaction}/payments', [TransactionController::class, 'addPayment'])->whereNumber('transaction')->name('staff.transactions.pay');
        });
        Route::middleware('can:transactions.view')->group(function () {
            Route::get('/staff/transactions', [TransactionController::class, 'index'])->name('staff.transactions.index');
            Route::get('/staff/transactions/{transaction}', [TransactionController::class, 'show'])->whereNumber('transaction')->name('staff.transactions.show');
            Route::get('/staff/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->whereNumber('transaction')->name('staff.transactions.receipt');
        });

        // Reports (Phase 15): view, print or export as CSV (Fig 6.2)
        Route::get('/staff/reports', ReportController::class)->middleware('can:reports.view')->name('staff.reports.index');

        // Waivers and consent forms (Phase 13). Staff prepare them; the owner signs at the clinic or in the portal.
        Route::middleware('can:waivers.view')->group(function () {
            Route::get('/staff/waivers', [WaiverController::class, 'index'])->name('staff.waivers.index');
            Route::get('/staff/waivers/{waiver}', [WaiverController::class, 'show'])->whereNumber('waiver')->name('staff.waivers.show');
            Route::get('/staff/waivers/{waiver}/print', [WaiverController::class, 'print'])->whereNumber('waiver')->name('staff.waivers.print');
        });
        Route::middleware('can:waivers.prepare')->group(function () {
            Route::get('/staff/waivers/create', [WaiverController::class, 'create'])->name('staff.waivers.create');
            Route::post('/staff/waivers', [WaiverController::class, 'store'])->name('staff.waivers.store');
            Route::post('/staff/waivers/{waiver}/sign', [WaiverController::class, 'signAtClinic'])->whereNumber('waiver')->name('staff.waivers.sign');
            Route::delete('/staff/waivers/{waiver}', [WaiverController::class, 'destroy'])->whereNumber('waiver')->name('staff.waivers.destroy');
        });

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

        // Appointments (Phase 9). Same controller for Staff and Vet/Admin.
        Route::get('/admin/appointments', [ClinicAppointmentController::class, 'index'])->middleware('can:appointments.view')->name('admin.appointments.index');
        Route::middleware('can:appointments.manage')->group(function () {
            Route::get('/admin/appointments/create', [ClinicAppointmentController::class, 'create'])->name('admin.appointments.create');
            Route::post('/admin/appointments', [ClinicAppointmentController::class, 'store'])->name('admin.appointments.store');
            Route::get('/admin/appointments/{appointment}/edit', [ClinicAppointmentController::class, 'edit'])->whereNumber('appointment')->name('admin.appointments.edit');
            Route::put('/admin/appointments/{appointment}', [ClinicAppointmentController::class, 'update'])->whereNumber('appointment')->name('admin.appointments.update');
            Route::patch('/admin/appointments/{appointment}/confirm', [ClinicAppointmentController::class, 'confirm'])->whereNumber('appointment')->name('admin.appointments.confirm');
            Route::patch('/admin/appointments/{appointment}/complete', [ClinicAppointmentController::class, 'complete'])->whereNumber('appointment')->name('admin.appointments.complete');
            Route::patch('/admin/appointments/{appointment}/cancel', [ClinicAppointmentController::class, 'cancel'])->whereNumber('appointment')->name('admin.appointments.cancel');
        });
        Route::get('/admin/appointments/{appointment}', [ClinicAppointmentController::class, 'show'])->whereNumber('appointment')->middleware('can:appointments.view')->name('admin.appointments.show');

        // Patient flow board, view only (Phase 11)
        Route::get('/admin/patient-flow', [PatientFlowController::class, 'index'])->middleware('can:patient_flow.view')->name('admin.flow.index');

        // Pets (Phase 7)
        Route::get('/admin/pets', [AdminPetController::class, 'index'])->middleware('can:pets.view')->name('admin.pets.index');  // adminpets.html
        Route::get('/admin/pets/{pet}', [AdminPetController::class, 'show'])->whereNumber('pet')->name('admin.pets.show');

        // Pet records (Phase 10): consultations, treatments, prescriptions, vaccinations, care instructions
        Route::get('/admin/pet-records', [MedicalRecordController::class, 'index'])->middleware('can:records.view')->name('admin.records.index');  // adminpetrecords.html
        Route::get('/admin/pet-records/{record}', [MedicalRecordController::class, 'show'])->whereNumber('record')->middleware('can:records.view')->name('admin.records.show');
        Route::middleware('can:records.write')->group(function () {
            Route::get('/admin/pet-records/create', [MedicalRecordController::class, 'create'])->name('admin.records.create');
            Route::post('/admin/pet-records', [MedicalRecordController::class, 'store'])->name('admin.records.store');
            Route::get('/admin/pet-records/{record}/edit', [MedicalRecordController::class, 'edit'])->whereNumber('record')->name('admin.records.edit');
            Route::put('/admin/pet-records/{record}', [MedicalRecordController::class, 'update'])->whereNumber('record')->name('admin.records.update');
            Route::post('/admin/pet-records/{record}/care', [MedicalRecordController::class, 'storeCare'])->whereNumber('record')->name('admin.records.care.store');
            Route::patch('/admin/care-instructions/{care}/release', [MedicalRecordController::class, 'releaseCare'])->whereNumber('care')->name('admin.care.release');
            Route::delete('/admin/care-instructions/{care}', [MedicalRecordController::class, 'destroyCare'])->whereNumber('care')->name('admin.care.destroy');
        });

        // Inventory (Phase 12): the vet views stock and records what was used (decision P3)
        Route::get('/admin/inventory', [InventoryController::class, 'index'])->middleware('can:inventory.view')->name('admin.inventory.index');  // admininventory.html
        Route::get('/admin/inventory/log', [InventoryController::class, 'log'])->middleware('can:inventory.view')->name('admin.inventory.log');
        Route::get('/admin/inventory/{item}', [InventoryController::class, 'show'])->whereNumber('item')->middleware('can:inventory.view')->name('admin.inventory.show');
        Route::post('/admin/inventory/{item}/usage', [InventoryController::class, 'recordUsage'])->whereNumber('item')->middleware('can:inventory.record_usage')->name('admin.inventory.usage');

        // Waivers (Phase 13): the vet reviews signed forms and keeps the form texts up to date
        Route::middleware('can:waivers.view')->group(function () {
            Route::get('/admin/waivers', [WaiverController::class, 'index'])->name('admin.waivers.index');
            Route::get('/admin/waivers/{waiver}', [WaiverController::class, 'show'])->whereNumber('waiver')->name('admin.waivers.show');
            Route::get('/admin/waivers/{waiver}/print', [WaiverController::class, 'print'])->whereNumber('waiver')->name('admin.waivers.print');
        });
        Route::middleware('can:waivers.review')->group(function () {
            Route::patch('/admin/waivers/{waiver}/review', [WaiverController::class, 'review'])->whereNumber('waiver')->name('admin.waivers.review');
            Route::get('/admin/waiver-templates', [WaiverTemplateController::class, 'index'])->name('admin.waiver-templates.index');
            Route::get('/admin/waiver-templates/create', [WaiverTemplateController::class, 'create'])->name('admin.waiver-templates.create');
            Route::post('/admin/waiver-templates', [WaiverTemplateController::class, 'store'])->name('admin.waiver-templates.store');
            Route::get('/admin/waiver-templates/{template}/edit', [WaiverTemplateController::class, 'edit'])->whereNumber('template')->name('admin.waiver-templates.edit');
            Route::put('/admin/waiver-templates/{template}', [WaiverTemplateController::class, 'update'])->whereNumber('template')->name('admin.waiver-templates.update');
        });

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

        // Payments / transactions (Phase 14): the vet views them and voids a wrong bill with a reason
        Route::middleware('can:transactions.view')->group(function () {
            Route::get('/admin/transactions', [TransactionController::class, 'index'])->name('admin.transactions.index');  // adminpayments.html
            Route::get('/admin/transactions/{transaction}', [TransactionController::class, 'show'])->whereNumber('transaction')->name('admin.transactions.show');
            Route::get('/admin/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->whereNumber('transaction')->name('admin.transactions.receipt');
        });
        Route::patch('/admin/transactions/{transaction}/void', [TransactionController::class, 'void'])->whereNumber('transaction')->middleware('can:transactions.void')->name('admin.transactions.void');

        // Reports (Phase 15): view, print or export as CSV
        Route::get('/admin/reports', ReportController::class)->middleware('can:reports.view')->name('admin.reports.index');  // adminreports.html

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
        Route::get('/superadmin', SuperAdminDashboardController::class)->name('superadmin.dashboard');  // superadmindashboard.html

        // User accounts (Phase 8)
        Route::middleware('can:users.manage')->group(function () {
            Route::get('/superadmin/users', [UserController::class, 'index'])->name('superadmin.users.index');  // superadminusers.html
            Route::get('/superadmin/users/create', [UserController::class, 'create'])->name('superadmin.users.create');
            Route::post('/superadmin/users', [UserController::class, 'store'])->name('superadmin.users.store');
            Route::get('/superadmin/users/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('superadmin.users.edit');
            Route::put('/superadmin/users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('superadmin.users.update');
            Route::patch('/superadmin/users/{user}/status', [UserController::class, 'toggleStatus'])->whereNumber('user')->name('superadmin.users.status');
        });

        // Roles & permissions (Phase 8)
        Route::middleware('can:roles.manage')->group(function () {
            Route::get('/superadmin/roles', [RoleController::class, 'index'])->name('superadmin.roles.index');
            Route::put('/superadmin/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('superadmin.roles.update');
        });

        // Read-only oversight with real data (Phase 8)
        Route::get('/superadmin/appointments', [OversightController::class, 'appointments'])->middleware('can:appointments.view')->name('superadmin.appointments');  // superadminappointments.html
        Route::get('/superadmin/pet-records', [OversightController::class, 'petRecords'])->middleware('can:pets.view')->name('superadmin.pet-records');  // superadminpetrecords.html
        Route::get('/superadmin/inventory', [OversightController::class, 'inventory'])->middleware('can:inventory.view')->name('superadmin.inventory');  // superadmininventory.html
        Route::get('/superadmin/activity-logs', [OversightController::class, 'activityLogs'])->middleware('can:activity_logs.view')->name('superadmin.activity-logs');  // superadminactivity.html

        // Waivers, view only (Phase 13)
        Route::middleware('can:waivers.view')->group(function () {
            Route::get('/superadmin/waivers', [WaiverController::class, 'index'])->name('superadmin.waivers.index');  // superadminwaivers.html
            Route::get('/superadmin/waivers/{waiver}', [WaiverController::class, 'show'])->whereNumber('waiver')->name('superadmin.waivers.show');
            Route::get('/superadmin/waivers/{waiver}/print', [WaiverController::class, 'print'])->whereNumber('waiver')->name('superadmin.waivers.print');
        });

        // Sales and transaction records, view only (Phase 14). The old POS page (superadminpos.html) was only
        // a list of sales, so it was merged into this page: Super Admin does not take payments (decision P1/P2).
        Route::middleware('can:transactions.view')->group(function () {
            Route::get('/superadmin/transactions', [TransactionController::class, 'index'])->name('superadmin.transactions');  // superadmintransactions.html
            Route::get('/superadmin/transactions/{transaction}', [TransactionController::class, 'show'])->whereNumber('transaction')->name('superadmin.transactions.show');
            Route::get('/superadmin/transactions/{transaction}/receipt', [TransactionController::class, 'receipt'])->whereNumber('transaction')->name('superadmin.transactions.receipt');
        });

        // Reports, view only (Phase 15)
        Route::get('/superadmin/reports', ReportController::class)->middleware('can:reports.view')->name('superadmin.reports');  // superadminreports.html

        // Made real in a later phase (backups/settings: 17)
        Route::view('/superadmin/backups', 'superadmin.backups')->middleware('can:backups.manage')->name('superadmin.backups');  // superadminbackup.html
        Route::view('/superadmin/settings', 'superadmin.settings')->middleware('can:settings.manage')->name('superadmin.settings');  // superadminsettings.html

        // My Profile (Phase 8)
        Route::get('/superadmin/profile', [ProfileController::class, 'show'])->name('superadmin.profile');
        Route::get('/superadmin/profile/edit', [ProfileController::class, 'edit'])->name('superadmin.profile.edit');
        Route::put('/superadmin/profile', [ProfileController::class, 'update'])->name('superadmin.profile.update');
        Route::put('/superadmin/profile/password', [ProfileController::class, 'updatePassword'])->name('superadmin.password.update');

        Route::view('/superadmin/logout', 'superadmin.logout')->name('superadmin.logout');  // superadminlogout.html
    });

});
