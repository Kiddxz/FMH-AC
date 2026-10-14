<?php
/**
 * Dentist-to-patient record sharing.
 * Everything a dentist saves for a patient (examination chart, procedure record, completed visit,
 * odontogram update, treatment plan) becomes a row in dental_records, which the patient sees under
 * Dental Records / Treatment History, and the patient gets a notification about it.
 */
require_once __DIR__ . '/appointment_helpers.php';

const SD_RECORD_SOURCES = ['manual', 'chart', 'visit', 'odontogram', 'plan'];

function sd_user_full_name(mysqli $conn, int $userId): string
{
    $st = $conn->prepare("SELECT CONCAT(first_name,' ',last_name) n FROM users WHERE id=? LIMIT 1");
    $st->bind_param('i', $userId);
    $st->execute();
    $name = (string)($st->get_result()->fetch_assoc()['n'] ?? '');
    $st->close();
    return trim($name);
}

/** Normalises "16, 26 ,38" (or an array) into the clinic's FDI tooth numbers, keeping chart order. */
function sd_clean_tooth_list($teeth): string
{
    $list = is_array($teeth) ? $teeth : preg_split('/[\s,;]+/', (string)$teeth);
    $valid = [];
    foreach ($list as $t) {
        $t = trim((string)$t);
        if (preg_match('/^[1-8][1-8]$/', $t) && !in_array($t, $valid, true)) $valid[] = $t;
    }
    return implode(', ', array_slice($valid, 0, 32));
}

/**
 * Creates or updates the patient-visible record and notifies the patient.
 * $r keys: patient_id, dentist_id, procedure_name, diagnosis, notes, tooth_number, record_date, source,
 * chart_id, appointment_id. A chart or appointment has at most one record, which is updated on later saves.
 * Returns the dental_records id.
 */
function sd_share_record_with_patient(mysqli $conn, array $r, bool $notify = true): int
{
    $patientId = (int)$r['patient_id'];
    $dentistId = (int)($r['dentist_id'] ?? 0) ?: null;
    $source = in_array($r['source'] ?? 'manual', SD_RECORD_SOURCES, true) ? $r['source'] : 'manual';
    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)($r['record_date'] ?? '')) ? $r['record_date'] : date('Y-m-d');
    $procedure = mb_substr(trim((string)$r['procedure_name']), 0, 150) ?: 'Dental record';
    $diagnosis = mb_substr(trim((string)($r['diagnosis'] ?? '')), 0, 255);
    $notes = mb_substr(trim((string)($r['notes'] ?? '')), 0, 5000);
    $teeth = sd_clean_tooth_list($r['tooth_number'] ?? '');
    $chartId = (int)($r['chart_id'] ?? 0) ?: null;
    $appointmentId = (int)($r['appointment_id'] ?? 0) ?: null;
    $dentistName = $dentistId ? sd_user_full_name($conn, $dentistId) : '';
    $dentistLabel = $dentistName !== '' ? 'Dr. ' . $dentistName : 'Clinic staff';

    $existing = 0;
    if ($chartId || $appointmentId) {
        $column = $chartId ? 'chart_id' : 'appointment_id';
        $key = $chartId ?: $appointmentId;
        $st = $conn->prepare("SELECT id FROM dental_records WHERE $column=? AND patient_id=? ORDER BY id DESC LIMIT 1");
        $st->bind_param('ii', $key, $patientId);
        $st->execute();
        $existing = (int)($st->get_result()->fetch_assoc()['id'] ?? 0);
        $st->close();
    }

    if ($existing) {
        $st = $conn->prepare('UPDATE dental_records SET record_date=?,procedure_name=?,diagnosis=?,dentist_name=?,notes=?,dentist_id=?,tooth_number=?,source=? WHERE id=?');
        $st->bind_param('sssssissi', $date, $procedure, $diagnosis, $dentistLabel, $notes, $dentistId, $teeth, $source, $existing);
        $st->execute();
        $st->close();
        $recordId = $existing;
        // A record the patient had hidden becomes visible again once the dentist changes it.
        $st = $conn->prepare('DELETE FROM patient_hidden_records WHERE patient_id=? AND record_id=?');
        if ($st) { $st->bind_param('ii', $patientId, $recordId); $st->execute(); $st->close(); }
    } else {
        $st = $conn->prepare('INSERT INTO dental_records(patient_id,record_date,procedure_name,diagnosis,dentist_name,notes,dentist_id,chart_id,appointment_id,tooth_number,source) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $st->bind_param('isssssiiiss', $patientId, $date, $procedure, $diagnosis, $dentistLabel, $notes, $dentistId, $chartId, $appointmentId, $teeth, $source);
        $st->execute();
        $recordId = (int)$st->insert_id;
        $st->close();
    }

    if ($notify) {
        $verb = $existing ? 'updated' : 'added';
        $title = $existing ? 'Dental Record Updated' : 'New Dental Record';
        $message = $dentistLabel . ' ' . $verb . ' a record in your Dental Records: ' . $procedure
            . ' (' . date('M d, Y', strtotime($date)) . ')' . ($teeth !== '' ? ' · Teeth: ' . $teeth : '')
            . ($diagnosis !== '' ? ' · ' . $diagnosis : '') . '.';
        sd_notify_patient($conn, $patientId, $title, mb_substr($message, 0, 1000), 'record', $appointmentId);
    }
    return $recordId;
}

