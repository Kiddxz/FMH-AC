<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_helpers.php';
require_once __DIR__ . '/otp_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$message = '';
$wrongPassword = false;
$lockSeconds = 0;
$emailValue = strtolower(trim($_POST['login_email'] ?? ''));

/*
 * Sign-in lockout: 5 wrong attempts lock the email for 5 minutes. After a lockout ends,
 * the next wrong password locks it again for 10 minutes. A successful sign-in clears it.
 */
const LOGIN_MAX_ATTEMPTS = 5;
function login_attempt_state(mysqli $conn, string $email): array
{
    $st = $conn->prepare('SELECT failed_count,lock_level,UNIX_TIMESTAMP(locked_until) locked_ts,UNIX_TIMESTAMP(last_failed_at) last_ts FROM login_attempts WHERE email=? LIMIT 1');
    $st->bind_param('s', $email);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    $state = ['failed' => (int)($row['failed_count'] ?? 0), 'level' => (int)($row['lock_level'] ?? 0), 'locked_ts' => (int)($row['locked_ts'] ?? 0)];
    // Forget old history after a quiet day.
    if ($row && (int)$row['last_ts'] > 0 && (int)$row['last_ts'] < time() - 86400 && $state['locked_ts'] < time()) $state = ['failed' => 0, 'level' => 0, 'locked_ts' => 0];
    return $state;
}
function login_attempt_failed(mysqli $conn, string $email, array $state): array
{
    $failed = $state['failed'] + 1;
    $level = $state['level'];
    $lockedUntil = null;
    if ($level >= 1 || $failed >= LOGIN_MAX_ATTEMPTS) {
        $level = min(9, $level + 1);
        $minutes = $level >= 2 ? 10 : 5;
        $lockedUntil = date('Y-m-d H:i:s', time() + $minutes * 60);
        $failed = 0;
    }
    $ip = client_ip();
    $st = $conn->prepare('INSERT INTO login_attempts(email,failed_count,lock_level,locked_until,last_failed_at,last_ip) VALUES(?,?,?,?,NOW(),?) ON DUPLICATE KEY UPDATE failed_count=VALUES(failed_count),lock_level=VALUES(lock_level),locked_until=VALUES(locked_until),last_failed_at=NOW(),last_ip=VALUES(last_ip)');
    $st->bind_param('siiss', $email, $failed, $level, $lockedUntil, $ip);
    $st->execute();
    $st->close();
    return ['failed' => $failed, 'level' => $level, 'locked_ts' => $lockedUntil ? strtotime($lockedUntil) : 0];
}
/** True when the newest emailed sign-in code for this email can still be used. */
function login_code_active(mysqli $conn, string $email): bool
{
    $st = $conn->prepare("SELECT used_at IS NULL AND expires_at>NOW() AND attempts<5 ok FROM auth_otps WHERE email=? AND purpose='login' ORDER BY id DESC LIMIT 1");
    $st->bind_param('s', $email);
    $st->execute();
    $ok = (int)($st->get_result()->fetch_assoc()['ok'] ?? 0) === 1;
    $st->close();
    return $ok;
}
function login_lock_message(int $seconds): string
{
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    return 'Too many failed sign-in attempts. Please wait ' . $m . ':' . str_pad((string)$s, 2, '0', STR_PAD_LEFT) . ' before trying again.';
}

