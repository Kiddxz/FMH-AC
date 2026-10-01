# FMH Animal Clinic — Phase 1 Audit v2 (checked against the capstone paper)

**Sources inspected**

| Source | Details |
|---|---|
| Capstone paper | *A Web-Based Sales, Inventory and Appointment Management System for FMH Animal Clinic*, STI College Las Piñas, May 28 2026, 73 pp. All text, the use-case, activity and ERD diagrams, and the 3 client interviews were read |
| ZIP file | `FMH_animal_clinic.zip`, compared file-by-file with the GitHub repo |

**No project files were modified.**

### Status legend
| Status | Meaning |
|---|---|
| **COMPLETE** | Works end-to-end with MySQL plus backend authorization. *Nothing qualifies yet.* |
| **PARTIAL** | A UI exists but is static HTML or localStorage only. |
| **MISSING** | No UI and no logic. |
| **INCORRECT** | Exists but is wrong, insecure or misleading. |
| **NEEDS TESTING** | Can only be judged once built or deployed. |

---

## A. Current project structure

### ZIP vs repository

| Item | Finding |
|---|---|
| 49 HTML + `animal.css` + `animal.js` | **Byte-for-byte identical** in the ZIP and the repo |
| `image/home.jpg, services.jpg, about.jpg, contact.jpg, bg.jpg` | **In the ZIP only.** `animal.css` references them (`url("image/…")`), so **background images are broken in the repo** |
| Image formats | The four `.jpg` files are actually **AVIF** images, and `bg.jpg` is a **4.1 MB PNG**. Chrome, Firefox and Edge display them, but the 4 MB background slows page loads (NFR004) |
| `image/Educational_Videos.html` | **Unrelated** (Las Piñas National High School GAD/Brigada video page, referencing missing `.mp4` files). Recommend excluding it from the new app; it is kept in the original backup |

### Page inventory

```
Public/auth (7):  home, login, register, forgotpass, adminlogin, assistantlogin, superadminlogin
Customer (6):     dashboard, profile, mypets, addpet, appointment, history
Admin (14):       admindashboard, adminappointments, adminappointmentdetails, newappointment, editappointment,
                  adminpets, adminpetrecords, adminusers, adminservices, adminpayments, admininventory,
                  adminreports, adminprofile, adminlogout
Assistant (7):    assistantdashboard, assistantappointments, assistantappointmentdetails, assistantpets,
                  assistantpetrecords, assistantprofile, assistantlogout
Super Admin (13): superadmindashboard, users, appointments, petrecords, waivers, pos, transactions,
                  inventory, reports, activity, backup, settings, logout
```

There is no backend, no database and no tests. **All data is hard-coded HTML.**

## B. Existing features (verified in a headless browser)

| Feature | Reality |
|---|---|
| Landing page with role-picker modal | ✅ Works (navigation only) |
| Customer/Assistant login (`login.html?role=…`) | ⚠️ Checks **hard-coded passwords in animal.js**, stores the role in localStorage |
| Super Admin login | ⚠️ Same approach (`superadmin123`) |
| Admin login | ❌ **Accepts any credentials.** It uses a GET to admindashboard.html, which puts the password in the URL |
| `assistantlogin.html` | ❌ **Always fails** (no `?role=`). The Assistant logout button sends users here |
| Appointment add/edit/delete/filter JS | ❌ **Dead code.** The element IDs it targets exist on no page |
| Edit appointment save | ⚠️ Writes localStorage `editedAppointment`, which is never read |
| Confirm/Cancel appointment | ❌ Admin: no handler. Assistant: `alert()` only |
| SA backup/recovery | ❌ Saves a timestamp to localStorage and says "Database backup created successfully" |
| SA settings | ⚠️ Saved to localStorage, never used |
| SA "Generate Report" | ❌ Handler targets IDs that don't exist |
| ~60 table/search/filter/Add/View/Edit/Delete buttons | ❌ No handlers |
| Register, Forgot Password, Add Pet, Book Appointment forms | ❌ `action="#"`, nothing is saved |
| JS runtime | ❌ `ReferenceError: accountContinueBtn is not defined` on **18 pages** (stray block at the end of animal.js) |
| Responsive CSS | ✅ 21 media queries (see the NFR section for defects) |

## C. Roles: paper vs existing

The paper's **Scope** (pp. 5–6) and **Requirements Analysis** (pp. 43–44) agree with each other, and I treat them as **authoritative**. The use-case diagrams (Figs 5.1–5.3) differ in places (see G).

