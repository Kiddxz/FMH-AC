<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
require '../includes/ui.php';
ui_header('Backup & Recovery', 'superadmin');
ui_sidebar('superadmin', 'backup.php');
?>
<div class="page-head"><div><div class="eyebrow">DATA PROTECTION</div><h1>Backup &amp; Recovery</h1><div class="muted">Create a SQL backup of the SupremeDent database.</div></div></div>
<?php if (isset($_GET['denied'])): ?><div class="flash error">Your password is incorrect. The backup was not created.</div><?php endif; ?>
<?php if (isset($_GET['limited'])): ?><div class="flash error">Too many backup attempts. Please wait a few minutes.</div><?php endif; ?>
<section class="card"><h2>Database Backup</h2>
<p class="muted">The backup contains every patient's personal and dental information. Confirm your password to download it, keep the file in a safe place, and never share it.</p>
<form method="post" action="backup_download.php" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="field"><label for="backupPassword">Your password</label><input type="password" id="backupPassword" name="current_password" maxlength="128" autocomplete="current-password" required></div>
<div class="actions full"><button class="btn btn-primary"><i class="fa-solid fa-download"></i> Download SQL Backup</button></div></form>
<p class="muted" style="margin-top:14px">Before restoring any database, keep a separate backup of the current database and verify the file.</p></section>
<?php ui_footer(); ?>
