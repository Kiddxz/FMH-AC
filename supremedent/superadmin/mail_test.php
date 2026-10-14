<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
require_once __DIR__ . '/../config/mail.php';
$cfg = supremeDentMailConfig();
$msg = ''; $type = 'error'; $diagnostic = '';
$email = trim((string)($_POST['email'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) $msg = 'Session expired. Refresh and try again.';
    elseif (!valid_email_address($email)) $msg = 'Enter a valid destination email address.';
    elseif (time() - (int)($_SESSION['last_mail_test'] ?? 0) < 60) $msg = 'Please wait 60 seconds between mail tests.';
    else {
        $_SESSION['last_mail_test'] = time();
        $ok = sendOtpEmail($email, 'SupremeDent Mail Test', (string)random_int(100000, 999999), 'test');
        if ($ok) {
            $type = 'success';
            $msg = 'Gmail accepted the test email for ' . $email . '. Check that destination inbox and spam folder.';
        } else {
            $error = supremeDentMailLastError();
            $msg = $error['message'] ?: 'The mail test failed.';
            $diagnostic = $error['code'] . ': ' . $error['detail'];
        }
    }
}
require __DIR__ . '/../includes/ui.php';
ui_header('Email / OTP Test', 'superadmin');
ui_sidebar('superadmin', 'mail_test.php');
?>
<div class="page-head"><div><div class="eyebrow">EMAIL / OTP</div><h1>Gmail OTP Test</h1><div class="muted">Test the same mail connection used by patient signup and password recovery.</div></div></div>
<?php if ($msg) flash($type, $msg); ?>
<section class="card">
<div class="otp-test-status">
<div><b>Gmail sender</b><span><?= e($cfg['gmail_user']) ?></span></div>
<div><b>App Password</b><span><?= preg_match('/^[a-zA-Z0-9]{16}$/D', $cfg['gmail_app_password']) ? 'Loaded (16 characters)' : 'Missing or invalid' ?></span></div>
<div><b>OpenSSL</b><span><?= extension_loaded('openssl') ? 'Enabled' : 'Disabled — enable in php.ini' ?></span></div>
<div><b>SMTP ports</b><span><?= e((string)$cfg['smtp_port']) ?> (465 SSL / 587 STARTTLS)</span></div>
</div>
<?php if ($diagnostic): ?><div class="form-section-title">Connection diagnostic</div><pre style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e($diagnostic) ?></pre><?php endif; ?>
<div class="form-section-title">Send a Test OTP</div>
<form method="post" class="form-grid" id="mailTestForm">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="field full"><label>Destination email</label><input type="email" name="email" value="<?= e($email) ?>" required placeholder="yourname@gmail.com"></div>
<div class="actions full"><button type="submit" class="btn btn-primary">Send Test OTP</button></div>
</form>
<p class="muted">The test email is sent to the destination above. Signup uses the patient's entered email; Forgot Password uses the registered account email.</p>
<div class="form-section-title">If the connection fails</div>
<p class="muted">Check config/mail.local.php for the Gmail App Password. In XAMPP php.ini, enable OpenSSL. The mailer includes a trusted CA certificate bundle at config/cacert.pem. Restart Apache after changing php.ini. Detailed errors appear in C:\xampp\apache\logs\error.log. Keep TLS certificate verification enabled.</p>
</section>
<script>document.getElementById('mailTestForm').addEventListener('submit',function(){const b=this.querySelector('button');b.disabled=true;b.textContent='Sending...';});</script>
<?php ui_footer(); ?>
