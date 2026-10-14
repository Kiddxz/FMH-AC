<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'superadmin', false);

require_once '../config/security.php';start_secure_session();require_once '../config/database.php';
if(($_SESSION['role']??'')!=='superadmin'){header('Location: ../auth/login.php');exit;}
$msg='';$type='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $first=trim($_POST['first_name']??'');$last=trim($_POST['last_name']??'');$email=strtolower(trim($_POST['email']??''));$pass=$_POST['password']??'';$confirm=$_POST['confirm_password']??'';$namePattern="/^[\\p{L}\\s.'-]{2,100}$/u";
 if(!csrf_valid($_POST['csrf_token']??null)){$msg='Your session expired. Refresh and try again.';$type='error';}
 elseif(!preg_match($namePattern,$first)||!preg_match($namePattern,$last)){$msg='Enter a valid first and last name.';$type='error';}
 elseif(!valid_email_address($email)){$msg='Please enter a valid email.';$type='error';}
 elseif(!valid_strong_password($pass)){$msg='Password must be 8-128 characters and include uppercase, lowercase, a number, and a special character.';$type='error';}
 elseif($pass!==$confirm){$msg='Passwords do not match.';$type='error';}
 else{$check=$conn->prepare('SELECT id FROM users WHERE email=? LIMIT 1');$check->bind_param('s',$email);$check->execute();$exists=$check->get_result()->num_rows>0;$check->close();if($exists){$msg='This email is already registered.';$type='error';}else{$hash=password_hash($pass,PASSWORD_DEFAULT);$role='assistant';$status='active';$st=$conn->prepare('INSERT INTO users(first_name,last_name,email,password,role,status,must_change_password) VALUES(?,?,?,?,?,?,1)');$st->bind_param('ssssss',$first,$last,$email,$hash,$role,$status);if($st->execute()){log_activity($conn,$_SESSION['user_id'],'Created assistant account','User Management','Created '.$email);$msg='Assistant account created successfully. The assistant signs in with this email and the password you set, then must choose a new private password.';$type='success';$_POST=[];}else{$msg='Unable to create assistant account.';$type='error';}$st->close();}}
}
require '../includes/ui.php';ui_header('Create Assistant','superadmin');ui_sidebar('superadmin','users.php');?>
<div class="page-head"><div><div class="eyebrow">USER MANAGEMENT</div><h1>Create Assistant</h1><div class="muted">Only the Super Admin can create assistant accounts.</div></div><a class="btn btn-secondary" href="users.php">Back to Users</a></div><?php if($msg)flash($type,$msg);?>
<section class="card"><div class="form-title"><div class="icon-tile">👩‍💼</div><div><h2>New Assistant Account</h2><p class="muted">The assistant will sign in using email and password. They will be asked to change this temporary password on first sign-in.</p></div></div><form method="post" class="form-grid" autocomplete="off"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="field"><label>First Name *</label><input name="first_name" maxlength="100" required value="<?=e($_POST['first_name']??'')?>"></div><div class="field"><label>Last Name *</label><input name="last_name" maxlength="100" required value="<?=e($_POST['last_name']??'')?>"></div><div class="field full"><label>Email Address *</label><input type="email" name="email" maxlength="150" autocomplete="off" autocapitalize="none" spellcheck="false" required value="<?=e($_POST['email']??'')?>"></div><div class="field"><label>Password *</label><input type="password" name="password" maxlength="128" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{8,128}" title="Use 8+ characters with uppercase, lowercase, number, and special character." required></div><div class="field"><label>Confirm Password *</label><input type="password" name="confirm_password" maxlength="128" autocomplete="new-password" data-lpignore="true" data-1p-ignore="true" required></div><div class="actions full"><button class="btn btn-primary">Create Assistant Account</button><a class="btn btn-secondary" href="users.php">Cancel</a></div></form></section><?php ui_footer();?>
