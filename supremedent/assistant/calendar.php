<?php
// The separate Calendar page was removed from the assistant portal; the calendar now lives in Appointments → Appointment View.
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
header('Location: appointments.php?view=calendar');
exit;