| Paper role (who) | Paper responsibilities (Scope + Req. Analysis) | Existing equivalent | Gap |
|---|---|---|---|
| **Super Admin** (researchers/developers) | Manage user accounts, assign roles & permissions, monitor system activity, DB backup & recovery, system settings/maintenance. "Highest level of access" | "Super Admin" pages | Has the right pages but all are fake. Also holds POS/waiver/pet-record/appointment screens (conflict P1) |
| **Veterinarian/Admin** (doctors + admin) | Dashboards, monitor appointments, check walk-in/patient flow, **review/update medical records, add consultation notes, prescriptions, treatment records**, monitor inventory, review transaction summaries, generate reports. Limited system config | "Admin" | Has POS-like Payments, Users, Services. **No consultation, prescription or treatment entry, no patient-flow view** |
| **Staff/Receptionist** (Cashier + Office Assistant) | **Register walk-in customers & pets, process appointment requests, monitor patient flow, assist with waivers, update inventory, manage suppliers, record in-clinic payments via POS**, generate reports (Fig 6.2) | "Assistant" / "Veterinary Assistant" | Has only appointments (view/confirm) and pets (view). **Missing walk-in, patient flow, waivers, inventory, suppliers, POS and reports** |
| **Customer** | Create account, manage profile, create pet profiles, book appointments, view status & booking history, **fill out waiver forms when needed**, **download post-treatment care instructions**. No access to internal records, sales, staff records or full medical notes | "Pet Owner" | Static pages only. No care instructions, waivers or downloads |

**Enforcement today: none.** `userRole` is written but never read, and every URL opens without login.

## D. Existing modules vs paper modules

| Paper module (Scope) | Existing pages | Verdict |
|---|---|---|
| User Login & Role-Based Access | 4 logins, register, forgotpass | Insecure / fake |
| Dashboard | 4 role dashboards | Static |
| Inventory Monitoring | admininventory, superadmininventory | Static; no usage, logs, expiry alerts or suppliers |
| Walk-In & Patient Flow Monitoring | — | **Absent** |
| Digital Pet Records | adminpets, adminpetrecords, assistantpets, assistantpetrecords, superadminpetrecords, mypets, addpet | Static; no clinical records |
| Digital Waiver & Consent | superadminwaivers | Static list only |
| POS & Transaction Records | adminpayments, superadminpos, superadmintransactions | Read-only static lists; no POS |
| Reports | adminreports, superadminreports | Static |
| Customer Portal | dashboard, profile, mypets, addpet, appointment, history | Static |
| System Administration (Sprint 10) | superadminusers, activity, backup, settings | Fake |
| (not in paper as a module, but supports POS and booking) | adminservices | Static; keep it, because POS and booking need a service price list |

---

## E. Functional requirements: checklist and gap analysis

**Backend/DB required: YES for every row** (Laravel routes, controllers, models, validation and MySQL). "Files" names the existing pages that become Blade views, with **(new)** marking views to create.

### E1. Paper REQ001–REQ028

#### User Management

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ001 + Fig 6.3/6.4 | Create accounts and log in securely; **email verification code on registration**; **forgot password via emailed code, new password must differ from the old one**; redirect to the role's module | **INCORRECT** | Register saves nothing. Logins are hard-coded or accept anything. No email verification. Forgot-password does nothing | register, login, forgotpass, adminlogin, assistantlogin, superadminlogin, animal.js, verify-email (new), reset-password (new) | Register → code emailed → account active only after the code. Wrong password rejected; throttled after 5 tries. Reset rejects reusing the old password. Each role lands on its own dashboard |
| REQ002 | Four roles: Super Admin, Vet/Admin, Staff/Receptionist, Customer | **PARTIAL** | Page sets exist but nothing is enforced. "Assistant" label | all | Role × URL matrix test (403/redirect) |
| REQ003 | Users edit their own profile | **PARTIAL** | profile, adminprofile, assistantprofile are static, Edit buttons dead. **SA has no profile page** | the 3 profiles + SA profile (new) | Edit persists. Password change requires the current password |
| REQ004 | Administrators view/update/activate/deactivate accounts | **PARTIAL** | superadminusers + adminusers static, buttons dead | superadminusers, adminusers | Deactivated user cannot log in. Action is logged |

#### Customer Portal

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ005 | Portal: account info, booking details, pet records (basic) | **PARTIAL** | Hard-coded "Mark". Counts (0) contradict My Pets | dashboard, profile, mypets | Customer A sees only A's data |
| REQ006 | Manage pet profiles; monitor appointment info | **PARTIAL** | Add Pet saves nothing. No edit. Species only Dog/Cat/Other (interview: also hamsters, birds, exotics) | mypets, addpet, history, pet-edit (new) | CRUD own pets. Editing another owner's pet ID → 403 |
| REQ007 + Scope | Access & **download** post-treatment care instructions | **MISSING** | — | (new) customer pet page + PDF/print | Vet releases instructions → owner downloads them; other owners get 403 |

