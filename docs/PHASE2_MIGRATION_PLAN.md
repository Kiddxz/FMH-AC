# Phase 2 — Backup and Migration Plan

This plan converts the static FMH Animal Clinic frontend into a Laravel + MySQL application **without losing or redesigning the original**. It is based on the Phase 1 audit (`docs/PHASE1_AUDIT.md`) and the capstone paper.

> **Beginner note:** today every page is a plain `.html` file with typed-in sample data. In Laravel, each page becomes a **Blade view** (an HTML template that can show real data from the database). A **route** is the web address, and a **controller** is the PHP code that runs when that address is opened: it loads data, checks permissions and returns the view.

---

## 1. Preserving the original ✅ (done in this phase)

| What | Where | Why |
|---|---|---|
| Exact copy of the ZIP contents (47 pages, `animal.css`, `animal.js`, `image/` with 5 pictures + `Educational_Videos.html`) | `legacy-frontend/` | A permanent, untouched reference. Verified identical to the ZIP with `diff -r` |
| The original upload commit | git commit `7d5587b` ("Add files via upload") | You can always check out the exact original |
| Phase 1 audit | `docs/PHASE1_AUDIT.md` | So the audit lives with the project |

**Nothing in `legacy-frontend/` will ever be edited.** The root-level HTML files are still in place, untouched. In Phase 3 they are replaced by the Laravel app (their content lives on in `legacy-frontend/` and in the Blade views).

> ⚠️ The repo was missing the `image/` folder (the background pictures). It is now preserved in `legacy-frontend/image/` and will be copied into the Laravel app in Phase 3, which fixes the broken backgrounds.

## 2. Decisions used for this plan

These are my recommendations from audit section G, used as defaults. **You can still change any of them; none of them is coded before Phase 5.**

| # | Default decision |
|---|---|
| P1 | Super Admin = system administration + **read-only** oversight pages (appointments, pet records list, waivers, sales, transactions, inventory, reports). No editing of clinical/POS/inventory data; medical notes hidden |
| P2 | **Staff/cashier** operates the POS; Vet/Admin sees transactions read-only + summaries |
| P3 | **Staff** manages inventory items, stock-in and suppliers; Vet/Admin monitors and records usage |
| P4 | **Vet/Admin** writes clinical records; Staff manages pet profiles and sees vaccination history and visits, but not full consultation notes |
| P5 | Staff prepares waivers; the customer signs at the clinic or in the portal |
| P6 | Only Super Admin manages user accounts; Admin "Users" becomes a read-only customer directory |
| P7 | "Assessment/Diagnosis" is a free-text field typed by the vet; no automated suggestions |
| P8 | No online payments, no customer queue tracking or notifications |
| P9 | No Bootstrap; keep `animal.css` |
| P11 | Cash/GCash/Maya/Card are labels recorded by the cashier; no payment gateway |
| P12 | A bill can take several payments and shows its remaining balance |
| P15 | **One login page**; the server sends each user to their role's dashboard. The landing-page role-picker modal stays |
| P16 | Customers book by selecting one of their registered pets |
| P17 | `Educational_Videos.html` is not carried into the new app |

## 3. Target folder layout (created in Phase 3)

```
FMH-AC/
├── legacy-frontend/        ← original, never edited
├── docs/                   ← audit, plans, checklists
├── app/Http/Controllers/   ← Customer/, Staff/, Admin/, SuperAdmin/, Auth/
├── app/Models/  app/Policies/  app/Services/  app/Http/Middleware/
├── database/migrations/  database/seeders/
├── resources/views/
│   ├── layouts/            ← public, customer, staff, admin, superadmin
│   ├── partials/nav/       ← one nav per role (fixes inconsistent menus)
│   ├── public/  auth/  customer/  staff/  admin/  superadmin/
├── public/
│   ├── animal.css          ← copied unchanged
│   ├── animal.js           ← cleaned version (see §6)
│   └── image/              ← the 5 pictures
└── routes/web.php
```

**Why `animal.css` goes in `public/` root and not `public/css/`:** the CSS loads backgrounds with relative paths like `url("image/home.jpg")`. Those are resolved relative to the CSS file. Keeping `animal.css` next to `image/` means **zero CSS edits** and identical visuals.

## 4. Page → route → view → controller map (all 47 pages)

Role prefixes: Customer `/portal`, Staff `/staff`, Vet/Admin `/admin`, Super Admin `/superadmin`. Every prefix is protected by login + role middleware.

### 4.1 Public & authentication

