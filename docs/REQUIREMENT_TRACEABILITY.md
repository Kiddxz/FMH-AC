# FMH Animal Clinic — Requirement Traceability List (Gap Analysis)

**Source of truth:** the capstone paper *A Web-Based Sales, Inventory and Appointment Management System for FMH Animal Clinic* (STI College Las Piñas, May 28 2026).
**Base project:** `FMH_animal_clinic.zip` (49 HTML pages, `animal.css`, `animal.js`, `image/`).

### Status key
| Status | Meaning |
|---|---|
| COMPLETE | Works with Laravel + MySQL and was tested. *Nothing is COMPLETE yet.* |
| PARTIAL | A page or design exists but only shows typed-in sample data (no database). |
| MISSING | Nothing exists yet. |
| INCORRECT | Exists but is wrong, insecure or misleading. |
| NEEDS TESTING | Can only be checked after it is built or deployed. |

> **Numbering note:** the paper numbers its functional requirements REQ001–REQ028 and **restarts at REQ001** for the non-functional ones. To avoid confusion this list uses:
> - **FR-REQ001…028** for functional requirements
> - **NFR-REQ001…024** for non-functional requirements
> - **SCOPE-01…16** for requirements written in the paper's Scope, Sprints or diagrams that have no REQ number
>
> "Old file" means a page from the ZIP. "New file" means a Laravel file we will create. "Dev phase" points to the 20-phase plan in `docs/DEVELOPMENT_PLAN.md`.

## Summary

| Group | Total | COMPLETE | PARTIAL | MISSING | INCORRECT | NEEDS TESTING |
|---|---|---|---|---|---|---|
| Functional (FR-REQ) | 28 | 0 | 16 | 9 | 3 | 0 |
| Scope items (SCOPE) | 16 | 0 | 3 | 11 | 2 | 0 |
| Non-functional (NFR-REQ) | 24 | 0 | 6 | 9 | 4 | 4 (+1 N/A) |

---

# Part A — Functional Requirements

## User Management Module

**FR-REQ001**
- **Requirement:** Users can create accounts and log in securely (paper p. 44). Fig 6.3 adds an **email verification code** at registration. Fig 6.4 adds **forgot password by emailed code**, the new password must differ from the old one, and each role is sent to its own module after login.
- **Current implementation:**
  - `register.html`: form with `action="#"`, so nothing is saved.
  - `login.html`: compares against passwords typed inside `animal.js` (`owner123`, `assistant123`).
  - `adminlogin.html`: accepts any email/password and puts the password in the web address.
  - `assistantlogin.html`: always fails.
  - `superadminlogin.html`: password `superadmin123` is inside `animal.js`.
  - `forgotpass.html`: does nothing.
- **Status:** INCORRECT
- **What needs to be changed:**
  - Laravel registration saves the user to MySQL with a hashed password.
  - A 6-digit email code is sent and must be entered before the account works.
  - One secure login page with throttling (blocks repeated wrong passwords).
  - Password reset by emailed code with a "new ≠ old" check.
  - Redirect to the correct dashboard per role.
  - Delete the typed-in passwords from `animal.js`.
- **Files involved:**
  - Old: register.html, login.html, adminlogin.html, assistantlogin.html, superadminlogin.html, forgotpass.html, animal.js
  - New: `app/Http/Controllers/Auth/*`, `resources/views/auth/*`, `routes/web.php`, `users` + `email_verification_codes` tables
  - Dev phase: 3
- **Testing method:**
  - Register → a code arrives (in the Laravel log during development) → you can't log in until the code is entered.
  - A wrong password is rejected, and the 6th wrong try is temporarily blocked.
  - A reset code works, but reusing the old password is rejected.
  - Each role lands on its own dashboard.

**FR-REQ002**
- **Requirement:** Support the roles Super Administrator, Veterinarian/Administrator, Staff/Receptionist and Customer, each with designated access.
- **Current implementation:** Separate page sets exist (`superadmin*`, `admin*`, `assistant*`, customer pages). The role is saved in the browser (localStorage) but **never checked**, so anyone can open any page by typing its address.
- **Status:** PARTIAL
- **What needs to be changed:**
  - `roles` table.
  - Role middleware on every route group.
  - Rename "Assistant" to "Staff/Receptionist" and "Admin" to "Veterinarian/Admin" in the labels.
