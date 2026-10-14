<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

$uid = (int)$_SESSION['user_id'];
$msg = '';
$type = 'success';
$roles = ['patient', 'assistant', 'dentist', 'superadmin'];
$statuses = ['active', 'inactive'];
$confirm = null; // pending Super Admin change waiting for the admin's password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    $role = (string)($_POST['role'] ?? '');
    $target = null;
    if ($id > 0) {
        $st = $conn->prepare('SELECT id,first_name,last_name,email,role,status FROM users WHERE id=? LIMIT 1');
        $st->bind_param('i', $id);
        $st->execute();
        $target = $st->get_result()->fetch_assoc();
        $st->close();
    }
    if (!$target || $id === $uid || !in_array($status, $statuses, true) || !in_array($role, $roles, true)) {
        $msg = 'Invalid user update. Your own active Super Admin account cannot be changed here.';
        $type = 'error';
    } elseif ($target['role'] === $role && $target['status'] === $status) {
        $msg = 'No changes to save.';
    } else {
        // Giving or removing Super Admin rights is the most powerful change, so the admin's password is required.
        $sensitive = $role === 'superadmin' || $target['role'] === 'superadmin';
        $passwordOk = false;
        if ($sensitive && isset($_POST['confirm_password'])) {
            $st = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
            $st->bind_param('i', $uid);
            $st->execute();
            $passwordOk = password_verify((string)$_POST['confirm_password'], (string)($st->get_result()->fetch_assoc()['password'] ?? ''));
            $st->close();
            if (!$passwordOk) { usleep(250000); $msg = 'Your password is incorrect. The change was not saved.'; $type = 'error'; }
        }
        if ($sensitive && !$passwordOk) {
            if (!isset($_POST['confirm_password'])) $confirm = ['user' => $target, 'role' => $role, 'status' => $status];
        } else {
            $st = $conn->prepare('UPDATE users SET status=?,role=? WHERE id=?');
            $st->bind_param('ssi', $status, $role, $id);
            $st->execute();
            $st->close();
            log_activity($conn, $uid, 'Updated user account', 'Users', 'User #' . $id . ' (' . $target['email'] . ') role ' . $target['role'] . '→' . $role . ', status ' . $target['status'] . '→' . $status);
            $msg = 'User updated.' . ($status === 'inactive' ? ' The account was signed out and can no longer sign in.' : '');
        }
    }
}

$roleFilter = in_array($_GET['role'] ?? '', $roles, true) ? $_GET['role'] : '';
$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT id,first_name,last_name,email,role,status,created_at,last_login_at FROM users WHERE 1=1';
$types = '';
$params = [];
if ($roleFilter !== '') { $sql .= ' AND role=?'; $types .= 's'; $params[] = $roleFilter; }
if ($q !== '') { $like = '%' . $q . '%'; $sql .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)'; $types .= 'sss'; array_push($params, $like, $like, $like); }
$sql .= ' ORDER BY created_at DESC LIMIT 500';
$st = $conn->prepare($sql);
if ($types !== '') $st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result();
$label = static fn(string $r) => $r === 'superadmin' ? 'Super Admin' : ucfirst($r);
require '../includes/ui.php';
ui_header('Users', 'superadmin');
ui_sidebar('superadmin', 'users.php');
?>
<div class="page-head"><div><div class="eyebrow">USER MANAGEMENT</div><h1>Users</h1><div class="muted">Manage account roles and status. Setting an account to Inactive signs it out immediately and blocks sign-in.</div></div><div style="display:flex;gap:8px"><a class="btn btn-primary" href="create_dentist.php">Create Dentist</a><a class="btn btn-secondary" href="create_assistant.php">Create Assistant</a></div></div>
<?php if ($msg) flash($type, $msg); ?>
<?php if ($confirm): ?>
<section class="card"><h2>Confirm Super Admin change</h2><p>You are changing <b><?= e($confirm['user']['first_name'] . ' ' . $confirm['user']['last_name']) ?></b> (<?= e($confirm['user']['email']) ?>) from <b><?= e($label($confirm['user']['role'])) ?>, <?= e($confirm['user']['status']) ?></b> to <b><?= e($label($confirm['role'])) ?>, <?= e($confirm['status']) ?></b>. Enter your password to confirm.</p>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$confirm['user']['id'] ?>"><input type="hidden" name="role" value="<?= e($confirm['role']) ?>"><input type="hidden" name="status" value="<?= e($confirm['status']) ?>">
<div class="field"><label for="confirmPassword">Your password</label><input type="password" id="confirmPassword" name="confirm_password" maxlength="128" autocomplete="current-password" required autofocus></div>
<div class="actions full"><button class="btn btn-primary">Confirm Change</button><a class="btn btn-secondary" href="users.php">Cancel</a></div></form></section>
<?php endif; ?>
<section class="card"><form class="directory-search" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Search name or email" aria-label="Search users"><select name="role" aria-label="Role"><option value="">All roles</option><?php foreach ($roles as $r): ?><option value="<?= e($r) ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= e($label($r)) ?></option><?php endforeach; ?></select><button class="btn btn-primary">Search</button><?php if ($q !== '' || $roleFilter !== ''): ?><a class="btn btn-secondary" href="users.php">Clear</a><?php endif; ?></form>
<div class="table-wrap"><table class="table"><thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th><th>Last sign-in</th><th>Update</th></tr></thead><tbody>
<?php while ($r = $rows->fetch_assoc()): $formId = 'user-' . $r['id']; $self = (int)$r['id'] === $uid; ?>
<tr><td><b><?= e($r['first_name'] . ' ' . $r['last_name']) ?></b></td><td><?= e($r['email']) ?></td>
<td><select name="role" form="<?= $formId ?>" <?= $self ? 'disabled' : '' ?>><?php foreach ($roles as $role): ?><option value="<?= $role ?>" <?= $r['role'] === $role ? 'selected' : '' ?>><?= e($label($role)) ?></option><?php endforeach; ?></select></td>
<td><select name="status" form="<?= $formId ?>" <?= $self ? 'disabled' : '' ?>><?php foreach ($statuses as $s): ?><option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option><?php endforeach; ?></select></td>
<td><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td><td><?= $r['last_login_at'] ? e(date('M d, Y h:i A', strtotime($r['last_login_at']))) : '<span class="muted">—</span>' ?></td>
<td><?php if ($self): ?><span class="muted">Current account</span><?php else: ?><form id="<?= $formId ?>" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-secondary">Save</button></form><?php endif; ?></td></tr>
<?php endwhile; ?></tbody></table></div></section>
<?php ui_footer(); ?>
