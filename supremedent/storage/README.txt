LOCAL DEVELOPMENT OTP LOGGING

Every generated Sign Up / Forgot Password OTP is written to otp.log FIRST so you can open the file directly in VS Code while testing on XAMPP.
The same OTP is also sent to PHP error_log; in XAMPP Apache check C:\xampp\apache\logs\error.log and search for [SupremeDent OTP].
If Gmail SMTP is configured, the OTP is also emailed to the requested address.

Apache browser access to this storage folder is denied by .htaccess.
Disable/remove OTP logging before deploying the system to production.
