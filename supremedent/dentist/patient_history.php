<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

$pid = (int)($_GET['patient_id'] ?? 0);
$q = trim($_GET['q'] ?? '');
$all = $conn->query("SELECT id,first_name,last_name,email FROM users WHERE role='patient' ORDER BY last_name,first_name")->fetch_all(MYSQLI_ASSOC);
$matches = [];
if (!$pid && $q !== '') {
    // Quick access: one match opens the history directly, several matches are listed to pick from.
    $needle = mb_strtolower($q);
    foreach ($all as $p) {
        $label = mb_strtolower($p['last_name'] . ', ' . $p['first_name']);
        $hay = mb_strtolower($p['first_name'] . ' ' . $p['last_name'] . ' ' . $p['last_name'] . ', ' . $p['first_name'] . ' ' . $p['email'] . ' #' . $p['id']);
        if ($label === $needle || mb_strtolower($p['email']) === $needle) { $matches = [$p]; break; }
        if (str_contains($hay, $needle)) $matches[] = $p;
    }
    if (count($matches) === 1) $pid = (int)$matches[0]['id'];
}
$events = [];
$selected = '';
if ($pid) {
    $st = $conn->prepare("SELECT first_name,last_name FROM users WHERE id=? AND role='patient'");
    $st->bind_param('i', $pid); $st->execute(); $x = $st->get_result()->fetch_assoc(); $st->close();
    if ($x) {
        $selected = $x['first_name'] . ' ' . $x['last_name'];
        if ($q === '') $q = $x['last_name'] . ', ' . $x['first_name'];
        $st = $conn->prepare("SELECT appointment_date d,appointment_time t,appointment_type title,reason detail,status FROM appointments WHERE patient_id=? UNION ALL SELECT record_date,'00:00:00',procedure_name,CONCAT(COALESCE(diagnosis,''),' ',COALESCE(notes,'')),'Dental Record' FROM dental_records WHERE patient_id=? UNION ALL SELECT created_at,'00:00:00',procedure_name,CONCAT('Tooth ',COALESCE(tooth_number,''),' - ',COALESCE(description,'')),status FROM treatment_plans WHERE patient_id=? UNION ALL SELECT request_date,'00:00:00',case_type,CONCAT(laboratory_name,' - ',COALESCE(notes,'')),status FROM laboratory_cases WHERE patient_id=? ORDER BY d DESC");
        $st->bind_param('iiii', $pid, $pid, $pid, $pid); $st->execute(); $events = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    } else { $pid = 0; }
}
require '../includes/ui.php'; ui_header('Patient History', 'dentist'); ui_sidebar('dentist', 'patient_history.php'); ?>
<div class="page-head compact-head"><div><div class="eyebrow">PATIENT HISTORY</div><h1>Unified Patient History</h1><div class="muted">Appointments, dental records, treatment plans and laboratory cases.</div></div></div>
<section class="card record-card">
    <form class="history-search" method="get" role="search">
        <label class="history-search-box"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input name="q" list="historyPatients" value="<?= e($q) ?>" placeholder="Search patient by name, email, or ID" aria-label="Search patient" autocomplete="off"></label>
        <datalist id="historyPatients"><?php foreach ($all as $p): ?><option value="<?= e($p['last_name'] . ', ' . $p['first_name']) ?>"><?= e($p['email']) ?></option><?php endforeach; ?></datalist>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    </form>
</section>
<?php if (!$pid && $q !== ''): ?>
<section class="card record-card"><div class="card-heading"><div><h2>Search results</h2><p class="muted"><?= count($matches) ?> patient<?= count($matches) === 1 ? '' : 's' ?> found for “<?= e($q) ?>”.</p></div></div>
<?php if (!$matches): ?><div class="empty-note">No patients matched your search.</div><?php else: ?><div class="table-wrap"><table class="record-table"><thead><tr><th>Patient</th><th>Email</th><th>Action</th></tr></thead><tbody><?php foreach ($matches as $m): ?><tr><td data-label="Patient"><b><?= e($m['first_name'] . ' ' . $m['last_name']) ?></b> <span class="muted">#<?= (int)$m['id'] ?></span></td><td data-label="Email"><?= e($m['email']) ?></td><td data-label="Action"><a class="btn btn-primary btn-sm" href="patient_history.php?patient_id=<?= (int)$m['id'] ?>">Open History</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<?php endif; ?>
<?php if ($pid): ?><section class="card record-card"><div class="card-heading"><div><h2><?= e($selected) ?></h2></div></div><div class="table-wrap"><table class="record-table"><thead><tr><th>Date</th><th>Event</th><th>Details</th><th>Status</th></tr></thead><tbody><?php foreach ($events as $ev): ?><tr><td data-label="Date"><?= e(date('M d, Y', strtotime($ev['d']))) ?></td><td data-label="Event"><?= e($ev['title']) ?></td><td data-label="Details"><?= e(trim($ev['detail'])) ?></td><td data-label="Status"><span class="status-pill <?= e(strtolower($ev['status'])) ?>"><?= e($ev['status'] === 'rejected' ? 'Declined' : ucfirst($ev['status'])) ?></span></td></tr><?php endforeach; if (!$events): ?><tr><td colspan="4" class="empty">No history found.</td></tr><?php endif; ?></tbody></table></div></section><?php endif; ?>
<?php ui_footer(); ?>
