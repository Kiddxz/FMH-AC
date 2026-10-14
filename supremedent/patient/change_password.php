<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient');

$msg = '';
$type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $con = (string)($_POST['confirm_password'] ?? '');

    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $msg = 'Your session expired. Refresh the page and try again.';
        $type = 'error';
    } elseif (!valid_strong_password($new)) {
        $msg = 'New password must be 8-128 characters and include uppercase, lowercase, a number, and a special character.';
        $type = 'error';
    } elseif ($new !== $con) {
        $msg = 'Passwords do not match.';
        $type = 'error';
    } else {
        $st = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
        $uid = (int)$_SESSION['user_id'];
        $st->bind_param('i', $uid);
        $st->execute();
        $user = $st->get_result()->fetch_assoc();
        $st->close();

        if (!$user || !password_verify($cur, $user['password'])) {
            usleep(250000);
            $msg = 'Current password is incorrect.';
            $type = 'error';
        } elseif (password_verify($new, $user['password'])) {
            $msg = 'Choose a new password that is different from your current password.';
            $type = 'error';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $st = $conn->prepare('UPDATE users SET password=?,must_change_password=0,password_changed_at=NOW() WHERE id=?');
            $st->bind_param('si', $hash, $uid);
            $st->execute();
            $st->close();
            session_regenerate_id(true);
            $_SESSION['session_regenerated_at'] = time();
            // Keeps this session signed in; every other session of this account is signed out.
            $_SESSION['auth_fp'] = sd_auth_fingerprint($hash);
            log_activity($conn, $uid, 'Password changed', 'Security', 'Patient changed password from profile.');
            $msg = 'Password changed successfully. Other devices signed in to your account were signed out.';
            $type = 'success';
        }
    }
}

require '../includes/ui.php';
ui_header('Change Password', 'patient');
ui_sidebar('patient', 'profile.php');
?>
<div class="page-head"><div><div class="eyebrow">PROFILE</div><h1>Change Password</h1><div class="muted">Keep your SupremeDent account secure.</div></div></div>
<?php if ($msg) flash($type, $msg); ?>
<section class="card">
<form method="post" class="form-grid" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
    <div class="field full"><label>Current Password</label><input type="password" name="current_password" autocomplete="current-password" required></div>
    <div class="field"><label>New Password</label><input type="password" name="new_password" minlength="8" maxlength="128" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,128}" title="Use uppercase, lowercase, a number, and a special character." required></div>
    <div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" minlength="8" maxlength="128" autocomplete="new-password" required></div>
    <div class="actions full"><button class="btn btn-primary">Change Password</button></div>
</form>
</section>
<?php ui_footer(); ?>