#### Digital Pet Records

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ008 | Vets & authorized staff create/update pet records | **PARTIAL** | Lists only, Add/View/Edit dead | adminpets, adminpetrecords, assistantpets, assistantpetrecords | Vet adds a record. Staff permissions per decision P4 |
| REQ009 | Store pet details, medical history, consultation records, prescriptions, treatments, vaccination records | **MISSING** (except static pet details) | assistantpetrecords has one static history table | adminpetrecords, assistantpetrecords, record forms (new) | Each record type saves and appears in the history timeline |
| REQ010 | Search & retrieve pet records | **PARTIAL** | Search boxes dead | all pet/record lists | Search by pet, owner or mobile returns the right rows |

#### Walk-in & Patient Flow

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ011 | Staff register walk-in customers & pets | **MISSING** | — | (new) staff walk-in page | Customer without an account is created and linked to the pet. Duplicate mobile is detected |
| REQ012 | Patient flow statuses: waiting, ongoing, completed, cancelled | **MISSING** | Only a dropdown label "Patient Flow Report" | (new) flow board | Status transitions are saved with timestamps. Two staff browsers see the same state |
| REQ013 | Organize customers by service: consultation, vaccination, treatment, grooming | **MISSING** (as flow) / **INCORRECT** (service lists) | Dropdowns list only Consultation/Vaccination/Grooming; **Treatment is missing**. adminservices shows 5 different services | appointment, newappointment, editappointment, adminservices | Board filters by purpose. All forms use the same DB service list |

#### Inventory

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ014 | Monitor medicines, vaccines, clinic supplies | **PARTIAL** | Static. **No "Medicines" category.** Products sold at POS (dog food etc., per Interview 3) have no category either | admininventory, superadmininventory | Add a medicine, a vaccine, a supply and a product |
| REQ015 | Track quantities & expiration dates | **PARTIAL** | Expiry shown only on the SA page; **admininventory, where items are managed, has no expiry column** | admininventory | Stock-in batch with expiry. Qty never goes below 0 |
| REQ016 + Scope | Low-stock & expiration alerts (**in-app notifications**) | **PARTIAL** (static Low Stock badge) / **MISSING** (expiry) | — | dashboards, header alert badge (new) | Qty ≤ reorder level → alert. Expiring within N days → alert. Expired batch is blocked from use/sale |
| REQ017 | Update inventory when items are added or used | **MISSING** | — | (new) stock-in, usage | Every change writes a usage-log row |

#### POS & Transactions

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ018 | Cashiers & authorized staff record payments and sales | **INCORRECT** | Only read-only lists (superadminpos under SA, adminpayments under Admin). **Staff has no POS** | superadminpos → staff POS (new) | Staff rings up services + products → receipt. Stock is deducted in one DB transaction |
| REQ019 | Walk-in and appointment-based transactions | **MISSING** | — | (new) | Bill from a flow-board visit and from an appointment |
| REQ020 | Securely store transactions | **MISSING** | Static rows. **Prices contradict the Services page** (Consultation ₱800 vs ₱500). Two ID schemes (PAY-/TXN-) | adminpayments, superadmintransactions | Unique receipt no. Total = sum of items. Paid transactions cannot be edited (void with reason only) |
| REQ021 | Administrators generate sales & transaction summaries | **PARTIAL** | Static revenue table | adminreports, superadminreports | Summary equals the DB sum for the date range |

#### Waivers & Consent

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ022 | Staff prepare & manage waiver/consent forms (operations, refusal of treatment, health certificates, other major procedures) | **MISSING** | — | (new) staff waiver pages | Staff creates a waiver for customer/pet/procedure |
| REQ023 | Securely store completed forms | **MISSING** | — | (new) | Signed snapshot is immutable; later template edits don't alter it |
| REQ024 | Authorized users review/monitor submitted waivers | **PARTIAL** | superadminwaivers: 2 static rows, no view page | superadminwaivers | Review action is recorded and logged |

#### Dashboards & Reports

