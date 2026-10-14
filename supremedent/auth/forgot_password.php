<?php
require_once __DIR__.'/../config/security.php';
start_secure_session();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/otp_helpers.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$msg='';$type='error';$email=strtolower(trim($_POST['email']??''));
if($_SERVER['REQUEST_METHOD']==='POST'){
    $otp=trim($_POST['otp']??'');
    if(!csrf_valid($_POST['csrf_token']??null))$msg='Your session expired. Refresh and try again.';
    elseif(!valid_email_address($email)||!preg_match('/^\d{6}$/',$otp))$msg='Enter a valid email and 6-digit OTP. Check for typing errors such as gmail.com.com.';
    else{
        $st=$conn->prepare("SELECT id FROM users WHERE email=? AND status='active' LIMIT 1");$st->bind_param('s',$email);$st->execute();$u=$st->get_result()->fetch_assoc();$st->close();
        if(!$u||!otp_consume($conn,$email,'reset',$otp)){$msg='The reset code is invalid or expired. Request a new reset email.';}
        else{session_regenerate_id(true);$_SESSION['reset_user_id']=(int)$u['id'];$_SESSION['reset_verified_at']=time();header('Location: reset_password.php');exit;}
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Forgot Password | SupremeDent</title><link rel="stylesheet" href="../assets/css/styles.css"><link rel="stylesheet" href="../assets/css/app.css"><link rel="stylesheet" href="../assets/css/revamp.css"><link rel="stylesheet" href="../assets/css/ui_fixes.css"><link rel="stylesheet" href="../assets/css/revisions_2026_10.css?v=20261002"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head><body>
<div class="auth-page auth-stacked"><div class="auth-shell"><aside class="auth-welcome"><div class="auth-welcome-content"><div class="auth-logo"><img src="../assets/images/logo.png" alt="SupremeDent"></div><h1>Password Recovery</h1><p>We will email you a password reset link and a 6-digit code.</p><div class="auth-benefits"><span><i class="fa-solid fa-envelope-circle-check"></i> Reset email with link + code</span><span><i class="fa-solid fa-clock"></i> 10-minute code expiry</span><span><i class="fa-solid fa-key"></i> One-time reset session</span></div></div></aside><main class="auth-form-panel"><a class="auth-back" href="login.php"><i class="fa-solid fa-arrow-left"></i> Back to Login</a><div class="auth-form-wrap"><div class="auth-wordmark">SUPREMEDENT</div><p class="auth-kicker">Forgot password</p>
<?php if($msg):?><div class="auth-message is-<?=e($type)?>" style="display:block"><?=e($msg)?></div><?php endif;?>
<form method="post" autocomplete="off" id="forgotForm"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><div class="auth-field"><label for="email">Email Address</label><input id="email" class="input-field" type="email" name="email" value="<?=e($email)?>" maxlength="150" autocomplete="off" autocapitalize="none" spellcheck="false" required><div class="field-feedback" id="emailFeedback"></div></div><div class="auth-field"><label for="otp">Reset Code</label><div class="otp-inline"><input id="otp" class="input-field" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" autocomplete="one-time-code" required><button type="button" class="otp-send" id="sendOtp">Send Email</button></div><div class="otp-help" id="otpHelp">Click Send Email. We will email a reset link and a 6-digit code to your registered address.</div></div><button class="btn btn-primary" type="submit"><i class="fa-solid fa-unlock-keyhole"></i> Continue</button></form></div></main></div></div>
<script src="../assets/js/auth_otp.js?v=20261009"></script>
<script>
function validSupremeDentEmail(value){value=value.trim();if(value.length<5||value.length>150||value.split('@').length!==2)return false;const [local,domainRaw]=value.split('@'),domain=domainRaw.toLowerCase();if(!/^[A-Za-z0-9.!#$%&'*+/=?^_`{|}~-]+$/.test(local)||local.includes('..'))return false;if(!/^(?=.{3,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/i.test(domain)||domain.includes('..'))return false;if(/\.(com|net|org|edu|gov|ph)\.\1$/i.test(domain))return false;const providers={'gmail.':['gmail.com'],'googlemail.':['googlemail.com'],'outlook.':['outlook.com'],'hotmail.':['hotmail.com'],'icloud.':['icloud.com']};for(const [prefix,allowed] of Object.entries(providers)){if(domain.startsWith(prefix)&&!allowed.includes(domain))return false}return true}
const email=document.getElementById('email'),feedback=document.getElementById('emailFeedback');
function validateEmail(){const v=email.value.trim();if(!v){feedback.textContent='';feedback.className='field-feedback';email.setCustomValidity('');return false}const ok=validSupremeDentEmail(v);feedback.textContent=ok?'Email format looks valid.':'Check the email format. Example: name@gmail.com.';feedback.className='field-feedback '+(ok?'good':'bad');email.setCustomValidity(ok?'':'Enter a valid email address.');return ok}email.addEventListener('input',validateEmail);email.addEventListener('blur',validateEmail);
SupremeDentOtp.bind('forgotForm', 'reset', validateEmail);
document.getElementById('otp').addEventListener('input',e=>e.target.value=e.target.value.replace(/\D/g,'').slice(0,6));document.getElementById('forgotForm').addEventListener('submit',e=>{if(!validateEmail()){e.preventDefault();email.focus()}});
</script></body></html>
