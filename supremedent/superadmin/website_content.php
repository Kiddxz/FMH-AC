<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

start_secure_session();require_once '../config/database.php';if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}
$msg='';$type='success';
$keys=['clinic_name','clinic_phone','clinic_email','clinic_address','clinic_hours','appointment_start_time','appointment_end_time','appointment_slot_minutes','booking_lead_hours','laboratory_enabled'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['clinic_email']??'');$slot=(int)($_POST['appointment_slot_minutes']??60);$lead=(int)($_POST['booking_lead_hours']??3);
    if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)){$msg='Please enter a valid clinic email address.';$type='error';}
    elseif($slot<15||$slot>240||$lead<0||$lead>72){$msg='Please check the appointment slot and lead-time settings.';$type='error';}
    else{foreach($keys as $k){$v=$k==='laboratory_enabled'?(isset($_POST[$k])?'1':'0'):trim($_POST[$k]??'');$st=$conn->prepare('INSERT INTO clinic_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');$st->bind_param('ss',$k,$v);$st->execute();$st->close();}$msg='Website and appointment settings updated.';}
}
$settings=clinic_settings($conn);require '../includes/ui.php';ui_header('Website Content','superadmin');ui_sidebar('superadmin','website_content.php');
?>
<div class="page-head"><div><div class="eyebrow">WEBSITE & BOOKING</div><h1>Website Content</h1><div class="muted">Update public clinic details and appointment availability rules.</div></div></div><?php if($msg)flash($type,$msg);?>
<section class="card"><div class="card-heading"><div><h2>Public Clinic Information</h2><p class="muted">These details appear on the landing page contact section and footer.</p></div></div><form method="post" class="form-grid"><div class="field"><label>Clinic Name</label><input name="clinic_name" value="<?=e($settings['clinic_name']??'')?>"></div><div class="field"><label>Phone</label><input name="clinic_phone" value="<?=e($settings['clinic_phone']??'')?>"></div><div class="field"><label>Email</label><input type="email" name="clinic_email" value="<?=e($settings['clinic_email']??'')?>"></div><div class="field"><label>Clinic Hours</label><input name="clinic_hours" value="<?=e($settings['clinic_hours']??'')?>"></div><div class="field full"><label>Address</label><input name="clinic_address" value="<?=e($settings['clinic_address']??'')?>"></div>
<div class="field"><label>Appointment Start Time</label><input type="time" name="appointment_start_time" value="<?=e($settings['appointment_start_time']??'09:00')?>" required></div><div class="field"><label>Appointment End Time</label><input type="time" name="appointment_end_time" value="<?=e($settings['appointment_end_time']??'17:00')?>" required></div><div class="field"><label>Slot Duration (minutes)</label><input type="number" min="15" max="240" step="15" name="appointment_slot_minutes" value="<?=e($settings['appointment_slot_minutes']??'60')?>" required></div><div class="field"><label>Booking Cutoff / Lead Time (hours)</label><input type="number" min="0" max="72" name="booking_lead_hours" value="<?=e($settings['booking_lead_hours']??'3')?>" required></div><div class="field full"><label class="setting-toggle"><input type="checkbox" name="laboratory_enabled" <?=($settings['laboratory_enabled']??'0')==='1'?'checked':''?>><span>Enable Laboratory module for Dentist</span></label><small class="muted">Disabled by default based on the latest scope review. Existing laboratory data is preserved.</small></div><div class="actions full"><button class="btn btn-primary">Save Website & Booking Settings</button></div></form></section>
<style>.setting-toggle{display:flex!important;align-items:center;gap:9px}.setting-toggle input{width:auto!important}</style>
<?php ui_footer(); ?>
