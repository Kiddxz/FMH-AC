<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
require_once __DIR__ . '/../includes/exam_record.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare('SELECT c.*, u.first_name, u.last_name, u.email FROM dental_charts c JOIN users u ON u.id = c.patient_id WHERE c.id = ? LIMIT 1'); $stmt->bind_param('i', $id); $stmt->execute(); $chart = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$chart) { header('Location: dental_charts.php'); exit; }
$chart['chart_date'] = $chart['chart_date'] ?: date('Y-m-d', strtotime($chart['created_at']));
$teeth = [];
$stmt = $conn->prepare('SELECT tooth_number, condition_name FROM dental_chart_teeth WHERE chart_id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $t) $teeth[$t['tooth_number']] = $t['condition_name']; $stmt->close();
$data = json_decode($chart['examination_data'] ?: '{}', true); if (!is_array($data)) $data = [];
$record = exam_record_normalize($data);
$back = 'dental_charts.php?patient_id=' . (int)$chart['patient_id'];
require '../includes/ui.php'; ui_header('View Chart', 'dentist'); ui_sidebar('dentist', 'chart_view.php');
?>
<div class="page-head compact-head"><div><div class="eyebrow">DENTAL EXAMINATION RECORD</div><h1><?=e($chart['first_name'].' '.$chart['last_name'])?></h1><div class="muted">Chart dated <?=e(date('M d, Y', strtotime($chart['chart_date'])))?>. Print shows exactly this record.</div></div><a class="btn btn-secondary" href="chart_editor.php?id=<?=$id?>"><i class="fa-solid fa-pen"></i> Edit Chart</a></div>
<?php exam_record_print($chart, $record, $teeth); ?>
<div class="paper-actions"><a class="btn btn-secondary" href="<?=e($back)?>">Cancel</a><button type="button" class="btn btn-primary" onclick="printChart()"><i class="fa-solid fa-print"></i> Print</button></div>
<script>
function printChart(){
    const originalTitle = document.title;
    document.title = 'SupremeDent-Dental-Chart-<?=(int)$chart['id']?>';
    window.print();
    setTimeout(() => { document.title = originalTitle; }, 1000);
}
</script>
<?php ui_footer(); ?>
