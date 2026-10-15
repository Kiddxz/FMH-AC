<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

require_once '../config/security.php';start_secure_session();require_once '../config/database.php';
if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}
$msg='';$type='success';$roles=['patient','assistant','dentist','superadmin'];$statuses=['active','inactive'];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['id']??0);$status=$_POST['status']??'';$role=$_POST['role']??'';
    if(!csrf_valid($_POST['csrf_token']??null)){$msg='Your session expired. Refresh and try again.';$type='error';}
    elseif($id<=0||$id===(int)$_SESSION['user_id']||!in_array($status,$statuses,true)||!in_array($role,$roles,true)){$msg='Invalid user update. Your own active Super Admin account cannot be changed here.';$type='error';}
    else{$st=$conn->prepare('UPDATE users SET status=?,role=? WHERE id=?');$st->bind_param('ssi',$status,$role,$id);$st->execute();$st->close();log_activity($conn,$_SESSION['user_id'],'Updated user account','Users','User ID '.$id.' role='.$role.' status='.$status);$msg='User updated.';}
}
$rows=$conn->query('SELECT id,first_name,last_name,email,role,status,created_at FROM users ORDER BY created_at DESC');
require '../includes/ui.php';ui_header('Users','superadmin');ui_sidebar('superadmin','users.php');?>
<div class="page-head"><div><div class="eyebrow">USER MANAGEMENT</div><h1>Users</h1><div class="muted">Manage account roles and status. All roles sign in with email + password. OTP is used for registration and password recovery.</div></div><div style="display:flex;gap:8px"><a class="btn btn-primary" href="create_dentist.php">Create Dentist</a><a class="btn btn-secondary" href="create_assistant.php">Create Assistant</a></div></div>
<?php if($msg)flash($type,$msg);?>
<section class="card"><div class="table-wrap"><table class="table"><thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th><th>Update</th></tr></thead><tbody>
<?php while($r=$rows->fetch_assoc()):$formId='user-'.$r['id'];$self=(int)$r['id']===(int)$_SESSION['user_id'];?>
<tr><td><b><?=e($r['first_name'].' '.$r['last_name'])?></b></td><td><?=e($r['email'])?></td>
<td><select name="role" form="<?=$formId?>" <?=$self?'disabled':''?>><?php foreach($roles as $role):?><option value="<?=$role?>" <?=$r['role']===$role?'selected':''?>><?=e($role==='superadmin'?'Super Admin':ucfirst($role))?></option><?php endforeach;?></select></td>
<td><select name="status" form="<?=$formId?>" <?=$self?'disabled':''?>><?php foreach($statuses as $s):?><option value="<?=$s?>" <?=$r['status']===$s?'selected':''?>><?=e(ucfirst($s))?></option><?php endforeach;?></select></td>
<td><?=e(date('M d, Y',strtotime($r['created_at'])))?></td>
<td><?php if($self):?><span class="muted">Current account</span><?php else:?><form id="<?=$formId?>" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn btn-secondary">Save</button></form><?php endif;?></td></tr>
<?php endwhile;?></tbody></table></div></section><?php ui_footer();?>
