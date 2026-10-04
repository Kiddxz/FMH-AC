# FMH Animal Clinic Management System — User Acceptance Test (UAT) Script

**System:** FMH Animal Clinic Management System (Laravel + MySQL)
**Prepared for:** FMH Animal Clinic, Las Piñas City
**How to use:** Follow each test case in order. Write **P** (passed) or **F** (failed) in the *Result* column. If a test fails, write what happened in *Remarks*.
**Before testing:** Run `php artisan migrate:fresh --seed` so the fictitious demo records are loaded (this erases all other data).

## Test accounts (fictitious demo data)

| Role | Email | Password |
|---|---|---|
| Super Admin | superadmin@fmhanimalclinic.com | superadmin123 |
| Veterinarian / Admin | admin@fmhanimalclinic.com | admin123 |
| Staff / Receptionist | assistant@fmhanimalclinic.com | assistant123 |
| Customer / Pet Owner | owner@fmhanimalclinic.com | owner123 |

Tester: ______________________  Date: ______________  Browser / Device: ______________________

---

## A. Login, Registration and Accounts

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| A-01 | All | Open the Home page and click **Login**. Choose each account type and log in with its demo account. | Each account opens its own dashboard (Portal, Staff, Admin or Super Admin). | | |
| A-02 | Guest | Log in with a correct email and a wrong password. | "Invalid email or password." message; not logged in. | | |
| A-03 | Guest | Enter a wrong password many times in a row. | After several tries: "Too many login attempts. Please try again in … seconds." | | |
| A-04 | Guest | Click **Create Account**, fill in the form and submit. Open `storage/logs/laravel.log` to get the 6-digit code and enter it. | Account is created and the customer portal opens. | | |
| A-05 | Guest | Click **Forgot Password**, enter the owner's email, get the code from the log, set a new password, then log in with it. | Password is changed; the new password works and the old one does not. | | |
| A-06 | Guest | While logged out, type `/staff`, `/admin` and `/superadmin` in the address bar. | Each address goes to the Login page. | | |
| A-07 | Customer | While logged in as the customer, type `/staff` and `/superadmin` in the address bar. | "Access Denied" (403) page. | | |
| A-08 | Any | Click **Logout**, confirm, then press the browser's **Back** button. | The previous inside page is not shown again; Login page appears. | | |
| A-09 | Any | Open **Profile**, change the contact number and save; change the password using the current password. | Changes are saved; the new password works on the next login. | | |

## B. Customer / Pet Owner Portal

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| B-01 | Customer | Open **My Pets** → **+ Add New Pet**, fill in the form and save. | The new pet appears in My Pets. | | |
| B-02 | Customer | Open a pet → **Edit**, change the breed and save. | The new breed is shown. | | |
| B-03 | Customer | Open **Book Appointment**, choose a pet, service and a Monday–Saturday date. | Available time slots load; a Sunday shows "clinic closed". | | |
| B-04 | Customer | Choose a time slot, write a reason and submit. | A reference number (APP-…) is shown with status **Pending**. | | |
| B-05 | Customer | Open **Appointment History**. | The new booking is listed with its status. | | |
| B-06 | Customer | Open a released care instruction and click **Download**. | A printable/downloadable care instruction opens. | | |
| B-07 | Customer | Open **Waivers**, open the waiting waiver, type your full name, tick the agreement box and sign. | Waiver status becomes **Signed**. | | |
| B-08 | Customer | Try to open another owner's pet by changing the number in the address (e.g. `/portal/pets/3`). | "Access Denied" (403). | | |

## C. Staff / Receptionist

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| C-01 | Staff | Open the **Dashboard**. | Today's appointments, patient status, collected today, recent pet registrations and inventory alerts are shown. | | |
| C-02 | Staff | Open **Appointments**, open the customer's new booking (B-04) and click **Confirm Appointment**. | Status becomes **Confirmed**; the customer sees Confirmed in their history. | | |
| C-03 | Staff | **Edit / Reschedule** the appointment to another time slot. | New date/time is saved. | | |
| C-04 | Staff | Cancel an appointment and type a reason. | Status becomes **Cancelled** with the reason shown. | | |
| C-05 | Staff | Open **Patient Flow** → **Walk-in**. Register a new walk-in customer and pet. | Walk-in appears in the **Waiting** column. | | |
| C-06 | Staff | Check in a confirmed appointment for today, then move it Waiting → Ongoing → Completed. | The card moves to each column; the dashboard numbers change. | | |
| C-07 | Staff | Open **Customers**, search by last name and open a customer. | The customer's details and pets are shown. | | |
| C-08 | Staff | Open **Pets** and search for a pet name. | Matching pets are listed; results are shown one page at a time. | | |
| C-09 | Staff | Open **Waivers** → prepare a waiver for a pet, then sign it at the clinic with the owner's name. | Waiver is created and becomes **Signed**; it can be printed. | | |

## D. Veterinarian / Admin

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| D-01 | Vet | Open a pet → **+ Add Record**. Enter weight, temperature, chief complaint, findings and diagnosis; save. | Record is listed under Medical Records. | | |
| D-02 | Vet | In the record, add a vaccination with a next-due date. | Vaccination appears in the pet's Vaccination History. | | |
| D-03 | Vet | Add care instructions and click **Release to Owner**. | The owner can now see and download the instructions (B-06). | | |
| D-04 | Vet | Open **Waivers**, open a signed waiver and mark it **Reviewed**. | Status becomes **Reviewed**. | | |
| D-05 | Vet | Open **Waivers → Templates**, edit a template's wording and save. | New waivers use the new wording; old signed waivers keep their original text. | | |
| D-06 | Vet | Open **Services**, add a service with a price, then deactivate it. | Active services can be booked; the inactive one is not offered. | | |
| D-07 | Vet | Open **Payments**, open a bill and **Void** it with a reason. | Status becomes **Void**; the receipt shows "VOID"; used stock is returned. | | |

