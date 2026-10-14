<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'dentist', false);

start_secure_session();
if (($_SESSION['role'] ?? '') !== 'dentist') { header('Location: ../auth/login.php'); exit; }
require_once '../config/database.php';
$q=trim($_GET['q']??'');
if($q!==''){
    $like='%'.$q.'%';
    $st=$conn->prepare("SELECT id,first_name,last_name,email,status,created_at FROM users WHERE role='patient' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR CAST(id AS CHAR) LIKE ?) ORDER BY last_name,first_name");
    $st->bind_param('ssss',$like,$like,$like,$like);$st->execute();$result=$st->get_result();
}else{$result=$conn->query("SELECT id,first_name,last_name,email,status,created_at FROM users WHERE role='patient' ORDER BY last_name,first_name");}
require '../includes/ui.php';ui_header('Patients','dentist');ui_sidebar('dentist','patients.php');
?>
<div class="page-head"><div><div class="eyebrow">PATIENT DIRECTORY</div><h1>Patients</h1><div class="muted">Search registered patients and open their chart records and treatment information.</div></div><span class="directory-count"><b><?=$result?$result->num_rows:0?></b> Results</span></div>
<section class="card"><form class="directory-search" method="get"><label class="sr-only" for="patientSearch">Search patients</label><input id="patientSearch" name="q" value="<?=e($q)?>" placeholder="Search by patient name, email, or ID"><button class="btn btn-primary" type="submit">Search</button><?php if($q!==''):?><a class="btn btn-secondary" href="patients.php">Clear</a><?php endif;?></form></section>
<section class="card"><div class="card-heading"><div><h2>Registered Patients</h2><p class="muted">Select a patient to review chart records, self-reported dental conditions, and treatment plans.</p></div></div><div class="table-wrap"><table class="table patient-directory"><thead><tr><th>Patient</th><th>Email</th><th>Status</th><th>Registered</th><th>Action</th></tr></thead><tbody>
<?php if(!$result||$result->num_rows===0):?><tr><td colspan="5" class="empty">No patients matched your search.</td></tr><?php endif;?>
<?php if($result): while($patient=$result->fetch_assoc()):?><tr><td><span class="patient-initial"><?=e(strtoupper(substr($patient['first_name'],0,1)))?></span><span><b><?=e($patient['first_name'].' '.$patient['last_name'])?></b><br><small class="muted">Patient ID: <?=e($patient['id'])?></small></span></td><td><?=e($patient['email'])?></td><td><span class="badge status-<?=e(strtolower($patient['status']))?>"><?=e(ucfirst($patient['status']))?></span></td><td><?=e(date('M d, Y',strtotime($patient['created_at'])))?></td><td><a class="btn btn-primary" href="patient_view.php?id=<?=(int)$patient['id']?>">Manage Care</a></td></tr><?php endwhile; endif;?>
</tbody></table></div></section>
<?php if(isset($st))$st->close();ui_footer();?>