/** Removes the patient-visible record that belongs to a deleted chart. */
function sd_remove_chart_record(mysqli $conn, int $chartId): void
{
    $st = $conn->prepare('DELETE FROM dental_records WHERE chart_id=?');
    $st->bind_param('i', $chartId);
    $st->execute();
    $st->close();
}

/** "Caries: 16, 26 · Missing: 38" from a [tooth => condition] map. */
function sd_teeth_summary(array $teeth): string
{
    $groups = [];
    foreach ($teeth as $tooth => $condition) $groups[(string)$condition][] = (string)$tooth;
    $parts = [];
    foreach ($groups as $condition => $list) $parts[] = $condition . ': ' . implode(', ', $list);
    return implode(' · ', $parts);
}

/** Shares a dentist-confirmed examination chart with the patient. */
function sd_share_chart_with_patient(mysqli $conn, int $chartId, int $dentistId): void
{
    $st = $conn->prepare('SELECT * FROM dental_charts WHERE id=? LIMIT 1');
    $st->bind_param('i', $chartId);
    $st->execute();
    $chart = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$chart) return;
    $teeth = [];
    $st = $conn->prepare('SELECT tooth_number,condition_name FROM dental_chart_teeth WHERE chart_id=? ORDER BY id');
    $st->bind_param('i', $chartId);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $t) $teeth[$t['tooth_number']] = $t['condition_name'];
    $st->close();

    $notes = trim((string)($chart['notes'] ?? ''));
    $exam = json_decode((string)($chart['examination_data'] ?? '{}'), true);
    if (is_array($exam) && function_exists('exam_record_normalize')) {
        $rec = exam_record_normalize($exam);
        $labels = (defined('EXAM_RECOMMEND_LEFT') ? EXAM_RECOMMEND_LEFT : []) + (defined('EXAM_RECOMMEND_RIGHT') ? EXAM_RECOMMEND_RIGHT : []);
        $recommended = [];
        foreach ($rec['recommendations'] ?? [] as $value) $recommended[] = $labels[$value] ?? $value;
        if ($recommended) $notes = trim('Recommendations: ' . implode('; ', $recommended) . ".\n" . $notes);
    }
    sd_share_record_with_patient($conn, [
        'patient_id' => (int)$chart['patient_id'],
        'dentist_id' => $dentistId,
        'record_date' => $chart['chart_date'] ?: date('Y-m-d', strtotime((string)$chart['created_at'])),
        'procedure_name' => 'Dental Examination (Dental Chart)',
        'diagnosis' => $teeth ? sd_teeth_summary($teeth) : 'No tooth conditions marked',
        'notes' => $notes,
        'tooth_number' => array_keys($teeth),
        'source' => 'chart',
        'chart_id' => $chartId,
    ]);
}

/**
 * Marks the dentist's approved (or unassigned) appointment completed and saves the visit to the
 * patient's records, with what was done, so the patient sees it under Dental Records / Treatment History.
 */
function sd_complete_appointment(mysqli $conn, int $appointmentId, int $dentistId, string $notes = '', string $teeth = '', string $diagnosis = ''): bool
{
    $st = $conn->prepare("UPDATE appointments SET status='completed', dentist_id=COALESCE(dentist_id, ?) WHERE id=? AND (dentist_id=? OR dentist_id IS NULL) AND status='approved'");
    $st->bind_param('iii', $dentistId, $appointmentId, $dentistId);
    $st->execute();
    $changed = $st->affected_rows === 1;
    $st->close();
    if (!$changed) return false;
    $a = sd_load_appointment($conn, $appointmentId);
    if ($a) {
        $notes = trim($notes);
        sd_share_record_with_patient($conn, [
            'patient_id' => (int)$a['patient_id'],
            'dentist_id' => $dentistId,
            'record_date' => $a['appointment_date'],
            'procedure_name' => $a['appointment_type'],
            'diagnosis' => $diagnosis,
            'notes' => $notes !== '' ? $notes : 'Visit completed on ' . sd_appointment_when($a) . '.',
            'tooth_number' => $teeth,
            'source' => 'visit',
            'appointment_id' => $appointmentId,
        ]);
    }
    log_activity($conn, $dentistId, 'Completed appointment', 'Appointments', 'Appointment #' . $appointmentId);
    return true;
}