## E. Inventory

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| E-01 | Staff | Open **Inventory**. | Items with quantity, minimum stock, next expiry and status are listed; Inventory Alerts box is shown. | | |
| E-02 | Staff | **+ Add Item** with a minimum stock level. | Item is saved and listed. | | |
| E-03 | Staff | Open an item → **Stock In** a batch with a batch number and expiration date. | Quantity goes up; the batch is listed; the movement appears in the Usage Log. | | |
| E-04 | Staff/Vet | **Record Usage** of an item. | Quantity goes down; the oldest-expiring batch is used first. | | |
| E-05 | Staff | Use an item until it is below its minimum. | It appears under **Low / Out of Stock**; the 🔔 number goes up. | | |
| E-06 | Staff | Open **Suppliers**, add and edit a supplier. | Supplier is saved and can be chosen for items. | | |

## F. POS and Transactions

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| F-01 | Staff | Open **POS**, choose a customer, add a service and a product, pay in **Cash** with an amount higher than the total. | Bill is saved as **Paid**; change is computed; product stock goes down. | | |
| F-02 | Staff | Make a bill and pay only part of it with **GCash** (enter a reference number). | Status is **Partially Paid** with the correct balance. | | |
| F-03 | Staff | Open the partially paid bill and pay the balance. | Status becomes **Paid**. | | |
| F-04 | Staff | Try to sell more of a product than is in stock. | The sale is refused with a stock message. | | |
| F-05 | Staff | Click **Print Receipt**. | A receipt with OR number, clinic name/address/contact, items, payments and balance opens; it can be printed or saved as PDF. | | |
| F-06 | Staff | From Patient Flow or an appointment, click **Bill**. | POS opens with the customer, pet and service already filled in. | | |

## G. Reports

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| G-01 | Staff/Vet | Open **Reports**, choose each report type (appointments, patient flow, inventory, pet records, sales) with a date range. | Summary cards and tables match the records entered during this test. | | |
| G-02 | Staff/Vet | Choose an end date earlier than the start date. | A validation message is shown. | | |
| G-03 | Staff/Vet | Click **Export CSV**, then open the file in Excel. | The file opens with the same rows as the screen. | | |
| G-04 | Staff/Vet | Click **Print**. | A clean printable report with the clinic name opens. | | |

## H. Super Admin

| ID | Role | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|---|
| H-01 | Super Admin | Open **Users**, **+ Add User** as Staff/Receptionist, then log in with the new account. | The new account opens the Staff dashboard. | | |
| H-02 | Super Admin | **Deactivate** that user while they are logged in elsewhere, then let them open any page. | They are logged out with "Your account is inactive". | | |
| H-03 | Super Admin | Open **Roles & Permissions**, remove a permission from Staff (e.g. Reports), then log in as Staff. | The Reports link/page is no longer available to Staff. Restore the permission afterwards. | | |
| H-04 | Super Admin | Open **Activity Logs** and filter by user, module and date; export CSV. | Logins, failed logins, bookings, payments, voids, backups, etc. from this test are listed. | | |
| H-05 | Super Admin | Open **Backup & Recovery** → **Create Backup**, then **Download**. | A `.json` backup file is downloaded. | | |
| H-06 | Super Admin | Change a pet's name, then **Restore** the backup from H-05 (type RESTORE and your password). | The old pet name is back; a "before-restore" backup was made; the activity log is kept. | | |
| H-07 | Super Admin | Open **Settings**, change the clinic name and contact number; save. | The new name appears on receipts, waivers and printed reports. | | |
| H-08 | Super Admin | In **Settings**, untick Saturday under Clinic Hours and save. | Customers can no longer book on Saturday. Tick it again afterwards. | | |
| H-09 | Super Admin | Set **System Status** to Maintenance, then log in as Staff in another browser. | Staff sees the "Under Maintenance" page; Super Admin can still work. Set it back to Active. | | |

## I. Usability, Devices and Browsers

| ID | Steps | Expected Result | Result | Remarks |
|---|---|---|---|---|
| I-01 | Repeat A-01, B-03, C-01, F-01 and H-05 in **Google Chrome**. | All work. | | |
| I-02 | Repeat the same in **Microsoft Edge**. | All work. | | |
| I-03 | Repeat the same in **Mozilla Firefox**. | All work. | | |
| I-04 | On a phone (or browser phone view: F12 → Ctrl+Shift+M), open each dashboard and press **☰ Menu**. | The menu opens; every link can be reached; the page does not scroll sideways. | | |
| I-05 | On a phone, book an appointment as the customer (B-03, B-04). | Booking works on the small screen. | | |
| I-06 | Open the Login page and any list page with many records. | Pages open in about 2 seconds or less. | | |

---

## Summary

| Module | Test cases | Passed | Failed |
|---|---|---|---|
| A. Login, Registration and Accounts | 9 | | |
| B. Customer / Pet Owner Portal | 8 | | |
| C. Staff / Receptionist | 9 | | |
| D. Veterinarian / Admin | 7 | | |
| E. Inventory | 6 | | |
| F. POS and Transactions | 6 | | |
| G. Reports | 4 | | |
| H. Super Admin | 9 | | |
| I. Usability, Devices and Browsers | 6 | | |
| **Total** | **64** | | |

Tested by: ______________________  Signature: ____________  Date: ____________

Noted by (Clinic): ______________________  Signature: ____________  Date: ____________
