<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
require_once __DIR__ . '/../includes/appointment_helpers.php';


$uid = (int) $_SESSION['user_id'];
$msg = '';
$type = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $msg = 'Your session expired. Refresh and try again.';
        $type = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'complete') {
            $id = (int) ($_POST['appointment_id'] ?? 0);
            // Unassigned bookings are taken by the dentist who completes them.
            $st = $conn->prepare("UPDATE appointments SET status='completed', dentist_id=COALESCE(dentist_id, ?) WHERE id=? AND (dentist_id=? OR dentist_id IS NULL) AND status='approved'");
            $st->bind_param('iii', $uid, $id, $uid);
            $st->execute();
            $changed = $st->affected_rows;
            $st->close();
            $msg = $changed ? 'Appointment marked completed.' : 'Only your approved (or unassigned) appointments can be completed.';
            $type = $changed ? 'success' : 'error';
        } elseif ($action === 'cancel') {
            [$ok, $text] = sd_cancel_appointment($conn, (int) ($_POST['appointment_id'] ?? 0), $uid, 'dentist', (string) ($_POST['cancel_reason'] ?? ''));
            $msg = $ok ? $text . ' The patient was notified.' : $text;
            $type = $ok ? 'success' : 'error';
        } elseif ($action === 'block') {
            $date = $_POST['unavailable_date'] ?? '';
            $allDay = isset($_POST['all_day']) ? 1 : 0;
            $start = $allDay ? null : ($_POST['start_time'] ?? null);
            $end = $allDay ? null : ($_POST['end_time'] ?? null);
            $reason = trim($_POST['reason'] ?? 'Unavailable');
            if (!clinic_open_day($date) || (!$allDay && (!$start || !$end || $start >= $end))) {
                $msg = 'Choose a valid Monday–Saturday date and time. The clinic is closed on Sundays.';
                $type = 'error';
            } else {
                $st = $conn->prepare("INSERT INTO provider_unavailability(user_id,unavailable_date,start_time,end_time,all_day,reason,created_by) VALUES(?,?,?,?,?,?,?)");
                $st->bind_param('isssisi', $uid, $date, $start, $end, $allDay, $reason, $uid);
                $st->execute();
                $st->close();
                $msg = 'Unavailable schedule added to your calendar.';
            }
        } elseif ($action === 'unblock') {
            $id = (int) ($_POST['block_id'] ?? 0);
            $st = $conn->prepare('DELETE FROM provider_unavailability WHERE id=? AND user_id=?');
            $st->bind_param('ii', $id, $uid);
            $st->execute();
            $st->close();
            $msg = 'Unavailable mark removed.';
        }
    }
}
$events = [];
// The calendar shows every booking so the dentist can see who is scheduled at each date and time.
$st = $conn->prepare("SELECT a.id,a.patient_id,a.dentist_id,a.appointment_date,a.appointment_time,a.appointment_type,a.reason,a.marked_teeth,a.status,a.cancel_reason,u.first_name,u.last_name,u.email,d.first_name dentist_first,d.last_name dentist_last FROM appointments a JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id ORDER BY a.appointment_date,a.appointment_time");
$st->execute();
$r = $st->get_result();
$allAppointments = [];
while ($x = $r->fetch_assoc()) {
    $allAppointments[] = $x;
    if (in_array($x['status'], ['pending', 'approved', 'completed'], true)) $events[] = $x;
}
$st->close();
// Appointment requests list: upcoming bookings first, then the most recent past ones.
$todayKey = date('Y-m-d');
$requests = array_values(array_filter($allAppointments, static fn($a) => $a['appointment_date'] >= $todayKey));
$past = array_reverse(array_values(array_filter($allAppointments, static fn($a) => $a['appointment_date'] < $todayKey)));
$requests = array_merge($requests, array_slice($past, 0, 20));
$viewData = [];
foreach ($allAppointments as $x) $viewData[(int) $x['id']] = sd_appointment_view_data($x);
$blocks = [];
$st = $conn->prepare("SELECT id,unavailable_date,start_time,end_time,all_day,reason FROM provider_unavailability WHERE user_id=? AND unavailable_date>=DATE_SUB(CURDATE(),INTERVAL 60 DAY) ORDER BY unavailable_date,start_time");
$st->bind_param('i', $uid);
$st->execute();
$r = $st->get_result();
while ($x = $r->fetch_assoc())
    $blocks[] = $x;
