# FMH Animal Clinic Management System — Final Requirement Audit

**Source of truth:** the capstone paper *A Web-Based Sales, Inventory and Appointment Management System for FMH Animal Clinic* (STI College Las Piñas).
**System:** Laravel 13 + MySQL. **Automated tests:** 168 tests in `tests/Feature` (run with `php artisan test`). **Manual tests:** `docs/UAT_TEST_SCRIPT.md` (64 cases).

### Status key
| Status | Meaning |
|---|---|
| **COMPLETE** | Built with Laravel + MySQL and proven by automated tests. |
| **COMPLETE (UAT)** | Built; proven by the manual UAT script (browser/device/visual checks that automated tests cannot do). |
| **DEPLOYMENT** | Built and supported by the system, but can only be confirmed on the clinic's real server and network. |
| **LIMITATION** | Outside the system, as stated in the paper's Scope and Limitations. |

## Summary

| Group | Total | COMPLETE | COMPLETE (UAT) | DEPLOYMENT | LIMITATION |
|---|---|---|---|---|---|
| Functional (FR-REQ001–028) | 28 | 28 | 0 | 0 | 0 |
| Scope items (SCOPE-01–16) | 16 | 16 | 0 | 0 | 0 |
| Non-functional (NFR-REQ001–024) | 24 | 15 | 4 | 4 | 1 |
| **Total** | **68** | **59** | **4** | **4** | **1** |

Before this project (gap analysis, Phase 1): 0 of 68 requirements were complete.

---

## Part A — Functional Requirements

### User Management Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ001 | Create accounts and log in securely; email verification code at registration; forgot password by emailed code (new password must differ) | `/login`, `/register`, `/register/verify`, `/forgot-password`, `/reset-password`; passwords hashed; login limit | AuthenticationTest: each_role_logs_in…, registration_with_email_code, forgot_password_reset_flow_and_new_must_differ_from_old, wrong_password_is_rejected_and_throttled | COMPLETE |
| FR-REQ002 | Roles: Super Administrator, Veterinarian/Administrator, Staff/Receptionist, Customer, each with designated access | `role:` middleware on `/superadmin`, `/admin`, `/staff`, `/portal` | RoleAccessTest: every_role_reaches_only_its_own_area; SecurityTest: every_inside_page_needs_a_login_and_the_right_role (all routes) | COMPLETE |
| FR-REQ003 | Users edit and update their own profile | Profile page of every role (details + change password) | CustomerPortalTest: profile_update_syncs_customer_record, change_password_rules; StaffModuleTest: staff_profile_edit_and_password; AdminModuleTest: admin_profile_edit; SuperAdminModuleTest: super_admin_profile | COMPLETE |
| FR-REQ004 | Administrators view, update, activate and deactivate user accounts | Super Admin → Users | SuperAdminModuleTest: user_list_filters, edit_user_change_role_and_reset_password, deactivate_blocks_login_and_activate_restores, super_admin_cannot_lock_themselves_out | COMPLETE |

### Customer Portal Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ005 | Portal with account information, booking details and pet records | `/portal` dashboard, My Pets, Appointment History, Profile | CustomerPortalTest: dashboard_shows_real_counts, my_pets_lists_only_own_pets | COMPLETE |
| FR-REQ006 | Customers manage pet profiles and monitor appointments | My Pets (add/edit), Book Appointment, Appointment History | CustomerPortalTest: add_pet_is_saved_to_the_logged_in_owner, owner_can_view_and_edit_own_pet; AppointmentTest: customer_books_own_pet_as_pending | COMPLETE |
| FR-REQ007 | Customers access and download post-treatment care instructions | Pet profile → released care instructions → Download | PetRecordTest: care_instructions_draft_release_and_customer_download | COMPLETE |

