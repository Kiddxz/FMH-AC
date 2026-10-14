<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
function dc(mysqli $c, string $s): int
{
    $r = $c->query($s);
    return $r ? (int) ($r->fetch_assoc()['c'] ?? 0) : 0;
}
$uid = (int) $_SESSION['user_id'];
$patients = dc($conn, "SELECT COUNT(*) c FROM users WHERE role='patient'");
$today = dc($conn, "SELECT COUNT(*) c FROM appointments WHERE dentist_id=$uid AND appointment_date=CURDATE() AND status='approved'");
$charts = dc($conn, 'SELECT COUNT(*) c FROM dental_charts');
$pendingPlans = dc($conn, "SELECT COUNT(*) c FROM treatment_plans WHERE status IN ('Proposed','Approved','In Progress')") + dc($conn, "SELECT COUNT(*) c FROM restoration_orders WHERE dentist_id=$uid AND status NOT IN ('Completed','Cancelled')");
$todayRows = [];
$r = $conn->query("SELECT a.id,a.appointment_time,a.appointment_type,a.status,u.first_name,u.last_name FROM appointments a JOIN users u ON u.id=a.patient_id WHERE a.dentist_id=$uid AND a.appointment_date=CURDATE() AND a.status IN ('approved','completed') ORDER BY a.appointment_time");
if ($r)
    $todayRows = $r->fetch_all(MYSQLI_ASSOC);
$upcoming = [];
$r = $conn->query("SELECT a.id,a.appointment_date,a.appointment_time,a.appointment_type,a.status,a.created_at,u.first_name,u.last_name FROM appointments a JOIN users u ON u.id=a.patient_id WHERE a.dentist_id=$uid AND a.status IN ('approved','completed') ORDER BY a.appointment_date DESC,a.appointment_time DESC LIMIT 5");
if ($r)
    $upcoming = $r->fetch_all(MYSQLI_ASSOC);
$activity = [];
foreach ($upcoming as $a)
    $activity[] = ['icon' => 'calendar', 'title' => ($a['status'] === 'completed' ? 'Visit completed' : 'Appointment assigned') . ' · ' . $a['first_name'] . ' ' . $a['last_name'], 'text' => $a['appointment_type'] . ' on ' . date('M d, Y', strtotime($a['appointment_date'])) . ' at ' . date('h:i A', strtotime($a['appointment_time'])), 'ts' => strtotime($a['created_at']), 'url' => 'appointment_view.php?id=' . $a['id']];
$r = $conn->query("SELECT c.id,c.created_at,u.first_name,u.last_name FROM dental_charts c JOIN users u ON u.id=c.patient_id ORDER BY c.created_at DESC LIMIT 4");
if ($r)
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $c)
        $activity[] = ['icon' => 'record', 'title' => 'Dental chart saved · ' . $c['first_name'] . ' ' . $c['last_name'], 'text' => 'Examination record ready to review or print.', 'ts' => strtotime($c['created_at']), 'url' => 'chart_view.php?id=' . $c['id']];
$r = $conn->query("SELECT ro.created_at,ro.restoration_type,ro.status,u.first_name,u.last_name FROM restoration_orders ro JOIN users u ON u.id=ro.patient_id WHERE ro.dentist_id=$uid ORDER BY ro.created_at DESC LIMIT 3");
if ($r)
    foreach ($r->fetch_all(MYSQLI_ASSOC) as $p)
        $activity[] = ['icon' => 'plan', 'title' => ucfirst($p['restoration_type']) . ' restoration plan · ' . $p['first_name'] . ' ' . $p['last_name'], 'text' => 'Status: ' . $p['status'] . '.', 'ts' => strtotime($p['created_at']), 'url' => 'treatment_plans.php'];
usort($activity, static fn($a, $b) => $b['ts'] <=> $a['ts']);
$activity = array_slice($activity, 0, 6);
$icons = ['calendar' => 'fa-calendar-check', 'record' => 'fa-file-medical', 'plan' => 'fa-clipboard-list'];
require '../includes/ui.php';
ui_header('Dentist Dashboard', 'dentist');
ui_sidebar('dentist', 'dashboard.php'); ?>
<section class="dashboard-hero">
    <div>
        <div class="eyebrow">DENTIST PORTAL</div>
        <h1>Welcome back, <?= e($_SESSION['first_name'] ?? 'Dentist') ?>.</h1>
        <p>Review today's patient schedule, open clinical charts, and manage treatment plans from one workspace.</p>
    </div>
    <div class="dashboard-date"><?= e(date('l')) ?><b><?= e(date('M d')) ?></b><span><?= e(date('Y')) ?></span></div>
</section>
<section class="metric-grid"><a href="patients.php" class="metric-card"><span>Registered
            Patients</span><b><?= $patients ?></b><small>Open patient directory</small></a><a href="appointments.php"
        class="metric-card"><span>Today's Appointments</span><b><?= $today ?></b><small>View calendar</small></a><a
        href="dental_charts.php" class="metric-card"><span>Dental Charts</span><b><?= $charts ?></b><small>Review chart
            records</small></a><a href="treatment_plans.php" class="metric-card"><span>Active Treatment
            Plans</span><b><?= $pendingPlans ?></b><small>Manage recommended care</small></a></section>
<div class="staff-dash-columns">
    <section class="card staff-dash-panel">
        <div class="activity-head">
            <h2>Recent activity</h2><a href="appointments.php">Calendar</a>
        </div>
        <ul class="activity-list"><?php if (!$activity): ?>
                <li class="activity-placeholder"><span class="activity-icon empty"></span>
                    <div><b>Appointments, charts and plans will show up here</b><span>Each update appears in order.</span>
                    </div>
                </li><?php endif; ?><?php foreach ($activity as $a): ?>
                <li><span class="activity-icon <?= e($a['icon']) ?>"><i
                            class="fa-solid <?= e($icons[$a['icon']] ?? 'fa-circle') ?>"></i></span>
                    <div><b><a class="plain-link"
                                href="<?= e($a['url']) ?>"><?= e($a['title']) ?></a></b><span><?= e($a['text']) ?></span></div>
                    <time><?= e(date('Y-m-d', $a['ts']) === date('Y-m-d') ? date('h:i A', $a['ts']) : date('M j', $a['ts'])) ?></time>
                </li><?php endforeach; ?>
        </ul>
    </section>
    <section class="card staff-dash-panel">
        <div class="activity-head">
            <h2>Today's schedule</h2><a href="appointments.php">Calendar</a>
        </div>
        <div class="today-list"><?php if (!$todayRows): ?>
                <div class="empty-note">No confirmed appointments for today.</div>
            <?php endif; ?><?php foreach ($todayRows as $t): ?><a class="today-row"
                    href="appointment_view.php?id=<?= $t['id'] ?>"><time><?= e(date('h:i A', strtotime($t['appointment_time']))) ?></time><span><b><?= e($t['first_name'] . ' ' . $t['last_name']) ?></b><small><?= e($t['appointment_type']) ?></small></span><span
                        class="status-pill <?= e($t['status']) ?>"><?= e(ucfirst($t['status'])) ?></span></a><?php endforeach; ?>
        </div>
    </section>
</div>
<?php ui_footer(); ?>