- **Files involved:**
  - Old: all pages
  - New: `app/Http/Middleware/EnsureRole.php`, `bootstrap/app.php`, `routes/web.php`
  - Dev phase: 4
- **Testing method:** Log in as each role and try every other role's addresses. You should be blocked (403 page or redirect).

**FR-REQ003**
- **Requirement:** Users can edit and update their own profile.
- **Current implementation:**
  - `profile.html`, `adminprofile.html` and `assistantprofile.html` show typed-in data, and their "Edit Profile" and "Change Password" buttons do nothing.
  - Super Admin has no profile page.
- **Status:** PARTIAL
- **What needs to be changed:** One profile controller with edit-profile and change-password forms (the current password is required), plus a Super Admin profile page.
- **Files involved:**
  - Old: profile.html, adminprofile.html, assistantprofile.html
  - New: `ProfileController.php`, `resources/views/*/profile.blade.php`
  - Dev phase: 5
- **Testing method:** Change your name or mobile → refresh → the change is still there. Changing the password with a wrong current password is rejected.

**FR-REQ004**
- **Requirement:** Administrators can view, update, activate and deactivate user accounts.
- **Current implementation:** `superadminusers.html` (all roles) and `adminusers.html` (pet owners) are typed-in tables. Add/Edit/Deactivate do nothing.
- **Status:** PARTIAL
- **What needs to be changed:**
  - Super Admin gets full account management (decision P6).
  - Admin "Users" becomes a read-only customer list.
  - Deactivated users cannot log in.
- **Files involved:**
  - Old: superadminusers.html, adminusers.html
  - New: `SuperAdmin/UserController.php`, views
  - Dev phase: 8
- **Testing method:** Super Admin creates a Staff account → it can log in. Deactivate it → the login is refused. An Admin opening the user-management address is blocked.

## Customer Portal Module

**FR-REQ005**
- **Requirement:** A customer portal showing account information, booking details and (basic) pet records.
- **Current implementation:**
  - `dashboard.html` says "Welcome, Mark!" (typed in).
  - The counters show 0 while My Pets shows "Buddy" (contradiction).
  - `history.html` uses a different menu style from the other customer pages.
- **Status:** PARTIAL
- **What needs to be changed:** The dashboard reads the logged-in customer's real data from MySQL, and all customer pages share one customer menu.
- **Files involved:**
  - Old: dashboard.html, history.html
  - New: `Customer/DashboardController.php`, `resources/views/customer/*`, `layouts/customer.blade.php`
  - Dev phase: 5
- **Testing method:** Log in as customer A, then customer B. Each sees only their own pets and appointments, and the counters match.

**FR-REQ006**
- **Requirement:** Customers manage pet profiles and monitor appointment information.
- **Current implementation:**
  - `mypets.html` shows one typed-in pet.
  - `addpet.html` saves nothing.
  - There is no edit page.
  - Species is limited to Dog/Cat/Other, although the client interview mentions hamsters, birds and exotics.
- **Status:** PARTIAL
- **What needs to be changed:** Add, edit and view pets saved in the `pets` table, a wider species list, and a birthdate instead of age (age goes out of date).
- **Files involved:**
  - Old: mypets.html, addpet.html
  - New: `Customer/PetController.php`, `customer/pets/*.blade.php`
  - Dev phase: 5
- **Testing method:** Add a pet → it appears in My Pets. Edit it → the change is saved. Typing another customer's pet ID in the address → blocked (403).

**FR-REQ007**
- **Requirement:** Customers can access, and per the Scope **download**, post-treatment care instructions.
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** The vet writes care instructions on a medical record and marks them "release to owner". The customer sees them on the pet page and can download or print them.
- **Files involved:**
  - New: `care_instructions` table, `Customer/CareInstructionController.php`, views
  - Dev phase: 10
- **Testing method:** The vet releases instructions → the owner sees and downloads them. A different customer gets 403. Unreleased instructions stay hidden.

## Digital Pet Records Module

