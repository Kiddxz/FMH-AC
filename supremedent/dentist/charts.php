<?php
// Chart records now live on the Dental Charts page.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
$params = [];
if ((int)($_GET['patient_id'] ?? 0) > 0) $params['patient_id'] = (int)$_GET['patient_id'];
if (isset($_GET['saved'])) $params['saved'] = 1;
if (isset($_GET['deleted'])) $params['deleted'] = 1;
if (trim($_GET['q'] ?? '') !== '') $params['q'] = trim($_GET['q']);
header('Location: dental_charts.php' . ($params ? '?' . http_build_query($params) : ''));
exit;
