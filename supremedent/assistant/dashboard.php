<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
function ac($c,$s){$r=$c->query($s);return $r?(int)($r->fetch_assoc()['c']??0):0;}
$p=ac($conn,"SELECT COUNT(*) c FROM users WHERE role='patient'");
$today=ac($conn,"SELECT COUNT(*) c FROM appointments WHERE appointment_date=CURDATE() AND status IN ('pending','approved')");
// Bookings are approved automatically, so this card counts upcoming confirmed bookings instead of pending requests.
$pending=ac($conn,"SELECT COUNT(*) c FROM appointments WHERE status IN ('pending','approved') AND appointment_date>=CURDATE()");
$charts=ac($conn,'SELECT COUNT(*) c FROM dental_charts');
$todayRows=[];$r=$conn->query("SELECT a.id,a.appointment_time,a.appointment_type,a.status,u.first_name,u.last_name FROM appointments a JOIN users u ON u.id=a.patient_id WHERE a.appointment_date=CURDATE() AND a.status IN ('pending','approved','completed') ORDER BY a.appointment_time");if($r)$todayRows=$r->fetch_all(MYSQLI_ASSOC);
// Latest clinic events the front desk cares about.
$activity=[];
$r=$conn->query("SELECT a.id,a.appointment_date,a.appointment_time,a.appointment_type,a.status,a.created_at,u.first_name,u.last_name FROM appointments a JOIN users u ON u.id=a.patient_id ORDER BY a.created_at DESC,a.id DESC LIMIT 6");
if($r)foreach($r->fetch_all(MYSQLI_ASSOC) as $a)$activity[]=['icon'=>$a['status']==='pending'?'warn':'calendar','title'=>($a['status']==='pending'?'New appointment request':'Appointment '.($a['status']==='rejected'?'declined':($a['status']==='approved'?'booked':$a['status']))).' · '.$a['first_name'].' '.$a['last_name'],'text'=>$a['appointment_type'].' on '.date('M d, Y',strtotime($a['appointment_date'])).' at '.date('h:i A',strtotime($a['appointment_time'])),'ts'=>strtotime($a['created_at']),'url'=>'appointment_view.php?id='.$a['id']];
$r=$conn->query("SELECT first_name,last_name,created_at FROM users WHERE role='patient' ORDER BY created_at DESC LIMIT 3");
if($r)foreach($r->fetch_all(MYSQLI_ASSOC) as $u)$activity[]=['icon'=>'check','title'=>'Patient registered · '.$u['first_name'].' '.$u['last_name'],'text'=>'New patient account in the directory.','ts'=>strtotime($u['created_at']),'url'=>'patients.php'];
$r=$conn->query("SELECT c.created_at,u.first_name,u.last_name FROM dental_charts c JOIN users u ON u.id=c.patient_id ORDER BY c.created_at DESC LIMIT 3");
if($r)foreach($r->fetch_all(MYSQLI_ASSOC) as $c)$activity[]=['icon'=>'record','title'=>'Dental chart saved · '.$c['first_name'].' '.$c['last_name'],'text'=>'Examination chart recorded.','ts'=>strtotime($c['created_at']),'url'=>'charts.php'];
usort($activity,static fn($a,$b)=>$b['ts']<=>$a['ts']);$activity=array_slice($activity,0,6);
$icons=['warn'=>'fa-bell','calendar'=>'fa-calendar-check','check'=>'fa-user-plus','record'=>'fa-file-medical'];
function when_label(int $ts):string{if(date('Y-m-d',$ts)===date('Y-m-d'))return date('h:i A',$ts);return date('M j',$ts);}
require '../includes/ui.php';ui_header('Assistant Dashboard','assistant');ui_sidebar('assistant','dashboard.php');?>
<section class="dashboard-hero"><div><div class="eyebrow">ASSISTANT PORTAL</div><h1>Welcome back, <?=e($_SESSION['first_name']??'Assistant')?>.</h1><p>Coordinate patient requests, schedules, registrations, and dental chart preparation.</p></div><div class="dashboard-date"><?=e(date('l'))?><b><?=e(date('M d'))?></b><span><?=e(date('Y'))?></span></div></section>
<section class="metric-grid"><a href="patients.php" class="metric-card"><span>Patients</span><b><?=$p?></b><small>Patient directory</small></a><a href="appointments.php?view=calendar" class="metric-card"><span>Today's Schedule</span><b><?=$today?></b><small>Appointments today</small></a><a href="appointments.php" class="metric-card"><span>Upcoming Bookings</span><b><?=$pending?></b><small>Approved automatically</small></a><a href="charts.php" class="metric-card"><span>Dental Charts</span><b><?=$charts?></b><small>Clinical chart records</small></a></section>
<div class="staff-dash-columns">
<section class="card staff-dash-panel"><div class="activity-head"><h2>Recent activity</h2><a href="appointments.php">See all</a></div><ul class="activity-list"><?php if(!$activity):?><li class="activity-placeholder"><span class="activity-icon empty"></span><div><b>Requests, registrations and charts will show up here</b><span>Each update appears in order.</span></div></li><?php endif;?><?php foreach($activity as $a):?><li><span class="activity-icon <?=e($a['icon'])?>"><i class="fa-solid <?=e($icons[$a['icon']]??'fa-circle')?>"></i></span><div><b><a href="<?=e($a['url'])?>" class="plain-link"><?=e($a['title'])?></a></b><span><?=e($a['text'])?></span></div><time><?=e(when_label($a['ts']))?></time></li><?php endforeach;?></ul></section>
<section class="card staff-dash-panel"><div class="activity-head"><h2>Today's schedule</h2><a href="appointments.php?view=calendar">Calendar</a></div><div class="today-list"><?php if(!$todayRows):?><div class="empty-note">No appointments scheduled for today.</div><?php endif;?><?php foreach($todayRows as $t):?><a class="today-row" href="appointment_view.php?id=<?=$t['id']?>"><time><?=e(date('h:i A',strtotime($t['appointment_time'])))?></time><span><b><?=e($t['first_name'].' '.$t['last_name'])?></b><small><?=e($t['appointment_type'])?></small></span><span class="status-pill <?=e($t['status'])?>"><?=e(ucfirst($t['status']))?></span></a><?php endforeach;?></div></section>
</div>
<?php ui_footer();?>