**FR-REQ008**
- **Requirement:** Veterinarians and authorized staff can create and update digital pet records.
- **Current implementation:** `adminpetrecords.html`, `assistantpetrecords.html` and `superadminpetrecords.html` are typed-in tables. Add/View/Edit do nothing.
- **Status:** PARTIAL
- **What needs to be changed:**
  - Vet/Admin creates and updates clinical records.
  - Staff creates and updates pet profiles and owner info, and views vaccination history (decision P4).
- **Files involved:**
  - Old: adminpetrecords.html, assistantpetrecords.html, adminpets.html, assistantpets.html
  - New: `Admin/MedicalRecordController.php`, `PetController.php`, views
  - Dev phase: 10
- **Testing method:** The vet adds a consultation → it appears in the pet's history. Staff cannot open the consultation-notes form (403).

**FR-REQ009**
- **Requirement:** Store pet details, medical history, consultation records, prescriptions, treatments and vaccination records in the database.
- **Current implementation:** Only typed-in pet details and one typed-in "Medical History" table in `assistantpetrecords.html`.
- **Status:** MISSING
- **What needs to be changed:** Add the tables `medical_records`, `prescriptions`, `treatments` and `vaccinations`, each with its own form.
- **Files involved:**
  - New: migrations + models + forms
  - Dev phases: 2 and 10
- **Testing method:** Create one of each record type → each shows in the pet's history in date order.

**FR-REQ010**
- **Requirement:** Authorized users can search and retrieve pet records easily.
- **Current implementation:** Search boxes exist on the pet and record pages but do nothing.
- **Status:** PARTIAL
- **What needs to be changed:** Server-side search by pet name, owner name or mobile number, with paging.
- **Files involved:**
  - Old: adminpets.html, adminpetrecords.html, assistantpets.html
  - New: controller `index()` methods
  - Dev phase: 10
- **Testing method:** Search "Max" → only matching pets appear. Search by owner mobile → their pets appear.

## Walk-in and Patient Flow Monitoring Module

**FR-REQ011**
- **Requirement:** Staff can register walk-in customers and pet patients.
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:**
  - A walk-in page where staff enter the owner (no online account needed) and the pet, or pick an existing owner.
  - It warns if the mobile number already exists (prevents duplicates).
- **Files involved:**
  - New: `Staff/WalkInController.php`, `staff/walk-ins/create.blade.php`
  - Dev phase: 11
- **Testing method:** Register a walk-in → the customer and pet are saved, and the visit appears on the flow board as "Waiting". Registering the same mobile twice shows a warning.

**FR-REQ012**
- **Requirement:** Patient flow monitoring with the statuses waiting, ongoing service, completed and cancelled.
- **Current implementation:** None. Only a label "Patient Flow Report" in a dropdown.
- **Status:** MISSING
- **What needs to be changed:**
  - `patient_visits` table.
  - A flow board with Start / Complete / Cancel buttons.
  - Times are recorded for each change.
  - The page refreshes automatically so all staff see the same thing.
  - No customer notifications (Limitations).
- **Files involved:**
  - New: `PatientFlowController.php`, `patient-flow.blade.php`
  - Dev phase: 11
- **Testing method:** Waiting → Start → Ongoing → Complete → Completed. Cancel asks for a reason. Two browsers show the same board.

**FR-REQ013**
- **Requirement:** Organize customers by service: consultation, vaccination, treatment or grooming.
- **Current implementation:**
  - The service dropdowns list only Consultation, Vaccination and Grooming; **"Treatment" is missing**.
  - `adminservices.html` lists 5 different services.
  - Lists are typed in and don't match each other.
- **Status:** INCORRECT
- **What needs to be changed:** A `services` table where each service has a purpose (consultation/vaccination/treatment/grooming). Every form and the flow board read from it, and the board can filter by purpose.
- **Files involved:**
  - Old: appointment.html, newappointment.html, editappointment.html, adminservices.html
  - New: `Admin/ServiceController.php`
  - Dev phases: 2, 9 and 11
- **Testing method:** Add a service in Admin → it appears in every service dropdown. Filter the board by "Treatment".

## Inventory Management Module

