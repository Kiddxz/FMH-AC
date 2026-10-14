<?php
/**
 * JSON endpoint for the floating message widget.
 * The signed-in session decides who the user is; recipients are re-validated on every send.
 */
ini_set('display_errors', '0');
require_once '../config/security.php';
start_secure_session();
require_once '../config/database.php';
$previousActivity = (int)($_SESSION['last_activity'] ?? 0);
require_portal_role($conn, ['patient','assistant','dentist','superadmin'], true);
// The unread-badge poll runs in the background; it must not count as user activity for the idle timeout.
if ($previousActivity > 0 && $_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? 'summary') === 'summary' && isset($_GET['poll'])) $_SESSION['last_activity'] = $previousActivity;
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');

$uid = (int)$_SESSION['user_id'];
$role = (string)$_SESSION['role'];
$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'summary');

function msg_json(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
/** Roles each portal may write to. Patients only message the clinic assistants. */
function msg_allowed_roles(string $role): array {
    return match ($role) {
        'patient' => ['assistant'],
        'assistant' => ['patient', 'dentist', 'assistant'],
        'dentist' => ['patient', 'assistant', 'dentist'],
        default => ['patient', 'assistant', 'dentist', 'superadmin'],
    };
}

try {
    if ($action === 'summary') {
        // One row per counterpart, newest exchange first.
        $sql = "SELECT x.other_id, u.first_name, u.last_name, u.role, m.subject, m.message, m.created_at, m.sender_id,
                       (SELECT COUNT(*) FROM user_messages ur WHERE ur.sender_id = x.other_id AND ur.recipient_id = ? AND ur.is_read = 0) unread
                FROM (SELECT IF(sender_id = ?, recipient_id, sender_id) other_id, MAX(id) last_id
                      FROM user_messages WHERE sender_id = ? OR recipient_id = ? GROUP BY other_id) x
                JOIN user_messages m ON m.id = x.last_id
                JOIN users u ON u.id = x.other_id
                ORDER BY m.id DESC LIMIT 60";
        $st = $conn->prepare($sql);
        $st->bind_param('iiii', $uid, $uid, $uid, $uid);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        $conversations = [];
        $unread = 0;
        foreach ($rows as $r) {
            $unread += (int)$r['unread'];
            $conversations[] = [
                'user_id' => (int)$r['other_id'],
                'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'role' => ucfirst($r['role']),
                'subject' => $r['subject'],
                'preview' => mb_substr(preg_replace('/\s+/', ' ', $r['message']), 0, 90),
                'from_me' => (int)$r['sender_id'] === $uid,
                'at' => date('M d, h:i A', strtotime($r['created_at'])),
                'unread' => (int)$r['unread'],
            ];
        }
        msg_json(['ok' => true, 'unread' => $unread, 'conversations' => $conversations]);
    }

    if ($action === 'thread') {
        $other = (int)($_GET['user_id'] ?? 0);
        $st = $conn->prepare("SELECT id, first_name, last_name, role FROM users WHERE id = ? LIMIT 1");
        $st->bind_param('i', $other);
        $st->execute();
        $person = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$person) msg_json(['ok' => false, 'message' => 'Conversation not found.'], 404);

        // Opening the conversation marks the other person's messages as seen (read receipt for the sender).
        $st = $conn->prepare('UPDATE user_messages SET is_read = 1, read_at = COALESCE(read_at, NOW()) WHERE sender_id = ? AND recipient_id = ? AND is_read = 0');
        $st->bind_param('ii', $other, $uid);
        $st->execute();
        $st->close();

        // Latest 300 messages, shown oldest first.
        $st = $conn->prepare("SELECT * FROM (SELECT id, sender_id, subject, message, is_read, read_at, created_at FROM user_messages
                              WHERE (sender_id = ? AND recipient_id = ?) OR (sender_id = ? AND recipient_id = ?)
                              ORDER BY id DESC LIMIT 300) t ORDER BY id ASC");
        $st->bind_param('iiii', $uid, $other, $other, $uid);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();

        $messages = array_map(static function ($m) use ($uid) {
            $mine = (int)$m['sender_id'] === $uid;
            $seen = (int)$m['is_read'] === 1;
            return [
                'id' => (int)$m['id'],
                'mine' => $mine,
                'subject' => $m['subject'],
                'message' => $m['message'],
                'at' => date('M d, Y · h:i A', strtotime($m['created_at'])),
                // Delivery status is only shown on the sender's own messages.
                'status' => $mine ? ($seen ? 'seen' : 'delivered') : null,
                'seen_at' => $mine && $seen && $m['read_at'] ? date('M d, h:i A', strtotime($m['read_at'])) : null,
            ];
        }, $rows);
        $canReply = in_array($person['role'], msg_allowed_roles($role), true);
        msg_json(['ok' => true, 'person' => ['id' => (int)$person['id'], 'name' => trim($person['first_name'] . ' ' . $person['last_name']), 'role' => ucfirst($person['role'])], 'can_reply' => $canReply, 'messages' => $messages]);
    }

    if ($action === 'recipients') {
        $roles = msg_allowed_roles($role);
        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $st = $conn->prepare("SELECT id, first_name, last_name, role FROM users WHERE status = 'active' AND id <> ? AND role IN ($placeholders) ORDER BY last_name, first_name");
        $types = 'i' . str_repeat('s', count($roles));
        $st->bind_param($types, $uid, ...$roles);
        $st->execute();
        $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
        $st->close();
        $groups = [];
        foreach ($roles as $r) $groups[$r] = [];
        foreach ($rows as $r) $groups[$r['role']][] = ['id' => (int)$r['id'], 'label' => $r['last_name'] . ', ' . $r['first_name'] . ' — ' . ucfirst($r['role'])];
        msg_json(['ok' => true, 'groups' => $groups]);
    }

    if ($action === 'send') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid($_POST['csrf_token'] ?? null)) msg_json(['ok' => false, 'message' => 'Your session expired. Refresh the page and try again.'], 419);
        $recipient = (int)($_POST['recipient_id'] ?? 0);
        $subject = trim((string)($_POST['subject'] ?? ''));
        $body = trim((string)($_POST['message'] ?? ''));
        if (!$recipient || $subject === '' || $body === '') msg_json(['ok' => false, 'message' => 'Choose a recipient and complete the subject and message.'], 422);
        if (mb_strlen($subject) > 180 || mb_strlen($body) > 3000) msg_json(['ok' => false, 'message' => 'The subject or message is too long.'], 422);

        $st = $conn->prepare("SELECT role FROM users WHERE id = ? AND status = 'active' AND id <> ? LIMIT 1");
        $st->bind_param('ii', $recipient, $uid);
        $st->execute();
        $target = $st->get_result()->fetch_assoc();
        $st->close();
        if (!$target || !in_array($target['role'], msg_allowed_roles($role), true)) msg_json(['ok' => false, 'message' => 'That recipient is not available.'], 403);

        $st = $conn->prepare('INSERT INTO user_messages (sender_id, recipient_id, subject, message) VALUES (?, ?, ?, ?)');
        $st->bind_param('iiss', $uid, $recipient, $subject, $body);
        $st->execute();
        $st->close();
        msg_json(['ok' => true, 'message' => 'Message sent.', 'user_id' => $recipient]);
    }

    msg_json(['ok' => false, 'message' => 'Unknown action.'], 400);
} catch (Throwable $e) {
    error_log('[SupremeDent messages API] ' . $e->getMessage());
    msg_json(['ok' => false, 'message' => 'Messages are temporarily unavailable.'], 500);
}
