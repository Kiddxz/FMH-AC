<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);
require_once __DIR__ . '/../includes/appointment_helpers.php';

$uid = (int) $_SESSION['user_id'];
$msg = '';
$type = '';
$profileComplete = patient_profile_is_complete($conn, $uid);
$slotConfig = appointment_slot_settings($conn);
$leadSeconds = $slotConfig['lead_hours'] * 3600;
// One patient per time slot: once a time is booked it is no longer offered to anyone else.
$capacity = 1;

$slots = [];
$startTs = strtotime('2000-01-01 ' . $slotConfig['start']);
$endTs = strtotime('2000-01-01 ' . $slotConfig['end']);
for ($ts = $startTs; $ts < $endTs; $ts += $slotConfig['minutes'] * 60) {
    $slots[] = date('H:i', $ts);
}
if (!$slots)
    $slots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'];

$serviceNames = [];
$r = $conn->query("SELECT name FROM services WHERE is_active=1 ORDER BY id");
if ($r) {
    while ($s = $r->fetch_assoc())
        $serviceNames[] = $s['name'];
}
if (!$serviceNames)
    $serviceNames = ['Dental Checkup', 'Dental Cleaning', 'Dental Restoration', 'Tooth Extraction', 'Consultation'];
$preselected = trim($_GET['service'] ?? '');
if (!in_array($preselected, $serviceNames, true))
    $preselected = '';

