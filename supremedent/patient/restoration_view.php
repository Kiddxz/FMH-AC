<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'patient', false);

start_secure_session();
require_once '../config/database.php';
if (($_SESSION['role'] ?? '') !== 'patient') {
    header('Location: ../auth/login.php');
    exit;
}
$uid = (int) $_SESSION['user_id'];
$id = (int) ($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT ro.*,CONCAT(d.first_name,' ',d.last_name) dentist_name FROM restoration_orders ro JOIN users d ON d.id=ro.dentist_id WHERE ro.id=? AND ro.patient_id=? LIMIT 1");
$st->bind_param('ii', $id, $uid);
$st->execute();
$o = $st->get_result()->fetch_assoc();
$st->close();
if (!$o) {
    header('Location: treatment_plans.php');
    exit;
}
$teeth = json_decode($o['selected_teeth'] ?: '[]', true) ?: [];
$work = json_decode($o['work_options'] ?: '[]', true) ?: [];
$mat = json_decode($o['teeth_material_options'] ?: '[]', true) ?: [];
$stain = json_decode($o['staining_options'] ?: '[]', true) ?: [];
require '../includes/ui.php';
ui_header('Treatment Plan', 'patient');
ui_sidebar('patient', 'treatment_plans.php'); ?>
<div class="page-head">
    <div>
        <div class="eyebrow">RESTORATION PLAN</div>
        <h1><?= e(ucfirst($o['restoration_type'])) ?> Restoration</h1>
        <div class="muted">Prepared by <?= e($o['dentist_name']) ?>.</div>
    </div><a class="btn btn-secondary" href="treatment_plans.php">Back to Plans</a>
</div>
<section class="card">
    <div class="restoration-summary">
        <div><b>Order No.</b><span><?= e($o['order_no'] ?: '—') ?></span></div>
        <div><b>Date Sent</b><span><?= e($o['date_sent'] ?: '—') ?></span></div>
        <div><b>Date Due</b><span><?= e($o['date_due'] ?: '—') ?></span></div>
        <div><b>Status</b><span><?= e($o['status']) ?></span></div>
    </div>
    <div class="form-section-title">Selected Teeth</div>
    <p><?= e($teeth ? implode(', ', $teeth) : 'None') ?></p>
    <div class="form-section-title">Type of Work</div>
    <p><?= e($work ? implode(', ', $work) : 'None') ?></p>
    <div class="form-section-title">Materials / Design</div>
    <p><?= e($mat ? implode(', ', $mat) : 'None') ?></p><?php if ($stain): ?>
        <div class="form-section-title">Staining</div>
        <p><?= e(implode(', ', $stain)) ?></p><?php endif; ?>
    <div class="form-section-title">Instructions</div>
    <div class="message-body"><?= e($o['instructions'] ?: 'No additional instructions.') ?></div>
</section><?php ui_footer(); ?>