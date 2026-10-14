<?php
/** Patient-only list hiding. The dentist's original clinical record remains intact. */
require_once __DIR__.'/../config/security.php';
if(session_status()!==PHP_SESSION_ACTIVE) start_secure_session();
function patient_record_visibility(mysqli $conn,int $uid):array{
    try{
        $conn->query("CREATE TABLE IF NOT EXISTS patient_hidden_records (patient_id INT NOT NULL,record_id INT NOT NULL,hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY(patient_id,record_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['record_action']??'')==='hide'){
            if(!csrf_valid($_POST['csrf_token']??null)) return [true,'Security check failed. Please refresh and try again.'];
            $id=(int)($_POST['record_id']??0);
            $check=$conn->prepare('SELECT id FROM dental_records WHERE id=? AND patient_id=? LIMIT 1');$check->bind_param('ii',$id,$uid);$check->execute();$exists=$check->get_result()->num_rows>0;$check->close();
            if(!$exists) return [true,'Record not found.'];
            $st=$conn->prepare('INSERT IGNORE INTO patient_hidden_records(patient_id,record_id) VALUES(?,?)');$st->bind_param('ii',$uid,$id);$st->execute();$st->close();
            header('Location: '.strtok($_SERVER['REQUEST_URI'],'?').'?hidden=1');exit;
        }
        return [true,''];
    }catch(Throwable $e){return [false,'List hiding is temporarily unavailable. Your records remain safe.'];}
}