| Paper ref | Requirement | Status | Existing / gap | Files | Test |
|---|---|---|---|---|---|
| REQ025 | Dashboards for monitoring operations | **PARTIAL** | 4 dashboards, all numbers hard-coded | 4 dashboards | Counts match DB queries |
| REQ026 + Scope | Summaries: appointments today, **current patient status**, low-stock alerts, **recent patient registrations**, transactions | **PARTIAL** (appt/txn/inventory static) / **MISSING** (patient status, recent registrations) | — | 4 dashboards | |
| REQ027 + Scope + Fig 6.2 | Reports: appointments, **patient/customer flow**, inventory, pet record summaries, transactions, **daily customer/patient count**; filter by date range & status; validation; **optional export** | **PARTIAL** | Static tables. Generate button dead. No flow or daily-count reports, no export | adminreports, superadminreports, staff reports (new) | Each report matches DB data. Invalid date range shows an error. Export file opens |
| REQ028 | Dashboard per assigned role | **PARTIAL** | 4 exist but are reachable by anyone | 4 dashboards | Each role gets only its own dashboard |

### E2. Requirements stated in Scope / Sprints / Figures but without a REQ number

| Paper ref | Requirement | Status | Existing / gap | Test |
|---|---|---|---|---|
| Sprint 4, Concept. framework, Fig 6.5 | **Available time-slot selection & slot management** (calendar, avoid double booking) | **MISSING** | Free date/time inputs only | Full slot rejected. Past date rejected. Concurrent last-slot booking: only one succeeds |
| Sprint 4 | Staff **approve, update, cancel** appointments | **PARTIAL** | Buttons exist, do nothing | Approval changes the status the customer sees |
| Scope (Customer) | View appointment status & booking history | **PARTIAL** | history.html: one static card | Own records only |
| Fig 6.8 | Customer **downloads** booking-history records | **MISSING** | — | Download (PDF/print) contains only own data |
| Scope (Customer, Staff), Sprint 7 | Customer **fills out** waiver; staff **assists** | **MISSING** | — | See conflict P5 |
| Scope (Inventory) | **Usage logs** | **MISSING** | — | Append-only log for every stock change |
| Scope (Staff), Req. Analysis | **Supplier management** | **MISSING** | — | CRUD; deactivate a supplier that has history |
| Scope (POS), Fig 6.5 | **Receipt generation** | **MISSING** | — | Printable receipt with receipt no. |
| Fig 6.5 | **Collect remaining balance** for already-booked customers | **MISSING** | — | Partial payment → balance shown → settle later |
| Scope (POS), Interview 3 | POS sells **services and products** (medicines, vaccines, dog food, other products) | **MISSING** | — | Product sale deducts stock |
| Scope (Super Admin) | **Assign roles & permissions** | **MISSING** | Role filter only | Removing a permission blocks the route (403) |
| Scope (SA), NFR023–024 | **Monitor system activity / activity logs** | **PARTIAL** | 4 static rows | Logins, appointments, transactions and inventory updates are logged automatically |
| Scope (SA) | **Database backup & recovery** | **INCORRECT** | Fake (localStorage) | Backup file created. Restore brings back deleted data |
| Scope (SA), Sprint 10 | **System settings / maintenance** | **PARTIAL / INCORRECT** | localStorage only. Maintenance does nothing | Maintenance mode blocks non-SA users |
| Scope (Admin) | Vet/Admin **checks walk-in & patient flow** | **MISSING** | — | Read-only flow board for Vet/Admin |
| Interview 2 | Assign veterinarian (3–5 vets per day) | **MISSING** | Static "Dr. Maria Santos" | Visit/record shows the assigned vet |

**Totals: 44 traced requirements (28 REQ + 16 scope/figure items) → COMPLETE 0 · PARTIAL 17 · MISSING 22 · INCORRECT 5.** Mixed rows are counted by their worse status.

### E3. Your original checklist mapped to the paper
Every item in your list maps to a row above, for example:

| Your item | Paper row |
|---|---|
| Slot management | Sprint 4 |
| Usage logs | Scope (Inventory) |
| Suppliers | Scope (Staff) |
| Sales summaries | REQ021 |
| Waiver review | REQ024 |
| Super Admin items | Scope (SA) |
| Patient monitoring summaries | REQ026 |

Nothing in your list goes beyond the paper.

---

## F. Non-functional requirements (paper NFR001–NFR024)

