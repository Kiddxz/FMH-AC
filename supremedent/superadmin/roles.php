<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
require_once __DIR__ . '/../includes/auth_helpers.php';

$uid = (int)$_SESSION['user_id'];
$msg = '';
$msgType = 'success';
$roleLabels = ['superadmin' => 'Super Admin', 'dentist' => 'Dentist', 'assistant' => 'Assistant', 'patient' => 'Patient'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login_security') {
    $st = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
    $st->bind_param('i', $uid);
    $st->execute();
    $hash = (string)($st->get_result()->fetch_assoc()['password'] ?? '');
    $st->close();
    $chosen = array_values(array_intersect(array_keys($roleLabels), (array)($_POST['otp_roles'] ?? [])));
    if (!password_verify((string)($_POST['current_password'] ?? ''), $hash)) {
        usleep(250000);
        $msg = 'Enter your current password to change sign-in security.';
        $msgType = 'error';
    } else {
        $value = implode(',', $chosen);
        $st = $conn->prepare("INSERT INTO clinic_settings(setting_key,setting_value) VALUES('login_otp_roles',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $st->bind_param('s', $value);
        $st->execute();
        $st->close();
        log_activity($conn, $uid, 'Changed sign-in security', 'Security', '2-step sign-in code for: ' . ($value !== '' ? $value : 'nobody'));
        $msg = 'Sign-in security updated.';
    }
}
$otpRoles = sd_login_otp_roles($conn);
$otpSwitchedOff = sd_local_config()['disable_login_otp'];

$defaults = [['view_dashboard','View dashboard'],['manage_patients','Manage patients'],['manage_appointments','Manage appointments'],['manage_dental_records','Manage dental records'],['manage_treatments','Manage treatment plans'],['manage_laboratory','Manage laboratory'],['view_reports','View reports'],['manage_users','Manage users'],['manage_website','Manage website content'],['manage_system','Manage system']];
$st = $conn->prepare('INSERT IGNORE INTO permissions(permission_key,label) VALUES(?,?)');
foreach ($defaults as $d) { $st->bind_param('ss', $d[0], $d[1]); $st->execute(); }
$st->close();
$perms = $conn->query('SELECT * FROM permissions ORDER BY label');
require '../includes/ui.php';
ui_header('Roles & Permissions', 'superadmin');
ui_sidebar('superadmin', 'roles.php');
?>
<div class="page-head"><div><div class="eyebrow">ACCESS CONTROL</div><h1>Roles &amp; Permissions</h1><div class="muted">Each page checks the signed-in account's role in the database on every request; changing a URL never grants access.</div></div></div>
<?php if ($msg) flash($msgType, $msg); ?>
<section class="card"><div class="grid grid-4"><div class="stat"><b>Patient</b><span class="muted">Own appointments, records, plans and profile.</span></div><div class="stat"><b>Assistant</b><span class="muted">Patients, appointments, walk-ins and draft charts for dentist review.</span></div><div class="stat"><b>Dentist</b><span class="muted">Clinical records, charts, odontogram, plans, laboratory and reports.</span></div><div class="stat"><b>Super Admin</b><span class="muted">Accounts, appointments overview, system administration and monitoring.</span></div></div></section>
<section class="card" id="security"><div class="card-heading"><div><h2>Sign-in Security</h2><p class="muted">2-step verification: after the correct password, these roles must also enter a 6-digit code sent to their email. A stolen password alone is then not enough to get in.</p></div></div>
<?php if ($otpSwitchedOff): ?><div class="flash error">The sign-in code is switched off on this server (disable_login_otp in config/security.local.php). Turn it back on as soon as email works again.</div><?php endif; ?>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="login_security">
<div class="field full"><label>Require the emailed sign-in code for</label><div style="display:flex;flex-wrap:wrap;gap:16px"><?php foreach ($roleLabels as $key => $label): ?><label style="display:flex;align-items:center;gap:6px"><input type="checkbox" name="otp_roles[]" value="<?= e($key) ?>" style="width:auto" <?= in_array($key, $otpRoles, true) ? 'checked' : '' ?>> <?= e($label) ?></label><?php endforeach; ?></div><small class="muted">Recommended: Super Admin and Dentist. Make sure Gmail works first (Email / OTP Test).</small></div>
<div class="field"><label for="secPassword">Your current password</label><input type="password" id="secPassword" name="current_password" maxlength="128" autocomplete="current-password" required></div>
<div class="actions full"><button class="btn btn-primary">Save Sign-in Security</button></div></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Protection</th><th>Rule</th></tr></thead><tbody>
<tr><td>Wrong password lock</td><td>5 wrong passwords lock the email for 5 minutes, then 10 minutes.</td></tr>
<tr><td>Network limit</td><td>30 failed sign-ins from one network pause that network for 15 minutes.</td></tr>
<tr><td>Session timeout</td><td>Signed out after 30 minutes without activity, and after 12 hours in any case.</td></tr>
<tr><td>Password change</td><td>Changing or resetting a password signs out every other device.</td></tr>
<tr><td>Temporary passwords</td><td>Accounts created by the clinic must choose their own password on first sign-in.</td></tr>
<tr><td>Forms</td><td>Every form and request carries a security token (CSRF protection).</td></tr>
</tbody></table></div></section>
<section class="card"><h2>Available Permissions</h2><div class="table-wrap"><table class="table"><thead><tr><th>Key</th><th>Permission</th></tr></thead><tbody><?php while ($p = $perms->fetch_assoc()): ?><tr><td><?= e($p['permission_key']) ?></td><td><?= e($p['label']) ?></td></tr><?php endwhile; ?></tbody></table></div></section>
<?php ui_footer(); ?>
