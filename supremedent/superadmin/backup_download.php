<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);
 start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){http_response_code(403);exit('Forbidden');}$file='supremedent_db_'.date('Ymd_His').'.sql';$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.$file;$mysqldump='mysqldump';$cmd=escapeshellcmd($mysqldump).' --host=localhost --user='.escapeshellarg('root').' --password='.escapeshellarg(''). ' '.escapeshellarg('supremedent_db').' > '.escapeshellarg($tmp);@exec($cmd,$out,$code);if($code!==0||!is_file($tmp)){ $sql="-- SupremeDent database backup\n-- Export using phpMyAdmin if mysqldump is unavailable.\n";header('Content-Type:text/plain');header('Content-Disposition:attachment; filename="'.$file.'"');echo $sql;exit;}log_activity($conn,$_SESSION['user_id'],'Created database backup','Backup',$file);header('Content-Type:application/sql');header('Content-Disposition:attachment; filename="'.$file.'"');header('Content-Length:'.filesize($tmp));readfile($tmp);@unlink($tmp);