### Digital Pet Records Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ008 | Veterinarians/authorized staff create and update digital pet records | Admin → Pet Records → Add/Edit Record | PetRecordTest: vet_writes_a_full_consultation_with_care_instructions, vet_edits_a_record_and_lists_are_replaced | COMPLETE |
| FR-REQ009 | Store pet details, medical history, consultations, prescriptions, treatments and vaccinations | Tables `pets`, `medical_records`, `prescriptions`, `treatments`, `vaccinations` | PetRecordTest: vet_writes_a_full_consultation…, vaccination_record_adds_to_vaccination_history, validation_rules | COMPLETE |
| FR-REQ010 | Search and retrieve pet records easily | Search on Pets and Pet Records pages; paging | PetRecordTest: records_page_lists_pets_and_searches_records; StaffModuleTest: pet_list_search_and_species_filter; PerformanceTest: lists_show_one_page_at_a_time | COMPLETE |

### Walk-in and Patient Flow Monitoring Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ011 | Staff register walk-in customers and pets | Staff → Patient Flow → Walk-in | PatientFlowTest: new_walk_in_goes_to_the_board_with_queue_numbers, duplicate_mobile_number_shows_a_warning_first, returning_customer_checks_in_existing_or_new_pet | COMPLETE |
| FR-REQ012 | Patient flow statuses: waiting, ongoing, completed, cancelled | Patient Flow board (4 columns) | PatientFlowTest: appointment_check_in_and_status_flow, cancel_needs_a_reason | COMPLETE |
| FR-REQ013 | Organize customers by service: consultation, vaccination, treatment, grooming | Patient Flow "purpose" filter; services have a purpose | PatientFlowTest: purpose_filter_date_and_auto_refresh_part | COMPLETE |

### Inventory Management Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ014 | Monitor medicines, vaccines and supplies | Inventory page (Staff, Vet view, Super Admin view) | InventoryTest: list_shows_real_stock_numbers_and_alerts | COMPLETE |
| FR-REQ015 | Track quantities and expiration dates | Batches with batch number and expiration date; usable stock excludes expired | InventoryTest: stock_in_creates_a_batch_and_a_log_row, expired_stock_is_not_used_and_can_be_disposed | COMPLETE |
| FR-REQ016 | Low-stock and expiration alerts (in-app) | Inventory Alerts box on dashboards and Inventory page; 🔔 count in the menu; alert days set by Super Admin | InventoryTest: bell_and_dashboards_show_the_alerts; BackupSettingsTest: expiry_alert_days_change_the_alerts | COMPLETE |
| FR-REQ017 | Update inventory when items are added or used | Stock In, Record Usage (oldest expiry first), count correction, automatic deduction on sale | InventoryTest: usage_takes_from_the_batch_that_expires_first, count_correction_logs_the_difference; PosTest: sale_saves_bill_payment_and_deducts_stock | COMPLETE |

### POS and Transaction Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ018 | Record payment transactions and clinic sales | Staff → POS (cash, GCash, Maya, card — recorded only) | PosTest: sale_saves_bill_payment_and_deducts_stock, bad_input_is_refused | COMPLETE |
| FR-REQ019 | Walk-in and appointment-based transactions | POS "Bill" from Patient Flow and from an appointment; counter sales | PosTest: bill_a_walk_in_visit_only_once, bill_an_appointment | COMPLETE |
| FR-REQ020 | Securely store transaction records | `transactions`, `transaction_items`, `payments`; void with reason (no delete); access by permission | PosTest: vet_voids_a_bill_and_the_stock_comes_back, super_admin_views_only, access_rules | COMPLETE |
| FR-REQ021 | Sales and transaction summaries | Reports → Sales; Transactions list totals | ReportTest: sales_report_excludes_void_bills; PosTest: lists_filters_and_totals | COMPLETE |

### Digital Waiver and Consent Forms Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ022 | Staff prepare and manage waiver/consent forms (operations, refusal of treatment, health certificates, major procedures) | Staff → Waivers; Vet → Waiver Templates | WaiverTest: staff_prepares_a_waiver_with_a_copy_of_the_text, unsigned_waiver_can_be_cancelled, vet_reviews_signed_waivers_and_manages_templates | COMPLETE |
| FR-REQ023 | Securely store completed forms | Signed text is locked (copy of the template at signing); owners see only their own | WaiverTest: owner_signs_at_the_clinic_and_the_text_is_locked, another_owner_cannot_see_or_sign | COMPLETE |
| FR-REQ024 | Review and monitor submitted waivers | Vet review; Super Admin view-only list; print | WaiverTest: vet_reviews_signed_waivers_and_manages_templates, super_admin_views_only | COMPLETE |

