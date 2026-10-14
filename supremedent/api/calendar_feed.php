<?php
/** Shared staff schedule feed. Session role, not query parameters, controls visibility. */
require_once '../config/security.php';
start_secure_session();
require_once '../config/database.php';
require_portal_role($conn, ['assistant','dentist'], true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');
$role=$_SESSION['role'] ?? '';
$userId=(int)($_SESSION['user_id'] ?? 0);
$events=[]; $blocks=[];
$columns='a.id,a.patient_id,a.dentist_id,a.appointment_date,a.appointment_time,a.appointment_type,a.reason,a.marked_teeth,a.status,u.first_name,u.last_name,u.email';
// Every staff calendar shows who booked each date and time (revision 2), not only the signed-in dentist's patients.
$stmt=$conn->prepare("SELECT $columns,d.first_name dentist_first,d.last_name dentist_last FROM appointments a JOIN users u ON u.id=a.patient_id LEFT JOIN users d ON d.id=a.dentist_id WHERE a.status IN ('pending','approved','completed') ORDER BY a.appointment_date,a.appointment_time");
$stmt->execute();
$result=$stmt->get_result();
while($entry=$result->fetch_assoc()) $events[]=$entry;
$stmt->close();
if ($role==='dentist') {
    $st=$conn->prepare('SELECT id,unavailable_date,start_time,end_time,all_day,reason FROM provider_unavailability WHERE user_id=? AND unavailable_date>=DATE_SUB(CURDATE(),INTERVAL 60 DAY) ORDER BY unavailable_date,start_time');
    $st->bind_param('i',$userId);
} else {
    $st=$conn->prepare("SELECT p.id,p.unavailable_date,p.start_time,p.end_time,p.all_day,p.reason,CONCAT(u.first_name,' ',u.last_name) staff_name FROM provider_unavailability p JOIN users u ON u.id=p.user_id AND u.role='dentist' WHERE p.unavailable_date>=DATE_SUB(CURDATE(),INTERVAL 60 DAY) ORDER BY p.unavailable_date,p.start_time");
}
$st->execute();
$res=$st->get_result();
while($entry=$res->fetch_assoc()) $blocks[]=$entry;
$st->close();
echo json_encode(['events'=>$events,'blocks'=>$blocks,'checked_at'=>date(DATE_ATOM)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
