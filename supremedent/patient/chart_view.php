<?php
/** Read-only view of a dental examination chart the dentist shared with this patient. */
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);
require_once __DIR__ . '/../includes/exam_record.php';

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
// Only the patient's own charts, and only after a dentist confirmed them (assistant drafts stay internal).
$stmt = $conn->prepare('SELECT c.*, u.first_name, u.last_name, u.email FROM dental_charts c JOIN users u ON u.id = c.patient_id WHERE c.id = ? AND c.patient_id = ? AND c.dentist_id IS NOT NULL LIMIT 1');
$stmt->bind_param('ii', $id, $uid);
$stmt->execute();
$chart = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$chart) { header('Location: dental_records.php'); exit; }
$chart['chart_date'] = $chart['chart_date'] ?: date('Y-m-d', strtotime($chart['created_at']));
$teeth = [];
$stmt = $conn->prepare('SELECT tooth_number, condition_name FROM dental_chart_teeth WHERE chart_id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $t) $teeth[$t['tooth_number']] = $t['condition_name'];
$stmt->close();
$data = json_decode($chart['examination_data'] ?: '{}', true);
$record = exam_record_normalize(is_array($data) ? $data : []);
require '../includes/ui.php';
ui_header('My Dental Chart', 'patient');
ui_sidebar('patient', 'dental_records.php');
?>
<div class="page-head compact-head"><div><div class="eyebrow">DENTAL EXAMINATION RECORD</div><h1>My Dental Chart</h1><div class="muted">Chart dated <?= e(date('M d, Y', strtotime($chart['chart_date']))) ?>, recorded by your dentist.</div></div><a class="btn btn-secondary" href="dental_records.php"><i class="fa-solid fa-arrow-left"></i> Back to Dental Records</a></div>
<?php exam_record_print($chart, $record, $teeth); ?>
<div class="paper-actions"><button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print / Save as PDF</button></div>
<?php ui_footer(); ?>
