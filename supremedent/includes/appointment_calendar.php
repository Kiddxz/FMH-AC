<?php
/**
 * Full-width appointment calendar for the assistant and dentist portals.
 * Patients are listed inside each date; clicking a date (or a patient) opens a pop-up
 * with that day's schedule and a "View" action for each appointment.
 * Inputs: $calendarRole ('assistant'|'dentist'), $calendarEvents, $calendarBlocks.
 */
$calendarRole = ($calendarRole ?? '') === 'dentist' ? 'dentist' : 'assistant';
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
?>
<section class="card full-calendar-card" id="fullCalendar" aria-label="Appointment calendar">
    <div class="full-cal-toolbar">
        <div class="full-cal-nav"><button type="button" class="calendar-nav" data-fc-prev aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></button><h2 data-fc-title aria-live="polite"></h2><button type="button" class="calendar-nav" data-fc-next aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></button></div>
        <div class="full-cal-tools"><label class="full-cal-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input type="search" data-fc-search placeholder="Search patient or service" aria-label="Search appointments"></label><button type="button" class="btn btn-secondary btn-sm" data-fc-today>Today</button><button type="button" class="btn btn-secondary btn-sm" data-fc-refresh>Refresh</button></div>
    </div>
    <div class="full-cal-week"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
    <div class="full-cal-grid" data-fc-grid></div>
</section>

<div class="sd-modal" id="dayModal" role="dialog" aria-modal="true" aria-labelledby="dayModalTitle" aria-hidden="true">
    <div class="sd-modal-dialog sd-modal-wide day-modal">
        <button type="button" class="sd-modal-close" data-close-day aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <div class="card-heading"><div><h2 id="dayModalTitle">Appointments</h2><p class="muted" data-day-count></p></div></div>
        <div data-day-blocks></div>
        <div class="table-wrap"><table class="record-table day-table"><thead><tr><th>Date &amp; Time</th><th>Patient</th><th>Service</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead><tbody data-day-rows></tbody></table></div>
    </div>
