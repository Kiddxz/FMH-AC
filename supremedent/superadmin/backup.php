<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}require '../includes/ui.php';ui_header('Backup & Recovery','superadmin');ui_sidebar('superadmin','backup.php');?><div class="page-head"><div><div class="eyebrow">DATA PROTECTION</div><h1>Backup & Recovery</h1><div class="muted">Create a SQL backup of the SupremeDent database.</div></div></div><section class="card"><h2>Database Backup</h2><p class="muted">The backup uses the local MySQL server configured for XAMPP.</p><div class="actions"><a class="btn btn-primary" href="backup_download.php">Download SQL Backup</a></div><p class="muted" style="margin-top:14px">Before restoring any database, keep a separate backup of the current database and verify the file.</p></section><?php ui_footer();?>
