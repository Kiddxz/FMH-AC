<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

start_secure_session(); require_once '../config/database.php';
if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}
function count_rows($conn,$sql){$r=$conn->query($sql);return $r?(int)$r->fetch_assoc()['c']:0;}
$users=count_rows($conn,"SELECT COUNT(*) c FROM users");$patients=count_rows($conn,"SELECT COUNT(*) c FROM users WHERE role='patient'");$dentists=count_rows($conn,"SELECT COUNT(*) c FROM users WHERE role='dentist' AND status='active'");$assistants=count_rows($conn,"SELECT COUNT(*) c FROM users WHERE role='assistant' AND status='active'");$pending=count_rows($conn,"SELECT COUNT(*) c FROM appointments WHERE status='pending'");$approved=count_rows($conn,"SELECT COUNT(*) c FROM appointments WHERE status='approved'");
require '../includes/ui.php';ui_header('Super Admin Dashboard','superadmin');ui_sidebar('superadmin','dashboard.php');
?>
<div class="page-head"><div><div class="eyebrow">SYSTEM ADMINISTRATION</div><h1>Super Admin Dashboard</h1><div class="muted">Control staff accounts and monitor the SupremeDent workflow.</div></div></div>
<section class="grid grid-4"><div class="stat"><span>Total Users</span><b><?=$users?></b></div><div class="stat"><span>Patients</span><b><?=$patients?></b></div><div class="stat"><span>Active Dentists</span><b><?=$dentists?></b></div><div class="stat"><span>Active Assistants</span><b><?=$assistants?></b></div></section>
<section class="grid grid-2"><div class="card"><div class="card-heading"><div><h2>Appointment Workflow</h2><p class="muted">Monitor requests from patients to assistant approval and dentist assignment.</p></div></div><div class="mini-stats"><div><strong><?=$pending?></strong><span>Pending review</span></div><div><strong><?=$approved?></strong><span>Approved</span></div></div><div class="actions"><a class="btn btn-primary" href="../assistant/appointments.php">Review Requests</a><a class="btn btn-secondary" href="../dentist/appointments.php">View Dentist Queue</a></div></div>
<div class="card"><div class="card-heading"><div><h2>Staff Accounts</h2><p class="muted">Create the accounts used by your clinic team.</p></div></div><div class="actions"><a class="btn btn-primary" href="create_dentist.php">+ Create Dentist</a><a class="btn btn-secondary" href="create_assistant.php">+ Create Assistant</a><a class="btn btn-secondary" href="users.php">Manage Users</a></div></div></section>
<section class="card"><h2>System Tools</h2><div class="actions"><a class="btn btn-secondary" href="activity_logs.php">Activity Logs</a><a class="btn btn-secondary" href="backup.php">Backup & Recovery</a><a class="btn btn-secondary" href="website_content.php">Website Content</a><a class="btn btn-secondary" href="monitoring.php">System Monitoring</a></div></section>
<?php ui_footer(); ?>