| NFR | Requirement | Status | Evidence / action |
|---|---|---|---|
| 001 | Simple, user-friendly UI | **PARTIAL** | Clean, consistent card design; but dead buttons and `alert()` boxes. → flash messages, inline validation |
| 002 | Clear navigation | **INCORRECT** | Admin nav differs between pages (adminpetrecords, adminprofile, newappointment and editappointment lack Payments/Inventory/Reports). Admin dashboard "Logout" → home.html. Admin profile is not linked. history.html uses different header classes → one nav partial per role |
| 003 | Common browsers, desktop & mobile | **PARTIAL** | See 016/017 |
| 004 | Reasonable response time | **NEEDS TESTING** | 4 MB background PNG → compress. DB indexes and pagination |
| 005 | Multiple simultaneous users | **MISSING** | localStorage is per browser → MySQL + sessions |
| 006 | Real-time updates (schedules, inventory, transactions) | **MISSING** | Server data; flow board / dashboards auto-refresh (polling). Internal only, **no customer notifications** |
| 007 | Accurate, consistent records | **INCORRECT** | Contradicting prices, counts and dates in the mock-ups |
| 008 | Minimize errors in transactions | **MISSING** | DB transactions, server-side totals |
| 009 | Minimize downtime | **NEEDS TESTING** | Deployment-dependent |
| 010 | Secure login for admin/vet/staff | **INCORRECT** | See H |
| 011 | Protect sensitive records | **INCORRECT** | See H |
| 012 | RBAC by assigned permissions | **MISSING** | Middleware + permissions + policies |
| 013 | Scalability | **MISSING** | Normalized schema, indexes, pagination |
| 014 | Modular design | **PARTIAL** | 49 copies of the nav → Blade layouts/partials, service classes |
| 015 | Future enhancements | **PARTIAL** | Same |
| 016 | Chrome, Firefox, Edge | **NEEDS TESTING** | Tested only in Chromium so far. AVIF images are supported in current versions of all three |
| 017 | Desktop & mobile | **PARTIAL** | At 375px: **admindashboard overflows (503px)**. SA nav shows 3 of 13 links (horizontal scroll strip) |
| 018 | Available in clinic hours | **NEEDS TESTING** | Deployment |
| 019 | Depends on stable internet | **N/A** (assumption) | — |
| 020 | Minimize interruption during maintenance | **MISSING** | Maintenance mode is fake → real middleware |
| 021 | Prevent duplicate/inconsistent records | **MISSING** | Unique constraints, FKs, duplicate-customer check |
| 022 | Accurate appointment, inventory, payment and medical records | **MISSING** | Validation + transactions + locks |
| 023 | Log logins, appointments, transactions, inventory updates | **MISSING** | Central logger |
| 024 | Maintain activity logs | **PARTIAL** (static) | Read-only log viewer with filters |

---

## G. Conflicts that need your decision

The paper contradicts itself in places, and the project differs from it. I will not change anything until you choose.