$st->close();
require '../includes/ui.php';
ui_header('Appointments', 'dentist');
ui_sidebar('dentist', 'appointments.php'); ?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">DENTIST APPOINTMENTS</div>
        <h1>Appointments</h1>
        <div class="muted">Patient bookings are approved automatically. Click a date to see who is scheduled, or use View for the booking details.</div>
    </div>
</div><?php if ($msg)
    flash($type, $msg); ?>
<?php $calendarRole = 'dentist';
$calendarEvents = $events;
$calendarBlocks = $blocks;
require '../includes/appointment_calendar.php'; ?>
<section class="card requests-card" data-live-region="requests"><div class="card-heading"><div><h2>Appointment Requests</h2><p class="muted">Bookings made by patients. Your own patients are marked; unassigned bookings can be taken by any dentist.</p></div><span class="badge"><?= count($requests) ?> shown</span></div><div class="table-wrap"><table class="table"><thead><tr><th>Patient</th><th>Schedule</th><th>Service / Reason</th><th>Status</th><th>Dentist</th><th>Action</th></tr></thead><tbody><?php if (!$requests): ?><tr><td colspan="6" class="empty">No appointments yet.</td></tr><?php endif; ?><?php foreach ($requests as $a): ?><tr><td><b><?= e($a['first_name'] . ' ' . $a['last_name']) ?></b><br><span class="muted">Patient #<?= (int) $a['patient_id'] ?></span></td><td><?= e(date('M d, Y', strtotime($a['appointment_date']))) ?><br><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></td><td><b><?= e($a['appointment_type']) ?></b><br><span class="muted"><?= e($a['reason'] ?: 'No reason provided') ?></span></td><td><span class="status-pill <?= e($a['status']) ?>"><?= e($a['status'] === 'rejected' ? 'Declined' : ucfirst($a['status'])) ?></span></td><td><?= (int) $a['dentist_id'] === $uid ? '<b>You</b>' : ($a['dentist_first'] ? e($a['dentist_first'] . ' ' . $a['dentist_last']) : '<span class="muted">Not assigned</span>') ?></td><td><button type="button" class="btn btn-primary btn-sm" data-view-appt="<?= (int) $a['id'] ?>">View</button></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require '../includes/appointment_view_modal.php'; ?>
<script>(()=>{const data=<?= json_encode((object) $viewData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;document.querySelectorAll('[data-view-appt]').forEach(b=>b.addEventListener('click',()=>openAppointmentView(data[b.dataset.viewAppt])));})();</script>
<details class="card tool-panel sd-unavailability-card"><summary><span><b>Mark Unavailable</b><small>Block a full day or a specific time period on your calendar.</small></span><i class="fa-solid fa-chevron-down"></i></summary>
<form method="post" class="form-grid compact"><input type="hidden" name="csrf_token"
                value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="block">
            <div class="field"><label>Date</label><input type="date" name="unavailable_date" min="<?= date('Y-m-d') ?>"
                    required></div>
            <div class="field checkbox-field"><label><input type="checkbox" name="all_day" value="1" checked
                        id="allDayToggle"> All day</label></div>
            <div class="field time-block"><label>Start</label><input type="time" name="start_time" value="09:00"></div>
            <div class="field time-block"><label>End</label><input type="time" name="end_time" value="17:00"></div>
            <div class="field full"><label>Reason</label><input name="reason" maxlength="255"
                    placeholder="e.g. Leave, training, personal appointment"></div>
            <div class="actions full"><button class="btn btn-primary">Save Unavailable Time</button></div>
        </form>
        <div class="unavailability-list"><?php foreach (array_slice($blocks, 0, 8) as $b): ?>
                <div class="unavailability-row">
                    <span><b><?= e(date('M d, Y', strtotime($b['unavailable_date']))) ?></b><small><?= $b['all_day'] ? 'All day' : e(date('h:i A', strtotime($b['start_time'])) . ' – ' . date('h:i A', strtotime($b['end_time']))) ?><?= $b['reason'] ? ' • ' . e($b['reason']) : '' ?></small></span>
                    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input
                            type="hidden" name="action" value="unblock"><input type="hidden" name="block_id"
                            value="<?= $b['id'] ?>"><button class="btn btn-danger">Remove</button></form>
                </div><?php endforeach; ?>
        </div>
</details>
<script>document.getElementById('allDayToggle').addEventListener('change', function () {document.querySelectorAll('.time-block').forEach(el => el.style.display = this.checked ? 'none' : '');}); document.getElementById('allDayToggle').dispatchEvent(new Event('change'));</script>
<?php ui_footer(); ?>