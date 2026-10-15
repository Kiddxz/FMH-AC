SUPREMEDENT — SUPER ADMIN BACKUP TOOL (superadmin_setup/)

What it does
  Creates a backup Super Admin account (or the first one on a new database), so the clinic is not locked
  out if the main Super Admin forgets the password or leaves. The new account signs in on the normal
  login page (auth/login.php) and opens the Super Admin portal.

How to use (on the XAMPP computer itself)
  1. Open superadmin_setup/setup_config.php with Notepad.
  2. Put a long secret key in 'setup_key' (24+ characters). Opening
     http://localhost/supremedent/superadmin_setup/ while it is locked shows a random key you can copy.
  3. Save, then open http://localhost/supremedent/superadmin_setup/
  4. Enter the setup key, the admin's first name, last name, email and a 12+ character password
     (uppercase, lowercase, number and special character).
  5. Enter the 6-digit code sent to that email. The Super Admin account is created.
     If Gmail OTP is not configured yet, set 'email_verification' => false in setup_config.php.
  6. Set 'setup_key' back to '' to lock the tool again.

Protection
  - Only works on the server computer (localhost); other computers get "Not Found".
  - Needs the secret setup key; 5 wrong keys pause the tool for 15 minutes.
  - The new admin's email is confirmed with a 6-digit code.
  - Every new Super Admin is written to Activity Logs, and the other Super Admins get an email alert.
  - Wrong setup keys are also written to Activity Logs.

Files
  index.php         the tool
  setup_config.php  setup key and options (keep private)
  .htaccess         blocks other computers and hides setup_config.php / README.txt
