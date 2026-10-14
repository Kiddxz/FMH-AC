<?php
/**
 * Profile settings for the assistant, dentist and super admin portals.
 * The including page has already run require_portal_role(). Changing the sign-in email or the
 * password requires the current password, so a borrowed or stolen session cannot take over the account.
 */
if(!function_exists('valid_email_address')) require_once '../config/security.php';
if(!isset($conn))require_once '../config/database.php';
require_once __DIR__ . '/../config/mail.php';
$role=$_SESSION['role']??'';$uid=(int)($_SESSION['user_id']??0);if(!$uid){header('Location: ../auth/login.php');exit;}
$message='';$messageType='';
$st=$conn->prepare('SELECT first_name,last_name,email,password FROM users WHERE id=? LIMIT 1');$st->bind_param('i',$uid);$st->execute();$user=$st->get_result()->fetch_assoc();$st->close();
if($_SERVER['REQUEST_METHOD']==='POST'){
    $first=trim($_POST['first_name']??'');$last=trim($_POST['last_name']??'');$email=strtolower(trim($_POST['email']??''));
    $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
    $namePattern="/^[\\p{L}\\s.'-]{2,100}$/u";
    $emailChanged=$email!==strtolower((string)$user['email']);
    $needsPassword=$emailChanged||$new!=='';
    if(!csrf_valid($_POST['csrf_token']??null)){$message='Your session expired. Refresh and try again.';$messageType='error';}
    elseif(!preg_match($namePattern,$first)||!preg_match($namePattern,$last)||!valid_email_address($email)){$message='Enter a valid name and email address.';$messageType='error';}
    elseif($needsPassword&&!password_verify($current,(string)$user['password'])){usleep(250000);$message='Enter your current password to change your email address or password.';$messageType='error';log_activity($conn,$uid,'Profile change refused','Security','Wrong current password');}
    elseif($new!==''&&!valid_strong_password($new)){$message='New password must be 8-128 characters with uppercase, lowercase, a number and a special character, and must not be a common password.';$messageType='error';}
    elseif($new!==''&&$new!==$confirm){$message='The new passwords do not match.';$messageType='error';}
    else{
        $check=$conn->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');$check->bind_param('si',$email,$uid);$check->execute();$exists=$check->get_result()->num_rows>0;$check->close();
        if($exists){$message='That email address cannot be used.';$messageType='error';}
        else{
            $st=$conn->prepare('UPDATE users SET first_name=?,last_name=?,email=? WHERE id=?');$st->bind_param('sssi',$first,$last,$email,$uid);$st->execute();$st->close();
            if($new!==''){
                $hash=password_hash($new,PASSWORD_DEFAULT);
                $st=$conn->prepare('UPDATE users SET password=?,must_change_password=0,password_changed_at=NOW() WHERE id=?');$st->bind_param('si',$hash,$uid);$st->execute();$st->close();
                session_regenerate_id(true);
                $_SESSION['auth_fp']=sd_auth_fingerprint($hash);
                log_activity($conn,$uid,'Password changed','Security','Changed from profile settings');
            }
            if($emailChanged){
                log_activity($conn,$uid,'Sign-in email changed','Security','From '.$user['email'].' to '.$email);
                // Alert the old address so the real owner notices an unexpected change.
                sendSecurityNoticeEmail((string)$user['email'],$first,'Your SupremeDent sign-in email was changed','The sign-in email of your SupremeDent account was changed to '.$email.' on '.date('M d, Y h:i A').'.');
            }
            $_SESSION['first_name']=$first;$_SESSION['last_name']=$last;$_SESSION['email']=$email;
            $user=['first_name'=>$first,'last_name'=>$last,'email'=>$email,'password'=>$user['password']];
            $message='Profile settings updated.'.($new!==''?' Other devices signed in to your account were signed out.':'');$messageType='success';
        }
    }
}
require '../includes/ui.php';ui_header('Profile Settings',$role);ui_sidebar($role,'profile.php');?>
<div class="page-head"><div><div class="eyebrow"><?=e(strtoupper($role))?> PROFILE</div><h1>Profile Settings</h1><div class="muted">Update your account details. Your current password is required to change your email address or password.</div></div></div><?php if($message)flash($messageType,$message);?><section class="profile-settings-grid"><div class="card profile-summary"><span class="profile-avatar-large"><?=e(strtoupper(substr($user['first_name']??'U',0,1)))?></span><h2><?=e(($user['first_name']??'').' '.($user['last_name']??''))?></h2><p><?=e(ucfirst($role))?></p><small><?=e($user['email']??'')?></small></div><div class="card"><form method="post" class="form-grid" autocomplete="off"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="field"><label>First Name</label><input name="first_name" maxlength="100" value="<?=e($user['first_name']??'')?>" required></div><div class="field"><label>Last Name</label><input name="last_name" maxlength="100" value="<?=e($user['last_name']??'')?>" required></div><div class="field full"><label>Email Address</label><input type="email" name="email" maxlength="150" value="<?=e($user['email']??'')?>" required></div><div class="field full"><label>Current Password <small class="muted">(required to change email or password)</small></label><input type="password" name="current_password" maxlength="128" autocomplete="current-password"></div><div class="field"><label>New Password</label><input type="password" name="new_password" maxlength="128" autocomplete="new-password"></div><div class="field"><label>Confirm New Password</label><input type="password" name="confirm_password" maxlength="128" autocomplete="new-password"></div><div class="actions full"><button class="btn btn-primary">Save Changes</button></div></form></div></section><?php ui_footer();?>
