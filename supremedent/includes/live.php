<?php
/**
 * Realtime support (revision 2). sd_live_state() returns a short fingerprint of the data a portal
 * page shows, plus badge counts. assets/js/live_updates.js polls api/live.php and refreshes the page
 * by itself when the fingerprint changes, so nobody has to press reload to see new bookings,
 * cancellations, notifications, records or plans.
 *
 * Read flags (notification is_read) and chat messages are left out on purpose: opening a notification
 * or sending a chat message must not reload the page the user is working on.
 */
function sd_live_scalar(mysqli $conn, string $sql, string $types = '', array $params = []): string
{
    try {
        if ($types === '') {
            $r = $conn->query($sql);
            $row = $r ? $r->fetch_row() : null;
        } else {
            $st = $conn->prepare($sql);
            if (!$st) return '';
            $st->bind_param($types, ...$params);
            $st->execute();
            $row = $st->get_result()->fetch_row();
            $st->close();
        }
        return $row ? implode('|', array_map('strval', $row)) : '';
    } catch (Throwable $e) {
        return '';
    }
}

function sd_live_state(mysqli $conn, string $role, int $uid): array
{
    $parts = [];
    // Appointments change every calendar (bookings, cancellations, completions, reschedules).
    $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0), COALESCE(MAX(updated_at),'') FROM appointments");
    $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0) FROM provider_unavailability");
    $badge = 0;
    if ($role === 'patient') {
        $parts[] = sd_live_scalar($conn, 'SELECT COALESCE(MAX(id),0) FROM notifications WHERE patient_id=?', 'i', [$uid]);
        $parts[] = sd_live_scalar($conn, 'SELECT COUNT(*), COALESCE(MAX(id),0) FROM dental_records WHERE patient_id=?', 'i', [$uid]);
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM treatment_plans WHERE patient_id=?", 'i', [$uid]);
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM restoration_orders WHERE patient_id=?", 'i', [$uid]);
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM dental_charts WHERE patient_id=?", 'i', [$uid]);
        $badge = (int)sd_live_scalar($conn, 'SELECT COUNT(*) FROM notifications WHERE patient_id=? AND is_read=0', 'i', [$uid]);
    } elseif ($role === 'assistant' || $role === 'dentist') {
        $parts[] = sd_live_scalar($conn, 'SELECT COALESCE(MAX(id),0) FROM staff_notifications WHERE user_id=?', 'i', [$uid]);
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0) FROM users WHERE role='patient'");
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM dental_charts");
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM restoration_orders");
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0) FROM dental_records");
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(updated_at),'') FROM patient_tooth_reports");
        $badge = (int)sd_live_scalar($conn, 'SELECT COUNT(*) FROM staff_notifications WHERE user_id=? AND is_read=0', 'i', [$uid]);
    } else {
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0), SUM(status='new') FROM contact_messages");
        $parts[] = sd_live_scalar($conn, "SELECT COUNT(*), COALESCE(MAX(id),0), SUM(status='active') FROM users");
        $badge = (int)sd_live_scalar($conn, "SELECT COUNT(*) FROM contact_messages WHERE status='new'");
    }
    return ['version' => substr(md5($role . '#' . implode('#', $parts)), 0, 16), 'notifications' => $badge];
}
