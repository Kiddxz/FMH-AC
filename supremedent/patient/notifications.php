<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);
require_once __DIR__ . '/../includes/appointment_helpers.php';
require_once __DIR__ . '/../includes/notifications_center.php';

$uid = (int) $_SESSION['user_id'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read']) && csrf_valid($_POST['csrf_token'] ?? null)) {
    $st = $conn->prepare('UPDATE notifications SET is_read=1 WHERE patient_id=?');
    $st->bind_param('i', $uid);
    $st->execute();
    $st->close();
    header('Location: notifications.php');
    exit;
}
$st = $conn->prepare("SELECT n.id,n.title,n.message,n.type,n.is_read,n.created_at,n.appointment_id,
        a.id a_id,a.patient_id,a.appointment_date,a.appointment_time,a.appointment_type,a.reason,a.marked_teeth,a.status,a.cancel_reason,
        u.first_name,u.last_name,d.first_name dentist_first,d.last_name dentist_last
    FROM notifications n
    LEFT JOIN appointments a ON a.id=n.appointment_id AND a.patient_id=n.patient_id
    LEFT JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id
    WHERE n.patient_id=? ORDER BY n.created_at DESC, n.id DESC");
$st->bind_param('i', $uid);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
$items = [];
$unread = 0;
foreach ($rows as $r) {
    if (!(int) $r['is_read']) $unread++;
    $appointment = $r['a_id'] ? sd_appointment_view_data(['id' => $r['a_id']] + $r) : null;
    $links = ['appointment' => ['appointments.php', 'View in My Appointments'], 'treatment' => ['treatment_plans.php', 'View My Treatment Plans'], 'record' => ['dental_records.php', 'View My Dental Records']];
    [$link, $label] = $links[$r['type']] ?? [null, null];
    // Record notifications always open Dental Records, even when they came from an appointment.
    if ($r['type'] === 'record') $items[] = $r + ['appointment' => null, 'link' => $link, 'link_label' => $label];
    else $items[] = $r + ['appointment' => $appointment, 'link' => $appointment ? null : $link, 'link_label' => $appointment ? null : $label];
}
require '../includes/ui.php';
ui_header('Notifications', 'patient');
ui_sidebar('patient', 'notifications.php');
?>
<div class="page-head">
    <div>
        <div class="eyebrow">NOTIFICATIONS</div>
        <h1>My Notifications</h1>
        <div class="muted">Appointment updates, treatment notices, and clinic announcements in one place.</div>
    </div><?php if ($unread): ?>
        <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-secondary" name="mark_all_read" value="1">Mark all as read</button></form><?php endif; ?>
</div>
<?php render_notifications_center([
    'role' => 'patient',
    'items' => $items,
    'summary' => [['Total Notifications', (string) count($rows)], ['Unread', (string) $unread], ['Patient', $_SESSION['first_name'] ?? 'Patient']],
    'appointment_url' => 'appointments.php?view=',
    'appointment_label' => 'View in My Appointments',
    'read_api' => '../api/notifications.php?portal=patient',
]); ?>
<?php ui_footer(); ?>