**FR-REQ014**
- **Requirement:** Authorized users can monitor medicines, vaccines and clinic supplies.
- **Current implementation:**
  - `admininventory.html` and `superadmininventory.html` are typed-in tables.
  - **There is no "Medicines" category.**
  - Products sold at the counter, such as dog food, have no category either.
- **Status:** PARTIAL
- **What needs to be changed:** An `inventory_items` table with the categories medicine, vaccine, supply and product. Lists with search and filters.
- **Files involved:**
  - Old: admininventory.html, superadmininventory.html
  - New: `InventoryController.php`, views
  - Dev phase: 12
- **Testing method:** Add one item of each category. The category filter shows the right items.

**FR-REQ015**
- **Requirement:** Track inventory quantities and expiration dates.
- **Current implementation:** Typed-in quantities. Expiry dates appear only on the Super Admin page; **the Admin page, where items are managed, has no expiry column.**
- **Status:** PARTIAL
- **What needs to be changed:** Stock is recorded in batches, each with its own expiry date. The quantity is the sum of the batches and can never go below 0.
- **Files involved:**
  - New: `inventory_batches` table
  - Dev phase: 12
- **Testing method:** Stock-in 2 batches with different expiry dates → the total is correct and both expiry dates show.

**FR-REQ016**
- **Requirement:** Low-stock and expiration alerts (shown as "in-app notifications" per the Scope).
- **Current implementation:** Typed-in "Low Stock" badges. No expiry alerts.
- **Status:** PARTIAL
- **What needs to be changed:**
  - Alerts are computed automatically: quantity ≤ reorder level, or expiry within N days (N set in Settings).
  - A bell/badge in the staff and vet menus, plus a dashboard panel.
  - Expired batches cannot be used or sold.
- **Files involved:**
  - New: `InventoryAlertService.php`, nav partials, dashboards
  - Dev phase: 12
- **Testing method:** Use stock until it drops below the reorder level → an alert appears. Add a batch expiring in 10 days → an expiry alert appears.

**FR-REQ017**
- **Requirement:** Authorized users update inventory when items are added or used.
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** Stock-in, usage and adjustment forms. Every change writes a row in the usage log (`inventory_movements`).
- **Files involved:**
  - New: `InventoryService.php`, `inventory_movements` table
  - Dev phase: 12
- **Testing method:** Record a usage of 3 → the quantity drops by 3 and a log row shows who did it and when.

## POS and Transaction Module

**FR-REQ018**
- **Requirement:** Cashiers and authorized staff record payment transactions and clinic sales.
- **Current implementation:** Only read-only typed-in lists: `superadminpos.html` (under Super Admin) and `adminpayments.html` (under Admin). **Staff, who are the cashiers, have no POS at all.**
- **Status:** INCORRECT
- **What needs to be changed:**
  - A Staff POS page: pick the customer and pet, add services and products, enter the payment, then save.
  - Saving creates the bill, its items and the payment, and deducts stock, all inside one database transaction (all-or-nothing).
  - Vet/Admin and Super Admin can only view (decisions P1, P2).
- **Files involved:**
  - Old: superadminpos.html, adminpayments.html
  - New: `Staff/PosController.php`, `PosService.php`, `staff/pos.blade.php`
  - Dev phase: 14
- **Testing method:** Sell a consultation plus 1 bag of dog food → a receipt appears and dog food stock drops by 1. If anything fails, nothing is saved.

**FR-REQ019**
- **Requirement:** Support both walk-in and appointment-based transactions.
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A bill can be linked to a walk-in visit or to an appointment. There is a "Bill this visit" button on the flow board and on the appointment details page.
- **Files involved:**
  - New: `transactions` table (appointment_id / patient_visit_id)
  - Dev phase: 14
- **Testing method:** Bill one walk-in and one appointment. Each transaction shows its type and link.

**FR-REQ020**
- **Requirement:** Securely store transaction records for monitoring and reporting.
- **Current implementation:**
  - Typed-in rows using two different ID styles (PAY-… vs TXN-…).
  - **Prices contradict the Services page** (Consultation ₱800 vs ₱500).
- **Status:** MISSING
- **What needs to be changed:**
  - Unique receipt numbers.
  - Totals are calculated by the server, never trusted from the browser.
  - Paid bills cannot be edited, only voided with a reason, and the void is logged.
