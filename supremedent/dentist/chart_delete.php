<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
require_once __DIR__ . '/../includes/patient_records.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? null)) { header('Location: dental_charts.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);
$patientId = 0;
$result = 'deleted';
if ($id > 0) {
    $stmt = $conn->prepare('SELECT patient_id, dentist_id, created_by FROM dental_charts WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $chart = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $patientId = (int)($chart['patient_id'] ?? 0);
    // A dentist may delete their own charts and unconfirmed assistant drafts, not another dentist's clinical record.
    $owner = (int)($chart['dentist_id'] ?? 0);
    if (!$chart || ($owner !== 0 && $owner !== $uid)) {
        $result = 'denied';
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('DELETE FROM dental_chart_teeth WHERE chart_id = ?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $stmt = $conn->prepare('DELETE FROM dental_charts WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            sd_remove_chart_record($conn, $id);
            $conn->commit();
            log_activity($conn, $uid, 'Deleted dental chart', 'Dental Charts', 'Chart #' . $id . ' · patient #' . $patientId);
        } catch (Throwable $e) { $conn->rollback(); $result = 'denied'; error_log('[SupremeDent chart delete] ' . $e->getMessage()); }
    }
}
header('Location: dental_charts.php?' . (($_POST['back'] ?? '') === 'patient' && $patientId ? 'patient_id=' . $patientId . '&' : '') . $result . '=1');