function valid_slot_request(string $date, string $time, array $slots, int $leadSeconds): bool
{
    if (!clinic_open_day($date) || !in_array(substr($time, 0, 5), $slots, true))
        return false;
    $ts = strtotime($date . ' ' . substr($time, 0, 5));
    return $ts !== false && $ts >= (time() + $leadSeconds);
}
function slot_is_full(mysqli $conn, string $date, string $time, int $capacity, int $excludeId = 0): bool
{
    $st = $conn->prepare("SELECT COUNT(*) c FROM appointments WHERE appointment_date=? AND appointment_time=? AND status IN ('pending','approved') AND id<>?");
    $st->bind_param('ssi', $date, $time, $excludeId);
    $st->execute();
    $count = (int) ($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    return $count >= $capacity;
}
/** Serialises check-then-book for one date/time so two patients submitting together cannot both get it. */
function slot_lock(mysqli $conn, string $date, string $time): bool
{
    $name = 'sd_slot_' . $date . '_' . $time;
    $st = $conn->prepare('SELECT GET_LOCK(?, 5) l');
    $st->bind_param('s', $name);
    $st->execute();
    $ok = (int) ($st->get_result()->fetch_assoc()['l'] ?? 0) === 1;
    $st->close();
    return $ok;
}
function slot_unlock(mysqli $conn, string $date, string $time): void
{
    $name = 'sd_slot_' . $date . '_' . $time;
    $st = $conn->prepare('SELECT RELEASE_LOCK(?)');
    $st->bind_param('s', $name);
    $st->execute();
    $st->close();
}
function booked_slots(mysqli $conn): array
{
    $map = [];
    $from = date('Y-m-d');
    $to = date('Y-m-d', strtotime('+120 days'));
    $st = $conn->prepare("SELECT appointment_date,DATE_FORMAT(appointment_time,'%H:%i') slot_time,COUNT(*) c FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status IN ('pending','approved') GROUP BY appointment_date,appointment_time");
    $st->bind_param('ss', $from, $to);
    $st->execute();
    $res = $st->get_result();
    while ($row = $res->fetch_assoc()) {
        $map[$row['appointment_date']][$row['slot_time']] = (int) $row['c'];
    }
    $st->close();
    return $map;
}
function json_out(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

// Live availability for the booking pop-up, so times taken by other patients turn gray without a reload.
if (($_GET['availability'] ?? '') === '1') {
    json_out(['availability' => (object) booked_slots($conn), 'now' => time() * 1000]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $ajax = ($_POST['ajax'] ?? '') === '1';
    $id = (int) ($_POST['id'] ?? 0);
    $fail = static function (string $text) use ($ajax, &$msg, &$type) {
        if ($ajax) json_out(['ok' => false, 'message' => $text], 422);
        $msg = $text;
        $type = 'error';
    };
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $fail('Your session expired. Refresh the page and try again.');
    } elseif ($action === 'create') {
        $d = trim($_POST['appointment_date'] ?? '');
        $t = substr(trim($_POST['appointment_time'] ?? ''), 0, 5);
        $service = trim($_POST['appointment_type'] ?? '');
        $reason = trim($_POST['reason'] ?? '');
        $teeth = sd_clean_teeth((string) ($_POST['marked_teeth'] ?? '[]'));
        if (!$profileComplete) {
            $fail('Please complete your patient profile before booking an appointment.');
        } elseif (!in_array($service, $serviceNames, true)) {
            $fail('Please select a valid clinic service.');
        } elseif (mb_strlen($reason) > 1000) {
            $fail('Please keep the reason under 1000 characters.');
        } elseif (!valid_slot_request($d, $t, $slots, $leadSeconds)) {
            $fail('Please choose an available schedule at least ' . $slotConfig['lead_hours'] . ' hours from now.');
        } elseif (!slot_lock($conn, $d, $t)) {
            $fail('Another patient is booking this time right now. Please try again in a moment.');
        } elseif (slot_is_full($conn, $d, $t, $capacity)) {
            slot_unlock($conn, $d, $t);
            $fail('Sorry, that time was just booked by another patient. Please choose another time slot.');
        } else {
            // Patient bookings are approved automatically and given a free dentist when one is available.
            $dentistId = sd_pick_dentist($conn, $d, $t);
            $teethJson = json_encode($teeth);
            $st = $conn->prepare("INSERT INTO appointments(patient_id,dentist_id,appointment_date,appointment_time,appointment_type,reason,marked_teeth,status) VALUES(?,?,?,?,?,?,?,'approved')");
            $st->bind_param('iisssss', $uid, $dentistId, $d, $t, $service, $reason, $teethJson);
            $booked = $st->execute();
            $newId = (int) $st->insert_id;
            $st->close();
            slot_unlock($conn, $d, $t);
            if (!$booked) {
                $fail('Unable to book the appointment. Please try again.');
            } else {
                // The latest marked teeth also stay on the patient's chart for the dentist.
                if ($teeth) {
                    $conn->begin_transaction();
                    try {
                        $del = $conn->prepare("DELETE FROM patient_tooth_reports WHERE patient_id=? AND concern_type IN ('Healthy','Caries','Filled','Missing','Fractured','Marked')");
                        $del->bind_param('i', $uid);
                        $del->execute();
                        $del->close();
                        $ins = $conn->prepare("INSERT INTO patient_tooth_reports(patient_id,tooth_number,concern_type,severity,notes,status) VALUES(?,?,'Marked',1,?,'new')");
                        $note = 'Marked when booking appointment #' . $newId;
                        foreach ($teeth as $tooth) {
                            $ins->bind_param('iss', $uid, $tooth, $note);
                            $ins->execute();
                        }
                        $ins->close();
                        $conn->commit();
                    } catch (Throwable $e) {
                        $conn->rollback();
                        error_log('[SupremeDent booking teeth] ' . $e->getMessage());
                    }
                }
                $appt = sd_load_appointment($conn, $newId);
                $when = date('M d, Y', strtotime($d)) . ' at ' . date('h:i A', strtotime($t));
                sd_notify_patient($conn, $uid, 'Appointment Confirmed', 'Your ' . $service . ' appointment on ' . $when . ' is booked and confirmed.' . ($teeth ? ' Marked teeth: ' . implode(', ', $teeth) . '.' : ''), 'appointment', $newId);
                $patientName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
                sd_notify_staff($conn, ['assistant'], $dentistId ? [$dentistId] : [], 'New appointment · ' . $patientName,
                    $patientName . ' booked ' . $service . ' on ' . $when . '.' . ($reason !== '' ? ' Concern: ' . $reason : ''), $newId, $uid, $uid);
                // With no free dentist the booking is still confirmed; every dentist is told so someone can take it.
                if (!$dentistId) sd_notify_staff($conn, ['dentist'], [], 'New appointment (no dentist assigned) · ' . $patientName, $patientName . ' booked ' . $service . ' on ' . $when . '.', $newId, $uid, $uid);
                log_activity($conn, $uid, 'Booked appointment', 'Appointments', 'Appointment #' . $newId . ' auto-approved');
                if ($ajax) json_out(['ok' => true, 'message' => 'Your appointment is confirmed. See you at the clinic!', 'appointment' => $appt ? sd_appointment_view_data($appt) : null]);
                header('Location: appointments.php?done=booked');
                exit;
            }
        }
    } elseif ($action === 'cancel' && $id) {
        [$ok, $text] = sd_cancel_appointment($conn, $id, $uid, 'patient', (string) ($_POST['cancel_reason'] ?? ''));
        if ($ok) {
            header('Location: appointments.php?done=cancelled');
            exit;
        }
        $fail($text);
    } elseif ($action === 'reschedule' && $id) {
        $st = $conn->prepare("SELECT * FROM appointments WHERE id=? AND patient_id=? LIMIT 1");
        $st->bind_param('ii', $id, $uid);
        $st->execute();
        $a = $st->get_result()->fetch_assoc();
        $st->close();
        $d = trim($_POST['appointment_date'] ?? '');
        $t = substr(trim($_POST['appointment_time'] ?? ''), 0, 5);
        if (!$a) {
            $fail('Appointment not found.');
        }
        // Bookings are approved automatically, so any active upcoming appointment can be moved.
        elseif (!in_array($a['status'], ['pending', 'approved'], true) || strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) < time()) {
            $fail('Only upcoming active appointments can be rescheduled.');
        } elseif (!valid_slot_request($d, $t, $slots, $leadSeconds)) {
            $fail('Choose a valid available schedule at least ' . $slotConfig['lead_hours'] . ' hours from now.');
        } elseif (!slot_lock($conn, $d, $t)) {
            $fail('Another patient is booking this time right now. Please try again in a moment.');
        } elseif (slot_is_full($conn, $d, $t, $capacity, $id)) {
            slot_unlock($conn, $d, $t);
            $fail('Sorry, that time was just booked by another patient. Please choose another time slot.');
        } else {
            // Keep the same dentist when they are free at the new time, otherwise give it to a free dentist.
            $oldDentist = (int) ($a['dentist_id'] ?? 0);
            $dentistId = ($oldDentist && sd_dentist_free($conn, $oldDentist, $d, $t, $id)) ? $oldDentist : sd_pick_dentist($conn, $d, $t, $id);
            $st = $conn->prepare("UPDATE appointments SET appointment_date=?,appointment_time=?,dentist_id=? WHERE id=? AND patient_id=? AND status IN ('pending','approved')");
            $st->bind_param('ssiii', $d, $t, $dentistId, $id, $uid);
            $st->execute();
            $moved = $st->affected_rows === 1;
            $st->close();
            slot_unlock($conn, $d, $t);
            if (!$moved) {
                $fail('This appointment was already updated. Refresh and try again.');
            } else {
                $newWhen = date('M d, Y', strtotime($d)) . ' at ' . date('h:i A', strtotime($t));
                $oldWhen = sd_appointment_when($a);
                sd_notify_patient($conn, $uid, 'Appointment Rescheduled', 'Your ' . $a['appointment_type'] . ' appointment was moved from ' . $oldWhen . ' to ' . $newWhen . '.', 'appointment', $id);
                $patientName = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
                sd_notify_staff($conn, ['assistant'], array_filter([$oldDentist, (int) $dentistId]), 'Appointment rescheduled · ' . $patientName,
                    $patientName . ' moved ' . $a['appointment_type'] . ' from ' . $oldWhen . ' to ' . $newWhen . '.', $id, $uid, $uid);
                log_activity($conn, $uid, 'Rescheduled appointment', 'Appointments', 'Appointment #' . $id);
                header('Location: appointments.php?done=rescheduled');
                exit;
            }
        }
    }
}
$done = $_GET['done'] ?? '';
if (!$msg && $done === 'booked') {
    $msg = 'Your appointment has been booked successfully.';
    $type = 'success';
}
if (!$msg && $done === 'rescheduled') {
    $msg = 'Your appointment was rescheduled.';
    $type = 'success';
}
if (!$msg && $done === 'cancelled') {
    $msg = 'Your appointment was cancelled. You can book a new schedule anytime.';
    $type = 'success';
}

// Availability map for the next 120 days.
$availability = booked_slots($conn);

$all = [];
$st = $conn->prepare("SELECT a.*, d.first_name dentist_first, d.last_name dentist_last, u.first_name, u.last_name FROM appointments a JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id WHERE a.patient_id=? ORDER BY a.appointment_date DESC,a.appointment_time DESC");
$st->bind_param('i', $uid);
$st->execute();
$res = $st->get_result();
while ($a = $res->fetch_assoc())
    $all[] = $a;
$st->close();
// Upcoming first (soonest at the top), then everything in the past.
$now = time();
usort($all, static function ($a, $b) use ($now) {
    $ta = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
    $tb = strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
    $fa = $ta >= $now && in_array($a['status'], ['pending', 'approved'], true);
    $fb = $tb >= $now && in_array($b['status'], ['pending', 'approved'], true);
    if ($fa !== $fb)
        return $fa ? -1 : 1;
    return $fa ? $ta <=> $tb : $tb <=> $ta;
});
$statusLabels = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Declined', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$viewData = [];
foreach ($all as $a)
    $viewData[(int) $a['id']] = sd_appointment_view_data($a);

require '../includes/ui.php';
ui_header('Appointments', 'patient');
ui_sidebar('patient', 'appointments.php');
?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">APPOINTMENTS</div>
        <h1>My Appointments</h1>
        <div class="muted">Book a visit, mark the teeth that concern you, and follow your schedule. Bookings are confirmed right away.</div>
    </div><button type="button" class="btn btn-primary" id="newAppointmentBtn"><i class="fa-solid fa-calendar-plus"></i>
        New Appointment</button>
</div>
<?php if ($msg)
    flash($type, $msg); ?>
<section class="card appointment-list-card" data-live-region="appointments">
    <?php if (!$all): ?>
        <div class="appointment-empty"><i class="fa-regular fa-calendar"></i>
            <p>No upcoming appointments.</p><span>Use <b>New Appointment</b> to book your first visit.</span>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="record-table appointment-table">
                <thead>
                    <tr>
                        <th>Date &amp; Time</th>
                        <th>Service</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all as $a):
                        $future = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) >= $now;
                        $active = in_array($a['status'], ['pending', 'approved'], true);
                        $canMove = $active && $future;
                        $canCancel = $active && $future; ?>
                        <tr class="<?= $future && $active ? '' : 'is-past' ?>">
                            <td data-label="Date &amp; Time">
                                <b><?= e(date('M d, Y', strtotime($a['appointment_date']))) ?></b><br><span
                                    class="muted"><?= e(date('h:i A', strtotime($a['appointment_time']))) ?></span></td>
                            <td data-label="Service"><?= e($a['appointment_type']) ?></td>
                            <td data-label="Reason"><?= e($a['reason'] ?: '—') ?></td>
                            <td data-label="Status"><span
                                    class="status-pill <?= e($a['status']) ?>"><?= e($statusLabels[$a['status']] ?? ucfirst($a['status'])) ?></span>
                            </td>
                            <td data-label="Actions"><div class="row-actions">
                                <button type="button" class="btn btn-secondary btn-sm" data-view-appt="<?= (int) $a['id'] ?>"><i class="fa-regular fa-eye"></i> View</button>
                                <?php if ($canMove): ?><button type="button" class="btn btn-secondary btn-sm"
                                        data-reschedule="<?= $a['id'] ?>" data-date="<?= e($a['appointment_date']) ?>"
                                        data-time="<?= e(substr($a['appointment_time'], 0, 5)) ?>"
                                        data-when="<?= e(date('M d, Y · h:i A', strtotime($a['appointment_date'] . ' ' . $a['appointment_time']))) ?>"><i
                                            class="fa-solid fa-clock-rotate-left"></i> Reschedule</button><?php endif; ?>
                                <?php if ($canCancel): ?><button type="button" class="btn btn-danger btn-sm" data-cancel-appt="<?= (int) $a['id'] ?>"
                                        data-when="<?= e(date('M d, Y · h:i A', strtotime($a['appointment_date'] . ' ' . $a['appointment_time']))) ?>"
                                        data-service="<?= e($a['appointment_type']) ?>"><i class="fa-solid fa-ban"></i> Cancel</button><?php endif; ?>
                            </div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<!-- Step 1: Select Schedule -->