| # | Conflict | Where | My recommendation |
|---|---|---|---|
| **P1** | **Super Admin scope.** Scope/Req. Analysis: SA = system-level only (users, roles, logs, backup, settings). Use-case Figs 5.1/5.3: SA also *views* POS, sales, financial reports, inventory, pet records, waivers, appointments ("full access"). **Existing SA UI follows the diagrams.** You asked to keep SA separate | pp. 5, 30, 44 | Keep the existing SA monitoring pages but make them **read-only oversight** (no creating/editing clinical, POS or inventory data). Hide medical-note contents from SA by default (Interview 2: "only doctors access records"). Operational work stays with Staff and Vet/Admin |
| **P2** | **Who runs the POS.** Scope, Req. Analysis, REQ018 and Interview 3: **Staff/cashier**. Fig 5.2: Vet/Admin. Existing: Admin "Payments" (read-only) + SA "POS" (read-only); Staff has nothing | pp. 6, 8, 29, 64 | **Staff operates the POS.** Vet/Admin sees transaction records **read-only** plus summaries (Fig 6.1 says "read-only") |
| **P3** | **Who edits inventory.** Scope/Fig 6.6: Staff updates; Vet/Admin "monitors". Existing: Admin has Add/Edit | pp. 6, 36 | **Staff** manages items, stock-in, suppliers. **Vet/Admin** views and records **usage** during treatment (REQ017 "authorized users… used"). Admin's inventory page becomes monitoring + usage |
| **P4** | **Medical record access.** Interview 2: "only doctors can access records". REQ008: "veterinarians **and authorized staff** create and update" | pp. 45, 61 | **Vet/Admin**: full clinical records (consultation, prescriptions, treatments, vaccinations, care instructions). **Staff**: create/update pet profiles & owner info, view vaccination history and visit list, **no** full consultation notes. SA can re-grant this via permissions |
| **P5** | **Customer waivers.** Scope: customers "fill out waiver… when needed". Limitations: customer access is "viewing… only". Sprint 7: staff "assist customers in completing" | pp. 6, 9, 26 | Staff prepares a waiver for a specific customer/pet/procedure. The customer signs it **either at the clinic** (staff device) **or in the portal** ("Forms to sign"). Both are stored the same way |
| **P6** | **Who manages accounts.** REQ004 says "administrators". Scope says Super Admin. Existing Admin "Users" page manages pet owners | pp. 5, 44 | Only **SA** creates/edits/activates/deactivates accounts and roles. Admin "Users" becomes a **read-only customer directory** |
| **P7** | **"Diagnosis".** The ERD's Medical_Records has a `diagnosis` field; your instructions say the system excludes medical diagnosis | p. 50 | Keep a free-text **"Assessment / Diagnosis (entered by the veterinarian)"** field: documentation only, no automated suggestions or decisions |
| **P8** | The TAM section mentions "online payments", "live queue status tracking" and "automated queue progression" for pet owners; Limitations and Req. Analysis exclude these | pp. 20, 9, 43 | Follow Limitations: **none of these will be built** |
| **P9** | Design section says the frontend uses **Bootstrap**; the existing project uses its own `animal.css` | p. 48 | **No Bootstrap**, to preserve your design (maybe update that sentence in the paper) |
| **P10** | The **ERD is incomplete/duplicated** (two Customers, two Transactions, a redundant "Users Table"; no suppliers, patient-flow, usage-log or activity-log tables, though p. 43 says the DB stores them) | p. 50 | Implement a clean superset (section K). I can give you an updated ERD for the paper later |
| **P11** | Payment methods: the paper only says "processed at the cashier and recorded manually". Existing UI offers Cash, GCash, Maya, Credit/Debit Card | p. 9 | Keep them as **labels of in-clinic payments recorded by the cashier** (optional reference no.). No gateway or API |
| **P12** | Fig 6.5 "collect remaining balance" implies partial payments | p. 35 | Support a bill with one or more payments and a running balance |
| **P13** | Email verification & reset codes (Figs 6.3/6.4) need an email service | pp. 33–34 | Dev/demo: Laravel log/Mailpit. Real demo: a Gmail SMTP app password you provide (Phase 5) |
| **P14** | Role labels: "Assistant/Veterinary Assistant" vs paper "Staff/Receptionist" (Cashier + Office Assistant); "Administrator" vs "Veterinarian/Admin" | — | Rename labels; keep pages and styling |
| **P15** | Login: 4 separate login pages vs Fig 6.4's single Login Module that redirects by role | p. 34 | **One login page** for everyone; the server decides the role. Keep the landing-page role-picker modal as-is visually, but every choice opens the same secure login. Old login URLs redirect to it |
| **P16** | Booking form makes customers retype pet details instead of selecting a registered pet | appointment.html | Select from "My Pets" (with an add-pet shortcut) |
| **P17** | `Educational_Videos.html` (unrelated school content) inside `image/` | ZIP | Leave it out of the new app (it stays in the preserved original) |
| **P18** | Multi-branch: the client wanted cross-branch access (Interview 2), but Limitations say single branch only | pp. 9, 60 | Single branch, per Limitations |

---

## H. Security issues found

| Severity | Issue | Where |
|---|---|---|
| 🔴 Critical | **No authentication on any page.** Every URL opens directly, including SA backup/settings/users and all customer, medical and sales data | all pages |
| 🔴 Critical | **Admin login accepts any credentials** and sends email+password in the **URL** | adminlogin.html |
| 🔴 Critical | **Plaintext passwords in public JS** (`owner123`, `assistant123`, `superadmin123`) | animal.js |
| 🔴 Critical | Role kept in user-editable localStorage, and never checked | animal.js |
| 🟠 High | To prevent in Laravel: customers reading other customers' pets/appointments/care instructions by changing an ID (IDOR); Staff reaching Vet medical notes; Admin reaching SA routes; SA editing clinical data (P1) | to build |
| 🟠 High | Sensitive data named by the client (contact numbers, addresses, emails, medical records, sales/income) is protected only by hidden links | all |
| 🟠 High | Logout doesn't invalidate anything | *logout pages |
| 🟡 Medium | No CSRF (no server yet) → `@csrf` on every form | all forms |
| 🟡 Medium | XSS pattern (`innerHTML` with user input) | animal.js `createAppointmentRow` (dead; won't be ported) |
| 🟡 Medium | No password policy, throttling, lockout or real reset | login/register/forgotpass |
| 🟡 Medium | Fake backup gives false confidence | superadminbackup |
| ⚪ Laravel-phase rules | `$fillable` (never take `role_id`, `status` or totals from the request); Form Request validation; policies on every record route; server-computed POS totals; backups stored outside `public/`; `APP_DEBUG=false` in production; HTTPS in deployment (client asked for secure communication) | to build |

---

## I. Backend / database requirements
- **Every** REQ needs Laravel + MySQL.
- **DB transactions + row locks** are required for:
  - POS checkout (bill, items, payment, stock deduction, usage log)
  - Inventory usage during treatment
  - Slot booking (no double booking)
  - Flow status changes that also complete the appointment
  - Restore from backup
- **Environment:** PHP and Composer are present in this container; MySQL isn't installed yet (I'll install it for testing in Phase 3/4).

