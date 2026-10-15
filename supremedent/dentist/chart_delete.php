<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? null)) { header('Location: dental_charts.php'); exit; }
$id = (int)($_POST['id'] ?? 0);
$patientId = 0;
if ($id > 0) {
    $stmt = $conn->prepare('SELECT patient_id FROM dental_charts WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $patientId = (int)($stmt->get_result()->fetch_assoc()['patient_id'] ?? 0); $stmt->close();
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare('DELETE FROM dental_chart_teeth WHERE chart_id = ?');
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        $stmt = $conn->prepare('DELETE FROM dental_charts WHERE id = ?'); $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        $conn->commit();
    } catch (Throwable $e) { $conn->rollback(); }
}
header('Location: dental_charts.php?' . (($_POST['back'] ?? '') === 'patient' && $patientId ? 'patient_id=' . $patientId . '&' : '') . 'deleted=1');
