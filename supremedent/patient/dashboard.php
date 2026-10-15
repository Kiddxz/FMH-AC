<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);
$uid = (int) $_SESSION['user_id'];
function pc($c, $q, $id)
{
    $s = $c->prepare($q);
    $s->bind_param('i', $id);
    $s->execute();
    $v = (int) ($s->get_result()->fetch_assoc()['c'] ?? 0);
    $s->close();
    return $v;
}
function patient_rows(mysqli $c, string $q, int $id): array
{
    $s = $c->prepare($q);
    if (!$s) return [];
    $s->bind_param('i', $id);
    $s->execute();
    $rows = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
    return $rows;
}
$up = pc($conn, "SELECT COUNT(*) c FROM appointments WHERE patient_id=? AND appointment_date>=CURDATE() AND status IN ('pending','approved')", $uid);
$dr = pc($conn, 'SELECT COUNT(*) c FROM dental_records WHERE patient_id=?', $uid);
$tp = pc($conn, "SELECT COUNT(*) c FROM treatment_plans WHERE patient_id=? AND status NOT IN ('Completed','Cancelled')", $uid)
    + pc($conn, "SELECT COUNT(*) c FROM restoration_orders WHERE patient_id=? AND status NOT IN ('Completed','Cancelled')", $uid);
$complete = patient_profile_is_complete($conn, $uid);
$profile = patient_profile_data($conn, $uid);

// Recent activity: every row comes from something the patient or the clinic actually did.
$activity = [];
$add = static function (string $icon, string $title, string $text, ?string $when) use (&$activity) {
    $ts = $when ? strtotime($when) : false;
    if ($ts === false) return;
    $activity[] = ['icon' => $icon, 'title' => $title, 'text' => $text, 'ts' => $ts];
};
$account = patient_rows($conn, 'SELECT created_at FROM users WHERE id=? LIMIT 1', $uid);
if ($account) $add('check', 'Account created', 'Welcome to the SupremeDent patient portal.', $account[0]['created_at']);
if (!empty($profile['phone_verified_at'])) $add('phone', 'Contact number verified', 'Your mobile number was verified by OTP.', $profile['phone_verified_at']);
foreach (patient_rows($conn, 'SELECT title,message,type,created_at FROM notifications WHERE patient_id=? ORDER BY created_at DESC,id DESC LIMIT 12', $uid) as $n) {
    $icon = $n['type'] === 'appointment' ? (stripos($n['title'], 'declin') !== false || stripos($n['title'], 'reject') !== false ? 'declined' : 'calendar') : ($n['type'] === 'treatment' ? 'plan' : 'bell');
    $add($icon, $n['title'], $n['message'], $n['created_at']);
}
foreach (patient_rows($conn, 'SELECT procedure_name,dentist_name,record_date,created_at FROM dental_records WHERE patient_id=? ORDER BY created_at DESC,id DESC LIMIT 6', $uid) as $r) {
    $add('record', 'Dental record added', $r['procedure_name'] . ($r['dentist_name'] ? ' · recorded by ' . $r['dentist_name'] : ''), $r['created_at'] ?: $r['record_date']);
}
foreach (patient_rows($conn, "SELECT restoration_type,status,created_at FROM restoration_orders WHERE patient_id=? ORDER BY created_at DESC LIMIT 4", $uid) as $r) {
    $add('plan', ucfirst($r['restoration_type']) . ' restoration plan', 'Your dentist prepared a treatment plan (' . $r['status'] . ').', $r['created_at']);
}
foreach (patient_rows($conn, "SELECT procedure_name,status,created_at FROM treatment_plans WHERE patient_id=? ORDER BY created_at DESC LIMIT 4", $uid) as $r) {
    $add('plan', 'Treatment plan: ' . $r['procedure_name'], 'Status: ' . $r['status'] . '.', $r['created_at']);
}
$marks = patient_rows($conn, "SELECT COUNT(*) c, MAX(updated_at) last_at FROM patient_tooth_reports WHERE patient_id=? AND concern_type IN ('Healthy','Caries','Filled','Missing','Fractured','Marked')", $uid);
if ($marks && (int)$marks[0]['c'] > 0) $add('tooth', 'Dental chart updated', (int)$marks[0]['c'] . ' marked ' . ((int)$marks[0]['c'] === 1 ? 'tooth' : 'teeth') . ' saved for dentist review.', $marks[0]['last_at']);
foreach (patient_rows($conn, "SELECT m.subject,m.created_at,CONCAT(u.first_name,' ',u.last_name) n FROM user_messages m JOIN users u ON u.id=m.recipient_id WHERE m.sender_id=? ORDER BY m.id DESC LIMIT 3", $uid) as $m) {
    $add('message', 'Message sent to ' . $m['n'], $m['subject'], $m['created_at']);
}
usort($activity, static fn($a, $b) => $b['ts'] <=> $a['ts']);
$activity = array_slice($activity, 0, 5);
$missing = [];
if (!$complete) {
    if (trim((string)$profile['phone']) === '' || empty($profile['phone_verified_at'])) $missing[] = 'verified contact number';
    if (trim((string)$profile['birthday']) === '') $missing[] = 'birthday';
    if (trim((string)$profile['gender']) === '') $missing[] = 'gender';
    if (trim((string)$profile['address']) === '') $missing[] = 'address';
}
function activity_when(int $ts): string
{
    if (date('Y-m-d', $ts) === date('Y-m-d')) return 'Today';
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) return 'Yesterday';
    return date('Y', $ts) === date('Y') ? date('M j', $ts) : date('M j, Y', $ts);
}
$activityIcons = ['check' => 'fa-check', 'phone' => 'fa-mobile-screen', 'calendar' => 'fa-calendar-check', 'declined' => 'fa-calendar-xmark', 'record' => 'fa-file-medical', 'plan' => 'fa-clipboard-list', 'tooth' => 'fa-tooth', 'message' => 'fa-paper-plane', 'bell' => 'fa-bell', 'warn' => 'fa-exclamation'];

