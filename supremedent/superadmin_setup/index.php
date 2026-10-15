<?php
/**
 * SupremeDent Super Admin backup tool.
 *
 * Creates a BACKUP Super Admin account (or the first one on a new database), so the clinic is not locked
 * out when the main Super Admin forgets the password or leaves. It is protected in several ways:
 *   1. it only answers on the server computer itself (allowed_ips, plus "Require local" in .htaccess);
 *   2. it needs the secret setup key from setup_config.php;
 *   3. 5 wrong keys pause the tool for 15 minutes;
 *   4. the new admin's email is confirmed with a 6-digit code (the same Gmail OTP used by sign-up);
 *   5. every new Super Admin is written to Activity Logs and the other Super Admins get an email alert.
 * The tool does not change any other SupremeDent page; the new account signs in on the normal login page.
 */
require_once __DIR__ . '/../config/security.php';
start_secure_session();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$config = ['setup_key' => '', 'allowed_ips' => ['127.0.0.1', '::1'], 'email_verification' => true];
$local = require __DIR__ . '/setup_config.php';
if (is_array($local)) $config = array_replace($config, array_intersect_key($local, $config));

// Anyone else gets a plain "Not Found", as if this folder did not exist.
$ip = client_ip();
if (!in_array($ip, (array)$config['allowed_ips'], true)) {
    http_response_code(404);
    echo '<!doctype html><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1></body></html>';
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/otp_helpers.php';

const SA_SETUP_KEY_MIN = 24;
const SA_SETUP_MAX_FAILS = 5;
const SA_SETUP_WINDOW = 900;        // 15 minutes
const SA_SETUP_PENDING_TTL = 600;   // the emailed code must be entered within 10 minutes
const SA_PASSWORD_MIN = 12;

$conn->query("CREATE TABLE IF NOT EXISTS superadmin_setup_attempts (ip VARCHAR(45) NOT NULL PRIMARY KEY, failures INT UNSIGNED NOT NULL DEFAULT 0, window_start INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

function sa_setup_failures(mysqli $conn, string $ip): int
{
    $st = $conn->prepare('SELECT failures, window_start FROM superadmin_setup_attempts WHERE ip=? LIMIT 1');
    $st->bind_param('s', $ip);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row || (int)$row['window_start'] + SA_SETUP_WINDOW <= time()) return 0;
    return (int)$row['failures'];
}

function sa_setup_fail(mysqli $conn, string $ip): void
{
    $now = time();
    $expired = $now - SA_SETUP_WINDOW;
    $st = $conn->prepare('INSERT INTO superadmin_setup_attempts(ip,failures,window_start) VALUES(?,1,?) ON DUPLICATE KEY UPDATE failures=IF(window_start<=?,1,failures+1), window_start=IF(window_start<=?,VALUES(window_start),window_start)');
    $st->bind_param('siii', $ip, $now, $expired, $expired);
    $st->execute();
    $st->close();
}

function sa_setup_clear(mysqli $conn, string $ip): void
{
    $st = $conn->prepare('DELETE FROM superadmin_setup_attempts WHERE ip=?');
    $st->bind_param('s', $ip);
    $st->execute();
    $st->close();
}

/** Creates the Super Admin from the verified details. Returns [ok, message]. */
function sa_setup_create(mysqli $conn, array $p, string $ip): array
{
    $st = $conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
    $st->bind_param('s', $p['email']);
    $st->execute();
    $exists = $st->get_result()->num_rows > 0;
    $st->close();
    if ($exists) return [false, 'That email address is already used by another account. Use a different email.'];

    $st = $conn->prepare("INSERT INTO users(first_name,last_name,email,password,role,status) VALUES(?,?,?,?,'superadmin','active')");
    $st->bind_param('ssss', $p['first_name'], $p['last_name'], $p['email'], $p['hash']);
    $ok = $st->execute();
    $newId = (int)$st->insert_id;
    $st->close();
    if (!$ok || $newId < 1) return [false, 'The account could not be created. Please try again.'];

    log_activity($conn, $newId, 'Backup Super Admin created', 'Security', $p['email'] . ' · created with superadmin_setup from ' . $ip);

    // Every other active Super Admin is told, so an account created behind their back is noticed.
    $st = $conn->prepare("SELECT email, first_name FROM users WHERE role='superadmin' AND status='active' AND id<>?");
    $st->bind_param('i', $newId);
    $st->execute();
    $admins = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    foreach ($admins as $admin) {
        $body = "Hello {$admin['first_name']},\n\nA new Super Admin account was created for {$p['first_name']} {$p['last_name']} ({$p['email']}) "
            . 'with the Super Admin backup tool on ' . date('M d, Y h:i A') . ".\n\n"
            . "If you did not expect this, sign in, set that account to Inactive under Users, and check the Activity Logs.\n\nSupremeDent Dental Services";
        sendViaGmailSmtp($admin['email'], 'A new SupremeDent Super Admin was created', $body);
    }
    return [true, $p['email']];
}

$setupKey = (string)$config['setup_key'];
$locked = strlen($setupKey) < SA_SETUP_KEY_MIN;
$useEmailCode = (bool)$config['email_verification'];
$message = '';
$messageType = 'error';
$step = 'form';
$createdEmail = '';
$old = ['first_name' => '', 'last_name' => '', 'email' => ''];

$pending = $_SESSION['sa_setup_pending'] ?? null;
if (is_array($pending) && time() - (int)($pending['started'] ?? 0) > SA_SETUP_PENDING_TTL) {
    unset($_SESSION['sa_setup_pending']);
    $pending = null;
    $message = 'The verification took too long. Please start again.';
}
if (is_array($pending)) $step = 'verify';

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'cancel') {
        unset($_SESSION['sa_setup_pending']);
        $pending = null;
        $step = 'form';
    } elseif ($action === 'start' && $step === 'form') {
        foreach ($old as $k => $_) $old[$k] = trim((string)($_POST[$k] ?? ''));
        $old['email'] = strtolower($old['email']);
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');
        $namePattern = "/^[\\p{L}\\s.'-]{2,100}$/u";
        if (sa_setup_failures($conn, $ip) >= SA_SETUP_MAX_FAILS) {
            $message = 'Too many wrong setup keys. The tool is paused for 15 minutes.';
        } elseif (!hash_equals($setupKey, (string)($_POST['setup_key'] ?? ''))) {
            sa_setup_fail($conn, $ip);
            log_activity($conn, null, 'Wrong Super Admin setup key', 'Security', 'superadmin_setup from ' . $ip);
            usleep(500000);
            $message = 'The setup key is incorrect.';
        } elseif (!preg_match($namePattern, $old['first_name']) || !preg_match($namePattern, $old['last_name'])) {
            $message = 'Enter a valid first and last name.';
        } elseif (!valid_email_address($old['email'])) {
            $message = 'Enter a valid email address that only this admin can open.';
        } elseif (strlen($password) < SA_PASSWORD_MIN || !valid_strong_password($password)) {
            $message = 'Use a password of at least ' . SA_PASSWORD_MIN . ' characters with uppercase, lowercase, a number and a special character.';
        } elseif ($password !== $confirm) {
            $message = 'The passwords do not match.';
        } else {
            sa_setup_clear($conn, $ip);
            $details = $old + ['hash' => password_hash($password, PASSWORD_DEFAULT), 'started' => time()];
            if ($useEmailCode) {
                // Same 6-digit email code used when the clinic registers a patient (it also refuses emails already in use).
                $result = otp_issue($conn, $old['email'], 'register', $old['first_name'] . ' ' . $old['last_name']);
                if ($result[0]) {
                    session_regenerate_id(true);
                    $_SESSION['sa_setup_pending'] = $details;
                    $pending = $details;
                    $step = 'verify';
                    $message = 'A 6-digit code was sent to ' . $old['email'] . '. It expires in 10 minutes.';
                    $messageType = 'success';
                } else {
                    // Only email-delivery failures get the Gmail hint (not "already registered" or "please wait").
                    $mailProblem = !in_array((string)($result[2]['code'] ?? ''), ['', 'cooldown'], true);
                    $message = $result[1] . ($mailProblem ? ' (If Gmail is not set up yet, set email_verification to false in superadmin_setup/setup_config.php.)' : '');
                }
            } else {
                [$ok, $text] = sa_setup_create($conn, $details, $ip);
                if ($ok) { $createdEmail = $text; $step = 'done'; } else { $message = $text; }
            }
        }
    } elseif ($action === 'verify' && is_array($pending)) {
        $code = preg_replace('/\D/', '', (string)($_POST['otp'] ?? ''));
        if (otp_consume($conn, (string)$pending['email'], 'register', $code)) {
            [$ok, $text] = sa_setup_create($conn, $pending, $ip);
            unset($_SESSION['sa_setup_pending']);
            $pending = null;
            session_regenerate_id(true);
            if ($ok) { $createdEmail = $text; $step = 'done'; } else { $message = $text; $step = 'form'; }
        } else {
            // A double-click sends the code twice: the first request already created the account. Matching this
            // session's own password hash shows "done" for that case only, never for anyone else's account.
            $st = $conn->prepare("SELECT id FROM users WHERE email=? AND role='superadmin' AND password=? LIMIT 1");
            $st->bind_param('ss', $pending['email'], $pending['hash']);
            $st->execute();
            $alreadyCreated = $st->get_result()->num_rows > 0;
            $st->close();
            if ($alreadyCreated) {
                $createdEmail = (string)$pending['email'];
                unset($_SESSION['sa_setup_pending']);
                $pending = null;
                $step = 'done';
            } else {
                $message = 'That code is incorrect or expired. Check the newest email from SupremeDent, or start again.';
            }
        }
    }
}

