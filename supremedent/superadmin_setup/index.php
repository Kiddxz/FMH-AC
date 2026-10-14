<?php
/**
 * SupremeDent Super Admin setup tool.
 *
 * Creates the first Super Admin of a new installation, or a BACKUP Super Admin so the clinic is never
 * locked out if the main admin forgets the password or leaves. It is deliberately hard to reach:
 *   1. it only answers requests from the server computer itself (setup_allowed_ips, plus .htaccess "Require local");
 *   2. it needs the secret setup_key from config/security.local.php (not stored in git);
 *   3. wrong keys are rate limited per network;
 *   4. the new admin's email must be verified with a 6-digit code (setup_email_otp);
 *   5. every creation is written to the Activity Logs and announced to the existing Super Admins by email.
 */
require_once __DIR__ . '/../config/security.php';
start_secure_session();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

$config = sd_local_config();
$ip = client_ip();
if (!in_array($ip, (array)$config['setup_allowed_ips'], true)) {
    http_response_code(404);
    echo '<!doctype html><title>Not Found</title><h1>Not Found</h1>';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/otp_helpers.php';

const SETUP_KEY_MIN_LENGTH = 24;
const SETUP_MAX_BAD_KEYS = 5;
const SETUP_WINDOW = 900;
const SETUP_PENDING_LIFETIME = 600;

$setupKey = (string)$config['setup_key'];
$locked = strlen($setupKey) < SETUP_KEY_MIN_LENGTH;
$useOtp = (bool)$config['setup_email_otp'];
$bucket = 'setup_key:' . $ip;
$message = '';
$messageType = 'error';
$step = 'form';
$created = null;
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];
$pending = $_SESSION['sa_setup'] ?? null;
if (is_array($pending) && time() - (int)($pending['started'] ?? 0) > SETUP_PENDING_LIFETIME) { unset($_SESSION['sa_setup']); $pending = null; }
if (is_array($pending)) $step = 'verify';

