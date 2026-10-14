<?php
// Old page that let assistants write patient records directly. Assistants now prepare charts for the
// dentist (Create Chart); only dentist-confirmed records reach the patient.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
header('Location: chart_editor.php');
exit;