</div>
<script>
(() => {
    'use strict';
    const role = <?= json_encode($calendarRole) ?>;
    let events = <?= json_encode(array_values($calendarEvents ?? []), $jsonFlags) ?: '[]' ?>;
    let blocks = <?= json_encode(array_values($calendarBlocks ?? []), $jsonFlags) ?: '[]' ?>;
    const csrf = <?= json_encode(csrf_token(), $jsonFlags) ?>;
    const myId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    const teethOf = e => { try { const t = JSON.parse(e.marked_teeth || '[]'); return Array.isArray(t) ? t.map(String) : []; } catch (_) { return []; } };
    const viewData = e => ({ id: e.id, patient: [e.first_name, e.last_name].filter(Boolean).join(' '), patient_id: e.patient_id, date: new Date(e.appointment_date + 'T12:00:00').toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }), time: time(e.appointment_time), service: e.appointment_type, reason: e.reason || '', status: e.status, teeth: teethOf(e), dentist: [e.dentist_first, e.dentist_last].filter(Boolean).join(' '), cancel_reason: e.cancel_reason || '' });
    const root = document.getElementById('fullCalendar'), modal = document.getElementById('dayModal');
    const grid = root.querySelector('[data-fc-grid]'), search = root.querySelector('[data-fc-search]');
    const pad = n => String(n).padStart(2, '0'), key = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const statusLabel = { pending: 'Pending', approved: 'Approved', completed: 'Completed', rejected: 'Declined', cancelled: 'Cancelled' };
    const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; };
    const name = e => [e.first_name, e.last_name].filter(Boolean).join(' ');
    const shortName = e => (e.first_name || '') + (e.last_name ? ' ' + e.last_name.charAt(0) + '.' : '');
    const time = t => { const [h, m] = String(t).split(':').map(Number); return new Date(2000, 0, 1, h, m).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); };
    let month = new Date(); month = new Date(month.getFullYear(), month.getMonth(), 1);
    let openDate = null;
    const matches = e => { const q = search.value.trim().toLowerCase(); return !q || [e.first_name, e.last_name, e.appointment_type, e.reason, e.email].join(' ').toLowerCase().includes(q); };
    const dayEvents = k => events.filter(e => e.appointment_date === k && matches(e)).sort((a, b) => String(a.appointment_time).localeCompare(String(b.appointment_time)));
    const dayBlocks = k => blocks.filter(b => b.unavailable_date === k);

    function render() {
        root.querySelector('[data-fc-title]').textContent = month.toLocaleDateString([], { month: 'long', year: 'numeric' });
        grid.replaceChildren();
        const today = key(new Date());
        for (let i = 0; i < month.getDay(); i++) grid.append(el('div', 'fc-day blank'));
        const days = new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate();
        for (let d = 1; d <= days; d++) {
            const date = new Date(month.getFullYear(), month.getMonth(), d), k = key(date), rows = dayEvents(k), absences = dayBlocks(k), sunday = date.getDay() === 0;
            const cell = el('button', 'fc-day' + (sunday ? ' sunday' : '') + (k === today ? ' today' : '') + (rows.length ? ' has-events' : ''));
            cell.type = 'button';
            cell.setAttribute('aria-label', date.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' }) + ', ' + rows.length + ' patient' + (rows.length === 1 ? '' : 's'));
            const head = el('div', 'fc-day-head');
            head.append(el('b', null, String(d)));
            if (rows.length) head.append(el('span', 'fc-count', String(rows.length)));
            cell.append(head);
            if (sunday) cell.append(el('span', 'fc-closed', 'Closed'));
            if (absences.length) cell.append(el('span', 'fc-unavailable', 'Unavailable'));
            rows.slice(0, 3).forEach(e => cell.append(el('span', 'fc-chip ' + e.status, time(e.appointment_time) + ' ' + shortName(e))));
            if (rows.length > 3) cell.append(el('small', 'fc-more', '+' + (rows.length - 3) + ' more'));
            cell.addEventListener('click', () => openDay(k));
            grid.append(cell);
        }
    }

    function actionCell(e) {
        const td = el('td');
        td.dataset.label = 'Actions';
        const wrap = el('div', 'row-actions');
        const view = el('a', 'btn btn-primary btn-sm', 'View');
        view.href = 'appointment_view.php?id=' + encodeURIComponent(e.id);
        // Opens the shared View pop-up (date, time, service, concern, marked teeth) when the page has it.
        view.addEventListener('click', ev => { if (typeof window.openAppointmentView !== 'function') return; ev.preventDefault(); closeDay(); window.openAppointmentView(viewData(e)); });
        wrap.append(view);
        if (role === 'dentist' && e.status === 'approved' && (!e.dentist_id || Number(e.dentist_id) === myId)) {
            const form = el('form'); form.method = 'post';
            [['csrf_token', csrf], ['action', 'complete'], ['appointment_id', e.id]].forEach(([n, v]) => { const i = el('input'); i.type = 'hidden'; i.name = n; i.value = v; form.append(i); });
            const done = el('button', 'btn btn-secondary btn-sm', 'Mark completed'); done.type = 'submit';
            form.append(done); wrap.append(form);
        }
        td.append(wrap);
        return td;
    }
    function openDay(k) {
        openDate = k;
        const date = new Date(k + 'T12:00:00'), rows = dayEvents(k), absences = dayBlocks(k);
        document.getElementById('dayModalTitle').textContent = date.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
        modal.querySelector('[data-day-count]').textContent = (date.getDay() === 0 ? 'The clinic is closed on Sundays. ' : '') + rows.length + ' patient' + (rows.length === 1 ? '' : 's') + ' scheduled on this date.';
        const blockBox = modal.querySelector('[data-day-blocks]');
        blockBox.replaceChildren();
        absences.forEach(b => blockBox.append(el('div', 'day-block-note', (b.staff_name ? b.staff_name + ' unavailable' : 'You marked this date unavailable') + ' · ' + (+b.all_day ? 'All day' : String(b.start_time).slice(0, 5) + '–' + String(b.end_time).slice(0, 5)) + (b.reason ? ' · ' + b.reason : ''))));
        const body = modal.querySelector('[data-day-rows]');
        body.replaceChildren();
        if (!rows.length) { const tr = el('tr'); const td = el('td', 'empty', 'No appointments scheduled for this date.'); td.colSpan = 6; tr.append(td); body.append(tr); }
        rows.forEach(e => {
            const tr = el('tr');
            const cells = [['Date & Time', date.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + time(e.appointment_time)], ['Patient', name(e)], ['Service', e.appointment_type], ['Reason', e.reason || '—']];
            cells.forEach(([label, text]) => { const td = el('td', null, text); td.dataset.label = label; tr.append(td); });
            const st = el('td'); st.dataset.label = 'Status'; st.append(el('span', 'status-pill ' + e.status, statusLabel[e.status] || e.status)); tr.append(st);
            tr.append(actionCell(e));
            body.append(tr);
        });
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
    }
    function closeDay() { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); openDate = null; }
    modal.querySelector('[data-close-day]').addEventListener('click', closeDay);
    modal.addEventListener('click', e => { if (e.target === modal) closeDay(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) closeDay(); });
    root.querySelector('[data-fc-prev]').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() - 1, 1); render(); });
    root.querySelector('[data-fc-next]').addEventListener('click', () => { month = new Date(month.getFullYear(), month.getMonth() + 1, 1); render(); });
    root.querySelector('[data-fc-today]').addEventListener('click', () => { const n = new Date(); month = new Date(n.getFullYear(), n.getMonth(), 1); render(); openDay(key(n)); });
    search.addEventListener('input', render);
    let refreshing = false;
    async function refresh() {
        if (refreshing) return; refreshing = true;
        const btn = root.querySelector('[data-fc-refresh]'); btn.disabled = true;
        try {
            const res = await fetch('../api/calendar_feed.php?portal=' + encodeURIComponent(role), { credentials: 'same-origin', cache: 'no-store' });
            const data = await res.json();
            if (Array.isArray(data.events) && Array.isArray(data.blocks)) { events = data.events; blocks = data.blocks; render(); if (openDate) openDay(openDate); }
        } catch (_) { btn.title = 'Could not refresh. Try again.'; }
        finally { refreshing = false; btn.disabled = false; }
    }
    root.querySelector('[data-fc-refresh]').addEventListener('click', refresh);
    setInterval(() => { if (!document.hidden && !modal.classList.contains('is-open')) refresh(); }, 8000);
    render();
})();
</script>