| Original page | Route(s) | Blade view | Controller | Notes |
|---|---|---|---|---|
| home.html | `GET /` | public/home | (view route) | Modal "Continue" → `/login` |
| login.html | `GET/POST /login` | auth/login | Auth\LoginController | Single login (P15) |
| adminlogin.html | `GET /admin/login` → redirect `/login` | — | — | Kept only in legacy |
| assistantlogin.html | `GET /staff/login` → redirect `/login` | — | — | Broken today; replaced |
| superadminlogin.html | `GET /superadmin/login` → redirect `/login` | — | — | Design kept in legacy |
| register.html | `GET/POST /register` | auth/register | Auth\RegisterController | Creates customer account |
| *(new)* | `GET/POST /verify-email` | auth/verify-code | Auth\EmailVerificationController | Fig 6.3 email code |
| forgotpass.html | `GET/POST /forgot-password` | auth/forgot-password | Auth\PasswordResetController | Sends code (Fig 6.4) |
| *(new)* | `GET/POST /reset-password` | auth/reset-password | Auth\PasswordResetController | New ≠ old password |
| — | `POST /logout` | — | Auth\LoginController@logout | Real session logout |

### 4.2 Customer portal (`/portal`)

| Original page | Route(s) | Blade view | Controller |
|---|---|---|---|
| dashboard.html | `GET /portal` | customer/dashboard | Customer\DashboardController |
| profile.html | `GET/PUT /portal/profile`, `PUT /portal/password` | customer/profile | ProfileController (shared) |
| mypets.html | `GET /portal/pets` | customer/pets/index | Customer\PetController |
| addpet.html | `GET /portal/pets/create`, `POST /portal/pets` | customer/pets/create | Customer\PetController |
| *(new)* | `GET/PUT /portal/pets/{pet}/edit` | customer/pets/edit | Customer\PetController |
| *(new)* | `GET /portal/pets/{pet}` | customer/pets/show (profile + care instructions) | Customer\PetController |
| *(new)* | `GET /portal/care-instructions/{id}/download` | customer/care-instruction-pdf | Customer\CareInstructionController |
| appointment.html | `GET /portal/appointments/create`, `POST /portal/appointments`, `GET /portal/slots` | customer/appointments/create | Customer\AppointmentController |
| history.html | `GET /portal/appointments`, `PATCH /portal/appointments/{a}/cancel`, `GET /portal/appointments/download` | customer/appointments/index | Customer\AppointmentController |
| *(new)* | `GET /portal/waivers`, `GET/POST /portal/waivers/{w}/sign` | customer/waivers/* | Customer\WaiverController |

### 4.3 Staff/Receptionist (`/staff`, from the `assistant*` pages)

| Original page | Route(s) | Blade view | Controller |
|---|---|---|---|
| assistantdashboard.html | `GET /staff` | staff/dashboard | Staff\DashboardController |
| assistantappointments.html | `GET /staff/appointments` | staff/appointments/index | AppointmentController (shared) |
| assistantappointmentdetails.html | `GET /staff/appointments/{a}`, `PATCH …/status` | staff/appointments/show | AppointmentController |
| *(from newappointment/editappointment)* | `GET/POST /staff/appointments/create`, `GET/PUT …/{a}/edit` | shared appointments/form | AppointmentController |
| assistantpets.html | `GET /staff/pets` | staff/pets/index | PetController (shared) |
| assistantpetrecords.html | `GET /staff/pets/{pet}` | staff/pets/show | PetController |
| assistantprofile.html | `GET/PUT /staff/profile` | staff/profile | ProfileController |
| assistantlogout.html | `GET /staff/logout` (confirm page) → `POST /logout` | staff/logout | — |
| *(new)* Walk-in | `GET/POST /staff/walk-ins` | staff/walk-ins/create | Staff\WalkInController |
| *(new)* Patient flow | `GET /staff/patient-flow`, `PATCH /staff/visits/{v}/status` | staff/patient-flow | PatientFlowController |
| *(new)* Customers | `GET /staff/customers`, `…/{c}` | staff/customers/* | CustomerController |
| *(new)* Inventory | `GET /staff/inventory`, CRUD, `POST …/stock-in`, `POST …/usage` | staff/inventory/* | InventoryController |
| *(new)* Suppliers | `/staff/suppliers` (resource) | staff/suppliers/* | Staff\SupplierController |
| *(new)* POS | `GET /staff/pos`, `POST /staff/pos/checkout` | staff/pos | Staff\PosController |
| *(new)* Transactions | `GET /staff/transactions`, `…/{t}`, `…/{t}/receipt`, `POST …/{t}/payments` | staff/transactions/* | TransactionController |
| *(new)* Waivers | `/staff/waivers` (create/list/show, sign at clinic) | staff/waivers/* | Staff\WaiverController |
| *(new)* Reports | `GET /staff/reports` | shared reports/index | ReportController |

### 4.4 Veterinarian/Admin (`/admin`)

| Original page | Route(s) | Blade view | Controller |
|---|---|---|---|
| admindashboard.html | `GET /admin` | admin/dashboard | Admin\DashboardController |
| adminappointments.html | `GET /admin/appointments` | admin/appointments/index | AppointmentController |
| adminappointmentdetails.html | `GET /admin/appointments/{a}`, `PATCH …/status` | admin/appointments/show | AppointmentController |
| newappointment.html | `GET/POST /admin/appointments/create` | shared appointments/form | AppointmentController |
| editappointment.html | `GET/PUT /admin/appointments/{a}/edit` | shared appointments/form | AppointmentController |
| adminpets.html | `GET /admin/pets` | admin/pets/index | PetController |
| adminpetrecords.html | `GET /admin/pet-records`, `GET /admin/pets/{pet}/records` | admin/records/* | Admin\MedicalRecordController |
| *(new)* | `GET/POST /admin/pets/{pet}/records/create` (consultation, treatment, prescriptions, vaccination, care instructions) | admin/records/form | Admin\MedicalRecordController |
| adminusers.html | `GET /admin/customers` (read-only, P6) | admin/customers/index | CustomerController |
| adminservices.html | `/admin/services` (resource) | admin/services/* | Admin\ServiceController |
| adminpayments.html | `GET /admin/transactions` (read-only, P2) | admin/transactions/index | TransactionController |
| admininventory.html | `GET /admin/inventory`, `POST …/usage` (P3) | admin/inventory/index | InventoryController |
| adminreports.html | `GET /admin/reports`, `GET …/export` | shared reports/index | ReportController |
| adminprofile.html | `GET/PUT /admin/profile` | admin/profile | ProfileController |
| adminlogout.html | `GET /admin/logout` → `POST /logout` | admin/logout | — |
| *(new)* | `GET /admin/patient-flow` | shared patient-flow | PatientFlowController |
| *(new)* | `GET /admin/waivers`, `PATCH …/{w}/review` | admin/waivers/* | WaiverReviewController |

### 4.5 Super Admin (`/superadmin`)

| Original page | Route(s) | Blade view | Controller | Mode |
|---|---|---|---|---|
| superadmindashboard.html | `GET /superadmin` | superadmin/dashboard | SuperAdmin\DashboardController | — |
| superadminusers.html | `/superadmin/users` (resource + activate/deactivate) | superadmin/users/* | SuperAdmin\UserController | Full |
| *(new)* | `GET/PUT /superadmin/roles` | superadmin/roles | SuperAdmin\RolePermissionController | Full |
| superadminappointments.html | `GET /superadmin/appointments` | superadmin/appointments | SuperAdmin\OversightController | Read-only |
| superadminpetrecords.html | `GET /superadmin/pet-records` | superadmin/pet-records | SuperAdmin\OversightController | Read-only, no notes |
| superadminwaivers.html | `GET /superadmin/waivers` | superadmin/waivers | SuperAdmin\OversightController | Read-only |
| superadminpos.html | `GET /superadmin/sales` | superadmin/sales | SuperAdmin\OversightController | Read-only |
| superadmintransactions.html | `GET /superadmin/transactions` | superadmin/transactions | SuperAdmin\OversightController | Read-only |
| superadmininventory.html | `GET /superadmin/inventory` | superadmin/inventory | SuperAdmin\OversightController | Read-only |
| superadminreports.html | `GET /superadmin/reports` | shared reports/index | ReportController | Read-only |
| superadminactivity.html | `GET /superadmin/activity-logs` | superadmin/activity | SuperAdmin\ActivityLogController | Read-only |
| superadminbackup.html | `GET/POST /superadmin/backups`, `…/{b}/download`, `…/{b}/restore` | superadmin/backup | SuperAdmin\BackupController | Full |
| superadminsettings.html | `GET/PUT /superadmin/settings` | superadmin/settings | SuperAdmin\SettingController | Full |
| superadminlogout.html | `GET /superadmin/logout` → `POST /logout` | superadmin/logout | — | — |
| *(new)* | `GET/PUT /superadmin/profile` | superadmin/profile | ProfileController | REQ003 |

**Totals:** 44 of the 47 original pages carry over to Blade with their design (an earlier version of this plan said 49; that count wrongly included animal.css and animal.js). The 3 role-specific login pages are replaced by the single login, and they and `Educational_Videos.html` stay only in `legacy-frontend/`. About 25 new views are needed for missing features, and they reuse existing CSS classes (`admin-table-card`, `admin-tools`, `appointment-form-card`, `superadmin-table`, …) so they look native.

## 5. CSS, JS and asset reuse

| Asset | Decision |
|---|---|
| `animal.css` (5,788 lines) | **Reused unchanged** in Phase 3. Later fixes are small additions only (admin dashboard mobile overflow, SA nav on mobile) |
| `image/*.jpg` | **Reused** in `public/image/`. `bg.jpg` (4.1 MB) to be compressed later for speed; the original stays in legacy |
| Emoji icons | Kept |
| Inline `onclick="window.location.href='…'"` buttons | **Kept as-is**, with the hard-coded file name replaced by a Laravel route. The CSS classes are element-agnostic, so this keeps identical styling |
| Hard-coded dates ("📅 August 12, 2026") | Replaced with today's date from the server |
| Hard-coded table rows and numbers | Replaced with database data. **The seeders recreate the same sample names (Mark Santos/Max, John Cruz/Buddy, Anna Reyes/Coco…) as fictitious demo data**, so pages look as they do today, now with real data (Interview 2 recommends fictitious data for testing) |

## 6. JavaScript: what stays, what the backend replaces

| `animal.js` part | Decision | Replaced by |
|---|---|---|
| Login modal open/close/role-card selection | **Keep** | — ("Continue" now opens `/login`) |
| `loginForm` with hard-coded passwords | **Remove** (security) | Laravel session login, hashed passwords |
| `superAdminLoginForm` hard-coded | **Remove** | Same single login |
| `superAdminLogoutBtn` (localStorage) | **Remove** | `POST /logout` form |
| Appointment modal add/edit/delete/filter (dead code) | **Remove** | Server CRUD; search/filters as GET query strings (work even without JS) |
| `editAppointmentForm` → localStorage | **Remove** | `PUT` form + Form Request validation |
| `generateReportBtn` (dead) | **Remove** | ReportController |
| Backup/recovery to localStorage (fake) | **Remove** | BackupService (mysqldump) |
| Settings to localStorage | **Remove** | `settings` table |
| Stray block at end of file (causes `ReferenceError` on 18 pages) | **Remove** | — |
| Inline `confirmAppointment()`/`cancelAppointment()` alerts | **Replace** | Real `PATCH` forms; keep a `confirm()` dialog before submit |
| *(new, small)* | **Add** | Confirm-before-submit helper; POS cart preview (server recalculates totals); patient-flow auto-refresh (polling); slot loader on the booking form |

**Rule:** JavaScript is used only for convenience. Every check that matters (login, role, ownership, totals, stock, slot availability) happens in Laravel on the server.

## 7. Features → database tables

Full columns are in audit §K. The final schema is shown for approval in Phase 4.

| Feature | Tables |
|---|---|
| Login, roles, permissions | users, roles, permissions, permission_role, email_verification_codes, password_reset_tokens, sessions |
| Customers & pets | customers, pets |
| Services | services |
| Appointments & slots | appointments, clinic_hours, blocked_dates |
| Walk-in & patient flow | patient_visits |
| Medical records | medical_records, prescriptions, treatments, vaccinations, care_instructions |
| Inventory & suppliers | inventory_items, inventory_batches, inventory_movements (usage log), suppliers |
| POS & transactions | transactions, transaction_items, payments |
| Waivers | waiver_templates, waivers |
| Activity logs | activity_logs |
| Backup & settings | backups, settings |

## 8. Phase 3 checklist (next step, not started)

1. Install Laravel 12 in the repo root.
2. Copy `animal.css`, `image/` and `animal.js` into `public/`.
3. Build 5 layouts + 4 nav partials from the existing markup.
4. Convert the 46 pages to Blade with the same markup and classes; data stays static for now.
5. Add routes (no auth yet; added in Phase 5).
6. Remove the root-level `.html` duplicates (preserved in `legacy-frontend/`).
7. **Visual check:** screenshot each legacy page and its Blade page with a headless browser, compare, and report any differences.
8. Commit + push, then stop for your review.

**To run it on your own computer** (from Phase 3 on) you will need PHP 8.2+, Composer and MySQL. XAMPP 8.2+ works. Setup steps will be written in `README.md`.