- **Files involved:**
  - Old: adminpayments.html, superadmintransactions.html
  - New: `TransactionController.php`
  - Dev phase: 14
- **Testing method:** Change a price in the browser's developer tools before saving → the server uses the real price. Voiding requires a reason.

**FR-REQ021**
- **Requirement:** Administrators generate sales and transaction summaries.
- **Current implementation:** A typed-in "Revenue Summary" in `adminreports.html`.
- **Status:** PARTIAL
- **What needs to be changed:** Summaries by date range, service and payment method, calculated from MySQL.
- **Files involved:**
  - Old: adminreports.html, superadminreports.html
  - New: `ReportController.php`
  - Dev phase: 15
- **Testing method:** The summary total equals the sum of paid transactions in the date range (voided ones excluded).

## Digital Waiver and Consent Forms Module

**FR-REQ022**
- **Requirement:** Staff prepare and manage digital waiver and consent forms. Per Interview 2, these cover operations, refusal of treatment, health certificates and major procedures.
- **Current implementation:** None (only a typed-in list for Super Admin).
- **Status:** MISSING
- **What needs to be changed:** Waiver templates plus a Staff page to create a waiver for a specific customer, pet and procedure.
- **Files involved:**
  - New: `waiver_templates`, `waivers` tables, `Staff/WaiverController.php`
  - Dev phase: 13
- **Testing method:** Staff creates a "Refusal of treatment" waiver for Max → it shows as "Pending signature".

**FR-REQ023**
- **Requirement:** Securely store completed waiver and consent forms.
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** When signed, a copy of the exact text, the signer's name, the date and the IP address are stored and can never be edited. The customer signs at the clinic or in the portal (decision P5).
- **Files involved:**
  - New: as FR-REQ022, plus `Customer/WaiverController.php`
  - Dev phase: 13
- **Testing method:** Sign it → the status is "Signed". Edit the template afterwards → the signed copy doesn't change.

**FR-REQ024**
- **Requirement:** Authorized users review and monitor submitted waiver records.
- **Current implementation:** `superadminwaivers.html` has 2 typed-in rows and no "view" page.
- **Status:** PARTIAL
- **What needs to be changed:** The vet reviews the waiver and marks it "Reviewed". Super Admin gets a read-only list.
- **Files involved:**
  - Old: superadminwaivers.html
  - New: `WaiverReviewController.php`
  - Dev phase: 13
- **Testing method:** The vet marks it reviewed → the reviewer and time show, and the action is in the activity log.

## Dashboard and Reports Module

**FR-REQ025**
- **Requirement:** Dashboards for monitoring clinic operations.
- **Current implementation:** `admindashboard.html`, `assistantdashboard.html`, `superadmindashboard.html` and `dashboard.html` exist, all with typed-in numbers.
- **Status:** PARTIAL
- **What needs to be changed:** All numbers come from MySQL.
- **Files involved:**
  - Old: the 4 dashboards
  - New: `*/DashboardController.php`
  - Dev phase: 15
- **Testing method:** Add an appointment for today → "Today's Appointments" goes up by 1.

**FR-REQ026**
- **Requirement:** Show summaries of appointments, transactions, inventory status and patient monitoring. The Scope adds: today's appointments, current patient status, low-stock alerts and **recent patient registrations**.
- **Current implementation:** Typed-in appointment, transaction and inventory numbers. No patient-status or recent-registrations panels.
- **Status:** PARTIAL
- **What needs to be changed:** Add the missing panels and compute every number.
- **Files involved:** Same as FR-REQ025 (Dev phase 15).
- **Testing method:** Each panel matches the underlying list page.

