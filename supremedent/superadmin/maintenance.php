<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}require '../includes/ui.php';ui_header('Maintenance','superadmin');ui_sidebar('superadmin','maintenance.php');?><div class="page-head"><div><div class="eyebrow">SYSTEM</div><h1>Maintenance</h1><div class="muted">Simple maintenance checklist for the local system.</div></div></div><section class="card"><div class="grid grid-2"><div class="stat"><b>Database</b><span class="muted">Check MySQL/MariaDB in XAMPP before using the system.</span></div><div class="stat"><b>Files</b><span class="muted">Keep regular copies of the project folder and database.</span></div><div class="stat"><b>Security</b><span class="muted">Use strong passwords and keep role access restricted.</span></div><div class="stat"><b>Updates</b><span class="muted">Test changes locally before deployment.</span></div></div></section><?php ui_footer();?>
