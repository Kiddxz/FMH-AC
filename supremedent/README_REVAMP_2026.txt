SUPREMEDENT 2026 REVAMP - UPDATED BUILD

LANDING PAGE
- Pages 1-3 of the supplied LANDING-PAGE.pdf are represented by a 3-slide hero carousel.
- Slides auto-advance every 5 seconds and support left/right arrows and clickable dots.
- The hero/clinic image carousel is larger on desktop while remaining responsive on tablets/phones.
- Services follow the page-4 card layout and each Learn More button opens service details.
- Contact/footer follows page 5 and includes working Facebook, Instagram, and TikTok links.

AUTHENTICATION / VALIDATION
- Login and Sign Up remain centered.
- NORMAL LOGIN NO LONGER REQUIRES OTP. All roles log in with email + password.
- Login forms ask browsers/password managers not to autofill or save credentials (autocomplete off/new-password, decoy fields, no Remember Me). Browser/password-manager settings can still override a website request.
- Email validation catches malformed addresses and common typos such as gmail.com.com.
- Example accepted: Asdasd123@gmail.com
- New/reset/staff-created passwords require 8-128 characters with uppercase, lowercase, number, and special character.
- Passwords are stored using password_hash(), not plain text.
- Sign Up still requires a 6-digit OTP before a patient account is created.
- Forgot Password still requires OTP before a password reset.
- OTPs expire after 10 minutes, are hashed in the database, are single-use, and have attempt/rate limits.

OTP DURING LOCAL DEVELOPMENT
- OTP is written FIRST to supremedent/storage/otp.log so it is visible in VS Code.
- The same OTP is written to PHP error_log; under XAMPP Apache check C:\xampp\apache\logs\error.log and search for [SupremeDent OTP].
- Gmail SMTP support is included in config/mail.php. Copy config/mail.local.php.example to config/mail.local.php and enter a Gmail address + 16-character App Password.
- PHP mail() remains a fallback if SMTP is not configured.
- Do not share mail.local.php if it contains a real App Password.
- Disable/remove OTP development logging before a real production deployment.

3D PATIENT CONCERN MAP
- Patient menu includes 3D Concern Map using assets/models/model.glb.
- Patient can choose/click a tooth, select a concern, severity, notes, and save.
- Assistant and Dentist can review the saved patient concern; Dentist can edit clinical/workflow fields.

APPOINTMENTS
- Patient requests appointments.
- Assistant can approve/reject and assign dentists.
- Dentist can review assigned appointments and mark approved appointments completed.

DATABASE
- auth_otps stores hashed OTP records.
- patient_tooth_reports stores patient-reported 3D concern data.
- config/schema.php and the included SQL upgrade support the new tables.


2026-09-12 V3 updates:
- Dentist appointment page is now calendar-first with patient names and unavailable-time marking.
- Patient Dental Condition Map uses the same Healthy/Caries/Filled/Missing/Fractured visualization as the clinical chart and saves patient-reported marks for dentist review.
- Patient Concerns was removed from the dentist menu; patient-reported marks now appear inside Patients > Manage Care and Chart Records.
- Search added to patient directory and chart records.
- Dentist/Assistant/Patient dashboards redesigned.
- User avatar/name in the top bar opens Profile Settings for every role.
- Dental chart database migration fixes older dental_charts tables that are missing chart_date or newer columns.
- Action button icons are hidden for a cleaner interface.


V4 UPDATE (2026-09-12)
- Unified Patient Dental Records + Notifications UI with shared portal shell.
- Added global top bar Search, Messages, Notifications, Calendar, and clickable Profile for all roles.
- Added internal user messaging and role-aware notifications.
- Rebuilt Dentist Treatment Plans around Removable and Fixed Restoration forms with searchable history.
- Added interactive tooth selection + 3D model area to restoration forms.
- Added Super Admin Email / OTP Test page and updated Gmail setup guide.
