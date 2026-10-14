<?php
/**
 * Shared "View appointment" pop-up for the assistant, dentist and super admin portals.
 * Shows what the patient chose when booking: date, time, dental service, reason/concern and the
 * teeth they clicked on the 3D model. Staff can open the full details page or cancel from here.
 *
 * Usage: require this file once, then call openAppointmentView(data) from JS where data comes from
 * sd_appointment_view_data(). $viewModalDetailUrl (default 'appointment_view.php') sets the details link;
 * $viewModalCanCancel enables the Cancel form, which posts action=cancel&appointment_id to the current page.
 */
$viewModalDetailUrl = $viewModalDetailUrl ?? 'appointment_view.php';
$viewModalCanCancel = $viewModalCanCancel ?? true;
?>
<div class="sd-modal" id="apptViewModal" role="dialog" aria-modal="true" aria-labelledby="apptViewTitle" aria-hidden="true">
    <div class="sd-modal-dialog appt-view-dialog">
        <button type="button" class="sd-modal-close" data-appt-view-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        <div class="appt-view-head"><span class="appt-view-icon"><i class="fa-regular fa-calendar-check"></i></span><div><h2 id="apptViewTitle">Appointment</h2><p class="muted" data-av-patient></p></div><span class="status-pill" data-av-status></span></div>
        <dl class="appt-view-grid">
            <div><dt>Date</dt><dd data-av-date></dd></div>
            <div><dt>Time</dt><dd data-av-time></dd></div>
            <div><dt>Dental Service</dt><dd data-av-service></dd></div>
            <div><dt>Dentist</dt><dd data-av-dentist></dd></div>
            <div class="wide"><dt>Reason / Concern</dt><dd data-av-reason></dd></div>
            <div class="wide" data-av-cancel-wrap hidden><dt>Cancellation</dt><dd data-av-cancel></dd></div>
        </dl>
        <div class="appt-view-teeth">
            <div class="appt-view-teeth-head"><b>Teeth marked by the patient</b><span data-av-teeth-list></span></div>
            <div class="mini-arch" data-av-arch></div>
        </div>
        <div class="appt-view-actions">
            <a class="btn btn-secondary" data-av-details href="#">Full Details &amp; 3D Model</a>
            <?php if ($viewModalCanCancel): ?><button type="button" class="btn btn-danger" data-av-cancel-open>Cancel Appointment</button><?php endif; ?>
        </div>
        <?php if ($viewModalCanCancel): ?>
        <form method="post" class="appt-view-cancel" data-av-cancel-form hidden>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" data-av-id>
            <label for="avCancelReason">Reason for cancelling (sent to the patient)</label>
            <textarea id="avCancelReason" name="cancel_reason" maxlength="255" placeholder="e.g. Dentist unavailable, patient request"></textarea>
            <div class="appt-view-actions"><button type="button" class="btn btn-secondary" data-av-cancel-back>Keep Appointment</button><button type="submit" class="btn btn-danger">Confirm Cancel</button></div>
        </form>
        <?php endif; ?>
    </div>
</div>
<script>
(() => {
    const modal = document.getElementById('apptViewModal');
    const q = s => modal.querySelector(s);
    const upper = ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'];
    const lower = ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'];
    const labels = { pending: 'Pending', approved: 'Approved', completed: 'Completed', rejected: 'Declined', cancelled: 'Cancelled' };
    const detailUrl = <?= json_encode($viewModalDetailUrl) ?>;
    function close() { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); }
    window.openAppointmentView = a => {
        if (!a) return;
        q('#apptViewTitle').textContent = a.service || 'Appointment';
        q('[data-av-patient]').textContent = a.patient + ' · Patient #' + a.patient_id;
        const st = q('[data-av-status]'); st.className = 'status-pill ' + a.status; st.textContent = labels[a.status] || a.status;
        q('[data-av-date]').textContent = a.date;
        q('[data-av-time]').textContent = a.time;
        q('[data-av-service]').textContent = a.service;
        q('[data-av-dentist]').textContent = a.dentist || 'Not assigned';
        q('[data-av-reason]').textContent = a.reason || 'No reason provided.';
        q('[data-av-cancel-wrap]').hidden = a.status !== 'cancelled';
        q('[data-av-cancel]').textContent = a.cancel_reason || 'No reason given.';
        const teeth = a.teeth || [];
        q('[data-av-teeth-list]').textContent = teeth.length ? teeth.join(', ') : 'No teeth marked';
        const arch = q('[data-av-arch]'); arch.replaceChildren();
        [upper, lower].forEach(row => { const r = document.createElement('div'); r.className = 'mini-arch-row'; row.forEach(t => { const s = document.createElement('span'); s.textContent = t; if (teeth.includes(t)) s.className = 'on'; r.append(s); }); arch.append(r); });
        q('[data-av-details]').href = detailUrl + '?id=' + encodeURIComponent(a.id);
        const cancelBtn = q('[data-av-cancel-open]'), form = q('[data-av-cancel-form]');
        if (cancelBtn) { cancelBtn.hidden = !['pending', 'approved'].includes(a.status); form.hidden = true; q('[data-av-id]').value = a.id; q('#avCancelReason').value = ''; }
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
    };
    q('[data-appt-view-close]').addEventListener('click', close);
    modal.addEventListener('click', e => { if (e.target === modal) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal.classList.contains('is-open')) close(); });
    const open = q('[data-av-cancel-open]');
    if (open) {
        open.addEventListener('click', () => { q('[data-av-cancel-form]').hidden = false; open.hidden = true; q('#avCancelReason').focus(); });
        q('[data-av-cancel-back]').addEventListener('click', () => { q('[data-av-cancel-form]').hidden = true; open.hidden = false; });
    }
})();
</script>
