/*
 * SupremeDent realtime updates (revision 2).
 * Polls ../api/live.php every few seconds. Badge counts update in place; when the page's data changed
 * (new booking, cancellation, notification, record, plan...) the page refreshes itself while the user is
 * idle, keeping the scroll position. If the user is typing, has a pop-up open, or is on an editing page,
 * a small "New updates" bar is shown instead so no unsaved work is lost.
 */
(() => {
    'use strict';
    const script = document.currentScript;
    if (!script) return;
    let version = script.dataset.version || '';
    const api = script.dataset.api || '../api/live.php';
    const POLL_MS = 5000;
    const page = location.pathname.split('/').pop() || '';
    // Editing pages never reload by themselves; they show the update bar instead.
    const manualPages = ['profile.php', 'change_password.php', 'register_patient.php', 'chart_editor.php', 'charts.php', 'restoration_form.php',
        'odontogram.php', 'dental_chart.php', 'Treatment_plan.php', 'website_content.php', 'users.php', 'create_dentist.php', 'create_assistant.php',
        'roles.php', 'maintenance.php', 'backup.php', 'mail_test.php', 'dental_management.php', 'save_odontogram.php'];
    const manual = manualPages.includes(page);
    let dirty = false, lastInput = 0, pending = false, timer = null, bar = null;

    document.addEventListener('input', e => { if (e.target.closest('form') || e.target.matches('input,textarea,select')) { dirty = true; lastInput = Date.now(); } }, true);
    document.addEventListener('change', e => { if (e.target.closest('form')) { dirty = true; lastInput = Date.now(); } }, true);
    document.addEventListener('submit', () => { dirty = false; }, true);

    function busy() {
        if (dirty || Date.now() - lastInput < 8000) return true;
        if (document.querySelector('.sd-modal.is-open, .sms-otp-modal.is-open, .profile-booking-modal.is-open, .signup-terms-modal.is-open, .service-modal.is-open')) return true;
        const chat = document.getElementById('sdChatPanel');
        if (chat && !chat.hidden) return true;
        const a = document.activeElement;
        if (a && a.matches && a.matches('input:not([type=button]):not([type=submit]),textarea,select,[contenteditable="true"]')) return true;
        const sel = window.getSelection && window.getSelection();
        if (sel && String(sel).trim()) return true;
        return false;
    }
    function setBell(n) {
        const badge = document.getElementById('sdBellBadge');
        if (!badge) return;
        badge.hidden = !n;
        badge.textContent = n > 99 ? '99+' : String(n);
    }
    window.sdSetBellBadge = setBell;

    function showBar() {
        if (bar) return;
        bar = document.createElement('div');
        bar.className = 'sd-live-bar';
        bar.setAttribute('role', 'status');
        bar.innerHTML = '<i class="fa-solid fa-rotate"></i><span>New updates are available.</span>';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'Refresh';
        btn.addEventListener('click', reload);
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'sd-live-close';
        close.setAttribute('aria-label', 'Dismiss');
        close.innerHTML = '&times;';
        close.addEventListener('click', () => { bar.remove(); bar = null; });
        bar.append(btn, close);
        document.body.append(bar);
    }
    function reload() {
        try { sessionStorage.setItem('sd-live-scroll', JSON.stringify({ path: location.pathname + location.search, y: window.scrollY })); } catch (_) {}
        location.reload();
    }
    function applyPending() {
        if (!pending) return;
        if (manual || dirty) { showBar(); return; }
        if (busy()) return; // try again on the next tick
        reload();
    }
    async function poll() {
        if (document.hidden) return;
        try {
            const res = await fetch(api, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
            if (res.status === 401) { location.href = '../auth/login.php'; return; }
            const data = await res.json();
            if (!data || !data.ok) return;
            setBell(Number(data.notifications) || 0);
            if (version && data.version && data.version !== version) pending = true;
            if (!version) version = data.version;
            applyPending();
        } catch (_) { /* offline for a moment; try again next tick */ }
    }

    // Restore the scroll position after an automatic refresh.
    try {
        const saved = JSON.parse(sessionStorage.getItem('sd-live-scroll') || 'null');
        sessionStorage.removeItem('sd-live-scroll');
        if (saved && saved.path === location.pathname + location.search) window.addEventListener('load', () => window.scrollTo(0, saved.y || 0));
    } catch (_) {}

    timer = setInterval(poll, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
})();
