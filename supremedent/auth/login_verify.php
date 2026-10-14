<?php
/**
 * Step 2 of signing in for roles that use 2-step verification (Super Admin by default):
 * the 6-digit code emailed after the correct password.
 */
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/otp_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

const LOGIN_CODE_LIFETIME = 600;   // the whole sign-in must finish within 10 minutes
const LOGIN_CODE_MAX_TRIES = 5;    // wrong codes before the password must be entered again

$pending = $_SESSION['pending_login'] ?? null;
$message = '';
$type = 'error';

function sd_pending_user(mysqli $conn, array $pending): ?array
{
    $id = (int)($pending['user_id'] ?? 0);
    $st = $conn->prepare("SELECT * FROM users WHERE id=? AND status='active' LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();
    $st->close();
    // A password change after step 1 cancels this sign-in.
    if (!$user || !hash_equals((string)($pending['fp'] ?? ''), sd_auth_fingerprint((string)$user['password']))) return null;
    return $user;
}

if (!is_array($pending) || time() - (int)($pending['started'] ?? 0) > LOGIN_CODE_LIFETIME) {
    unset($_SESSION['pending_login']);
    header('Location: login.php');
    exit;
}
$user = sd_pending_user($conn, $pending);
if (!$user) {
    unset($_SESSION['pending_login']);
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? 'verify');
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'resend') {
        $result = otp_issue($conn, (string)$pending['email'], 'login', trim($user['first_name'] . ' ' . $user['last_name']));
        $message = $result[0] ? 'A new sign-in code was sent to your email.' : $result[1];
        $type = $result[0] ? 'success' : 'error';
    } else {
        $code = preg_replace('/\D/', '', (string)($_POST['otp'] ?? ''));
        if (otp_consume($conn, (string)$pending['email'], 'login', $code)) {
            unset($_SESSION['pending_login']);
            sd_complete_login($conn, $user, 'password + email code');
        }
        $tries = (int)($pending['tries'] ?? 0) + 1;
        $_SESSION['pending_login']['tries'] = $tries;
        log_activity($conn, (int)$user['id'], 'Wrong sign-in code', 'Authentication', 'Attempt ' . $tries);
        if ($tries >= LOGIN_CODE_MAX_TRIES) {
            unset($_SESSION['pending_login']);
            header('Location: login.php?code_failed=1');
            exit;
        }
        $message = 'That code is incorrect or expired. Check the newest email from SupremeDent, or send a new code.';
    }
}

$masked = (static function (string $email): string {
    [$local, $domain] = explode('@', $email, 2) + ['', ''];
    return substr($local, 0, 2) . str_repeat('•', max(1, strlen($local) - 2)) . '@' . $domain;
})((string)$pending['email']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign-in Code | SupremeDent</title>
<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page auth-stacked"><div class="auth-shell auth-shell-login">
<header class="auth-welcome"><div class="auth-welcome-content"><div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent logo"></div><h1>2-Step Verification</h1><p>Your password was correct. Enter the 6-digit code we emailed to finish signing in.</p></div></header>
<main class="auth-form-panel"><a href="login.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Use a different account</a><div class="auth-form-wrap">
<div class="auth-wordmark">SUPREMEDENT</div><p class="auth-kicker">Code sent to <?= e($masked) ?></p>
<?php if ($message): ?><div class="auth-message is-<?= e($type) ?>" style="display:block" role="alert"><?= e($message) ?></div><?php endif; ?>
<form method="post" autocomplete="off" id="verifyForm">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="verify">
<div class="auth-field"><label for="otp">Sign-in code</label><input type="text" id="otp" name="otp" class="input-field" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" autocomplete="one-time-code" required autofocus></div>
<button type="submit" class="btn btn-primary"><i class="fa-solid fa-shield-halved"></i> Verify and Sign In</button>
</form>
<form method="post" class="options-row login-options"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="resend"><span class="muted">The code expires in 10 minutes.</span><button type="submit" class="forgot-link" style="background:none;border:0;cursor:pointer">Send a new code</button></form>
<div class="auth-footer">Did not try to sign in? Someone may know your password. <a href="forgot_password.php">Reset it now</a>.</div>
</div></main></div></div>
<script>document.getElementById('otp').addEventListener('input',e=>{e.target.value=e.target.value.replace(/\D/g,'').slice(0,6)});</script>
</body></html>
