<?php
// Old navigation hub; every item is now in the dentist sidebar.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
header('Location: patients.php');
exit;
