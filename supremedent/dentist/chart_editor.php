<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);
$editorRole = 'dentist';
require __DIR__ . '/../includes/chart_editor_body.php';
