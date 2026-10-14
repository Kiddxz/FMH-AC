<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 require_once '../config/security.php'; start_secure_session(); require_once '../config/database.php'; if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;} require '../includes/profile_settings.php'; ?>
