<?php
/**
 * Super Admin › Appointments: every booking in the clinic, with month/status/dentist filters,
 * a per-dentist queue, dentist (re)assignment and cancellation.
 */
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
require_once __DIR__ . '/../includes/appointment_helpers.php';

$uid = (int)$_SESSION['user_id'];
$msg = '';
$msgType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'cancel') {
        [$ok, $text] = sd_cancel_appointment($conn, (int)($_POST['appointment_id'] ?? 0), $uid, 'superadmin', (string)($_POST['cancel_reason'] ?? ''));
        $msg = $ok ? $text . ' The patient was notified.' : $text;
        $msgType = $ok ? 'success' : 'error';
    } elseif ($action === 'assign') {
        [$ok, $text] = sd_assign_dentist($conn, (int)($_POST['appointment_id'] ?? 0), (int)($_POST['dentist_id'] ?? 0), $uid);
        $msg = $ok ? $text . ' The dentist and the patient were notified.' : $text;
        $msgType = $ok ? 'success' : 'error';
    }
}

$month = (string)($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) $month = date('Y-m');
$statusFilter = (string)($_GET['status'] ?? 'all');
$statusOptions = ['all' => 'All statuses', 'upcoming' => 'Upcoming (active)', 'approved' => 'Approved', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
if (!isset($statusOptions[$statusFilter])) $statusFilter = 'all';
$dentistFilter = (string)($_GET['dentist'] ?? '');
$start = $month . '-01';
$end = date('Y-m-t', strtotime($start));

$dentists = [];
$r = $conn->query("SELECT id,first_name,last_name FROM users WHERE role='dentist' AND status='active' ORDER BY last_name,first_name");
if ($r) $dentists = $r->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT a.*,CONCAT(p.first_name,' ',p.last_name) patient_name,CONCAT(d.first_name,' ',d.last_name) dentist_name
        FROM appointments a JOIN users p ON p.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id WHERE a.appointment_date BETWEEN ? AND ?";
$types = 'ss';
$params = [$start, $end];
if ($statusFilter === 'upcoming') { $sql .= " AND a.status IN ('pending','approved') AND a.appointment_date>=CURDATE()"; }
elseif ($statusFilter !== 'all') { $sql .= ' AND a.status=?'; $types .= 's'; $params[] = $statusFilter; }
if ($dentistFilter === 'none') { $sql .= ' AND a.dentist_id IS NULL'; }
elseif (ctype_digit($dentistFilter)) { $sql .= ' AND a.dentist_id=?'; $types .= 'i'; $params[] = (int)$dentistFilter; }
$sql .= ' ORDER BY a.appointment_date,a.appointment_time';
$st = $conn->prepare($sql);
$st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();

// Dentist queue: workload per dentist, plus bookings nobody has been assigned to yet.
$queue = [];
$r = $conn->query("SELECT u.id,CONCAT(u.first_name,' ',u.last_name) name,
        SUM(a.appointment_date=CURDATE() AND a.status IN ('pending','approved')) today,
        SUM(a.appointment_date>=CURDATE() AND a.status IN ('pending','approved')) upcoming,
        SUM(a.status='completed' AND a.appointment_date>=DATE_FORMAT(CURDATE(),'%Y-%m-01')) done_month
    FROM users u LEFT JOIN appointments a ON a.dentist_id=u.id
    WHERE u.role='dentist' AND u.status='active' GROUP BY u.id ORDER BY u.last_name,u.first_name");
if ($r) $queue = $r->fetch_all(MYSQLI_ASSOC);
$unassigned = 0;
$r = $conn->query("SELECT COUNT(*) c FROM appointments WHERE dentist_id IS NULL AND status IN ('pending','approved') AND appointment_date>=CURDATE()");
if ($r) $unassigned = (int)($r->fetch_assoc()['c'] ?? 0);

$filterUrl = static fn(array $over) => 'calendar.php?' . http_build_query(array_merge(['month' => $month, 'status' => $statusFilter, 'dentist' => $dentistFilter], $over));
require '../includes/ui.php';
ui_header('Appointments', 'superadmin');
ui_sidebar('superadmin', 'calendar.php');
?>
<div class="page-head"><div><div class="eyebrow">CLINIC APPOINTMENTS</div><h1>Appointments</h1><div class="muted">System-wide view of patient schedules and dentist assignments. Bookings are approved automatically; assign a dentist when none was free.</div></div></div>
<?php if ($msg) flash($msgType, $msg); ?>
<section class="card" id="queue"><div class="card-heading"><div><h2>Dentist Queue</h2><p class="muted">Active bookings per dentist. Click a name to see only that dentist's appointments.</p></div><?php if ($unassigned): ?><a class="badge status-inactive" href="<?= e($filterUrl(['dentist' => 'none', 'status' => 'upcoming', 'month' => date('Y-m')])) ?>"><?= $unassigned ?> unassigned</a><?php endif; ?></div>
<div class="table-wrap"><table class="table"><thead><tr><th>Dentist</th><th>Today</th><th>Upcoming</th><th>Completed this month</th></tr></thead><tbody>
<?php if (!$queue): ?><tr><td colspan="4" class="empty">No active dentists. Create one under Users.</td></tr><?php endif; ?>
<?php foreach ($queue as $q): ?><tr><td><a class="plain-link" href="<?= e($filterUrl(['dentist' => (string)$q['id']])) ?>"><b>Dr. <?= e($q['name']) ?></b></a></td><td><?= (int)$q['today'] ?></td><td><?= (int)$q['upcoming'] ?></td><td><?= (int)$q['done_month'] ?></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<section class="card"><form class="directory-search" method="get">
<input type="month" name="month" value="<?= e($month) ?>" aria-label="Month">
<select name="status" aria-label="Status"><?php foreach ($statusOptions as $k => $label): ?><option value="<?= e($k) ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<select name="dentist" aria-label="Dentist"><option value="">All dentists</option><option value="none" <?= $dentistFilter === 'none' ? 'selected' : '' ?>>Unassigned</option><?php foreach ($dentists as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $dentistFilter === (string)$d['id'] ? 'selected' : '' ?>>Dr. <?= e($d['last_name'] . ', ' . $d['first_name']) ?></option><?php endforeach; ?></select>
<button class="btn btn-primary">Show</button><a class="btn btn-secondary" href="calendar.php">Reset</a></form>
<div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Dentist</th><th>Service</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($rows as $r): $active = in_array($r['status'], ['pending', 'approved'], true) && strtotime($r['appointment_date'] . ' ' . $r['appointment_time']) >= time(); ?>
<tr><td><?= e(date('M d, Y', strtotime($r['appointment_date']))) ?></td><td><?= e(date('h:i A', strtotime($r['appointment_time']))) ?></td><td><?= e($r['patient_name']) ?></td>
<td><?= $r['dentist_name'] ? 'Dr. ' . e($r['dentist_name']) : '<span class="badge status-inactive">Unassigned</span>' ?>
<?php if ($active): ?><form method="post" style="display:flex;gap:6px;margin-top:6px"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="assign"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>"><select name="dentist_id" required aria-label="Assign dentist"><option value=""><?= $r['dentist_id'] ? 'Reassign…' : 'Assign…' ?></option><?php foreach ($dentists as $d): if ((int)$d['id'] === (int)$r['dentist_id']) continue; ?><option value="<?= (int)$d['id'] ?>">Dr. <?= e($d['last_name']) ?></option><?php endforeach; ?></select><button class="btn btn-secondary btn-sm">Save</button></form><?php endif; ?></td>
<td><?= e($r['appointment_type']) ?></td><td><span class="status-pill <?= e($r['status']) ?>"><?= e($r['status'] === 'rejected' ? 'Declined' : ucfirst($r['status'])) ?></span></td>
<td><?php if (in_array($r['status'], ['pending', 'approved'], true)): ?><form method="post" class="row-actions" onsubmit="return confirm('Cancel this appointment? The patient will be notified.')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="<?= (int)$r['id'] ?>"><input name="cancel_reason" maxlength="255" placeholder="Reason (optional)" aria-label="Cancellation reason"><button class="btn btn-danger btn-sm">Cancel</button></form><?php else: ?><span class="muted">—</span><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="7" class="empty">No appointments match these filters.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php ui_footer(); ?>
