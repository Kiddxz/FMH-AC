<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);

// Legacy URL: tooth marking now happens while booking an appointment.
header('Location: appointments.php', true, 302);
exit;