**FR-REQ027**
- **Requirement:** Reports on appointments, inventory, transactions and pet records. The Scope adds a **patient/customer flow** report and a **daily customer/patient count**. Fig 6.2: filter by date range and status, validate the filter input, and optionally export.
- **Current implementation:**
  - `adminreports.html` is typed in.
  - The `superadminreports.html` "Generate Report" button does nothing (its script looks for elements that don't exist).
  - No flow report, no daily count, no export.
- **Status:** PARTIAL
- **What needs to be changed:** A report generator with filters and validation, CSV export and a print view. Staff, Admin and Super Admin can run reports.
- **Files involved:**
  - Old: adminreports.html, superadminreports.html
  - New: `ReportController.php`, `reports/*.blade.php`
  - Dev phase: 15
- **Testing method:** Each report's numbers match the database. A "From" date after the "To" date shows an error message. The CSV opens in Excel.

**FR-REQ028**
- **Requirement:** Dashboard views based on the user's assigned role.
- **Current implementation:** Four role dashboards exist, but anyone can open any of them.
- **Status:** PARTIAL
- **What needs to be changed:** After login, each role goes only to its own dashboard. Other dashboards are blocked.
- **Files involved:** routes + middleware (Dev phases 4 and 15).
- **Testing method:** A customer opening the Admin dashboard address → blocked.

---

# Part B — Requirements from the Scope, Sprints and Diagrams (no REQ number)

**SCOPE-01 — Appointment slot selection & slot management** (Sprint 4, Conceptual Framework, Fig 6.5)
- **Current implementation:** Free date and time boxes; any time can be typed in.
- **Status:** MISSING
- **What needs to be changed:**
  - Clinic hours, slot length and slots-per-time are set in Settings.
  - The booking form shows only free slots.
  - The server blocks double booking.
- **Files involved:** appointment.html → `customer/appointments/create.blade.php`, `BookingService.php`, `clinic_hours` table (Dev phase 9)
- **Testing method:** A full slot doesn't appear. Past dates are rejected. Two people booking the last slot at the same moment → only one succeeds.

**SCOPE-02 — Staff approve, update and cancel appointments** (Sprint 4)
- **Current implementation:** Buttons exist on `assistantappointmentdetails.html` and `adminappointmentdetails.html` but only show an alert or do nothing.
- **Status:** PARTIAL
- **What needs to be changed:** Real status changes, with rules (e.g. Completed can't go back to Pending) and a cancel reason.
- **Files involved:** Dev phase 9
- **Testing method:** Staff confirms → the customer sees "Confirmed" in their history.

**SCOPE-03 — Customer views appointment status and booking history**
- **Current implementation:** `history.html` has one typed-in card.
- **Status:** PARTIAL
- **What needs to be changed:** The customer's real appointments, upcoming and past.
- **Files involved:** Dev phase 9
- **Testing method:** Only the customer's own appointments are shown.

**SCOPE-04 — Customer downloads booking-history records** (Fig 6.8)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A download/print button for the booking history.
- **Files involved:** Dev phase 9
- **Testing method:** The downloaded file contains only the customer's own records.

**SCOPE-05 — Customer fills out waiver forms; staff assist** (Scope, Sprint 7)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A "Forms to sign" page in the portal, plus a sign-at-clinic page for staff.
- **Files involved:** Dev phase 13
- **Testing method:** The customer signs in the portal → staff see it as "Signed".

**SCOPE-06 — Inventory usage logs** (Scope)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** An append-only usage log, viewable and filterable.
- **Files involved:** Dev phase 12
- **Testing method:** Every stock change has a log row, and log rows cannot be edited.

**SCOPE-07 — Supplier management** (Scope: Staff; Requirements Analysis)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** Supplier add, edit, deactivate and list. Batches record their supplier.
- **Files involved:** `Staff/SupplierController.php` (Dev phase 12)
- **Testing method:** Add a supplier and choose it during stock-in. Deactivating keeps its history.

**SCOPE-08 — Receipt generation** (Scope POS, Fig 6.5)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A printable receipt with the receipt number.
- **Files involved:** Dev phase 14
- **Testing method:** The receipt shows the items, total, amount paid, change and balance.

**SCOPE-09 — Collect remaining balance** (Fig 6.5)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A bill can have more than one payment and shows the remaining balance (decision P12).
- **Files involved:** `payments` table (Dev phase 14)
- **Testing method:** Pay ₱500 of ₱800 → the balance is ₱300. Pay ₱300 → the status is "Paid".

**SCOPE-10 — POS sells services and products** (Scope, Interview 3: medicines, vaccines, grooming, dog food, other products)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** The POS lists both services and inventory products.
- **Files involved:** Dev phase 14
- **Testing method:** Selling a product lowers its stock.

**SCOPE-11 — Super Admin assigns roles and permissions** (Scope)
- **Current implementation:** Only a role filter dropdown on `superadminusers.html`.
- **Status:** MISSING
- **What needs to be changed:** A permission checklist per role that Super Admin can edit.
- **Files involved:** `permissions`, `permission_role` tables, `SuperAdmin/RolePermissionController.php` (Dev phases 4 and 8)
- **Testing method:** Untick "POS" for Staff → Staff opening the POS gets 403.

**SCOPE-12 — Super Admin monitors system activity** (Scope, NFR-REQ023/024)
- **Current implementation:** `superadminactivity.html` has 4 typed-in rows.
- **Status:** PARTIAL
- **What needs to be changed:** Real automatic logging plus a filterable viewer.
- **Files involved:** Dev phase 16
- **Testing method:** Log in → a "login" row appears with the time and IP address.

**SCOPE-13 — Database backup and recovery** (Scope)
- **Current implementation:** `superadminbackup.html` only saves a timestamp in the browser and **falsely** says "Database backup created successfully".
- **Status:** INCORRECT
- **What needs to be changed:** A real MySQL backup file, a list of backups, download, and restore with confirmation.
- **Files involved:** `BackupService.php` (Dev phase 17)
- **Testing method:** Back up → delete a pet → restore → the pet is back.

**SCOPE-14 — System settings and maintenance** (Scope, Sprint 10)
- **Current implementation:** `superadminsettings.html` saves to the browser only; "Maintenance" does nothing.
- **Status:** INCORRECT
- **What needs to be changed:** A `settings` table (clinic info, hours, alert days) and a real maintenance mode that blocks everyone except Super Admin.
- **Files involved:** Dev phase 17
- **Testing method:** Turn on maintenance → Staff see a maintenance page while Super Admin still works.

**SCOPE-15 — Vet/Admin checks walk-in and patient flow** (Scope: Admin)
- **Current implementation:** None.
- **Status:** MISSING
- **What needs to be changed:** A flow board view for Vet/Admin.
- **Files involved:** Dev phase 11
- **Testing method:** The vet sees the same board as Staff.

**SCOPE-16 — Assign a veterinarian to visits and records** (Interview 2: 3–5 vets per day)
- **Current implementation:** The text "Dr. Maria Santos" is typed in.
- **Status:** MISSING
- **What needs to be changed:** A veterinarian dropdown on visits and records.
- **Files involved:** Dev phases 10 and 11
- **Testing method:** The record shows the assigned vet.

---

# Part C — Non-Functional Requirements

| ID | Requirement | Current implementation | Status | What needs to be changed | Files / Dev phase | Testing method |
|---|---|---|---|---|---|---|
| NFR-REQ001 | Simple, user-friendly interface | Clean card/table design, but ~60 buttons do nothing and messages are pop-up `alert()`s | PARTIAL | Every button works; messages show inside the page | all views / 5–17 | Click every button on every page: nothing is dead |
| NFR-REQ002 | Clear navigation menus | Admin menu differs from page to page (some lack Payments/Inventory/Reports). Admin dashboard "Logout" goes to home.html. Admin profile isn't in the menu | INCORRECT | One menu file per role, used by every page | `partials/nav/*` / 1 | Every page of a role shows the same menu |
| NFR-REQ003 | Usable in common browsers on desktop and mobile | Works on desktop; issues on mobile (see NFR-REQ017) | PARTIAL | See 016/017 | / 18–19 | See 016/017 |
| NFR-REQ004 | Reasonable response time | Static pages are fast, but `bg.jpg` is a 4.1 MB image | NEEDS TESTING | Compress the image; database indexes; paging | / 18–19 | Pages load in under ~2 s with 1,000+ records |
| NFR-REQ005 | Many users at the same time | Data is saved per browser (localStorage), so users can't share data | MISSING | MySQL + Laravel sessions | / 2–3 | Two people logged in at once see the same data |
| NFR-REQ006 | Real-time updates of schedules, inventory, transactions | Everything is typed in | MISSING | Data read live from MySQL; the flow board and dashboards refresh automatically | / 11, 15 | A change by one user appears for another within ~15 s |
| NFR-REQ007 | Accurate, consistent records | Typed-in data contradicts itself (prices, counts, dates) | INCORRECT | All numbers come from the database | / 2–15 | Dashboard counts equal the list counts |
| NFR-REQ008 | Minimize errors in transactions | No transactions exist | MISSING | Server-calculated totals, all-or-nothing saves | / 14 | Force an error mid-sale → nothing half-saved |
| NFR-REQ009 | Minimize downtime | — | NEEDS TESTING | Deployment matter | / 19 | Uptime check after deployment |
| NFR-REQ010 | Secure login for admin, vet and staff | Typed-in passwords; admin login accepts anything | INCORRECT | Laravel auth, hashed passwords, throttling | / 3 | Wrong password refused; 6th try blocked |
| NFR-REQ011 | Protect sensitive records (contact numbers, addresses, emails, medical records, sales/income per Interview 2) | Every page opens without logging in | INCORRECT | Login + roles + ownership checks on every page | / 4, 18 | Logged out → every inside address sends you to login |
| NFR-REQ012 | Role-based access by permissions | None | MISSING | Middleware + permissions + policies | / 4 | Full role-vs-address test table passes |
| NFR-REQ013 | Scalability | — | MISSING | Proper tables, indexes, paging | / 2 | Seed 10,000 records; pages still fast |
| NFR-REQ014 | Structured, modular design | 49 copies of each menu; one huge CSS file | PARTIAL | Blade layouts and partials, controllers per module, service classes | / 1 | Changing a menu means editing one file |
| NFR-REQ015 | Future improvements without rebuilding | Same as above | PARTIAL | Same | / 1 | — |
| NFR-REQ016 | Works in Chrome, Firefox and Edge | Only tested in Chromium; images are AVIF files named .jpg (supported by all three) | NEEDS TESTING | Test in all three | / 19 | Open key pages in each browser |
| NFR-REQ017 | Works on desktop and mobile | On a phone screen: **admin dashboard is wider than the screen**, and the Super Admin menu shows only 3 of 13 links | PARTIAL | Small CSS additions | `public/animal.css` / 18 | Phone-size view: no sideways scrolling, all menu links reachable |
| NFR-REQ018 | Available during clinic hours | — | NEEDS TESTING | Deployment | / 19 | — |
| NFR-REQ019 | Depends on a stable internet connection | (an assumption, not a feature) | N/A | — | — | — |
| NFR-REQ020 | Minimize interruption during maintenance | "Maintenance" setting does nothing | MISSING | Real maintenance mode | / 17 | See SCOPE-14 |
| NFR-REQ021 | Prevent duplicate and inconsistent records | None | MISSING | Unique email/receipt numbers, duplicate-customer warning, foreign keys | / 2, 11 | Duplicate email/receipt is refused |
| NFR-REQ022 | Accurate appointment, inventory, payment and medical records | None | MISSING | Validation on every form, database transactions, row locks | / 9–14 | Invalid input is refused with a clear message |
| NFR-REQ023 | Log logins, appointments, transactions and inventory updates | None | MISSING | Automatic activity logger | / 16 | Each action creates a log row |
| NFR-REQ024 | Keep activity logs for monitoring and troubleshooting | Typed-in table | PARTIAL | Read-only log viewer with filters | / 16 | Filter by user, module and date works |

---

# Part D — Other findings that affect the work

| Finding | Impact |
|---|---|
| The `image/` folder is in the ZIP but was missing from the GitHub repo | Background pictures break unless `image/` is copied next to `animal.css` |
| `image/Educational_Videos.html` is unrelated school content | Not carried into Laravel (kept in the original backup) |
| `animal.js` throws `ReferenceError: accountContinueBtn is not defined` on 18 pages | Fixed in Dev phase 1 |
| Appointment add/edit/delete code in `animal.js` never runs (no page has its elements) | Removed; replaced by Laravel |
| The paper contradicts itself on roles (Scope vs use-case diagrams) | Decisions P1–P18 in `docs/PHASE2_MIGRATION_PLAN.md` §2 |
