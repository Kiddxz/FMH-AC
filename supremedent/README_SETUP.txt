# SupremeDent – merged check build

This package is a **separate merged copy**. The original `supremedent_up1(1).zip` is not modified.

## What was transferred/added

1. **webup11 landing page UI**
   - Public landing page is now `index.php`.
   - Login and Sign Up buttons go to the real PHP authentication.
2. **Sign in**
   - Uses the existing `supremedent_db.users` table.
   - Role redirects remain: patient / assistant / dentist / superadmin.
   - Added **Forgot password?**
3. **Sign up + email OTP**
   - New patient registration sends a 6-digit OTP.
   - Account is created only after OTP verification.
4. **Forgot password + OTP**
   - Sends a 6-digit reset code.
   - Lets the user create a new password.
5. **Calendar**
   - Added database-driven monthly calendar pages:
     - `assistant/calendar.php`
     - `dentist/calendar.php`
     - `superadmin/calendar.php`
   - Shows appointment date, time, patient, service and status.
6. **3D dental chart**
   - Preserved the existing SupremeDent 3D odontogram and its GLB model.
   - Dentist can continue using `dentist/odontogram.php`.
   - Super Admin can open patient 3D charts from `superadmin/patients.php`.
   - Super Admin is allowed to save odontogram records in this merged copy.
7. **Database**
   - Existing tables/data are preserved.
   - OTP now uses the `auth_otps` table; patient 3D concern reporting uses `patient_tooth_reports`.

## XAMPP setup

1. Extract this folder into:
   `C:\xampp\htdocs\`
2. Start **Apache** and **MySQL** in XAMPP.
3. In phpMyAdmin, create/select `supremedent_db`.
4. Import `supremedent_db.sql`.
   - It contains no default accounts. Create the first Super Admin with `superadmin_setup/`
     (see SECURITY_AND_FLOW_UPDATE_2026_10_14.txt, section C).
   - If you are replacing your current database, export/backup it first.
5. Open:
   `http://localhost/supremedent_merged/`

## OTP email on localhost

The OTP code is sent through PHP `mail()`. XAMPP usually needs a mail/sendmail configuration before real emails can be delivered.

For local checking, every generated OTP is also written to:
`storage/otp.log`

This is intentionally included as a development fallback. Do not use that log mechanism on a production server.

Mail settings are in:
`config/mail.php`

Change `MAIL_FROM` to your clinic email if needed.

## Important

The original uploaded ZIP was left untouched. This build is a new copy intended for your checking/demo.
