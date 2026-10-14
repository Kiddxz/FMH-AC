<?php
// Old chart page (it wrote to a column that no longer exists). Charts are created in Dental Charts / Create Chart.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
$patient = (int)($_GET['patient'] ?? 0);
header('Location: ' . ($patient > 0 ? 'dental_charts.php?patient_id=' . $patient : 'dental_charts.php'));
exit;
