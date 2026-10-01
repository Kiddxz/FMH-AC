# FMH Animal Clinic — Development Plan (20 phases)

**Your environment:** Windows · Visual Studio Code · Laravel Herd · PHP 8.4 · MySQL
**Project folder:** `C:\Users\mae\Herd\FMH AC`

**Rules for every phase**
- One phase at a time. You test it, tell me the result, and only then do we start the next one.
- Before I give you any code, I build and test it myself in a Laravel copy on my side, so what you paste has already been run once.
- For every file you get:
  - the exact path
  - whether to **create** it or **replace** it
  - the **complete** contents
  - the VS Code steps
  - the terminal commands and where to run them
  - a test checklist, the expected results, and what to do if there's an error
- Your original design (`animal.css`, page markup, emoji icons, images) is preserved. New pages reuse the same CSS classes.
- The original HTML project is kept untouched as a backup (`legacy-frontend/` in GitHub, plus your own ZIP).
- Requirement IDs refer to `docs/REQUIREMENT_TRACEABILITY.md`.

### How Laravel pieces fit together (beginner overview)

| Piece | Folder | What it does |
|---|---|---|
| **Route** | `routes/web.php` | Connects a web address (e.g. `/staff/pos`) to code |
| **Controller** | `app/Http/Controllers/` | The PHP code that runs for that address: checks permission, reads/saves data, picks the page |
| **Model** | `app/Models/` | Represents one database table (e.g. `Pet` ↔ `pets` table) |
| **Migration** | `database/migrations/` | A file that creates or changes a database table. `php artisan migrate` runs them |
| **Seeder** | `database/seeders/` | Fills the database with starting data (roles, demo users, services) |
| **Blade view** | `resources/views/` | Your HTML page, with `{{ }}` spots where real data appears |
| **Middleware** | `app/Http/Middleware/` | A guard that runs before a page opens (e.g. "must be logged in as Staff") |
| **Policy** | `app/Policies/` | Rules like "a customer may only open *their own* pet" |
| **public/** | `public/` | Files the browser loads directly: `animal.css`, `animal.js`, `image/` |

---

## The phases

### PHASE 1 — Laravel project structure
- **Goal:** Your existing pages run inside Laravel and look exactly the same. No database yet.
- **Work:**
  - Copy `animal.css`, `animal.js` (with the 18-page error fixed) and `image/` into `public/`.
  - Create 5 layouts (public, customer, staff, admin, superadmin) and 4 menu files (one per role), built from your current markup.
  - Convert the pages to Blade views, still with sample data.
  - Create `routes/web.php` with every address from the migration plan.
- **Requirements touched:** NFR-REQ002 (one menu per role), NFR-REQ014/015 (modular).
- **Test:** Open each address in Herd and compare it with the old HTML page. Same look, all menu links work, no console errors.

### PHASE 2 — Database and migrations
- **Goal:** All MySQL tables created.
- **Work:**
  - Before writing migrations, I show you the full table list (columns, keys, relationships, statuses) for approval.
  - Then: migrations, models with relationships, and seeders (roles, permissions, services, demo accounts, fictitious records using your current sample names).
- **Requirements touched:** FR-REQ009, FR-REQ020, FR-REQ023, NFR-REQ013, NFR-REQ021.
- **Test:** `php artisan migrate:fresh --seed` runs without errors; the tables are visible in your MySQL tool.

### PHASE 3 — Authentication
- **Goal:** Real login and registration.
- **Work:**
  - One login page.
  - Registration with an emailed 6-digit code.
  - Forgot/reset password by code (new ≠ old).
  - Throttling, logout, remember-me.
  - The typed-in passwords are deleted from `animal.js`.
- **Requirements touched:** FR-REQ001, NFR-REQ010.
- **Test:** register → code → login; wrong password; reset; logout. During development, codes appear in `storage/logs/laravel.log`.

### PHASE 4 — Role-based access
- **Goal:** Each role only reaches its own pages.
- **Work:**
  - Role middleware on `/portal`, `/staff`, `/admin` and `/superadmin`.
  - Permission checks (editable by Super Admin later).
  - Ownership policies.
  - A 403 page in the FMH style.
- **Requirements touched:** FR-REQ002, FR-REQ028, NFR-REQ011, NFR-REQ012.
- **Test:** an automated test plus a manual checklist: every role × every role's addresses.

### PHASE 5 — Customer / Pet Owner module
- **Goal:** A working customer portal.
- **Work:** dashboard with real counts, profile edit + change password, My Pets (add, edit, view).
- **Requirements touched:** FR-REQ003, FR-REQ005, FR-REQ006.
- **Test:** two customer accounts can't see each other's pets.

### PHASE 6 — Staff / Receptionist module
- **Goal:** Staff area working.
- **Work:**
  - Staff dashboard and profile.
  - Customer directory (search).
  - Pet list and pet profile editing.
  - The "Assistant" labels renamed to "Staff/Receptionist".
- **Requirements touched:** FR-REQ003, FR-REQ008 (profiles), FR-REQ010.

### PHASE 7 — Veterinarian / Admin module
- **Goal:** Vet/Admin area working.
- **Work:**
  - Admin dashboard and profile.
  - Services management (price list used by booking and POS).
  - Read-only customer directory.
  - Pet list.
- **Requirements touched:** FR-REQ003, FR-REQ013 (service list).

### PHASE 8 — Super Admin module
- **Goal:** System administration.
- **Work:**
  - User accounts: create, edit, activate, deactivate.
  - Roles & permissions screen.
  - Super Admin profile.
  - The read-only oversight pages from your current Super Admin menu.
- **Requirements touched:** FR-REQ004, SCOPE-11.

### PHASE 9 — Appointments
- **Goal:** Online booking with time slots.
- **Work:**
  - Clinic hours and slots.
  - Customer booking: pick a registered pet, a service and a free slot.
  - Staff/Admin approve, update and cancel, with status rules.
  - Customer history and download.
- **Requirements touched:** SCOPE-01 to SCOPE-04, FR-REQ006.
- **Test includes:** double booking is impossible.

### PHASE 10 — Pet records
- **Goal:** Digital medical records.
- **Work:**
  - Consultation (with the vet-typed assessment), treatments, prescriptions, vaccinations.
  - Care instructions released to the owner, which the customer can download.
  - Search.
  - Staff see vaccination history only (decision P4).
- **Requirements touched:** FR-REQ007 to FR-REQ010, SCOPE-16.

### PHASE 11 — Walk-in and patient flow
- **Goal:** Walk-in registration and an internal flow board.
- **Work:**
  - Walk-in customer + pet registration, with a duplicate-mobile warning.
  - Checking in appointments.
  - Board columns: Waiting / Ongoing / Completed / Cancelled, filtered by purpose, auto-refreshing.
  - Vet/Admin view.
- **Requirements touched:** FR-REQ011 to FR-REQ013, SCOPE-15.
- **Rule:** no customer notifications (paper Limitations).

### PHASE 12 — Inventory
- **Goal:** Stock you can trust.
- **Work:**
  - Items in four categories: medicine, vaccine, supply, product.
  - Batches with expiry dates; stock-in, usage and adjustment.
  - Usage log.
  - Low-stock and expiry alerts (bell + dashboard).
  - Suppliers.
- **Requirements touched:** FR-REQ014 to FR-REQ017, SCOPE-06, SCOPE-07.

### PHASE 13 — Waiver / Consent
- **Goal:** Paperless consent.
- **Work:**
  - Templates (operation, refusal of treatment, health certificate, major treatment).
  - Staff prepare a waiver.
  - The customer signs at the clinic or in the portal.
  - The signed copy is locked.
  - The vet reviews; Super Admin can view.
- **Requirements touched:** FR-REQ022 to FR-REQ024, SCOPE-05.

### PHASE 14 — POS and transactions
- **Goal:** Cashier recording of in-clinic payments.
- **Work:**
  - POS for services and products.
  - Walk-in and appointment bills.
  - Multiple payments with a remaining balance.
  - Printable receipts.
  - Void with reason.
  - Stock deducted inside one database transaction.
  - No online payment (paper Limitations).
- **Requirements touched:** FR-REQ018 to FR-REQ020, SCOPE-08 to SCOPE-10, NFR-REQ008.

### PHASE 15 — Reports (and dashboards)
- **Goal:** Real numbers everywhere.
- **Work:**
  - Reports: appointments, patient flow, inventory, pet records, transactions/sales, daily patient count.
  - Date and status filters with validation.
  - CSV export and a print view.
  - Every dashboard panel computed from the database.
- **Requirements touched:** FR-REQ021, FR-REQ025 to FR-REQ027, NFR-REQ006/007.

### PHASE 16 — Activity logs
- **Goal:** Know who did what.
- **Work:**
  - Automatic logging of logins, appointments, transactions, inventory, waivers and admin actions.
  - Super Admin viewer with filters.
- **Requirements touched:** NFR-REQ023, NFR-REQ024, SCOPE-12.

### PHASE 17 — Backup/recovery and settings
- **Goal:** Real system maintenance.
- **Work:**
  - MySQL backups (create, list, download, restore with confirmation).
  - Settings: clinic info, hours, alert days.
  - Maintenance mode.
- **Requirements touched:** SCOPE-13, SCOPE-14, NFR-REQ020.

### PHASE 18 — Security
- **Goal:** A full security pass.
- **Work:**
  - Re-check every route for login, role and ownership.
  - CSRF, validation, mass-assignment protection, secure headers, `APP_DEBUG=false` for deployment.
  - Mobile CSS fixes (admin dashboard overflow, Super Admin menu).
  - Compress `bg.jpg`.
- **Requirements touched:** NFR-REQ011/012/017/004.

### PHASE 19 — Testing
- **Goal:** Proof that it works.
- **Work:**
  - Automated tests (`php artisan test`) for every module and for unauthorized access.
  - A manual test script for user acceptance testing.
  - Browser check in Chrome, Firefox and Edge, on desktop and phone sizes.
- **Requirements touched:** all NFRs marked NEEDS TESTING.

### PHASE 20 — Final capstone requirement audit
- **Goal:** An honest final table.
- **Work:** Requirement | Status | Evidence (page/feature) | Test result for every FR, SCOPE and NFR item. Anything not fully done is explained.

---

## Decisions in use (you can still change them)

These are the defaults from `docs/PHASE2_MIGRATION_PLAN.md` §2:

| # | Decision |
|---|---|
| P1 | Super Admin pages for clinic data are **read-only**, and medical notes are hidden from Super Admin |
| P2 | **Staff** run the POS; Vet/Admin views transactions only |
| P3 | **Staff** manage inventory and suppliers; Vet/Admin views inventory and records usage |
| P4 | **Vets** write clinical records; Staff see vaccination history only |
| P5 | Waivers can be signed at the clinic or in the portal |
| P6 | Only Super Admin manages accounts |
| P7 | "Assessment/Diagnosis" is typed by the vet only; nothing is automated |
| P11 | Cash, GCash, Maya and Card are recorded by hand; no payment gateway |
| P12 | Partial payments with a running balance are supported |
| P15 | **One login page** for everyone |
