<?php
/** Realtime fingerprint + badge counts for the signed-in user (polled by assets/js/live_updates.js). */
ini_set('display_errors', '0');
require_once '../config/security.php';
start_secure_session();
require_once '../config/database.php';
$previousActivity = (int)($_SESSION['last_activity'] ?? 0);
require_portal_role($conn, ['patient', 'assistant', 'dentist', 'superadmin'], true);
// Background polling must not keep an idle session alive forever, so the activity clock is restored.
if ($previousActivity > 0) $_SESSION['last_activity'] = $previousActivity;
require_once '../includes/live.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
$state = sd_live_state($conn, (string)$_SESSION['role'], (int)$_SESSION['user_id']);
session_write_close();
echo json_encode(['ok' => true] + $state, JSON_UNESCAPED_SLASHES);