<div class="sd-modal" id="bookingModal" role="dialog" aria-modal="true" aria-labelledby="bookingTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-wide schedule-dialog">
        <div class="schedule-head">
            <div>
                <h2 id="bookingTitle">Select Schedule</h2>
                <p class="muted" id="bookingIntro" hidden></p>
            </div>
            <div class="schedule-head-tools"><span class="cutoff-badge"><i class="fa-solid fa-clock"></i> <?= (int) $slotConfig['lead_hours'] ?>-hour booking cutoff</span><button type="button" class="sd-modal-close static" data-close-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>
        </div>
        <form method="post" id="bookingForm" class="booking-layout" novalidate><input type="hidden" name="csrf_token"
                value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" id="bookingAction" value="create"><input
                type="hidden" name="id" id="bookingId" value=""><input type="hidden" name="appointment_date"
                id="appointmentDate"><input type="hidden" name="appointment_time" id="appointmentTime">
            <div class="booking-calendar">
                <div class="calendar-head"><button type="button" class="calendar-nav" id="prevMonth"
                        aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong
                        id="calendarTitle"></strong><button type="button" class="calendar-nav" id="nextMonth"
                        aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div>
                <div class="calendar-week">
                    <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                </div>
                <div id="calendarGrid" class="calendar-grid"></div>
                <div class="calendar-legend"><span><i class="available"></i> Available</span><span><i class="full"></i>
                        Fully booked</span><span><i class="sunday"></i> Sunday closed</span><span><i class="closed"></i>
                        Unavailable</span></div>
            </div>
            <div class="slot-panel">
                <div>
                    <h3 id="selectedDateLabel">Select a date</h3>
                    <p class="muted">Then choose an available time slot.</p>
                </div>
                <div id="timeSlots" class="time-slots"><span class="slot-placeholder">Available times will appear here.</span></div>
                <div class="field-hint error" id="bookingError" role="alert"></div>
                <button class="btn btn-primary continue-btn" type="submit" id="bookingSubmit" disabled><span>Continue to Dental Details</span> <i class="fa-solid fa-arrow-right keep-icon"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Step 2: dental details with the 3D model, then Book Appointment -->
