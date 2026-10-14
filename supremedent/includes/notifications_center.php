<?php
/**
 * Notification list shared by the patient, assistant and dentist portals (revision 2):
 * one plain summary box, then a list where every notification opens a details pop-up.
 *
 * $nc = [
 *   'items' => [[id, title, message, type, is_read, created_at, appointment => sd_appointment_view_data()|null, link => url|null]],
 *   'summary' => [[label, value], ...]   // shown in the plain box
 *   'appointment_url' => 'appointment_view.php?id=' | 'appointments.php?view=', 'appointment_label' => 'Open Appointment',
 *   'read_api' => '../api/notifications.php' | null,
 * ]
 */
function render_notifications_center(array $nc): void
{
    $icons = ['appointment' => 'fa-calendar-days', 'treatment' => 'fa-tooth', 'announcement' => 'fa-bullhorn', 'general' => 'fa-bell', 'inquiry' => 'fa-envelope', 'record' => 'fa-file-medical', 'chart' => 'fa-teeth'];
    $items = $nc['items'] ?? [];
    $payload = [];
    foreach ($items as $n) {
        $payload[(int)$n['id']] = [
            'id' => (int)$n['id'], 'title' => (string)$n['title'], 'message' => (string)$n['message'], 'type' => (string)$n['type'],
            'read' => (int)$n['is_read'] === 1, 'at' => date('l, M d, Y · h:i A', strtotime($n['created_at'])),
            'appointment' => $n['appointment'] ?? null, 'link' => $n['link'] ?? null, 'link_label' => $n['link_label'] ?? null,
        ];
    }
    $flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;
    ?>
<section class="card notif-summary-box" aria-label="Notification summary">
    <?php foreach ($nc['summary'] as [$label, $value]): ?><div><span><?= e($label) ?></span><b><?= e($value) ?></b></div><?php endforeach; ?>
</section>
<section class="card" data-live-region="notifications">
    <div class="card-heading"><div><h2>Recent Notifications</h2><p class="muted">Your newest updates appear first. Click a notification to see its details.</p></div></div>
    <?php if (!$items): ?>
        <div class="empty">No notifications yet.</div>
    <?php else: ?>
        <div class="notification-list-v4">
            <?php foreach ($items as $n): $unread = !(int)$n['is_read']; ?>
                <button type="button" class="notification-row-v4 notif-clickable <?= $unread ? 'unread' : '' ?>" data-notif="<?= (int)$n['id'] ?>" aria-haspopup="dialog">
                    <span class="notification-symbol"><i class="fa-solid <?= e($icons[$n['type']] ?? 'fa-bell') ?>"></i></span>
                    <span class="notification-copy">
                        <span class="notif-row-head"><b><?= e($n['title']) ?></b><time><?= e(date('M d, Y · h:i A', strtotime($n['created_at']))) ?></time></span>
                        <span class="notif-row-text"><?= e($n['message']) ?></span>
                        <?php if ($unread): ?><span class="unread-tag">Unread</span><?php endif; ?>
                    </span>
                    <i class="fa-solid fa-chevron-right notif-chevron" aria-hidden="true"></i>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<div class="sd-modal" id="notifModal" role="dialog" aria-modal="true" aria-labelledby="notifModalTitle" aria-hidden="true">
    <div class="sd-modal-dialog notif-dialog">
        <button type="button" class="sd-modal-close" data-notif-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <div class="notif-dialog-head"><span class="notification-symbol"><i class="fa-solid fa-bell" data-nm-icon></i></span><div><h2 id="notifModalTitle"></h2><time data-nm-time></time></div></div>
        <p class="notif-dialog-message" data-nm-message></p>
        <div class="notif-dialog-appt" data-nm-appt hidden>
            <h3>Appointment details</h3>
            <dl class="appt-view-grid">
                <div><dt>Date</dt><dd data-nm-date></dd></div>
                <div><dt>Time</dt><dd data-nm-timeval></dd></div>
                <div><dt>Dental Service</dt><dd data-nm-service></dd></div>
                <div><dt>Status</dt><dd><span class="status-pill" data-nm-status></span></dd></div>
                <div class="wide" data-nm-patient-wrap><dt>Patient</dt><dd data-nm-patient></dd></div>
                <div class="wide"><dt>Reason / Concern</dt><dd data-nm-reason></dd></div>
                <div class="wide"><dt>Marked Teeth</dt><dd data-nm-teeth></dd></div>
            </dl>
        </div>
        <div class="appt-view-actions"><a class="btn btn-primary" data-nm-link hidden href="#"><?= e($nc['appointment_label'] ?? 'Open Appointment') ?></a><button type="button" class="btn btn-secondary" data-notif-close>Close</button></div>
    </div>
</div>
<script>
(() => {
    const data = <?= json_encode((object)$payload, $flags) ?>;
    const icons = <?= json_encode($icons) ?>;
    const apptUrl = <?= json_encode($nc['appointment_url'] ?? '') ?>;
    const readApi = <?= json_encode($nc['read_api'] ?? null) ?>;
    const csrf = <?= json_encode(csrf_token()) ?>;
    const defaultLinkLabel = <?= json_encode($nc['appointment_label'] ?? 'Open Appointment') ?>;
    const isPatient = <?= json_encode(($nc['role'] ?? '') === 'patient') ?>;
    const labels = { pending: 'Pending', approved: 'Approved', completed: 'Completed', rejected: 'Declined', cancelled: 'Cancelled' };
    const modal = document.getElementById('notifModal'), q = s => modal.querySelector(s);
    function close() { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); }
    function markRead(n, row) {
        if (n.read || !readApi) return;
        n.read = true;
        row.classList.remove('unread'); row.querySelector('.unread-tag')?.remove();
        fetch(readApi, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ action: 'read', id: n.id, csrf_token: csrf }) })
            .then(r => r.json()).then(d => { if (d && typeof d.unread === 'number') window.sdSetBellBadge?.(d.unread); }).catch(() => {});
    }
    document.querySelectorAll('[data-notif]').forEach(row => row.addEventListener('click', () => {
        const n = data[row.dataset.notif]; if (!n) return;
        q('#notifModalTitle').textContent = n.title;
        q('[data-nm-time]').textContent = n.at;
        q('[data-nm-message]').textContent = n.message;
        q('[data-nm-icon]').className = 'fa-solid ' + (icons[n.type] || 'fa-bell');
        const a = n.appointment, box = q('[data-nm-appt]'), link = q('[data-nm-link]');
        box.hidden = !a;
        if (a) {
            q('[data-nm-date]').textContent = a.date; q('[data-nm-timeval]').textContent = a.time; q('[data-nm-service]').textContent = a.service;
            const st = q('[data-nm-status]'); st.className = 'status-pill ' + a.status; st.textContent = labels[a.status] || a.status;
            q('[data-nm-patient-wrap]').hidden = isPatient; q('[data-nm-patient]').textContent = a.patient;
            q('[data-nm-reason]').textContent = a.reason || 'No reason provided.';
            q('[data-nm-teeth]').textContent = a.teeth && a.teeth.length ? a.teeth.join(', ') : 'None';
        }
        const href = n.link || (a && apptUrl ? apptUrl + encodeURIComponent(a.id) : '');
        link.hidden = !href; if (href) link.href = href;
        link.textContent = n.link_label || defaultLinkLabel;
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
        markRead(n, row);
    }));
    modal.querySelectorAll('[data-notif-close]').forEach(b => b.addEventListener('click', close));
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) close(); });
})();
</script>
    <?php
}