## J. Recommended Laravel architecture
- **Laravel 12 + Blade**, with no Bootstrap and no SPA. `animal.css` → `public/css/animal.css` unchanged. Images → `public/image/` so the existing `url("image/…")` paths keep working. `animal.js` is trimmed later (with your approval) to the role-picker modal and small UI helpers.
- **Layouts:** `public`, `customer`, `staff`, `admin`, `superadmin`, built from your current markup and classes. One nav partial each (fixes NFR002).
- **Auth:**
  - One login (P15)
  - Email verification code at registration
  - Reset by emailed code with a "new ≠ old" rule
  - Throttling, remember-me, session regeneration
  - An `active` status check
- **Authorization, in 3 layers:**
  1. `role` middleware on route groups `/portal`, `/staff`, `/vet`, `/superadmin`
  2. `permission` checks from a `permission_role` table that SA edits
  3. **Policies** for ownership (Pet, Appointment, CareInstruction, Waiver, Transaction)
- **Validation:** Form Requests; `$fillable` on models.
- **Services:** `BookingService` (slots + locks), `PatientFlowService`, `InventoryService` (the only way stock changes, and it always writes a usage log), `PosService`, `BackupService` (mysqldump to `storage/app/backups`).
- **Activity logging:** one `ActivityLogger` for logins, appointments, transactions, inventory and admin actions (NFR023).
- **Settings:** clinic info, hours & slots, alert thresholds, maintenance mode.
- **Alerts:** low-stock and expiry computed from the DB, shown as an in-app badge/panel for Staff & Vet. No customer notifications.
- **Downloads:** printable HTML/PDF for care instructions, booking history, receipts and reports (PDF via `barryvdh/laravel-dompdf`; CSV for report export).
- **Tests:** PHPUnit feature tests per module, plus an unauthorized-access matrix.

## K. Recommended tables and relationships

This aligns with the paper's ERD (Roles, Users, Customers, Pets, Services, Appointments, Medical_Records, Inventory, Transactions, Waivers) and adds the tables the paper's text requires.