// Many failed sign-ins from one network (password spraying across many emails) pause that network for 15 minutes.
const LOGIN_IP_MAX_FAILURES = 30;
const LOGIN_IP_WINDOW = 900;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $emailValue;
    $password = (string)($_POST['login_password'] ?? '');
    $ipBucket = 'login_ip:' . client_ip();

    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } elseif (!valid_email_address($email)) {
        $message = 'Enter a valid email address.';
    } elseif ($password === '') {
        $message = 'Enter your password.';
    } elseif (sd_rate_limited($conn, $ipBucket, LOGIN_IP_MAX_FAILURES, LOGIN_IP_WINDOW)) {
        $message = 'Too many failed sign-in attempts from this network. Please wait 15 minutes and try again.';
    } elseif (($attempt = login_attempt_state($conn, $email)) && $attempt['locked_ts'] > time()) {
        $lockSeconds = $attempt['locked_ts'] - time();
        $message = login_lock_message($lockSeconds);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $badEmail = !$user || $user['status'] !== 'active';
        // Always run one bcrypt check so a missing account takes as long as a wrong password.
        $passwordOk = password_verify($password, $badEmail ? '$2y$10$tkXtNN55CABXWptIoaGP/..5cRZAe5euEx0MkrKdflb/Cz2urtJiW' : (string)$user['password']);
        if ($badEmail || !$passwordOk) {
            usleep(250000);
            sd_rate_hit($conn, $ipBucket, LOGIN_IP_WINDOW);
            $attempt = login_attempt_failed($conn, $email, $attempt);
            if ($attempt['locked_ts'] > time()) {
                $lockSeconds = $attempt['locked_ts'] - time();
                $message = login_lock_message($lockSeconds);
                if ($user) log_activity($conn, (int)$user['id'], 'Sign-in locked', 'Authentication', 'Locked for ' . ceil($lockSeconds / 60) . ' minutes after failed attempts');
            } else {
                $left = LOGIN_MAX_ATTEMPTS - $attempt['failed'];
                // One message for both cases, so the form cannot be used to find out which emails have accounts.
                $message = 'The email or password you entered is incorrect. ' . $left . ' attempt' . ($left === 1 ? '' : 's') . ' remaining.';
                $wrongPassword = true;
            }
        } elseif (sd_login_destination((string)$user['role']) === null) {
            $message = 'This account cannot sign in. Please contact the clinic.';
        } else {
            if (password_needs_rehash((string)$user['password'], PASSWORD_DEFAULT)) {
                $user['password'] = password_hash($password, PASSWORD_DEFAULT);
                $st = $conn->prepare('UPDATE users SET password=? WHERE id=?');
                $uid = (int)$user['id'];
                $st->bind_param('si', $user['password'], $uid);
                $st->execute();
                $st->close();
            }
            if (sd_login_otp_required($conn, (string)$user['role'])) {
                // 2-step verification: the password was right, now prove access to the account's email.
                $otp = otp_issue($conn, $email, 'login', trim($user['first_name'] . ' ' . $user['last_name']));
                // During the 1-minute cooldown, continue only if the code sent just before is still usable.
                $usable = $otp[0] || (($otp[2]['code'] ?? '') === 'cooldown' && login_code_active($conn, $email));
                if (!$usable) {
                    $message = (($otp[2]['code'] ?? '') === 'cooldown' ? 'Please wait a minute before requesting another sign-in code.' : $otp[1]) . ' The sign-in code could not be sent.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['pending_login'] = ['user_id' => (int)$user['id'], 'email' => $email, 'started' => time(), 'fp' => sd_auth_fingerprint((string)$user['password'])];
                    log_activity($conn, (int)$user['id'], 'Password accepted, sign-in code sent', 'Authentication', 'Role: ' . $user['role']);
                    header('Location: login_verify.php');
                    exit;
                }
            } else {
                sd_complete_login($conn, $user, 'password');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><meta name="description" content="Sign in securely to SupremeDent.">
<title>Log In | SupremeDent</title>
<link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="auth-page auth-stacked"><div class="auth-shell auth-shell-login">
<header class="auth-welcome"><div class="auth-welcome-content"><div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent logo"></div><h1>Welcome to SupremeDent</h1><p>Manage appointments, dental records, treatment plans, and dental care in one secure place.</p></div></header>
<main class="auth-form-panel"><a href="../index.php" class="auth-back"><i class="fa-solid fa-arrow-left"></i> Back to Home</a><div class="auth-form-wrap">
<div class="auth-wordmark">SUPREMEDENT</div><p class="auth-kicker">Sign in to your account</p>
<?php if($message):?><div class="auth-message is-error" style="display:block" role="alert" id="loginMessage"<?php if($lockSeconds>0):?> data-lock-seconds="<?=(int)$lockSeconds?>"<?php endif;?>><span id="loginMessageText"><?=e($message)?></span><?php if($wrongPassword||$lockSeconds>0):?> <a href="forgot_password.php">Forgotten password?</a><?php endif;?></div><?php endif;?>
<?php if(isset($_GET['code_failed'])):?><div class="auth-message is-error" style="display:block" role="alert">Too many wrong sign-in codes. Enter your password again to get a new code.</div><?php endif;?>
<?php if(isset($_GET['reset'])):?><div class="auth-message is-success" style="display:block" role="status">Password reset successfully. You can now log in.</div><?php endif;?>
<?php if(isset($_GET['verified'])||isset($_GET['registered'])):?><div class="auth-message is-success" style="display:block" role="status"><i class="fa-solid fa-circle-check"></i> Account verified. You can now log in.</div><?php endif;?>
<form method="post" autocomplete="off" id="loginForm">
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<div class="credential-decoy" aria-hidden="true"><input type="text" tabindex="-1" autocomplete="username"><input type="password" tabindex="-1" autocomplete="new-password"></div>
<div class="auth-field"><label for="login_email">Email Address</label><input type="email" id="login_email" name="login_email" class="input-field" maxlength="150" placeholder="Enter your email" value="<?=e($emailValue)?>" autocomplete="off" autocapitalize="none" spellcheck="false" data-lpignore="true" data-1p-ignore="true" readonly required><div class="field-feedback" id="emailFeedback" aria-live="polite"></div></div>
<div class="auth-field"><label for="login_password">Password</label><div class="password-control"><input type="password" id="login_password" name="login_password" class="input-field" maxlength="128" placeholder="Enter your password" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" readonly required><button type="button" class="password-toggle" data-toggle-password="login_password" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye-slash"></i></button></div></div>
<div class="options-row login-options"><span></span><a class="forgot-link" href="forgot_password.php">Forgot password?</a></div>
<button type="submit" class="btn btn-primary">Log In</button>
</form>
<div class="auth-footer">Don't have a patient account? <a href="signup.php">Create an account</a></div>
</div></main></div></div>
<script>
const emailInput=document.getElementById('login_email'),passwordInput=document.getElementById('login_password'),emailFeedback=document.getElementById('emailFeedback');
[emailInput,passwordInput].forEach(input=>{const unlock=()=>input.removeAttribute('readonly');input.addEventListener('focus',unlock,{once:true});input.addEventListener('pointerdown',unlock,{once:true});});
function validSupremeDentEmail(value){value=value.trim();if(value.length<5||value.length>150||value.split('@').length!==2)return false;const [local,domainRaw]=value.split('@'),domain=domainRaw.toLowerCase();if(!/^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+$/.test(local)||local.includes('..'))return false;if(!/^(?=.{3,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i.test(domain)||domain.includes('..'))return false;if(/\.(com|net|org|edu|gov|ph)\.\1$/i.test(domain))return false;const providers={'gmail.':['gmail.com'],'googlemail.':['googlemail.com'],'outlook.':['outlook.com'],'hotmail.':['hotmail.com'],'icloud.':['icloud.com']};for(const [prefix,allowed] of Object.entries(providers)){if(domain.startsWith(prefix)&&!allowed.includes(domain))return false}return true}
function updateEmailFeedback(){const v=emailInput.value.trim();if(!v){emailFeedback.textContent='';emailFeedback.className='field-feedback';emailInput.setCustomValidity('');return true}const ok=validSupremeDentEmail(v);emailFeedback.textContent=ok?'':'Please enter a valid email address.';emailFeedback.className='field-feedback '+(ok?'':'bad');emailInput.setCustomValidity(ok?'':'Enter a valid email address.');return ok}
emailInput.addEventListener('input',updateEmailFeedback);emailInput.addEventListener('blur',updateEmailFeedback);updateEmailFeedback();
// Open eye = password is visible, slashed eye = password is hidden.
document.querySelectorAll('[data-toggle-password]').forEach(button=>button.addEventListener('click',function(){const input=document.getElementById(this.dataset.togglePassword),icon=this.querySelector('i');if(!input)return;input.removeAttribute('readonly');const show=input.type==='password';input.type=show?'text':'password';icon.classList.toggle('fa-eye',show);icon.classList.toggle('fa-eye-slash',!show);this.setAttribute('aria-label',show?'Hide password':'Show password');this.setAttribute('aria-pressed',String(show))}));
// Lockout countdown: the Log In button stays disabled until the wait time is over.
(()=>{const box=document.getElementById('loginMessage');if(!box||!box.dataset.lockSeconds)return;const text=document.getElementById('loginMessageText'),btn=document.querySelector('#loginForm button[type=submit]');const end=Date.now()+Number(box.dataset.lockSeconds)*1000;function tick(){const left=Math.max(0,Math.ceil((end-Date.now())/1000));if(!left){text.textContent='You can try signing in again now.';box.classList.remove('is-error');box.classList.add('is-success');btn.disabled=false;btn.textContent='Log In';clearInterval(t);return}const m=Math.floor(left/60),s=String(left%60).padStart(2,'0');text.textContent='Too many failed sign-in attempts. Please wait '+m+':'+s+' before trying again.';btn.disabled=true;btn.textContent='Try again in '+m+':'+s}const t=setInterval(tick,1000);tick()})();
document.getElementById('loginForm').addEventListener('submit',e=>{emailInput.removeAttribute('readonly');passwordInput.removeAttribute('readonly');if(!updateEmailFeedback()||!passwordInput.value){e.preventDefault();if(!updateEmailFeedback())emailInput.focus();else passwordInput.focus();}});
</script>
</body></html>
