<?php
require_once __DIR__ . '/../config/security.php';
start_secure_session();
require_once __DIR__ . '/../config/database.php';
require_portal_role($conn, 'assistant', false);
require_once __DIR__ . '/../auth/otp_helpers.php';

/** Random temporary password that always passes valid_strong_password(). */
function sd_random_password(int $length = 10): string
{
    $sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%&*?'];
    $chars = [];
    foreach ($sets as $set) $chars[] = $set[random_int(0, strlen($set) - 1)];
    $all = implode('', $sets);
    while (count($chars) < $length) $chars[] = $all[random_int(0, strlen($all) - 1)];
    for ($i = count($chars) - 1; $i > 0; $i--) { $j = random_int(0, $i); [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]]; }
    return implode('', $chars);
}

$msg = '';
$type = '';
$old = ['first_name' => '', 'last_name' => '', 'email' => '', 'address' => '', 'birthday' => '', 'gender' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) $old[$k] = trim((string) ($_POST[$k] ?? ''));
    $fn = $old['first_name'];
    $ln = $old['last_name'];
    $email = strtolower($old['email']);
    $address = $old['address'];
    $birthday = $old['birthday'] !== '' ? $old['birthday'] : null;
    $gender = $old['gender'];
    $otp = trim((string) ($_POST['otp'] ?? ''));
    $namePattern = "/^[\\p{L}\\s.'-]{2,100}$/u";
    if (!csrf_valid($_POST['csrf_token'] ?? null)) {
        $msg = 'Your session expired. Refresh and try again.';
        $type = 'error';
    } elseif (!preg_match($namePattern, $fn) || !preg_match($namePattern, $ln)) {
        $msg = 'Enter a valid first and last name.';
        $type = 'error';
    } elseif (!valid_email_address($email)) {
        $msg = 'Enter a valid email address.';
        $type = 'error';
    } elseif ($birthday !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday) || strtotime($birthday) === false || strtotime($birthday) > time())) {
        $msg = 'Enter a valid birthday.';
        $type = 'error';
    } elseif ($gender !== '' && !in_array($gender, ['Male', 'Female'], true)) {
        $msg = 'Select a valid gender.';
        $type = 'error';
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $msg = 'Send a verification code to the patient\'s email and enter the 6-digit code.';
        $type = 'error';
    } else {
        $st = $conn->prepare('SELECT id FROM users WHERE email=?');
        $st->bind_param('s', $email);
        $st->execute();
        $exists = $st->get_result()->num_rows;
        $st->close();
        if ($exists) {
            $msg = 'This email is already registered.';
            $type = 'error';
        } elseif (!otp_consume($conn, $email, 'register', $otp)) {
            $msg = 'The email verification code is invalid or expired. Send a new code.';
            $type = 'error';
        } else {
            // The email is verified: create the account with a random password and email the login details.
            $password = sd_random_password();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $st = $conn->prepare("INSERT INTO users(first_name,last_name,email,password,role,status,must_change_password) VALUES(?,?,?,?,'patient','active',1)");
            $st->bind_param('ssss', $fn, $ln, $email, $hash);
            if ($st->execute()) {
                $newId = (int) $st->insert_id;
                $st->close();
                $ps = $conn->prepare('INSERT INTO patient_profiles(user_id,address,birthday,gender) VALUES(?,?,?,?)');
                $ps->bind_param('isss', $newId, $address, $birthday, $gender);
                $ps->execute();
                $ps->close();
                log_activity($conn, (int) $_SESSION['user_id'], 'Created patient', 'Patients', $email . ' (email verified)');
                if (sendPatientCredentialsEmail($email, trim($fn . ' ' . $ln), $password)) {
                    $msg = 'Patient account created. The username and a temporary password were emailed to ' . $email . '.';
                    $type = 'success';
                } else {
                    $msg = 'Patient account created, but the login email could not be sent. Ask the patient to use "Forgot password" on the login page to set a password.';
                    $type = 'error';
                }
                $old = ['first_name' => '', 'last_name' => '', 'email' => '', 'address' => '', 'birthday' => '', 'gender' => ''];
            } else {
                $st->close();
                $msg = 'Unable to create patient.';
                $type = 'error';
            }
        }
    }
}
require '../includes/ui.php';
ui_header('Register Patient', 'assistant');
ui_sidebar('assistant', 'register_patient.php'); ?>
<div class="page-head compact-head">
    <div>
        <div class="eyebrow">PATIENTS</div>
        <h1>Register Patient</h1>
        <div class="muted">Create a patient account for clinic registration.</div>
    </div>
</div><?php if ($msg)
    flash($type, $msg); ?>