// The list of Super Admins is only shown after the key was accepted and an account was created.
$superAdmins = [];
if ($step === 'done') {
    $r = $conn->query("SELECT first_name,last_name,email,status FROM users WHERE role='superadmin' ORDER BY id");
    if ($r) $superAdmins = $r->fetch_all(MYSQLI_ASSOC);
}
$suggestedKey = $locked ? bin2hex(random_bytes(24)) : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">
<title>Super Admin Backup | SupremeDent</title>
<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>.sa-code{display:block;padding:10px 12px;border-radius:10px;background:#f1f5f9;font-family:Consolas,monospace;font-size:13px;word-break:break-all;margin:8px 0}.sa-steps{padding-left:18px;line-height:1.7}.sa-admins{margin:14px 0 0;padding:0;list-style:none;font-size:14px}.sa-admins li{padding:6px 0;border-top:1px solid #e5edf3}</style>
</head>
<body>
<div class="auth-page auth-stacked"><div class="auth-shell">
<header class="auth-welcome"><div class="auth-welcome-content"><div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent logo"></div><h1>Super Admin Backup</h1><p>Create a backup Super Admin account so the clinic is never locked out.</p>
<div class="auth-benefits"><span><i class="fa-solid fa-computer"></i> Works only on the server computer</span><span><i class="fa-solid fa-key"></i> Needs the secret setup key</span><span><i class="fa-solid fa-envelope-circle-check"></i> Email confirmed with a code</span><span><i class="fa-solid fa-list-check"></i> Recorded in Activity Logs</span></div></div></header>
<main class="auth-form-panel"><a href="../auth/login.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Back to Login</a><div class="auth-form-wrap">
<div class="auth-wordmark">SUPREMEDENT</div>
<?php if ($message): ?><div class="auth-message is-<?= e($messageType) ?>" style="display:block" role="alert"><?= e($message) ?></div><?php endif; ?>

<?php if ($locked): ?>
<p class="auth-kicker">The backup tool is locked</p>
<?php if ($setupKey !== ''): ?><div class="auth-message is-error" style="display:block" role="alert">The setup key in setup_config.php is too short (<?= strlen($setupKey) ?> characters). Use at least <?= SA_SETUP_KEY_MIN ?> characters, for example the key below.</div><?php endif; ?>
<p class="muted">To unlock it, the person in charge of the clinic computer must set a secret setup key:</p>
<ol class="sa-steps">
<li>Open <b>superadmin_setup/setup_config.php</b> in a text editor (for example Notepad).</li>
<li>Put a random key of at least <?= SA_SETUP_KEY_MIN ?> characters in <b>'setup_key'</b>. You can use this one, generated just now:<code class="sa-code"><?= e($suggestedKey) ?></code></li>
<li>Save the file and reload this page. Keep the key private.</li>
</ol>
<p class="muted">When you are done, set <b>'setup_key'</b> back to <b>''</b> to lock the tool again.</p>

<?php elseif ($step === 'done'): ?>
<p class="auth-kicker">Backup Super Admin created</p>
<div class="auth-message is-success" style="display:block"><i class="fa-solid fa-circle-check"></i> <b><?= e($createdEmail) ?></b> can now sign in on the normal login page as Super Admin.</div>
<p class="muted">For safety, set <b>'setup_key'</b> back to <b>''</b> in superadmin_setup/setup_config.php if you do not need to create another admin.</p>
<a class="btn btn-primary" href="../auth/login.php"><i class="fa-solid fa-right-to-bracket"></i> Go to Login</a>

<?php elseif ($step === 'verify'): ?>
<p class="auth-kicker">Confirm <?= e((string)$pending['email']) ?></p>
<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="verify">
<div class="auth-field"><label for="otp">6-digit code from the email</label><input class="input-field" id="otp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required autofocus></div>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-shield"></i> Create Super Admin</button></form>
<form method="post" class="auth-footer"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><button type="submit" class="forgot-link" style="background:none;border:0;cursor:pointer">Start over</button></form>

<?php else: ?>
<p class="auth-kicker">New backup Super Admin</p>
<form method="post" autocomplete="off"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="start">
<div class="auth-field"><label for="setupKey">Setup key</label><input class="input-field" id="setupKey" type="password" name="setup_key" maxlength="200" autocomplete="off" required></div>
<div class="auth-grid two-up"><div class="auth-field"><label for="fn">First Name</label><input class="input-field" id="fn" name="first_name" maxlength="100" value="<?= e($old['first_name']) ?>" required></div><div class="auth-field"><label for="ln">Last Name</label><input class="input-field" id="ln" name="last_name" maxlength="100" value="<?= e($old['last_name']) ?>" required></div></div>
<div class="auth-field"><label for="em">Email Address</label><input class="input-field" id="em" type="email" name="email" maxlength="150" value="<?= e($old['email']) ?>" autocapitalize="none" spellcheck="false" required></div>
<div class="auth-field"><label for="pw">Password <small><?= SA_PASSWORD_MIN ?>+ characters</small></label><input class="input-field" id="pw" type="password" name="password" maxlength="128" autocomplete="new-password" required></div>
<div class="auth-field"><label for="pw2">Confirm Password</label><input class="input-field" id="pw2" type="password" name="confirm_password" maxlength="128" autocomplete="new-password" required></div>
<button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-shield"></i> <?= $useEmailCode ? 'Send Verification Code' : 'Create Super Admin' ?></button></form>
<?php endif; ?>

<?php if ($superAdmins): ?>
<ul class="sa-admins" aria-label="Super Admin accounts"><li><b>Super Admin accounts (<?= count($superAdmins) ?>)</b></li><?php foreach ($superAdmins as $a): ?><li><?= e($a['first_name'] . ' ' . $a['last_name']) ?> · <?= e($a['email']) ?><?= $a['status'] !== 'active' ? ' · inactive' : '' ?></li><?php endforeach; ?></ul>
<?php endif; ?>
</div></main></div></div>
<script>
document.getElementById('otp')?.addEventListener('input',e=>{e.target.value=e.target.value.replace(/\D/g,'').slice(0,6)});
// Sending the email takes a few seconds; stop a second click from submitting the same step twice.
document.querySelectorAll('form').forEach(f=>f.addEventListener('submit',()=>f.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=true)));
window.addEventListener('pageshow',()=>document.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=false));
</script>
</body></html>
