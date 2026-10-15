<?php
/**
 * Appointment "Patient Details" page shared by the assistant and dentist portals.
 * Shows the patient name, service, reason and the teeth the patient marked on their
 * own dental chart, as a numbered chart and on the 3D model.
 * Expects $detailRole ('assistant'|'dentist') and a signed-in $conn session.
 */
$detailRole = $detailRole === 'dentist' ? 'dentist' : 'assistant';
require_once __DIR__ . '/appointment_helpers.php';
$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$detailMsg = ''; $detailType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) { $detailMsg = 'Your session expired. Refresh and try again.'; $detailType = 'error'; }
    elseif (($_POST['action'] ?? '') === 'cancel') {
        [$ok, $text] = sd_cancel_appointment($conn, $id, $uid, $detailRole, (string)($_POST['cancel_reason'] ?? ''));
        $detailMsg = $ok ? $text . ' The patient was notified.' : $text; $detailType = $ok ? 'success' : 'error';
    } elseif (($_POST['action'] ?? '') === 'complete' && $detailRole === 'dentist') {
        $st = $conn->prepare("UPDATE appointments SET status='completed', dentist_id=COALESCE(dentist_id, ?) WHERE id=? AND (dentist_id=? OR dentist_id IS NULL) AND status='approved'");
        $st->bind_param('iii', $uid, $id, $uid); $st->execute(); $ok = $st->affected_rows === 1; $st->close();
        $detailMsg = $ok ? 'Appointment marked completed.' : 'Only your approved (or unassigned) appointments can be completed.'; $detailType = $ok ? 'success' : 'error';
    }
}
$st = $conn->prepare("SELECT a.*, u.first_name, u.last_name, u.email, p.phone, p.birthday, p.gender, d.first_name dentist_first, d.last_name dentist_last
    FROM appointments a JOIN users u ON u.id = a.patient_id LEFT JOIN patient_profiles p ON p.user_id = a.patient_id LEFT JOIN users d ON d.id = a.dentist_id
    WHERE a.id = ? LIMIT 1");
$st->bind_param('i', $id);
$st->execute();
$appt = $st->get_result()->fetch_assoc();
$st->close();
// Assistants and dentists both see every booking on their calendars (revision 2).
$allowed = (bool)$appt;
$backUrl = 'appointments.php';
if (!$allowed) {
    require __DIR__ . '/ui.php';
    ui_header('Patient Details', $detailRole);
    ui_sidebar($detailRole, 'appointment_view.php');
    echo '<div class="page-head compact-head"><div><h1>Appointment not found</h1><div class="muted">It may have been removed or it is not assigned to you.</div></div><a class="btn btn-secondary" href="' . e($backUrl) . '">Back to Calendar</a></div>';
    ui_footer();
    return;
}
// Teeth the patient clicked while booking this appointment; older bookings fall back to the patient's chart marks.
$marks = [];
foreach (sd_clean_teeth((string)($appt['marked_teeth'] ?? '')) as $t) $marks[$t] = 'Marked';
if (!$marks && $appt['marked_teeth'] === null) {
    $st = $conn->prepare("SELECT tooth_number, concern_type FROM patient_tooth_reports WHERE patient_id = ? AND concern_type IN ('Marked','Healthy','Caries','Filled','Missing','Fractured') ORDER BY updated_at, id");
    $st->bind_param('i', $appt['patient_id']);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $m) $marks[$m['tooth_number']] = $m['concern_type'];
    $st->close();
}
$age = '';
if (!empty($appt['birthday']) && strtotime($appt['birthday'])) $age = (new DateTime($appt['birthday']))->diff(new DateTime('today'))->y;
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'rejected' => 'Declined', 'cancelled' => 'Cancelled'];
$permanent = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28','48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
$patientName = trim($appt['first_name'] . ' ' . $appt['last_name']);
require __DIR__ . '/ui.php';
ui_header('Patient Details', $detailRole);
ui_sidebar($detailRole, 'appointment_view.php');
?>
<div class="page-head compact-head"><div><div class="eyebrow">APPOINTMENT</div><h1>Patient Details</h1></div><a class="btn btn-secondary" href="<?= e($backUrl) ?>"><i class="fa-solid fa-arrow-left"></i> Back to Appointments</a></div>
<?php if ($detailMsg) flash($detailType, $detailMsg); ?>
<section class="card appt-detail-card">
    <div class="appt-detail-top">
        <div class="appt-patient-name"><span class="patient-initial large"><?= e(strtoupper(substr($appt['first_name'], 0, 1))) ?></span><div><h2><?= e($patientName) ?></h2><p class="muted">Patient ID #<?= (int)$appt['patient_id'] ?> · <?= e($appt['email']) ?></p></div></div>
        <div class="appt-meta">
            <div><small>Schedule</small><b><?= e(date('M d, Y', strtotime($appt['appointment_date']))) ?> · <?= e(date('h:i A', strtotime($appt['appointment_time']))) ?></b></div>
            <div><small>Status</small><b><span class="status-pill <?= e($appt['status']) ?>"><?= e($statusLabels[$appt['status']] ?? ucfirst($appt['status'])) ?></span></b></div>
            <div><small>Dentist</small><b><?= $appt['dentist_first'] ? e($appt['dentist_first'] . ' ' . $appt['dentist_last']) : 'Not assigned' ?></b></div>
            <?php if ($age !== '' || $appt['gender'] || $appt['phone']): ?><div><small>Patient</small><b><?= e(implode(' · ', array_filter([$age !== '' ? $age . ' yrs' : '', $appt['gender'], $appt['phone']]))) ?></b></div><?php endif; ?>
        </div>
    </div>
    <div class="chart-split appt-detail-split">
        <div class="chart-split-left">
            <div class="dental-arches compact-arches readonly">
                <div class="arch-label">Upper Arch</div>
                <div class="tooth-arch"><?php foreach (array_slice($permanent, 0, 16) as $tooth): ?><div class="chart-tooth" data-mark="<?= e($marks[$tooth] ?? '') ?>"><span class="tooth-crown"></span><b><?= $tooth ?></b></div><?php endforeach; ?></div>
                <div class="tooth-arch lower"><?php foreach (array_slice($permanent, 16) as $tooth): ?><div class="chart-tooth" data-mark="<?= e($marks[$tooth] ?? '') ?>"><span class="tooth-crown"></span><b><?= $tooth ?></b></div><?php endforeach; ?></div>
                <div class="arch-label">Lower Arch</div>
            </div>
            <div class="marked-summary"><span class="legend-chip"><i class="dot marked"></i> Clicked by patient</span><span class="muted"><?= $marks ? 'Teeth: ' . e(implode(', ', array_keys($marks))) : 'The patient did not mark any teeth.' ?></span></div>
            <div class="appt-info-block"><small>Service</small><p><?= e($appt['appointment_type']) ?></p></div>
            <div class="appt-info-block"><small>Reason</small><p><?= nl2br(e($appt['reason'] ?: 'No reason provided.')) ?></p></div>
        </div>
        <div class="model-box">
            <div class="model-box-head"><span><i class="fa-solid fa-cube"></i> 3D Odontogram</span><button type="button" class="model-reset" id="modelReset"><i class="fa-solid fa-arrows-rotate"></i> Reset view</button></div>
            <div class="model-stage tall" id="apptModel"><span>Loading dental model...</span></div>
            <small>Teeth the patient clicked when booking are shown in blue. Drag to rotate.</small>
        </div>
    </div>
    <?php $isActive = in_array($appt['status'], ['pending', 'approved'], true); if ($appt['status'] === 'cancelled'): ?><div class="appt-info-block"><small>Cancelled</small><p><?= e($appt['cancel_reason'] ?: 'No reason given.') ?></p></div><?php endif; ?>
    <?php if ($isActive): ?><form method="post" class="appt-detail-actions appt-cancel-inline" onsubmit="return confirm('Cancel this appointment? The patient will be notified.')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input name="cancel_reason" maxlength="255" placeholder="Reason for cancelling (sent to the patient)"><button class="btn btn-danger">Cancel Appointment</button><?php if ($detailRole === 'dentist' && $appt['status'] === 'approved' && (empty($appt['dentist_id']) || (int)$appt['dentist_id'] === $uid)): ?><button class="btn btn-secondary" formaction="" name="action" value="complete" onclick="this.form.onsubmit=null">Mark Completed</button><?php endif; ?></form><?php endif; ?>
    <?php if ($detailRole === 'dentist'): ?><div class="appt-detail-actions"><a class="btn btn-secondary" href="dental_charts.php?patient_id=<?= (int)$appt['patient_id'] ?>"><i class="fa-solid fa-teeth"></i> Dental Charts</a><a class="btn btn-primary" href="chart_editor.php?patient_id=<?= (int)$appt['patient_id'] ?>"><i class="fa-solid fa-file-circle-plus"></i> Create Chart</a></div><?php else: ?><div class="appt-detail-actions"><a class="btn btn-primary" href="chart_editor.php?patient_id=<?= (int)$appt['patient_id'] ?>"><i class="fa-solid fa-file-circle-plus"></i> Create Chart</a></div><?php endif; ?>
</section>
<script type="importmap">{"imports":{"three":"../assets/js/three.module.js"}}</script>
<script type="module">
import { createToothViewer } from '../assets/js/tooth_viewer.js';
const marks = <?= json_encode((object)$marks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const colors = { Marked: 0x2676db, Healthy: 0x3ca878, Caries: 0xdf5656, Filled: 0x3b82c4, Missing: 0x64748b, Fractured: 0xd69325 };
const viewer = createToothViewer(document.getElementById('apptModel'), { colorFor: id => colors[marks[id]] ?? null, interactive: false });
document.getElementById('modelReset').addEventListener('click', () => viewer.reset());
</script>
<?php ui_footer(); ?>
