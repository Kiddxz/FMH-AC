<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
$editorRole = 'assistant';
require __DIR__ . '/../includes/chart_editor_body.php';
