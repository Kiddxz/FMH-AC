<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);


$uid = (int) $_SESSION['user_id'];
$st = $conn->prepare("SELECT ro.*,CONCAT(u.first_name,' ',u.last_name) dentist_name FROM restoration_orders ro JOIN users u ON u.id=ro.dentist_id WHERE ro.patient_id=? ORDER BY ro.created_at DESC");
$st->bind_param('i', $uid);
$st->execute();
$restorations = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
$st = $conn->prepare("SELECT tp.*,CONCAT(u.first_name,' ',u.last_name) dentist_name FROM treatment_plans tp LEFT JOIN users u ON u.id=tp.dentist_id WHERE tp.patient_id=? AND NOT EXISTS(SELECT 1 FROM restoration_orders ro WHERE ro.treatment_plan_id=tp.id) ORDER BY tp.created_at DESC");
$st->bind_param('i', $uid);
$st->execute();
$plans = $st->get_result()->fetch_all(MYSQLI_ASSOC);
$st->close();
require '../includes/ui.php';
ui_header('Treatment Plans', 'patient');
ui_sidebar('patient', 'treatment_plans.php'); ?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">TREATMENT PLANS</div>
        <h1>My Treatment Plans</h1>
        <div class="muted"></div>
    </div>
</div>
<section class="stat-row">
    <div class="stat"><span>Restoration Plans</span><b><?= count($restorations) ?></b></div>
    <div class="stat"><span>Other Plans</span><b><?= count($plans) ?></b></div>
</section>
<section class="card record-card">
    <div class="card-heading">
        <div>
            <h2>Restoration Plans</h2>
            <p class="muted"></p>
        </div>
    </div>
    <div class="table-wrap">
        <table class="record-table even-cols">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Order No.</th>
                    <th>Selected Teeth</th>
                    <th>Status</th>
                    <th>Dentist</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody><?php foreach ($restorations as $r):
                $teeth = json_decode($r['selected_teeth'] ?: '[]', true); ?>
                    <tr>
                        <td><?= e(date('M d, Y', strtotime($r['created_at']))) ?></td>
                        <td><?= e(ucfirst($r['restoration_type'])) ?></td>
                        <td><?= e($r['order_no'] ?: '—') ?></td>
                        <td><?= e(is_array($teeth) && $teeth ? implode(', ', $teeth) : '—') ?></td>
                        <td><span class="status-pill <?= e(strtolower(str_replace(' ','-',$r['status']))) ?>"><?= e($r['status']) ?></span></td>
                        <td><?= e($r['dentist_name']) ?></td>
                        <td><a class="btn btn-secondary btn-sm" href="restoration_view.php?id=<?= $r['id'] ?>">View</a></td>
                    </tr><?php endforeach;
            if (!$restorations): ?>
                    <tr>
                        <td colspan="7" class="empty">No restoration plan yet.</td>
                    </tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section><?php if ($plans): ?>
    <section class="card record-card">
        <div class="card-heading">
            <div>
                <h2>Other Recommendations</h2>
            </div>
        </div>
        <div class="table-wrap">
            <table class="record-table even-cols">
                <thead>
                    <tr>
                        <th>Procedure</th>
                        <th>Tooth</th>
                        <th>Status</th>
                        <th>Dentist</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($plans as $p): ?>
                        <tr>
                            <td><?= e($p['procedure_name']) ?></td>
                            <td><?= e($p['tooth_number'] ?: '—') ?></td>
                            <td><span class="badge"><?= e($p['status']) ?></span></td>
                            <td><?= e($p['dentist_name'] ?: '—') ?></td>
                            <td><?= e($p['notes'] ?: '—') ?></td>
                        </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section><?php endif; ?><?php ui_footer(); ?>