<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: backup.php'); exit; }
$uid = (int)$_SESSION['user_id'];
// A full export of patient data needs the admin's password again (a hijacked session alone is not enough).
if (!sd_rate_allow($conn, 'backup:' . $uid, 5, 900)) { header('Location: backup.php?limited=1'); exit; }
$st = $conn->prepare('SELECT password FROM users WHERE id=? LIMIT 1');
$st->bind_param('i', $uid);
$st->execute();
$hash = (string)($st->get_result()->fetch_assoc()['password'] ?? '');
$st->close();
if (!password_verify((string)($_POST['current_password'] ?? ''), $hash)) {
    usleep(250000);
    log_activity($conn, $uid, 'Backup refused', 'Backup', 'Wrong password');
    header('Location: backup.php?denied=1');
    exit;
}

$db = supremeDentDbConfig();
$file = 'supremedent_db_' . date('Ymd_His') . '.sql';
$tmp = tempnam(sys_get_temp_dir(), 'sdbak');
// The password goes through the environment, not the command line, so other processes cannot read it.
$cmd = 'mysqldump --single-transaction --host=' . escapeshellarg($db['host']) . ' --user=' . escapeshellarg($db['username'])
    . ' ' . escapeshellarg($db['database']) . ' > ' . escapeshellarg($tmp);
$env = array_merge(getenv(), ['MYSQL_PWD' => (string)$db['password']]);
$proc = @proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
$code = 1;
if (is_resource($proc)) {
    stream_get_contents($pipes[1]); stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($proc);
}
unset($db);
if ($code !== 0 || !is_file($tmp) || filesize($tmp) === 0) {
    @unlink($tmp);
    log_activity($conn, $uid, 'Backup failed', 'Backup', 'mysqldump unavailable');
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '"');
    echo "-- SupremeDent database backup\n-- mysqldump is not available on this server. Export the database with phpMyAdmin instead.\n";
    exit;
}
log_activity($conn, $uid, 'Created database backup', 'Backup', $file);
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
while (ob_get_level() > 0) ob_end_clean(); // stream the file instead of holding it in memory
readfile($tmp);
@unlink($tmp);
