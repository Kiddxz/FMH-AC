<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;} $db=$conn->ping();$php=PHP_VERSION;$dbName=$conn->query('SELECT DATABASE()')->fetch_row()[0];require '../includes/ui.php';ui_header('System Monitoring','superadmin');ui_sidebar('superadmin','monitoring.php');?><div class="page-head"><div><div class="eyebrow">SYSTEM</div><h1>System Monitoring</h1><div class="muted">Basic health information for local XAMPP deployment.</div></div></div><section class="grid grid-3"><div class="stat">Database<b><?= $db?'Online':'Offline' ?></b></div><div class="stat">PHP Version<b><?=e($php)?></b></div><div class="stat">Database<b><?=e($dbName)?></b></div></section><section class="card"><h2>Environment</h2><p class="muted">SupremeDent is designed for PHP + MySQL/MariaDB on XAMPP.</p></section><?php ui_footer();?>
