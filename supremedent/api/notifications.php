<?php
/**
 * Notification actions for the patient, assistant and dentist portals.
 * POST action=read&id=N marks one notification as read, action=read_all marks every one.
 * Patients use the `notifications` table; clinic staff use `staff_notifications`.
 */
ini_set('display_errors', '0');
require_once '../config/security.php';
start_secure_session();
require_once '../config/database.php';
require_portal_role($conn, ['patient', 'assistant', 'dentist', 'superadmin'], true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');

$uid = (int)$_SESSION['user_id'];
$role = (string)$_SESSION['role'];
function notif_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') notif_json(['ok' => false, 'message' => 'Method not allowed.'], 405);
if (!csrf_valid($_POST['csrf_token'] ?? null)) notif_json(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);

$action = (string)($_POST['action'] ?? '');
$id = (int)($_POST['id'] ?? 0);
try {
    if ($role === 'superadmin') notif_json(['ok' => true]);
    $table = $role === 'patient' ? 'notifications' : 'staff_notifications';
    $owner = $role === 'patient' ? 'patient_id' : 'user_id';
    if ($action === 'read' && $id > 0) {
        $st = $conn->prepare("UPDATE $table SET is_read=1 WHERE id=? AND $owner=?");
        $st->bind_param('ii', $id, $uid);
    } elseif ($action === 'read_all') {
        $st = $conn->prepare("UPDATE $table SET is_read=1 WHERE $owner=? AND is_read=0");
        $st->bind_param('i', $uid);
    } else {
        notif_json(['ok' => false, 'message' => 'Unknown action.'], 400);
    }
    $st->execute();
    $st->close();
    $st = $conn->prepare("SELECT COUNT(*) c FROM $table WHERE $owner=? AND is_read=0");
    $st->bind_param('i', $uid);
    $st->execute();
    $unread = (int)($st->get_result()->fetch_assoc()['c'] ?? 0);
    $st->close();
    notif_json(['ok' => true, 'unread' => $unread]);
} catch (Throwable $e) {
    error_log('[SupremeDent notifications API] ' . $e->getMessage());
    notif_json(['ok' => false, 'message' => 'Notifications are temporarily unavailable.'], 500);
}
