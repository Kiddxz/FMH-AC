<?php
// Old static treatment plan mock-up (it saved nothing). Restoration plans are made in Treatment Plans.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
header('Location: treatment_plans.php');
exit;