<div class="sd-modal" id="detailsModal" role="dialog" aria-modal="true" aria-labelledby="detailsTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-wide details-dialog">
        <button type="button" class="sd-modal-close" data-close-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <div class="details-layout">
            <div class="details-model">
                <div class="model-box">
                    <div class="model-box-head"><span><i class="fa-solid fa-cube"></i> 3D Dental Model</span><button type="button" class="model-reset" id="bookingModelReset" title="Reset view"><i class="fa-solid fa-arrows-rotate"></i> Reset view</button></div>
                    <div class="model-stage" id="bookingModel"><span>Loading dental model...</span></div>
                    <small id="markedTeethNote">Click a tooth to mark where you feel discomfort. Drag to rotate.</small>
                </div>
                <div class="details-model-actions"><button type="button" class="btn btn-secondary" id="clearTeeth">Clear All</button><button type="button" class="btn btn-primary" id="saveTeeth">Save Marked Teeth</button></div>
            </div>
            <div class="details-review">
                <span class="review-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                <h2 id="detailsTitle">Please review your appointment</h2>
                <dl class="review-summary"><dt>DATE</dt><dd id="reviewDate">—</dd><dt>TIME</dt><dd id="reviewTime">—</dd></dl>
                <div class="field"><label for="serviceSelect">Dental Service *</label><select id="serviceSelect" required>
                        <option value="">Select service</option><?php foreach ($serviceNames as $name): ?>
                            <option value="<?= e($name) ?>" <?= $preselected === $name ? 'selected' : '' ?>><?= e($name) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="field"><label for="reasonInput">Reason / Concern</label><textarea id="reasonInput" maxlength="1000" placeholder="Briefly describe your dental concern"></textarea></div>
                <div class="field-hint error" id="detailsError" role="alert"></div>
                <button type="button" class="btn btn-primary book-btn" id="bookAppointmentBtn">Book Appointment</button>
            </div>
        </div>
    </div>
