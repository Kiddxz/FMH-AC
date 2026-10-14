<?php
/**
 * Simple list of a patient's clinic records: Date · Service · Recorded by · Action.
 * Expects $records, $hidingEnabled, $hideWarning, $listTitle, $listIntro, $emptyText, $notesLabel.
 */
?>
<?php if ($hideWarning): ?>
    <div class="flash error"><?= e($hideWarning) ?></div><?php endif; ?>
<?php if (isset($_GET['hidden'])): ?>
    <div class="flash success">This entry was removed from your view. The clinic's original medical record was not deleted.
    </div><?php endif; ?>
<section class="card record-card">
    <div class="card-heading">
        <div>
            <h2><?= e($listTitle) ?></h2>
            <p class="muted"><?= e($listIntro) ?></p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="record-table even-cols">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Service</th>
                    <th>Recorded</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$records): ?>
                    <tr>
                        <td colspan="4" class="empty"><?= e($emptyText) ?></td>
                    </tr><?php endif; ?>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td data-label="Date"><?= e(date('M d, Y', strtotime($r['record_date']))) ?></td>
                        <td data-label="Service"><b><?= e($r['procedure_name']) ?></b></td>
                        <td data-label="Recorded"><?= e($r['dentist_name'] ?: 'Clinic staff') ?></td>
                        <td data-label="Action">
                            <div class="row-actions"><button type="button" class="btn btn-secondary btn-sm"
                                    aria-expanded="false" aria-controls="record-<?= $r['id'] ?>"
                                    onclick="toggleRecord(this)"><i class="fa-regular fa-eye"></i>
                                    View</button><?php if ($hidingEnabled): ?>
                                    <form method="post"
                                        onsubmit="return confirm('Remove this entry from your patient view? The clinic\'s original record will NOT be deleted.')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden"
                                            name="record_action" value="hide"><input type="hidden" name="record_id"
                                            value="<?= $r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit"
                                            title="Hide from your view; clinical records are preserved"><i
                                                class="fa-regular fa-trash-can"></i> Delete</button></form><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <tr class="record-detail-row" id="record-<?= $r['id'] ?>" hidden>
                        <td colspan="4">
                            <div class="record-detail-grid">
                                <div><b>Diagnosis</b><?= e($r['diagnosis'] ?: 'No diagnosis recorded') ?></div>
                                <div><b>Dentist</b><?= e($r['dentist_name'] ?: 'Not specified') ?></div>
                                <div><b><?= e($notesLabel) ?></b><?= nl2br(e($r['notes'] ?: 'No notes recorded.')) ?></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="record-note"></p>
</section>
<script>function toggleRecord(btn) { const row = document.getElementById(btn.getAttribute('aria-controls')); const showing = row.hidden; row.hidden = !showing; btn.setAttribute('aria-expanded', String(showing)); btn.innerHTML = showing ? '<i class="fa-regular fa-eye-slash"></i> Hide' : '<i class="fa-regular fa-eye"></i> View' }</script>