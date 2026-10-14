<?php
require_once __DIR__ . '/../config/security.php';
// Signs out only the portal the Logout button belongs to; other portals on this browser stay signed in.
// If the portal cannot be told, every portal on this browser is signed out.
if (request_portal() !== '') {
    start_secure_session();
    destroy_secure_session();
} else {
    start_secure_session();
    destroy_all_portal_sessions();
}
header('Location: login.php');
exit;