</div>

<!-- Step 3: success -->
<div class="sd-modal" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-small success-dialog">
        <span class="success-icon"><i class="fa-solid fa-check"></i></span>
        <h2 id="successTitle">Appointment Booked Successfully!</h2>
        <p class="muted" id="successText">Your appointment is confirmed.</p>
        <dl class="confirm-list" id="successList"></dl>
        <div class="profile-dialog-actions"><button type="button" class="btn btn-primary" id="successDone">Done</button></div>
    </div>
</div>

<!-- Reschedule confirmation (pending requests only) -->
<div class="sd-modal" id="confirmModal" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-small">
        <span class="profile-dialog-icon"><i class="fa-solid fa-clipboard-check"></i></span>
        <h2 id="confirmTitle">Please review the new schedule</h2>
        <dl class="confirm-list" id="confirmList"></dl>
        <div class="profile-dialog-actions"><button type="button" class="btn btn-secondary" id="confirmEdit"><i class="fa-solid fa-pen"></i> Edit</button><button type="button" class="btn btn-primary" id="confirmSubmit"><i class="fa-solid fa-paper-plane"></i> Confirm</button></div>
    </div>
</div>

<!-- Cancel -->
<div class="sd-modal" id="cancelModal" role="dialog" aria-modal="true" aria-labelledby="cancelTitle" aria-hidden="true">
    <form method="post" class="sd-modal-dialog sd-modal-small cancel-dialog">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" id="cancelId">
        <span class="profile-dialog-icon danger"><i class="fa-solid fa-calendar-xmark"></i></span>
        <h2 id="cancelTitle">Cancel this appointment?</h2>
        <p class="muted" id="cancelText"></p>
        <div class="field"><label for="cancelReason">Reason (optional)</label><textarea name="cancel_reason" id="cancelReason" maxlength="255" placeholder="Let the clinic know why you are cancelling"></textarea></div>
        <div class="profile-dialog-actions"><button type="button" class="btn btn-secondary" data-close-modal>Keep Appointment</button><button type="submit" class="btn btn-danger">Cancel Appointment</button></div>
    </form>
</div>

<!-- View details -->
<div class="sd-modal" id="viewModal" role="dialog" aria-modal="true" aria-labelledby="viewTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-small view-dialog">
        <button type="button" class="sd-modal-close" data-close-modal aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <span class="profile-dialog-icon"><i class="fa-regular fa-calendar-check"></i></span>
        <h2 id="viewTitle">Appointment Details</h2>
        <dl class="confirm-list" id="viewList"></dl>
    </div>
</div>

<div class="profile-booking-modal" id="profileBookingModal" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="profileModalTitle">
    <div class="profile-booking-dialog"><button type="button" class="profile-dialog-close" aria-label="Close"
            onclick="closeProfileBooking()"><i class="fa-solid fa-xmark"></i></button><span
            class="profile-dialog-icon"><i class="fa-solid fa-user-check"></i></span>
        <h2 id="profileModalTitle">Please complete your profile to continue.</h2>
        <div class="profile-dialog-actions"><button type="button" class="btn btn-secondary"
                onclick="closeProfileBooking()">Not now</button><a class="btn btn-primary" href="profile.php">Complete
                Profile</a></div>
    </div>
