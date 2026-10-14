<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
$detailRole = 'dentist';
require __DIR__ . '/../includes/appointment_detail.php';
