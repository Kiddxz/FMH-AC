<?php
/**
 * Shared appointment workflow (revision 2):
 * - patient bookings are approved automatically and given an available dentist,
 * - any role can cancel an active appointment (status 'cancelled'),
 * - patients and staff receive notifications that link back to the appointment.
 */

const SD_TEETH = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28','48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];

/** Clean list of FDI tooth numbers from user input (JSON array, object map or comma list). */
function sd_clean_teeth($raw): array
{
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : preg_split('/[\s,]+/', $raw);
    }
    if (!is_array($raw)) return [];
    if (array_keys($raw) !== range(0, count($raw) - 1)) $raw = array_keys($raw);
    $teeth = array_values(array_unique(array_filter(array_map('strval', $raw), static fn($t) => in_array($t, SD_TEETH, true))));
    usort($teeth, static fn($a, $b) => array_search($a, SD_TEETH, true) <=> array_search($b, SD_TEETH, true));
    return $teeth;
}

function sd_appointment_when(array $a): string
{
    return date('M d, Y', strtotime($a['appointment_date'])) . ' at ' . date('h:i A', strtotime($a['appointment_time']));
}

function sd_notify_patient(mysqli $conn, int $patientId, string $title, string $message, string $type = 'appointment', ?int $appointmentId = null): void
{
    $st = $conn->prepare('INSERT INTO notifications(patient_id,title,message,type,appointment_id,is_read) VALUES(?,?,?,?,?,0)');
    if (!$st) return;
    $st->bind_param('isssi', $patientId, $title, $message, $type, $appointmentId);
    $st->execute();
    $st->close();
}

/**
 * Notify active staff. $roles chooses whole roles (e.g. all assistants); $userIds adds specific people.
 * The acting user never notifies themself.
 */
function sd_notify_staff(mysqli $conn, array $roles, array $userIds, string $title, string $message, ?int $appointmentId = null, ?int $patientId = null, int $exceptUser = 0, string $type = 'appointment'): void
{
    $targets = [];
    if ($roles) {
        $roles = array_values(array_intersect($roles, ['assistant', 'dentist', 'superadmin']));
        if ($roles) {
            $in = implode(',', array_fill(0, count($roles), '?'));
            $st = $conn->prepare("SELECT id FROM users WHERE status='active' AND role IN ($in)");
            $st->bind_param(str_repeat('s', count($roles)), ...$roles);
            $st->execute();
            foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) $targets[(int)$r['id']] = true;
            $st->close();
        }
    }
    foreach ($userIds as $id) if ((int)$id > 0) $targets[(int)$id] = true;
    unset($targets[$exceptUser]);
    if (!$targets) return;
    $st = $conn->prepare('INSERT INTO staff_notifications(user_id,title,message,type,appointment_id,patient_id) VALUES(?,?,?,?,?,?)');
    if (!$st) return;
    foreach (array_keys($targets) as $uid) {
        $st->bind_param('isssii', $uid, $title, $message, $type, $appointmentId, $patientId);
        $st->execute();
    }
    $st->close();
}

/** True when the staff member blocked this date/time in provider_unavailability. */
function sd_staff_unavailable(mysqli $conn, int $userId, string $date, string $time): bool
{
    $time = substr($time, 0, 5) . ':00';
    $st = $conn->prepare("SELECT id FROM provider_unavailability WHERE user_id=? AND unavailable_date=? AND (all_day=1 OR (? >= start_time AND ? < end_time)) LIMIT 1");
    $st->bind_param('isss', $userId, $date, $time, $time);
    $st->execute();
    $blocked = $st->get_result()->num_rows > 0;
    $st->close();
    return $blocked;
}

/**
 * Picks an active dentist who is free at that date/time (not blocked, no other active appointment),
 * preferring the one with the fewest appointments that day. Returns null when nobody is free.
 */