### Dashboard and Reports Module
| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| FR-REQ025 | Dashboards for monitoring clinic operations | Four role dashboards with live numbers | StaffModuleTest / AdminModuleTest / SuperAdminModuleTest / CustomerPortalTest: dashboard_shows_real_… | COMPLETE |
| FR-REQ026 | Summaries: appointments, transactions, inventory, patient monitoring, today's appointments, current patient status, low-stock alerts, recent registrations | "Today at the Clinic", Inventory Alerts, today's appointments | ReportTest: dashboards_show_today_at_the_clinic; InventoryTest: bell_and_dashboards_show_the_alerts | COMPLETE |
| FR-REQ027 | Reports: appointments, inventory, transactions, pet records, patient flow, daily count; date/status filters with validation; export/print | Reports page; CSV export; print view | ReportTest: appointment_report_counts_and_status_filter, filters_are_validated, inventory_report, flow_and_daily_count_reports, record_summary_has_no_medical_notes, csv_export_and_print_view | COMPLETE |
| FR-REQ028 | Dashboard views based on the user's role | Login sends each role to its own dashboard; other dashboards are blocked | AuthenticationTest: each_role_logs_in_and_lands_on_its_dashboard; RoleAccessTest: every_role_reaches_only_its_own_area | COMPLETE |

---

## Part B — Scope, Sprints and Diagrams

| ID | Requirement | Where in the system | Proven by | Status |
|---|---|---|---|---|
| SCOPE-01 | Appointment slot selection and slot management | Time slots from Clinic Hours; full slots and double booking refused | AppointmentTest: slots_follow_clinic_hours, full_slot_and_same_pet_double_booking_are_rejected; BackupSettingsTest: clinic_hours_decide_the_time_slots | COMPLETE |
| SCOPE-02 | Staff approve, update and cancel appointments | Confirm, Edit/Reschedule, Cancel (with reason) | AppointmentTest: status_rules_for_the_clinic, clinic_creates_confirmed_appointment_and_reschedules, clinic_cancel_needs_a_reason | COMPLETE |
| SCOPE-03 | Customer views appointment status and booking history | Appointment History | AppointmentTest: customer_books_own_pet_as_pending, customer_cancel_rules | COMPLETE |
| SCOPE-04 | Customer downloads booking-history records | Appointment History → Download | AppointmentTest: history_download_and_ownership | COMPLETE |
| SCOPE-05 | Customer fills out waivers; staff assist | Portal signing; signing at the clinic | WaiverTest: customer_reads_and_signs_in_the_portal, owner_signs_at_the_clinic_and_the_text_is_locked | COMPLETE |
| SCOPE-06 | Inventory usage logs | Inventory → Usage Log (filters) | InventoryTest: usage_log_page_and_filters | COMPLETE |
| SCOPE-07 | Supplier management | Staff → Inventory → Suppliers | InventoryTest: suppliers_are_managed_by_staff | COMPLETE |
| SCOPE-08 | Receipt generation | Printable receipt (OR number, clinic details from Settings) | PosTest: sale_saves_bill_payment_and_deducts_stock; BackupSettingsTest: clinic_information_is_printed_on_receipts | COMPLETE |
| SCOPE-09 | Collect remaining balance | Partially Paid → Pay balance | PosTest: partial_payment_then_collect_the_balance | COMPLETE |
| SCOPE-10 | POS sells services and products | POS lines for services and inventory products | PosTest: pos_page_lists_services_and_products, not_enough_stock_saves_nothing | COMPLETE |
| SCOPE-11 | Super Admin assigns roles and permissions | Super Admin → Roles & Permissions | SuperAdminModuleTest: roles_and_permissions_change_access, super_admin_role_keeps_protected_permissions; RoleAccessTest: removing_a_permission_blocks_the_page | COMPLETE |
| SCOPE-12 | Super Admin monitors system activity | Activity Logs (filters, CSV); Recent System Activity timeline | ActivityLogTest (8 tests); SuperAdminModuleTest: dashboard_shows_real_numbers_and_activity | COMPLETE |
| SCOPE-13 | Database backup and recovery | Super Admin → Backup & Recovery (create, download, upload, restore, delete) | BackupSettingsTest: create_and_download_a_backup, restore_brings_the_data_back_and_keeps_the_log, upload_checks_the_file, delete_a_backup | COMPLETE |
| SCOPE-14 | System settings and maintenance | Super Admin → Settings (clinic info, hours, expiry days, maintenance mode) | BackupSettingsTest: maintenance_mode, login_page_works_during_maintenance, clinic_hours_decide_the_time_slots | COMPLETE |
| SCOPE-15 | Vet/Admin checks walk-in and patient flow | Admin → Patient Flow | PatientFlowTest: vet_views_board_and_writes_a_record_for_the_visit | COMPLETE |
| SCOPE-16 | Assign a veterinarian to visits and records | Veterinarian on appointments, patient visits and medical records | PatientFlowTest: vet_views_board_and_writes_a_record_for_the_visit; PetRecordTest: vet_writes_a_full_consultation… | COMPLETE |

