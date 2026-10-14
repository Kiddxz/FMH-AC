<?php
// Dental records are added on the patient's Patient Care page, which also shares them with the patient.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
$patient = (int)($_GET['patient_id'] ?? 0);
header('Location: ' . ($patient > 0 ? 'patient_view.php?id=' . $patient . '#records' : 'patients.php'));
exit;