// Calendar data: the patient's own appointments, plus the times other patients already booked
// (date and time only, no names) so the patient can see which schedules are taken.
$calendarAppointments = [];
foreach (patient_rows($conn, "SELECT appointment_date,DATE_FORMAT(appointment_time,'%H:%i') appointment_time,appointment_type,status FROM appointments WHERE patient_id=? AND status IN ('pending','approved','completed','rejected','cancelled') ORDER BY appointment_date,appointment_time", $uid) as $a) {
    $calendarAppointments[] = $a;
}
$bookedByOthers = patient_rows($conn, "SELECT appointment_date,DATE_FORMAT(appointment_time,'%H:%i') appointment_time FROM appointments WHERE patient_id<>? AND status IN ('pending','approved') AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 120 DAY) ORDER BY appointment_date,appointment_time", $uid);
require '../includes/ui.php';
ui_header('Patient Dashboard', 'patient');
ui_sidebar('patient', 'dashboard.php'); ?>
<section class="dashboard-hero patient-hero">
    <div>
        <div class="eyebrow">PATIENT PORTAL</div>
        <h1>Hello, <?= e($_SESSION['first_name'] ?? 'Patient') ?>.</h1>
        <p>Book appointments, share your dental condition map, and review records prepared by your clinic.</p>
    </div>
    <div class="dashboard-date"><?= e(date('l')) ?><b><?= e(date('M d')) ?></b><span><?= e(date('Y')) ?></span></div>
</section><?php if (!$complete): ?>
    <div class="simple-alert">
        <div><b>Complete your profile before booking.</b><span>Add your contact information, birthday, gender, and
                address.</span></div><a class="btn btn-primary" href="profile.php">Complete Profile</a>
    </div><?php endif; ?>
<div class="patient-dash-grid">
    <section class="card activity-card">
        <div class="activity-head"><h2>Recent activity</h2><a href="notifications.php">See all</a></div>
        <ul class="activity-list">
            <?php if ($missing): ?>
                <li><span class="activity-icon warn"><i class="fa-solid fa-exclamation"></i></span><div><b>Profile needs a few details</b><span><?= e(ucfirst(implode(', ', array_slice($missing, 0, -1)) . (count($missing) > 1 ? ', and ' : '') . end($missing))) ?> <?= count($missing) > 1 ? 'are' : 'is' ?> still missing.</span></div><time>Today</time></li>
            <?php endif; ?>
            <?php foreach ($activity as $a): ?>
                <li><span class="activity-icon <?= e($a['icon']) ?>"><i class="fa-solid <?= e($activityIcons[$a['icon']] ?? 'fa-circle') ?>"></i></span><div><b><?= e($a['title']) ?></b><span><?= e($a['text']) ?></span></div><time datetime="<?= e(date('c', $a['ts'])) ?>"><?= e(activity_when($a['ts'])) ?></time></li>
            <?php endforeach; ?>
            <?php if (count($activity) + ($missing ? 1 : 0) < 4): ?>
                <li class="activity-placeholder"><span class="activity-icon empty"></span><div><b>Appointments, records and plans will show up here</b><span>Each update from your clinic appears in order.</span></div></li>
            <?php endif; ?>
        </ul>
    </section>
    <div class="dash-stat-stack">
        <a href="appointments.php" class="metric-card compact"><span>Upcoming Appointments</span><b><?= $up ?></b><small>Pending or approved</small></a>
        <a href="dental_records.php" class="metric-card compact"><span>Dental Records</span><b><?= $dr ?></b><small>Recorded procedures</small></a>
        <a href="treatment_plans.php" class="metric-card compact"><span>Active Treatment Plans</span><b><?= $tp ?></b><small>Recommended care</small></a>
    </div>
