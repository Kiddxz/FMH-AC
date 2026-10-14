<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);

// Revision 2: marking teeth on the 3D dental model is now part of booking an appointment
// (Appointments → New Appointment → Continue to Dental Details), so this page moved there.
header('Location: appointments.php', true, 302);
exit;