| Table | Key columns (PK `id`) | FKs / unique / indexes |
|---|---|---|
| roles | name, slug | slug **unique**. Seeded: super_admin, vet_admin, staff, customer |
| permissions | slug, description | slug **unique** |
| permission_role | role_id, permission_id | composite PK |
| users | role_id, first_name, last_name, email, contact_number, password, status(active/inactive), email_verified_at, last_login_at | email **unique**; FK roles |
| email_verification_codes | user_id/email, code_hash, expires_at, attempts | index email |
| customers | user_id (nullable for walk-ins), first_name, last_name, contact_number, email, address, is_walk_in | user_id **unique** nullable; index (last_name, contact_number) |
| pets | customer_id, pet_name, species, breed, gender, birthdate, color, status(active/deceased/archived) | FK customers |
| services | service_name, purpose(consultation/vaccination/treatment/grooming), description, price, duration_minutes, is_active | name **unique** |
| clinic_hours / blocked_dates | day_of_week, open/close, slot_minutes, max_per_slot / date, reason | day **unique** / date **unique** |
| appointments | reference, customer_id, pet_id, service_id, veterinarian_id?, appointment_date, appointment_time, status(pending/confirmed/completed/cancelled), reason, notes, cancel_reason, created_by | reference **unique**; index (date, time), status |
| patient_visits (**patient flow**) | queue_no, customer_id, pet_id, service_id, appointment_id?, visit_type(walk_in/appointment), status(waiting/ongoing/completed/cancelled), checked_in_at, started_at, completed_at, cancelled_at, cancel_reason, veterinarian_id?, handled_by | appointment_id **unique** nullable; index (status, checked_in_at) |
| medical_records | pet_id, patient_visit_id?, veterinarian_id, record_type(consultation/treatment/vaccination/grooming), consultation_date, weight_kg, temperature_c, chief_complaint, findings, assessment (vet-entered, P7), notes | index (pet_id, consultation_date) |
| prescriptions | medical_record_id, inventory_item_id?, medicine_name, dosage, frequency, duration, instructions | FKs |
| treatments | medical_record_id, procedure_name, description | FK |
| vaccinations | pet_id, medical_record_id?, inventory_batch_id?, vaccine_name, batch_no, date_administered, next_due_date, administered_by | index next_due_date |
| care_instructions | medical_record_id, pet_id, title, body, released_to_owner(bool), released_at | customer sees released only (Interview 2: "subject to approval") |
| suppliers | name, contact_person, contact_number, email, address, is_active | name **unique** |
| inventory_items | sku, item_name, category(medicine/vaccine/supply/product), unit, reorder_level, selling_price, default_supplier_id?, is_active | sku **unique** |
| inventory_batches | inventory_item_id, supplier_id?, batch_no, quantity (≥0), expiration_date?, received_at, unit_cost | index expiration_date |
| inventory_movements (**usage log**) | inventory_item_id, inventory_batch_id?, type(stock_in/usage/sale/adjustment/disposal), quantity(±), reference_type/id, user_id, remarks, created_at | append-only |
| transactions | receipt_no, customer_id?, pet_id?, appointment_id?, patient_visit_id?, type(walk_in/appointment), subtotal, discount, total, amount_paid, balance, status(unpaid/partial/paid/voided), void_reason, voided_by, cashier_id | receipt_no **unique**; index created_at |
| transaction_items | transaction_id, service_id?, inventory_item_id?, description, quantity, unit_price, line_total | FKs |
| payments | transaction_id, amount, payment_method(cash/gcash/maya/card — manual), reference_no?, amount_tendered, change_due, received_by, payment_date | supports "remaining balance" (P12) |
| waiver_templates | title, waiver_type(operation/refusal_of_treatment/health_certificate/major_treatment/other), body, version, is_active | |
| waivers | waiver_template_id, customer_id, pet_id, appointment_id?, patient_visit_id?, waiver_type, content_snapshot, signer_name, signature, signed_date, signed_via(clinic/portal), status(pending/signed/reviewed), prepared_by, reviewed_by, reviewed_at | FKs |
| activity_logs | user_id?, action, module, subject_type/id, description, ip_address, user_agent, created_at | append-only; index (module, created_at) |
| settings | key, value | key **unique** |
| backups | filename, size, type, status, created_by | |
| sessions, password_reset_tokens, cache, jobs | Laravel defaults | |

**Relationships:**
- **Roles & users:** Role 1–N User; Role N–N Permission.
- **Customers & pets:** User 1–0..1 Customer; Customer 1–N Pet.
- **Pet history:** Pet 1–N Appointment, PatientVisit, MedicalRecord and Vaccination.
- **Clinical records:** MedicalRecord 1–N Prescription, Treatment and CareInstruction.
- **Visits:** Appointment 1–0..1 PatientVisit.
- **Inventory:** Item 1–N Batch and Movement; Supplier 1–N Batch.
- **Billing:** Transaction 1–N TransactionItem and Payment.
- **Waivers:** WaiverTemplate 1–N Waiver.

*I'll present the final version again before writing migrations (Phase 4).*

## L. Migration roadmap

| Phase | Work |
|---|---|
| **2** | Preserve the original: git tag `original-frontend` + copy the ZIP contents, **including images**, to `legacy-frontend/`. Map all 49 pages → Blade view / route / controller. Record your P1–P18 decisions |
| **3** | Laravel skeleton; assets to `public/`; layouts + nav partials; pages converted 1:1 (same markup/classes); before/after screenshots to prove the design is unchanged |
| **4** | Show the final schema → migrations, models, relationships, seeders (roles, permissions, services, demo accounts, fictitious records — Interview 2 recommends fictitious data) |
| **5** | Auth (login, email code, reset), role & permission middleware, policies, unauthorized-access tests |
| **6** | Modules in your order 1–16, each: implement → test → check against the paper → update the checklist |
| **7** | Final table: Requirement \| Status \| Evidence \| Test result |

## M. Priority order
1. Auth + roles + permissions + policies (fixes all 🔴 issues; REQ001–002, NFR010–012)
2. Customers, pets, services (REQ005–006, REQ013 service list)
3. Appointments + slots + approval + history (Sprint 4)
4. Walk-in + patient flow (REQ011–013; entirely missing)
5. Medical records + care instructions (REQ007–010)
6. Inventory + batches/expiry + alerts + usage logs + suppliers (REQ014–017)
7. POS + payments/balances + receipts + transactions (REQ018–020)
8. Waivers (REQ022–024)
9. Dashboards + reports + summaries (REQ021, REQ025–028)
10. Activity log viewer, SA users/permissions, backup/recovery, settings
11. NFR polish (nav, mobile overflow, image compression, browser tests)