---

## Part C — Non-Functional Requirements

| ID | Requirement | How the system meets it | Proven by | Status |
|---|---|---|---|---|
| NFR-REQ001 | Simple, user-friendly interface | Original design kept; one menu style; clear cards, colored statuses and alerts | UAT sections A–H; LayoutTest | COMPLETE (UAT) |
| NFR-REQ002 | Clear navigation menus | One menu per role; active page highlighted; every page reachable; phone menu | LayoutTest: super_admin_menu_has_every_page, panels_show_the_logged_in_user_and_the_menu_button | COMPLETE |
| NFR-REQ003 | Usable in common browsers on desktop and mobile | Responsive CSS (`fmh-ui.css`); no sideways scrolling | UAT I-01 to I-05 | COMPLETE (UAT) |
| NFR-REQ004 | Reasonable response time | Paging, database indexes, eager loading; `bg.jpg` reduced from 4.1 MB to about 0.2 MB | PerformanceTest: busy_pages_stay_fast_with_more_than_1000_records (13 pages under 2 s with 1,200+ records) | COMPLETE |
| NFR-REQ005 | Many users at the same time | Each user has their own session; MySQL transactions keep stock and payments correct when two users work at once | PosTest: not_enough_stock_saves_nothing (transaction rollback); real multi-user load must be observed at the clinic | DEPLOYMENT |
| NFR-REQ006 | Real-time updates of schedules, inventory, transactions | Every page reads current data; Patient Flow board refreshes itself; stock changes immediately on sale/usage | PatientFlowTest: purpose_filter_date_and_auto_refresh_part; PosTest: sale_saves_bill_payment_and_deducts_stock | COMPLETE |
| NFR-REQ007 | Accurate, consistent records | Validation on every form; foreign keys; money stored exactly | All module tests (validation tests in each) | COMPLETE |
| NFR-REQ008 | Minimize errors in transactions | Server computes totals and change; stock checked; all-or-nothing saving; void instead of delete | PosTest: bad_input_is_refused, not_enough_stock_saves_nothing, vet_voids_a_bill_and_the_stock_comes_back | COMPLETE |
| NFR-REQ009 | Minimize downtime | Backup/restore, maintenance mode, friendly error pages | BackupSettingsTest; SecurityTest: the_error_page_hides_technical_details; uptime itself depends on the clinic server | DEPLOYMENT |
| NFR-REQ010 | Secure login for admin, vet and staff | Hashed passwords, login limit, session renewal, deactivated accounts logged out | AuthenticationTest; RoleAccessTest: deactivated_user_is_logged_out_on_next_click | COMPLETE |
| NFR-REQ011 | Protect sensitive records | Login + role + ownership on every page; reports/oversight hide medical notes; security headers; no-store pages; backups not reachable by web address | SecurityTest (6 tests); CustomerPortalTest: owner_cannot_reach_another_owners_pet; ReportTest: record_summary_has_no_medical_notes | COMPLETE |
| NFR-REQ012 | Role-based access by permissions | Permissions per role, editable by Super Admin; checked on every route and in policies | RoleAccessTest: permission_abilities_follow_the_role, removing_a_permission_blocks_the_page; SecurityTest: every_inside_page_needs_a_login_and_the_right_role | COMPLETE |
| NFR-REQ013 | Scalability | Paging on every long list; indexes on searched columns; tested with 1,200+ records | PerformanceTest (2 tests) | COMPLETE |
| NFR-REQ014 | Structured, modular design | Laravel MVC: controllers per module, models, policies, services (POS, inventory, reports, backup) | Code structure; 18 test files, one per module | COMPLETE |
| NFR-REQ015 | Future improvements without rebuilding | Settings page, editable waiver templates, permissions table, services table; migrations for database changes | SuperAdminModuleTest: roles_and_permissions_change_access; WaiverTest: …manages_templates | COMPLETE |
| NFR-REQ016 | Works in Chrome, Firefox and Edge | Standard HTML/CSS/JS only (no browser-specific features) | UAT I-01, I-02, I-03 | COMPLETE (UAT) |
| NFR-REQ017 | Works on desktop and mobile | Phone menu, 2-column cards on phones, tables scroll inside their card | UAT I-04, I-05; LayoutTest | COMPLETE (UAT) |
| NFR-REQ018 | Available during clinic hours | Clinic hours in Settings; maintenance mode for planned work | Depends on the clinic's server staying on | DEPLOYMENT |
| NFR-REQ019 | Depends on a stable internet connection | Stated in the paper as a limitation; the system also runs on the clinic's local network | — | LIMITATION |
| NFR-REQ020 | Minimize interruption during maintenance | Maintenance mode: Super Admin keeps working, others see a notice; backups before restore | BackupSettingsTest: maintenance_mode, restore_brings_the_data_back_and_keeps_the_log | COMPLETE |
| NFR-REQ021 | Prevent duplicate and inconsistent records | Unique emails/references; duplicate mobile warning for walk-ins; no double booking; database constraints | PatientFlowTest: duplicate_mobile_number_shows_a_warning_first; AppointmentTest: full_slot_and_same_pet_double_booking_are_rejected; SuperAdminModuleTest: validation_duplicate_email_and_weak_password | COMPLETE |
| NFR-REQ022 | Accurate appointment, inventory, payment and medical records | Status rules, stock by batch, payment balances, locked waiver text | AppointmentTest: status_rules_for_the_clinic; InventoryTest; PosTest; PetRecordTest | COMPLETE |
| NFR-REQ023 | Log logins, appointments, transactions and inventory updates | Activity log written automatically, including failed and blocked logins | ActivityLogTest: logins_and_failed_logins_are_logged, clinic_work_is_logged_with_the_user_and_ip | COMPLETE |
| NFR-REQ024 | Keep activity logs for monitoring and troubleshooting | Log entries cannot be changed or deleted; filters and CSV export; kept during restore | ActivityLogTest: log_entries_cannot_be_changed_or_deleted, viewer_filters; BackupSettingsTest: restore_brings_the_data_back_and_keeps_the_log | DEPLOYMENT* |

\* NFR-REQ024 works and is tested; it is marked DEPLOYMENT only because *how long* logs are kept is a clinic decision (no automatic deletion is built in).

---

## Part D — Limitations kept from the paper (not built on purpose)

| Limitation in the paper | What the system does |
|---|---|
| No online payment | GCash, Maya and card are **recorded** with a reference number; no money passes through the system. |
| No notifications to customers (SMS/email reminders) | Customers see statuses in the portal. Email is used only for account codes (registration, forgot password). |
| No automated diagnosis | Diagnoses are typed by the veterinarian only. |
| Depends on internet | Can run on the clinic's local network; online access needs the clinic's internet. |

## Part E — Before the clinic uses the system

1. Run `php artisan fmh:security-check` and fix every **FIX** line (APP_DEBUG=false, APP_ENV=production, change or deactivate demo accounts).
2. Run `php artisan fmh:compress-images` once.
3. Create a backup and save a copy outside the computer (USB).
4. Complete the UAT script with clinic staff and keep the signed copy.
5. Never upload the `.env` file or backup files anywhere public.