function sd_pick_dentist(mysqli $conn, string $date, string $time, int $excludeAppointment = 0): ?int
{
    $time = substr($time, 0, 5) . ':00';
    $st = $conn->prepare("SELECT u.id,
            (SELECT COUNT(*) FROM appointments a WHERE a.dentist_id=u.id AND a.appointment_date=? AND a.status IN ('pending','approved')) day_load,
            (SELECT COUNT(*) FROM appointments b WHERE b.dentist_id=u.id AND b.appointment_date=? AND b.appointment_time=? AND b.status IN ('pending','approved') AND b.id<>?) busy
        FROM users u WHERE u.role='dentist' AND u.status='active' ORDER BY day_load ASC, u.id ASC");
    $st->bind_param('sssi', $date, $date, $time, $excludeAppointment);
    $st->execute();
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    foreach ($rows as $r) {
        if ((int)$r['busy'] > 0) continue;
        if (sd_staff_unavailable($conn, (int)$r['id'], $date, $time)) continue;
        return (int)$r['id'];
    }
    return null;
}

function sd_load_appointment(mysqli $conn, int $id): ?array
{
    $st = $conn->prepare("SELECT a.*, u.first_name, u.last_name, u.email, d.first_name dentist_first, d.last_name dentist_last
        FROM appointments a JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id WHERE a.id=? LIMIT 1");
    $st->bind_param('i', $id);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    return $row ?: null;
}

/**
 * Cancels an active (pending/approved) appointment.
 * Patients may only cancel their own upcoming appointments; staff may cancel any active one.
 * Returns [ok, message].
 */
function sd_cancel_appointment(mysqli $conn, int $appointmentId, int $actorId, string $actorRole, string $reason = ''): array
{
    $a = sd_load_appointment($conn, $appointmentId);
    if (!$a) return [false, 'Appointment not found.'];
    if ($actorRole === 'patient' && (int)$a['patient_id'] !== $actorId) return [false, 'Appointment not found.'];
    if (!in_array($actorRole, ['patient', 'assistant', 'dentist', 'superadmin'], true)) return [false, 'You cannot cancel this appointment.'];
    if (!in_array($a['status'], ['pending', 'approved'], true)) return [false, 'Only pending or approved appointments can be cancelled.'];
    if ($actorRole === 'patient' && strtotime($a['appointment_date'] . ' ' . $a['appointment_time']) < time()) return [false, 'Past appointments can no longer be cancelled.'];

    $reason = mb_substr(trim($reason), 0, 255);
    $st = $conn->prepare("UPDATE appointments SET status='cancelled', cancelled_by=?, cancelled_at=NOW(), cancel_reason=? WHERE id=? AND status IN ('pending','approved')");
    $st->bind_param('isi', $actorId, $reason, $appointmentId);
    $st->execute();
    $changed = $st->affected_rows === 1;
    $st->close();
    if (!$changed) return [false, 'This appointment was already updated. Refresh and try again.'];

    $when = sd_appointment_when($a);
    $patientName = trim($a['first_name'] . ' ' . $a['last_name']);
    $by = $actorRole === 'patient' ? 'the patient' : 'the clinic ' . $actorRole;
    $why = $reason !== '' ? ' Reason: ' . rtrim($reason, '. ') . '.' : '';
    if ($actorRole === 'patient') {
        sd_notify_patient($conn, (int)$a['patient_id'], 'Appointment Cancelled', 'You cancelled your ' . $a['appointment_type'] . ' appointment on ' . $when . '.' . $why, 'appointment', $appointmentId);
    } else {
        sd_notify_patient($conn, (int)$a['patient_id'], 'Appointment Cancelled', 'Your ' . $a['appointment_type'] . ' appointment on ' . $when . ' was cancelled by the clinic.' . $why . ' You can book a new schedule anytime.', 'appointment', $appointmentId);
    }
    sd_notify_staff($conn, ['assistant'], [(int)$a['dentist_id']], 'Appointment cancelled · ' . $patientName,
        $patientName . "'s " . $a['appointment_type'] . ' appointment on ' . $when . ' was cancelled by ' . $by . '.' . $why, $appointmentId, (int)$a['patient_id'], $actorId);
    if (function_exists('log_activity')) log_activity($conn, $actorId, 'Cancelled appointment', 'Appointments', 'Appointment #' . $appointmentId . ($reason !== '' ? ' · ' . $reason : ''));
    return [true, 'The appointment on ' . $when . ' was cancelled.'];
}

/** Appointment data shown in the shared "View" pop-ups (date, time, service, reason, teeth). */
function sd_appointment_view_data(array $a): array
{
    return [
        'id' => (int)$a['id'],
        'patient' => trim(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? '')),
        'patient_id' => (int)$a['patient_id'],
        'date' => date('l, F j, Y', strtotime($a['appointment_date'])),
        'time' => date('g:i A', strtotime($a['appointment_time'])),
        'service' => (string)$a['appointment_type'],
        'reason' => (string)($a['reason'] ?? ''),
        'status' => (string)$a['status'],
        'teeth' => sd_clean_teeth((string)($a['marked_teeth'] ?? '')),
        'dentist' => trim(($a['dentist_first'] ?? '') . ' ' . ($a['dentist_last'] ?? '')),
        'cancel_reason' => (string)($a['cancel_reason'] ?? ''),
    ];
}
