<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);

$uid = (int) $_SESSION['user_id'];
require_once '../includes/patient_record_visibility.php';
[$hidingEnabled,$hideWarning]=patient_record_visibility($conn,$uid);
$sql='SELECT d.id,d.record_date,d.procedure_name,d.diagnosis,d.dentist_name,d.notes FROM dental_records d';
if($hidingEnabled)$sql.=' LEFT JOIN patient_hidden_records h ON h.record_id=d.id AND h.patient_id=? WHERE d.patient_id=? AND h.record_id IS NULL';
else $sql.=' WHERE d.patient_id=?';
$sql.=' ORDER BY d.record_date DESC,d.id DESC';
$st=$conn->prepare($sql);if($hidingEnabled)$st->bind_param('ii',$uid,$uid);else $st->bind_param('i',$uid);
$st->execute();$records=$st->get_result()->fetch_all(MYSQLI_ASSOC);$st->close();
$latest=$records?date('M d, Y',strtotime($records[0]['record_date'])):'None';
$listTitle='Dental Record History';
$listIntro='';
$emptyText='No dental records to display.';
$notesLabel='Notes';
require '../includes/ui.php';
ui_header('Dental Records', 'patient');
ui_sidebar('patient', 'dental_records.php'); ?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">DENTAL RECORDS</div>
        <h1>My Dental Records</h1>
        <div class="muted"></div>
    </div>
</div>
<section class="stat-row">
    <div class="stat"><span>Dental Records</span><b><?= count($records) ?></b></div>
    <div class="stat"><span>Latest Record</span><b><?= e($latest) ?></b></div>
    <div class="stat"><span>Patient</span><b><?= e($_SESSION['first_name'] ?? 'Patient') ?></b></div>
</section>
<?php require '../includes/patient_record_list.php'; ?>
<?php ui_footer(); ?>
