<?php
// Clinical charts belong to the dentist portal; the Super Admin manages patient accounts under Users.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
header('Location: users.php?role=patient');
exit;
