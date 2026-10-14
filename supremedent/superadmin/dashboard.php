<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
require_once __DIR__ . '/../includes/auth_helpers.php';

function count_rows(mysqli $conn, string $sql): int { $r = $conn->query($sql); return $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0; }
$users = count_rows($conn, "SELECT COUNT(*) c FROM users");
$patients = count_rows($conn, "SELECT COUNT(*) c FROM users WHERE role='patient'");
$dentists = count_rows($conn, "SELECT COUNT(*) c FROM users WHERE role='dentist' AND status='active'");
$assistants = count_rows($conn, "SELECT COUNT(*) c FROM users WHERE role='assistant' AND status='active'");
$superadmins = count_rows($conn, "SELECT COUNT(*) c FROM users WHERE role='superadmin' AND status='active'");
// Bookings are approved automatically, so the workflow card tracks today's load and bookings that still need a dentist.
$today = count_rows($conn, "SELECT COUNT(*) c FROM appointments WHERE appointment_date=CURDATE() AND status IN ('pending','approved')");
$upcoming = count_rows($conn, "SELECT COUNT(*) c FROM appointments WHERE appointment_date>=CURDATE() AND status IN ('pending','approved')");
$unassigned = count_rows($conn, "SELECT COUNT(*) c FROM appointments WHERE dentist_id IS NULL AND appointment_date>=CURDATE() AND status IN ('pending','approved')");
$cancelledMonth = count_rows($conn, "SELECT COUNT(*) c FROM appointments WHERE status='cancelled' AND appointment_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01')");
$newInquiries = count_rows($conn, "SELECT COUNT(*) c FROM contact_messages WHERE status='new'");
$failedLogins = count_rows($conn, "SELECT COUNT(*) c FROM activity_logs WHERE action IN ('Sign-in locked','Wrong sign-in code','Blocked unauthorized page access') AND created_at>=DATE_SUB(NOW(), INTERVAL 1 DAY)");
$otpRoles = sd_login_otp_roles($conn);
require '../includes/ui.php';
ui_header('Super Admin Dashboard', 'superadmin');
ui_sidebar('superadmin', 'dashboard.php');
?>
<div class="page-head"><div><div class="eyebrow">SYSTEM ADMINISTRATION</div><h1>Super Admin Dashboard</h1><div class="muted">Control staff accounts and monitor the SupremeDent workflow.</div></div></div>
<section class="grid grid-4"><div class="stat"><span>Total Users</span><b><?= $users ?></b></div><div class="stat"><span>Patients</span><b><?= $patients ?></b></div><div class="stat"><span>Active Dentists</span><b><?= $dentists ?></b></div><div class="stat"><span>Active Assistants</span><b><?= $assistants ?></b></div></section>
<section class="grid grid-2">
<div class="card"><div class="card-heading"><div><h2>Appointment Workflow</h2><p class="muted">Patient bookings are approved automatically and given a free dentist. Assign a dentist to any booking that has none.</p></div></div>
<div class="mini-stats"><div><strong><?= $today ?></strong><span>Today</span></div><div><strong><?= $upcoming ?></strong><span>Upcoming</span></div><div><strong><?= $unassigned ?></strong><span>Need a dentist</span></div><div><strong><?= $cancelledMonth ?></strong><span>Cancelled this month</span></div></div>
<div class="actions"><a class="btn btn-primary" href="calendar.php?status=upcoming">Review Appointments</a><a class="btn btn-secondary" href="calendar.php#queue">View Dentist Queue</a><?php if ($unassigned): ?><a class="btn btn-secondary" href="calendar.php?status=upcoming&amp;dentist=none">Assign Dentists (<?= $unassigned ?>)</a><?php endif; ?></div></div>
<div class="card"><div class="card-heading"><div><h2>Staff Accounts</h2><p class="muted">Create the accounts used by your clinic team. New staff must change the password you give them on first sign-in.</p></div></div><div class="actions"><a class="btn btn-primary" href="create_dentist.php">+ Create Dentist</a><a class="btn btn-secondary" href="create_assistant.php">+ Create Assistant</a><a class="btn btn-secondary" href="users.php">Manage Users</a></div></div>
</section>
<section class="grid grid-2">
<div class="card"><div class="card-heading"><div><h2>Security</h2><p class="muted">Account protection at a glance.</p></div></div>
<div class="mini-stats"><div><strong><?= $superadmins ?></strong><span>Active Super Admins</span></div><div><strong><?= $failedLogins ?></strong><span>Security events (24h)</span></div></div>
<p class="muted">2-step sign-in code: <b><?= $otpRoles ? e(implode(', ', array_map(static fn($r) => $r === 'superadmin' ? 'Super Admin' : ucfirst($r), $otpRoles))) : 'Off' ?></b><?= $superadmins < 2 ? ' · Tip: create a backup Super Admin with the setup tool (superadmin_setup/) so the clinic is never locked out.' : '' ?></p>
<div class="actions"><a class="btn btn-secondary" href="roles.php#security">Sign-in Security</a><a class="btn btn-secondary" href="activity_logs.php">Activity Logs</a></div></div>
<div class="card"><div class="card-heading"><div><h2>System Tools</h2><p class="muted"><?= $newInquiries ?> new website inquir<?= $newInquiries === 1 ? 'y' : 'ies' ?>.</p></div></div><div class="actions"><a class="btn btn-secondary" href="inquiries.php">Inquiries</a><a class="btn btn-secondary" href="backup.php">Backup &amp; Recovery</a><a class="btn btn-secondary" href="website_content.php">Website Content</a><a class="btn btn-secondary" href="monitoring.php">System Monitoring</a></div></div>
</section>
<?php ui_footer(); ?>
