<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

$q = trim($_GET['q'] ?? '');
$patientId = (int)($_GET['patient_id'] ?? 0);
$patient = null;
$charts = [];
if ($patientId > 0) {
    // One patient's chart history.
    $st = $conn->prepare("SELECT u.id,u.first_name,u.last_name,u.email,p.phone,p.birthday,p.gender FROM users u LEFT JOIN patient_profiles p ON p.user_id=u.id WHERE u.id=? AND u.role='patient' LIMIT 1");
    $st->bind_param('i', $patientId); $st->execute(); $patient = $st->get_result()->fetch_assoc(); $st->close();
    if ($patient) {
        $st = $conn->prepare("SELECT c.id,COALESCE(c.chart_date,DATE(c.created_at)) chart_date,COUNT(t.id) marked_teeth,COALESCE(cr.first_name,'Clinic') creator_first,COALESCE(cr.last_name,'Staff') creator_last,COALESCE(c.created_role,'dentist') created_role FROM dental_charts c LEFT JOIN dental_chart_teeth t ON t.chart_id=c.id LEFT JOIN users cr ON cr.id=COALESCE(c.created_by,c.dentist_id) WHERE c.patient_id=? GROUP BY c.id ORDER BY chart_date DESC,c.id DESC");
        $st->bind_param('i', $patientId); $st->execute(); $charts = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
    }
} else {
    $like = '%' . $q . '%';
    $st = $conn->prepare("SELECT u.id,u.first_name,u.last_name,u.email,COUNT(c.id) chart_count,MAX(COALESCE(c.chart_date,DATE(c.created_at))) latest_date,
        (SELECT c2.id FROM dental_charts c2 WHERE c2.patient_id=u.id ORDER BY COALESCE(c2.chart_date,DATE(c2.created_at)) DESC,c2.id DESC LIMIT 1) latest_id
        FROM users u LEFT JOIN dental_charts c ON c.patient_id=u.id
        WHERE u.role='patient' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)
        GROUP BY u.id ORDER BY (COUNT(c.id)>0) DESC, latest_date DESC, u.last_name, u.first_name");
    $st->bind_param('ssss', $like, $like, $like, $like); $st->execute(); $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
}
require '../includes/ui.php'; ui_header('Dental Charts', 'dentist'); ui_sidebar('dentist', 'dental_charts.php');
$deleteForm = static function (int $id, string $back): string {
    return '<form method="post" action="chart_delete.php" onsubmit="return confirm(\'Delete this chart? This cannot be undone.\');"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="back" value="' . e($back) . '"><button class="btn btn-danger btn-sm" type="submit">Delete</button></form>';
};
?>
<?php if ($patientId > 0 && $patient): ?>
<div class="page-head compact-head"><div><div class="eyebrow">DENTAL EXAMINATION RECORD</div><h1><?= e($patient['first_name'] . ' ' . $patient['last_name']) ?></h1><div class="muted"><?= e(implode(' · ', array_filter([$patient['email'], $patient['phone'], $patient['gender'], $patient['birthday'] ? (new DateTime($patient['birthday']))->diff(new DateTime('today'))->y . ' yrs' : '']))) ?></div></div><div class="actions"><a class="btn btn-secondary" href="dental_charts.php"><i class="fa-solid fa-arrow-left"></i> All Patients</a><a class="btn btn-primary" href="chart_editor.php?patient_id=<?= $patientId ?>"><i class="fa-solid fa-file-circle-plus"></i> Create Chart</a></div></div>
<?php if (isset($_GET['saved'])): ?><div class="flash success">Chart saved successfully.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="flash success">Chart deleted.</div><?php endif; ?>
<section class="card record-card"><div class="card-heading"><div><h2>Dental Charts</h2><p class="muted">Every examination record saved for this patient, newest first.</p></div><span class="badge"><?= count($charts) ?> chart<?= count($charts) === 1 ? '' : 's' ?></span></div>
<div class="table-wrap"><table class="record-table"><thead><tr><th>Chart Date</th><th>Marked Teeth</th><th>Created By</th><th>Actions</th></tr></thead><tbody>
<?php if (!$charts): ?><tr><td colspan="4" class="empty">No dental charts yet. Use Create Chart to record the first examination.</td></tr><?php endif; ?>
<?php foreach ($charts as $c): ?><tr><td data-label="Chart Date"><b><?= e(date('M d, Y', strtotime($c['chart_date']))) ?></b></td><td data-label="Marked Teeth"><?= (int)$c['marked_teeth'] ?> marked</td><td data-label="Created By"><?= e($c['creator_first'] . ' ' . $c['creator_last']) ?> <span class="muted">· <?= e(ucfirst($c['created_role'])) ?></span></td><td data-label="Actions"><div class="row-actions"><a class="btn btn-secondary btn-sm" href="chart_editor.php?id=<?= (int)$c['id'] ?>">Edit</a><a class="btn btn-primary btn-sm" href="chart_view.php?id=<?= (int)$c['id'] ?>">View</a><?= $deleteForm((int)$c['id'], 'patient') ?></div></td></tr><?php endforeach; ?>
</tbody></table></div></section>
<?php else: ?>
<div class="page-head compact-head"><div><div class="eyebrow">DENTAL EXAMINATION RECORD</div><h1>Dental Charts</h1><div class="muted">Patient list with their latest dental chart. Click a patient to see all of their charts and details.</div></div><a class="btn btn-primary" href="chart_editor.php"><i class="fa-solid fa-file-circle-plus"></i> Create Chart</a></div>
<?php if (isset($_GET['saved'])): ?><div class="flash success">Chart saved successfully.</div><?php endif; ?><?php if (isset($_GET['deleted'])): ?><div class="flash success">Chart deleted.</div><?php endif; ?>
<section class="card record-card">
<form class="directory-search" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Search patient name or email"><button class="btn btn-primary">Search</button><?php if ($q !== ''): ?><a class="btn btn-secondary" href="dental_charts.php">Clear</a><?php endif; ?></form>
<div class="table-wrap"><table class="record-table"><thead><tr><th>Patient List</th><th>Charts</th><th>Latest Chart</th><th>Actions</th></tr></thead><tbody>
<?php if (!$rows): ?><tr><td colspan="4" class="empty">No patients matched your search.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): $latest = (int)$r['latest_id']; ?><tr>
<td data-label="Patient"><a class="plain-link" href="dental_charts.php?patient_id=<?= (int)$r['id'] ?>"><b><?= e($r['first_name'] . ' ' . $r['last_name']) ?></b></a><br><small class="muted"><?= e($r['email']) ?></small></td>
<td data-label="Charts"><?= (int)$r['chart_count'] ?></td>
<td data-label="Latest Chart"><?= $r['latest_date'] ? e(date('M d, Y', strtotime($r['latest_date']))) : '<span class="muted">No chart yet</span>' ?></td>
<td data-label="Actions"><div class="row-actions"><?php if ($latest): ?><a class="btn btn-secondary btn-sm" href="chart_editor.php?id=<?= $latest ?>">Edit</a><a class="btn btn-primary btn-sm" href="chart_view.php?id=<?= $latest ?>">View</a><?= $deleteForm($latest, 'list') ?><?php else: ?><a class="btn btn-secondary btn-sm" href="chart_editor.php?patient_id=<?= (int)$r['id'] ?>">Create Chart</a><?php endif; ?></div></td>
</tr><?php endforeach; ?>
</tbody></table></div></section>
<?php endif; ?>
<?php ui_footer(); ?>