function setup_create_superadmin(mysqli $conn, array $p, string $ip): array
{
    $st = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $st->bind_param('s', $p['email']);
    $st->execute();
    $exists = $st->get_result()->num_rows > 0;
    $st->close();
    if ($exists) return [false, 'That email address is already used by another account.'];
    $st = $conn->prepare("INSERT INTO users(first_name,last_name,email,password,role,status,must_change_password,password_changed_at) VALUES(?,?,?,?,'superadmin','active',0,NOW())");
    $st->bind_param('ssss', $p['first_name'], $p['last_name'], $p['email'], $p['hash']);
    $st->execute();
    $newId = (int)$st->insert_id;
    $st->close();
    log_activity($conn, $newId, 'Super Admin created with setup tool', 'Security', $p['email'] . ' · from ' . $ip);
    // Tell every other active Super Admin, so a secretly created admin account is noticed.
    $st = $conn->prepare("SELECT email,first_name FROM users WHERE role='superadmin' AND status='active' AND id<>?");
    $st->bind_param('i', $newId);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $admin) {
        sendSecurityNoticeEmail($admin['email'], $admin['first_name'], 'A new SupremeDent Super Admin was created',
            'A Super Admin account for ' . $p['first_name'] . ' ' . $p['last_name'] . ' (' . $p['email'] . ') was created with the setup tool on ' . date('M d, Y h:i A') . '.');
    }
    $st->close();
    return [true, $p['email']];
}

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'cancel') {
        unset($_SESSION['sa_setup']);
        $pending = null;
        $step = 'form';
    } elseif ($action === 'start') {
        foreach ($old as $k => $_) $old[$k] = trim((string)($_POST[$k] ?? ''));
        $old['email'] = strtolower($old['email']);
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $namePattern = "/^[\\p{L}\\s.'-]{2,100}$/u";
        if (sd_rate_limited($conn, $bucket, SETUP_MAX_BAD_KEYS, SETUP_WINDOW)) {
            $message = 'Too many wrong setup keys. The setup tool is paused for 15 minutes.';
        } elseif (!hash_equals($setupKey, (string)($_POST['setup_key'] ?? ''))) {
            sd_rate_hit($conn, $bucket, SETUP_WINDOW);
            log_activity($conn, null, 'Wrong Super Admin setup key', 'Security', 'From ' . $ip);
            usleep(500000);
            $message = 'The setup key is incorrect.';
        } elseif (!preg_match($namePattern, $old['first_name']) || !preg_match($namePattern, $old['last_name'])) {
            $message = 'Enter a valid first and last name.';
        } elseif (!valid_email_address($old['email'])) {
            $message = 'Enter a valid email address. Use an email only this admin can open.';
        } elseif (!valid_strong_password($password) || strlen($password) < 12) {
            $message = 'Use a Super Admin password of at least 12 characters with uppercase, lowercase, a number and a special character (no common words).';
        } elseif ($password !== $confirm) {
            $message = 'The passwords do not match.';
        } else {
            sd_rate_clear($conn, $bucket);
            $data = $old + ['hash' => password_hash($password, PASSWORD_DEFAULT), 'started' => time()];
            if ($useOtp) {
                $result = otp_issue($conn, $old['email'], 'setup', $old['first_name'] . ' ' . $old['last_name']);
                if ($result[0]) {
                    session_regenerate_id(true);
                    $_SESSION['sa_setup'] = $data;
                    $pending = $data;
                    $step = 'verify';
                    $message = 'A 6-digit code was sent to ' . $old['email'] . '.';
                    $messageType = 'success';
                } else {
                    $message = $result[1] . ' (If Gmail is not set up yet, set setup_email_otp to false in config/security.local.php for this first install.)';
                }
            } else {
                [$ok, $text] = setup_create_superadmin($conn, $data, $ip);
                if ($ok) { $created = $text; $step = 'done'; } else { $message = $text; }
            }
        }
    } elseif ($action === 'verify' && is_array($pending)) {
        $code = preg_replace('/\D/', '', (string)($_POST['otp'] ?? ''));
        if (otp_consume($conn, (string)$pending['email'], 'setup', $code)) {
            [$ok, $text] = setup_create_superadmin($conn, $pending, $ip);
            unset($_SESSION['sa_setup']);
            session_regenerate_id(true);
            if ($ok) { $created = $text; $step = 'done'; } else { $message = $text; $step = 'form'; }
        } else {
            $message = 'That code is incorrect or expired.';
        }
    }
}
$suggestedKey = $locked ? bin2hex(random_bytes(24)) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Super Admin Setup | SupremeDent</title>
<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>.setup-code{display:block;padding:10px 12px;border-radius:10px;background:#f1f5f9;font-family:Consolas,monospace;font-size:13px;word-break:break-all;margin:8px 0}.setup-steps{padding-left:18px;line-height:1.7}</style>
</head>
<body>
<div class="auth-page auth-stacked"><div class="auth-shell">
<header class="auth-welcome"><div class="auth-welcome-content"><div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent logo"></div><h1>Super Admin Setup</h1><p>Create the first Super Admin, or a backup Super Admin account for emergencies.</p><div class="auth-benefits"><span><i class="fa-solid fa-computer"></i> Works only on the server computer</span><span><i class="fa-solid fa-key"></i> Needs the secret setup key</span><span><i class="fa-solid fa-envelope-circle-check"></i> Email verified by code</span><span><i class="fa-solid fa-list-check"></i> Logged and announced to other admins</span></div></div></header>
<main class="auth-form-panel"><a href="../auth/login.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Back to Login</a><div class="auth-form-wrap">
<div class="auth-wordmark">SUPREMEDENT</div>
<?php if ($message): ?><div class="auth-message is-<?= e($messageType) ?>" style="display:block" role="alert"><?= e($message) ?></div><?php endif; ?>

<?php if ($locked): ?>
<p class="auth-kicker">The setup tool is locked</p>
<p class="muted">To use it, the person in charge of the server must set a secret setup key:</p>
<ol class="setup-steps">
<li>Copy <b>config/security.local.php.example</b> to <b>config/security.local.php</b>.</li>
<li>Put a long random key in <b>'setup_key'</b>, for example this one generated just now:<code class="setup-code"><?= e($suggestedKey) ?></code></li>
<li>Save the file, reload this page, and keep the key somewhere safe (not in a shared chat or group).</li>
</ol>
<p class="muted">After creating the backup admin you can clear the key again to lock this tool.</p>

<?php elseif ($step === 'done'): ?>
<p class="auth-kicker">Super Admin created</p>
<div class="auth-message is-success" style="display:block"><i class="fa-solid fa-circle-check"></i> The Super Admin account <b><?= e($created) ?></b> is ready. It was recorded in the Activity Logs.</div>
<p class="muted">For safety, clear <b>setup_key</b> in config/security.local.php now if you do not need to create another admin.</p>
<a class="btn btn-primary" href="../auth/login.php">Go to Login</a>

<?php elseif ($step === 'verify'): ?>
<p class="auth-kicker">Verify <?= e((string)$pending['email']) ?></p>
<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="verify">
<div class="auth-field"><label for="otp">6-digit code from the email</label><input class="input-field" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></div>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-shield"></i> Create Super Admin</button></form>
<form method="post" class="auth-footer"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><button type="submit" class="forgot-link" style="background:none;border:0;cursor:pointer">Start over</button></form>

<?php else: ?>
<p class="auth-kicker">New Super Admin account</p>
<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="start">
<div class="auth-field"><label for="setupKey">Setup key</label><input class="input-field" id="setupKey" type="password" name="setup_key" maxlength="200" autocomplete="off" required></div>
<div class="auth-grid two-up"><div class="auth-field"><label for="fn">First Name</label><input class="input-field" id="fn" name="first_name" maxlength="100" value="<?= e($old['first_name']) ?>" required></div><div class="auth-field"><label for="ln">Last Name</label><input class="input-field" id="ln" name="last_name" maxlength="100" value="<?= e($old['last_name']) ?>" required></div></div>
<div class="auth-field"><label for="em">Email Address</label><input class="input-field" id="em" type="email" name="email" maxlength="150" value="<?= e($old['email']) ?>" autocapitalize="none" spellcheck="false" required></div>
<div class="auth-field"><label for="pw">Password <small>12+ characters</small></label><input class="input-field" id="pw" type="password" name="password" maxlength="128" autocomplete="new-password" required></div>
<div class="auth-field"><label for="pw2">Confirm Password</label><input class="input-field" id="pw2" type="password" name="confirm_password" maxlength="128" autocomplete="new-password" required></div>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-shield"></i> <?= $useOtp ? 'Send Verification Code' : 'Create Super Admin' ?></button></form>
<?php endif; ?>
</div></main></div></div>
</body></html>