</div>
<section class="card mini-calendar-card" id="patientCalendar">
    <div class="card-heading"><div><h2>My Calendar</h2><p class="muted">Your appointments and the times other patients already booked. Click a date to see the details.</p></div><a class="btn btn-book" href="appointments.php?new=1"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a></div>
    <div class="mini-calendar-layout">
        <div class="mini-calendar">
            <div class="mini-calendar-head"><button type="button" class="calendar-nav" data-cal-prev aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><strong data-cal-title></strong><button type="button" class="calendar-nav" data-cal-next aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div>
            <div class="mini-calendar-week"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
            <div class="mini-calendar-grid" data-cal-grid></div>
        </div>
        <aside class="mini-calendar-detail" data-cal-detail aria-live="polite"></aside>
    </div>
    <div class="mini-cal-legend"><span><i class="dot approved"></i> Your appointment</span><span><i class="dot cancelled"></i> Cancelled</span><span><i class="dot booked-other"></i> Booked by another patient</span>
    </div>
</section>
<script>
(() => {
    const items = <?= json_encode($calendarAppointments, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const others = <?= json_encode($bookedByOthers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const root = document.getElementById('patientCalendar');
    const grid = root.querySelector('[data-cal-grid]'), title = root.querySelector('[data-cal-title]'), detail = root.querySelector('[data-cal-detail]');
    const pad = n => String(n).padStart(2, '0'), key = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const label = { pending: 'Pending', approved: 'Approved', completed: 'Completed', rejected: 'Declined', cancelled: 'Cancelled' };
    const today = key(new Date());
    let month = new Date(); month = new Date(month.getFullYear(), month.getMonth(), 1);
    let selected = today;
    const fmtTime = t => { const [h, m] = t.split(':').map(Number); return new Date(2000, 0, 1, h, m).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); };
    function showDetail() {
        const list = items.filter(a => a.appointment_date === selected);
        const taken = others.filter(a => a.appointment_date === selected);
        const d = new Date(selected + 'T12:00:00');
        detail.replaceChildren();
        const h = document.createElement('h3'); h.textContent = d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
        detail.append(h);
        if (!list.length && !taken.length) { const p = document.createElement('p'); p.className = 'muted'; p.textContent = d.getDay() === 0 ? 'The clinic is closed on Sundays.' : 'No appointments on this date.'; detail.append(p); return; }
        list.forEach(a => {
            const row = document.createElement('div'); row.className = 'mini-cal-appt';
            const b = document.createElement('b'); b.textContent = fmtTime(a.appointment_time) + ' · ' + a.appointment_type;
            const s = document.createElement('span'); s.className = 'status-pill ' + a.status; s.textContent = label[a.status] || a.status;
            row.append(b, s); detail.append(row);
        });
        if (taken.length) {
            const t = document.createElement('p'); t.className = 'mini-cal-taken-title'; t.textContent = 'Already booked by other patients'; detail.append(t);
            const wrap = document.createElement('div'); wrap.className = 'mini-cal-taken';
            taken.forEach(a => { const c = document.createElement('span'); c.textContent = fmtTime(a.appointment_time); wrap.append(c); });
            detail.append(wrap);
        }
    }
    function render() {
        title.textContent = month.toLocaleDateString([], { month: 'long', year: 'numeric' });
        grid.replaceChildren();
        for (let i = 0; i < month.getDay(); i++) grid.append(Object.assign(document.createElement('span'), { className: 'mini-day blank' }));
        const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        for (let day = 1; day <= days; day++) {
            const date = new Date(month.getFullYear(), month.getMonth(), day), k = key(date), list = items.filter(a => a.appointment_date === k), takenCount = others.filter(a => a.appointment_date === k).length;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'mini-day' + (k === today ? ' today' : '') + (k === selected ? ' selected' : '') + (date.getDay() === 0 ? ' sunday' : '') + (list.length ? ' has-appt' : '');
            btn.textContent = day;
            if (list.length || takenCount) { const dots = document.createElement('span'); dots.className = 'mini-dots'; list.slice(0, 3).forEach(a => { const i = document.createElement('i'); i.className = 'dot ' + a.status; dots.append(i); }); if (takenCount) { const i = document.createElement('i'); i.className = 'dot booked-other'; i.title = takenCount + ' booked by other patients'; dots.append(i); } btn.append(dots); }
            btn.setAttribute('aria-label', date.toLocaleDateString([], { month: 'long', day: 'numeric' }) + ', ' + list.length + ' of your appointment' + (list.length === 1 ? '' : 's') + (takenCount ? ', ' + takenCount + ' booked by others' : ''));
            btn.addEventListener('click', () => { selected = k; render(); showDetail(); });
            grid.append(btn);
        }
    }
    root.querySelector('[data-cal-prev]').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() - 1, 1); render(); });
    root.querySelector('[data-cal-next]').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() + 1, 1); render(); });
    render(); showDetail();
})();
</script>
<?php ui_footer(); ?>
