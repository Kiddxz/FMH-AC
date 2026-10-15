<?php
/**
 * Notifications page for the assistant, dentist and super admin portals.
 * Assistants and dentists get stored notifications (new bookings, cancellations, walk-ins) that open a
 * details pop-up; the super admin sees new website inquiries.
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once '../config/database.php';
require_once __DIR__ . '/appointment_helpers.php';
require_once __DIR__ . '/notifications_center.php';
$role = $_SESSION['role'] ?? '';
$uid = (int)($_SESSION['user_id'] ?? 0);
if (!$uid || $role === 'patient') { header('Location: ../auth/login.php'); exit; }
$items = [];
if ($role === 'assistant' || $role === 'dentist') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read']) && csrf_valid($_POST['csrf_token'] ?? null)) {
        $st = $conn->prepare('UPDATE staff_notifications SET is_read=1 WHERE user_id=?');
        $st->bind_param('i', $uid); $st->execute(); $st->close();
        header('Location: notifications.php'); exit;
    }
    $st = $conn->prepare("SELECT n.id,n.title,n.message,n.type,n.is_read,n.created_at,
            a.id a_id,a.patient_id,a.appointment_date,a.appointment_time,a.appointment_type,a.reason,a.marked_teeth,a.status,a.cancel_reason,
            u.first_name,u.last_name,d.first_name dentist_first,d.last_name dentist_last
        FROM staff_notifications n LEFT JOIN appointments a ON a.id=n.appointment_id
        LEFT JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id
        WHERE n.user_id=? ORDER BY n.created_at DESC, n.id DESC LIMIT 200");
    $st->bind_param('i', $uid);
    $st->execute();
    foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
        $items[] = $r + ['appointment' => $r['a_id'] ? sd_appointment_view_data(['id' => $r['a_id']] + $r) : null, 'link' => null];
    }
    $st->close();
} else {
    $r = $conn->query("SELECT id,name,email,phone,subject,message,status,created_at FROM contact_messages ORDER BY created_at DESC LIMIT 60");
    while ($r && $x = $r->fetch_assoc()) {
        $items[] = ['id' => $x['id'], 'title' => 'Website inquiry · ' . $x['name'], 'message' => trim(($x['subject'] ? $x['subject'] . "\n" : '') . $x['message'] . "\n\n" . $x['email'] . ($x['phone'] ? ' · ' . $x['phone'] : '')),
            'type' => 'inquiry', 'is_read' => $x['status'] === 'new' ? 0 : 1, 'created_at' => $x['created_at'], 'appointment' => null, 'link' => 'inquiries.php'];
    }
}
$unread = count(array_filter($items, static fn($n) => !(int)$n['is_read']));
require '../includes/ui.php';
ui_header('Notifications', $role);
ui_sidebar($role, '');
?>
<div class="page-head"><div><div class="eyebrow">NOTIFICATIONS</div><h1><?= e(ucfirst($role === 'superadmin' ? 'Admin' : $role)) ?> Notifications</h1><div class="muted"><?= $role === 'superadmin' ? 'Website inquiries from the public contact form.' : 'New patient bookings, cancellations and walk-ins. Click a notification to see the details.' ?></div></div><?php if ($unread && $role !== 'superadmin'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-secondary" name="mark_all_read" value="1">Mark all as read</button></form><?php endif; ?></div>
<?php render_notifications_center([
    'role' => $role,
    'items' => $items,
    'summary' => [['Total Notifications', (string)count($items)], ['Unread', (string)$unread], [ucfirst($role === 'superadmin' ? 'Admin' : $role), $_SESSION['first_name'] ?? '']],
    'appointment_url' => 'appointment_view.php?id=',
    'appointment_label' => $role === 'superadmin' ? 'Open Inquiries' : 'Open Appointment',
    'read_api' => $role === 'superadmin' ? null : '../api/notifications.php?portal=' . $role,
]); ?>
<?php ui_footer(); ?>
