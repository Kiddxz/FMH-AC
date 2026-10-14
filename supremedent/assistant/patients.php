<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
$q = trim($_GET['q'] ?? '');
$like = '%' . $q . '%';
$st = $conn->prepare("SELECT id,first_name,last_name,email,status,created_at FROM users WHERE role='patient' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) ORDER BY last_name,first_name");
$st->bind_param('sss', $like, $like, $like);
$st->execute();
$rows = $st->get_result();
require '../includes/ui.php';
ui_header('Patients', 'assistant');
ui_sidebar('assistant', 'patients.php'); ?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">PATIENT MANAGEMENT</div>
        <h1>Patients</h1>
        <div class="muted">Search and review registered patients.</div>
    </div>
</div>
<section class="card record-card">
    <form class="searchbar"><input name="q" value="<?= e($q) ?>" placeholder="Search name or email"><button
            class="btn btn-secondary">Search</button></form>
    <div class="table-wrap">
        <table class="record-table">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Registered</th>
                </tr>
            </thead>
            <tbody><?php if ($rows->num_rows === 0): ?><tr><td colspan="4" class="empty">No patients matched your search.</td></tr><?php endif; ?><?php while ($r = $rows->fetch_assoc()): ?>
                    <tr>
                        <td data-label="Patient"><b><?= e($r['first_name'] . ' ' . $r['last_name']) ?></b><br><span class="muted">ID
                                #<?= e($r['id']) ?></span></td>
                        <td data-label="Email"><?= e($r['email']) ?></td>
                        <td data-label="Status"><span class="status-pill <?= $r['status'] === 'active' ? 'approved' : 'rejected' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
                        <td data-label="Registered"><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td>
                    </tr><?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section><?php ui_footer(); ?>
