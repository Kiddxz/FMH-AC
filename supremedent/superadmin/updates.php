<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}require '../includes/ui.php';ui_header('System Updates','superadmin');ui_sidebar('superadmin','updates.php');?><div class="page-head"><div><div class="eyebrow">SYSTEM</div><h1>System Updates</h1><div class="muted">Track the installed version and planned improvements.</div></div></div><section class="card"><div class="grid grid-2"><div class="stat">Current Version<b>2.0</b></div><div class="stat">Environment<b>XAMPP Local</b></div></div><p class="muted">For safety, code updates should be copied and tested manually rather than replacing server files automatically.</p></section><?php ui_footer();?>
