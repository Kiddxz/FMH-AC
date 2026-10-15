<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);

$userId = (int) $_SESSION["user_id"];
$firstName = $_SESSION["first_name"] ?? "Patient";

require_once '../includes/patient_record_visibility.php';
[$hidingEnabled,$hideWarning]=patient_record_visibility($conn,$userId);
$sql='SELECT d.id,d.record_date,d.procedure_name,d.diagnosis,d.dentist_name,d.notes FROM dental_records d';
if($hidingEnabled)$sql.=' LEFT JOIN patient_hidden_records h ON h.record_id=d.id AND h.patient_id=? WHERE d.patient_id=? AND h.record_id IS NULL';
else $sql.=' WHERE d.patient_id=?';
$sql.=' ORDER BY d.record_date DESC,d.id DESC';
$stmt=$conn->prepare($sql);if($hidingEnabled)$stmt->bind_param('ii',$userId,$userId);else $stmt->bind_param('i',$userId);
$stmt->execute();$records=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);$stmt->close();
$latestDate=$records?date('M d, Y',strtotime($records[0]['record_date'])):'None';
$listTitle='Treatment Records';
$listIntro='';
$emptyText='No treatment history to display.';
$notesLabel='Treatment Notes';
require '../includes/ui.php';
ui_header('Treatment History', 'patient');
ui_sidebar('patient', 'treatment_history.php');
?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">TREATMENT HISTORY</div>
        <h1>My Treatment History</h1>
        <div class="muted"></div>
    </div>
</div>
<section class="stat-row">
    <div class="stat"><span>Total Treatments</span><b><?= count($records) ?></b></div>
    <div class="stat"><span>Latest Treatment</span><b><?= e($latestDate) ?></b></div>
    <div class="stat"><span>Patient</span><b><?= e($firstName) ?></b></div>
</section>
<?php require '../includes/patient_record_list.php'; ?>
<?php ui_footer(); ?>