<section class="card register-patient-card">
    <form method="post" class="register-patient-form" id="registerPatientForm" autocomplete="off" novalidate><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="rp-field"><label for="rpFirst">First Name *</label><input id="rpFirst" name="first_name" maxlength="100" placeholder="Enter first name" value="<?= e($old['first_name']) ?>" required></div>
        <div class="rp-field"><label for="rpLast">Last Name *</label><input id="rpLast" name="last_name" maxlength="100" placeholder="Enter last name" value="<?= e($old['last_name']) ?>" required></div>
        <div class="rp-field"><label for="rpEmail">Email *</label><input id="rpEmail" type="email" name="email" maxlength="150" placeholder="Enter email address" value="<?= e($old['email']) ?>" autocapitalize="none" spellcheck="false" required></div>
        <div class="rp-field"><label for="rpOtp">Email OTP</label><div class="rp-otp"><input id="rpOtp" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6-digit code" autocomplete="one-time-code"><button type="button" class="rp-send" id="rpSendOtp">Send OTP</button></div></div>
        <div class="rp-help full" id="rpHelp" role="status" aria-live="polite">Send a code to the patient's email. After the email is verified, the patient receives their username and a random password by email.</div>
        <div class="rp-field full"><label for="rpAddress">Address</label><input id="rpAddress" name="address" maxlength="255" placeholder="Enter complete address" value="<?= e($old['address']) ?>"></div>
        <div class="rp-field"><label for="rpBirthday">Birthday</label><input id="rpBirthday" type="date" name="birthday" max="<?= date('Y-m-d') ?>" value="<?= e($old['birthday']) ?>"></div>
        <div class="rp-field"><label for="rpGender">Gender</label><select id="rpGender" name="gender">
                <option value="">Select</option>
                <option <?= $old['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                <option <?= $old['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            </select></div>
        <div class="rp-actions full"><button class="btn btn-primary rp-create" type="submit"><i class="fa-solid fa-user-plus keep-icon"></i> Create Patient</button></div>
    </form>
</section>
<script>
(() => {
    const form = document.getElementById('registerPatientForm'), btn = document.getElementById('rpSendOtp'), help = document.getElementById('rpHelp');
    const email = document.getElementById('rpEmail'), otp = document.getElementById('rpOtp');
    const validEmail = v => /^[^\s@]+@[^\s@]+\.[a-z]{2,24}$/i.test(v.trim());
    let timer = null, deadline = 0, sending = false;
    function setHelp(text, cls) { help.textContent = text; help.className = 'rp-help full' + (cls ? ' ' + cls : ''); }
    function tick() { const s = Math.ceil((deadline - Date.now()) / 1000); if (s <= 0) { clearInterval(timer); btn.disabled = false; btn.textContent = 'Send OTP'; return; } btn.disabled = true; btn.textContent = 'Resend in ' + s + 's'; }
    otp.addEventListener('input', () => { otp.value = otp.value.replace(/\D/g, '').slice(0, 6); });
    email.addEventListener('input', () => { otp.value = ''; });
    btn.addEventListener('click', async () => {
        if (sending || Date.now() < deadline) return;
        if (!form.first_name.value.trim() || !form.last_name.value.trim()) { setHelp('Enter the patient\'s first and last name first.', 'bad'); return; }
        if (!validEmail(email.value)) { setHelp('Enter a valid email address first.', 'bad'); email.focus(); return; }
        sending = true; btn.disabled = true; btn.textContent = 'Sending...'; setHelp('Sending a verification code to ' + email.value.trim() + '...');
        try {
            const res = await fetch('send_register_otp.php', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' }, body: new URLSearchParams({ email: email.value.trim(), first_name: form.first_name.value.trim(), last_name: form.last_name.value.trim(), csrf_token: form.csrf_token.value }) });
            const data = await res.json().catch(() => ({ ok: false, message: 'Unexpected server response.' }));
            setHelp(data.message || (data.ok ? 'Code sent.' : 'Could not send the code.'), data.ok ? 'good' : 'bad');
            const wait = Math.max(0, Number(data.retry_after) || (data.ok ? 60 : 0));
            if (wait) { deadline = Date.now() + wait * 1000; tick(); timer = setInterval(tick, 1000); } else { btn.disabled = false; btn.textContent = 'Send OTP'; }
            if (data.ok) otp.focus();
        } catch (_) { setHelp('Could not send the code. Check the connection and try again.', 'bad'); btn.disabled = false; btn.textContent = 'Send OTP'; }
        finally { sending = false; }
    });
    form.addEventListener('submit', e => {
        if (!form.first_name.value.trim() || !form.last_name.value.trim() || !validEmail(email.value)) { e.preventDefault(); setHelp('Complete the first name, last name and a valid email.', 'bad'); return; }
        if (!/^\d{6}$/.test(otp.value)) { e.preventDefault(); setHelp('Enter the 6-digit code sent to the patient\'s email.', 'bad'); otp.focus(); }
    });
})();
</script>
<?php ui_footer(); ?>