</div>
<script>
    let availability = <?= json_encode((object) $availability, JSON_UNESCAPED_SLASHES) ?>;
    const slots = <?= json_encode($slots) ?>;
    const capacity = <?= $capacity ?>;
    const leadHours = <?= (int) $slotConfig['lead_hours'] ?>;
    const profileComplete = <?= $profileComplete ? 'true' : 'false' ?>;
    const appointmentData = <?= json_encode((object) $viewData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const serverNow = new Date(<?= time() * 1000 ?>);
    const maxDate = new Date(serverNow); maxDate.setDate(maxDate.getDate() + 90); maxDate.setHours(23, 59, 59, 999);
    let currentMonth = new Date(serverNow.getFullYear(), serverNow.getMonth(), 1);
    let selectedDate = '', mode = 'create', ownSlot = null;
    const pad = n => String(n).padStart(2, '0');
    const dateKey = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const $ = id => document.getElementById(id);
    const monthTitle = $('calendarTitle'), grid = $('calendarGrid'), slotBox = $('timeSlots'), dateInput = $('appointmentDate'), timeInput = $('appointmentTime'), submit = $('bookingSubmit'), bookingError = $('bookingError');
    function openModal(id) { const m = $(id); m.classList.add('is-open'); m.setAttribute('aria-hidden', 'false'); }
    function closeModal(id) { const m = $(id); m.classList.remove('is-open'); m.setAttribute('aria-hidden', 'true'); }
    function closeProfileBooking() { const m = $('profileBookingModal'); m.classList.remove('is-open'); m.setAttribute('aria-hidden', 'true') }
    document.querySelectorAll('[data-close-modal]').forEach(b => b.addEventListener('click', () => closeModal(b.closest('.sd-modal').id)));
    document.querySelectorAll('.sd-modal').forEach(m => m.addEventListener('click', e => { if (e.target === m && m.id !== 'successModal') closeModal(m.id) }));
    $('profileBookingModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeProfileBooking() });
    document.addEventListener('keydown', e => { if (e.key !== 'Escape') return; const open = [...document.querySelectorAll('.sd-modal.is-open')].pop(); if (open && open.id !== 'successModal') closeModal(open.id); closeProfileBooking(); });
    function slotDate(date, time) { const [y, m, d] = date.split('-').map(Number), [h, min] = time.split(':').map(Number); return new Date(y, m - 1, d, h, min, 0, 0) }
    function slotBlock(date, time) { if (slotDate(date, '00:00').getDay() === 0) return 'closed'; if (slotDate(date, time).getTime() < serverNow.getTime() + leadHours * 3600000) return 'cutoff'; let used = availability[date]?.[time] || 0; if (ownSlot && ownSlot.date === date && ownSlot.time === time) used--; return used >= capacity ? 'booked' : ''; }
    function slotAvailable(date, time) { return slotBlock(date, time) === ''; }
    function dayState(date) { const d = slotDate(date, '00:00'); if (d.getDay() === 0) return 'sunday'; if (d > maxDate) return 'closed'; const validSlots = slots.filter(t => slotDate(date, t).getTime() >= serverNow.getTime() + leadHours * 3600000); if (!validSlots.length) return 'closed'; return validSlots.some(t => slotAvailable(date, t)) ? 'available' : 'full' }
    function fmtTime(time) { return new Date('2000-01-01T' + time + ':00').toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) }
    function fmtDate(key) { return slotDate(key, '12:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) }
    function renderCalendar() {
        grid.innerHTML = ''; monthTitle.textContent = currentMonth.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
        const first = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1), days = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0).getDate();
        for (let i = 0; i < first.getDay(); i++) { const e = document.createElement('span'); e.className = 'calendar-day blank'; grid.appendChild(e) }
        for (let day = 1; day <= days; day++) {
            const d = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), day), key = dateKey(d), state = dayState(key), b = document.createElement('button');
            b.type = 'button'; b.className = 'calendar-day ' + state + (key === selectedDate ? ' selected' : '');
            b.title = { sunday: 'Clinic closed on Sundays', full: 'Fully booked', closed: 'Unavailable', available: 'Available' }[state];
            b.textContent = day; b.disabled = state !== 'available'; b.addEventListener('click', () => selectDate(key)); grid.appendChild(b)
        }
        $('prevMonth').disabled = currentMonth <= new Date(serverNow.getFullYear(), serverNow.getMonth(), 1);
        $('nextMonth').disabled = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1) > maxDate;
    }
    function resetSelection() { selectedDate = ''; dateInput.value = ''; timeInput.value = ''; submit.disabled = true; $('selectedDateLabel').textContent = 'Select a date'; slotBox.innerHTML = '<span class="slot-placeholder">Available times will appear here.</span>'; bookingError.textContent = ''; }
    function showSelection() { submit.disabled = !timeInput.value; }
    // Each time is a pill; booked or past-cutoff times are gray and cannot be picked.
    function renderSlots() {
        slotBox.replaceChildren();
        if (!selectedDate) { slotBox.innerHTML = '<span class="slot-placeholder">Available times will appear here.</span>'; return; }
        slots.forEach(time => {
            const block = slotBlock(selectedDate, time), b = document.createElement('button');
            b.type = 'button'; b.className = 'time-slot' + (block ? ' is-' + block : '') + (timeInput.value === time ? ' selected' : '');
            b.disabled = !!block;
            b.innerHTML = '<span>' + fmtTime(time) + '</span>' + (block ? '<small>' + (block === 'booked' ? 'Booked' : 'Unavailable') + '</small>' : '');
            b.setAttribute('aria-label', fmtTime(time) + (block === 'booked' ? ', already booked by another patient' : block ? ', unavailable' : ', available'));
            b.addEventListener('click', () => { timeInput.value = time; bookingError.textContent = ''; renderSlots(); showSelection(); });
            slotBox.appendChild(b);
        });
    }
    function selectDate(key) { selectedDate = key; dateInput.value = key; timeInput.value = ''; $('selectedDateLabel').textContent = fmtDate(key); renderSlots(); showSelection(); renderCalendar(); refreshAvailability(); }
    // Re-read which times are taken so a slot booked by another patient turns gray right away.
    let refreshTimer = null;
    async function refreshAvailability() {
        try {
            const res = await fetch('appointments.php?availability=1', { credentials: 'same-origin', cache: 'no-store' });
            const data = await res.json();
            availability = data.availability || {};
            if (timeInput.value && selectedDate && !slotAvailable(selectedDate, timeInput.value)) { timeInput.value = ''; bookingError.textContent = 'Sorry, that time was just booked by another patient. Please choose another time.'; showSelection(); }
            renderCalendar(); renderSlots();
        } catch (_) { /* The server still refuses double bookings on submit. */ }
    }
    $('prevMonth').addEventListener('click', () => { currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() - 1, 1); renderCalendar() });
    $('nextMonth').addEventListener('click', () => { currentMonth = new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 1); renderCalendar() });
    function openBooking(newMode, btn) {
        mode = newMode; resetSelection();
        const isMove = mode === 'reschedule';
        $('bookingAction').value = mode; $('bookingId').value = isMove ? btn.dataset.reschedule : '';
        $('bookingTitle').textContent = isMove ? 'Reschedule Appointment' : 'Select Schedule';
        $('bookingIntro').hidden = !isMove;
        $('bookingIntro').textContent = isMove ? 'Current schedule: ' + btn.dataset.when + '. Choose a new date and time.' : '';
        submit.querySelector('span').textContent = isMove ? 'Continue' : 'Continue to Dental Details';
        // The appointment's current slot counts as free while moving it.
        ownSlot = isMove ? { date: btn.dataset.date, time: btn.dataset.time } : null;
        currentMonth = new Date(serverNow.getFullYear(), serverNow.getMonth(), 1); renderCalendar(); openModal('bookingModal');
        refreshAvailability(); clearInterval(refreshTimer);
        refreshTimer = setInterval(() => { const open = $('bookingModal').classList.contains('is-open') || $('detailsModal').classList.contains('is-open'); if (!open) clearInterval(refreshTimer); else if (!document.hidden) refreshAvailability(); }, 10000);
    }
    $('newAppointmentBtn').addEventListener('click', () => { if (!profileComplete) { const m = $('profileBookingModal'); m.classList.add('is-open'); m.setAttribute('aria-hidden', 'false'); return; } openBooking('create') });
    document.querySelectorAll('[data-reschedule]').forEach(b => b.addEventListener('click', () => openBooking('reschedule', b)));

    // ---- Step 2: dental details ----
    const marked = new Set();
    window.bookingMarked = marked;
    let savedTeeth = [];
    const teethOrder = <?= json_encode(SD_TEETH) ?>;
    const sortTeeth = list => [...list].sort((a, b) => teethOrder.indexOf(a) - teethOrder.indexOf(b));
    function updateTeethNote(saved) {
        const list = sortTeeth(marked);
        $('markedTeethNote').textContent = list.length ? (saved ? 'Saved marked teeth: ' : 'Marked teeth (not saved yet): ') + list.join(', ') : 'Click a tooth to mark where you feel discomfort. Drag to rotate.';
        $('markedTeethNote').className = saved && list.length ? 'is-saved' : '';
        window.bookingViewer?.sync();
    }
    window.toggleBookingTooth = id => { marked.has(id) ? marked.delete(id) : marked.add(id); updateTeethNote(false); };
    $('clearTeeth').addEventListener('click', () => { marked.clear(); updateTeethNote(false); });
    $('saveTeeth').addEventListener('click', () => { savedTeeth = sortTeeth(marked); updateTeethNote(true); });
    $('bookingModelReset').addEventListener('click', () => window.bookingViewer?.reset());

    $('bookingForm').addEventListener('submit', e => {
        e.preventDefault();
        if (!dateInput.value || !timeInput.value) { bookingError.textContent = 'Please select an available date and time slot.'; return; }
        if (mode === 'reschedule') {
            const list = $('confirmList'); list.replaceChildren();
            [['Date', fmtDate(dateInput.value)], ['Time', fmtTime(timeInput.value)]].forEach(([k, v]) => { const dt = document.createElement('dt'); dt.textContent = k; const dd = document.createElement('dd'); dd.textContent = v; list.append(dt, dd) });
            openModal('confirmModal'); return;
        }
        $('reviewDate').textContent = fmtDate(dateInput.value);
        $('reviewTime').textContent = fmtTime(timeInput.value);
        $('detailsError').textContent = '';
        closeModal('bookingModal');
        openModal('detailsModal');
        window.dispatchEvent(new Event('booking-details-open'));
    });
    $('confirmEdit').addEventListener('click', () => closeModal('confirmModal'));
    $('confirmSubmit').addEventListener('click', async () => {
        $('confirmSubmit').disabled = true;
        await refreshAvailability();
        if (!timeInput.value) { closeModal('confirmModal'); $('confirmSubmit').disabled = false; return; }
        $('bookingForm').submit();
    });
    $('bookAppointmentBtn').addEventListener('click', async () => {
        const err = $('detailsError'), btn = $('bookAppointmentBtn');
        err.textContent = '';
        if (!$('serviceSelect').value) { err.textContent = 'Please choose the dental service you need.'; $('serviceSelect').focus(); return; }
        // Marks that were clicked but not yet saved are saved together with the booking.
        if (marked.size !== savedTeeth.length || sortTeeth(marked).some((t, i) => t !== savedTeeth[i])) { savedTeeth = sortTeeth(marked); updateTeethNote(true); }
        btn.disabled = true; btn.textContent = 'Booking...';
        const body = new URLSearchParams({ ajax: '1', action: 'create', csrf_token: document.querySelector('#bookingForm [name=csrf_token]').value, appointment_date: dateInput.value, appointment_time: timeInput.value, appointment_type: $('serviceSelect').value, reason: $('reasonInput').value.trim(), marked_teeth: JSON.stringify(savedTeeth) });
        try {
            const res = await fetch('appointments.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' }, body });
            const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response. Please try again.' }));
            if (!data.ok) throw new Error(data.message || 'Unable to book the appointment.');
            const a = data.appointment || {};
            const rows = [['Date', a.date || fmtDate(dateInput.value)], ['Time', a.time || fmtTime(timeInput.value)], ['Dental Service', a.service || $('serviceSelect').value], ['Reason / Concern', a.reason || 'No reason provided'], ['Marked Teeth', (a.teeth && a.teeth.length) ? a.teeth.join(', ') : 'None']];
            const list = $('successList'); list.replaceChildren();
            rows.forEach(([k, v]) => { const dt = document.createElement('dt'); dt.textContent = k; const dd = document.createElement('dd'); dd.textContent = v; list.append(dt, dd) });
            $('successText').textContent = data.message || 'Your appointment is confirmed.';
            closeModal('detailsModal');
            openModal('successModal');
        } catch (ex) {
            err.textContent = ex.message;
            if (/booked|schedule|time/i.test(ex.message)) refreshAvailability();
        } finally { btn.disabled = false; btn.textContent = 'Book Appointment'; }
    });
    $('successDone').addEventListener('click', () => { location.href = 'appointments.php'; });

    // ---- View / Cancel ----
    function fillList(listId, rows) { const list = $(listId); list.replaceChildren(); rows.forEach(([k, v]) => { const dt = document.createElement('dt'); dt.textContent = k; const dd = document.createElement('dd'); dd.textContent = v; list.append(dt, dd) }); }
    const statusText = { pending: 'Pending', approved: 'Approved', completed: 'Completed', rejected: 'Declined', cancelled: 'Cancelled' };
    document.querySelectorAll('[data-view-appt]').forEach(b => b.addEventListener('click', () => {
        const a = appointmentData[b.dataset.viewAppt]; if (!a) return;
        const rows = [['Date', a.date], ['Time', a.time], ['Dental Service', a.service], ['Reason / Concern', a.reason || 'No reason provided'], ['Marked Teeth', a.teeth.length ? a.teeth.join(', ') : 'None'], ['Status', statusText[a.status] || a.status]];
        if (a.dentist) rows.push(['Dentist', a.dentist]);
        if (a.status === 'cancelled' && a.cancel_reason) rows.push(['Cancel Reason', a.cancel_reason]);
        fillList('viewList', rows); openModal('viewModal');
    }));
    document.querySelectorAll('[data-cancel-appt]').forEach(b => b.addEventListener('click', () => {
        $('cancelId').value = b.dataset.cancelAppt; $('cancelReason').value = '';
        $('cancelText').textContent = b.dataset.service + ' · ' + b.dataset.when;
        openModal('cancelModal');
    }));
    // Deep link from a notification: appointments.php?view=ID opens that appointment's details.
    (() => { const v = new URLSearchParams(location.search).get('view'); if (v && appointmentData[v]) document.querySelector('[data-view-appt="' + CSS.escape(v) + '"]')?.click(); })();
<?php if (($preselected || isset($_GET['new'])) && $profileComplete): ?>openBooking('create'); <?php elseif (isset($_GET['new'])): ?>$('newAppointmentBtn').click(); <?php endif; ?>
</script>
<script type="importmap">{"imports":{"three":"../assets/js/three.module.js"}}</script>
<script type="module">
import { createToothViewer } from '../assets/js/tooth_viewer.js';
// The 3D model is created the first time the dental-details pop-up is visible, so it can size itself.
window.addEventListener('booking-details-open', () => {
    if (window.bookingViewer) { window.bookingViewer.sync(); return; }
    requestAnimationFrame(() => {
        window.bookingViewer = createToothViewer(document.getElementById('bookingModel'), {
            colorFor: id => window.bookingMarked?.has(id) ? 0x2676db : null,
            onToothClick: id => window.toggleBookingTooth(id)
        });
    });
});
</script>
<?php ui_footer(); ?>
